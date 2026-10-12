<?php
/*
 * @copyright 2026 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

declare(strict_types=1);

namespace OCA\HtmlViewer\Tests\Unit\Service;

use Exception;
use OCA\HtmlViewer\Dto\DirectEditingFile;
use OCA\HtmlViewer\Dto\FileContent;
use OCA\HtmlViewer\Dto\FileSizeLimits;
use OCA\HtmlViewer\Exception\FileTooLargeException;
use OCA\HtmlViewer\Exception\InvalidTokenException;
use OCA\HtmlViewer\Service\DirectEditingService;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCP\DirectEditing\IManager;
use OCP\DirectEditing\IToken;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lock\LockedException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectEditingService::class)]
#[AllowMockObjectsWithoutExpectations]
class DirectEditingServiceTest extends TestCase {

    protected const TOKEN = 'aB3dE5gH7jK9mN1pQ3sT5vW7yZ9bC2eF4hJ6kM8nP0rS2uV4xY6zA8cD0fG2hJ4k';

    protected const MAX_SIZE = 100;

    protected const MAX_EDITOR_SIZE = 50;

    protected IManager&MockObject $manager;

    protected IUserManager&Stub $userManager;

    protected IUserSession&MockObject $userSession;

    protected DirectEditingService $service;

    protected function setUp(): void {
        $this->manager = $this->createMock(IManager::class);
        $this->userManager = $this->createStub(IUserManager::class);
        $this->userSession = $this->createMock(IUserSession::class);

        $settings = $this->createStub(SettingsServiceInterface::class);
        $settings->method('getFileSizeLimits')->willReturn(new FileSizeLimits(self::MAX_SIZE, self::MAX_EDITOR_SIZE, 10));

        $this->service = new DirectEditingService($this->manager, $this->userManager, $this->userSession, $settings);
    }

    public function testOpenFileReturnsFileOfToken(): void {
        $file = $this->createFile(size: 42, updateable: true);
        $this->expectTokenLookup($this->createToken($file));

        $result = $this->service->openFile(self::TOKEN);

        $this->assertEquals(new DirectEditingFile(self::TOKEN, 'page.html', 42, true), $result);
    }

    public function testOpenFileReportsReadOnlyFile(): void {
        $this->expectTokenLookup($this->createToken($this->createFile(updateable: false)));

        $this->assertFalse($this->service->openFile(self::TOKEN)->editable);
    }

    public function testValidTokenIsExtended(): void {
        $token = $this->createMock(IToken::class);
        $token->method('getEditor')->willReturn('htmlviewer');
        $token->method('hasBeenAccessed')->willReturn(true);
        $token->method('getFile')->willReturn($this->createFile());
        $token->expects($this->once())->method('extend');
        $this->expectTokenLookup($token);

        $this->service->openFile(self::TOKEN);
    }

    public static function malformedTokens(): array {
        return [
            'empty'             => [''],
            'too short'         => [substr(self::TOKEN, 1)],
            'too long'          => [self::TOKEN.'a'],
            'special character' => [substr(self::TOKEN, 1).'-'],
            'whitespace'        => [substr(self::TOKEN, 1).' '],
            'trailing newline'  => [self::TOKEN."\n"],
            'non ascii'         => [substr(self::TOKEN, 2).'ä'],
            'path traversal'    => ['../../'.substr(self::TOKEN, 6)],
        ];
    }

    #[DataProvider('malformedTokens')]
    public function testMalformedTokenIsRejectedWithoutLookup(string $token): void {
        $this->manager->expects($this->never())->method('getToken');

        $this->expectException(InvalidTokenException::class);
        $this->service->openFile($token);
    }

    public function testUnknownTokenIsRejected(): void {
        $this->manager->expects($this->once())->method('getToken')->willThrowException(new Exception('Invalid token'));

        $this->expectException(InvalidTokenException::class);
        $this->service->openFile(self::TOKEN);
    }

    public static function unusableTokens(): array {
        return [
            'other editor'      => ['text', true],
            'not accessed'      => ['htmlviewer', false],
            'other and unused'  => ['text', false],
        ];
    }

    #[DataProvider('unusableTokens')]
    public function testUnusableTokenIsRejectedAndNotExtended(string $editor, bool $accessed): void {
        $token = $this->createMock(IToken::class);
        $token->method('getEditor')->willReturn($editor);
        $token->method('hasBeenAccessed')->willReturn($accessed);
        $token->expects($this->never())->method('extend');
        $token->expects($this->never())->method('getFile');
        $this->expectTokenLookup($token);

        $this->expectException(InvalidTokenException::class);
        $this->service->readFile(self::TOKEN);
    }

    public function testTokenWithUnavailableFileIsRejected(): void {
        $token = $this->createStub(IToken::class);
        $token->method('getEditor')->willReturn('htmlviewer');
        $token->method('hasBeenAccessed')->willReturn(true);
        $token->method('getFile')->willThrowException(new NotFoundException());
        $this->expectTokenLookup($token);

        $this->expectException(InvalidTokenException::class);
        $this->service->readFile(self::TOKEN);
    }

    public static function unsupportedMimetypes(): array {
        return [
            'plain text' => ['text/plain'],
            'xhtml'      => ['application/xhtml+xml'],
            'svg'        => ['image/svg+xml'],
            'uppercase'  => ['TEXT/HTML'],
        ];
    }

    #[DataProvider('unsupportedMimetypes')]
    public function testTokenWithUnsupportedFileIsRejected(string $mimetype): void {
        $file = $this->createMock(File::class);
        $file->method('getMimeType')->willReturn($mimetype);
        $file->expects($this->never())->method('getContent');
        $this->expectTokenLookup($this->createToken($file));

        $this->expectException(InvalidTokenException::class);
        $this->service->readFile(self::TOKEN);
    }

    public function testReadFileReturnsContent(): void {
        $this->expectTokenLookup($this->createToken($this->createFile(content: '<p>Hello</p>')));

        $this->assertEquals(new FileContent('<p>Hello</p>'), $this->service->readFile(self::TOKEN));
    }

    public function testReadFileAllowsFileWithMaximumSize(): void {
        $this->expectTokenLookup($this->createToken($this->createFile(size: self::MAX_SIZE)));

        $this->assertSame('<p>Hello</p>', $this->service->readFile(self::TOKEN)->content);
    }

    public function testReadFileRejectsTooLargeFile(): void {
        $file = $this->createFile(size: self::MAX_SIZE + 1);
        $file->expects($this->never())->method('getContent');
        $this->expectTokenLookup($this->createToken($file));

        $this->expectException(FileTooLargeException::class);
        $this->service->readFile(self::TOKEN);
    }

    public function testReadFileRejectsUnreadableFile(): void {
        $file = $this->createFile(readable: false);
        $file->expects($this->never())->method('getContent');
        $this->expectTokenLookup($this->createToken($file));

        $this->expectException(NotPermittedException::class);
        $this->service->readFile(self::TOKEN);
    }

    public function testReadFileRejectsLockedFile(): void {
        $file = $this->createFile();
        $file->method('getContent')->willThrowException(new LockedException('page.html'));
        $this->expectTokenLookup($this->createToken($file));

        $this->expectException(NotPermittedException::class);
        $this->service->readFile(self::TOKEN);
    }

    public function testWriteFileStoresContentAsTokenUser(): void {
        $tokenUser = $this->createStub(IUser::class);
        $sessionUser = $this->createStub(IUser::class);
        $this->userManager->method('get')->willReturnMap([['alice', $tokenUser]]);
        $this->userSession->method('getUser')->willReturn($sessionUser);

        $activeUsers = [];
        $writtenAs = null;
        $this->userSession->expects($this->exactly(2))
                          ->method('setVolatileActiveUser')
                          ->willReturnCallback(function (?IUser $user) use (&$activeUsers) {
                              $activeUsers[] = $user;
                          });

        $file = $this->createFile();
        $file->expects($this->once())
             ->method('putContent')
             ->with('<p>New</p>')
             ->willReturnCallback(function () use (&$activeUsers, &$writtenAs) {
                 $writtenAs = end($activeUsers);
             });
        $this->expectTokenLookup($this->createToken($file));

        $this->service->writeFile(self::TOKEN, new FileContent('<p>New</p>'));

        $this->assertSame($tokenUser, $writtenAs);
        $this->assertSame([$tokenUser, $sessionUser], $activeUsers);
    }

    public function testWriteFileRestoresMissingSessionUser(): void {
        $tokenUser = $this->createStub(IUser::class);
        $this->userManager->method('get')->willReturn($tokenUser);
        $this->userSession->method('getUser')->willReturn(null);

        $activeUsers = [];
        $this->userSession->method('setVolatileActiveUser')
                          ->willReturnCallback(function (?IUser $user) use (&$activeUsers) {
                              $activeUsers[] = $user;
                          });
        $this->expectTokenLookup($this->createToken($this->createFile()));

        $this->service->writeFile(self::TOKEN, new FileContent('<p>New</p>'));

        $this->assertSame([$tokenUser, null], $activeUsers);
    }

    public function testWriteFileAllowsContentWithMaximumSize(): void {
        $this->userManager->method('get')->willReturn($this->createStub(IUser::class));
        $file = $this->createFile();
        $file->expects($this->once())->method('putContent');
        $this->expectTokenLookup($this->createToken($file));

        // 25 two byte characters are exactly the limit of 50 bytes
        $this->service->writeFile(self::TOKEN, new FileContent(str_repeat('ä', 25)));
    }

    public function testWriteFileMeasuresSizeInBytes(): void {
        $file = $this->createFile();
        $file->expects($this->never())->method('putContent');
        $this->expectTokenLookup($this->createToken($file));
        $this->userSession->expects($this->never())->method('setVolatileActiveUser');

        // 26 characters, but 52 bytes
        $this->expectException(FileTooLargeException::class);
        $this->service->writeFile(self::TOKEN, new FileContent(str_repeat('ä', 26)));
    }

    public function testWriteFileRejectsReadOnlyFile(): void {
        $file = $this->createFile(updateable: false);
        $file->expects($this->never())->method('putContent');
        $this->expectTokenLookup($this->createToken($file));
        $this->userSession->expects($this->never())->method('setVolatileActiveUser');

        $this->expectException(NotPermittedException::class);
        $this->service->writeFile(self::TOKEN, new FileContent('<p>New</p>'));
    }

    public function testWriteFileRejectsTokenOfDeletedUser(): void {
        $this->userManager->method('get')->willReturn(null);
        $file = $this->createFile();
        $file->expects($this->never())->method('putContent');
        $this->expectTokenLookup($this->createToken($file));
        $this->userSession->expects($this->never())->method('setVolatileActiveUser');

        $this->expectException(InvalidTokenException::class);
        $this->service->writeFile(self::TOKEN, new FileContent('<p>New</p>'));
    }

    public function testWriteFileRejectsMalformedToken(): void {
        $this->manager->expects($this->never())->method('getToken');

        $this->expectException(InvalidTokenException::class);
        $this->service->writeFile('invalid', new FileContent('<p>New</p>'));
    }

    public static function writeErrors(): array {
        return [
            'locked'        => [new LockedException('page.html'), NotPermittedException::class],
            'not permitted' => [new NotPermittedException(), NotPermittedException::class],
        ];
    }

    /**
     * @param class-string<\Throwable> $expected
     */
    #[DataProvider('writeErrors')]
    public function testWriteFileRestoresSessionUserAfterError(Exception $error, string $expected): void {
        $tokenUser = $this->createStub(IUser::class);
        $sessionUser = $this->createStub(IUser::class);
        $this->userManager->method('get')->willReturn($tokenUser);
        $this->userSession->method('getUser')->willReturn($sessionUser);

        $activeUsers = [];
        $this->userSession->method('setVolatileActiveUser')
                          ->willReturnCallback(function (?IUser $user) use (&$activeUsers) {
                              $activeUsers[] = $user;
                          });

        $file = $this->createFile();
        $file->method('putContent')->willThrowException($error);
        $this->expectTokenLookup($this->createToken($file));

        try {
            $this->service->writeFile(self::TOKEN, new FileContent('<p>New</p>'));
            $this->fail('The write error was not passed on');
        } catch(\Throwable $e) {
            $this->assertInstanceOf($expected, $e);
        }

        $this->assertSame([$tokenUser, $sessionUser], $activeUsers);
    }

    protected function createFile(
        int    $size = 12,
        bool   $readable = true,
        bool   $updateable = true,
        string $content = '<p>Hello</p>',
    ): File&MockObject {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('page.html');
        $file->method('getMimeType')->willReturn('text/html');
        $file->method('getSize')->willReturn($size);
        $file->method('isReadable')->willReturn($readable);
        $file->method('isUpdateable')->willReturn($updateable);
        $file->method('getContent')->willReturn($content);

        return $file;
    }

    protected function createToken(File $file): IToken&Stub {
        $token = $this->createStub(IToken::class);
        $token->method('getEditor')->willReturn('htmlviewer');
        $token->method('hasBeenAccessed')->willReturn(true);
        $token->method('getUser')->willReturn('alice');
        $token->method('getFile')->willReturn($file);

        return $token;
    }

    protected function expectTokenLookup(IToken $token): void {
        $this->manager->expects($this->once())
                      ->method('getToken')
                      ->with(self::TOKEN)
                      ->willReturn($token);
    }
}
