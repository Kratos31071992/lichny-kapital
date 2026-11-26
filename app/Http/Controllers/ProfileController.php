<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ProfileCompletionService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Профиль пользователя (п. 4 ТЗ).
 * Форма целиком: контакты, семья и работа, текстовые блоки,
 * шкала активного дохода, индикатор заполненности, экспорт и
 * полное удаление аккаунта (152-ФЗ).
 */
class ProfileController extends Controller
{
    public function __construct(private readonly ProfileCompletionService $completion)
    {
    }

    /** Форма профиля с данными текущего пользователя. */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'profileCompletion' => $this->completion->percent($request->user()),
            'profileBlocks' => $this->completion->blocks($request->user()),
            'messengers' => User::MESSENGERS,
        ]);
    }

    /** Сохранение профиля. Все поля опциональны — кроме базовых контактов. */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            // Мессенджеры: только значения из разрешённого списка (MAX, Telegram, WhatsApp).
            'messengers' => 'nullable|array',
            'messengers.*' => [Rule::in(User::MESSENGERS)],
            'family_status' => 'nullable|string|max:255',
            'children' => 'nullable|integer|min:0|max:30',
            'employment' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'risk_attitude' => 'nullable|string|max:255',
            'planning_horizon' => 'nullable|string|max:255',
            'about_situation' => 'nullable|string|max:10000',
            'investment_strategy' => 'nullable|string|max:10000',
            // Шкала активного дохода: возраст 0–100 (п. 4 ТЗ).
            'active_income_until' => 'nullable|integer|min:0|max:100',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Фото загружается отдельно от текстовых полей.
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('photos', 'public');
        }

        unset($validated['photo']);
        $user->fill($validated);

        // Смена email обнуляет подтверждение — пользователь подтвердит новый адрес.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'Профиль сохранён.');
    }

    /**
     * Экспорт данных пользователя (п. 4 ТЗ + 152-ФЗ):
     * «Скачать мои данные» — все персональные данные одним читаемым JSON-файлом.
     */
    public function export(Request $request)
    {
        $user = $request->user();

        // Формат JSON: структурирован, читаем, легко проверить целостность.
        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'profile' => $user->only([
                'name', 'last_name', 'email', 'phone', 'city', 'birth_date', 'messengers',
                'family_status', 'children', 'employment', 'profession',
                'risk_attitude', 'planning_horizon',
                'about_situation', 'investment_strategy', 'active_income_until',
                'created_at',
            ]),
        ], 200, [
            'Content-Disposition' => 'attachment; filename="my-data-' . $user->id . '.json"',
        ]);
    }

    /**
     * Полное удаление аккаунта (п. 4 ТЗ + 152-ФЗ).
     * Удаляются сам пользователь, его файл фото и сессия.
     * Записи журнала действий сохраняются (target_user_id станет null) —
     * журнал принадлежит платформе, а не пользователю.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
