'use strict';

const updateCartBadge = (count) => {
    const cartLink = document.querySelector('a[href="cart.php"]');
    if (!cartLink) {
        return;
    }

    let badge = cartLink.querySelector('.badge');
    if (count < 1) {
        badge?.remove();
        return;
    }

    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle';
        cartLink.append(badge);
    }
    badge.textContent = count;
};

const sendShopAction = async (button, action, csrfToken, message) => {
    button.disabled = true;
    try {
        const response = await fetch('shop.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({
                ajax_action: action,
                product_id: button.dataset.productId,
                csrf_token: csrfToken
            })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            message.replaceChildren(document.createTextNode(result.message || 'The request could not be completed.'));
            message.className = 'alert alert-warning';
            if (result.login_required === true) {
                const loginLink = document.createElement('a');
                loginLink.href = 'login.php';
                loginLink.className = 'alert-link ms-1';
                loginLink.textContent = 'Sign in';
                message.append(' ', loginLink);
            }
            return;
        }

        message.textContent = result.message;
        message.className = 'alert alert-success';
        if (action === 'toggle_wishlist') {
            button.setAttribute('aria-pressed', String(result.wishlisted));
            button.setAttribute('aria-label', result.wishlisted ? 'Remove from wishlist' : 'Add to wishlist');
            button.title = result.wishlisted ? 'Remove from wishlist' : 'Add to wishlist';
            button.querySelector('i').classList.toggle('fas', result.wishlisted);
            button.querySelector('i').classList.toggle('far', !result.wishlisted);
        }
        if (action === 'add_to_cart' && Number.isInteger(result.cart_count)) {
            updateCartBadge(result.cart_count);
        }
    } catch (error) {
        message.textContent = 'A network error prevented the request. Please try again.';
        message.className = 'alert alert-warning';
    } finally {
        button.disabled = false;
    }
};

const initializeShopActions = () => {
    const shopPage = document.getElementById('shop-page');
    if (!shopPage) {
        return;
    }

    const message = document.getElementById('shop-message');
    shopPage.addEventListener('click', (event) => {
        const button = event.target.closest('.add-to-cart, .wishlist-btn');
        if (!button) {
            return;
        }

        event.preventDefault();
        const action = button.classList.contains('add-to-cart') ? 'add_to_cart' : 'toggle_wishlist';
        sendShopAction(button, action, shopPage.dataset.csrfToken, message);
    });
};

const initializeCartActions = () => {
    const cartPage = document.getElementById('cart-page');
    if (!cartPage) {
        return;
    }

    const message = document.getElementById('cart-message');
    const showCartMessage = (text, isError = false) => {
        message.textContent = text;
        message.className = `alert ${isError ? 'alert-warning' : 'alert-success'}`;
    };

    const updateCartSummary = (result) => {
        document.getElementById('cart-subtotal').textContent = result.subtotal;
        document.getElementById('cart-shipping').textContent = result.shipping;
        document.getElementById('cart-total').textContent = result.total;

        const shippingNote = document.getElementById('shipping-note');
        if (result.shipping_remaining === null) {
            shippingNote.classList.add('d-none');
        } else {
            document.getElementById('shipping-remaining').textContent = result.shipping_remaining;
            shippingNote.classList.remove('d-none');
        }
        updateCartBadge(result.cart_count);
    };

    cartPage.addEventListener('click', async (event) => {
        const button = event.target.closest('.quantity-button, .remove-item');
        if (!button) {
            return;
        }

        event.preventDefault();
        const row = button.closest('tr[data-cart-id]');
        if (!row) {
            return;
        }

        const removeItem = button.classList.contains('remove-item');
        button.disabled = true;
        try {
            const response = await fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: new URLSearchParams({
                    ajax_action: removeItem ? 'remove_item' : 'update_quantity',
                    cart_id: row.dataset.cartId,
                    change: removeItem ? '' : button.dataset.change,
                    csrf_token: cartPage.dataset.csrfToken
                })
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                showCartMessage(result.message || 'The cart could not be updated. Please try again.', true);
                return;
            }

            updateCartSummary(result);
            showCartMessage(result.message);
            if (removeItem) {
                row.remove();
                if (result.cart_count === 0) {
                    window.location.reload();
                }
                return;
            }

            row.querySelector('.cart-quantity').value = result.quantity;
            row.querySelector('.item-total').textContent = result.item_total;
            row.querySelector('[data-change="-1"]').disabled = result.quantity <= 1;
            row.querySelector('[data-change="1"]').disabled = result.quantity >= result.available_stock;
        } catch (error) {
            showCartMessage('A network error prevented the cart update. Please try again.', true);
        } finally {
            if (button.isConnected) {
                button.disabled = false;
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initializeShopActions();
    initializeCartActions();

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-confirm]');
        if (form && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent default button behavior
        const productId = this.getAttribute('data-product-id');
        const icon = this.querySelector('i');
        
        fetch('ajax_wishlist.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `product_id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                if(data.action === 'added') {
                    icon.classList.remove('far'); // Regular heart
                    icon.classList.add('fas', 'text-danger'); // Solid red heart
                } else {
                    icon.classList.remove('fas', 'text-danger');
                    icon.classList.add('far');
                }
            } else {
                alert(data.message || 'Please login to use the wishlist.');
                window.location.href = 'login.php';
            }
        });
    });
});