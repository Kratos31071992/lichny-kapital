<?php

/**
 * Настройки резервного копирования базы данных (ТЗ: ежедневные бэкапы).
 * Диск 'backups' объявлен в config/filesystems.php и ведёт ВНЕ сервера —
 * копия должна пережить отказ самого сервера.
 */
return [
    'disk' => env('BACKUP_DISK', 'backups'),
    'retention_days' => env('BACKUP_RETENTION_DAYS', 14),
];
