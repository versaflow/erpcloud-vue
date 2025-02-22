<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class FileUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240'
        ]);

        try {
            $file = $request->file('file');
            $fileName = Str::uuid() . '_' . $file->getClientOriginalName();
            
            // Store in the public disk under attachments directory
            $path = Storage::disk('public')->putFileAs(
                'attachments',
                $file,
                $fileName
            );

            // Log the successful upload
            Log::info('File uploaded successfully', [
                'filename' => $fileName,
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            // Return the full storage path
            return response()->json([
                'name' => $file->getClientOriginalName(),
                'path' => storage_path('app/public/' . $path),
                'relative_path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
