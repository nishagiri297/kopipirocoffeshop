<?php
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$p = find_product($id);
if (!$p) {
    http_response_code(404);
    flash('error', 'That product does not exist.');
    redirect('menu.php');
}

// Save a rating (logged-in customers only, one rating per product, can be updated)
if (is_post()) {
    verify_csrf();
    $user = require_role('customer');
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
    $comment = mb_substr(trim($_POST['comment'] ?? ''), 0, 255);

    $stmt = db()->prepare('SELECT id FROM ratings WHERE product_id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    if ($existing = $stmt->fetchColumn()) {
        db()->prepare('UPDATE ratings SET rating = ?, comment = ?, created_at = ? WHERE id = ?')
            ->execute([$rating, $comment, date('Y-m-d H:i:s'), $existing]);
        flash('success', 'Your rating was updated.');
    } else {
        db()->prepare('INSERT INTO ratings (product_id, user_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $user['id'], $rating, $comment, date('Y-m-d H:i:s')]);
        flash('success', 'Thanks for rating ' . $p['name'] . '!');
    }
    redirect('product.php?id=' . $id . '#reviews');
}

$stmt = db()->prepare('SELECT r.*, u.name FROM ratings r JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC');
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$myRating = null;
if (is_member()) {
    foreach ($reviews as $r) {
        if ((int)$r['user_id'] === current_user()['id']) { $myRating = $r; }
    }
}

$stmt = db()->prepare(product_select_sql() . ' WHERE p.category_id = ? AND p.id <> ? AND p.is_available = 1 ORDER BY avg_rating DESC LIMIT 4');
$stmt->execute([$p['category_id'], $id]);
$related = $stmt->fetchAll();

$pageTitle = $p['name'];
require 'includes/header.php';
?>

<section class="container section-tight">
    <nav class="breadcrumb"><a href="menu.php">Menu</a> / <a href="menu.php?category=<?= e($p['category_slug']) ?>"><?= e($p['category_name']) ?></a> / <span><?= e($p['name']) ?></span></nav>

    <div class="product-detail">
        <div class="detail-media">
            <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>">
        </div>
        <div class="detail-info">
            <span class="chip chip-<?= e($p['category_slug']) ?> chip-static"><?= e($p['category_name']) ?></span>
            <h1><?= e($p['name']) ?></h1>
            <div class="product-rating big"><?= stars((float)$p['avg_rating'], (int)$p['rating_count']) ?></div>
            <p class="detail-desc"><?= nl2br(e($p['description'])) ?></p>

            <div class="detail-price">
                <?php if (discount_applies($p)): ?>
                    <strong><?= money(final_price($p)) ?></strong>
                    <s><?= money($p['price']) ?></s>
                    <span class="chip chip-save chip-static">Member -<?= discount_label() ?></span>
                <?php else: ?>
                    <strong><?= money($p['price']) ?></strong>
                <?php endif; ?>
            </div>
            <?php if (!is_member() && can_shop() && (!DISCOUNT_COFFEE_ONLY || $p['is_coffee'])): ?>
                <p class="hint">💡 Members pay <strong><?= money($p['price'] - round($p['price'] * MEMBER_DISCOUNT_RATE)) ?></strong> for this. <a href="login.php?next=<?= urlencode('product.php?id=' . $id) ?>">Log in</a> or <a href="register.php">sign up</a>.</p>
            <?php endif; ?>

            <?php if (!$p['is_available']): ?>
                <div class="alert alert-warning">This item is currently sold out.</div>
            <?php elseif (can_shop()): ?>
                <form action="cart_action.php" method="post" class="detail-cart add-to-cart-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <div class="qty-stepper">
                        <button type="button" data-step="-1" aria-label="Decrease">−</button>
                        <input type="number" name="qty" value="1" min="1" max="50" aria-label="Quantity">
                        <button type="button" data-step="1" aria-label="Increase">+</button>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Add to cart</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="container section-tight" id="reviews">
    <div class="reviews-grid">
        <div class="card">
            <h2>Ratings &amp; reviews</h2>
            <div class="rating-summary">
                <span class="rating-big"><?= $p['rating_count'] ? number_format($p['avg_rating'], 1) : '–' ?></span>
                <div><?= stars((float)$p['avg_rating']) ?><p class="muted"><?= (int)$p['rating_count'] ?> rating<?= $p['rating_count'] == 1 ? '' : 's' ?></p></div>
            </div>

            <?php if (is_member()): ?>
                <form method="post" class="rating-form">
                    <?= csrf_field() ?>
                    <label><?= $myRating ? 'Update your rating' : 'Rate this item' ?></label>
                    <div class="star-input">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= ($myRating['rating'] ?? 5) == $i ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>" title="<?= $i ?> stars">★</label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comment" rows="2" maxlength="255" placeholder="Tell others what you thought (optional)"><?= e($myRating['comment'] ?? '') ?></textarea>
                    <button class="btn btn-primary btn-sm" type="submit">Submit rating</button>
                </form>
            <?php elseif (!current_user()): ?>
                <p class="muted"><a href="login.php?next=<?= urlencode('product.php?id=' . $id) ?>">Log in</a> as a customer to rate this item.</p>
            <?php endif; ?>
        </div>

        <div class="review-list">
            <?php if (!$reviews): ?>
                <p class="muted">No reviews yet. Be the first!</p>
            <?php endif; ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review">
                    <div class="review-head">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span>
                        <div><strong><?= e($r['name']) ?></strong><small class="muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></small></div>
                        <?= stars((float)$r['rating']) ?>
                    </div>
                    <?php if ($r['comment']): ?><p><?= e($r['comment']) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($related): ?>
<section class="container section">
    <div class="section-head"><div><span class="eyebrow">You may also like</span><h2>More <?= e(strtolower($p['category_name'])) ?></h2></div></div>
    <div class="product-grid">
        <?php foreach ($related as $p) { include 'includes/product_card.php'; } ?>
    </div>
</section>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
