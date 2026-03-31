<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
?>

<div class="order-form">
    <?php if ($arResult['IS_EMPTY']): ?>
        <div class="order-form__empty">
            <p>Корзина пуста. Добавьте товары для оформления заказа.</p>
            <a href="/catalog/" class="order-form__btn-catalog">В каталог</a>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <?php if (!empty($arResult['ERROR'])): ?>
        <div class="order-form__error"><?= htmlspecialcharsbx($arResult['ERROR']) ?></div>
    <?php endif; ?>

    <form method="post" action="" class="order-form__form">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="ORDER_CONFIRM" value="Y">

        <!-- Шаг 1: Данные покупателя -->
        <fieldset class="order-form__step">
            <legend class="order-form__step-title">1. Данные покупателя</legend>

            <?php foreach ($arResult['ORDER_PROPERTIES'] as $prop): ?>
                <div class="order-form__field">
                    <label class="order-form__label" for="prop_<?= $prop['CODE'] ?>">
                        <?= htmlspecialcharsbx($prop['NAME']) ?>
                        <?php if ($prop['REQUIRED'] === 'Y'): ?><span class="order-form__required">*</span><?php endif; ?>
                    </label>

                    <?php if ($prop['TYPE'] === 'TEXTAREA'): ?>
                        <textarea
                            id="prop_<?= $prop['CODE'] ?>"
                            name="ORDER_PROP[<?= $prop['CODE'] ?>]"
                            class="order-form__textarea"
                            <?= $prop['REQUIRED'] === 'Y' ? 'required' : '' ?>
                        ><?= htmlspecialcharsbx($_POST['ORDER_PROP'][$prop['CODE']] ?? $prop['DEFAULT_VALUE']) ?></textarea>
                    <?php else: ?>
                        <input
                            type="text"
                            id="prop_<?= $prop['CODE'] ?>"
                            name="ORDER_PROP[<?= $prop['CODE'] ?>]"
                            class="order-form__input"
                            value="<?= htmlspecialcharsbx($_POST['ORDER_PROP'][$prop['CODE']] ?? $prop['DEFAULT_VALUE']) ?>"
                            <?= $prop['REQUIRED'] === 'Y' ? 'required' : '' ?>
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </fieldset>

        <!-- Шаг 2: Доставка -->
        <?php if (!empty($arResult['DELIVERY_SERVICES'])): ?>
            <fieldset class="order-form__step">
                <legend class="order-form__step-title">2. Способ доставки</legend>
                <?php foreach ($arResult['DELIVERY_SERVICES'] as $delivery): ?>
                    <label class="order-form__radio">
                        <input
                            type="radio"
                            name="DELIVERY_ID"
                            value="<?= $delivery['ID'] ?>"
                            <?= ($delivery === reset($arResult['DELIVERY_SERVICES'])) ? 'checked' : '' ?>
                        >
                        <span class="order-form__radio-label">
                            <?= htmlspecialcharsbx($delivery['NAME']) ?>
                            <?php if ($delivery['PRICE'] > 0): ?>
                                — <?= number_format($delivery['PRICE'], 0, '.', ' ') ?> ₽
                            <?php else: ?>
                                — бесплатно
                            <?php endif; ?>
                        </span>
                        <?php if ($delivery['DESCRIPTION']): ?>
                            <span class="order-form__radio-desc"><?= htmlspecialcharsbx($delivery['DESCRIPTION']) ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>

        <!-- Шаг 3: Оплата -->
        <?php if (!empty($arResult['PAYMENT_SYSTEMS'])): ?>
            <fieldset class="order-form__step">
                <legend class="order-form__step-title">3. Способ оплаты</legend>
                <?php foreach ($arResult['PAYMENT_SYSTEMS'] as $ps): ?>
                    <label class="order-form__radio">
                        <input
                            type="radio"
                            name="PAY_SYSTEM_ID"
                            value="<?= $ps['ID'] ?>"
                            <?= ($ps === reset($arResult['PAYMENT_SYSTEMS'])) ? 'checked' : '' ?>
                        >
                        <span class="order-form__radio-label"><?= htmlspecialcharsbx($ps['NAME']) ?></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>

        <!-- Итого -->
        <div class="order-form__summary">
            <h3>Ваш заказ</h3>
            <table class="order-form__summary-table">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Кол-во</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($arResult['BASKET_ITEMS'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialcharsbx($item['NAME']) ?></td>
                            <td><?= $item['QUANTITY'] ?></td>
                            <td><?= number_format($item['TOTAL'], 0, '.', ' ') ?> ₽</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2"><strong>Итого:</strong></td>
                        <td><strong><?= number_format($arResult['BASKET_TOTAL'], 0, '.', ' ') ?> ₽</strong></td>
                    </tr>
                </tfoot>
            </table>

            <button type="submit" class="order-form__btn-submit">Подтвердить заказ</button>
        </div>
    </form>
</div>
