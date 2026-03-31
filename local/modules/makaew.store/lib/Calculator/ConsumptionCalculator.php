<?php

namespace Makaew\Store\Calculator;

use Makaew\Store\MaterialTable;

/**
 * Калькулятор расхода строительных материалов.
 *
 * Рассчитывает необходимое количество материала
 * на основе площади и параметров поверхности.
 */
class ConsumptionCalculator
{
    /** Коэффициент запаса (10%) */
    private const RESERVE_FACTOR = 1.10;

    /** Формулы расчёта по типу материала */
    private const FORMULAS = [
        // Краска: расход (л/м2) × площадь × кол-во слоёв
        'paint' => 'rate * area * layers',
        // Штукатурка: расход (кг/м2) × площадь × толщина (мм) / 1мм
        'plaster' => 'rate * area * thickness',
        // Плитка: площадь / площадь_одной_плитки + запас на подрезку
        'tile' => '(area / tile_area) * cut_reserve',
        // Наливной пол: расход (кг/м2) × площадь × толщина (мм)
        'floor' => 'rate * area * thickness',
        // По умолчанию: расход × площадь
        'default' => 'rate * area',
    ];

    /**
     * Рассчитать расход материала.
     *
     * @param int   $materialId ID материала
     * @param float $area       Площадь (м2)
     * @param array $params     Доп. параметры (layers, thickness, tile_width, tile_height)
     * @return CalculationResult
     */
    public function calculate(int $materialId, float $area, array $params = []): CalculationResult
    {
        $material = MaterialTable::getByCode((string) $materialId)
            ?? MaterialTable::getList([
                'filter' => ['=id' => $materialId],
                'limit' => 1,
            ])->fetch();

        if (!$material) {
            return CalculationResult::error('Материал не найден');
        }

        $rate = (float) $material['consumption_rate'];
        if ($rate <= 0) {
            return CalculationResult::error('Для данного материала не задана норма расхода');
        }

        $type = $this->detectMaterialType($material);
        $amount = $this->applyFormula($type, $rate, $area, $params);
        $amountWithReserve = ceil($amount * self::RESERVE_FACTOR * 100) / 100;

        return new CalculationResult(
            materialId: $materialId,
            materialName: $material['name'],
            area: $area,
            amount: round($amount, 2),
            amountWithReserve: $amountWithReserve,
            unit: $material['unit'],
            formula: self::FORMULAS[$type] ?? self::FORMULAS['default'],
            params: $params,
        );
    }

    /**
     * Определить тип материала по единице расхода.
     */
    private function detectMaterialType(array $material): string
    {
        $unit = mb_strtolower($material['consumption_unit'] ?? '');

        return match (true) {
            str_contains($unit, 'л/м') => 'paint',
            str_contains($unit, 'кг/м') && str_contains(mb_strtolower($material['name']), 'штукатур') => 'plaster',
            str_contains($unit, 'кг/м') && str_contains(mb_strtolower($material['name']), 'наливн') => 'floor',
            str_contains(mb_strtolower($material['name']), 'плитк') => 'tile',
            default => 'default',
        };
    }

    /**
     * Применить формулу расчёта.
     */
    private function applyFormula(string $type, float $rate, float $area, array $params): float
    {
        return match ($type) {
            'paint' => $rate * $area * max(1, (int) ($params['layers'] ?? 2)),

            'plaster' => $rate * $area * max(1, (float) ($params['thickness'] ?? 10)),

            'tile' => $this->calculateTiles($area, $params),

            'floor' => $rate * $area * max(1, (float) ($params['thickness'] ?? 3)),

            default => $rate * $area,
        };
    }

    /**
     * Расчёт количества плитки.
     */
    private function calculateTiles(float $area, array $params): float
    {
        $tileWidth = (float) ($params['tile_width'] ?? 0.3);   // метры
        $tileHeight = (float) ($params['tile_height'] ?? 0.3);  // метры
        $tileArea = $tileWidth * $tileHeight;

        if ($tileArea <= 0) {
            return 0;
        }

        $cutReserve = (float) ($params['cut_reserve'] ?? 1.10); // 10% на подрезку

        return ($area / $tileArea) * $cutReserve;
    }
}
