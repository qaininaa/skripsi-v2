<?php

namespace Domain\Report\Services;

use Domain\Report\Interfaces\FieldLockRepositoryInterface;
use Domain\Report\Models\FieldLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Domain service for the per-field "first writer wins" locking model.
 *
 * Usage flow inside another service that persists analyst input:
 *
 *   $locks = $this->fieldLocks->getForRow('section_entries', $row->id);
 *
 *   if ($this->fieldLocks->canEdit($locks, 'time_start', $analystId)) {
 *       $row->time_start = $newValue;
 *       $row->save();
 *       if ($newValue !== null && $newValue !== '') {
 *           $this->fieldLocks->lockField('section_entries', $row->id, 'time_start', $analystId);
 *       }
 *   }
 *
 * The "lock" is created lazily — only when a field receives its first
 * non-empty value. Fields that have never been written remain editable
 * by any analyst on the same role.
 */
class FieldLockService
{
    public function __construct(
        protected FieldLockRepositoryInterface $repository,
    ) {
    }

    /**
     * Whether the given user is allowed to write the field, considering its
     * existing lock (if any). Pre-fetched locks may be passed to avoid
     * per-field DB hits inside save loops.
     *
     * @param  Collection<int, FieldLock>|null  $existingLocks
     */
    public function canEdit(?Collection $existingLocks, string $fieldName, string $userId): bool
    {
        if ($existingLocks === null) {
            return true;
        }
        $lock = $existingLocks->firstWhere('field_name', $fieldName);
        return $lock === null || $lock->filled_by === $userId;
    }

    /**
     * Lock or refresh ownership for a field. Returns true if the lock exists
     * after the call (whether newly created or already present).
     *
     * No-op when value is null/empty: we only lock fields that actually
     * carry data.
     */
    public function lockField(string $tableName, string $rowId, string $fieldName, string $userId, mixed $value = '__SKIP_CHECK__'): ?FieldLock
    {
        if ($value !== '__SKIP_CHECK__' && ($value === null || $value === '')) {
            return null;
        }
        return $this->repository->lock($tableName, $rowId, $fieldName, $userId);
    }

    /**
     * Bulk helper to fetch existing locks for one row, suitable for caching
     * in tight save loops. Repository may return an empty collection.
     */
    public function getForRow(string $tableName, string $rowId): Collection
    {
        return $this->repository->getForRow($tableName, $rowId);
    }

    /**
     * Bulk helper for many rows of the same table.
     *
     * @param  array<int, string>  $rowIds
     * @return array<string, Collection<int, FieldLock>>  keyed by row_id
     */
    public function getForRowsKeyed(string $tableName, array $rowIds): array
    {
        $rows = $this->repository->getForRows($tableName, $rowIds);
        $out = [];
        foreach ($rowIds as $id) {
            $out[$id] = collect();
        }
        foreach ($rows as $lock) {
            $out[$lock->row_id] = ($out[$lock->row_id] ?? collect())->push($lock);
        }
        return $out;
    }

    /**
     * Resolve the table name for a model. Convenience helper.
     */
    public function tableOf(Model $model): string
    {
        return $model->getTable();
    }
}
