<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Contracts\Files\FileServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FileDownloadController extends Controller
{
    public function __invoke(Request $request, string $publicId, FileServiceInterface $files): StreamedResponse
    {
        $download = $files->prepareDownload($request->user(), $publicId);

        return response()->streamDownload(function () use ($download): void {
            try {
                $download->stream->send();
            } finally {
                $download->stream->close();
            }
        }, $download->name, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => (string) $download->sizeBytes,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
