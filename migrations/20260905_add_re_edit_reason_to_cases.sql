-- Migration: Add re_edit_reason column to cases table
ALTER TABLE cases 
ADD COLUMN IF NOT EXISTS re_edit_reason TEXT NULL DEFAULT NULL AFTER amendment_notes;
