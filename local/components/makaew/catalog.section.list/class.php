<?php

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент списка разделов каталога.
 *
 * Отображает категории товаров с изображениями и счётчиками.
 *
 * Параметры:
 *   IBLOCK_ID   — ID инфоблока
 *   PARENT_ID   — ID родительского раздела (0 = корневые)
 *   DEPTH_LEVEL — Глубина вложенности для отображения
 *   CACHE_TIME  — Время кэширования
 */
class CatalogSectionListComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['IBLOCK_ID'] = (int) ($arParams['IBLOCK_ID'] ?? IBLOCK_CATALOG_ID);
        $arParams['PARENT_ID'] = (int) ($arParams['PARENT_ID'] ?? 0);
        $arParams['DEPTH_LEVEL'] = (int) ($arParams['DEPTH_LEVEL'] ?? 1);
        $arParams['CACHE_TIME'] = (int) ($arParams['CACHE_TIME'] ?? 3600);

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('iblock')) {
            ShowError('Модуль iblock не установлен.');

            return;
        }

        if ($this->startResultCache($this->arParams['CACHE_TIME'])) {
            $this->arResult['SECTIONS'] = $this->loadSections();

            if (empty($this->arResult['SECTIONS'])) {
                $this->abortResultCache();
            }

            $this->includeComponentTemplate();
        }
    }

    /**
     * Загрузка разделов с количеством элементов.
     */
    private function loadSections(): array
    {
        $filter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
        ];

        if ($this->arParams['PARENT_ID'] > 0) {
            $filter['SECTION_ID'] = $this->arParams['PARENT_ID'];
        } else {
            $filter['DEPTH_LEVEL'] = $this->arParams['DEPTH_LEVEL'];
        }

        $sections = [];

        $dbSections = \CIBlockSection::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            $filter,
            true, // Считаем элементы
            ['ID', 'NAME', 'CODE', 'PICTURE', 'DESCRIPTION', 'SORT', 'ELEMENT_CNT'],
        );

        while ($section = $dbSections->GetNext()) {
            $section['SECTION_PAGE_URL'] = '/catalog/' . $section['CODE'] . '/';
            $section['PICTURE_SRC'] = $this->getImageSrc($section['PICTURE']);
            $sections[] = $section;
        }

        return $sections;
    }

    private function getImageSrc(?int $fileId): string
    {
        if (!$fileId) {
            return '';
        }

        $file = \CFile::GetFileArray($fileId);

        return $file ? $file['SRC'] : '';
    }
}
