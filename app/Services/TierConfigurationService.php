<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DuplicateCatalogException;
use App\Exceptions\CatalogResourceNotFoundException;
use App\Repositories\TierConfigurationRepository;
use App\Validation\TierConfigurationValidator;
use PDOException;

final readonly class TierConfigurationService
{
    public function __construct(
        private TierConfigurationRepository $repository,
        private TierConfigurationValidator $validator
    ) {
    }

    /** @return array<string, mixed> */
    public function overview(): array
    {
        return ['tiers' => $this->repository->tiers(), 'perks' => $this->repository->perks()];
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function options(): array
    {
        return ['tiers' => $this->repository->tiers(), 'services' => $this->repository->services()];
    }

    public function tier(int $id): array
    {
        return $this->repository->tier($id) ?? throw new CatalogResourceNotFoundException();
    }

    public function perk(int $id): array
    {
        return $this->repository->perk($id) ?? throw new CatalogResourceNotFoundException();
    }

    public function saveTier(?int $id, int $adminId, array $input): int
    {
        if ($id !== null) {
            $this->tier($id);
        }
        try {
            return $this->repository->saveTier($id, $this->validator->tier($input), $adminId);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new DuplicateCatalogException('Mã hoặc thứ tự hạng đã tồn tại.');
            }
            throw $exception;
        }
    }

    public function savePerk(?int $id, int $adminId, array $input): int
    {
        if ($id !== null) {
            $this->perk($id);
        }
        $options = $this->options();

        return $this->repository->savePerk(
            $id,
            $this->validator->perk(
                $input,
                array_map('intval', array_column($options['tiers'], 'id')),
                array_map('intval', array_column($options['services'], 'id'))
            ),
            $adminId
        );
    }

    public function setTierActive(int $id, bool $active, int $adminId): void
    {
        if (!$this->repository->setActive('tiers', $id, $active, $adminId)) {
            throw new CatalogResourceNotFoundException();
        }
    }

    public function setPerkActive(int $id, bool $active, int $adminId): void
    {
        if (!$this->repository->setActive('tier_perks', $id, $active, $adminId)) {
            throw new CatalogResourceNotFoundException();
        }
    }
}
