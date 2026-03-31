<?php

/**
 * Простой раннер миграций.
 *
 * Запуск: php local/migrations/migrate.php
 *
 * Выполняет все файлы миграций в порядке сортировки по имени.
 * Для продакшена рекомендуется использовать специализированные
 * инструменты (например, sprint.migration или ws.migrations).
 */

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../../');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

echo "=== Running migrations ===\n\n";

$files = glob(__DIR__ . '/2024_*.php');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    echo "--- {$name} ---\n";

    try {
        require $file;
    } catch (Throwable $e) {
        echo "ERROR: {$e->getMessage()}\n";
        echo "File: {$e->getFile()}:{$e->getLine()}\n";
        exit(1);
    }

    echo "\n";
}

echo "=== All migrations completed ===\n";
