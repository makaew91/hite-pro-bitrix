<?php

namespace Makaew\Store\Agent;

use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Loader;

/**
 * Агент мониторинга остатков на складе.
 *
 * Проверяет товары с низким остатком и отправляет
 * уведомление менеджеру закупок.
 *
 * Регистрация:
 * CAgent::AddAgent(
 *     '\Makaew\Store\Agent\StockMonitorAgent::run();',
 *     'makaew.store',
 *     'N',
 *     3600,      // каждый час
 *     '',
 *     'Y',
 *     ''
 * );
 */
class StockMonitorAgent
{
    /** Порог минимального остатка */
    private const LOW_STOCK_THRESHOLD = 10;

    /** Порог критического остатка (0 = закончился) */
    private const CRITICAL_STOCK_THRESHOLD = 0;

    /**
     * Точка входа агента.
     */
    public static function run(): string
    {
        if (!Loader::includeModule('catalog') || !Loader::includeModule('iblock')) {
            return static::class . '::run();';
        }

        $lowStockProducts = self::getLowStockProducts();
        $outOfStockProducts = self::getOutOfStockProducts();

        if (!empty($lowStockProducts) || !empty($outOfStockProducts)) {
            self::sendNotification($lowStockProducts, $outOfStockProducts);
        }

        return static::class . '::run();';
    }

    /**
     * Товары с низким остатком.
     */
    private static function getLowStockProducts(): array
    {
        $products = [];

        $dbProducts = ProductTable::getList([
            'filter' => [
                '>QUANTITY' => self::CRITICAL_STOCK_THRESHOLD,
                '<=QUANTITY' => self::LOW_STOCK_THRESHOLD,
                'ELEMENT.ACTIVE' => 'Y',
                'ELEMENT.IBLOCK_ID' => IBLOCK_CATALOG_ID,
            ],
            'select' => [
                'ID', 'QUANTITY',
                'ELEMENT_NAME' => 'ELEMENT.NAME',
                'ELEMENT_ID' => 'ELEMENT.ID',
            ],
            'limit' => 100,
        ]);

        while ($product = $dbProducts->fetch()) {
            $products[] = [
                'ID' => $product['ELEMENT_ID'],
                'NAME' => $product['ELEMENT_NAME'],
                'QUANTITY' => $product['QUANTITY'],
            ];
        }

        return $products;
    }

    /**
     * Товары с нулевым остатком.
     */
    private static function getOutOfStockProducts(): array
    {
        $products = [];

        $dbProducts = ProductTable::getList([
            'filter' => [
                '<=QUANTITY' => self::CRITICAL_STOCK_THRESHOLD,
                'ELEMENT.ACTIVE' => 'Y',
                'ELEMENT.IBLOCK_ID' => IBLOCK_CATALOG_ID,
            ],
            'select' => [
                'ID', 'QUANTITY',
                'ELEMENT_NAME' => 'ELEMENT.NAME',
                'ELEMENT_ID' => 'ELEMENT.ID',
            ],
            'limit' => 100,
        ]);

        while ($product = $dbProducts->fetch()) {
            $products[] = [
                'ID' => $product['ELEMENT_ID'],
                'NAME' => $product['ELEMENT_NAME'],
            ];
        }

        return $products;
    }

    /**
     * Отправка уведомления о низких остатках.
     */
    private static function sendNotification(array $lowStock, array $outOfStock): void
    {
        $lowStockList = '';
        foreach ($lowStock as $item) {
            $lowStockList .= sprintf(
                "- %s (ID: %d) — осталось: %d шт.\n",
                $item['NAME'],
                $item['ID'],
                $item['QUANTITY']
            );
        }

        $outOfStockList = '';
        foreach ($outOfStock as $item) {
            $outOfStockList .= sprintf(
                "- %s (ID: %d) — НЕТ В НАЛИЧИИ\n",
                $item['NAME'],
                $item['ID']
            );
        }

        \CEvent::Send('MAKAEW_LOW_STOCK_ALERT', SITE_ID, [
            'LOW_STOCK_COUNT' => count($lowStock),
            'OUT_OF_STOCK_COUNT' => count($outOfStock),
            'LOW_STOCK_LIST' => $lowStockList ?: 'Нет',
            'OUT_OF_STOCK_LIST' => $outOfStockList ?: 'Нет',
            'ADMIN_URL' => '/bitrix/admin/cat_product_list.php',
        ]);
    }
}
