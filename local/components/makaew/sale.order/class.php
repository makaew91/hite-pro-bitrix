<?php

use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\PaySystem;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент оформления заказа.
 *
 * Пошаговая форма: данные покупателя → доставка → оплата → подтверждение.
 * Работает с Sale API для создания заказа.
 */
class SaleOrderComponent extends CBitrixComponent
{
    /** @var array Обязательные модули */
    private const REQUIRED_MODULES = ['sale', 'catalog', 'iblock'];

    public function executeComponent(): void
    {
        if (!$this->checkModules()) {
            return;
        }

        if (!$this->checkAuth()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ORDER_CONFIRM'])) {
            $this->processOrder();

            return;
        }

        $this->arResult = $this->prepareFormData();
        $this->includeComponentTemplate();
    }

    /**
     * Подготовка данных для формы оформления.
     */
    private function prepareFormData(): array
    {
        global $USER;

        $userId = (int) $USER->GetID();

        // Корзина
        $fuserId = \Bitrix\Sale\Fuser::getIdByUserId($userId);
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

        $basketItems = [];
        /** @var \Bitrix\Sale\BasketItem $item */
        foreach ($basket as $item) {
            $basketItems[] = [
                'NAME' => $item->getField('NAME'),
                'QUANTITY' => $item->getQuantity(),
                'PRICE' => $item->getPrice(),
                'TOTAL' => $item->getFinalPrice(),
            ];
        }

        // Службы доставки
        $deliveryServices = [];
        $dbDelivery = Delivery\Services\Manager::getActiveList();
        foreach ($dbDelivery as $service) {
            if ($service['CLASS_NAME'] === '\\Bitrix\\Sale\\Delivery\\Services\\Group') {
                continue;
            }
            $deliveryServices[] = [
                'ID' => $service['ID'],
                'NAME' => $service['NAME'],
                'DESCRIPTION' => $service['DESCRIPTION'] ?? '',
                'PRICE' => $service['CONFIG']['MAIN']['PRICE'] ?? 0,
            ];
        }

        // Платёжные системы
        $paymentSystems = [];
        $dbPaySystems = PaySystem\Manager::getList([
            'filter' => ['ACTIVE' => 'Y'],
            'order' => ['SORT' => 'ASC'],
        ]);
        while ($ps = $dbPaySystems->fetch()) {
            $paymentSystems[] = [
                'ID' => $ps['ID'],
                'NAME' => $ps['NAME'],
                'DESCRIPTION' => $ps['DESCRIPTION'] ?? '',
            ];
        }

        // Свойства заказа (поля формы)
        $orderProperties = $this->getOrderProperties();

        // Данные пользователя для предзаполнения
        $userData = \CUser::GetByID($userId)->Fetch();

        return [
            'BASKET_ITEMS' => $basketItems,
            'BASKET_TOTAL' => $basket->getPrice(),
            'BASKET_COUNT' => $basket->count(),
            'DELIVERY_SERVICES' => $deliveryServices,
            'PAYMENT_SYSTEMS' => $paymentSystems,
            'ORDER_PROPERTIES' => $orderProperties,
            'USER_DATA' => [
                'NAME' => $userData['NAME'] ?? '',
                'LAST_NAME' => $userData['LAST_NAME'] ?? '',
                'EMAIL' => $userData['EMAIL'] ?? '',
                'PHONE' => $userData['PERSONAL_PHONE'] ?? '',
            ],
            'IS_EMPTY' => $basket->count() === 0,
        ];
    }

    /**
     * Получить свойства заказа для формы.
     */
    private function getOrderProperties(): array
    {
        $properties = [];
        $dbProps = \Bitrix\Sale\Property::getList([
            'filter' => ['ACTIVE' => 'Y', 'PERSON_TYPE_ID' => 1],
            'order' => ['SORT' => 'ASC'],
        ]);

        while ($prop = $dbProps->fetch()) {
            $properties[] = [
                'ID' => $prop['ID'],
                'CODE' => $prop['CODE'],
                'NAME' => $prop['NAME'],
                'TYPE' => $prop['TYPE'],
                'REQUIRED' => $prop['REQUIRED'],
                'DEFAULT_VALUE' => $prop['DEFAULT_VALUE'] ?? '',
            ];
        }

        return $properties;
    }

    /**
     * Обработка отправки формы — создание заказа.
     */
    private function processOrder(): void
    {
        global $USER;

        $orderService = new \Makaew\Store\Service\OrderService();

        $result = $orderService->createOrder(
            (int) $USER->GetID(),
            [
                'person_type_id' => 1,
                'properties' => $_POST['ORDER_PROP'] ?? [],
                'delivery_id' => (int) ($_POST['DELIVERY_ID'] ?? 0),
                'pay_system_id' => (int) ($_POST['PAY_SYSTEM_ID'] ?? 0),
            ]
        );

        if ($result['success']) {
            LocalRedirect('/order/confirm/?ORDER_ID=' . $result['order_id']);
        }

        $this->arResult = $this->prepareFormData();
        $this->arResult['ERROR'] = $result['message'];
        $this->includeComponentTemplate();
    }

    private function checkModules(): bool
    {
        foreach (self::REQUIRED_MODULES as $module) {
            if (!Loader::includeModule($module)) {
                ShowError("Модуль '{$module}' не установлен.");

                return false;
            }
        }

        return true;
    }

    private function checkAuth(): bool
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            LocalRedirect('/auth/?backurl=' . urlencode('/order/'));

            return false;
        }

        return true;
    }
}
