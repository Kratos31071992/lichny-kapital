<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Журнал действий администраторов (п. 5 ТЗ):
 * кто, когда и что менял у пользователей.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with(['admin:id,name,last_name', 'targetUser:id,name,last_name,email'])
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->whereHas('targetUser', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only('search'),
        ]);
    }
}
