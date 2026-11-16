<?php
require_once 'includes/functions.php';

if (!can_shop()) {
    flash('info', 'Staff accounts cannot place orders.');
    redirect(home_for_role(user_role()));
}

$cart = cart_summary();
if (!$cart['lines']) {
    flash('info', 'Your cart is empty.');
    redirect('menu.php');
}

$user = current_user();
$errors = [];
$form = [
    'name' => $user['name'] ?? '',
    'phone' => $user['phone'] ?? '',
    'order_type' => 'dine-in',
    'table_no' => '',
    'note' => '',
];

if (is_post()) {
    verify_csrf();
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }
    if ($form['name'] === '' || mb_strlen($form['name']) > 100) {
        $errors['name'] = 'Please enter your name.';
    }
    if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $form['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if (!in_array($form['order_type'], ['dine-in', 'takeaway'], true)) {
        $errors['order_type'] = 'Choose dine-in or takeaway.';
    }
    if ($form['order_type'] === 'dine-in' && !preg_match('/^[A-Za-z0-9]{1,10}$/', $form['table_no'])) {
        $errors['table_no'] = 'Enter your table number.';
    }

    if (!$errors) {
        $cart = cart_summary(); // recalculate right before saving
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO orders (order_code, customer_id, customer_name, customer_phone, cashier_id, order_type, table_no, note, subtotal, discount, total, status, created_at)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                'TMP-' . bin2hex(random_bytes(6)),
                $user && $user['role'] === 'customer' ? $user['id'] : null,
                $form['name'],
                $form['phone'] ?: null,
                pick_cashier(),
                $form['order_type'],
                $form['order_type'] === 'dine-in' ? $form['table_no'] : null,
                mb_substr($form['note'], 0, 255) ?: null,
                $cart['subtotal'],
                $cart['discount'],
                $cart['total'],
                'pending',
                date('Y-m-d H:i:s'),
            ]);
            $orderId = (int)$pdo->lastInsertId();
            $code = sprintf('KP-%05d', $orderId);
            $pdo->prepare('UPDATE orders SET order_code = ? WHERE id = ?')->execute([$code, $orderId]);

            $item = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($cart['lines'] as $line) {
                $item->execute([$orderId, $line['product']['id'], $line['product']['name'], $line['unit_price'], $line['unit_discount'], $line['qty'], $line['line_total']]);
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }

        cart_clear();
        $_SESSION['last_order'] = $orderId; // lets guests see their confirmation page
        redirect('order_success.php?id=' . $orderId);
    }
}

$pageTitle = 'Checkout';
require 'includes/header.php';
?>

<section class="container section-tight">
    <h1 class="page-title">Checkout</h1>

    <?php if (!is_member()): ?>
        <div class="alert alert-info checkout-member-alert">
            You're checking out as a <strong>guest</strong>, so regular prices apply.
            <a href="login.php?next=checkout.php">Log in</a> or <a href="register.php">sign up</a> to get <?= discount_label() ?> off<?= DISCOUNT_COFFEE_ONLY ? ' your coffee' : '' ?>. Your cart will be kept.
        </div>
    <?php endif; ?>

    <div class="checkout-grid">
        <form method="post" class="card" novalidate>
            <?= csrf_field() ?>
            <h2>Your details</h2>
            <div class="form-row">
                <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
                    <label for="name">Name</label>
                    <input id="name" name="name" value="<?= e($form['name']) ?>" required maxlength="100">
                    <?php if (isset($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                    <label for="phone">Phone <span class="muted">(optional)</span></label>
                    <input id="phone" name="phone" value="<?= e($form['phone']) ?>" inputmode="tel">
                    <?php if (isset($errors['phone'])): ?><small class="error"><?= e($errors['phone']) ?></small><?php endif; ?>
                </div>
            </div>

            <div class="field">
                <label>How would you like your order?</label>
                <div class="segmented">
                    <label><input type="radio" name="order_type" value="dine-in" <?= $form['order_type'] === 'dine-in' ? 'checked' : '' ?>><span>🪑 Dine-in</span></label>
                    <label><input type="radio" name="order_type" value="takeaway" <?= $form['order_type'] === 'takeaway' ? 'checked' : '' ?>><span>🥡 Takeaway</span></label>
                </div>
            </div>
            <div class="field table-field <?= isset($errors['table_no']) ? 'has-error' : '' ?>" <?= $form['order_type'] === 'takeaway' ? 'hidden' : '' ?>>
                <label for="table_no">Table number</label>
                <input id="table_no" name="table_no" value="<?= e($form['table_no']) ?>" maxlength="10" placeholder="e.g. 7">
                <?php if (isset($errors['table_no'])): ?><small class="error"><?= e($errors['table_no']) ?></small><?php endif; ?>
            </div>
            <div class="field">
                <label for="note">Notes for the barista <span class="muted">(optional)</span></label>
                <textarea id="note" name="note" rows="2" maxlength="255" placeholder="Less sugar, oat milk, extra hot…"><?= e($form['note']) ?></textarea>
            </div>
            <div class="pay-note">
                <strong>Payment:</strong> pay at the cashier counter (cash, card or QRIS). Your order goes straight to a cashier.
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Place order · <?= money($cart['total']) ?></button>
        </form>

        <aside class="card summary-card">
            <h2>Order items</h2>
            <table class="items-table">
                <thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Total</th></tr></thead>
                <tbody>
                <?php foreach ($cart['lines'] as $line): ?>
                    <tr>
                        <td>
                            <div class="item-cell">
                                <img src="<?= e(product_image($line['product']['image'])) ?>" alt="">
                                <span><?= e($line['product']['name']) ?>
                                <?php if ($line['unit_discount'] > 0): ?><span class="chip chip-save chip-static">-<?= discount_label() ?></span><?php endif; ?></span>
                            </div>
                        </td>
                        <td class="num"><?= (int)$line['qty'] ?></td>
                        <td class="num">
                            <?php if ($line['unit_discount'] > 0): ?>
                                <s class="muted"><?= money($line['unit_price']) ?></s><br><?= money($line['unit_price'] - $line['unit_discount']) ?>
                            <?php else: ?>
                                <?= money($line['unit_price']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="num"><strong><?= money($line['line_total']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <dl class="totals">
                <div><dt>Subtotal</dt><dd><?= money($cart['subtotal']) ?></dd></div>
                <div class="discount-row"><dt>Member discount<?= is_member() ? ' (' . discount_label() . ')' : '' ?></dt><dd>− <?= money($cart['discount']) ?></dd></div>
                <div class="grand"><dt>Total to pay</dt><dd><?= money($cart['total']) ?></dd></div>
            </dl>
            <a href="cart.php" class="link-arrow">← Edit cart</a>
        </aside>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
