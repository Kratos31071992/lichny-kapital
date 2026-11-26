<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Регистрация по п. 2 ТЗ: поля формы, чекбокс согласия,
 * honeypot, капча, подтверждение email, уведомление админу.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** Корректные данные формы регистрации. */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan@example.com',
            'phone' => '+7 900 111-22-33',
            'city' => 'Нижний Новгород',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
            'agreement' => true,
            'website' => '', // honeypot должен быть пустым
            'captcha_answer' => 4,
            'captcha_expected' => 4,
        ], $overrides);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_user_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', $this->validPayload());

        $this->assertAuthenticated();
        $user = User::where('email', 'ivan@example.com')->first();

        // Все поля профиля из формы сохранились.
        $this->assertNotNull($user);
        $this->assertSame('Петров', $user->last_name);
        $this->assertSame('+7 900 111-22-33', $user->phone);
        $this->assertSame('Нижний Новгород', $user->city);

        // Email считается неподтверждённым — отправляется письмо со ссылкой.
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        // После регистрации — на страницу «проверьте почту», не в кабинет.
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    public function test_registration_requires_agreement_checkbox(): void
    {
        $response = $this->post('/register', $this->validPayload(['agreement' => false]));

        $response->assertSessionHasErrors('agreement');
        $this->assertGuest();
    }

    public function test_honeypot_field_rejects_bots(): void
    {
        // Бот заполнил скрытое поле — регистрация отклоняется.
        $response = $this->post('/register', $this->validPayload(['website' => 'http://spam.example']));

        $response->assertSessionHasErrors('website');
        $this->assertGuest();
    }

    public function test_wrong_captcha_is_rejected(): void
    {
        $response = $this->post('/register', $this->validPayload(['captcha_answer' => 99]));

        $response->assertSessionHasErrors('captcha_answer');
        $this->assertGuest();
    }

    public function test_admin_is_notified_about_new_registration(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->post('/register', $this->validPayload());

        Notification::assertSentTo($admin, \App\Notifications\NewUserRegistered::class);
    }
}
