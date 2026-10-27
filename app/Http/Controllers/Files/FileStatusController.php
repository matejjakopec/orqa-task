<?php

declare(strict_types=1);

namespace App\Http\Controllers\Files;

use App\Contracts\Files\FileServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Files\ListFileStatusesRequest;
use Illuminate\Http\JsonResponse;

final class FileStatusController extends Controller
{
    public function __invoke(ListFileStatusesRequest $request, FileServiceInterface $files): JsonResponse
    {
        return response()->json($files->statuses($request->user(), $request->publicIds())->toArray())
            ->header('Cache-Control', 'private, no-store');
    }
}
