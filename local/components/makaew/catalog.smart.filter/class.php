<?php

use Bitrix\Iblock\PropertyIndex\Manager as FacetManager;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Умный фильтр каталога с фасетным индексом.
 *
 * Формирует параметры фильтрации по свойствам инфоблока,
 * поддерживает AJAX-обновление списка товаров.
 *
 * Параметры:
 *   IBLOCK_ID   — ID инфоблока
 *   SECTION_ID  — ID текущего раздела
 *   FILTER_NAME — Имя массива фильтра в $GLOBALS
 *   CACHE_TIME  — Время кэширования
 */
class CatalogSmartFilterComponent extends CBitrixComponent
{
    /** Свойства, доступные для фильтрации */
    private const FILTER_PROPERTIES = [
        'BRAND', 'COLOR', 'CONSUMPTION_UNIT', 'IS_NEW', 'IS_HIT',
    ];

    /** Числовые свойства для фильтрации по диапазону */
    private const RANGE_PROPERTIES = [
        'CONSUMPTION_RATE', 'WEIGHT',
    ];

    public function onPrepareComponentParams($arParams): array
    {
        $arParams['IBLOCK_ID'] = (int) ($arParams['IBLOCK_ID'] ?? IBLOCK_CATALOG_ID);
        $arParams['SECTION_ID'] = (int) ($arParams['SECTION_ID'] ?? 0);
        $arParams['FILTER_NAME'] = $arParams['FILTER_NAME'] ?? 'arrFilter';
        $arParams['CACHE_TIME'] = (int) ($arParams['CACHE_TIME'] ?? 3600);

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            ShowError('Модули iblock/catalog не установлены.');

            return;
        }

        $this->arResult = $this->buildFilterData();
        $this->applyFilter();
        $this->includeComponentTemplate();
    }

    /**
     * Построение данных для фильтра.
     *
     * Собирает доступные значения свойств с учётом текущего раздела
     * и фасетного индекса для быстрого подсчёта.
     */
    private function buildFilterData(): array
    {
        $iblockId = $this->arParams['IBLOCK_ID'];
        $sectionId = $this->arParams['SECTION_ID'];

        // Проверяем наличие фасетного индекса
        $facetEnabled = FacetManager::isReadyIndex($iblockId);

        $filterItems = [];

        // Ценовой диапазон
        $filterItems['PRICE'] = $this->getPriceRange($sectionId);

        // Свойства-списки и строки
        foreach (self::FILTER_PROPERTIES as $propCode) {
            $filterItems[$propCode] = $this->getPropertyValues($propCode, $sectionId, $facetEnabled);
        }

        // Числовые диапазоны
        foreach (self::RANGE_PROPERTIES as $propCode) {
            $filterItems[$propCode] = $this->getPropertyRange($propCode, $sectionId);
        }

        return [
            'ITEMS' => $filterItems,
            'SECTION_ID' => $sectionId,
            'FACET_ENABLED' => $facetEnabled,
        ];
    }

    /**
     * Получить диапазон цен в разделе.
     */
    private function getPriceRange(int $sectionId): array
    {
        $filter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
            'INCLUDE_SUBSECTIONS' => 'Y',
        ];

        if ($sectionId > 0) {
            $filter['SECTION_ID'] = $sectionId;
        }

        $prices = \CCatalogProduct::GetList(
            [],
            $filter,
            false,
            false,
            ['ELEMENT_ID', 'CATALOG_PRICE_1']
        );

        $min = PHP_INT_MAX;
        $max = 0;

        while ($price = $prices->Fetch()) {
            $val = (float) $price['CATALOG_PRICE_1'];
            if ($val > 0) {
                $min = min($min, $val);
                $max = max($max, $val);
            }
        }

        return [
            'NAME' => 'Цена',
            'TYPE' => 'range',
            'MIN' => $min === PHP_INT_MAX ? 0 : $min,
            'MAX' => $max,
            'CURRENT_MIN' => (float) ($_REQUEST['PRICE_MIN'] ?? 0),
            'CURRENT_MAX' => (float) ($_REQUEST['PRICE_MAX'] ?? 0),
        ];
    }

    /**
     * Получить доступные значения свойства.
     */
    private function getPropertyValues(string $propCode, int $sectionId, bool $facetEnabled): array
    {
        $property = \CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'CODE' => $propCode,
            ]
        )->Fetch();

        if (!$property) {
            return [];
        }

        $values = [];

        // Для свойств типа «Список» — берём значения из enum
        if ($property['PROPERTY_TYPE'] === 'L') {
            $enumRes = \CIBlockPropertyEnum::GetList(
                ['SORT' => 'ASC'],
                ['PROPERTY_ID' => $property['ID']]
            );
            while ($enum = $enumRes->Fetch()) {
                $values[] = [
                    'ID' => $enum['ID'],
                    'VALUE' => $enum['VALUE'],
                    'CHECKED' => in_array($enum['ID'], (array) ($_REQUEST['PROP_' . $propCode] ?? [])),
                ];
            }
        } elseif ($property['USER_TYPE'] === 'directory') {
            // Для справочников — из HL-блока
            $values = $this->getDirectoryValues($property);
        } else {
            // Для строковых — уникальные значения из элементов
            $values = $this->getUniqueStringValues($propCode, $sectionId);
        }

        return [
            'NAME' => $property['NAME'],
            'CODE' => $propCode,
            'TYPE' => ($property['PROPERTY_TYPE'] === 'N') ? 'range' : 'checkbox',
            'VALUES' => $values,
        ];
    }

    /**
     * Получить значения из справочника (HL-блок).
     */
    private function getDirectoryValues(array $property): array
    {
        $tableName = $property['USER_TYPE_SETTINGS']['TABLE_NAME'] ?? '';
        if (empty($tableName)) {
            return [];
        }

        Loader::includeModule('highloadblock');

        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => $tableName],
        ])->fetch();

        if (!$hlblock) {
            return [];
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        $rows = $entityClass::getList([
            'select' => ['UF_NAME', 'UF_XML_ID'],
            'order' => ['UF_SORT' => 'ASC', 'UF_NAME' => 'ASC'],
        ])->fetchAll();

        $values = [];
        $currentValues = (array) ($_REQUEST['PROP_' . $property['CODE']] ?? []);

        foreach ($rows as $row) {
            $values[] = [
                'ID' => $row['UF_XML_ID'],
                'VALUE' => $row['UF_NAME'],
                'CHECKED' => in_array($row['UF_XML_ID'], $currentValues),
            ];
        }

        return $values;
    }

    /**
     * Получить уникальные строковые значения свойства.
     */
    private function getUniqueStringValues(string $propCode, int $sectionId): array
    {
        $filter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
        ];

        if ($sectionId > 0) {
            $filter['SECTION_ID'] = $sectionId;
            $filter['INCLUDE_SUBSECTIONS'] = 'Y';
        }

        $dbRes = \CIBlockElement::GetList(
            [],
            $filter,
            false,
            false,
            ['ID', 'PROPERTY_' . $propCode]
        );

        $unique = [];
        $currentValues = (array) ($_REQUEST['PROP_' . $propCode] ?? []);

        while ($el = $dbRes->Fetch()) {
            $val = $el['PROPERTY_' . $propCode . '_VALUE'] ?? '';
            if ($val !== '' && !isset($unique[$val])) {
                $unique[$val] = [
                    'ID' => $val,
                    'VALUE' => $val,
                    'CHECKED' => in_array($val, $currentValues),
                ];
            }
        }

        return array_values($unique);
    }

    /**
     * Получить мин/макс числового свойства.
     */
    private function getPropertyRange(string $propCode, int $sectionId): array
    {
        $property = \CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'CODE' => $propCode,
            ]
        )->Fetch();

        if (!$property) {
            return [];
        }

        $filter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
        ];

        if ($sectionId > 0) {
            $filter['SECTION_ID'] = $sectionId;
            $filter['INCLUDE_SUBSECTIONS'] = 'Y';
        }

        $dbRes = \CIBlockElement::GetList([], $filter, false, false, [
            'ID', 'PROPERTY_' . $propCode,
        ]);

        $min = PHP_INT_MAX;
        $max = 0;

        while ($el = $dbRes->Fetch()) {
            $val = (float) ($el['PROPERTY_' . $propCode . '_VALUE'] ?? 0);
            if ($val > 0) {
                $min = min($min, $val);
                $max = max($max, $val);
            }
        }

        return [
            'NAME' => $property['NAME'],
            'CODE' => $propCode,
            'TYPE' => 'range',
            'MIN' => $min === PHP_INT_MAX ? 0 : $min,
            'MAX' => $max,
            'CURRENT_MIN' => (float) ($_REQUEST[$propCode . '_MIN'] ?? 0),
            'CURRENT_MAX' => (float) ($_REQUEST[$propCode . '_MAX'] ?? 0),
        ];
    }

    /**
     * Применить текущие параметры фильтра в глобальный массив.
     */
    private function applyFilter(): void
    {
        $filterName = $this->arParams['FILTER_NAME'];
        $GLOBALS[$filterName] = [];

        // Ценовой фильтр
        if (!empty($_REQUEST['PRICE_MIN'])) {
            $GLOBALS[$filterName]['>=CATALOG_PRICE_1'] = (float) $_REQUEST['PRICE_MIN'];
        }
        if (!empty($_REQUEST['PRICE_MAX'])) {
            $GLOBALS[$filterName]['<=CATALOG_PRICE_1'] = (float) $_REQUEST['PRICE_MAX'];
        }

        // Свойства-чекбоксы
        foreach (self::FILTER_PROPERTIES as $code) {
            $requestKey = 'PROP_' . $code;
            if (!empty($_REQUEST[$requestKey])) {
                $values = array_filter(
                    array_map('trim', (array) $_REQUEST[$requestKey])
                );
                if ($values) {
                    $GLOBALS[$filterName]['=PROPERTY_' . $code] = $values;
                }
            }
        }

        // Числовые диапазоны
        foreach (self::RANGE_PROPERTIES as $code) {
            if (!empty($_REQUEST[$code . '_MIN'])) {
                $GLOBALS[$filterName]['>=PROPERTY_' . $code] = (float) $_REQUEST[$code . '_MIN'];
            }
            if (!empty($_REQUEST[$code . '_MAX'])) {
                $GLOBALS[$filterName]['<=PROPERTY_' . $code] = (float) $_REQUEST[$code . '_MAX'];
            }
        }
    }
}
