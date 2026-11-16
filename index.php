<?php
require_once 'includes/functions.php';

$featured = db()->query(product_select_sql() . ' WHERE p.is_available = 1 ORDER BY avg_rating DESC, rating_count DESC, p.id LIMIT 4')->fetchAll();
$counts = [];
foreach (db()->query('SELECT category_id, COUNT(*) AS n FROM products WHERE is_available = 1 GROUP BY category_id') as $row) {
    $counts[$row['category_id']] = $row['n'];
}
$avgRating = (float)db()->query('SELECT AVG(rating) FROM ratings')->fetchColumn();

$pageTitle = 'Coffee & Toast';
require 'includes/header.php';
?>

<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Freshly roasted · Brewed to order</span>
            <h1>Good coffee, <em>thick toast</em>, slow mornings.</h1>
            <p class="lead">Order ahead from Kopipiro and pick it up at the counter, or sit down and we'll bring it to your table.</p>
            <div class="hero-actions">
                <a href="menu.php" class="btn btn-primary btn-lg">Order now</a>
                <?php if (!current_user()): ?>
                    <a href="register.php" class="btn btn-ghost btn-lg">Become a member</a>
                <?php endif; ?>
            </div>
            <ul class="hero-stats">
                <li><strong><?= array_sum($counts) ?></strong><span>handcrafted items</span></li>
                <li><strong><?= $avgRating ? number_format($avgRating, 1) . '★' : 'New' ?></strong><span>average rating</span></li>
                <li><strong><?= discount_label() ?></strong><span>member discount</span></li>
            </ul>
        </div>
        <div class="hero-art">
            <img src="assets/img/hero.svg" alt="A latte with latte art next to a slice of toast">
        </div>
    </div>
</section>

<?php if (!is_member() && can_shop()): ?>
<section class="container">
    <div class="promo-banner">
        <div>
            <h2>Members pay <?= discount_label() ?> less<?= DISCOUNT_COFFEE_ONLY ? ' on every coffee' : '' ?>.</h2>
            <p>Guests pay the regular price. Sign up in 30 seconds and the discount applies automatically at checkout.</p>
        </div>
        <a href="register.php" class="btn btn-light btn-lg">Create free account</a>
    </div>
</section>
<?php elseif (is_member()): ?>
<section class="container">
    <div class="promo-banner promo-member">
        <div>
            <h2>Welcome back, <?= e(explode(' ', current_user()['name'])[0]) ?> 👋</h2>
            <p>Your member discount of <?= discount_label() ?><?= DISCOUNT_COFFEE_ONLY ? ' on coffee' : '' ?> is active. Look for the green tags.</p>
        </div>
        <a href="menu.php?category=coffee" class="btn btn-light btn-lg">Browse coffee</a>
    </div>
</section>
<?php endif; ?>

<section class="section container">
    <div class="section-head">
        <div>
            <span class="eyebrow">Our menu</span>
            <h2>Something for every mood</h2>
        </div>
    </div>
    <div class="category-tiles">
        <?php
        $tileInfo = [
            'coffee' => ['Espresso, lattes and slow-steeped cold brew.', 'cappuccino.svg'],
            'non-coffee' => ['Matcha, chocolate and fruity iced teas.', 'matcha.svg'],
            'food' => ['Thick-cut toast and fresh bakes.', 'butter-toast.svg'],
        ];
        foreach (categories() as $c):
            [$blurb, $img] = $tileInfo[$c['slug']] ?? ['', null];
        ?>
            <a href="menu.php?category=<?= e($c['slug']) ?>" class="category-tile tile-<?= e($c['slug']) ?>">
                <div>
                    <h3><?= e($c['name']) ?></h3>
                    <p><?= e($blurb) ?></p>
                    <span class="tile-count"><?= (int)($counts[$c['id']] ?? 0) ?> items →</span>
                </div>
                <img src="<?= e(product_image($img)) ?>" alt="">
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container">
    <div class="section-head">
        <div>
            <span class="eyebrow">Customer favourites</span>
            <h2>Top rated this week</h2>
        </div>
        <a href="menu.php" class="link-arrow">See full menu →</a>
    </div>
    <div class="product-grid">
        <?php foreach ($featured as $p) { include 'includes/product_card.php'; } ?>
    </div>
</section>

<section class="section section-soft">
    <div class="container">
        <div class="section-head center">
            <div>
                <span class="eyebrow">How it works</span>
                <h2>Order in three easy steps</h2>
            </div>
        </div>
        <div class="steps">
            <div class="step"><span>1</span><h3>Pick your favourites</h3><p>Browse coffee, non-coffee drinks and toast. Add as many items as you like.</p></div>
            <div class="step"><span>2</span><h3>Check out</h3><p>Review your order with all prices and discounts shown before you confirm.</p></div>
            <div class="step"><span>3</span><h3>Pay at the counter</h3><p>A cashier picks up your order right away. Track its status from your account.</p></div>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
