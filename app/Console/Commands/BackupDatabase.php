<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Резервное копирование базы данных (ТЗ: автоматическое, ежедневное).
 *
 * Использование:
 *   php artisan backup:database           — создать копию сейчас
 *   (cron) 0 3 * * * php artisan backup:database   — каждый день в 03:00
 *
 * Для PostgreSQL используется pg_dump через подключение из конфига.
 * Копии хранятся 14 дней (ротация), диск задаётся в конфиге backups.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Создать резервную копию базы данных (pg_dump) и удалить копии старше 14 дней';

    public function handle(): int
    {
        $disk = Storage::disk(config('backups.disk', 'local'));
        $fileName = 'backup-' . now()->format('Y-m-d-His') . '.sql';

        // pg_dump берёт параметры из конфигурации подключения PostgreSQL.
        $config = config('database.connections.' . config('database.default'));
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %s -U %s %s',
            escapeshellarg($config['password'] ?? ''),
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 5432)),
            escapeshellarg($config['username'] ?? ''),
            escapeshellarg($config['database'] ?? ''),
        );

        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('pg_dump завершился с ошибкой: ' . implode("\n", $output));
            return self::FAILURE;
        }

        $disk->put($fileName, implode("\n", $output));
        $this->info("Копия создана: {$fileName}");

        // Ротация: удаляем копии старше 14 дней.
        foreach ($disk->files() as $file) {
            if (str_starts_with(basename($file), 'backup-') && $disk->lastModified($file) < now()->subDays(14)->getTimestamp()) {
                $disk->delete($file);
                $this->line("Удалена устаревшая копия: {$file}");
            }
        }

        return self::SUCCESS;
    }
}
