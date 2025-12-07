<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'class_year_id',
        'actor_id',
        'action',
        'entity_type',
        'entity_id',
        'from_json',
        'to_json',
    ];

    protected $casts = [
        'from_json' => 'array',
        'to_json'   => 'array',
    ];

    // User yang melakukan aksi
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // Tahun ajaran yang terkait dengan aktivitas
    public function classYear(): BelongsTo
    {
        return $this->belongsTo(ClassYear::class, 'class_year_id');
    }

    /**
     * Helper static: ActivityLog::record(...)
     *
     * - $action: string, misal "expense_request.created"
     * - $entity: model yang terkait (optional, boleh null)
     * - $classYearId: id tahun ajaran (optional, tapi sangat disarankan diisi)
     * - $from: snapshot sebelum (array/null)
     * - $to: snapshot sesudah (array/null)
     */
    public static function record(
        string $action,
        $entity = null,
        ?int $classYearId = null,
        $from = null,
        $to = null
    ): void {
        try {
            static::create([
                'class_year_id' => $classYearId,
                'actor_id'      => auth()->id(),
                'action'        => $action,
                'entity_type'   => $entity ? class_basename($entity) : null,
                'entity_id'     => $entity->id ?? null,
                'from_json'     => $from,
                'to_json'       => $to,
            ]);
        } catch (\Throwable $e) {
            // jangan sampai log bikin error ke user
        }
    }
}
