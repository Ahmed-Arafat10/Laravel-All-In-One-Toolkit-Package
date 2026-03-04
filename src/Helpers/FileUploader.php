<?php

namespace AhmedArafat\AllInOne\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class FileUploader
{
    /**
     * Generate a unique file name.
     *
     * This method builds a file name using:
     * - Optional prefix
     * - Current timestamp (Y_m_d_H_i_s_)
     * - Laravel's hashed file name
     *
     * Example output:
     * user_2026_03_04_02_33_21_a8f5f167f44f4964e6c998dee827110c.jpg
     *
     * @param UploadedFile $file   The uploaded file instance.
     * @param string|null  $prefix Optional prefix to prepend to the file name.
     *
     * @return string Generated file name including extension.
     */
    public static function generateFileName(
        UploadedFile $file,
        ?string $prefix = null
    ): string {
        $prefix = $prefix ? $prefix . '_' : '';

        return $prefix .
            Carbon::now()->format('Y_m_d_H_i_s_') .
            $file->hashName();
    }

    /**
     * Upload a file to the specified disk and path.
     *
     * If an old file name is provided, it will be deleted after the new file is stored.
     *
     * If the file is null, the method returns null.
     *
     * @param UploadedFile|null $file     The uploaded file instance.
     * @param string            $path     Directory path inside the disk.
     * @param string|null       $oldFile  Existing file name to delete (optional).
     * @param string|null       $prefix   Optional prefix for generated file name.
     * @param string            $disk     Filesystem disk name e.g. public/local (default: public).
     *
     * @return array|null Returns:
     *                    [
     *                        'fileName' => string,
     *                        'originalName' => string
     *                    ]
     *                    or null if no file was uploaded.
     */
    public static function upload(
        ?UploadedFile $file,
        string $path,
        ?string $oldFile = null,
        ?string $prefix = null,
        string $disk = 'public'
    ): ?array {
        if (!$file) {
            return null;
        }

        $fileName = self::generateFileName($file, $prefix);

        Storage::disk($disk)->putFileAs(
            $path,
            $file,
            $fileName
        );

        if ($oldFile) {
            self::delete($path, $oldFile, $disk);
        }

        return [
            'fileName' => $fileName,
            'originalName' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Delete a file from the specified disk.
     *
     * @param string $path     Directory path inside the disk.
     * @param string $fileName File name including extension.
     * @param string $disk     Filesystem disk name e.g. public/local (default: public).
     *
     * @return bool True if file was deleted successfully, false otherwise.
     */
    public static function delete(
        string $path,
        string $fileName,
        string $disk = 'public'
    ): bool {
        $fullPath = $path . '/' . $fileName;

        return Storage::disk($disk)->exists($fullPath)
            ? Storage::disk($disk)->delete($fullPath)
            : false;
    }

    /**
     * Generate a publicly accessible URL for a stored file.
     *
     * If the file does not exist or file name is null,
     * the default asset path will be returned.
     *
     * @param string      $path     Directory path inside the disk.
     * @param string|null $fileName File name including extension.
     * @param string      $disk     Filesystem disk name e.g. public/local (default: public).
     * @param string      $default  Default asset path if file is missing.
     *
     * @return string Public URL to the file or default asset URL.
     */
    public static function url(
        string $path,
        ?string $fileName = null,
        string $disk = 'public',
        string $default = 'NoImg.jpg'
    ): string {
        if (!$fileName) {
            return asset($default);
        }

        $fullPath = $path . '/' . $fileName;

        return Storage::disk($disk)->exists($fullPath)
            ? Storage::disk($disk)->url($fullPath)
            : asset($default);
    }
}
