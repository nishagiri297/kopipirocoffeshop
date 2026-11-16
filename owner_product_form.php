<?php
require_once 'includes/functions.php';
require_role('owner');

$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$product = $editing ? find_product($id) : null;
if ($editing && !$product) {
    flash('error', 'Product not found.');
    redirect('owner_products.php');
}

$form = [
    'name' => $product['name'] ?? '',
    'category_id' => $product['category_id'] ?? '',
    'price' => isset($product['price']) ? (float)$product['price'] : '',
    'description' => $product['description'] ?? '',
    'is_available' => $product['is_available'] ?? 1,
];
$errors = [];
$cats = categories();

/**
 * Validates and saves an uploaded image. Returns the new file name, or null if none uploaded.
 * Only real JPG/PNG/WEBP/GIF images are accepted, saved under a random name.
 */
function save_upload(array $file, array &$errors): ?string
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors['image'] = 'Upload failed (error ' . $file['error'] . '). The file may be too large.';
        return null;
    }
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        $errors['image'] = 'Image must be smaller than ' . MAX_UPLOAD_MB . ' MB.';
        return null;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !isset($allowed[$info['mime']])) {
        $errors['image'] = 'Please upload a JPG, PNG, WEBP or GIF image.';
        return null;
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = 'product_' . bin2hex(random_bytes(8)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        $errors['image'] = 'Could not save the image. Check that the uploads/products folder is writable.';
        return null;
    }
    return $name;
}

if (is_post()) {
    verify_csrf();
    $form['name'] = trim($_POST['name'] ?? '');
    $form['category_id'] = (int)($_POST['category_id'] ?? 0);
    $form['price'] = trim($_POST['price'] ?? '');
    $form['description'] = trim($_POST['description'] ?? '');
    $form['is_available'] = isset($_POST['is_available']) ? 1 : 0;

    if ($form['name'] === '' || mb_strlen($form['name']) > 120) {
        $errors['name'] = 'Enter a product name (max 120 characters).';
    }
    if (!in_array($form['category_id'], array_map('intval', array_column($cats, 'id')), true)) {
        $errors['category_id'] = 'Choose a category.';
    }
    if (!is_numeric($form['price']) || $form['price'] <= 0) {
        $errors['price'] = 'Enter a price greater than 0.';
    }
    if (!$editing && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors['image'] = 'Please upload a product photo.';
    }

    $newImage = null;
    if (!$errors && isset($_FILES['image'])) {
        $newImage = save_upload($_FILES['image'], $errors);
    }

    if (!$errors) {
        if ($editing) {
            $image = $newImage ?? $product['image'];
            db()->prepare('UPDATE products SET name = ?, category_id = ?, price = ?, description = ?, is_available = ?, image = ? WHERE id = ?')
                ->execute([$form['name'], $form['category_id'], $form['price'], $form['description'], $form['is_available'], $image, $id]);
            if ($newImage && $product['image'] && is_file(UPLOAD_DIR . $product['image'])) {
                @unlink(UPLOAD_DIR . $product['image']);
            }
            flash('success', '"' . $form['name'] . '" was updated.');
        } else {
            db()->prepare('INSERT INTO products (name, category_id, price, description, is_available, image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$form['name'], $form['category_id'], $form['price'], $form['description'], $form['is_available'], $newImage, date('Y-m-d H:i:s')]);
            flash('success', '"' . $form['name'] . '" was added to the menu.');
        }
        redirect('owner_products.php');
    }
}

$pageTitle = $editing ? 'Edit product' : 'Add product';
$bodyClass = 'dashboard';
require 'includes/header.php';
?>

<section class="container section-tight narrow-wide">
    <a href="owner_products.php" class="link-arrow">← Back to products</a>
    <h1 class="page-title"><?= $editing ? 'Edit ' . e($product['name']) : 'Add a new product' ?></h1>

    <form method="post" enctype="multipart/form-data" class="product-form" novalidate>
        <?= csrf_field() ?>
        <div class="card">
            <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
                <label for="name">Product name</label>
                <input id="name" name="name" value="<?= e($form['name']) ?>" maxlength="120" required placeholder="e.g. Hazelnut Latte">
                <?php if (isset($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
            </div>
            <div class="form-row">
                <div class="field <?= isset($errors['category_id']) ? 'has-error' : '' ?>">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Choose…</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= (int)$c['id'] ?>" data-coffee="<?= (int)$c['is_coffee'] ?>" <?= (int)$form['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?><small class="error"><?= e($errors['category_id']) ?></small><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['price']) ? 'has-error' : '' ?>">
                    <label for="price">Price (<?= e(CURRENCY) ?>)</label>
                    <input id="price" name="price" type="number" min="0" step="<?= CURRENCY_DECIMALS ? '0.01' : '500' ?>" value="<?= e($form['price']) ?>" required>
                    <small class="muted" id="member-price-hint" data-rate="<?= MEMBER_DISCOUNT_RATE ?>" data-coffee-only="<?= DISCOUNT_COFFEE_ONLY ? 1 : 0 ?>" data-currency="<?= e(CURRENCY) ?>"></small>
                    <?php if (isset($errors['price'])): ?><small class="error"><?= e($errors['price']) ?></small><?php endif; ?>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Taste, ingredients, hot or iced…"><?= e($form['description']) ?></textarea>
            </div>
            <label class="checkbox"><input type="checkbox" name="is_available" value="1" <?= $form['is_available'] ? 'checked' : '' ?>> Available for ordering</label>
        </div>

        <div class="card">
            <label>Product photo</label>
            <label class="upload-drop <?= isset($errors['image']) ? 'has-error' : '' ?>" for="image">
                <img id="image-preview" src="<?= e($editing ? product_image($product['image']) : 'assets/img/placeholder.svg') ?>" alt="Preview">
                <span><strong>Click to choose a photo</strong> or drag it here<br><small class="muted">JPG, PNG, WEBP or GIF · max <?= MAX_UPLOAD_MB ?> MB</small></span>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
            </label>
            <?php if (isset($errors['image'])): ?><small class="error"><?= e($errors['image']) ?></small><?php endif; ?>
            <?php if ($editing): ?><small class="muted">Leave empty to keep the current photo.</small><?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="owner_products.php" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg"><?= $editing ? 'Save changes' : 'Add product' ?></button>
        </div>
    </form>
</section>

<?php require 'includes/footer.php'; ?>
