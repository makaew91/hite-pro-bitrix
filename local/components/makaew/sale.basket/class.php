<?php

use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Компонент корзины.
 *
 * Отображает содержимое корзины с возможностью изменения
 * количества, удаления товаров и пересчёта итогов.
 */
class SaleBasketComponent extends CBitrixComponent
{
    public function executeComponent(): void
    {
        if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
            ShowError('Модули sale/catalog не установлены.');

            return;
        }

        $this->arResult = $this->loadBasket();
        $this->includeComponentTemplate();
    }

    /**
     * Загрузка содержимого корзины.
     */
    private function loadBasket(): array
    {
        $fuserId = Fuser::getId();
        $basket = Basket::loadItemsForFUser($fuserId, SITE_ID);

        $items = [];
        $totalPrice = 0;
        $totalCount = 0;
        $totalWeight = 0;

        /** @var \Bitrix\Sale\BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            $productId = $basketItem->getProductId();
            $quantity = $basketItem->getQuantity();
            $price = $basketItem->getPrice();
            $finalPrice = $basketItem->getFinalPrice();

            // Получаем детальную информацию о товаре
            $productInfo = $this->getProductInfo($productId);

            $items[] = [
                'BASKET_ID' => $basketItem->getId(),
                'PRODUCT_ID' => $productId,
                'NAME' => $basketItem->getField('NAME'),
                'QUANTITY' => $quantity,
                'PRICE' => $price,
                'TOTAL_PRICE' => $finalPrice,
                'CURRENCY' => $basketItem->getCurrency(),
                'WEIGHT' => (float) $basketItem->getWeight(),
                'IMAGE_SRC' => $productInfo['IMAGE_SRC'] ?? '',
                'DETAIL_URL' => $productInfo['DETAIL_URL'] ?? '',
                'CAN_BUY' => $basketItem->canBuy(),
            ];

            $totalPrice += $finalPrice;
            $totalCount += $quantity;
            $totalWeight += $basketItem->getWeight() * $quantity;
        }

        return [
            'ITEMS' => $items,
            'TOTAL_PRICE' => $totalPrice,
            'TOTAL_COUNT' => $totalCount,
            'TOTAL_WEIGHT' => $totalWeight,
            'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
            'IS_EMPTY' => empty($items),
        ];
    }

    /**
     * Получить изображение и URL товара.
     */
    private function getProductInfo(int $productId): array
    {
        $element = \CIBlockElement::GetByID($productId)->GetNext();
        if (!$element) {
            return [];
        }

        $imageSrc = '';
        if ($element['PREVIEW_PICTURE']) {
            $file = \CFile::GetFileArray($element['PREVIEW_PICTURE']);
            $imageSrc = $file ? $file['SRC'] : '';
        }

        return [
            'IMAGE_SRC' => $imageSrc,
            'DETAIL_URL' => $element['DETAIL_PAGE_URL'] ?? '',
        ];
    }
}
