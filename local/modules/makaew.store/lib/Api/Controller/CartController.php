<?php

namespace Makaew\Store\Api\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Sale\Fuser;
use Makaew\Store\Service\OrderService;

/**
 * REST API контроллер корзины.
 *
 * Endpoints:
 *   makaew:store.api.cart.list   — содержимое корзины
 *   makaew:store.api.cart.add    — добавить товар
 *   makaew:store.api.cart.update — изменить количество
 *   makaew:store.api.cart.remove — удалить товар из корзины
 */
class CartController extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
            new ActionFilter\Csrf(false),
        ];
    }

    public function configureActions(): array
    {
        return [
            'list' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET,
                        ActionFilter\HttpMethod::METHOD_POST,
                    ]),
                ],
            ],
            'add' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'update' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'remove' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
        ];
    }

    /**
     * GET|POST /api/cart/list
     */
    public function listAction(): array
    {
        $fuserId = Fuser::getId();
        $orderService = new OrderService();

        return $orderService->getBasket($fuserId);
    }

    /**
     * POST /api/cart/add
     *
     * Параметры: productId, quantity
     */
    public function addAction(Request $request): ?array
    {
        $productId = (int) $request->getPost('productId');
        $quantity = max(1, (float) ($request->getPost('quantity') ?? 1));

        if ($productId <= 0) {
            $this->addError(new Error('Invalid productId', 'INVALID_PRODUCT'));

            return null;
        }

        $orderService = new OrderService();
        $result = $orderService->addToBasket($productId, $quantity);

        if (!$result['success']) {
            $this->addError(new Error($result['message'], 'ADD_FAILED'));

            return null;
        }

        // Возвращаем обновлённые данные корзины
        $fuserId = Fuser::getId();
        $basket = $orderService->getBasket($fuserId);

        return [
            'message' => $result['message'],
            'cartCount' => $basket['count'],
            'cartTotal' => $basket['total'],
        ];
    }

    /**
     * POST /api/cart/update
     *
     * Параметры: basketId, quantity
     */
    public function updateAction(Request $request): ?array
    {
        $basketId = (int) $request->getPost('basketId');
        $quantity = max(1, (float) ($request->getPost('quantity') ?? 1));

        if ($basketId <= 0) {
            $this->addError(new Error('Invalid basketId', 'INVALID_BASKET'));

            return null;
        }

        $fuserId = Fuser::getId();
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

        $item = $basket->getItemById($basketId);
        if (!$item) {
            $this->addError(new Error('Basket item not found', 'NOT_FOUND'));

            return null;
        }

        $item->setField('QUANTITY', $quantity);
        $saveResult = $basket->save();

        if (!$saveResult->isSuccess()) {
            $this->addError(new Error(
                implode(', ', $saveResult->getErrorMessages()),
                'UPDATE_FAILED'
            ));

            return null;
        }

        return [
            'basketId' => $basketId,
            'quantity' => $quantity,
            'itemTotal' => $item->getFinalPrice(),
            'totalPrice' => number_format($basket->getPrice(), 0, '.', ' '),
            'totalCount' => $basket->count(),
        ];
    }

    /**
     * POST /api/cart/remove
     *
     * Параметры: basketId
     */
    public function removeAction(Request $request): ?array
    {
        $basketId = (int) $request->getPost('basketId');

        if ($basketId <= 0) {
            $this->addError(new Error('Invalid basketId', 'INVALID_BASKET'));

            return null;
        }

        $fuserId = Fuser::getId();
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fuserId, SITE_ID);

        $item = $basket->getItemById($basketId);
        if (!$item) {
            $this->addError(new Error('Basket item not found', 'NOT_FOUND'));

            return null;
        }

        $item->delete();
        $saveResult = $basket->save();

        if (!$saveResult->isSuccess()) {
            $this->addError(new Error(
                implode(', ', $saveResult->getErrorMessages()),
                'REMOVE_FAILED'
            ));

            return null;
        }

        return [
            'removed' => $basketId,
            'totalPrice' => number_format($basket->getPrice(), 0, '.', ' '),
            'totalCount' => $basket->count(),
        ];
    }
}
