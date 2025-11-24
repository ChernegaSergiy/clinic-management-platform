<?php

namespace App\Module\Inventory\Repository;

interface InventoryItemRepositoryInterface
{
    public function findAll(string $searchTerm = ''): array;
    public function save(array $data): bool;
    public function findById(int $id): ?array;
    public function update(int $id, array $data): bool;
    public function findItemsBelowMinStock(): array;
    public function findItemsAboveMaxStock(): array;
    // public function delete(int $id): bool;
}
