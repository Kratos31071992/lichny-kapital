<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Доступ в админку только для роли admin (п. 5 ТЗ).
 * Навешивается на группу маршрутов /admin в routes/web.php.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Неавторизованных отправляем на вход; авторизованных не-админов — на дашборд.
        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->isAdmin()) {
            abort(403, 'Доступ только для администраторов.');
        }

        return $next($request);
    }
}
