# Warehouse Management System (WMS)

A web-based system for managing the IT department's warehouse and lab equipment at **Damascus Intermediate Institute**: stock tracking, material requests from trainers, annual stock-take, maintenance, audit trail and reports.

Built with **Laravel 12**, Blade, Alpine.js and Tailwind CSS. The interface is Arabic (RTL).

> 🎓 Academic project, under active development. See [What works today](#-what-works-today) and the [Roadmap](#-roadmap) for the honest status.

## ✨ What works today

| Area | Capabilities |
|---|---|
| **Authentication** | Login/logout, password reset, profile page. Public registration is **disabled** — accounts are created by staff. Deactivated accounts cannot sign in. |
| **Roles & permissions** | Three roles (Spatie Permission): *Super Admin* (department head), *Admin* (warehouse keeper), *Trainer*. Enforced on the server, and links are hidden in the UI per role. |
| **Dashboard** | Live statistics: stock levels (total / low / out of stock / damaged / most used), order counters, top lab and top trainer. |
| **Items** | Full management for staff: add, edit, delete, and **restock** (receive goods). Search by name/barcode, filter by category or status (low stock, missing, damaged, most used), export the filtered list to **Excel** / **PDF**. Balances are never edited by hand — they change through orders, inventory and restocking, so every change has an audit trail. Items with history (orders, inventory, maintenance) cannot be deleted. |
| **Categories & storage locations** | Managed from *Settings* (section / cabinet / shelf). A category or location in use cannot be deleted. |
| **Orders** | Trainers create material requests with item lines. Staff approve or reject with mandatory notes. **Approval checks and deducts stock** atomically. Trainers only see their own orders. Search and status filters. |
| **Annual inventory** | Start a session (snapshots current stock) → record counted quantities → head resolves differences (stock is adjusted) → head approves and closes (balances carry forward as the next opening balance). |
| **Maintenance** | Report a fault, then committee decisions: send to repair, replace, scrap, mark fixed (with cost). Workflow rules prevent invalid transitions. |
| **Audit log** | Searchable timeline of orders, inventory, users, maintenance and settings actions. |
| **Reports** | Six reports (inventory, orders, annual inventory, damaged items, maintenance, trainer custody) with a date range, on-screen preview, and **PDF / Excel** export. |
| **Settings** | Low-stock threshold (applied across the app), default loan duration, system name, category management. |

### Roles at a glance

| | Super Admin | Admin (warehouse keeper) | Trainer |
|---|:---:|:---:|:---:|
| Dashboard, view items, export | ✅ | ✅ | ✅ |
| Add / edit / delete / restock items | ✅ | ✅ | ❌ |
| Create orders / see own orders | ✅ | ✅ | ✅ (own only) |
| Approve / reject orders | ✅ | ✅ | ❌ |
| Start inventory, enter counts | ✅ | ✅ | ❌ |
| Resolve differences, approve inventory | ✅ | ❌ | ❌ |
| Maintenance, reports, audit log, settings | ✅ | ✅ | ❌ |
| Manage users | all users and roles | trainers only | ❌ |

## 🛠️ Tech stack

Laravel 12 · PHP 8.2+ · SQLite (default) or MySQL · Blade · Alpine.js · Tailwind CSS (Vite) · [spatie/laravel-permission](https://github.com/spatie/laravel-permission) · [maatwebsite/excel](https://github.com/SpartnerNL/Laravel-Excel) · [mPDF](https://mpdf.github.io/) (Arabic PDF support)

All front-end assets are bundled locally by Vite — the app works without internet access.

## 🚀 Getting started

**Requirements:** PHP 8.2+ with `mbstring`, `gd`, `zip`, `xml` · Composer · Node.js 20.19+ (or 22.12+)

```bash
git clone https://github.com/israamunawwar/WMS-Backend.git
cd WMS-Backend

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

touch database/database.sqlite        # SQLite (default). For MySQL, edit the DB_* values in .env instead.
php artisan migrate --seed
php artisan serve
```

Open http://localhost:8000.

### Demo accounts

Created by the seeder, for **local development only** (change or remove them before any real deployment):

| Email | Password | Role |
|---|---|---|
| head@it.edu | password123 | Super Admin |
| warehouse@it.edu | password123 | Admin (warehouse keeper) |
| trainer@it.edu | password123 | Trainer |

The seeder also creates sample categories, items, orders, maintenance records and settings, and is safe to run more than once.

### Running the tests

```bash
php artisan test
```

The suite covers access control per role, item management, the inventory workflow, orders and stock deduction, maintenance, reports and exports.

### Development

```bash
npm run dev          # Vite dev server with hot reload
```

## 🧭 Roadmap

Honest list of what is **not** done yet:

- [ ] Equipment kits (tables exist, no UI)
- [ ] Lab records (table exists; orders currently use a free-text destination)
- [ ] In-app notifications (table exists, no UI)
- [ ] Barcode scanning (items have a barcode field; no scanner flow)
- [ ] REST API for a mobile client
- [ ] Email verification (disabled by design for now)

## 👩‍💻 Author

**Israa Munawwar** — [GitHub](https://github.com/israamunawwar)
