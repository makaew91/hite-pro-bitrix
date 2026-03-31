<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
?>

<div class="section-list">
    <?php if (empty($arResult['SECTIONS'])): ?>
        <p class="section-list__empty">Разделы не найдены</p>
    <?php else: ?>
        <div class="section-list__grid">
            <?php foreach ($arResult['SECTIONS'] as $section): ?>
                <a href="<?= htmlspecialcharsbx($section['SECTION_PAGE_URL']) ?>" class="section-list__item">
                    <?php if ($section['PICTURE_SRC']): ?>
                        <div class="section-list__image">
                            <img
                                src="<?= htmlspecialcharsbx($section['PICTURE_SRC']) ?>"
                                alt="<?= htmlspecialcharsbx($section['NAME']) ?>"
                                loading="lazy"
                            >
                        </div>
                    <?php endif; ?>

                    <div class="section-list__info">
                        <h3 class="section-list__name"><?= htmlspecialcharsbx($section['NAME']) ?></h3>
                        <?php if ($section['ELEMENT_CNT'] > 0): ?>
                            <span class="section-list__count">
                                <?= $section['ELEMENT_CNT'] ?> <?= $this->pluralize(
                                    $section['ELEMENT_CNT'],
                                    'товар', 'товара', 'товаров'
                                ) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
