# SmokeTech Inventory System

A PHP and MySQL inventory management system for products, suppliers, purchases, point-of-sale sales, customers, and reports.

## Requirements

- XAMPP with Apache, MySQL, and PHP 8.2 or later
- A MySQL database named `smoketech_inventory`

## Installation

1. Copy the project to `C:\xampp\htdocs\smoketech_inventory`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Create the `smoketech_inventory` database and import the application's base schema from your database backup or deployment source. The base schema is not currently included in this repository.
4. Apply the incremental SQL files in `database/migrations/` in date order after the base schema is available.
5. Check the connection settings in `config/database.php`. The default local settings are:

   - Host: `localhost`
   - Database: `smoketech_inventory`
   - User: `root`
   - Password: empty

6. Open `http://localhost/smoketech_inventory/` in your browser.

## Notes

- The repository currently contains only incremental migrations; it does not include a base schema or sample-data file.
- Keep a backup of the initialized database before applying migrations in another environment.
- Once the administrator exists, use `login.php` to sign in and manage the system.

## First administrator

If the `users` table is empty, open:

`http://localhost/smoketech_inventory/create_admin.php`

Create an administrator account, then sign in at:

`http://localhost/smoketech_inventory/login.php`

The administrator setup page does not remove existing user accounts.

## Main features

- Category, product, supplier, and customer management
- Product stock and reorder-level tracking
- Purchase recording with automatic stock increases
- Point of Sale with transactional stock deduction
- Cash, M-Pesa, and credit payment options
- Sales and low-stock reports
- Role-protected user management for administrators
- Services workflow, including a read-only Business Assistant with daily, weekly, and monthly recommendations

## ERP expansion foundation

See [docs/ERP_UPGRADE_GUIDE.md](docs/ERP_UPGRADE_GUIDE.md) before applying the additive branch, warehouse, settings, file-metadata, and document-sequence migration. Back up the database first.

## Notes

- Add categories and suppliers before creating products where applicable.
- Use the Purchases page to add new stock after a product exists.
- Sales only allow quantities that are currently available in stock.
- Do not expose `create_admin.php` publicly after initial setup unless access is controlled.
