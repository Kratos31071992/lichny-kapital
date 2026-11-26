<?php

namespace App\Services;

use App\Models\User;

/**
 * Считает, на сколько процентов заполнен профиль пользователя.
 *
 * Индикатор «Профиль заполнен на N%» из п. 4 ТЗ считается по заполненным
 * БЛОКАМ, а не по отдельным полям: пользователю понятнее цель «заполнить
 * раздел», чем «дозаполнить 3 поля из 17».
 *
 * Формула: доля заполненных блоков × 100, округление вниз.
 */
final class ProfileCompletionService
{
    /**
     * Блоки профиля и обязательные поля каждого блока.
     * Блок считается заполненным, когда заполнены ВСЕ его обязательные поля.
     */
    private const BLOCKS = [
        'Контакты' => ['name', 'last_name', 'email', 'phone', 'city'],
        'Личные данные' => ['birth_date'],
        'Мессенджеры' => ['messengers'],
        'Семья и работа' => ['family_status', 'children', 'employment', 'profession'],
        'Отношение к риску и горизонт' => ['risk_attitude', 'planning_horizon'],
        'Моя ситуация' => ['about_situation'],
        'Моя инвестиционная стратегия' => ['investment_strategy'],
        'Фото' => ['photo_path'],
    ];

    /**
     * Возвращает процент заполненности (0–100).
     */
    public function percent(User $user): int
    {
        return (int) floor($this->fraction($user) * 100);
    }

    /**
     * Детализация по блокам — для подсказок «что заполнить дальше».
     *
     * @return array<int, array{name: string, complete: bool}>
     */
    public function blocks(User $user): array
    {
        return array_map(
            fn (string $name, array $fields) => [
                'name' => $name,
                'complete' => $this->blockComplete($user, $fields),
            ],
            array_keys(self::BLOCKS),
            self::BLOCKS,
        );
    }

    private function fraction(User $user): float
    {
        $complete = 0;

        foreach (self::BLOCKS as $fields) {
            if ($this->blockComplete($user, $fields)) {
                $complete++;
            }
        }

        return $complete / count(self::BLOCKS);
    }

    /** Блок заполнен, если каждое его поле непустое. */
    private function blockComplete(User $user, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($this->isEmpty($user->{$field})) {
                return false;
            }
        }

        return true;
    }

    /** Пустое значение: null, пустая строка или пустой массив (messengers). */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
