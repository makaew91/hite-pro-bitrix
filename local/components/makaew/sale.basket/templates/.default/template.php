<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
?>

<div class="basket" id="basket-component">
    <?php if ($arResult['IS_EMPTY']): ?>
        <div class="basket__empty">
            <p>Ваша корзина пуста</p>
            <a href="/catalog/" class="basket__btn-catalog">Перейти в каталог</a>
        </div>
    <?php else: ?>
        <div class="basket__items">
            <?php foreach ($arResult['ITEMS'] as $item): ?>
                <div class="basket__item" data-basket-id="<?= $item['BASKET_ID'] ?>">
                    <div class="basket__item-image">
                        <?php if ($item['IMAGE_SRC']): ?>
                            <img src="<?= htmlspecialcharsbx($item['IMAGE_SRC']) ?>" alt="<?= htmlspecialcharsbx($item['NAME']) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="basket__item-info">
                        <a href="<?= htmlspecialcharsbx($item['DETAIL_URL']) ?>" class="basket__item-name">
                            <?= htmlspecialcharsbx($item['NAME']) ?>
                        </a>
                        <?php if (!$item['CAN_BUY']): ?>
                            <span class="basket__item-unavailable">Нет в наличии</span>
                        <?php endif; ?>
                    </div>

                    <div class="basket__item-quantity">
                        <button type="button" class="basket__qty-btn" data-action="minus">−</button>
                        <input
                            type="number"
                            class="basket__qty-input"
                            value="<?= $item['QUANTITY'] ?>"
                            min="1"
                            data-basket-id="<?= $item['BASKET_ID'] ?>"
                        >
                        <button type="button" class="basket__qty-btn" data-action="plus">+</button>
                    </div>

                    <div class="basket__item-price">
                        <?= number_format($item['TOTAL_PRICE'], 0, '.', ' ') ?> ₽
                    </div>

                    <button type="button" class="basket__item-remove" data-basket-id="<?= $item['BASKET_ID'] ?>" title="Удалить">
                        ✕
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="basket__summary">
            <div class="basket__total">
                <span>Итого:</span>
                <strong id="basket-total"><?= number_format($arResult['TOTAL_PRICE'], 0, '.', ' ') ?> ₽</strong>
            </div>
            <div class="basket__count">
                Товаров: <span id="basket-count"><?= $arResult['TOTAL_COUNT'] ?></span>
            </div>
            <a href="/order/" class="basket__btn-order">Оформить заказ</a>
        </div>
    <?php endif; ?>
</div>
