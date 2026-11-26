<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewUserRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Регистрация (п. 2 ТЗ).
 *
 * Поля формы: фамилия, имя, email, телефон, город, пароль
 * (с подтверждением и индикатором надёжности на фронтенде),
 * чекбокс согласия с документами, антиспам-защита.
 */
class RegisteredUserController extends Controller
{
    /** Форма регистрации. Показывается в модальном окне посадочной страницы. */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'captcha' => $this->captchaQuestion(),
        ]);
    }

    /**
     * Обработка регистрации.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Ограничение частоты (5 попыток/мин с IP) выполняет middleware
        // throttle:register на маршруте (лимитер объявлен в AppServiceProvider).
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Чекбокс согласия с Пользовательским соглашением и Политикой (п. 8 ТЗ).
            'agreement' => 'accepted',
            // Honeypot-поле: реальный пользователь его не заполняет, бот — заполняет.
            'website' => 'present|size:0',
            // Демо-капча. В продакшене заменяется на Яндекс SmartCaptcha:
            // проверка выполняется в отдельном middleware перед контроллером.
            'captcha_answer' => 'required|integer|same:captcha_expected',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'city' => $validated['city'],
            'password' => Hash::make($validated['password']),
        ]);

        // Событие Laravel отправляет письмо со ссылкой подтверждения email.
        // Без подтверждения полный вход недоступен (MustVerifyEmail + middleware verified).
        event(new Registered($user));

        // Уведомление админу о новой регистрации (п. 5 ТЗ), уходит в очередь.
        User::where('role', User::ROLE_ADMIN)->cursor()
            ->each(fn (User $admin) => $admin->notify(new NewUserRegistered($user)));

        Auth::login($user);

        // После регистрации — на страницу «проверьте почту», а не сразу в кабинет.
        return redirect()->route('verification.notice');
    }

    /**
     * Демонстрационная текстовая капча «решите пример».
     * В боевом проекте заменяется на Яндекс SmartCaptcha (п. 2 ТЗ).
     */
    private function captchaQuestion(): array
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);

        // Правильный ответ кладём в сессию и отдаём во фронтенд как
        // скрытое поле captcha_expected — для каркаса этого достаточно.
        // SmartCaptcha хранит токен на своей стороне и верифицирует сервер-сервер.
        session(['captcha_expected' => $a + $b]);

        return ['question' => "$a + $b = ?", 'expected' => $a + $b];
    }
}
