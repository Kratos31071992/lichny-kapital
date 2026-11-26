<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Админка: управление пользователями (п. 5 ТЗ).
 * Список с поиском/сортировкой/пагинацией, создание и редактирование,
 * генерация и смена паролей, блокировка, удаление (в т.ч. массовое).
 * Все изменения пишутся в журнал действий через AuditLogger.
 */
class UserController extends Controller
{
    /** Список пользователей (п. 5 ТЗ): поиск, сортировка, пагинация. */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('inviter:id,name,last_name')
            ->withCount('invites')
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->when($request->string('status')->toString(), function ($query, string $status) {
                // Статусы «email не подтверждён» / «активен» из ТЗ.
                $status === 'unconfirmed'
                    ? $query->whereNull('email_verified_at')
                    : $query->whereNotNull('email_verified_at');
            })
            ->orderBy($request->string('sort')->toString() ?: 'created_at', $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /** Форма создания пользователя администратором. */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/Edit', ['user' => null]);
    }

    /** Создание пользователя администратором (п. 5 ТЗ). */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ]);

        // Генерация пароля: админ задаёт его сам или получает случайный (п. 5 ТЗ).
        $password = $request->string('password')->toString() ?: Str::password(12);

        $user = User::create([
            ...$validated,
            'password' => Hash::make($password),
        ]);

        // Email считаем подтверждённым: аккаунт создан администратором вручную.
        $user->forceFill(['email_verified_at' => now()])->save();

        AuditLogger::log($request, 'user.create', $user, ['email' => $user->email]);

        return redirect()->route('admin.users.index')
            ->with('status', "Пользователь создан. Пароль: {$password}");
    }

    /** Форма редактирования пользователя. */
    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', ['user' => $user->load('inviter:id,name,last_name')]);
    }

    /** Редактирование пользователя администратором. */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ]);

        // Запоминаем старые значения для журнала действий (п. 5 ТЗ: «что менял»).
        $changes = array_diff_assoc($validated, $user->only(array_keys($validated)));

        $user->update($validated);

        AuditLogger::log($request, 'user.update', $user, $changes);

        return back()->with('status', 'Пользователь обновлён.');
    }

    /** Блокировка / разблокировка (п. 5 ТЗ: массовая блокировка спама). */
    public function block(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['blocked' => 'required|boolean']);

        // is_blocked не входит в обычный fillable (защита от подмены запроса),
        // поэтому меняем через forceFill.
        $user->forceFill(['is_blocked' => $validated['blocked']])->save();

        AuditLogger::log($request, $validated['blocked'] ? 'user.block' : 'user.unblock', $user);

        return back()->with('status', $validated['blocked'] ? 'Пользователь заблокирован.' : 'Пользователь разблокирован.');
    }

    /** Удаление пользователя (п. 5 ТЗ). */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Админ не может удалить сам себя — иначе система останется без доступа.
        abort_if($user->id === $request->user()->id, 422, 'Нельзя удалить собственную учётную запись.');

        AuditLogger::log($request, 'user.delete', $user, ['email' => $user->email]);
        $user->delete();

        return back()->with('status', 'Пользователь удалён.');
    }

    /** Массовые действия: блокировка и удаление выбранных (п. 5 ТЗ). */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id',
            'action' => ['required', Rule::in(['block', 'delete'])],
        ]);

        $users = User::whereIn('id', $validated['ids'])
            ->where('id', '!=', $request->user()->id) // себя исключаем
            ->get();

        foreach ($users as $user) {
            if ($validated['action'] === 'block') {
                $user->forceFill(['is_blocked' => true])->save();
                AuditLogger::log($request, 'user.block', $user, ['bulk' => true]);
            } else {
                AuditLogger::log($request, 'user.delete', $user, ['bulk' => true]);
                $user->delete();
            }
        }

        return back()->with('status', 'Массовое действие выполнено.');
    }

    /** Смена пароля пользователю (п. 5 ТЗ: «генерация и смена паролей»). */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => $request->boolean('generate')
                ? []
                : ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $password = $request->boolean('generate') ? Str::password(12) : $validated['password'];

        $user->update(['password' => Hash::make($password)]);

        AuditLogger::log($request, 'user.reset-password', $user);

        return back()->with('status', "Пароль изменён. Новый пароль: {$password}");
    }
}
