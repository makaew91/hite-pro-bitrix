<?php

use Bitrix\Main\Loader;
use Makaew\Store\Calculator\ConsumptionCalculator;
use Makaew\Store\MaterialTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент калькулятора расхода материалов.
 *
 * Позволяет рассчитать необходимое количество материала
 * на основе площади и типа поверхности.
 *
 * Параметры:
 *   MATERIAL_CODE — предвыбранный материал (из GET-параметра)
 *   CACHE_TIME    — время кэша для списка материалов
 */
class MaterialCalculatorComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['MATERIAL_CODE'] = trim($arParams['MATERIAL_CODE'] ?? $_GET['material'] ?? '');
        $arParams['CACHE_TIME'] = (int) ($arParams['CACHE_TIME'] ?? 3600);

        return $arParams;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('makaew.store')) {
            ShowError('Модуль makaew.store не установлен.');

            return;
        }

        $this->arResult = $this->prepareData();
        $this->includeComponentTemplate();
    }

    /**
     * Подготовка данных для формы калькулятора.
     */
    private function prepareData(): array
    {
        // Список материалов для выпадающего списка
        $materials = $this->getMaterials();

        // Предвыбранный материал
        $selectedMaterial = null;
        if ($this->arParams['MATERIAL_CODE']) {
            $selectedMaterial = MaterialTable::getByCode($this->arParams['MATERIAL_CODE']);
        }

        // Типы поверхности
        $surfaceTypes = [
            'smooth' => ['name' => 'Гладкая', 'factor' => 1.0],
            'rough' => ['name' => 'Шероховатая', 'factor' => 1.15],
            'porous' => ['name' => 'Пористая', 'factor' => 1.25],
            'textured' => ['name' => 'Рельефная', 'factor' => 1.35],
        ];

        return [
            'MATERIALS' => $materials,
            'SELECTED_MATERIAL' => $selectedMaterial,
            'SURFACE_TYPES' => $surfaceTypes,
        ];
    }

    /**
     * Получить список материалов с нормой расхода.
     */
    private function getMaterials(): array
    {
        $result = MaterialTable::getList([
            'filter' => [
                '=active' => 1,
                '!consumption_rate' => null,
            ],
            'select' => ['id', 'name', 'code', 'consumption_rate', 'consumption_unit', 'unit'],
            'order' => ['name' => 'ASC'],
        ]);

        $materials = [];
        while ($row = $result->fetch()) {
            $materials[] = [
                'ID' => $row['id'],
                'NAME' => $row['name'],
                'CODE' => $row['code'],
                'RATE' => $row['consumption_rate'],
                'RATE_UNIT' => $row['consumption_unit'],
                'UNIT' => $row['unit'],
            ];
        }

        return $materials;
    }
}
