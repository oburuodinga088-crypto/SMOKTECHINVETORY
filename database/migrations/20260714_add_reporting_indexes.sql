-- Query-supporting indexes for existing dashboard, report, cash-book and audit screens.
ALTER TABLE sales
    ADD KEY idx_sales_sale_date (sale_date),
    ADD KEY idx_sales_payment_balance (payment_status, balance);

ALTER TABLE purchases
    ADD KEY idx_purchases_date_status (purchase_date, payment_status);

ALTER TABLE cash_book_entries
    ADD KEY idx_cash_book_date_type (entry_date, entry_type);

ALTER TABLE system_audit_log
    ADD KEY idx_system_audit_created_at (created_at);
