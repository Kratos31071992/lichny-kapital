<?php

/*
 * Рецепт Deployer (deployer.org) — деплой на VPS заказчика.
 *
 * Атомарная выкладка: каждый деплой — новый каталог releases/<время>,
 * symlink current переключается только после успешной сборки.
 * Откат: dep rollback production.
 *
 * Запуск из GitHub Actions (см. .github/workflows/deploy.yml)
 * или вручную: dep deploy production
 */

namespace Deployer;

require 'recipe/laravel.php';

// Репозиторий заказчика: владелец кода — заказчик (п. «Правило владения» ТЗ).
set('repository', 'git@github.com:OWNER/lichny-kapital.git');

add('shared_files', ['.env']);
add('shared_dirs', ['storage']);
add('writable_dirs', ['storage', 'bootstrap/cache']);

// Хосты сервера заказчика. Данные — в deploy.yaml или переменных CI.
host('production')
    ->setHostname($_ENV['DEPLOY_HOST'] ?? 'example.com')
    ->setRemoteUser('deploy')
    ->setDeployPath('/var/www/lichny-kapital');

// После выкладки: миграции и перезапуск очередей (Redis).
after('deploy:vendors', 'artisan:migrate');
after('deploy:failed', 'deploy:unlock');

task('queue:restart', function () {
    run('cd {{release_path}} && php artisan queue:restart');
});
after('artisan:migrate', 'queue:restart');
