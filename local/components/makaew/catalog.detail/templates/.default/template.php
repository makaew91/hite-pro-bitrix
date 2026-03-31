<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
$element = $arResult['ELEMENT'];
$properties = $arResult['PROPERTIES'];
$specs = $arResult['SPECIFICATIONS'];
?>

<!-- Хлебные крошки -->
<nav class="breadcrumbs" aria-label="Навигация">
    <?php foreach ($arResult['BREADCRUMBS'] as $i => $crumb): ?>
        <?php if ($i > 0): ?><span class="breadcrumbs__sep">/</span><?php endif; ?>
        <?php if ($crumb['URL']): ?>
            <a href="<?= htmlspecialcharsbx($crumb['URL']) ?>" class="breadcrumbs__link">
                <?= htmlspecialcharsbx($crumb['NAME']) ?>
            </a>
        <?php else: ?>
            <span class="breadcrumbs__current"><?= htmlspecialcharsbx($crumb['NAME']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>

<div class="product-detail">
    <!-- Изображение -->
    <div class="product-detail__gallery">
        <?php if ($element['DETAIL_PICTURE_SRC']): ?>
            <img
                src="<?= htmlspecialcharsbx($element['DETAIL_PICTURE_SRC']) ?>"
                alt="<?= htmlspecialcharsbx($element['NAME']) ?>"
                class="product-detail__image"
            >
        <?php elseif ($element['PREVIEW_PICTURE_SRC']): ?>
            <img
                src="<?= htmlspecialcharsbx($element['PREVIEW_PICTURE_SRC']) ?>"
                alt="<?= htmlspecialcharsbx($element['NAME']) ?>"
                class="product-detail__image"
            >
        <?php else: ?>
            <div class="product-detail__no-image">Нет изображения</div>
        <?php endif; ?>

        <!-- Бейджи -->
        <div class="product-detail__badges">
            <?php if (!empty($properties['IS_NEW']['VALUE'])): ?>
                <span class="product-detail__badge product-detail__badge--new">Новинка</span>
            <?php endif; ?>
            <?php if (!empty($properties['IS_HIT']['VALUE'])): ?>
                <span class="product-detail__badge product-detail__badge--hit">Хит продаж</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Информация -->
    <div class="product-detail__content">
        <h1 class="product-detail__title"><?= htmlspecialcharsbx($element['NAME']) ?></h1>

        <?php if (!empty($properties['ARTICLE']['VALUE'])): ?>
            <p class="product-detail__article">Артикул: <?= htmlspecialcharsbx($properties['ARTICLE']['VALUE']) ?></p>
        <?php endif; ?>

        <?php if ($element['PREVIEW_TEXT']): ?>
            <div class="product-detail__preview"><?= $element['PREVIEW_TEXT'] ?></div>
        <?php endif; ?>

        <!-- Кнопки -->
        <div class="product-detail__actions">
            <button
                class="product-detail__btn-cart"
                data-product-id="<?= $element['ID'] ?>"
                type="button"
            >
                Добавить в корзину
            </button>
            <a href="/calculator/?material=<?= urlencode($element['CODE']) ?>" class="product-detail__btn-calc">
                Рассчитать расход
            </a>
        </div>

        <!-- Характеристики -->
        <?php if (!empty($specs)): ?>
            <div class="product-detail__specs">
                <h2 class="product-detail__specs-title">Характеристики</h2>
                <table class="product-detail__specs-table">
                    <tbody>
                    <?php foreach ($specs as $spec): ?>
                        <tr>
                            <td class="product-detail__spec-name"><?= htmlspecialcharsbx($spec['NAME']) ?></td>
                            <td class="product-detail__spec-value"><?= htmlspecialcharsbx($spec['VALUE']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Описание -->
<?php if ($element['DETAIL_TEXT']): ?>
    <div class="product-detail__description">
        <h2>Описание</h2>
        <div class="product-detail__text"><?= $element['DETAIL_TEXT'] ?></div>
    </div>
<?php endif; ?>

<!-- Документация -->
<?php if (!empty($properties['DOCS']['VALUE'])): ?>
    <div class="product-detail__docs">
        <h2>Документация</h2>
        <ul class="product-detail__docs-list">
            <?php
            $docValues = (array) $properties['DOCS']['VALUE'];
            foreach ($docValues as $fileId):
                $file = CFile::GetFileArray($fileId);
                if (!$file) continue;
                ?>
                <li>
                    <a href="<?= htmlspecialcharsbx($file['SRC']) ?>" target="_blank">
                        <?= htmlspecialcharsbx($file['ORIGINAL_NAME']) ?>
                        (<?= CFile::FormatSize($file['FILE_SIZE']) ?>)
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
