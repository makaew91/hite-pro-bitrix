<?php

namespace Makaew\Store\Service;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Shipment;

/**
 * Кастомный обработчик доставки «Доставка по городу».
 *
 * Рассчитывает стоимость доставки на основе веса заказа
 * и расстояния (упрощённая модель по зонам).
 *
 * Регистрируется через событие onSaleDeliveryServicesBuildList.
 */
class DeliveryHandler extends Base
{
    /** Базовая стоимость доставки */
    private const BASE_PRICE = 350;

    /** Стоимость за кг сверх бесплатного лимита */
    private const PRICE_PER_KG = 25;

    /** Бесплатный лимит веса (кг) */
    private const FREE_WEIGHT_LIMIT = 50;

    /** Порог бесплатной доставки по сумме заказа */
    private const FREE_DELIVERY_THRESHOLD = 15000;

    public static function getClassTitle(): string
    {
        return 'Доставка по городу (СтройМаркет)';
    }

    public static function getClassDescription(): string
    {
        return 'Доставка строительных материалов по городу. Бесплатно при заказе от '
            . number_format(self::FREE_DELIVERY_THRESHOLD, 0, '', ' ') . ' ₽.';
    }

    public function isCalculatePriceImmediately(): bool
    {
        return true;
    }

    /**
     * Расчёт стоимости доставки.
     *
     * Логика:
     * 1. Заказ >= 15000 руб → бесплатно
     * 2. Базовая ставка 350 руб
     * 3. +25 руб за каждый кг сверх 50 кг
     */
    protected function calculateConcrete(Shipment $shipment): \Bitrix\Sale\Delivery\CalculationResult
    {
        $result = new \Bitrix\Sale\Delivery\CalculationResult();

        $order = $shipment->getCollection()->getOrder();
        $orderPrice = $order->getPrice();

        // Бесплатная доставка при большом заказе
        if ($orderPrice >= self::FREE_DELIVERY_THRESHOLD) {
            $result->setDeliveryPrice(0);
            $result->setDescription('Бесплатная доставка при заказе от '
                . number_format(self::FREE_DELIVERY_THRESHOLD, 0, '', ' ') . ' ₽');

            return $result;
        }

        // Базовая стоимость
        $deliveryPrice = self::BASE_PRICE;

        // Наценка за вес
        $totalWeight = $shipment->getWeight() / 1000; // граммы → кг
        if ($totalWeight > self::FREE_WEIGHT_LIMIT) {
            $extraKg = $totalWeight - self::FREE_WEIGHT_LIMIT;
            $deliveryPrice += ceil($extraKg) * self::PRICE_PER_KG;
        }

        $result->setDeliveryPrice($deliveryPrice);

        $untilFree = self::FREE_DELIVERY_THRESHOLD - $orderPrice;
        $result->setDescription(
            sprintf('Ещё %s ₽ до бесплатной доставки', number_format($untilFree, 0, '', ' '))
        );

        return $result;
    }
}
