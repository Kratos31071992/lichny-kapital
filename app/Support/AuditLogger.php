<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Пишет записи в журнал действий администраторов (п. 5 ТЗ).
 *
 * Использование в контроллерах админки:
 *   AuditLogger::log($request, 'user.block', $user, ['is_blocked' => true]);
 */
final class AuditLogger
{
    /**
     * @param  string  $action  Системное имя действия, например user.update
     * @param  User|null  $target  Пользователь, над которым выполнено действие
     * @param  array  $details  Что именно изменилось (старые/новые значения)
     */
    public static function log(Request $request, string $action, ?User $target = null, array $details = []): void
    {
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'target_user_id' => $target?->id,
            'action' => $action,
            'details' => $details ?: null,
            'ip' => $request->ip(),
        ]);
    }
}
