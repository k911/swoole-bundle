<?php

declare(strict_types=1);

namespace K911\Swoole\Server\RequestHandler;

use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\Runtime\BootableInterface;
use RuntimeException;
use Swoole\Http\Request;
use Swoole\Http\Response;

/**
 * Advanced static files server simplifies serving static content directly by swoole server.
 *
 * Code mostly ported from `zendframework/zend-expressive-swoole` package.
 *
 * @see https://github.com/zendframework/zend-expressive-swoole/blob/8b33edb50732961cce9e980c10a5948636b98e4e/src/RequestHandlerSwooleRunner.php
 */
final class AdvancedStaticFilesServer implements RequestHandlerInterface, BootableInterface
{
    /**
     * Default static file extensions supported.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Complete_list_of_MIME_types
     */
    private const FILE_EXTENSION_MIME_TYPE_MAP = [
        '7z' => 'application/x-7z-compressed',
        'aac' => 'audio/aac',
        'arc' => 'application/octet-stream',
        'avi' => 'video/x-msvideo',
        'azw' => 'application/vnd.amazon.ebook',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'eot' => 'application/vnd.ms-fontobject',
        'epub' => 'application/epub+zip',
        'es' => 'application/ecmascript',
        'gif' => 'image/gif',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'oga' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'otf' => 'font/otf',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar' => 'application/x-rar-compressed',
        'rtf' => 'application/rtf',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ts' => 'application/typescript',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain',
        'wav' => 'audio/wav',
        'weba' => 'audio/webm',
        'webm' => 'video/webm',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'zip' => 'application/zip',
    ];

    private $decorated;
    private $cachedMimeTypes;
    private $configuration;

    /**
     * @var string
     */
    private $publicDir;

    public function __construct(RequestHandlerInterface $decorated, HttpServerConfiguration $configuration)
    {
        $this->decorated = $decorated;
        $this->cachedMimeTypes = [];
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        if (!$this->configuration->hasPublicDir()) {
            throw new RuntimeException('AdvancedStaticFilesHandler requires setting "public_dir", which is unavailable. Either disable driver or fill "public_dir" setting.');
        }

        $this->publicDir = $this->configuration->getPublicDir();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        if ('GET' === $request->server['request_method']) {
            $path = $this->publicDir.$request->server['request_uri'];
            if (isset($this->cachedMimeTypes[$path]) || $this->checkPath($path)) {
                $response->header('Content-Type', $this->cachedMimeTypes[$path]);
                $response->sendfile($path);

                return;
            }
        }

        $this->decorated->handle($request, $response);
    }

    private function checkPath(string $path): bool
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // eg. "file.js.map"
        if ('map' === $extension) {
            $extension = pathinfo(pathinfo($path, PATHINFO_FILENAME), PATHINFO_EXTENSION);
        }

        if (!isset(self::FILE_EXTENSION_MIME_TYPE_MAP[$extension])) {
            return false;
        }

        if (!file_exists($path)) {
            return false;
        }

        $this->cachedMimeTypes[$path] = self::FILE_EXTENSION_MIME_TYPE_MAP[$extension];

        return true;
    }
}
