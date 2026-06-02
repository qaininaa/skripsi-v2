<?php

namespace Domain\Report\Repositories;

use Domain\Report\Interfaces\FieldLockRepositoryInterface;
use Domain\Report\Models\FieldLock;
use Illuminate\Support\Collection;

class FieldLockRepository implements FieldLockRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function find(string $tableName, string $rowId, string $fieldName): ?FieldLock
    {
        return FieldLock::query()
            ->where('table_name', $tableName)
            ->where('row_id', $rowId)
            ->where('field_name', $fieldName)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getForRow(string $tableName, string $rowId): Collection
    {
        return FieldLock::query()
            ->where('table_name', $tableName)
            ->where('row_id', $rowId)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getForRows(string $tableName, array $rowIds): Collection
    {
        if (empty($rowIds)) {
            return collect();
        }

        return FieldLock::query()
            ->where('table_name', $tableName)
            ->whereIn('row_id', $rowIds)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function lock(string $tableName, string $rowId, string $fieldName, string $userId): FieldLock
    {
        return FieldLock::firstOrCreate(
            [
                'table_name' => $tableName,
                'row_id'     => $rowId,
                'field_name' => $fieldName,
            ],
            [
                'filled_by'  => $userId,
                'filled_at'  => now(),
            ],
        );
    }
}
