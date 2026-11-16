<?php
require_once 'includes/functions.php';

$category = $_GET['category'] ?? '';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'popular';

$where = ['1 = 1'];
$params = [];
if ($category !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $category;
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$orderBy = match ($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name' => 'p.name ASC',
    default => 'avg_rating DESC, rating_count DESC',
};

$stmt = db()->prepare(product_select_sql() . ' WHERE ' . implode(' AND ', $where) . " ORDER BY p.is_available DESC, $orderBy");
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Menu';
require 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Kopipiro menu</span>
        <h1>Explore our menu</h1>
        <p class="lead">
            <?php if (is_member()): ?>
                Member prices are shown. You're saving <?= discount_label() ?><?= DISCOUNT_COFFEE_ONLY ? ' on every coffee' : '' ?>.
            <?php elseif (can_shop()): ?>
                Prices shown are regular prices. <a href="login.php">Log in</a> or <a href="register.php">sign up</a> to get <?= discount_label() ?> off<?= DISCOUNT_COFFEE_ONLY ? ' coffee' : '' ?>.
            <?php else: ?>
                You are viewing the shop as staff.
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="container section-tight">
    <form class="menu-toolbar" method="get">
        <div class="pill-tabs" role="tablist">
            <a href="menu.php?<?= e(http_build_query(['q' => $search, 'sort' => $sort])) ?>" class="<?= $category === '' ? 'active' : '' ?>">All</a>
            <?php foreach (categories() as $c): ?>
                <a href="menu.php?<?= e(http_build_query(['category' => $c['slug'], 'q' => $search, 'sort' => $sort])) ?>"
                   class="<?= $category === $c['slug'] ? 'active' : '' ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="toolbar-right">
            <input type="hidden" name="category" value="<?= e($category) ?>">
            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search drinks or toast…">
            </div>
            <select name="sort" class="auto-submit" aria-label="Sort by">
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Top rated</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
            </select>
        </div>
    </form>

    <?php if (!$products): ?>
        <div class="empty-state">
            <img src="assets/img/placeholder.svg" alt="" width="120">
            <h3>No products found</h3>
            <p>Try another search or category.</p>
            <a href="menu.php" class="btn btn-primary">Show all products</a>
        </div>
    <?php else: ?>
        <p class="muted result-count"><?= count($products) ?> item<?= count($products) === 1 ? '' : 's' ?></p>
        <div class="product-grid">
            <?php foreach ($products as $p) { include 'includes/product_card.php'; } ?>
        </div>
    <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>
