<?php

namespace Domain\Report\Interfaces;

use Domain\Report\Models\FieldLock;
use Illuminate\Support\Collection;

/**
 * Contract for FieldLock data access.
 *
 * Identifies a field by the triplet (table_name, row_id, field_name).
 */
interface FieldLockRepositoryInterface
{
    /**
     * Lookup an existing lock for a single field.
     */
    public function find(string $tableName, string $rowId, string $fieldName): ?FieldLock;

    /**
     * Bulk lookup for an entire table+row pair.
     *
     * @return Collection<int, FieldLock>
     */
    public function getForRow(string $tableName, string $rowId): Collection;

    /**
     * Bulk lookup for many rows in the same table at once.
     *
     * @param  array<int, string>  $rowIds
     * @return Collection<int, FieldLock>
     */
    public function getForRows(string $tableName, array $rowIds): Collection;

    /**
     * Create or update a lock entry.
     *
     * Idempotent: if a lock already exists for the triplet, it is
     * NOT overwritten — the original filler keeps ownership.
     */
    public function lock(string $tableName, string $rowId, string $fieldName, string $userId): FieldLock;
}
