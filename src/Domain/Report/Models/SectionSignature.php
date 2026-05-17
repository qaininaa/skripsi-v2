<?php

namespace Domain\Report\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Per-section approval mark.
 *
 * @property string $id
 * @property string $section_instance_id
 * @property string $role            'monitoring' | 'reading' | 'review' | 'approval'
 * @property string $signed_by
 * @property Carbon $signed_at
 */
class SectionSignature extends Model
{
    use HasUuids;

    public const ROLE_MONITORING = 'monitoring';
    public const ROLE_READING    = 'reading';
    public const ROLE_REVIEW     = 'review';
    public const ROLE_APPROVAL   = 'approval';

    protected $fillable = [
        'section_instance_id',
        'role',
        'signed_by',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<SectionInstance, SectionSignature>
     */
    public function sectionInstance(): BelongsTo
    {
        return $this->belongsTo(SectionInstance::class, 'section_instance_id');
    }

    /**
     * @return BelongsTo<User, SectionSignature>
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
