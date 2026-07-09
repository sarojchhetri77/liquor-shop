---
name: ecommerce-architecture
description: Core architecture and conventions for this Laravel e-commerce app (roles, service layer, routes)
metadata:
  type: project
---

This app is a Laravel + Inertia v3 + Vue 3 e-commerce platform built on the Laravel Vue starter kit.

**Business logic lives in `app/Services/`** (CategoryService, ProductService, CartService, OrderService, ReviewService, StaffService). Controllers stay thin and only orchestrate. Keep new domain logic in services, not controllers — this is a hard requirement from the user.

**Roles**: `users.role` is a `UserRole` enum column (admin, staff, customer). Gated by the `role` middleware alias (`EnsureUserHasRole`), e.g. `->middleware('role:admin,staff')`. Staff management is admin-only (`role:admin`). Customers register via Fortify with extra `contact` + `dob` fields (see `CreateNewUser`).

**Routing**: storefront under `/` (`shop.*` names), admin under `/admin` (`admin.*` names). `/dashboard` smart-redirects by role (staff→admin, customer→orders). Product update uses POST (not PUT) so multipart image uploads work.

**Frontend**: admin/shop pages return `null` from the app.ts layout resolver and each page wraps its own `AdminLayout`/`ShopLayout` (so they can pass a per-page title). Toasts use `$this->toast()` on the base Controller (wraps `Inertia::flash('toast', ...)`), surfaced by `resources/js/lib/flashToast.ts`.

Products have many images (`product_images` table). Discount is `discount_percent` on products; `final_price` is a computed accessor (appended). Orders are Cash-on-Delivery. Seed login: admin@example.com / staff@example.com / customer@example.com, all password `password`.

**Branding**: this is a **liquor store called "Cheers"** (Nepal market — currency is `Rs.` / NPR via `resources/js/lib/format.ts`; top info bar shows phone/delivery hours/COD; 21+ age note). Theme is forced **light** (warm bordeaux/ivory/gold palette in `app.css`; appearance defaults to `light` in middleware, blade, and `useAppearance`). Products/categories with no uploaded photo render `components/shop/BottleThumb.vue` (gradient + drink icon keyed by category name).

**Promotions popup**: `promotions` table + `PromotionService` + admin CRUD (`/admin/promotions`, image upload). `HomeController` passes the active promotion (`PromotionService::activePopup()`) to the homepage, where `components/shop/PromoModal.vue` shows it once per browser session (sessionStorage key `promo_seen_{id}`). Admins/staff add promos from the panel.
