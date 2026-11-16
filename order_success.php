<?php
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT o.*, c.name AS cashier_name FROM orders o LEFT JOIN users c ON c.id = o.cashier_id WHERE o.id = ?');
$stmt->execute([$id]);
$o = $stmt->fetch();

// Only the guest who just placed it, or the customer who owns it, may view this page
$user = current_user();
$allowed = $o && (($_SESSION['last_order'] ?? 0) === $id || ($user && (int)$o['customer_id'] === $user['id']));
if (!$allowed) {
    flash('error', 'Order not found.');
    redirect('menu.php');
}
$items = order_items($id);

$pageTitle = 'Order ' . $o['order_code'];
require 'includes/header.php';
?>

<section class="container section-tight narrow">
    <div class="card success-card">
        <div class="success-icon">✓</div>
        <h1>Thank you, <?= e(explode(' ', $o['customer_name'])[0]) ?>!</h1>
        <p class="lead">Your order <strong><?= e($o['order_code']) ?></strong> has been sent to the cashier.</p>
        <div class="order-meta">
            <div><small>Status</small><?= status_badge($o['status']) ?></div>
            <div><small>Type</small><strong><?= e(ucfirst($o['order_type'])) ?><?= $o['table_no'] ? ' · Table ' . e($o['table_no']) : '' ?></strong></div>
            <div><small>Cashier</small><strong><?= e($o['cashier_name'] ?? 'Next available') ?></strong></div>
            <div><small>Placed</small><strong><?= e(date('d M Y, H:i', strtotime($o['created_at']))) ?></strong></div>
        </div>
        <?php include 'includes/order_items.php'; ?>
        <p class="muted">Please pay <strong><?= money($o['total']) ?></strong> at the counter and mention your order code.</p>
        <div class="hero-actions center">
            <a href="menu.php" class="btn btn-ghost">Order more</a>
            <?php if ($user): ?><a href="my_orders.php" class="btn btn-primary">Track my orders</a>
            <?php else: ?><a href="register.php" class="btn btn-primary">Sign up &amp; save <?= discount_label() ?> next time</a><?php endif; ?>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
