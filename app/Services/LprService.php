<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LprProviderInterface;
use App\Core\Logger;
use App\Core\UploadedFile;
use App\DTO\LprRecognitionOutcome;
use App\Exceptions\LprAttemptOwnershipException;
use App\Exceptions\LprProviderException;
use App\Exceptions\ValidationException;
use App\Repositories\LprAttemptRepository;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final readonly class LprService
{
    public function __construct(
        private LprAttemptRepository $attempts,
        private LprUploadService $uploads,
        private LprProviderInterface $provider,
        private LicensePlateService $plates,
        private Logger $logger,
        private float $confidenceThreshold
    ) {
        if ($confidenceThreshold < 0 || $confidenceThreshold > 1) {
            throw new InvalidArgumentException('Ngưỡng confidence LPR phải nằm trong khoảng 0 đến 1.');
        }
    }

    public function recognize(int $userId, ?UploadedFile $file): LprRecognitionOutcome
    {
        try {
            $upload = $this->uploads->store($file);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            try {
                $this->logger->error('Không thể lưu ảnh nhận diện biển số.', [
                    'exception' => $exception::class,
                ]);
            } catch (Throwable) {
                // Không để lỗi log thay thế manual fallback.
            }

            throw new ValidationException([
                'plate_image' => 'Không thể lưu ảnh lúc này. '
                    . 'Bạn vẫn có thể nhập biển số thủ công.',
            ]);
        }

        try {
            $result = $this->provider->recognize($upload->absolutePath);
            $recognizedText = trim($result->recognizedText);

            if (mb_strlen($recognizedText) > 50) {
                throw new LprProviderException('Provider trả kết quả vượt giới hạn lưu trữ.');
            }

            $normalizedText = $this->plates->normalize($recognizedText);
            $attemptId = $this->attempts->create(
                $userId,
                $upload->relativePath,
                $this->provider->name(),
                $recognizedText,
                $normalizedText,
                $result->confidence,
                'success'
            );
            $warning = $result->confidence === null || $result->confidence < $this->confidenceThreshold
                ? 'Độ tin cậy thấp. Vui lòng kiểm tra và sửa biển số trước khi lưu.'
                : null;

            return new LprRecognitionOutcome(
                $attemptId,
                $this->provider->name(),
                'success',
                $recognizedText,
                $normalizedText,
                $result->confidence,
                $warning
            );
        } catch (LprProviderException $exception) {
            $attemptId = $this->attempts->create(
                $userId,
                $upload->relativePath,
                $this->provider->name(),
                null,
                null,
                null,
                'failed'
            );
            $this->logger->error('Provider nhận diện biển số thất bại.', [
                'provider' => $this->provider->name(),
                'exception' => $exception::class,
            ]);

            return new LprRecognitionOutcome(
                $attemptId,
                $this->provider->name(),
                'failed',
                '',
                '',
                null,
                'Không thể nhận diện ảnh lúc này. '
                    . 'Bạn vẫn có thể nhập biển số thủ công bên dưới.'
            );
        }
    }

    /** @return array<string, mixed> */
    public function assertOwnedAttempt(int $attemptId, int $userId): array
    {
        $attempt = $this->attempts->findOwnedById($attemptId, $userId);

        if ($attempt === null) {
            throw new LprAttemptOwnershipException();
        }

        return $attempt;
    }

    public function recordConfirmation(int $attemptId, int $userId, string $confirmedPlate): void
    {
        $attempt = $this->assertOwnedAttempt($attemptId, $userId);
        $normalizedText = $this->plates->normalize($confirmedPlate);
        $status = $attempt['status'] === 'success' && $attempt['normalized_text'] === $normalizedText
            ? 'success'
            : 'manual_override';

        $this->attempts->updateConfirmation($attemptId, $userId, $normalizedText, $status);
    }

    /** @return array{content: string, mime_type: string} */
    public function ownedImage(int $attemptId, int $userId): array
    {
        $attempt = $this->assertOwnedAttempt($attemptId, $userId);

        try {
            return $this->uploads->read((string) $attempt['image_path']);
        } catch (Throwable) {
            throw new LprAttemptOwnershipException();
        }
    }
}
