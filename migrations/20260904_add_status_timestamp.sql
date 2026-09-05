-- Migration: Add status_timestamp to cases table
-- Date: 2026-09-04

ALTER TABLE cases ADD COLUMN status_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
