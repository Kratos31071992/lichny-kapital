<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Профиль пользователя (п. 4 ТЗ): редактирование, индикатор заполненности,
 * экспорт данных и полное удаление аккаунта (152-ФЗ).
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Иван',
            'last_name' => 'Петров',
            'email' => 'ivan@example.com',
            'phone' => '+7 900 111-22-33',
            'city' => 'Москва',
            'family_status' => 'В браке',
            'children' => 2,
            'employment' => 'Найм',
            'profession' => 'Инженер',
            'risk_attitude' => 'Умеренное',
            'planning_horizon' => '10+ лет',
            'about_situation' => 'Есть ипотека, накопления.',
            'investment_strategy' => 'Индексные фонды и недвижимость.',
            'active_income_until' => 60,
            'messengers' => ['Telegram', 'MAX'],
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Петров', $user->last_name);
        $this->assertSame(['Telegram', 'MAX'], $user->messengers);
        $this->assertSame(60, $user->active_income_until);
    }

    public function test_messengers_are_limited_to_allowed_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'last_name' => 'Тест',
            'email' => $user->email,
            'messengers' => ['Telegram', 'НесуществующийМессенджер'],
        ]);

        $response->assertSessionHasErrors('messengers.1');
    }

    public function test_profile_completion_percent_grows_with_filled_blocks(): void
    {
        $user = User::factory()->create([
            'name' => 'Иван',
            'last_name' => 'Петров',
            'phone' => '+7 900 111-22-33',
            'city' => 'Москва',
            'email' => 'ivan@example.com',
        ]);

        $page = $this->actingAs($user)->get('/profile');
        $page->assertOk();

        $percentBefore = $page->viewData('page')['props']['profileCompletion'];

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Иван', 'last_name' => 'Петров', 'email' => 'ivan@example.com',
            'phone' => '+7 900 111-22-33', 'city' => 'Москва',
            'about_situation' => 'Заполняю блок «Моя ситуация».',
            'investment_strategy' => 'Заполняю блок стратегии.',
            'family_status' => 'В браке', 'children' => 1,
            'employment' => 'Найм', 'profession' => 'Инженер',
            'risk_attitude' => 'Умеренное', 'planning_horizon' => '3–10 лет',
            'birth_date' => '1990-01-01',
        ])->assertSessionHasNoErrors();

        $percentAfter = $this->actingAs($user)->get('/profile')->viewData('page')['props']['profileCompletion'];

        $this->assertGreaterThan($percentBefore, $percentAfter);
    }

    public function test_user_can_export_their_data(): void
    {
        $user = User::factory()->create(['city' => 'Казань']);

        $response = $this->actingAs($user)->get('/profile/export');

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $data = $response->json();
        $this->assertSame('Казань', $data['profile']['city']);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ]);

        $response->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/profile')->delete('/profile', [
            'password' => 'wrong-password',
        ]);

        // Ошибка пароля возвращается в основной error-бег сессии.
        $response->assertSessionHasErrors('password');
        $this->assertNotNull($user->fresh());
    }
}
