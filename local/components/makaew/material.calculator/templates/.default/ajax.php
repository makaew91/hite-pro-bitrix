<?php

/**
 * AJAX-обработчик калькулятора расхода.
 *
 * Альтернативный эндпоинт для расчёта (помимо REST API).
 * Принимает POST-запросы, возвращает JSON.
 */

defined('B_PROLOG_INCLUDED') || define('B_PROLOG_INCLUDED', true);
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Makaew\Store\Calculator\ConsumptionCalculator;
use Makaew\Store\CalculationLogTable;

header('Content-Type: application/json; charset=utf-8');

// Только POST
$request = Application::getInstance()->getContext()->getRequest();
if (!$request->isPost()) {
    echo json_encode(['success' => false, 'error' => 'Only POST allowed']);
    die();
}

// Проверка CSRF
if (!check_bitrix_sessid()) {
    echo json_encode(['success' => false, 'error' => 'Invalid session']);
    die();
}

if (!Loader::includeModule('makaew.store')) {
    echo json_encode(['success' => false, 'error' => 'Module not loaded']);
    die();
}

$materialId = (int) $request->getPost('materialId');
$area = (float) $request->getPost('area');

if ($materialId <= 0 || $area <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    die();
}

$params = array_filter([
    'layers' => $request->getPost('layers'),
    'thickness' => $request->getPost('thickness'),
    'tile_width' => $request->getPost('tile_width'),
    'tile_height' => $request->getPost('tile_height'),
]);

$calculator = new ConsumptionCalculator();
$result = $calculator->calculate($materialId, $area, $params);

// Логируем успешный расчёт
if ($result->isSuccess()) {
    global $USER;
    CalculationLogTable::log(
        $materialId,
        $area,
        $result->amount,
        $USER->IsAuthorized() ? (int) $USER->GetID() : null,
        $params
    );
}

echo json_encode($result->toArray(), JSON_UNESCAPED_UNICODE);
die();
