<?php
require_once 'includes/functions.php';

if (current_user()) {
    redirect(home_for_role(user_role()));
}

$form = ['name' => '', 'email' => '', 'phone' => ''];
$errors = [];
$next = $_GET['next'] ?? $_POST['next'] ?? '';
$next = preg_match('/^[a-z_]+\.php$/i', $next) ? $next : '';

if (is_post()) {
    verify_csrf();
    $form['name'] = trim($_POST['name'] ?? '');
    $form['email'] = strtolower(trim($_POST['email'] ?? ''));
    $form['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 100) {
        $errors['name'] = 'Please enter your full name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        $stmt = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$form['email']]);
        if ($stmt->fetchColumn()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }
    if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $form['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if (strlen($password) < 6) {
        $errors['password'] = 'Use at least 6 characters.';
    } elseif ($password !== $confirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (!$errors) {
        // Public sign-up always creates a customer. Staff accounts are created by the owner.
        $stmt = db()->prepare('INSERT INTO users (name, email, password, role, phone, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$form['name'], $form['email'], password_hash($password, PASSWORD_DEFAULT), 'customer', $form['phone'] ?: null, date('Y-m-d H:i:s')]);
        login_user(['id' => db()->lastInsertId(), 'name' => $form['name'], 'email' => $form['email'], 'role' => 'customer', 'phone' => $form['phone']]);
        flash('success', 'Welcome to Kopipiro! Your ' . discount_label() . ' member discount is active.');
        redirect($next ?: (cart_count() ? 'cart.php' : 'menu.php'));
    }
}

$field = function (string $name, string $label, string $type = 'text', string $extra = '') use ($form, $errors) {
    $value = $type === 'password' ? '' : e($form[$name] ?? '');
    $err = isset($errors[$name]) ? '<small class="error">' . e($errors[$name]) . '</small>' : '';
    return '<div class="field' . ($err ? ' has-error' : '') . '"><label for="' . $name . '">' . $label . '</label>'
        . '<input type="' . $type . '" id="' . $name . '" name="' . $name . '" value="' . $value . '" ' . $extra . '>' . $err . '</div>';
};

$pageTitle = 'Create account';
$bodyClass = 'auth-page';
require 'includes/header.php';
?>

<section class="auth-wrap container">
    <div class="auth-card card">
        <div class="auth-side">
            <img src="assets/img/hero.svg" alt="">
            <h2>Why join?</h2>
            <ul class="check-list">
                <li><?= discount_label() ?> off<?= DISCOUNT_COFFEE_ONLY ? ' every coffee' : ' every order' ?></li>
                <li>Track your orders live</li>
                <li>Rate and review your favourites</li>
            </ul>
        </div>
        <form method="post" class="auth-form" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <h1>Create your account</h1>
            <p class="muted">It's free and takes 30 seconds.</p>
            <?= $field('name', 'Full name', 'text', 'required autocomplete="name"') ?>
            <?= $field('email', 'Email', 'email', 'required autocomplete="email"') ?>
            <?= $field('phone', 'Phone <span class="muted">(optional)</span>', 'tel', 'autocomplete="tel"') ?>
            <div class="form-row">
                <?= $field('password', 'Password', 'password', 'required minlength="6" autocomplete="new-password"') ?>
                <?= $field('password_confirm', 'Confirm password', 'password', 'required autocomplete="new-password"') ?>
            </div>
            <button class="btn btn-primary btn-lg btn-block" type="submit">Create account</button>
            <p class="auth-switch">Already a member? <a href="login.php">Log in</a></p>
        </form>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
