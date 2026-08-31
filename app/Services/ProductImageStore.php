<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageStore
{
    public const PREFIX = '/storage/products/';

    public function store(UploadedFile $file, string $productName, string $side, ?string $existing = null): string
    {
        Storage::disk('public')->makeDirectory('products');

        $existingPath = $this->relativePath($existing);
        if ($existingPath !== null) {
            Storage::disk('public')->put($existingPath, $file->getContent());

            return self::PREFIX.basename($existingPath);
        }

        $filename = $this->filename($productName, $side, $file);
        Storage::disk('public')->putFileAs('products', $file, $filename);

        return self::PREFIX.$filename;
    }

    public static function publicLinkReady(): bool
    {
        return is_link(public_path('storage')) || is_dir(public_path('storage'));
    }

    public function deleteIfLocal(?string $url): void
    {
        $path = $this->relativePath($url);
        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    public function filename(string $productName, string $side, UploadedFile $file): string
    {
        $slug = Str::slug($productName);
        if ($slug === '') {
            $slug = 'produk';
        }

        $slug = Str::limit($slug, 50, '');
        $side = Str::slug($side) ?: 'foto';
        $uniq = Str::lower(Str::random(6));
        $ext = $this->extension($file);

        return $slug.'-'.$side.'-'.$uniq.'.'.$ext;
    }

    private function extension(UploadedFile $file): string
    {
        $ext = strtolower((string) $file->guessExtension());

        return match ($ext) {
            'jpeg', 'jpe' => 'jpg',
            'png', 'webp', 'gif', 'jpg' => $ext,
            default => 'jpg',
        };
    }

    private function relativePath(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        if (! str_starts_with($path, self::PREFIX)) {
            return null;
        }

        return 'products/'.basename($path);
    }
}
