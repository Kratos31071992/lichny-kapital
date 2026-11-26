<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationChannelManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Раздел «Настройки интеграций» (п. 6 ТЗ).
 * Хранит поля для будущих ключей внешних сервисов (SMS-провайдер и т.п.)
 * и позволяет выполнить тестовую отправку по каждому каналу.
 */
class IntegrationController extends Controller
{
    /** Список каналов, видимых в настройках. SMS подключается заменой драйвера. */
    private const CHANNELS = ['mail' => 'Email (почтовый драйвер)', 'sms' => 'SMS (демо-драйвер)'];

    public function index(): Response
    {
        return Inertia::render('Admin/Integrations/Index', [
            'channels' => self::CHANNELS,
            // Значения ключей не показываем: в спринте 1 их ещё нет.
            'settings' => [
                'sms_api_key' => config('services.sms.api_key'),
                'mail_from' => config('mail.from.address'),
            ],
        ]);
    }

    /** Тестовая отправка (п. 6 ТЗ, кнопка «Тестовая отправка»). */
    public function test(Request $request, NotificationChannelManager $manager): RedirectResponse
    {
        $validated = $request->validate([
            'channel' => 'required|in:mail,sms',
        ]);

        $result = $manager->sendTest($request->user(), $validated['channel']);

        return back()->with('status', $result);
    }

    /**
     * Демонстрация бэкапа: создаёт копию БД (команда описана ниже).
     * В продакшене бэкапы запускаются cron-задачей, а не из админки.
     */
    public function backupNow(): RedirectResponse
    {
        Artisan::call('backup:database');

        return back()->with('status', 'Резервная копия создана: ' . trim(Artisan::output()));
    }
}
