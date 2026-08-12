# Reathi Gallery Fabric POS

A Laravel 12, Blade, Alpine.js and Tailwind CSS application for a cloth/fabric shop. Laravel 12 keeps the project compatible with the PHP 8.2 runtime bundled in the installed XAMPP. The domain model keeps main and remnant inventory separate while storing every physical quantity internally in each product's base unit.

## Included workflows

- Separate Main POS and Remnant/Cut-Piece POS
- Decimal unit conversion with transaction-time rate snapshots
- Unit-specific selling prices (prices do not have to follow the conversion ratio)
- Received-purchase workflow with supplier invoice cost, extra cost allocation and weighted average landed cost
- Main-to-remnant transfer with equal transfer-out and transfer-in movement entries
- Individual remnant lots, whole/partial-piece rule, exact remnant barcode and below-cost loss reporting
- Split payments, customer dues, secure public invoice tokens, print/PDF invoices and optional TextIt.biz SMS
- Products, units, categories, customers, suppliers and expenses
- Immutable stock movement ledger, stock adjustment and negative-stock control
- Live dashboard, P&L split by inventory, stock valuation and no-sale/dead-stock reports
- Role-based gates for admin, manager, cashier and storekeeper responsibilities
- Encrypted gateway credentials and configurable inventory/POS/business rules

## Local XAMPP demo (already configured)

The checked-out workspace uses the running XAMPP MySQL service and the `reathi_gallery` database, which has already been migrated and seeded:

```bash
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

Sign in with `admin@reathi.test` / `password`. The seed creates three real catalogue items and opening-stock ledger movements so both POS and inventory screens can be explored.

## Fresh XAMPP / MySQL setup

Create a database named `reathi_gallery`, copy `.env.example` to `.env`, set the MySQL credentials, then run:

```bash
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
```

Point Apache to this project's `public` directory. The default `.env.example` assumes the project remains at `htdocs/Reathi Gallery`.

## Verification

```bash
php artisan test
```

The suite covers piece/dozen and metre/yard conversion, high-precision weighted average cost, frozen historical profit, remnant stock conservation, below-cost remnant loss, invoice-cost supplier due, checkout idempotency and all primary staff screens.

## Precision and safety

Quantity columns use `DECIMAL(18,6)`, conversions/costs use up to eight decimal places, and money uses `DECIMAL(18,4)`. Core calculations use `brick/math` decimal arithmetic rather than floating point. Inventory-changing services run in database transactions with locked balance rows. Completed financial records are not exposed to hard-delete routes.
