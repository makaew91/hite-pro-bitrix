<?php

/**
 * Точка входа для кастомного кода.
 *
 * Этот файл подключается ядром Битрикс автоматически
 * при каждом хите (аналог bootstrap).
 */

defined('B_PROLOG_INCLUDED') || die();

// Константы проекта
require_once __DIR__ . '/constants.php';

// Регистрация обработчиков событий
require_once __DIR__ . '/events.php';

// Composer autoload (если vendor в корне проекта)
$composerAutoload = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}
