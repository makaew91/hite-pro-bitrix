<?php

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент списка товаров каталога.
 *
 * Параметры:
 *   IBLOCK_ID      — ID инфоблока каталога
 *   SECTION_ID     — ID раздела (0 = все)
 *   PAGE_SIZE      — Количество элементов на странице
 *   CACHE_TIME     — Время кэширования (сек)
 *   SORT_BY        — Поле сортировки (SORT, NAME, ID)
 *   SORT_ORDER     — Направление (ASC, DESC)
 */
class CatalogListComponent extends CBitrixComponent
{
    /** @var array Обязательные модули */
    private const REQUIRED_MODULES = ['iblock', 'catalog'];

    public function onPrepareComponentParams($arParams): array
    {
        $arParams['IBLOCK_ID'] = (int) ($arParams['IBLOCK_ID'] ?? IBLOCK_CATALOG_ID);
        $arParams['SECTION_ID'] = (int) ($arParams['SECTION_ID'] ?? 0);
        $arParams['PAGE_SIZE'] = (int) ($arParams['PAGE_SIZE'] ?? CATALOG_PAGE_SIZE);
        $arParams['CACHE_TIME'] = (int) ($arParams['CACHE_TIME'] ?? 3600);
        $arParams['SORT_BY'] = $arParams['SORT_BY'] ?? 'SORT';
        $arParams['SORT_ORDER'] = $arParams['SORT_ORDER'] ?? 'ASC';

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!$this->checkModules()) {
            return;
        }

        if ($this->startResultCache($this->arParams['CACHE_TIME'])) {
            $this->arResult = $this->loadData();

            if (empty($this->arResult['ITEMS'])) {
                $this->abortResultCache();
            }

            $this->includeComponentTemplate();
        }
    }

    /**
     * Загрузка данных каталога.
     */
    private function loadData(): array
    {
        $filter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
        ];

        if ($this->arParams['SECTION_ID'] > 0) {
            $filter['SECTION_ID'] = $this->arParams['SECTION_ID'];
        }

        // Подсчёт общего количества
        $totalCount = ElementTable::getCount($filter);

        // Постраничная навигация
        $navParams = new \Bitrix\Main\UI\PageNavigation('catalog-nav');
        $navParams->allowAllRecords(false)
            ->setPageSize($this->arParams['PAGE_SIZE'])
            ->initFromUri();

        // Выборка элементов
        $query = ElementTable::getList([
            'filter' => $filter,
            'select' => [
                'ID', 'NAME', 'CODE', 'PREVIEW_TEXT',
                'PREVIEW_PICTURE', 'SORT', 'IBLOCK_SECTION_ID',
            ],
            'order' => [
                $this->arParams['SORT_BY'] => $this->arParams['SORT_ORDER'],
            ],
            'limit' => $navParams->getLimit(),
            'offset' => $navParams->getOffset(),
        ]);

        $items = [];
        while ($row = $query->fetch()) {
            $row['DETAIL_PAGE_URL'] = $this->getDetailPageUrl($row);
            $row['PREVIEW_PICTURE_SRC'] = $this->getImageSrc($row['PREVIEW_PICTURE']);
            $items[] = $row;
        }

        // Загружаем свойства для всех элементов разом
        if (!empty($items)) {
            $items = $this->loadProperties($items);
        }

        return [
            'ITEMS' => $items,
            'NAV' => $navParams,
            'TOTAL_COUNT' => $totalCount,
        ];
    }

    /**
     * Загрузка свойств элементов пакетно.
     */
    private function loadProperties(array $items): array
    {
        $elementIds = array_column($items, 'ID');
        $propertiesByElement = [];

        $propRes = \CIBlockElement::GetPropertyValuesArray(
            $propertiesByElement,
            $this->arParams['IBLOCK_ID'],
            ['ID' => $elementIds],
            ['CODE' => ['ARTICLE', 'BRAND', 'UNIT', 'IS_NEW', 'IS_HIT']]
        );

        foreach ($items as &$item) {
            $item['PROPERTIES'] = $propertiesByElement[$item['ID']] ?? [];
        }
        unset($item);

        return $items;
    }

    /**
     * Формирование URL детальной страницы.
     */
    private function getDetailPageUrl(array $item): string
    {
        $sectionCode = '';
        if (!empty($item['IBLOCK_SECTION_ID'])) {
            $section = \Bitrix\Iblock\SectionTable::getList([
                'filter' => ['=ID' => $item['IBLOCK_SECTION_ID']],
                'select' => ['CODE'],
            ])->fetch();
            $sectionCode = $section['CODE'] ?? '';
        }

        return '/catalog/' . $sectionCode . '/' . $item['CODE'] . '/';
    }

    /**
     * Получение src изображения по ID файла.
     */
    private function getImageSrc(?int $fileId): string
    {
        if (!$fileId) {
            return '';
        }

        $file = \CFile::GetFileArray($fileId);

        return $file ? $file['SRC'] : '';
    }

    /**
     * Проверка подключения модулей.
     */
    private function checkModules(): bool
    {
        foreach (self::REQUIRED_MODULES as $module) {
            if (!Loader::includeModule($module)) {
                ShowError("Модуль '{$module}' не установлен.");

                return false;
            }
        }

        return true;
    }
}
