<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final readonly class UserRepository
{
    public function __construct(private PDO $database)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByPhone(string $phone): ?array
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            SELECT id, phone, full_name, password_hash, role, status
            FROM users
            WHERE phone = :phone
            LIMIT 1
            SQL
        );
        $statement->execute(['phone' => $phone]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function createCustomer(string $phone, string $fullName, string $passwordHash): int
    {
        $statement = $this->database->prepare(
            <<<'SQL'
            INSERT INTO users (current_tier_id, phone, full_name, password_hash, role, status)
            SELECT id, :phone, :full_name, :password_hash, 'customer', 'active'
            FROM tiers
            WHERE code = 'MEMBER' AND is_active = TRUE
            LIMIT 1
            SQL
        );
        $statement->execute([
            'phone' => $phone,
            'full_name' => $fullName,
            'password_hash' => $passwordHash,
        ]);

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Không tìm thấy hạng thành viên mặc định đang hoạt động.');
        }

        return (int) $this->database->lastInsertId();
    }

    public function updateLastLogin(int $userId): void
    {
        $statement = $this->database->prepare(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['id' => $userId]);
    }
}
