# ERP upgrade guide

## Delivered integration

The existing Services dropdown now includes **Business Assistant**. It is a read-only decision-support screen with daily, weekly, and monthly summaries. It calculates revenue, gross profit from `sale_items`, expenses, cash received, outstanding credit, best sellers, slow-moving/dead-stock candidates, reorder alerts, customer insights, cashier performance, supplier activity, and repair workload where the relevant existing tables are available.

The assistant does not call write queries and cannot alter sales, stock, customers, or any other record.

## Database migration

Before applying schema changes, back up the `smoketech_inventory` database. Then apply:

`database/migrations/20260714_add_erp_expansion_foundation.sql`

It only creates new, additive tables: branches, warehouses, transfer headers/items, application settings, managed file metadata, and document sequences. Existing transactions remain backward compatible because no existing table is altered.

The migration prepares multi-branch operation; the current transaction forms must be updated in a later migration/release to add nullable `branch_id` fields and branch selection safely, one module at a time.

## Deployment

Keep the project directory name configurable before public deployment: current navigation uses `/smoketech_inventory/` paths. Ensure Apache/PHP 8.2+, MySQL/MariaDB, and writable upload/export directories are available. Do not expose database credentials or setup utilities publicly.

## Remaining enterprise modules

The requested upload manager, barcode/QR generation, printer/PDF service, imports, branch-aware transaction forms, settings UI, and complete document-number migration are separate cross-cutting modules. They require controlled migrations and end-to-end tests per existing form to preserve historical records and are intentionally not claimed as complete by the foundation migration.
