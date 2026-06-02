<?php

namespace Domain\Report\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Per-field lock that records who first filled a column on a given row.
 *
 * Once a field is locked by analyst A, only analyst A can overwrite that
 * specific cell. This protects data already entered by a previous analyst
 * when the report is handed over (lock released, status unchanged) and
 * a fellow analyst takes over.
 *
 * The table is intentionally polymorphic so the same mechanism applies to:
 *   - section_entries.time_start / time_end / column_label_value / cfu_bacteri / cfu_fungsi
 *   - section_instances.note
 *   - instrument_entries.no_id / calibration_date / due_date
 *   - medium_entries.batch_number / gpt_number / expiration_date
 *   - incubators.no_id / calibration_date / due_date_calibration
 *   - incubator_entries.date_in / time_in / date_out / time_out
 *
 * @property string      $id
 * @property string      $table_name
 * @property string      $row_id
 * @property string      $field_name
 * @property string      $filled_by    user_id
 * @property Carbon|null $filled_at
 */
class FieldLock extends Model
{
    use HasUuids;

    protected $fillable = [
        'table_name',
        'row_id',
        'field_name',
        'filled_by',
        'filled_at',
    ];

    protected $casts = [
        'filled_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, FieldLock>
     */
    public function filler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by');
    }
}
