<?php

/**
 * Миграция: создание типа инфоблока для каталога.
 *
 * Тип инфоблока — контейнер для группировки связанных
 * инфоблоков (каталог, новости, акции и т.д.).
 */

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

$iblockType = [
    'ID' => IBLOCK_CATALOG_TYPE,
    'SECTIONS' => 'Y',
    'IN_RSS' => 'N',
    'SORT' => 100,
    'LANG' => [
        'ru' => [
            'NAME' => 'Каталог стройматериалов',
            'SECTION_NAME' => 'Разделы',
            'ELEMENT_NAME' => 'Товары',
        ],
        'en' => [
            'NAME' => 'Construction Materials Catalog',
            'SECTION_NAME' => 'Sections',
            'ELEMENT_NAME' => 'Products',
        ],
    ],
];

$iblockTypeObj = new CIBlockType();
$existingType = CIBlockType::GetByIDLang(IBLOCK_CATALOG_TYPE, 'ru');

if (!$existingType) {
    $result = $iblockTypeObj->Add($iblockType);
    if (!$result) {
        throw new RuntimeException(
            'Failed to create iblock type: ' . $iblockTypeObj->LAST_ERROR
        );
    }
    echo "Iblock type '" . IBLOCK_CATALOG_TYPE . "' created.\n";
} else {
    echo "Iblock type '" . IBLOCK_CATALOG_TYPE . "' already exists, skipping.\n";
}
