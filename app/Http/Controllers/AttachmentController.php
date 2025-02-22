<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Attachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    private $inlineTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'image', 'pdf'
    ];

    public function download(Attachment $attachment)
    {
        // Clean up the path by removing /app/public prefix if it exists
        $cleanPath = preg_replace('#^/app/public/#', '', $attachment->path);
        
        $privatePath = 'private/' . $cleanPath;
        $publicPath = $cleanPath;
        $publicDiskPath = 'public/' . $cleanPath;

       
        // Try to find the file in all possible locations
        if (Storage::disk('local')->exists($privatePath)) {
            $filePath = $privatePath;
            $fullPath = Storage::disk('local')->path($filePath);
        }
        elseif (Storage::disk('local')->exists($publicPath)) {
            $filePath = $publicPath;
            $fullPath = Storage::disk('local')->path($filePath);
        }
        elseif (Storage::disk('public')->exists($cleanPath)) {
            $filePath = $publicDiskPath;
            $fullPath = Storage::disk('public')->path($cleanPath);
        }
        else {
            Log::error('Attachment not found', [
                'id' => $attachment->id,
                'path' => $attachment->path,
                'clean_path' => $cleanPath,
                'tried_paths' => [$privatePath, $publicPath, $publicDiskPath]
            ]);
            abort(404, 'File not found');
        }

        // Get mime type using mime_content_type
        $mimeType = $attachment->mime_type ?? mime_content_type($fullPath) ?? 'application/octet-stream';

        Log::info('Serving attachment', [
            'filename' => $attachment->filename,
            'mime_type' => $mimeType,
            'path' => $filePath
        ]);

        $disposition = in_array($mimeType, $this->inlineTypes) ? 'inline' : 'attachment';
        $filename = $attachment->filename;

        // Use response()->file() instead of Storage::response()
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"'
        ]);
    }
}
