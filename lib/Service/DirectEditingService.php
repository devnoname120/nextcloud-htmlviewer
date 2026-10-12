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

namespace OCA\HtmlViewer\Service;

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Dto\DirectEditingFile;
use OCA\HtmlViewer\Dto\FileContent;
use OCA\HtmlViewer\Exception\FileTooLargeException;
use OCA\HtmlViewer\Exception\InvalidTokenException;
use OCP\DirectEditing\IManager;
use OCP\DirectEditing\IToken;
use OCP\Files\File;
use OCP\Files\NotPermittedException;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Lock\LockedException;
use Throwable;

class DirectEditingService implements DirectEditingServiceInterface {

    /**
     * Tokens are created by the direct editing manager with 64 characters of ISecureRandom::CHAR_HUMAN_READABLE
     */
    protected const TOKEN_PATTERN = '/^[A-Za-z0-9]{64}$/D';

    public function __construct(
        protected IManager                 $directEditingManager,
        protected IUserManager             $userManager,
        protected IUserSession             $userSession,
        protected SettingsServiceInterface $settings,
    ) {
    }

    public function openFile(string $token): DirectEditingFile {
        $file = $this->getFile($this->getAccessedToken($token));

        return new DirectEditingFile(
            $token,
            $file->getName(),
            $file->getSize(),
            $file->isUpdateable()
        );
    }

    public function readFile(string $token): FileContent {
        $file = $this->getFile($this->getAccessedToken($token));

        if($file->getSize() > $this->settings->getFileSizeLimits()->maxSize) {
            throw new FileTooLargeException();
        }
        if(!$file->isReadable()) {
            throw new NotPermittedException();
        }

        try {
            return new FileContent($file->getContent());
        } catch(LockedException $e) {
            throw new NotPermittedException('The file is locked', 0, $e);
        }
    }

    public function writeFile(string $token, FileContent $content): void {
        $tokenObject = $this->getAccessedToken($token);
        $file = $this->getFile($tokenObject);

        if(strlen($content->content) > $this->settings->getFileSizeLimits()->maxEditorSize) {
            throw new FileTooLargeException();
        }
        if(!$file->isUpdateable()) {
            throw new NotPermittedException();
        }

        // Run the write as the owner of the token for versions and activities.
        // A volatile user is used because a session user would also affect an existing login of the browser.
        $user = $this->userManager->get($tokenObject->getUser());
        if($user === null) {
            throw new InvalidTokenException();
        }
        $previousUser = $this->userSession->getUser();
        $this->userSession->setVolatileActiveUser($user);
        try {
            $file->putContent($content->content);
        } catch(LockedException $e) {
            throw new NotPermittedException('The file is locked', 0, $e);
        } finally {
            $this->userSession->setVolatileActiveUser($previousUser);
        }
    }

    /**
     * Returns the token for the editor page, which is only valid after the direct editing manager opened the page with it
     *
     * @throws InvalidTokenException
     */
    protected function getAccessedToken(string $token): IToken {
        if(preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            throw new InvalidTokenException();
        }

        try {
            $tokenObject = $this->directEditingManager->getToken($token);
        } catch(Throwable $e) {
            throw new InvalidTokenException('Unknown token', 0, $e);
        }

        if($tokenObject->getEditor() !== Application::APP_ID || !$tokenObject->hasBeenAccessed()) {
            throw new InvalidTokenException();
        }

        // Tokens are removed by a background job after some time without use, so every request keeps it alive
        $tokenObject->extend();

        return $tokenObject;
    }

    /**
     * @throws InvalidTokenException
     */
    protected function getFile(IToken $token): File {
        try {
            $file = $token->getFile();
        } catch(Throwable $e) {
            throw new InvalidTokenException('The file of the token is not available', 0, $e);
        }

        if(!in_array($file->getMimeType(), self::SUPPORTED_MIMETYPES, true)) {
            throw new InvalidTokenException('The file of the token is not supported');
        }

        return $file;
    }
}