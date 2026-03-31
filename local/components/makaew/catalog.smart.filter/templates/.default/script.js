(function () {
  'use strict';

  var form = document.getElementById('smart-filter');
  if (!form) return;

  var debounceTimer = null;
  var catalogContainer = document.querySelector('.catalog-list');

  /**
   * AJAX-обновление каталога при изменении фильтра.
   */
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    applyFilter();
  });

  // Автоприменение при изменении чекбоксов
  form.addEventListener('change', function (e) {
    if (e.target.type === 'checkbox') {
      applyFilter();
    }
  });

  // Автоприменение числовых полей с debounce
  form.addEventListener('input', function (e) {
    if (e.target.type === 'number') {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(applyFilter, 800);
    }
  });

  function applyFilter() {
    var formData = new FormData(form);
    var params = new URLSearchParams(formData).toString();
    var url = window.location.pathname + '?' + params;

    // Обновляем URL без перезагрузки
    window.history.pushState({}, '', url);

    // Показываем лоадер
    if (catalogContainer) {
      catalogContainer.classList.add('catalog-list--loading');
    }

    // AJAX-запрос
    BX.ajax({
      url: url,
      method: 'GET',
      dataType: 'html',
      onsuccess: function (html) {
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;

        // Обновляем список товаров
        var newCatalog = tempDiv.querySelector('.catalog-list');
        if (newCatalog && catalogContainer) {
          catalogContainer.innerHTML = newCatalog.innerHTML;
          catalogContainer.classList.remove('catalog-list--loading');
        }

        // Обновляем счётчики в фильтре
        var newFilter = tempDiv.querySelector('.smart-filter');
        if (newFilter) {
          updateFilterCounts(newFilter);
        }
      },
      onfailure: function () {
        if (catalogContainer) {
          catalogContainer.classList.remove('catalog-list--loading');
        }
      }
    });
  }

  /**
   * Обновление количества товаров рядом с чекбоксами.
   */
  function updateFilterCounts(newFilterEl) {
    var newCheckboxes = newFilterEl.querySelectorAll('.smart-filter__checkbox');
    newCheckboxes.forEach(function (newLabel) {
      var input = newLabel.querySelector('input');
      if (!input) return;

      var existingInput = form.querySelector(
        'input[name="' + input.name + '"][value="' + input.value + '"]'
      );

      if (existingInput) {
        var existingLabel = existingInput.closest('.smart-filter__checkbox');
        var countSpan = newLabel.querySelector('.smart-filter__count');
        var existingCount = existingLabel.querySelector('.smart-filter__count');

        if (countSpan && existingCount) {
          existingCount.textContent = countSpan.textContent;
        }
      }
    });
  }
})();
