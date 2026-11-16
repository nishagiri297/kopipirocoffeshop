<?php
require_once 'includes/functions.php';
$me = require_role('owner');

$errors = [];
$form = ['name' => '', 'email' => '', 'role' => 'cashier'];

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $form['name'] = trim($_POST['name'] ?? '');
        $form['email'] = strtolower(trim($_POST['email'] ?? ''));
        $form['role'] = in_array($_POST['role'] ?? '', ['cashier', 'owner'], true) ? $_POST['role'] : 'cashier';
        $password = $_POST['password'] ?? '';

        if (mb_strlen($form['name']) < 2) { $errors['name'] = 'Enter a name.'; }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email.';
        } else {
            $s = db()->prepare('SELECT 1 FROM users WHERE email = ?');
            $s->execute([$form['email']]);
            if ($s->fetchColumn()) { $errors['email'] = 'This email is already used.'; }
        }
        if (strlen($password) < 6) { $errors['password'] = 'At least 6 characters.'; }

        if (!$errors) {
            db()->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)')
                ->execute([$form['name'], $form['email'], password_hash($password, PASSWORD_DEFAULT), $form['role'], date('Y-m-d H:i:s')]);
            flash('success', ucfirst($form['role']) . ' account created for ' . $form['name'] . '.');
            redirect('owner_users.php');
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id === $me['id']) {
            flash('error', 'You cannot delete your own account.');
        } else {
            $s = db()->prepare('SELECT name, role FROM users WHERE id = ?');
            $s->execute([$id]);
            if ($u = $s->fetch()) {
                db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                // Hand this cashier's open orders to whoever is free now
                if ($u['role'] === 'cashier') {
                    $open = db()->query("SELECT id FROM orders WHERE cashier_id IS NULL AND status IN ('pending','preparing')")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($open as $oid) {
                        db()->prepare('UPDATE orders SET cashier_id = ? WHERE id = ?')->execute([pick_cashier(), $oid]);
                    }
                }
                flash('success', $u['name'] . ' was removed.');
            }
        }
        redirect('owner_users.php');
    }
}

$users = db()->query("SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.cashier_id = u.id) AS handled,
        (SELECT COUNT(*) FROM orders o WHERE o.cashier_id = u.id AND o.status IN ('pending','preparing')) AS open_orders,
        (SELECT COUNT(*) FROM orders o WHERE o.customer_id = u.id) AS placed
    FROM users u ORDER BY CASE u.role WHEN 'owner' THEN 0 WHEN 'cashier' THEN 1 ELSE 2 END, u.name")->fetchAll();

$pageTitle = 'Staff & users';
$bodyClass = 'dashboard';
require 'includes/header.php';
?>

<section class="container section-tight">
    <div class="page-head">
        <div>
            <span class="eyebrow">Owner · People</span>
            <h1 class="page-title">Staff &amp; users</h1>
            <p class="muted">Customers sign up themselves. Create cashier accounts here; new orders are shared automatically between cashiers.</p>
        </div>
    </div>

    <div class="users-grid">
        <div class="card table-card">
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Role</th><th class="num">Orders</th><th>Joined</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><div class="item-cell"><span class="avatar"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span><div><strong><?= e($u['name']) ?></strong><br><small class="muted"><?= e($u['email']) ?></small></div></div></td>
                            <td><span class="role-tag role-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                            <td class="num">
                                <?php if ($u['role'] === 'cashier'): ?><?= (int)$u['handled'] ?> handled<br><small class="muted"><?= (int)$u['open_orders'] ?> open</small>
                                <?php elseif ($u['role'] === 'customer'): ?><?= (int)$u['placed'] ?> placed
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                            <td class="num">
                                <?php if ((int)$u['id'] !== $me['id']): ?>
                                <form method="post" class="inline-form" data-confirm="Remove <?= e($u['name']) ?>?"><?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button class="btn btn-text btn-sm danger" type="submit">Remove</button>
                                </form>
                                <?php else: ?><small class="muted">You</small><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="post" class="card" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <h2>Add staff account</h2>
            <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
                <label for="name">Name</label><input id="name" name="name" value="<?= e($form['name']) ?>">
                <?php if (isset($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
            </div>
            <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                <label for="email">Email</label><input id="email" name="email" type="email" value="<?= e($form['email']) ?>">
                <?php if (isset($errors['email'])): ?><small class="error"><?= e($errors['email']) ?></small><?php endif; ?>
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="cashier" <?= $form['role'] === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                    <option value="owner" <?= $form['role'] === 'owner' ? 'selected' : '' ?>>Owner</option>
                </select>
            </div>
            <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
                <label for="password">Temporary password</label><input id="password" name="password" type="text" autocomplete="off">
                <?php if (isset($errors['password'])): ?><small class="error"><?= e($errors['password']) ?></small><?php endif; ?>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Create account</button>
        </form>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
