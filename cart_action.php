<?php
/**
 * Handles cart changes. Works with normal form posts (redirects back)
 * and with JavaScript fetch() calls (returns JSON).
 */
require_once 'includes/functions.php';

if (!is_post()) {
    redirect('cart.php');
}
verify_csrf();

$wantsJson = ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json';
$respond = function (bool $ok, string $message, string $back = 'cart.php') use ($wantsJson) {
    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'message' => $message, 'count' => cart_count()]);
        exit;
    }
    flash($ok ? 'success' : 'error', $message);
    redirect($back);
};

if (!can_shop()) {
    $respond(false, 'Staff accounts cannot place orders. Log out to order as a customer.', 'menu.php');
}

$action = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$qty = (int)($_POST['qty'] ?? 1);

switch ($action) {
    case 'add':
        $product = find_product($productId);
        if (!$product || !$product['is_available']) {
            $respond(false, 'Sorry, that product is not available.', 'menu.php');
        }
        cart_add($productId, max(1, $qty));
        $back = $_SERVER['HTTP_REFERER'] ?? 'menu.php';
        $respond(true, $product['name'] . ' added to your cart.', str_contains($back, 'cart_action') ? 'menu.php' : $back);
        break;

    case 'update':
        foreach ((array)($_POST['qty'] ?? []) as $id => $q) {
            cart_set((int)$id, (int)$q);
        }
        $respond(true, 'Cart updated.');
        break;

    case 'remove':
        cart_set($productId, 0);
        $respond(true, 'Item removed.');
        break;

    case 'clear':
        cart_clear();
        $respond(true, 'Your cart is now empty.');
        break;

    default:
        $respond(false, 'Unknown action.');
}
