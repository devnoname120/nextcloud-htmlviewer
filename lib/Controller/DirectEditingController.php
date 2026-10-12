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

namespace OCA\HtmlViewer\Controller;

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Dto\FileContent;
use OCA\HtmlViewer\Exception\FileTooLargeException;
use OCA\HtmlViewer\Exception\InvalidTokenException;
use OCA\HtmlViewer\Service\DirectEditingServiceInterface;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\NotPermittedException;
use OCP\IRequest;

/**
 * Endpoints of the direct editing page.
 *
 * The page is opened by the clients without a user session, so the requests are authorized with the direct editing
 * token in the TOKEN_HEADER instead. Since the token is not sent automatically by the browser, CSRF checks are not needed.
 */
class DirectEditingController extends Controller {

    public const TOKEN_HEADER = 'X-HtmlViewer-Token';

    protected const BRUTE_FORCE_ACTION = 'htmlviewer_direct_editing';

    public function __construct(
        IRequest                                $request,
        protected DirectEditingServiceInterface $directEditing,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[PublicPage]
    #[NoCSRFRequired]
    #[BruteForceProtection(action: self::BRUTE_FORCE_ACTION)]
    #[AnonRateLimit(limit: 60, period: 60)]
    #[UserRateLimit(limit: 60, period: 60)]
    public function read(): Response {
        try {
            $content = $this->directEditing->readFile($this->getToken());
        } catch(InvalidTokenException) {
            return $this->invalidTokenResponse();
        } catch(FileTooLargeException) {
            return new DataResponse([], Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
        } catch(NotPermittedException) {
            return new DataResponse([], Http::STATUS_FORBIDDEN);
        }

        // Never deliver the content as HTML, it would be rendered with the origin of Nextcloud
        return new DataDisplayResponse(
            $content->content,
            Http::STATUS_OK,
            [
                'Content-Type'           => 'text/plain; charset=utf-8',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control'          => 'no-store'
            ]
        );
    }

    /**
     * @param mixed $content The new content of the file
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[BruteForceProtection(action: self::BRUTE_FORCE_ACTION)]
    #[AnonRateLimit(limit: 60, period: 60)]
    #[UserRateLimit(limit: 60, period: 60)]
    public function write(mixed $content = null): Response {
        if(!is_string($content)) {
            return new DataResponse([], Http::STATUS_BAD_REQUEST);
        }

        try {
            $this->directEditing->writeFile($this->getToken(), new FileContent($content));
        } catch(InvalidTokenException) {
            return $this->invalidTokenResponse();
        } catch(FileTooLargeException) {
            return new DataResponse([], Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
        } catch(NotPermittedException) {
            return new DataResponse([], Http::STATUS_FORBIDDEN);
        }

        return new DataResponse([], Http::STATUS_NO_CONTENT);
    }

    protected function getToken(): string {
        return trim($this->request->getHeader(self::TOKEN_HEADER));
    }

    protected function invalidTokenResponse(): Response {
        $response = new DataResponse([], Http::STATUS_NOT_FOUND);
        $response->throttle(['action' => self::BRUTE_FORCE_ACTION]);

        return $response;
    }
}