<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент детальной страницы товара.
 *
 * Параметры:
 *   IBLOCK_ID   — ID инфоблока
 *   ELEMENT_CODE — Символьный код элемента
 *   CACHE_TIME  — Время кэширования
 */
class CatalogDetailComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['IBLOCK_ID'] = (int) ($arParams['IBLOCK_ID'] ?? IBLOCK_CATALOG_ID);
        $arParams['ELEMENT_CODE'] = trim($arParams['ELEMENT_CODE'] ?? '');
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
            $this->arResult = $this->loadElement();

            if (empty($this->arResult['ELEMENT'])) {
                $this->abortResultCache();
                $this->set404();

                return;
            }

            $this->includeComponentTemplate();
        }
    }

    /**
     * Загрузка элемента со всеми свойствами.
     */
    private function loadElement(): array
    {
        $element = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'CODE' => $this->arParams['ELEMENT_CODE'],
                'ACTIVE' => 'Y',
            ],
            false,
            ['nTopCount' => 1],
            [
                'ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT',
                'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'IBLOCK_SECTION_ID',
            ]
        )->GetNextElement();

        if (!$element) {
            return [];
        }

        $fields = $element->GetFields();
        $properties = $element->GetProperties();

        // Подготовка изображений
        $fields['PREVIEW_PICTURE_SRC'] = $this->getImageSrc($fields['PREVIEW_PICTURE']);
        $fields['DETAIL_PICTURE_SRC'] = $this->getImageSrc($fields['DETAIL_PICTURE']);

        // Хлебные крошки
        $breadcrumbs = $this->buildBreadcrumbs(
            (int) $fields['IBLOCK_SECTION_ID'],
            $fields['NAME']
        );

        // Таблица характеристик
        $specifications = $this->buildSpecifications($properties);

        return [
            'ELEMENT' => $fields,
            'PROPERTIES' => $properties,
            'SPECIFICATIONS' => $specifications,
            'BREADCRUMBS' => $breadcrumbs,
        ];
    }

    /**
     * Формирование таблицы характеристик из свойств.
     */
    private function buildSpecifications(array $properties): array
    {
        $specs = [];
        $displayCodes = [
            'ARTICLE', 'BRAND', 'UNIT', 'WEIGHT',
            'PACKAGE_VOLUME', 'CONSUMPTION_RATE', 'CONSUMPTION_UNIT', 'COLOR',
        ];

        foreach ($displayCodes as $code) {
            if (empty($properties[$code]['VALUE'])) {
                continue;
            }

            $value = $properties[$code]['VALUE'];

            // Для множественных свойств — объединяем через запятую
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // Для свойств типа «Список» — берём текстовое значение
            if (!empty($properties[$code]['VALUE_ENUM'])) {
                $value = $properties[$code]['VALUE_ENUM'];
            }

            $specs[] = [
                'NAME' => $properties[$code]['NAME'],
                'VALUE' => $value,
            ];
        }

        return $specs;
    }

    /**
     * Построение хлебных крошек.
     */
    private function buildBreadcrumbs(int $sectionId, string $elementName): array
    {
        $breadcrumbs = [
            ['NAME' => 'Главная', 'URL' => '/'],
            ['NAME' => 'Каталог', 'URL' => '/catalog/'],
        ];

        if ($sectionId > 0) {
            $navChain = \CIBlockSection::GetNavChain(
                $this->arParams['IBLOCK_ID'],
                $sectionId,
                ['ID', 'NAME', 'CODE'],
                true
            );

            foreach ($navChain as $section) {
                $breadcrumbs[] = [
                    'NAME' => $section['NAME'],
                    'URL' => '/catalog/' . $section['CODE'] . '/',
                ];
            }
        }

        $breadcrumbs[] = ['NAME' => $elementName, 'URL' => ''];

        return $breadcrumbs;
    }

    private function getImageSrc(?int $fileId): string
    {
        if (!$fileId) {
            return '';
        }

        $file = \CFile::GetFileArray($fileId);

        return $file ? $file['SRC'] : '';
    }

    private function set404(): void
    {
        \Bitrix\Main\Context::getCurrent()->getResponse()->setStatus(404);
        @define('ERROR_404', 'Y');
    }
}
