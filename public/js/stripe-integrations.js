document.addEventListener('DOMContentLoaded', function() {
  // Cart toggle functionality
  const cartIcon = document.querySelector('.cart-icon');
  const cart = document.querySelector('.stripe-cart');
  
  if (cartIcon && cart) {
    cartIcon.addEventListener('click', function() {
      cart.classList.toggle('open');
    });
  }

  // Update cart count
  function updateCartCount() {
    const cartItems = document.querySelectorAll('.stripe-cart li');
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
      cartCount.textContent = cartItems.length;
    }
  }

  // Initial cart count update
  updateCartCount();

  // Add to cart functionality
  const addToCartButtons = document.querySelectorAll('.add-to-cart');
  addToCartButtons.forEach(button => {
    button.addEventListener('click', function() {
      const productId = this.getAttribute('data-product-id');
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=stripe_add_to_cart&product_id=' + encodeURIComponent(productId),
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        }
      });
    });
  });

  // Checkout functionality
  const checkoutButton = document.querySelector('.checkout-button');
  if (checkoutButton) {
    checkoutButton.addEventListener('click', function() {
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=stripe_handle_checkout',
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          window.location.href = data.redirect_url;
        } else {
          alert(data.message);
        }
      });
    });
  }

  // Remove from cart functionality
  const removeFromCartButtons = document.querySelectorAll('.remove-from-cart');
  removeFromCartButtons.forEach(button => {
    button.addEventListener('click', function() {
      const productId = this.getAttribute('data-product-id');
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=stripe_remove_from_cart&product_id=' + encodeURIComponent(productId),
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        }
      });
    });
  });
});


// Checkout button handler
const checkoutButton = document.querySelector('.checkout-button');
if (checkoutButton) {
  checkoutButton.addEventListener('click', function() {
    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'action=stripe_checkout'
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.success && data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        throw new Error(data.message || 'Invalid response from server');
      }
    })
    .catch(error => {
      console.error('Checkout error:', error);
      alert('Checkout failed: ' + error.message);
    });
  });
}

