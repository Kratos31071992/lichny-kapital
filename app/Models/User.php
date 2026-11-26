<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Пользователь платформы «Личный капитал».
 *
 * Помимо аутентификации модель хранит весь профиль (п. 4 ТЗ):
 * контакты, семью и работу, текстовые блоки, шкалу активного дохода.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /** Мессенджеры, доступные для выбора в профиле (п. 4 «Контакты»). */
    public const MESSENGERS = ['MAX', 'Telegram', 'WhatsApp'];

    /** Роль администратора (п. 5 ТЗ). */
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * Поля, разрешённые для массового заполнения.
     * Не включаем role/is_blocked — их меняет только админка,
     * через отдельные методы, а не через форму профиля.
     */
    protected $fillable = [
        'name', 'last_name', 'email', 'phone', 'city', 'password',
        'birth_date', 'photo_path', 'messengers',
        'family_status', 'children', 'employment', 'profession',
        'risk_attitude', 'planning_horizon',
        'about_situation', 'investment_strategy', 'active_income_until',
    ];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Приведение типов при чтении из базы.
     * messengers хранится json-строкой, наружу отдаём массив.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'messengers' => 'array',
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Массово изменяемые админкой поля (не через форму профиля).
     * Отдельный список, чтобы role/is_blocked нельзя было подменить
     * через подделанный запрос от имени пользователя.
     */
    public function getAdminFillable(): array
    {
        return ['role', 'is_blocked'];
    }

    /** Форматное отображение ФИО в админке (п. 5 ТЗ, колонка «имя»). */
    public function getFullNameAttribute(): string
    {
        return trim($this->last_name . ' ' . $this->name);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isBlocked(): bool
    {
        return $this->is_blocked;
    }

    /** Кто пригласил пользователя (п. 5 ТЗ, колонка «кто пригласил»). */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    /** Приглашённые этим пользователем. */
    public function invites(): HasMany
    {
        return $this->hasMany(User::class, 'invited_by_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'target_user_id');
    }
}
