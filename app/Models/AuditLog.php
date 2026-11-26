<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Запись журнала действий администраторов (п. 5 ТЗ).
 * Кто, когда и что менял у пользователей.
 */
class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    protected $fillable = ['admin_id', 'target_user_id', 'action', 'details', 'ip'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    /** Администратор, совершивший действие. */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** Пользователь, над которым выполнено действие. */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
