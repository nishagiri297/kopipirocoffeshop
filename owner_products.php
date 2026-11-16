<?php
require_once 'includes/functions.php';
require_role('owner');

if (is_post()) {
    verify_csrf();
    $id = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        db()->prepare('UPDATE products SET is_available = 1 - is_available WHERE id = ?')->execute([$id]);
        flash('success', 'Availability updated.');
    } elseif ($action === 'delete' && ($p = find_product($id))) {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        if ($p['image'] && is_file(UPLOAD_DIR . $p['image'])) {
            @unlink(UPLOAD_DIR . $p['image']);
        }
        flash('success', '"' . $p['name'] . '" was deleted. Past orders keep their item names.');
    }
    redirect('owner_products.php?' . ($_SERVER['QUERY_STRING'] ?? ''));
}

$category = $_GET['category'] ?? '';
$sql = product_select_sql();
$params = [];
if ($category !== '') {
    $sql .= ' WHERE c.slug = ?';
    $params[] = $category;
}
$stmt = db()->prepare($sql . ' ORDER BY c.id, p.name');
$stmt->execute($params);
$products = $stmt->fetchAll();

$sold = [];
foreach (db()->query("SELECT oi.product_id, SUM(oi.quantity) AS qty FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.status <> 'cancelled' GROUP BY oi.product_id") as $r) {
    $sold[$r['product_id']] = (int)$r['qty'];
}

$pageTitle = 'Products';
$bodyClass = 'dashboard';
require 'includes/header.php';
?>

<section class="container section-tight">
    <div class="page-head">
        <div>
            <span class="eyebrow">Owner · Catalog</span>
            <h1 class="page-title">Products</h1>
            <p class="muted">Add products, upload photos and set prices. Members automatically see <?= discount_label() ?> off<?= DISCOUNT_COFFEE_ONLY ? ' coffee' : '' ?>.</p>
        </div>
        <a href="owner_product_form.php" class="btn btn-primary">+ Add product</a>
    </div>

    <div class="pill-tabs">
        <a href="owner_products.php" class="<?= $category === '' ? 'active' : '' ?>">All</a>
        <?php foreach (categories() as $c): ?>
            <a href="owner_products.php?category=<?= e($c['slug']) ?>" class="<?= $category === $c['slug'] ? 'active' : '' ?>"><?= e($c['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="card table-card">
        <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Product</th><th>Category</th><th class="num">Price</th><th class="num">Member price</th><th>Rating</th><th class="num">Sold</th><th>Status</th><th class="num">Actions</th></tr></thead>
                <tbody>
                <?php if (!$products): ?><tr><td colspan="8" class="center muted">No products yet.</td></tr><?php endif; ?>
                <?php foreach ($products as $p):
                    $memberPrice = (!DISCOUNT_COFFEE_ONLY || $p['is_coffee']) ? $p['price'] - round($p['price'] * MEMBER_DISCOUNT_RATE) : null; ?>
                    <tr class="<?= $p['is_available'] ? '' : 'row-muted' ?>">
                        <td><div class="item-cell"><img src="<?= e(product_image($p['image'])) ?>" alt=""><div><strong><?= e($p['name']) ?></strong><br><small class="muted truncate"><?= e($p['description']) ?></small></div></div></td>
                        <td><span class="chip chip-static chip-<?= e($p['category_slug']) ?>"><?= e($p['category_name']) ?></span></td>
                        <td class="num"><?= money($p['price']) ?></td>
                        <td class="num"><?= $memberPrice !== null ? '<span class="text-green">' . money($memberPrice) . '</span>' : '<span class="muted">—</span>' ?></td>
                        <td><?= stars((float)$p['avg_rating']) ?> <small class="muted">(<?= (int)$p['rating_count'] ?>)</small></td>
                        <td class="num"><?= $sold[$p['id']] ?? 0 ?></td>
                        <td>
                            <form method="post" class="inline-form"><?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="toggle">
                                <button type="submit" class="switch <?= $p['is_available'] ? 'on' : '' ?>" title="Click to toggle"><span></span><?= $p['is_available'] ? 'Available' : 'Sold out' ?></button>
                            </form>
                        </td>
                        <td class="num nowrap">
                            <a href="owner_product_form.php?id=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                            <form method="post" class="inline-form" data-confirm="Delete <?= e($p['name']) ?>? This cannot be undone."><?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-text btn-sm danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
