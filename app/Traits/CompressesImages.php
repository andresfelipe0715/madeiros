<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;

trait CompressesImages
{
    /**
     * Compress and store an image.
     *
     * @return string|false Path to the stored file
     */
    protected function compressAndStore(UploadedFile $file, string $directory, int $maxWidth = 1200, int $maxHeight = 1200, int $quality = 70): string|false
    {
        $imageInfo = getimagesize($file->getRealPath());
        if (! $imageInfo) {
            return $file->store($directory, 'public');
        }

        [$width, $height, $type] = $imageInfo;

        // Load image based on type
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_GIF => imagecreatefromgif($file->getRealPath()),
            IMAGETYPE_WEBP => imagecreatefromwebp($file->getRealPath()),
            default => null,
        };

        if (! $image) {
            return $file->store($directory, 'public');
        }

        // Calculate aspect ratio
        $ratio = $width / $height;
        if ($width > $maxWidth || $height > $maxHeight) {
            if ($maxWidth / $maxHeight > $ratio) {
                $maxWidth = $maxHeight * $ratio;
            } else {
                $maxHeight = $maxWidth / $ratio;
            }

            $newImage = imagecreatetruecolor($maxWidth, $maxHeight);

            // Handle transparency for PNG/WEBP
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $maxWidth, $maxHeight, $transparent);
            }

            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $maxWidth, $maxHeight, $width, $height);
            imagedestroy($image);
            $image = $newImage;
        }

        // Generate filename
        $filename = $file->hashName();
        $path = $directory.'/'.$filename;
        $fullPath = storage_path('app/public/'.$path);

        // Ensure directory exists
        if (! file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Save image (Force to JPEG for compression benefits)
        $result = imagejpeg($image, $fullPath, $quality);
        imagedestroy($image);

        return $result ? $path : false;
    }
}
