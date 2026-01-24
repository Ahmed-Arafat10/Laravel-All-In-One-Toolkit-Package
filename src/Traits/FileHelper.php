<?php

namespace AhmedArafat\AllInOne\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

trait FileHelper
{
    /**
     * Read and decode a JSON file from a given filesystem disk.
     *
     * This method is disk-agnostic and works with any Laravel filesystem
     * (local, public, s3, etc.).
     *
     * Examples:
     * - storage/app/private/test.json   → path: "private/test.json", disk: "local"
     * - storage/app/public/data.json    → path: "data.json", disk: "public"
     *
     * @param string $path Relative path inside the disk root.
     * @param string $disk Filesystem disk name (default: local).
     *
     * @return array Decoded JSON content as associative array.
     *
     * @throws RuntimeException If the file does not exist.
     * @throws RuntimeException If the file contains invalid JSON.
     */
    private function getJsonFileContent(string $path, string $disk = 'local'): array
    {
        if (!Storage::disk($disk)->exists($path)) {
            throw new RuntimeException("File [$path] not found on disk [$disk].");
        }
        $content = json_decode(
            Storage::disk($disk)->get($path),
            true
        );
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in file [$path].");
        }
        return $content;
    }

    /**
     * Extract metadata from an uploaded file in the request.
     *
     * Returns null if no file was uploaded under the given input name.
     *
     * @param Request $request The current HTTP request.
     * @param string $inputName Name of the file input (default: file).
     *
     * @return array|null {
     * @type string $original_name Original client file name.
     * @type string $extension File extension.
     * @type int $size File size in bytes.
     * @type string $mime MIME type.
     * }
     */
    public static function uploadedFileMetaData(
        Request $request,
        string  $inputName = 'file'
    ): ?array
    {
        /** @var UploadedFile|null $file */
        $file = $request->file($inputName);
        if (!$file) {
            return null;
        }
        return [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ];
    }
}
