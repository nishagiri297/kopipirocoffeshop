<?php
require_once 'includes/functions.php';
require_once 'includes/order_filters.php';
$user = require_role('owner');

// Owner can change status or reassign an order to another cashier
if (is_post()) {
    verify_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    if (isset($_POST['status']) && in_array($_POST['status'], ORDER_STATUSES, true)) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$_POST['status'], $orderId]);
        flash('success', 'Order status updated.');
    }
    if (isset($_POST['cashier_id'])) {
        $cid = (int)$_POST['cashier_id'];
        $ok = db()->prepare("SELECT 1 FROM users WHERE id = ? AND role = 'cashier'");
        $ok->execute([$cid]);
        if ($ok->fetchColumn()) {
            db()->prepare('UPDATE orders SET cashier_id = ? WHERE id = ?')->execute([$cid, $orderId]);
            flash('success', 'Order reassigned.');
        }
    }
    redirect('owner_dashboard.php?' . $_SERVER['QUERY_STRING']);
}

$f = order_filters();
$orders = filtered_orders($f, 300);
$itemsByOrder = items_for_orders(array_column($orders, 'id'));
$cashiers = db()->query("SELECT id, name FROM users WHERE role = 'cashier' ORDER BY name")->fetchAll();

// ---- KPIs (cancelled orders don't count as sales) ----
$stmt = db()->prepare("SELECT COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue, COALESCE(SUM(discount),0) AS discounts,
                              SUM(CASE WHEN customer_id IS NOT NULL THEN 1 ELSE 0 END) AS member_orders
                       FROM orders o WHERE {$f['where']} AND o.status <> 'cancelled'");
$stmt->execute($f['params']);
$kpi = $stmt->fetch();
$avgOrder = $kpi['orders'] ? $kpi['revenue'] / $kpi['orders'] : 0;

// ---- Chart: revenue per day (every day in range, even with zero sales) ----
$stmt = db()->prepare("SELECT DATE(o.created_at) AS day, SUM(o.total) AS revenue, COUNT(*) AS orders
                       FROM orders o WHERE {$f['where']} AND o.status <> 'cancelled' GROUP BY DATE(o.created_at)");
$stmt->execute($f['params']);
$byDay = [];
foreach ($stmt->fetchAll() as $r) { $byDay[$r['day']] = $r; }
$daily = ['labels' => [], 'revenue' => [], 'orders' => []];
for ($d = strtotime($f['from']); $d <= strtotime($f['to']); $d = strtotime('+1 day', $d)) {
    $key = date('Y-m-d', $d);
    $daily['labels'][] = date('d M', $d);
    $daily['revenue'][] = (float)($byDay[$key]['revenue'] ?? 0);
    $daily['orders'][] = (int)($byDay[$key]['orders'] ?? 0);
}

// ---- Chart: top products and sales by category ----
$stmt = db()->prepare("SELECT oi.product_name, SUM(oi.quantity) AS qty, SUM(oi.line_total) AS revenue,
                              COALESCE(c.name, 'Deleted products') AS category
                       FROM order_items oi
                       JOIN orders o ON o.id = oi.order_id
                       LEFT JOIN products p ON p.id = oi.product_id
                       LEFT JOIN categories c ON c.id = p.category_id
                       WHERE {$f['where']} AND o.status <> 'cancelled'
                       GROUP BY oi.product_name, c.name ORDER BY qty DESC");
$stmt->execute($f['params']);
$productSales = $stmt->fetchAll();
$top = array_slice($productSales, 0, 7);
$byCategory = [];
foreach ($productSales as $r) {
    $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + (float)$r['revenue'];
}

$chartData = [
    'currency' => CURRENCY,
    'daily' => $daily,
    'top' => ['labels' => array_column($top, 'product_name'), 'qty' => array_map('intval', array_column($top, 'qty'))],
    'category' => ['labels' => array_keys($byCategory), 'revenue' => array_values($byCategory)],
];

$query = http_build_query(['from' => $f['from'], 'to' => $f['to'], 'status' => $f['status'], 'cashier' => $f['cashier'] ?: '']);

$pageTitle = 'Owner dashboard';
$bodyClass = 'dashboard';
$pageScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
    'https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js',
    'assets/js/owner.js',
];
require 'includes/header.php';
?>

<section class="container section-tight">
    <div class="page-head">
        <div>
            <span class="eyebrow">Owner dashboard</span>
            <h1 class="page-title">Sales &amp; all orders</h1>
            <p class="muted"><?= e(date('d M Y', strtotime($f['from']))) ?> – <?= e(date('d M Y', strtotime($f['to']))) ?></p>
        </div>
        <div class="head-actions">
            <button type="button" class="btn btn-primary" id="export-excel" data-src="owner_export.php?<?= e($query) ?>&amp;format=json">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 19h16"/></svg>
                Download Excel
            </button>
            <a href="owner_export.php?<?= e($query) ?>&amp;format=csv" class="btn btn-ghost" title="Plain CSV - also opens in Excel">CSV</a>
        </div>
    </div>

    <form class="filter-bar card" method="get">
        <div class="field"><label for="from">From</label><input type="date" id="from" name="from" value="<?= e($f['from']) ?>"></div>
        <div class="field"><label for="to">To</label><input type="date" id="to" name="to" value="<?= e($f['to']) ?>"></div>
        <div class="field"><label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label for="cashier">Cashier</label>
            <select id="cashier" name="cashier">
                <option value="">All cashiers</option>
                <?php foreach ($cashiers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $f['cashier'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="filter-buttons">
            <button class="btn btn-primary" type="submit">Apply</button>
            <a href="owner_dashboard.php" class="btn btn-text">Reset</a>
        </div>
        <div class="quick-ranges">
            <a href="?from=<?= date('Y-m-d') ?>&to=<?= date('Y-m-d') ?>">Today</a>
            <a href="?from=<?= date('Y-m-d', strtotime('-6 days')) ?>&to=<?= date('Y-m-d') ?>">7 days</a>
            <a href="?from=<?= date('Y-m-d', strtotime('-29 days')) ?>&to=<?= date('Y-m-d') ?>">30 days</a>
            <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>">This month</a>
        </div>
    </form>

    <div class="kpi-grid">
        <div class="kpi kpi-accent"><small>Revenue</small><strong><?= money($kpi['revenue']) ?></strong><span>excl. cancelled orders</span></div>
        <div class="kpi"><small>Orders</small><strong><?= (int)$kpi['orders'] ?></strong><span><?= (int)$kpi['member_orders'] ?> by members</span></div>
        <div class="kpi"><small>Average order</small><strong><?= money($avgOrder) ?></strong><span>per order</span></div>
        <div class="kpi"><small>Member discounts given</small><strong><?= money($kpi['discounts']) ?></strong><span><?= discount_label() ?> member price</span></div>
    </div>

    <div class="chart-grid">
        <div class="card chart-card wide">
            <div class="chart-head"><h2>Daily sales</h2><span class="muted">Revenue and number of orders</span></div>
            <div class="chart-box"><canvas id="chart-daily"></canvas></div>
        </div>
        <div class="card chart-card">
            <div class="chart-head"><h2>Best sellers</h2><span class="muted">Units sold</span></div>
            <div class="chart-box"><canvas id="chart-top"></canvas></div>
        </div>
        <div class="card chart-card">
            <div class="chart-head"><h2>Sales by category</h2><span class="muted">Revenue share</span></div>
            <div class="chart-box"><canvas id="chart-category"></canvas></div>
        </div>
    </div>
    <script type="application/json" id="chart-data"><?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

    <div class="card table-card">
        <div class="chart-head">
            <h2>All orders</h2>
            <span class="muted"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?><?= count($orders) === 300 ? ' (showing latest 300 — export for all)' : '' ?></span>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                <tr><th>Order</th><th>Date</th><th>Customer</th><th>Items</th><th class="num">Discount</th><th class="num">Total</th><th>Cashier</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if (!$orders): ?><tr><td colspan="8" class="center muted">No orders match these filters.</td></tr><?php endif; ?>
                <?php foreach ($orders as $o): $items = $itemsByOrder[$o['id']] ?? []; ?>
                    <tr>
                        <td><button type="button" class="link-btn" data-toggle-row="row-<?= (int)$o['id'] ?>"><?= e($o['order_code']) ?> ▾</button></td>
                        <td><?= e(date('d M Y H:i', strtotime($o['created_at']))) ?></td>
                        <td><?= e($o['customer_name']) ?><br><small class="muted"><?= $o['customer_id'] ? 'Member' : 'Guest' ?> · <?= e(ucfirst($o['order_type'])) ?></small></td>
                        <td><?= array_sum(array_column($items, 'quantity')) ?></td>
                        <td class="num"><?= $o['discount'] > 0 ? '− ' . money($o['discount']) : '—' ?></td>
                        <td class="num"><strong><?= money($o['total']) ?></strong></td>
                        <td>
                            <form method="post" class="inline-form"><?= csrf_field() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                <select name="cashier_id" class="auto-submit compact-select" aria-label="Assigned cashier">
                                    <?php if (!$o['cashier_id']): ?><option value="">Unassigned</option><?php endif; ?>
                                    <?php foreach ($cashiers as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $o['cashier_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td>
                            <form method="post" class="inline-form"><?= csrf_field() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                <select name="status" class="auto-submit compact-select status-select status-<?= e($o['status']) ?>" aria-label="Status">
                                    <?php foreach (ORDER_STATUSES as $s): ?><option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <tr id="row-<?= (int)$o['id'] ?>" class="detail-row" hidden>
                        <td colspan="8"><?php include 'includes/order_items.php'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
