<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class WebImageOptimizer
{
    private const MAX_SIDE = 1600;

    private const QUALITY = 82;

    public function checkRuntime(): void
    {
        if (! extension_loaded('gd') || ! extension_loaded('exif')) {
            throw new RuntimeException('Image optimization requires PHP GD and EXIF.');
        }

        $support = gd_info();

        if (! ($support['JPEG Support'] ?? false) || ! ($support['PNG Support'] ?? false) || ! ($support['WebP Support'] ?? false)) {
            throw new RuntimeException('PHP GD must support JPEG, PNG, and WebP.');
        }
    }

    /** @return array{width: int, height: int, mime: string} */
    public function inspect(UploadedFile $image, string $attribute): array
    {
        $info = @getimagesize($image->getPathname());

        if ($info === false || ! in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $this->invalid($attribute, 'Gunakan gambar JPEG, PNG, atau WebP statis. GIF dan format lain tidak didukung.');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width > 8192 || $height > 8192 || $width * $height > 12000000) {
            $this->invalid($attribute, 'Dimensi gambar maksimal 8192 piksel per sisi dan 12 megapiksel. Perkecil gambar lalu unggah kembali.');
        }

        if (in_array($info['mime'], ['image/png', 'image/webp'], true)) {
            $bytes = file_get_contents($image->getPathname());

            if ($bytes === false) {
                throw new RuntimeException('Cannot read uploaded image.');
            }

            $this->validateChunks($bytes, $info['mime'], $attribute);
        }

        return ['width' => $width, 'height' => $height, 'mime' => $info['mime']];
    }

    public function store(UploadedFile $image, string $disk, string $directory, string $attribute): string
    {
        $this->checkRuntime();
        $info = $this->inspect($image, $attribute);
        $this->checkMemory($info['width'] * $info['height'], $attribute);
        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($image->getPathname()),
            'image/png' => @imagecreatefrompng($image->getPathname()),
            'image/webp' => @imagecreatefromwebp($image->getPathname()),
        };

        if ($source === false) {
            $this->invalid($attribute, 'Gambar rusak atau tidak dapat dibaca. Pilih gambar lain lalu unggah kembali.');
        }

        $target = null;

        try {
            if ($info['mime'] === 'image/jpeg') {
                $source = $this->orient($source, $image);
            }

            $scale = min(1, self::MAX_SIDE / max(imagesx($source), imagesy($source)));
            $width = max(1, (int) round(imagesx($source) * $scale));
            $height = max(1, (int) round(imagesy($source) * $scale));
            $target = imagecreatetruecolor($width, $height);
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));

            if (! imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source))) {
                throw new RuntimeException('Image resizing failed.');
            }

            $bytes = $this->encode($target);
        } finally {
            unset($source, $target);
        }

        $path = $directory.'/'.Str::uuid().'.webp';
        $storage = Storage::disk($disk);

        try {
            if (! $storage->put($path, $bytes, ['ContentType' => 'image/webp'])) {
                throw new RuntimeException('Optimized image upload failed.');
            }
        } catch (Throwable $exception) {
            try {
                if (! $storage->delete($path)) {
                    report(new RuntimeException('Failed to clean up image: '.$path));
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return $path;
    }

    public function encode(GdImage $image): string
    {
        ob_start();

        try {
            $encoded = imagewebp($image, null, self::QUALITY);
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $info = is_string($bytes) && $bytes !== '' ? @getimagesizefromstring($bytes) : false;

        if (! $encoded || $info === false || $info['mime'] !== 'image/webp') {
            throw new RuntimeException('WebP encoding failed.');
        }

        return $bytes;
    }

    private function orient(GdImage $image, UploadedFile $file): GdImage
    {
        $exif = @exif_read_data($file->getPathname(), 'IFD0');
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3, 4 => 180,
            5, 8 => 90,
            6, 7 => -90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);

            if ($rotated === false) {
                throw new RuntimeException('Image orientation correction failed.');
            }

            return $rotated;
        }

        return $image;
    }

    private function validateChunks(string $bytes, string $mime, string $attribute): void
    {
        $png = $mime === 'image/png';
        $offset = $png ? 8 : 12;
        $length = strlen($bytes);

        if (! $png && (substr($bytes, 0, 4) !== 'RIFF' || substr($bytes, 8, 4) !== 'WEBP' || unpack('V', substr($bytes, 4, 4))[1] + 8 !== $length)) {
            $this->invalid($attribute, 'Gambar rusak atau tidak dapat dibaca. Pilih gambar lain lalu unggah kembali.');
        }

        while ($offset < $length) {
            if ($length - $offset < ($png ? 12 : 8)) {
                $this->invalid($attribute, 'Gambar rusak atau tidak dapat dibaca. Pilih gambar lain lalu unggah kembali.');
            }

            $size = unpack($png ? 'N' : 'V', substr($bytes, $offset + ($png ? 0 : 4), 4))[1];
            $type = substr($bytes, $offset + ($png ? 4 : 0), 4);
            $next = $offset + $size + ($png ? 12 : 8 + ($size % 2));

            if ($next > $length) {
                $this->invalid($attribute, 'Gambar rusak atau tidak dapat dibaca. Pilih gambar lain lalu unggah kembali.');
            }

            if (($png && $type === 'acTL') || (! $png && (in_array($type, ['ANIM', 'ANMF'], true) || ($type === 'VP8X' && $size >= 10 && (ord($bytes[$offset + 8]) & 2) !== 0)))) {
                $this->invalid($attribute, 'Gambar beranimasi tidak didukung. Gunakan JPEG, PNG, atau WebP statis.');
            }

            $offset = $next;

            if ($png && $type === 'IEND') {
                return;
            }
        }

        if ($png) {
            $this->invalid($attribute, 'Gambar rusak atau tidak dapat dibaca. Pilih gambar lain lalu unggah kembali.');
        }
    }

    private function checkMemory(int $pixels, string $attribute): void
    {
        $limit = ini_get('memory_limit');

        if ($limit === false || $limit === '-1') {
            return;
        }

        $bytes = (int) $limit * match (strtolower(substr($limit, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        if (memory_get_usage(true) + $pixels * 16 + 32 * 1024 ** 2 > $bytes) {
            $this->invalid($attribute, 'Gambar terlalu besar untuk diproses. Perkecil resolusi lalu unggah kembali.');
        }
    }

    private function invalid(string $attribute, string $message): never
    {
        throw ValidationException::withMessages([$attribute => $message]);
    }
}
