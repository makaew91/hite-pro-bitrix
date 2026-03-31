(function () {
  'use strict';

  var basket = document.getElementById('basket-component');
  if (!basket) return;

  // Изменение количества
  basket.addEventListener('click', function (e) {
    var btn = e.target.closest('.basket__qty-btn');
    if (!btn) return;

    var row = btn.closest('.basket__item');
    var input = row.querySelector('.basket__qty-input');
    var basketId = input.dataset.basketId;
    var currentQty = parseInt(input.value, 10);
    var action = btn.dataset.action;

    var newQty = action === 'plus' ? currentQty + 1 : Math.max(1, currentQty - 1);
    input.value = newQty;

    updateBasketItem(basketId, newQty);
  });

  // Ручной ввод количества
  basket.addEventListener('change', function (e) {
    if (!e.target.classList.contains('basket__qty-input')) return;

    var qty = Math.max(1, parseInt(e.target.value, 10) || 1);
    e.target.value = qty;
    updateBasketItem(e.target.dataset.basketId, qty);
  });

  // Удаление
  basket.addEventListener('click', function (e) {
    var removeBtn = e.target.closest('.basket__item-remove');
    if (!removeBtn) return;

    var basketId = removeBtn.dataset.basketId;
    var row = removeBtn.closest('.basket__item');

    row.classList.add('basket__item--removing');

    BX.ajax.runAction('makaew:store.api.cart.remove', {
      data: { basketId: basketId }
    }).then(function () {
      row.remove();
      updateTotals();
    }).catch(function () {
      row.classList.remove('basket__item--removing');
    });
  });

  function updateBasketItem(basketId, quantity) {
    BX.ajax.runAction('makaew:store.api.cart.update', {
      data: { basketId: basketId, quantity: quantity }
    }).then(function (response) {
      if (response.data) {
        updateTotals(response.data);
      }
    });
  }

  function updateTotals(data) {
    if (data) {
      var totalEl = document.getElementById('basket-total');
      var countEl = document.getElementById('basket-count');
      if (totalEl) totalEl.textContent = data.totalPrice + ' ₽';
      if (countEl) countEl.textContent = data.totalCount;
    }
  }
})();
