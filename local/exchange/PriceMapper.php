<?php

namespace Makaew\Store\Exchange;

use Bitrix\Catalog\GroupTable;
use Bitrix\Main\Loader;

/**
 * Маппинг типов цен между 1С и Битрикс.
 *
 * 1С может отправлять несколько типов цен (розничная, оптовая,
 * дилерская). Этот класс сопоставляет их с типами цен
 * в модуле «Торговый каталог» Битрикс.
 */
class PriceMapper
{
    /** @var Logger */
    private Logger $logger;

    /**
     * Маппинг: название типа цены в 1С → ID типа цены в Битрикс.
     * Настраивается под конкретный проект.
     */
    private const PRICE_TYPE_MAP = [
        'Розничная' => 'BASE',
        'Оптовая' => 'WHOLESALE',
        'Дилерская' => 'DEALER',
        'Акционная' => 'SALE',
    ];

    /** @var array Кэш ID типов цен */
    private array $priceTypeIds = [];

    public function __construct()
    {
        $this->logger = new Logger();
        $this->loadPriceTypes();
    }

    /**
     * Получить ID типа цены Битрикс по названию из 1С.
     *
     * @param string $oneCPriceType Название типа цены из 1С
     * @return int|null ID типа цены или null если не найден
     */
    public function getPriceTypeId(string $oneCPriceType): ?int
    {
        $bitrixCode = self::PRICE_TYPE_MAP[$oneCPriceType] ?? null;

        if ($bitrixCode === null) {
            $this->logger->warning("Unknown 1C price type: {$oneCPriceType}");

            return null;
        }

        return $this->priceTypeIds[$bitrixCode] ?? null;
    }

    /**
     * Обработать массив цен из 1С.
     *
     * @param int   $productId ID товара в каталоге
     * @param array $prices    Цены из 1С ['Розничная' => 1500, 'Оптовая' => 1200]
     * @return array Результат обработки
     */
    public function applyPrices(int $productId, array $prices): array
    {
        Loader::includeModule('catalog');

        $results = [];

        foreach ($prices as $typeName => $priceValue) {
            $priceTypeId = $this->getPriceTypeId($typeName);

            if ($priceTypeId === null) {
                $results[$typeName] = ['success' => false, 'error' => 'Unknown price type'];
                continue;
            }

            $price = (float) str_replace([' ', ','], ['', '.'], (string) $priceValue);

            if ($price <= 0) {
                $this->logger->warning("Invalid price for product {$productId}: {$typeName} = {$priceValue}");
                $results[$typeName] = ['success' => false, 'error' => 'Invalid price value'];
                continue;
            }

            // Обновляем или создаём цену
            $existingPrice = \Bitrix\Catalog\PriceTable::getList([
                'filter' => [
                    '=PRODUCT_ID' => $productId,
                    '=CATALOG_GROUP_ID' => $priceTypeId,
                ],
                'select' => ['ID'],
            ])->fetch();

            if ($existingPrice) {
                $result = \Bitrix\Catalog\PriceTable::update($existingPrice['ID'], [
                    'PRICE' => $price,
                    'CURRENCY' => 'RUB',
                ]);
            } else {
                $result = \Bitrix\Catalog\PriceTable::add([
                    'PRODUCT_ID' => $productId,
                    'CATALOG_GROUP_ID' => $priceTypeId,
                    'PRICE' => $price,
                    'CURRENCY' => 'RUB',
                ]);
            }

            $results[$typeName] = [
                'success' => $result->isSuccess(),
                'price' => $price,
                'price_type_id' => $priceTypeId,
            ];

            if ($result->isSuccess()) {
                $this->logger->info("Price set: product={$productId}, type={$typeName}, price={$price}");
            } else {
                $this->logger->error(
                    "Price set failed: product={$productId}, type={$typeName}: "
                    . implode(', ', $result->getErrorMessages())
                );
            }
        }

        return $results;
    }

    /**
     * Загрузка типов цен из БД.
     */
    private function loadPriceTypes(): void
    {
        Loader::includeModule('catalog');

        $dbPriceTypes = GroupTable::getList([
            'select' => ['ID', 'NAME', 'XML_ID'],
        ]);

        while ($type = $dbPriceTypes->fetch()) {
            $code = $type['XML_ID'] ?: $type['NAME'];
            $this->priceTypeIds[$code] = (int) $type['ID'];
        }
    }
}
