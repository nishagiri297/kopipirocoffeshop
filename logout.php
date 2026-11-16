<?php
require_once 'includes/functions.php';

if (is_post()) {
    verify_csrf();
    // Keep the cart so a member who logs out doesn't lose it, but prices go back to normal.
    $cart = cart();
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['cart'] = $cart;
    flash('info', 'You have been logged out.');
}
redirect('index.php');
