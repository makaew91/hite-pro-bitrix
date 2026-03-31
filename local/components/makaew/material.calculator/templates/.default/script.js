(function () {
  'use strict';

  var form = document.getElementById('calc-form');
  var resultBlock = document.getElementById('calc-result');
  if (!form || !resultBlock) return;

  var materialSelect = document.getElementById('calc-material');
  var extraParams = document.getElementById('calc-extra-params');
  var RESERVE_FACTOR = 1.10;

  // Показ/скрытие дополнительных параметров в зависимости от материала
  materialSelect.addEventListener('change', function () {
    var opt = materialSelect.options[materialSelect.selectedIndex];
    var rateUnit = (opt.dataset.rateUnit || '').toLowerCase();
    var name = opt.textContent.toLowerCase();

    var layersField = document.getElementById('calc-layers-field');
    var thicknessField = document.getElementById('calc-thickness-field');
    var tileField = document.getElementById('calc-tile-field');

    layersField.style.display = 'none';
    thicknessField.style.display = 'none';
    tileField.style.display = 'none';
    extraParams.style.display = 'none';

    if (rateUnit.indexOf('л/м') !== -1) {
      // Краска — показать слои
      layersField.style.display = '';
      extraParams.style.display = '';
    } else if (name.indexOf('штукатур') !== -1 || name.indexOf('наливн') !== -1) {
      // Штукатурка / наливной пол — показать толщину
      thicknessField.style.display = '';
      extraParams.style.display = '';
    } else if (name.indexOf('плитк') !== -1) {
      // Плитка — показать размер
      tileField.style.display = '';
      extraParams.style.display = '';
    }
  });

  // Клиентский расчёт
  document.getElementById('calc-btn').addEventListener('click', function () {
    var materialOpt = materialSelect.options[materialSelect.selectedIndex];
    if (!materialOpt.value) {
      alert('Выберите материал');
      return;
    }

    var area = parseFloat(document.getElementById('calc-area').value);
    if (!area || area <= 0) {
      alert('Введите площадь');
      return;
    }

    var rate = parseFloat(materialOpt.dataset.rate);
    var rateUnit = (materialOpt.dataset.rateUnit || '').toLowerCase();
    var unit = materialOpt.dataset.unit;
    var name = materialOpt.textContent.trim();
    var nameLower = name.toLowerCase();

    // Коэффициент поверхности
    var surfaceSelect = document.getElementById('calc-surface');
    var surfaceOpt = surfaceSelect.options[surfaceSelect.selectedIndex];
    var surfaceFactor = parseFloat(surfaceOpt.dataset.factor) || 1;

    var amount = 0;

    if (rateUnit.indexOf('л/м') !== -1) {
      // Краска
      var layers = parseInt(document.getElementById('calc-layers').value, 10) || 2;
      amount = rate * area * layers * surfaceFactor;
    } else if (nameLower.indexOf('штукатур') !== -1 || nameLower.indexOf('наливн') !== -1) {
      // Штукатурка / наливной
      var thickness = parseFloat(document.getElementById('calc-thickness').value) || 10;
      amount = rate * area * thickness * surfaceFactor;
    } else if (nameLower.indexOf('плитк') !== -1) {
      // Плитка
      var tw = parseFloat(document.getElementById('calc-tile-w').value) || 0.3;
      var th = parseFloat(document.getElementById('calc-tile-h').value) || 0.3;
      var tileArea = tw * th;
      if (tileArea > 0) {
        amount = (area / tileArea) * 1.10; // 10% на подрезку
      }
    } else {
      amount = rate * area * surfaceFactor;
    }

    var reserve = Math.ceil(amount * RESERVE_FACTOR * 100) / 100;
    amount = Math.round(amount * 100) / 100;

    showResult(name, area, amount, reserve, unit);
  });

  // Серверный расчёт через AJAX
  document.getElementById('calc-btn-server').addEventListener('click', function () {
    var materialOpt = materialSelect.options[materialSelect.selectedIndex];
    if (!materialOpt.value) {
      alert('Выберите материал');
      return;
    }

    var area = parseFloat(document.getElementById('calc-area').value);
    if (!area || area <= 0) {
      alert('Введите площадь');
      return;
    }

    var data = {
      materialId: materialOpt.value,
      area: area,
      layers: document.getElementById('calc-layers').value,
      thickness: document.getElementById('calc-thickness').value,
      tile_width: document.getElementById('calc-tile-w').value,
      tile_height: document.getElementById('calc-tile-h').value
    };

    var btn = document.getElementById('calc-btn-server');
    btn.disabled = true;
    btn.textContent = 'Считаем...';

    BX.ajax.runAction('makaew:store.api.calculator.calculate', {
      data: data
    }).then(function (response) {
      btn.disabled = false;
      btn.textContent = 'Точный расчёт (сервер)';

      if (response.data && response.data.success) {
        var d = response.data.data;
        showResult(d.material_name, d.area, d.amount, d.amount_with_reserve, d.unit);
      } else {
        alert(response.data ? response.data.error : 'Ошибка расчёта');
      }
    }).catch(function () {
      btn.disabled = false;
      btn.textContent = 'Точный расчёт (сервер)';
      alert('Ошибка соединения');
    });
  });

  function showResult(materialName, area, amount, reserve, unit) {
    document.getElementById('result-material').textContent = materialName;
    document.getElementById('result-area').textContent = area + ' м²';
    document.getElementById('result-amount').textContent = amount + ' ' + unit;
    document.getElementById('result-reserve').textContent = reserve + ' ' + unit;

    resultBlock.style.display = '';
    resultBlock.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
})();
