<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Integrations;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class S3StorageAdapter {
    private FilesystemAdapter $disk;

    public function __construct() {
        $this->disk = Storage::disk('s3');
    }

    public function upload(UploadedFile $file, string $path): string {
        return $this->disk->putFile($path, $file, 'public');
    }

    public function uploadAs(UploadedFile $file, string $path, string $name): string {
        return $this->disk->putFileAs($path, $file, $name, 'public');
    }

    public function delete(string $path): bool {
        return $this->disk->delete($path);
    }

    public function exists(string $path): bool {
        return $this->disk->exists($path);
    }

    public function url(string $path): string {
        return $this->disk->url($path);
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration): string {
        return $this->disk->temporaryUrl($path, $expiration);
    }

    public function size(string $path): int {
        return $this->disk->size($path);
    }

    public function mimeType(string $path): string {
        return $this->disk->mimeType($path);
    }

    public function copy(string $from, string $to): bool {
        return $this->disk->copy($from, $to);
    }

    public function move(string $from, string $to): bool {
        return $this->disk->move($from, $to);
    }
}
