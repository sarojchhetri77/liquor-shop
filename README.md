# 🍷 Nectar — Liquor Store

A full-featured online liquor store (wines, spirits & beer) built with **Laravel 13**, **Inertia.js v3** and **Vue 3**. It has a polished, editorial storefront and a role-based admin panel with an analytics dashboard.

- **Storefront** — browse by category, filter/search products, product detail with image gallery, star-ratings & reviews, cart, and **Cash-on-Delivery** checkout.
- **Admin panel** — dashboard with revenue/orders charts, product & category management (multi-image upload, discounts), order management, promotions popup, and staff management.
- **Roles** — `admin`, `staff`, and `customer`, gated by middleware.
- **Architecture** — all business logic lives in a **service layer** (`app/Services`); controllers stay thin.

---

## Tech stack

| Layer | Tech |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13, Laravel Fortify (auth) |
| Frontend | Inertia.js v3, Vue 3, TypeScript, Tailwind CSS v4 |
| Database | SQLite (default) — swappable for MySQL/PostgreSQL |
| Tooling | Vite, Wayfinder, Pest (tests), Pint (formatting), ESLint + Prettier |

---

## Requirements

- **PHP** 8.3 or higher (with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` extensions)
- **MySQL** 8.0 or higher (or MariaDB 10.6+)
- **Composer** 2.x
- **Node.js** 20+ and **npm**
- *(optional)* [Laravel Herd](https://herd.laravel.com/) — serves the app automatically at `http://nectar.test` / `http://ecommerce.test`

---

## Getting started

### 1. Clone & install dependencies

```bash
git clone https://github.com/sarojchhetri77/liquor-shop.git
cd liquor-shop

composer install
npm install
```

### 2. Environment

```bash
# Copy the example env and generate an app key
cp .env.example .env
php artisan key:generate
```

The project uses **MySQL**. Create the database, then point the `DB_*` values in
`.env` at it:

```bash
mysql -u root -p -e "CREATE DATABASE \`liquor-shop\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=liquor-shop
DB_USERNAME=root
DB_PASSWORD=your-password
```

### 3. Migrate & seed

This creates all tables and seeds a demo catalog (categories, products with real
imagery, ~60 sample orders for the dashboard charts, and a promo popup):

```bash
php artisan migrate --seed
```

### 4. Link storage

Product/category/promotion image uploads are served from the public disk:

```bash
php artisan storage:link
```

### 5. Run the app

**Option A — one command (recommended):** runs the PHP server, queue, and Vite together:

```bash
composer run dev
```

**Option B — separately:**

```bash
# Terminal 1 — backend
php artisan serve

# Terminal 2 — frontend
npm run dev
```

**Option C — Laravel Herd:** just open the site URL (e.g. `http://nectar.test`) and run `npm run dev` for hot reloading.

Then visit **http://localhost:8000** (or your Herd URL).

---

## Demo accounts

After seeding, log in with any of these (password is `password` for all):

| Role | Email | Access |
| --- | --- | --- |
| Admin | `admin@example.com` | Full admin panel incl. staff management |
| Staff | `staff@example.com` | Admin panel (no staff management) |
| Customer | `customer@example.com` | Storefront, cart, orders, reviews |

- Storefront: `/`
- Admin panel: `/admin`

New customers can self-register at `/register` (name, email, contact, date of birth).

---

## Useful commands

```bash
# Run the test suite (Pest)
php artisan test

# Format PHP (Pint) and JS/Vue (Prettier)
vendor/bin/pint
npm run format

# Lint & type-check the frontend
npm run lint
npm run types:check

# Production build
npm run build

# Re-seed a fresh database
php artisan migrate:fresh --seed
```

---

## Project structure

```
app/
├── Enums/                 # UserRole, OrderStatus
├── Http/
│   ├── Controllers/
│   │   ├── Admin/         # Dashboard, Product, Category, Order, Promotion, Staff
│   │   └── Shop/          # Home, Product, Cart, Checkout, Order, Review
│   ├── Middleware/        # EnsureUserHasRole, HandleInertiaRequests
│   └── Requests/          # Form request validation
├── Models/                # Product, Category, Order, Review, Promotion, ...
└── Services/              # ★ Business logic (Product/Cart/Order/Review/... services)

resources/js/
├── components/shop/       # ProductCard, BottleThumb, PromoModal, charts helpers
├── layouts/               # ShopLayout, AdminLayout
├── pages/
│   ├── admin/             # Dashboard + management pages
│   └── shop/              # Storefront pages
└── directives/            # v-reveal (scroll animations)
```

---

## Notes

- You must be **21+** to purchase — the storefront reflects responsible-drinking messaging.
- Currency is displayed in **NPR (Rs.)**; adjust in `resources/js/lib/format.ts`.
- Payment is **Cash / Card on Delivery** — no payment gateway is integrated.

---

Built with ❤️ using Laravel, Inertia & Vue.
