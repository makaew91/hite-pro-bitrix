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

// --- Обработчики цен ---
// Логирование изменений цен для аудита
$eventManager->addEventHandler(
    'catalog',
    'OnPriceUpdate',
    ['\Makaew\Store\EventHandler\PriceChangeHandler', 'onPriceUpdate']
);

// --- Инвалидация кэша каталога ---
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    ['\Makaew\Store\EventHandler\CacheInvalidationHandler', 'onElementChange']
);

$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementUpdate',
    ['\Makaew\Store\EventHandler\CacheInvalidationHandler', 'onElementChange']
);

$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementDelete',
    ['\Makaew\Store\EventHandler\CacheInvalidationHandler', 'onElementChange']
);

$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockSectionAdd',
    ['\Makaew\Store\EventHandler\CacheInvalidationHandler', 'onSectionChange']
);

$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockSectionUpdate',
    ['\Makaew\Store\EventHandler\CacheInvalidationHandler', 'onSectionChange']
);

$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockSectionDelete',
    ['\Makaew\Store\EventHandler\CacheInvalidationHandler', 'onSectionChange']
);
