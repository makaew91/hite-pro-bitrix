<?php

/**
 * Константы проекта.
 *
 * Централизованное хранение ID инфоблоков, HL-блоков
 * и прочих конфигурационных значений.
 */

defined('B_PROLOG_INCLUDED') || die();

// Идентификаторы инфоблоков
const IBLOCK_CATALOG_ID = 1;
const IBLOCK_CATALOG_CODE = 'catalog';
const IBLOCK_CATALOG_TYPE = 'store_catalog';

// Highload-блоки
const HL_BRANDS_ID = 1;
const HL_UNITS_ID = 2;

// Лимиты
const CATALOG_PAGE_SIZE = 20;
const API_DEFAULT_LIMIT = 50;
const API_MAX_LIMIT = 200;

// Пути
const EXCHANGE_LOG_DIR = '/local/logs/exchange/';
