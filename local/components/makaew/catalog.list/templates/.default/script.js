(function () {
  'use strict';

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.catalog-list__btn-cart');
    if (!btn) return;

    e.preventDefault();
    var productId = btn.dataset.productId;
    if (!productId) return;

    btn.disabled = true;
    btn.textContent = 'Добавляем...';

    BX.ajax.runAction('makaew:store.api.cart.add', {
      data: { productId: productId, quantity: 1 }
    }).then(function (response) {
      btn.textContent = 'Добавлено ✓';
      btn.classList.add('catalog-list__btn-cart--added');

      // Обновляем счётчик корзины в шапке
      var cartCounter = document.querySelector('.header-cart__count');
      if (cartCounter && response.data && response.data.cartCount) {
        cartCounter.textContent = response.data.cartCount;
      }

      setTimeout(function () {
        btn.textContent = 'В корзину';
        btn.classList.remove('catalog-list__btn-cart--added');
        btn.disabled = false;
      }, 2000);
    }).catch(function () {
      btn.textContent = 'Ошибка';
      btn.disabled = false;

      setTimeout(function () {
        btn.textContent = 'В корзину';
      }, 2000);
    });
  });
})();
