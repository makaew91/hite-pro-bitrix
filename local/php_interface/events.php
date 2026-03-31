<?php

/**
 * Регистрация обработчиков событий.
 *
 * Все обработчики регистрируются здесь через EventManager.
 * Классы-обработчики расположены в модуле makaew.store.
 */

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

// --- Обработчики заказов ---
// Валидация и обработка перед сохранением заказа
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    ['\Makaew\Store\EventHandler\OrderHandler', 'onBeforeSaved']
);

// Действия после создания заказа (уведомления, логирование)
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    ['\Makaew\Store\EventHandler\OrderHandler', 'onSaved']
);
