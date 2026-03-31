<?php

namespace Makaew\Store\Service;

use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;

/**
 * Сервис для работы с заказами.
 *
 * Инкапсулирует логику создания заказов, работу с корзиной
 * и валидацию данных покупателя.
 */
class OrderService
{
    public function __construct()
    {
        Loader::includeModule('sale');
        Loader::includeModule('catalog');
    }

    /**
     * Получить текущую корзину пользователя.
     *
     * @return array{items: array, total: float, count: int}
     */
    public function getBasket(int $fuserId): array
    {
        $basket = Basket::loadItemsForFUser($fuserId, SITE_ID);
        $items = [];

        /** @var \Bitrix\Sale\BasketItem $item */
        foreach ($basket as $item) {
            $items[] = [
                'id' => $item->getId(),
                'product_id' => $item->getProductId(),
                'name' => $item->getField('NAME'),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'total' => $item->getFinalPrice(),
                'currency' => $item->getCurrency(),
            ];
        }

        return [
            'items' => $items,
            'total' => $basket->getPrice(),
            'count' => $basket->count(),
        ];
    }

    /**
     * Добавить товар в корзину.
     *
     * @param int   $productId ID товара (элемента каталога)
     * @param float $quantity  Количество
     * @return array{success: bool, message: string}
     */
    public function addToBasket(int $productId, float $quantity = 1): array
    {
        $fuserId = Fuser::getId();
        $basket = Basket::loadItemsForFUser($fuserId, SITE_ID);

        $item = $basket->getExistsItem('catalog', $productId);

        if ($item) {
            $item->setField('QUANTITY', $item->getQuantity() + $quantity);
        } else {
            $item = $basket->createItem('catalog', $productId);
            $item->setFields([
                'QUANTITY' => $quantity,
                'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => SITE_ID,
                'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\CatalogProvider::class,
            ]);
        }

        $result = $basket->save();

        if ($result->isSuccess()) {
            return ['success' => true, 'message' => 'Товар добавлен в корзину'];
        }

        return ['success' => false, 'message' => implode(', ', $result->getErrorMessages())];
    }

    /**
     * Создать заказ из текущей корзины.
     *
     * @param int   $userId    ID пользователя
     * @param array $orderData Данные заказа (person_type_id, properties, delivery_id, pay_system_id)
     * @return array{success: bool, order_id: ?int, message: string}
     */
    public function createOrder(int $userId, array $orderData): array
    {
        $order = Order::create(SITE_ID, $userId);
        $order->setPersonTypeId($orderData['person_type_id'] ?? 1);

        // Привязываем корзину
        $fuserId = Fuser::getIdByUserId($userId);
        $basket = Basket::loadItemsForFUser($fuserId, SITE_ID);

        if ($basket->count() === 0) {
            return ['success' => false, 'order_id' => null, 'message' => 'Корзина пуста'];
        }

        $order->setBasket($basket);

        // Устанавливаем свойства заказа
        $propertyCollection = $order->getPropertyCollection();
        foreach ($orderData['properties'] ?? [] as $code => $value) {
            $property = $this->findPropertyByCode($propertyCollection, $code);
            if ($property) {
                $property->setValue($value);
            }
        }

        // Доставка
        if (!empty($orderData['delivery_id'])) {
            $shipmentCollection = $order->getShipmentCollection();
            $shipment = $shipmentCollection->createItem();
            $shipment->setField('DELIVERY_ID', $orderData['delivery_id']);
        }

        // Оплата
        if (!empty($orderData['pay_system_id'])) {
            $paymentCollection = $order->getPaymentCollection();
            $payment = $paymentCollection->createItem();
            $payment->setField('PAY_SYSTEM_ID', $orderData['pay_system_id']);
            $payment->setField('SUM', $order->getPrice());
        }

        $result = $order->save();

        if ($result->isSuccess()) {
            return [
                'success' => true,
                'order_id' => $order->getId(),
                'message' => 'Заказ успешно создан',
            ];
        }

        return [
            'success' => false,
            'order_id' => null,
            'message' => implode(', ', $result->getErrorMessages()),
        ];
    }

    /**
     * Найти свойство заказа по символьному коду.
     */
    private function findPropertyByCode(
        \Bitrix\Sale\PropertyValueCollection $collection,
        string $code,
    ): ?\Bitrix\Sale\PropertyValue {
        /** @var \Bitrix\Sale\PropertyValue $property */
        foreach ($collection as $property) {
            if ($property->getField('CODE') === $code) {
                return $property;
            }
        }

        return null;
    }
}
