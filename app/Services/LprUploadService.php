<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\UploadedFile;
use App\DTO\StoredLprUpload;
use App\Exceptions\ValidationException;
use finfo;
use InvalidArgumentException;
use RuntimeException;

final readonly class LprUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private string $absoluteDirectory;
    private string $relativeDirectory;

    public function __construct(
        string $projectRoot,
        string $uploadDirectory,
        private int $maximumBytes
    ) {
        $normalizedDirectory = str_replace('\\', '/', $uploadDirectory);

        if (preg_match('#(^|/)\.\.(/|$)#', $normalizedDirectory) === 1) {
            throw new InvalidArgumentException(
                'Thư mục upload LPR không được chứa đoạn đường dẫn cha.'
            );
        }

        $this->relativeDirectory = trim($normalizedDirectory, '/');
        $this->absoluteDirectory = str_starts_with($normalizedDirectory, '/')
            ? rtrim($uploadDirectory, '/')
            : rtrim($projectRoot, '/') . '/' . $this->relativeDirectory;

        $publicDirectory = rtrim($projectRoot, '/') . '/public';

        if (
            $this->absoluteDirectory === $publicDirectory
            || str_starts_with($this->absoluteDirectory, $publicDirectory . '/')
        ) {
            throw new InvalidArgumentException('Ảnh LPR phải được lưu ngoài thư mục public.');
        }
    }

    public function store(?UploadedFile $file): StoredLprUpload
    {
        if ($file === null || $file->error === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(['plate_image' => 'Vui lòng chọn ảnh biển số để nhận diện.']);
        }

        if ($file->error !== UPLOAD_ERR_OK) {
            throw new ValidationException([
                'plate_image' => 'Ảnh tải lên không hoàn tất. Vui lòng chọn lại.',
            ]);
        }

        $actualSize = is_file($file->temporaryPath) ? filesize($file->temporaryPath) : false;

        if ($actualSize === false || $actualSize < 1) {
            throw new ValidationException(['plate_image' => 'Không thể đọc nội dung ảnh tải lên.']);
        }

        if ($actualSize > $this->maximumBytes || $file->size > $this->maximumBytes) {
            throw new ValidationException([
                'plate_image' => sprintf('Ảnh không được vượt quá %.1f MB.', $this->maximumBytes / 1048576),
            ]);
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file->temporaryPath);

        if (!is_string($mimeType) || !isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new ValidationException([
                'plate_image' => 'Chỉ chấp nhận ảnh JPEG, PNG hoặc WebP có nội dung hợp lệ.',
            ]);
        }

        $this->ensureDirectory();
        $filename = bin2hex(random_bytes(20)) . '.' . self::ALLOWED_MIME_TYPES[$mimeType];
        $absolutePath = $this->absoluteDirectory . '/' . $filename;
        $file->moveTo($absolutePath);
        chmod($absolutePath, 0640);

        return new StoredLprUpload(
            $absolutePath,
            $this->relativeDirectory . '/' . $filename
        );
    }

    /** @return array{content: string, mime_type: string} */
    public function read(string $relativePath): array
    {
        $expectedPrefix = $this->relativeDirectory . '/';

        if (!str_starts_with($relativePath, $expectedPrefix)) {
            throw new RuntimeException('Đường dẫn ảnh nhận diện không hợp lệ.');
        }

        $filename = basename($relativePath);
        $absolutePath = $this->absoluteDirectory . '/' . $filename;
        $realDirectory = realpath($this->absoluteDirectory);
        $realPath = realpath($absolutePath);

        if ($realDirectory === false || $realPath === false || !str_starts_with($realPath, $realDirectory . '/')) {
            throw new RuntimeException('Không tìm thấy ảnh nhận diện trong vùng lưu trữ an toàn.');
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($realPath);
        $content = file_get_contents($realPath);

        if (!is_string($mimeType) || !isset(self::ALLOWED_MIME_TYPES[$mimeType]) || $content === false) {
            throw new RuntimeException('Ảnh nhận diện không còn hợp lệ.');
        }

        return ['content' => $content, 'mime_type' => $mimeType];
    }

    private function ensureDirectory(): void
    {
        if (!is_dir($this->absoluteDirectory) && !mkdir($this->absoluteDirectory, 0750, true)) {
            throw new RuntimeException('Không thể tạo vùng lưu trữ ảnh nhận diện an toàn.');
        }

        if (!is_writable($this->absoluteDirectory)) {
            throw new RuntimeException('Vùng lưu trữ ảnh nhận diện không có quyền ghi.');
        }
    }
}
