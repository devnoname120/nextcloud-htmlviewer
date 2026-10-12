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

namespace OCA\HtmlViewer\DirectEditing;

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Exception\InvalidTokenException;
use OCA\HtmlViewer\Service\DirectEditingServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateServiceInterface;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\DirectEditing\IEditor;
use OCP\DirectEditing\IToken;
use OCP\IRequest;
use OCP\Util;

/**
 * Editor for HTML files in the mobile and desktop clients.
 *
 * The Text app also registers text/html. Clients and the server use the first editor which supports a mimetype,
 * so this editor is registered before the one of Text, see Application::DIRECT_EDITOR_PRIORITY.
 */
class HtmlDirectEditor implements IEditor {

    public function __construct(
        protected DirectEditingServiceInterface $directEditing,
        protected ViewerStateServiceInterface   $viewerState,
        protected IInitialState                 $initialState,
        protected IRequest                      $request,
    ) {
    }

    public function getId(): string {
        return Application::APP_ID;
    }

    public function getName(): string {
        return 'HTML Viewer';
    }

    public function getMimetypes(): array {
        return DirectEditingServiceInterface::SUPPORTED_MIMETYPES;
    }

    public function getMimetypesOptional(): array {
        return [];
    }

    public function getCreators(): array {
        return [];
    }

    public function isSecure(): bool {
        return false;
    }

    public function open(IToken $token): Response {
        // IToken does not expose the token itself, so it is taken from the route of the direct editing page
        $tokenValue = $this->request->getParam('token');

        try {
            if(!is_string($tokenValue)) {
                throw new InvalidTokenException();
            }
            $file = $this->directEditing->openFile($tokenValue);
        } catch(InvalidTokenException) {
            return new NotFoundResponse();
        }

        $this->viewerState->provideViewerState($token->getUser());
        $this->initialState->provideInitialState('directEditing', $file);

        Util::addScript(Application::APP_ID, 'htmlviewer-directEditing');
        Util::addTranslations(Application::APP_ID);

        return new TemplateResponse(Application::APP_ID, 'directEditing', [], TemplateResponse::RENDER_AS_BASE);
    }
}