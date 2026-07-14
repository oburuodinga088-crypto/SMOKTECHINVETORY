-- Required by the current Expenses UI. Existing category and name data remains in
-- expense_category and expense_name; no data is altered or removed.
ALTER TABLE expenses
    ADD COLUMN expense_code VARCHAR(50) NULL AFTER id,
    ADD COLUMN vendor VARCHAR(150) NULL AFTER payment_method,
    ADD COLUMN payment_status ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Paid' AFTER reference_no,
    ADD UNIQUE KEY uq_expenses_expense_code (expense_code),
    ADD KEY idx_expenses_date_status (expense_date, payment_status);
