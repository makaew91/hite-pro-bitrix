<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
$items = $arResult['ITEMS'];
?>

<form class="smart-filter" id="smart-filter" method="get" action="">
    <!-- Цена -->
    <?php if (!empty($items['PRICE']) && $items['PRICE']['MAX'] > 0): ?>
        <div class="smart-filter__section">
            <h4 class="smart-filter__title">Цена, ₽</h4>
            <div class="smart-filter__range">
                <input
                    type="number"
                    name="PRICE_MIN"
                    class="smart-filter__input"
                    placeholder="от <?= number_format($items['PRICE']['MIN'], 0, '', ' ') ?>"
                    value="<?= $items['PRICE']['CURRENT_MIN'] ?: '' ?>"
                    min="<?= $items['PRICE']['MIN'] ?>"
                    max="<?= $items['PRICE']['MAX'] ?>"
                >
                <span class="smart-filter__dash">—</span>
                <input
                    type="number"
                    name="PRICE_MAX"
                    class="smart-filter__input"
                    placeholder="до <?= number_format($items['PRICE']['MAX'], 0, '', ' ') ?>"
                    value="<?= $items['PRICE']['CURRENT_MAX'] ?: '' ?>"
                    min="<?= $items['PRICE']['MIN'] ?>"
                    max="<?= $items['PRICE']['MAX'] ?>"
                >
            </div>
        </div>
    <?php endif; ?>

    <!-- Свойства-чекбоксы -->
    <?php foreach ($items as $code => $item): ?>
        <?php if ($code === 'PRICE' || empty($item['VALUES']) || $item['TYPE'] !== 'checkbox') continue; ?>
        <div class="smart-filter__section">
            <h4 class="smart-filter__title"><?= htmlspecialcharsbx($item['NAME']) ?></h4>
            <div class="smart-filter__values">
                <?php foreach ($item['VALUES'] as $value): ?>
                    <label class="smart-filter__checkbox">
                        <input
                            type="checkbox"
                            name="PROP_<?= $code ?>[]"
                            value="<?= htmlspecialcharsbx($value['ID']) ?>"
                            <?= $value['CHECKED'] ? 'checked' : '' ?>
                        >
                        <span><?= htmlspecialcharsbx($value['VALUE']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Числовые диапазоны -->
    <?php foreach ($items as $code => $item): ?>
        <?php if ($code === 'PRICE' || empty($item) || ($item['TYPE'] ?? '') !== 'range') continue; ?>
        <?php if (($item['MAX'] ?? 0) <= 0) continue; ?>
        <div class="smart-filter__section">
            <h4 class="smart-filter__title"><?= htmlspecialcharsbx($item['NAME']) ?></h4>
            <div class="smart-filter__range">
                <input
                    type="number"
                    name="<?= $code ?>_MIN"
                    class="smart-filter__input"
                    placeholder="от <?= $item['MIN'] ?>"
                    value="<?= $item['CURRENT_MIN'] ?: '' ?>"
                    step="0.01"
                >
                <span class="smart-filter__dash">—</span>
                <input
                    type="number"
                    name="<?= $code ?>_MAX"
                    class="smart-filter__input"
                    placeholder="до <?= $item['MAX'] ?>"
                    value="<?= $item['CURRENT_MAX'] ?: '' ?>"
                    step="0.01"
                >
            </div>
        </div>
    <?php endforeach; ?>

    <div class="smart-filter__actions">
        <button type="submit" class="smart-filter__btn-apply" id="smart-filter-apply">Применить</button>
        <a href="?" class="smart-filter__btn-reset">Сбросить</a>
    </div>
</form>
