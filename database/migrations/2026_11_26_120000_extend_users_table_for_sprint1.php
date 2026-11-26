<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Расширение таблицы users под требования Спринта 1 ТЗ «Личный капитал».
 *
 * Профиль пользователя (п. 4 ТЗ) хранится прямо в users:
 * контакты, семья и работа, текстовые блоки, шкала активного дохода.
 * Разделять на отдельные таблицы рано — спринт 1, структура может
 * уточняться в следующих спринтах (таблицы доходов/расходов/активов).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // --- Контакты (п. 4 «Контакты») ---
            $table->string('last_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('city')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo_path')->nullable();
            // Предпочитаемые мессенджеры: MAX, Telegram, WhatsApp — выбор нескольких.
            // json вместо отдельной таблицы: список короткий и фиксированный ТЗ.
            $table->json('messengers')->nullable();

            // --- Семья и работа (п. 4 «Семья и работа») ---
            $table->string('family_status')->nullable();
            $table->unsignedTinyInteger('children')->nullable();
            $table->string('employment')->nullable();
            $table->string('profession')->nullable();
            $table->string('risk_attitude')->nullable();
            $table->string('planning_horizon')->nullable();

            // --- Два текстовых блока (п. 4) ---
            $table->text('about_situation')->nullable();
            $table->text('investment_strategy')->nullable();

            // --- Шкала активного дохода: до какого возраста планирует зарабатывать ---
            $table->unsignedTinyInteger('active_income_until')->default(60);

            // --- Служебное: роль, блокировка, реферальная связь «кто пригласил» ---
            $table->string('role')->default('user');
            $table->boolean('is_blocked')->default(false);
            $table->foreignId('invited_by_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by_id');
            $table->dropColumn([
                'last_name', 'phone', 'city', 'birth_date', 'photo_path', 'messengers',
                'family_status', 'children', 'employment', 'profession', 'risk_attitude',
                'planning_horizon', 'about_situation', 'investment_strategy',
                'active_income_until', 'role', 'is_blocked',
            ]);
        });
    }
};
