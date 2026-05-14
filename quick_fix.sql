USE pidevf;

-- Drop foreign key temporarily
ALTER TABLE demande_service DROP FOREIGN KEY FK_D16A217DC54C8C93;

-- Modify column
ALTER TABLE demande_service CHANGE type_id type_id BIGINT NOT NULL;

-- Re-add foreign key
ALTER TABLE demande_service ADD CONSTRAINT FK_D16A217DC54C8C93 FOREIGN KEY (type_id) REFERENCES type_service(id) ON DELETE CASCADE;

-- Add service_reaction fields
ALTER TABLE service_reaction ADD updated_at DATETIME DEFAULT NULL;
ALTER TABLE service_reaction ADD created_by BIGINT DEFAULT NULL;
ALTER TABLE service_reaction ADD updated_by BIGINT DEFAULT NULL;
ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCADE12AB56 FOREIGN KEY (created_by) REFERENCES users (id);
ALTER TABLE service_reaction ADD CONSTRAINT FK_5DA15CCA16FE72E1 FOREIGN KEY (updated_by) REFERENCES users (id);
CREATE INDEX IDX_5DA15CCADE12AB56 ON service_reaction (created_by);
CREATE INDEX IDX_5DA15CCA16FE72E1 ON service_reaction (updated_by);
