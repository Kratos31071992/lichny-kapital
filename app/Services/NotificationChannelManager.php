<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Единый механизм уведомлений (п. 6 ТЗ).
 *
 * Сейчас письма отправляются через почтовый драйвер Laravel,
 * SMS — заглушка, пишущая в лог. Когда появится реальный SMS-сервис,
 * он подключается ЗАМЕНОЙ ДРАЙВЕРА (новый класс Channel + конфиг),
 * без переписывания кода, который шлёт уведомления.
 */
final class NotificationChannelManager
{
    public function __construct(private readonly string $smsDriver = 'log')
    {
    }

    /**
     * Отправить письмо через текущий почтовый драйвер.
     * Реальный SMTP/Yandex 360 подключается в config/mail.php без правки этого кода.
     */
    public function sendMail(User $user, string $subject, string $body): void
    {
        Mail::raw($body, function ($message) use ($user, $subject) {
            $message->to($user->email)->subject($subject);
        });
    }

    /**
     * Отправить SMS. Демо-драйвер пишет в лог; продакшн-драйвер
     * (СМС-Центр, Twilio и т.п.) реализует тот же контракт.
     */
    public function sendSms(User $user, string $body): void
    {
        if ($this->smsDriver === 'log') {
            Log::info('[SMS демо-драйвер] Получатель: ' . $user->phone, ['text' => $body]);
        }
    }

    /** Тестовая отправка из админки (п. 6 ТЗ, кнопка «Тестовая отправка»). */
    public function sendTest(User $admin, string $channel): string
    {
        if ($channel === 'sms') {
            $this->sendSms($admin, 'Тестовое SMS с платформы «Личный капитал».');

            return 'SMS отправлено демо-драйвером (см. storage/logs).';
        }

        $this->sendMail($admin, 'Тестовое письмо — Личный капитал', 'Если вы получили это письмо, почтовый канал работает.');

        return 'Письмо отправлено на ' . $admin->email;
    }
}
