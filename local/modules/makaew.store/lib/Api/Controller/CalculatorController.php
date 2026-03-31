<?php

namespace Makaew\Store\Api\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Makaew\Store\Calculator\ConsumptionCalculator;
use Makaew\Store\CalculationLogTable;

/**
 * REST API контроллер калькулятора.
 *
 * Endpoint:
 *   makaew:store.api.calculator.calculate — расчёт расхода материала
 */
class CalculatorController extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
            new ActionFilter\Csrf(false),
        ];
    }

    /**
     * POST /api/calculator/calculate
     *
     * Параметры: materialId, area, layers?, thickness?, tile_width?, tile_height?
     */
    public function calculateAction(Request $request): ?array
    {
        $materialId = (int) $request->getPost('materialId');
        $area = (float) $request->getPost('area');

        if ($materialId <= 0) {
            $this->addError(new Error('Invalid materialId', 'INVALID_MATERIAL'));

            return null;
        }

        if ($area <= 0) {
            $this->addError(new Error('Area must be positive', 'INVALID_AREA'));

            return null;
        }

        $params = array_filter([
            'layers' => $request->getPost('layers'),
            'thickness' => $request->getPost('thickness'),
            'tile_width' => $request->getPost('tile_width'),
            'tile_height' => $request->getPost('tile_height'),
        ]);

        $calculator = new ConsumptionCalculator();
        $result = $calculator->calculate($materialId, $area, $params);

        // Логируем расчёт
        if ($result->isSuccess()) {
            global $USER;
            CalculationLogTable::log(
                $materialId,
                $area,
                $result->amount,
                ($USER && $USER->IsAuthorized()) ? (int) $USER->GetID() : null,
                $params
            );
        }

        return $result->toArray();
    }
}
