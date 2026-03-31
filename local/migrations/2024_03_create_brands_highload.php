<?php

/**
 * Миграция: Highload-блок «Бренды».
 *
 * Справочник брендов (производителей) для привязки
 * к товарам каталога через свойство типа «Справочник».
 *
 * Поля: UF_NAME, UF_XML_ID, UF_LOGO, UF_SORT, UF_LINK.
 */

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');

// --- Создание HL-блока ---
$hlblockData = [
    'NAME' => 'Brands',
    'TABLE_NAME' => 'b_hlbd_brands',
];

$existing = HighloadBlockTable::getList([
    'filter' => ['=TABLE_NAME' => $hlblockData['TABLE_NAME']],
])->fetch();

if ($existing) {
    $hlblockId = (int) $existing['ID'];
    echo "Highload block 'Brands' already exists (ID: {$hlblockId}), skipping creation.\n";
} else {
    $result = HighloadBlockTable::add($hlblockData);
    if (!$result->isSuccess()) {
        throw new RuntimeException(
            'Failed to create HL block: ' . implode(', ', $result->getErrorMessages())
        );
    }
    $hlblockId = $result->getId();
    echo "Highload block 'Brands' created (ID: {$hlblockId}).\n";
}

// --- Пользовательские поля ---
$fields = [
    [
        'FIELD_NAME' => 'UF_NAME',
        'USER_TYPE_ID' => 'string',
        'EDIT_FORM_LABEL' => ['ru' => 'Название', 'en' => 'Name'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Название', 'en' => 'Name'],
        'MANDATORY' => 'Y',
        'SORT' => 100,
        'SETTINGS' => ['SIZE' => 50],
    ],
    [
        'FIELD_NAME' => 'UF_XML_ID',
        'USER_TYPE_ID' => 'string',
        'EDIT_FORM_LABEL' => ['ru' => 'Символьный код', 'en' => 'XML ID'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Символьный код', 'en' => 'XML ID'],
        'MANDATORY' => 'Y',
        'SORT' => 200,
        'SETTINGS' => ['SIZE' => 50],
    ],
    [
        'FIELD_NAME' => 'UF_LOGO',
        'USER_TYPE_ID' => 'file',
        'EDIT_FORM_LABEL' => ['ru' => 'Логотип', 'en' => 'Logo'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Логотип', 'en' => 'Logo'],
        'MANDATORY' => 'N',
        'SORT' => 300,
        'SETTINGS' => [
            'EXTENSIONS' => 'jpg,jpeg,png,svg,webp',
        ],
    ],
    [
        'FIELD_NAME' => 'UF_SORT',
        'USER_TYPE_ID' => 'integer',
        'EDIT_FORM_LABEL' => ['ru' => 'Сортировка', 'en' => 'Sort'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Сортировка', 'en' => 'Sort'],
        'MANDATORY' => 'N',
        'SORT' => 400,
        'SETTINGS' => ['DEFAULT_VALUE' => 500],
    ],
    [
        'FIELD_NAME' => 'UF_LINK',
        'USER_TYPE_ID' => 'url',
        'EDIT_FORM_LABEL' => ['ru' => 'Сайт производителя', 'en' => 'Manufacturer URL'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Сайт', 'en' => 'URL'],
        'MANDATORY' => 'N',
        'SORT' => 500,
    ],
];

$entityId = 'HLBLOCK_' . $hlblockId;
$userField = new CUserTypeEntity();

foreach ($fields as $fieldData) {
    $existingField = CUserTypeEntity::GetList(
        [],
        ['ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldData['FIELD_NAME']]
    )->Fetch();

    if ($existingField) {
        echo "Field '{$fieldData['FIELD_NAME']}' already exists, skipping.\n";
        continue;
    }

    $fieldData['ENTITY_ID'] = $entityId;
    $fieldId = $userField->Add($fieldData);

    if (!$fieldId) {
        echo "Warning: failed to create field '{$fieldData['FIELD_NAME']}'.\n";
        continue;
    }

    echo "Field '{$fieldData['FIELD_NAME']}' created (ID: {$fieldId}).\n";
}
