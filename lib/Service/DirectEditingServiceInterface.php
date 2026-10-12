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

use OCA\HtmlViewer\Dto\DirectEditingFile;
use OCA\HtmlViewer\Dto\FileContent;
use OCA\HtmlViewer\Exception\FileTooLargeException;
use OCA\HtmlViewer\Exception\InvalidTokenException;
use OCP\Files\NotPermittedException;

/**
 * Access to files opened with the direct editing API of Nextcloud.
 *
 * The editor page is opened without a user session, so every request after the page itself
 * is authorized with the direct editing token of the file.
 */
interface DirectEditingServiceInterface {

    public const SUPPORTED_MIMETYPES = ['text/html'];

    /**
     * Returns the file of a token after the direct editing manager opened the editor page with it
     *
     * @throws InvalidTokenException
     */
    public function openFile(string $token): DirectEditingFile;

    /**
     * @throws InvalidTokenException
     * @throws FileTooLargeException
     * @throws NotPermittedException
     */
    public function readFile(string $token): FileContent;

    /**
     * @throws InvalidTokenException
     * @throws FileTooLargeException
     * @throws NotPermittedException
     */
    public function writeFile(string $token, FileContent $content): void;
}