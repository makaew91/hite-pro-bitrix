<?php

/**
 * Миграция: создание инфоблока «Каталог материалов».
 *
 * Инфоблок с типизированными свойствами:
 * бренд (справочник HL), единица измерения, артикул,
 * расход, цена, наличие, характеристики.
 */

use Bitrix\Main\Loader;

Loader::includeModule('iblock');

// --- Создание инфоблока ---
$iblock = new CIBlock();
$existingIblock = $iblock::GetList(
    [],
    ['CODE' => IBLOCK_CATALOG_CODE, 'TYPE' => IBLOCK_CATALOG_TYPE]
)->Fetch();

if (!$existingIblock) {
    $iblockId = $iblock->Add([
        'NAME' => 'Каталог материалов',
        'CODE' => IBLOCK_CATALOG_CODE,
        'IBLOCK_TYPE_ID' => IBLOCK_CATALOG_TYPE,
        'SITE_ID' => ['s1'],
        'SORT' => 100,
        'GROUP_ID' => ['2' => 'R'], // Все пользователи — чтение
        'VERSION' => 2,             // Хранение свойств в отдельной таблице
        'INDEX_ELEMENT' => 'Y',
        'INDEX_SECTION' => 'Y',
        'LIST_PAGE_URL' => '/catalog/',
        'SECTION_PAGE_URL' => '/catalog/#SECTION_CODE#/',
        'DETAIL_PAGE_URL' => '/catalog/#SECTION_CODE#/#ELEMENT_CODE#/',
    ]);

    if (!$iblockId) {
        throw new RuntimeException('Failed to create iblock: ' . $iblock->LAST_ERROR);
    }

    echo "Iblock 'catalog' created with ID: {$iblockId}\n";
} else {
    $iblockId = (int) $existingIblock['ID'];
    echo "Iblock 'catalog' already exists (ID: {$iblockId}), skipping.\n";
}

// --- Свойства инфоблока ---
$properties = [
    [
        'NAME' => 'Артикул',
        'CODE' => 'ARTICLE',
        'PROPERTY_TYPE' => 'S',         // Строка
        'IS_REQUIRED' => 'N',
        'SEARCHABLE' => 'Y',
        'FILTRABLE' => 'Y',
        'SORT' => 100,
    ],
    [
        'NAME' => 'Бренд',
        'CODE' => 'BRAND',
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => 'directory',      // Привязка к HL-блоку
        'USER_TYPE_SETTINGS' => [
            'TABLE_NAME' => 'b_hlbd_brands', // Таблица HL-блока
        ],
        'IS_REQUIRED' => 'N',
        'FILTRABLE' => 'Y',
        'SORT' => 200,
    ],
    [
        'NAME' => 'Единица измерения',
        'CODE' => 'UNIT',
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => 'directory',
        'USER_TYPE_SETTINGS' => [
            'TABLE_NAME' => 'b_hlbd_units',
        ],
        'IS_REQUIRED' => 'N',
        'SORT' => 300,
    ],
    [
        'NAME' => 'Расход на м²',
        'CODE' => 'CONSUMPTION_RATE',
        'PROPERTY_TYPE' => 'N',         // Число
        'IS_REQUIRED' => 'N',
        'FILTRABLE' => 'Y',
        'SORT' => 400,
    ],
    [
        'NAME' => 'Единица расхода',
        'CODE' => 'CONSUMPTION_UNIT',
        'PROPERTY_TYPE' => 'L',         // Список
        'VALUES' => [
            ['VALUE' => 'кг/м²', 'SORT' => 100],
            ['VALUE' => 'л/м²', 'SORT' => 200],
            ['VALUE' => 'шт/м²', 'SORT' => 300],
            ['VALUE' => 'м/м²', 'SORT' => 400],
        ],
        'SORT' => 500,
    ],
    [
        'NAME' => 'Вес (кг)',
        'CODE' => 'WEIGHT',
        'PROPERTY_TYPE' => 'N',
        'IS_REQUIRED' => 'N',
        'SORT' => 600,
    ],
    [
        'NAME' => 'Объём упаковки',
        'CODE' => 'PACKAGE_VOLUME',
        'PROPERTY_TYPE' => 'S',
        'IS_REQUIRED' => 'N',
        'SORT' => 700,
    ],
    [
        'NAME' => 'Цвет',
        'CODE' => 'COLOR',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE' => 'Y',
        'FILTRABLE' => 'Y',
        'SORT' => 800,
    ],
    [
        'NAME' => 'Новинка',
        'CODE' => 'IS_NEW',
        'PROPERTY_TYPE' => 'L',
        'VALUES' => [
            ['VALUE' => 'Да', 'SORT' => 100],
        ],
        'FILTRABLE' => 'Y',
        'SORT' => 900,
    ],
    [
        'NAME' => 'Хит продаж',
        'CODE' => 'IS_HIT',
        'PROPERTY_TYPE' => 'L',
        'VALUES' => [
            ['VALUE' => 'Да', 'SORT' => 100],
        ],
        'FILTRABLE' => 'Y',
        'SORT' => 1000,
    ],
    [
        'NAME' => 'Документация',
        'CODE' => 'DOCS',
        'PROPERTY_TYPE' => 'F',         // Файл
        'MULTIPLE' => 'Y',
        'SORT' => 1100,
    ],
];

$iblockProperty = new CIBlockProperty();

foreach ($properties as $property) {
    $existing = CIBlockProperty::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'CODE' => $property['CODE']]
    )->Fetch();

    if ($existing) {
        echo "Property '{$property['CODE']}' already exists, skipping.\n";
        continue;
    }

    $property['IBLOCK_ID'] = $iblockId;

    // Значения списка добавляются отдельно
    $values = $property['VALUES'] ?? null;
    unset($property['VALUES']);

    $propId = $iblockProperty->Add($property);
    if (!$propId) {
        echo "Warning: failed to create property '{$property['CODE']}': " . $iblockProperty->LAST_ERROR . "\n";
        continue;
    }

    // Добавляем значения для свойств типа «Список»
    if ($values && $property['PROPERTY_TYPE'] === 'L') {
        $enumObj = new CIBlockPropertyEnum();
        foreach ($values as $enumValue) {
            $enumObj->Add([
                'PROPERTY_ID' => $propId,
                'VALUE' => $enumValue['VALUE'],
                'SORT' => $enumValue['SORT'] ?? 500,
            ]);
        }
    }

    echo "Property '{$property['CODE']}' created (ID: {$propId}).\n";
}
