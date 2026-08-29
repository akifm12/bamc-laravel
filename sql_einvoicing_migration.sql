-- E-Invoicing migration — run as: sudo -u postgres psql -d your_db_name -f sql_einvoicing_migration.sql

ALTER TABLE companies
    ADD COLUMN IF NOT EXISTS einvoicing_enabled   BOOLEAN      NOT NULL DEFAULT FALSE,
    ADD COLUMN IF NOT EXISTS einvoicing_provider  VARCHAR(50),
    ADD COLUMN IF NOT EXISTS einvoicing_api_key   TEXT,
    ADD COLUMN IF NOT EXISTS einvoicing_seller_id VARCHAR(255);

ALTER TABLE invoices
    ADD COLUMN IF NOT EXISTS einvoice_uuid         VARCHAR(255),
    ADD COLUMN IF NOT EXISTS einvoice_qr_code      TEXT,
    ADD COLUMN IF NOT EXISTS einvoice_status       VARCHAR(50),
    ADD COLUMN IF NOT EXISTS einvoice_submitted_at TIMESTAMP,
    ADD COLUMN IF NOT EXISTS einvoice_error        TEXT;
