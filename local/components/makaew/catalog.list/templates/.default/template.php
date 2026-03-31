<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var CatalogListComponent $component */
?>

<div class="catalog-list">
    <?php if (empty($arResult['ITEMS'])): ?>
        <div class="catalog-list__empty">
            <p>Товары не найдены</p>
        </div>
    <?php else: ?>
        <div class="catalog-list__grid">
            <?php foreach ($arResult['ITEMS'] as $item): ?>
                <div class="catalog-list__item" data-id="<?= $item['ID'] ?>">
                    <a href="<?= htmlspecialcharsbx($item['DETAIL_PAGE_URL']) ?>" class="catalog-list__link">
                        <?php if (!empty($item['PREVIEW_PICTURE_SRC'])): ?>
                            <div class="catalog-list__image">
                                <img
                                    src="<?= htmlspecialcharsbx($item['PREVIEW_PICTURE_SRC']) ?>"
                                    alt="<?= htmlspecialcharsbx($item['NAME']) ?>"
                                    loading="lazy"
                                >
                            </div>
                        <?php endif; ?>

                        <div class="catalog-list__badges">
                            <?php if (!empty($item['PROPERTIES']['IS_NEW']['VALUE'])): ?>
                                <span class="catalog-list__badge catalog-list__badge--new">Новинка</span>
                            <?php endif; ?>
                            <?php if (!empty($item['PROPERTIES']['IS_HIT']['VALUE'])): ?>
                                <span class="catalog-list__badge catalog-list__badge--hit">Хит</span>
                            <?php endif; ?>
                        </div>

                        <div class="catalog-list__info">
                            <h3 class="catalog-list__name"><?= htmlspecialcharsbx($item['NAME']) ?></h3>

                            <?php if (!empty($item['PROPERTIES']['ARTICLE']['VALUE'])): ?>
                                <span class="catalog-list__article">
                                    Арт: <?= htmlspecialcharsbx($item['PROPERTIES']['ARTICLE']['VALUE']) ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($item['PREVIEW_TEXT'])): ?>
                                <p class="catalog-list__desc"><?= $item['PREVIEW_TEXT'] ?></p>
                            <?php endif; ?>
                        </div>
                    </a>

                    <div class="catalog-list__actions">
                        <button
                            class="catalog-list__btn-cart"
                            data-product-id="<?= $item['ID'] ?>"
                            type="button"
                        >
                            В корзину
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Постраничная навигация -->
        <?php if ($arResult['NAV'] instanceof \Bitrix\Main\UI\PageNavigation): ?>
            <div class="catalog-list__pagination">
                <?php
                $APPLICATION->IncludeComponent('bitrix:main.pagenavigation', '', [
                    'NAV_OBJECT' => $arResult['NAV'],
                    'SEF_MODE' => 'N',
                ]);
                ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
