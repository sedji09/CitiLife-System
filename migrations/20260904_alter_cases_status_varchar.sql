-- alter_cases_status_varchar.sql
-- Change cases.status from ENUM to VARCHAR(50) to support error tracking & revision statuses
ALTER TABLE cases MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Pending';
