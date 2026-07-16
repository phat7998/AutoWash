<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final readonly class LprAttemptRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function create(
        int $userId,
        string $imagePath,
        string $provider,
        ?string $recognizedText,
        ?string $normalizedText,
        ?float $confidence,
        string $status
    ): int {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO lpr_attempts (
                user_id, image_path, provider, recognized_text, normalized_text, confidence, status
            ) VALUES (
                :user_id, :image_path, :provider, :recognized_text, :normalized_text, :confidence, :status
            )
            SQL
        );
        $statement->execute([
            'user_id' => $userId,
            'image_path' => $imagePath,
            'provider' => $provider,
            'recognized_text' => $recognizedText,
            'normalized_text' => $normalizedText,
            'confidence' => $confidence,
            'status' => $status,
        ]);

        return (int) $this->database->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findOwnedById(int $attemptId, int $userId): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT id, user_id, image_path, provider, recognized_text, normalized_text, confidence, status
            FROM lpr_attempts
            WHERE id = :attempt_id AND user_id = :user_id
            LIMIT 1
            SQL
        );
        $statement->execute(['attempt_id' => $attemptId, 'user_id' => $userId]);
        $attempt = $statement->fetch();

        return is_array($attempt) ? $attempt : null;
    }

    public function updateConfirmation(int $attemptId, int $userId, string $normalizedText, string $status): void
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            UPDATE lpr_attempts
            SET normalized_text = :normalized_text, status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE id = :attempt_id AND user_id = :user_id
            SQL
        );
        $statement->execute([
            'attempt_id' => $attemptId,
            'user_id' => $userId,
            'normalized_text' => $normalizedText,
            'status' => $status,
        ]);
    }
}
