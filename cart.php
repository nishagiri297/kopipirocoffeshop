<?php
require_once 'includes/functions.php';

if (!can_shop()) {
    flash('info', 'Staff accounts cannot place orders.');
    redirect(home_for_role(user_role()));
}
$cart = cart_summary();

$pageTitle = 'Your cart';
require 'includes/header.php';
?>

<section class="container section-tight">
    <h1 class="page-title">Your cart</h1>

    <?php if (!$cart['lines']): ?>
        <div class="empty-state card">
            <img src="assets/img/placeholder.svg" alt="" width="120">
            <h3>Your cart is empty</h3>
            <p>Add a coffee or some toast to get started.</p>
            <a href="menu.php" class="btn btn-primary">Browse the menu</a>
        </div>
    <?php else: ?>
    <div class="checkout-grid">
        <form action="cart_action.php" method="post" class="card cart-card" id="cart-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <?php foreach ($cart['lines'] as $line): $p = $line['product']; ?>
                <div class="cart-line">
                    <img src="<?= e(product_image($p['image'])) ?>" alt="">
                    <div class="cart-line-info">
                        <a href="product.php?id=<?= (int)$p['id'] ?>"><strong><?= e($p['name']) ?></strong></a>
                        <small class="muted"><?= e($p['category_name']) ?></small>
                        <div class="unit-price">
                            <?php if ($line['unit_discount'] > 0): ?>
                                <s><?= money($line['unit_price']) ?></s> <?= money($line['unit_price'] - $line['unit_discount']) ?>
                                <span class="chip chip-save chip-static">-<?= discount_label() ?></span>
                            <?php else: ?>
                                <?= money($line['unit_price']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="qty-stepper small">
                        <button type="button" data-step="-1" aria-label="Decrease">−</button>
                        <input type="number" name="qty[<?= (int)$p['id'] ?>]" value="<?= (int)$line['qty'] ?>" min="0" max="50" class="auto-submit" aria-label="Quantity">
                        <button type="button" data-step="1" aria-label="Increase">+</button>
                    </div>
                    <strong class="line-total"><?= money($line['line_total']) ?></strong>
                    <button type="submit" form="remove-<?= (int)$p['id'] ?>" class="icon-btn" aria-label="Remove">✕</button>
                </div>
            <?php endforeach; ?>
            <div class="cart-actions">
                <a href="menu.php" class="link-arrow">← Continue shopping</a>
                <noscript><button class="btn btn-ghost btn-sm" type="submit">Update cart</button></noscript>
                <button type="submit" form="clear-cart" class="btn btn-text btn-sm">Clear cart</button>
            </div>
        </form>

        <?php foreach ($cart['lines'] as $line): ?>
            <form id="remove-<?= (int)$line['product']['id'] ?>" action="cart_action.php" method="post" hidden>
                <?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?= (int)$line['product']['id'] ?>">
            </form>
        <?php endforeach; ?>
        <form id="clear-cart" action="cart_action.php" method="post" hidden data-confirm="Remove everything from your cart?">
            <?= csrf_field() ?><input type="hidden" name="action" value="clear">
        </form>

        <aside class="card summary-card">
            <h2>Order summary</h2>
            <dl class="totals">
                <div><dt>Subtotal (<?= $cart['count'] ?> items)</dt><dd><?= money($cart['subtotal']) ?></dd></div>
                <div class="discount-row"><dt>Member discount</dt><dd>− <?= money($cart['discount']) ?></dd></div>
                <div class="grand"><dt>Total</dt><dd><?= money($cart['total']) ?></dd></div>
            </dl>
            <?php if (!is_member()): ?>
                <div class="alert alert-info">
                    <strong>Save <?= discount_label() ?><?= DISCOUNT_COFFEE_ONLY ? ' on coffee' : '' ?>.</strong>
                    <a href="login.php?next=cart.php">Log in</a> or <a href="register.php">create an account</a> before checking out. Your cart is kept.
                </div>
            <?php endif; ?>
            <a href="checkout.php" class="btn btn-primary btn-lg btn-block">Proceed to checkout</a>
        </aside>
    </div>
    <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>
