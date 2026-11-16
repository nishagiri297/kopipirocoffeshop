<?php
require_once 'includes/functions.php';

/** Only allow redirects to pages inside this site. */
function safe_next(?string $next): ?string
{
    if (!$next || !preg_match('/^[a-z_]+\.php(\?[^\s]*)?(#\w*)?$/i', basename($next))) {
        return null;
    }
    return basename($next);
}

if (current_user()) {
    redirect(home_for_role(user_role()));
}

$email = '';
$error = '';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);

if (is_post()) {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        login_user($user);
        flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!' . ($user['role'] === 'customer' ? ' Your member discount is now active.' : ''));
        $dest = home_for_role($user['role']);
        if ($user['role'] === 'customer' && $next) {
            $dest = $next;
        }
        redirect($dest);
    }
    $error = 'Incorrect email or password.';
}

$pageTitle = 'Log in';
$bodyClass = 'auth-page';
require 'includes/header.php';
?>

<section class="auth-wrap container">
    <div class="auth-card card">
        <div class="auth-side">
            <img src="assets/img/hero.svg" alt="">
            <h2>Members save <?= discount_label() ?></h2>
            <p>Log in and the discount<?= DISCOUNT_COFFEE_ONLY ? ' on coffee' : '' ?> is applied automatically to your cart.</p>
        </div>
        <form method="post" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <h1>Log in</h1>
            <p class="muted">Customers, cashiers and the owner all log in here.</p>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="email">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="toggle-password" aria-label="Show password">Show</button>
                </div>
            </div>
            <button class="btn btn-primary btn-lg btn-block" type="submit">Log in</button>
            <p class="auth-switch">New here? <a href="register.php<?= $next ? '?next=' . urlencode($next) : '' ?>">Create an account</a></p>

            <div class="demo-accounts">
                <strong>Demo accounts</strong>
                <button type="button" data-demo="owner@kopipiro.com" data-pass="owner123">Owner</button>
                <button type="button" data-demo="cashier1@kopipiro.com" data-pass="cashier123">Cashier</button>
                <button type="button" data-demo="customer@kopipiro.com" data-pass="customer123">Customer</button>
            </div>
        </form>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
