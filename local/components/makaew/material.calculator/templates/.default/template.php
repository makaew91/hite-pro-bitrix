<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
$materials = $arResult['MATERIALS'];
$selected = $arResult['SELECTED_MATERIAL'];
$surfaceTypes = $arResult['SURFACE_TYPES'];
?>

<div class="calculator" id="material-calculator">
    <h2 class="calculator__title">Калькулятор расхода материалов</h2>
    <p class="calculator__desc">Рассчитайте необходимое количество материала для вашего проекта</p>

    <form class="calculator__form" id="calc-form">
        <!-- Выбор материала -->
        <div class="calculator__field">
            <label class="calculator__label" for="calc-material">Материал</label>
            <select class="calculator__select" id="calc-material" name="material_id" required>
                <option value="">Выберите материал</option>
                <?php foreach ($materials as $mat): ?>
                    <option
                        value="<?= $mat['ID'] ?>"
                        data-rate="<?= $mat['RATE'] ?>"
                        data-rate-unit="<?= htmlspecialcharsbx($mat['RATE_UNIT']) ?>"
                        data-unit="<?= htmlspecialcharsbx($mat['UNIT']) ?>"
                        <?= ($selected && $selected['id'] == $mat['ID']) ? 'selected' : '' ?>
                    >
                        <?= htmlspecialcharsbx($mat['NAME']) ?>
                        (<?= $mat['RATE'] ?> <?= htmlspecialcharsbx($mat['RATE_UNIT']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Площадь -->
        <div class="calculator__field">
            <label class="calculator__label" for="calc-area">Площадь, м²</label>
            <input
                type="number"
                class="calculator__input"
                id="calc-area"
                name="area"
                min="0.1"
                step="0.1"
                placeholder="Например: 25"
                required
            >
        </div>

        <!-- Тип поверхности -->
        <div class="calculator__field">
            <label class="calculator__label" for="calc-surface">Тип поверхности</label>
            <select class="calculator__select" id="calc-surface" name="surface_type">
                <?php foreach ($surfaceTypes as $code => $type): ?>
                    <option value="<?= $code ?>" data-factor="<?= $type['factor'] ?>">
                        <?= htmlspecialcharsbx($type['name']) ?>
                        (×<?= $type['factor'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Дополнительные параметры (динамически показываются JS) -->
        <div class="calculator__extra" id="calc-extra-params" style="display:none;">
            <div class="calculator__field" id="calc-layers-field" style="display:none;">
                <label class="calculator__label" for="calc-layers">Количество слоёв</label>
                <input type="number" class="calculator__input" id="calc-layers" name="layers" value="2" min="1" max="5">
            </div>

            <div class="calculator__field" id="calc-thickness-field" style="display:none;">
                <label class="calculator__label" for="calc-thickness">Толщина слоя, мм</label>
                <input type="number" class="calculator__input" id="calc-thickness" name="thickness" value="10" min="1" max="100">
            </div>

            <div class="calculator__field" id="calc-tile-field" style="display:none;">
                <label class="calculator__label">Размер плитки</label>
                <div class="calculator__tile-size">
                    <input type="number" class="calculator__input" id="calc-tile-w" name="tile_width" value="0.3" min="0.01" step="0.01" placeholder="Ширина, м">
                    <span>×</span>
                    <input type="number" class="calculator__input" id="calc-tile-h" name="tile_height" value="0.3" min="0.01" step="0.01" placeholder="Высота, м">
                </div>
            </div>
        </div>

        <div class="calculator__actions">
            <button type="button" class="calculator__btn-calc" id="calc-btn">Рассчитать</button>
            <button type="button" class="calculator__btn-server" id="calc-btn-server">Точный расчёт (сервер)</button>
        </div>
    </form>

    <!-- Результат -->
    <div class="calculator__result" id="calc-result" style="display:none;">
        <h3 class="calculator__result-title">Результат расчёта</h3>
        <div class="calculator__result-grid">
            <div class="calculator__result-item">
                <span class="calculator__result-label">Материал:</span>
                <span class="calculator__result-value" id="result-material"></span>
            </div>
            <div class="calculator__result-item">
                <span class="calculator__result-label">Площадь:</span>
                <span class="calculator__result-value" id="result-area"></span>
            </div>
            <div class="calculator__result-item">
                <span class="calculator__result-label">Расход:</span>
                <strong class="calculator__result-value calculator__result-value--main" id="result-amount"></strong>
            </div>
            <div class="calculator__result-item">
                <span class="calculator__result-label">С запасом (10%):</span>
                <strong class="calculator__result-value calculator__result-value--reserve" id="result-reserve"></strong>
            </div>
        </div>
        <button type="button" class="calculator__btn-cart" id="result-add-cart" style="display:none;">
            Добавить в корзину
        </button>
    </div>
</div>
