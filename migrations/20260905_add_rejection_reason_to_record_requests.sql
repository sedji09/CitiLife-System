-- Migration: Add rejection_reason column to record_requests table
ALTER TABLE record_requests 
ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL DEFAULT NULL AFTER status;
