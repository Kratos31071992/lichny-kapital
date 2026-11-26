<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление администратору о новой регистрации (п. 5 ТЗ):
 * имя, город, email, телефон.
 *
 * ShouldQueue — письма уходят фоном через очередь Redis (п. 6 ТЗ),
 * регистрация пользователя не ждёт отправку почты.
 */
class NewUserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly User $newUser)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Новая регистрация — ' . $this->newUser->getFullNameAttribute())
            ->greeting('Зарегистрировался новый пользователь.')
            ->line('Имя: ' . $this->newUser->getFullNameAttribute())
            ->line('Город: ' . ($this->newUser->city ?? '—'))
            ->line('Email: ' . $this->newUser->email)
            ->line('Телефон: ' . ($this->newUser->phone ?? '—'));
    }
}
