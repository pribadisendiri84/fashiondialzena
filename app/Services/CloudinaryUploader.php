<?php

namespace App\Services;

use Cloudinary;
use Cloudinary\Uploader;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryUploader
{
    public function configured(): bool
    {
        return filled(config('services.cloudinary.url'))
            || (
                filled(config('services.cloudinary.cloud_name'))
                && filled(config('services.cloudinary.key'))
                && filled(config('services.cloudinary.secret'))
            );
    }

    public function upload(UploadedFile $file, string $folder = 'fashiondialzena'): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Cloudinary belum dikonfigurasi. Isi CLOUDINARY_URL di .env.');
        }

        $this->boot();

        $result = Uploader::upload($file->getRealPath(), [
            'folder' => $folder,
            'resource_type' => 'image',
            'overwrite' => false,
            'unique_filename' => true,
        ]);

        $url = $result['secure_url'] ?? null;

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Upload Cloudinary gagal: URL kosong.');
        }

        return $url;
    }

    private function boot(): void
    {
        $url = config('services.cloudinary.url');

        if (filled($url)) {
            // cloudinary://API_KEY:API_SECRET@CLOUD_NAME
            Cloudinary::config_from_url($url);

            return;
        }

        Cloudinary::config([
            'cloud_name' => config('services.cloudinary.cloud_name'),
            'api_key' => config('services.cloudinary.key'),
            'api_secret' => config('services.cloudinary.secret'),
            'secure' => true,
        ]);
    }
}
