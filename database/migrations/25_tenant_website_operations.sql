-- Central website operations metadata for ASENA-owned provisioning and lifecycle control.
ALTER TABLE tenant_sites
    ADD COLUMN lifecycle_status VARCHAR(32) NOT NULL DEFAULT 'published',
    ADD COLUMN provisioned_by_user_id INT NULL,
    ADD COLUMN provisioning_source VARCHAR(32) NOT NULL DEFAULT 'customer_order',
    ADD COLUMN payment_required TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN approved_by_user_id INT NULL,
    ADD COLUMN approved_at DATETIME NULL,
    ADD COLUMN published_by_user_id INT NULL,
    ADD COLUMN published_at DATETIME NULL,
    ADD COLUMN admin_notes TEXT NULL,
    ADD INDEX idx_tenant_sites_lifecycle (lifecycle_status),
    ADD INDEX idx_tenant_sites_source (provisioning_source);

ALTER TABLE website_orders
    ADD COLUMN reviewed_by_user_id INT NULL,
    ADD COLUMN reviewed_at DATETIME NULL,
    ADD COLUMN admin_notes TEXT NULL,
    ADD INDEX idx_website_orders_reviewed (reviewed_at);
