# ☕ Kopipiro — Coffee Ordering Web App

A coffee shop ordering website built with **HTML, CSS, JavaScript, PHP and MySQL** (no frameworks).
Customers browse coffee, non-coffee drinks and toast, add several items to a cart and check out.
**Logged-in members get 10% off coffee** and guests pay the normal price.
Cashiers see only the orders assigned to them. The owner manages products and sees all orders, sales charts and an Excel download.

---

## ✅ Requirements → where they are in the code

| Case study requirement | How it is implemented | File(s) |
|---|---|---|
| Admin can add products, upload images, set prices | Owner product list + add/edit form with photo upload (JPG/PNG/WEBP/GIF, max 2 MB, validated) | `owner_products.php`, `owner_product_form.php` |
| Registered + logged-in buyers get 10% discount | `unit_discount()` gives 10% off when the visitor is a logged-in **customer** | `includes/functions.php` |
| Guests pay the normal price | Guests are not members, so `unit_discount()` returns 0 | `includes/functions.php` |
| Shop sells coffee **and** other products | 3 categories: Coffee, Non-Coffee, Toast & Bakery (`is_coffee` flag per category) | `database.sql` |
| 1. Explore products with descriptions, ratings, prices | Menu with category tabs, search and sorting; product page with star ratings and reviews | `menu.php`, `product.php` |
| 2. Order multiple products | Session cart with quantities | `cart.php`, `cart_action.php` |
| 3. Checkout shows products with prices incl. discounts | Item table with original price struck through, member price, subtotal, discount and total | `checkout.php` |
| 4. Users: Owner, Customer, Cashier | `role` column on `users`; `require_role()` protects every page | `includes/functions.php` |
| 5. Cashiers only see their own orders | Orders are auto-assigned to the least busy cashier; cashier page filters `WHERE cashier_id = me` | `checkout.php`, `cashier.php` |
| 6. Owner sees all orders + sales charts + Excel download | Dashboard with filters, KPIs, 3 charts (Chart.js) and a real `.xlsx` export (SheetJS) plus CSV | `owner_dashboard.php`, `owner_export.php`, `assets/js/owner.js` |

> **Discount rule:** the case study says members get 10% off when they *order coffee*, so by default the discount applies to coffee only.
> To give 10% off **everything**, open `includes/config.php` and set `DISCOUNT_COFFEE_ONLY` to `false`.

**Extra features:** product ratings and reviews, order status tracking (pending → preparing → completed), "My Orders" history for members, staff account management, dine-in/takeaway with table number, responsive mobile design, CSRF protection, hashed passwords and prepared SQL statements.

---

## 🔑 Demo accounts

| Role | Email | Password |
|---|---|---|
| Owner | `owner@kopipiro.com` | `owner123` |
| Cashier | `cashier1@kopipiro.com` | `cashier123` |
| Cashier | `cashier2@kopipiro.com` | `cashier123` |
| Customer | `customer@kopipiro.com` | `customer123` |

The login page also has one-click buttons that fill these in.

---

## 🖥️ Part 1 — Run it on your computer (XAMPP)

1. **Install XAMPP** (PHP 8 or newer) from <https://www.apachefriends.org>.
2. **Start the servers:** open the *XAMPP Control Panel* and click **Start** next to **Apache** and **MySQL**.
3. **Copy the project:** put the `kopipiro` folder inside XAMPP's `htdocs` folder:
   - Windows: `C:\xampp\htdocs\kopipiro`
   - Mac: `/Applications/XAMPP/htdocs/kopipiro`
4. **Create the database:**
   1. Open <http://localhost/phpmyadmin>.
   2. Click the **Import** tab.
   3. Choose `kopipiro/database.sql` and click **Import** (or **Go**).
   4. A database called `kopipiro` appears with 6 tables and demo data.
5. **Open the site:** <http://localhost/kopipiro>

If you see *"Database connection failed"*, check that MySQL is running and that the user/password in `includes/config.php` match yours (XAMPP default is `root` with an empty password).

---

## 🐙 Part 2 — Upload the project to GitHub

### Option A: using Git commands (recommended)

1. Install Git from <https://git-scm.com> and create a free account on <https://github.com>.
2. On GitHub, click **New repository**, name it `kopipiro`, keep it **Public**, and **do not** tick "Add a README" (you already have one). Click **Create repository**.
3. Open a terminal (Windows: *Git Bash*) inside the project folder:
   ```bash
   cd C:/xampp/htdocs/kopipiro
   ```
4. Run these commands one by one (replace `YOUR-USERNAME`):
   ```bash
   git init
   git add .
   git commit -m "Kopipiro coffee ordering app"
   git branch -M main
   git remote add origin https://github.com/YOUR-USERNAME/kopipiro.git
   git push -u origin main
   ```
   The first push asks you to sign in to GitHub in your browser.
5. Refresh the GitHub page and all your files should be there, with this README shown on the front page.

**After you change code later:**
```bash
git add .
git commit -m "Describe what you changed"
git push
```

### Option B: without installing anything

On your new GitHub repository page, click **uploading an existing file**, drag in **everything inside** the `kopipiro` folder (including the `assets`, `includes` and `uploads` folders), then click **Commit changes**.

---

## 🌍 Part 3 — Put the website online (live demo link)

> ⚠️ **GitHub Pages cannot run PHP or MySQL.** It only serves plain HTML files, so your code lives on GitHub but the working site needs a PHP host.
> A free option that works well for student projects is **InfinityFree** (PHP + MySQL).

1. Sign up at <https://www.infinityfree.com> and create a hosting account with a free subdomain (e.g. `kopipiro.infinityfreeapp.com`).
2. In the control panel, go to **MySQL Databases** and create a database. Write down the **host**, **database name**, **username** and **password** it shows you.
3. Open **phpMyAdmin** from the control panel, select your new database, then **Import** `database.sql`.
   - Before importing, open `database.sql` in a text editor and **delete these two lines** (free hosts don't let you create databases from SQL):
     ```sql
     CREATE DATABASE IF NOT EXISTS kopipiro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     USE kopipiro;
     ```
4. Edit `includes/config.php` with the details from step 2:
   ```php
   define('DB_HOST', 'sqlXXX.infinityfree.com');
   define('DB_NAME', 'if0_12345678_kopipiro');
   define('DB_USER', 'if0_12345678');
   define('DB_PASS', 'your-password');
   ```
5. Open the **File Manager** (or use FileZilla), go into `htdocs`, and upload all the project files there.
6. Visit your subdomain. Add the live link to the top of this README so your lecturer can try it.

> 🔒 If your GitHub repo is public, don't commit your real hosting password. Keep the XAMPP defaults in the GitHub version and only put the real password in the copy on the host.

---

## 📁 Project structure

```
kopipiro/
├── index.php               Home page (hero, categories, top rated)
├── menu.php                Product catalog: filter, search, sort
├── product.php             Product details, ratings & reviews
├── cart.php                Cart page
├── cart_action.php         Add / update / remove cart items (AJAX + normal form)
├── checkout.php            Checkout with discounts, places the order
├── order_success.php       Order confirmation
├── my_orders.php           Customer order history
├── login.php / register.php / logout.php
├── cashier.php             Cashier: only orders assigned to them
├── owner_dashboard.php     Owner: all orders, filters, charts, Excel export
├── owner_export.php        Excel (JSON for SheetJS) and CSV export
├── owner_products.php      Owner: product list
├── owner_product_form.php  Owner: add / edit product with image upload
├── owner_users.php         Owner: staff accounts
├── database.sql            Tables + demo data
├── includes/
│   ├── config.php          Database + app settings  ← edit this
│   ├── db.php              PDO connection
│   ├── functions.php       Auth, roles, pricing/discount, cart helpers
│   ├── header.php / footer.php
│   ├── product_card.php    Reusable product card
│   ├── order_items.php     Reusable order item table
│   └── order_filters.php   Shared dashboard/export filters
├── assets/
│   ├── css/style.css       All styling (responsive)
│   ├── js/main.js          Cart AJAX, steppers, menus, image preview
│   ├── js/owner.js         Charts + Excel export
│   └── img/                Logo, hero, placeholder
└── uploads/products/       Product images (uploads go here)
```

## 🗄️ Database tables

- **users**: id, name, email, password (hashed), role (`owner` / `cashier` / `customer`), phone
- **categories**: id, name, slug, is_coffee
- **products**: id, category_id, name, description, price, image, is_available
- **ratings**: product_id, user_id, rating (1–5), comment, one per user per product
- **orders**: order_code, customer_id (NULL for guests), customer_name, cashier_id, order_type, table_no, subtotal, discount, total, status
- **order_items**: order_id, product_id, product_name, unit_price, unit_discount, quantity, line_total

Order items store the product name and price **at the time of the order**, so changing or deleting a product later never changes old receipts or sales reports.

## 🔒 Security measures

- Passwords hashed with `password_hash()` / `password_verify()`
- All SQL uses PDO **prepared statements** (no SQL injection)
- All output escaped with `htmlspecialchars()` (no XSS)
- CSRF token on every form
- Prices are always recalculated on the server, so a user can't change prices in the browser
- Image uploads are checked to be real images, given random names, and the uploads folder can't run scripts
- Every staff page checks the user's role

## ⚙️ Settings (`includes/config.php`)

| Setting | Default | Meaning |
|---|---|---|
| `MEMBER_DISCOUNT_RATE` | `0.10` | 10% member discount |
| `DISCOUNT_COFFEE_ONLY` | `true` | `false` = discount on all products |
| `CURRENCY` | `Rp` | e.g. change to `$` and set `CURRENCY_DECIMALS` to `2` |
| `APP_TIMEZONE` | `Asia/Jakarta` | Used for order dates and "today" stats |

## 🧰 Troubleshooting

- **Blank page or error:** XAMPP needs PHP 8+. Check the version in the XAMPP Control Panel.
- **Charts or Excel button not working:** they load Chart.js and SheetJS from the internet, so you need a connection. The **CSV** button always works offline.
- **Image upload fails:** make sure `uploads/products/` exists and is writable, and the file is under 2 MB.
- **Forgot a password:** re-import `database.sql` to reset all demo data.

---

Made for the Web Programming course — case study: *Kopipiro*.
