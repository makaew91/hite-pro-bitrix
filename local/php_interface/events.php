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

// Обработчики событий будут добавлены в feature/event-handlers
// и feature/cart-order ветках.
