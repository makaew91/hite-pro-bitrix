<?php

/**
 * Миграция: Highload-блок «Единицы измерения».
 *
 * Справочник единиц измерения для товаров каталога.
 * Используется как свойство типа «Справочник» в инфоблоке.
 *
 * Поля: UF_NAME, UF_XML_ID, UF_SHORT_NAME, UF_SORT.
 */

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');

// --- Создание HL-блока ---
$hlblockData = [
    'NAME' => 'Units',
    'TABLE_NAME' => 'b_hlbd_units',
];

$existing = HighloadBlockTable::getList([
    'filter' => ['=TABLE_NAME' => $hlblockData['TABLE_NAME']],
])->fetch();

if ($existing) {
    $hlblockId = (int) $existing['ID'];
    echo "Highload block 'Units' already exists (ID: {$hlblockId}), skipping creation.\n";
} else {
    $result = HighloadBlockTable::add($hlblockData);
    if (!$result->isSuccess()) {
        throw new RuntimeException(
            'Failed to create HL block: ' . implode(', ', $result->getErrorMessages())
        );
    }
    $hlblockId = $result->getId();
    echo "Highload block 'Units' created (ID: {$hlblockId}).\n";
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
        'FIELD_NAME' => 'UF_SHORT_NAME',
        'USER_TYPE_ID' => 'string',
        'EDIT_FORM_LABEL' => ['ru' => 'Сокращение', 'en' => 'Short name'],
        'LIST_COLUMN_LABEL' => ['ru' => 'Сокр.', 'en' => 'Short'],
        'MANDATORY' => 'N',
        'SORT' => 300,
        'SETTINGS' => ['SIZE' => 20],
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

// --- Предзаполнение справочника ---
$defaultUnits = [
    ['UF_NAME' => 'Штука', 'UF_XML_ID' => 'pcs', 'UF_SHORT_NAME' => 'шт', 'UF_SORT' => 100],
    ['UF_NAME' => 'Килограмм', 'UF_XML_ID' => 'kg', 'UF_SHORT_NAME' => 'кг', 'UF_SORT' => 200],
    ['UF_NAME' => 'Литр', 'UF_XML_ID' => 'ltr', 'UF_SHORT_NAME' => 'л', 'UF_SORT' => 300],
    ['UF_NAME' => 'Квадратный метр', 'UF_XML_ID' => 'sqm', 'UF_SHORT_NAME' => 'м²', 'UF_SORT' => 400],
    ['UF_NAME' => 'Погонный метр', 'UF_XML_ID' => 'rm', 'UF_SHORT_NAME' => 'п.м.', 'UF_SORT' => 500],
    ['UF_NAME' => 'Кубический метр', 'UF_XML_ID' => 'cbm', 'UF_SHORT_NAME' => 'м³', 'UF_SORT' => 600],
    ['UF_NAME' => 'Упаковка', 'UF_XML_ID' => 'pack', 'UF_SHORT_NAME' => 'уп', 'UF_SORT' => 700],
    ['UF_NAME' => 'Мешок', 'UF_XML_ID' => 'bag', 'UF_SHORT_NAME' => 'меш', 'UF_SORT' => 800],
];

$hlblock = HighloadBlockTable::getById($hlblockId)->fetch();
$entity = HighloadBlockTable::compileEntity($hlblock);
$entityClass = $entity->getDataClass();

foreach ($defaultUnits as $unit) {
    $existing = $entityClass::getList([
        'filter' => ['=UF_XML_ID' => $unit['UF_XML_ID']],
    ])->fetch();

    if ($existing) {
        echo "Unit '{$unit['UF_XML_ID']}' already exists, skipping.\n";
        continue;
    }

    $entityClass::add($unit);
    echo "Unit '{$unit['UF_XML_ID']}' added.\n";
}
