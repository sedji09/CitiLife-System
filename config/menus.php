<?php
/**
 * menus.php
 * Hardcoded menu structure for all roles, now with perm_key for RBAC.
 */

return [
  "radtech" => [
    ["label" => "Dashboard", "icon" => "layout-dashboard", "href" => "/dashboard", "perm_key" => "dashboard"],
    ["label" => "Patient Registration", "icon" => "user-plus", "href" => "/patient-registration", "perm_key" => "patient_reg"],
    ["label" => "Patient Queue (Today)", "icon" => "clipboard-list", "href" => "/patient-lists", "perm_key" => "worklist"],
    ["label" => "X-ray Records", "icon" => "folder-open", "href" => "/xray-patient-records", "perm_key" => "patient_history"],
    ["label" => "Record Request", "icon" => "send", "href" => "/record-request", "perm_key" => "record_requests"],
  ],

  "radiologist" => [
    ["label" => "Dashboard", "icon" => "layout-dashboard", "href" => "/dashboard", "perm_key" => "dashboard"],
    ["label" => "Worklist", "icon" => "clipboard-list", "href" => "/worklist", "perm_key" => "worklist"],
    ["label" => "Patient History", "icon" => "folder-open", "href" => "/patient-history", "perm_key" => "patient_history"],
  ],

  "admin_central" => [
    ["label" => "Dashboard", "icon" => "layout-dashboard", "href" => "/dashboard", "perm_key" => "dashboard"],
    ["label" => "Branches", "icon" => "building-2", "href" => "/branches", "perm_key" => "branch_mgmt"],
    ["label" => "Users", "icon" => "users", "href" => "/users", "perm_key" => "user_mgmt"],
    ["label" => "Patient Records", "icon" => "folder-open", "href" => "/patient-records", "perm_key" => "patient_history"],
    ["label" => "Patient Feedback", "icon" => "message-square", "href" => "/feedback", "perm_key" => "global_reports"],
    ["label" => "Reports", "icon" => "bar-chart-3", "href" => "/reports", "perm_key" => "global_reports"],
    ["label" => "Audit Logs", "icon" => "file-search", "href" => "/audit-logs", "perm_key" => "audit_logs"],
    ["label" => "System Settings", "icon" => "settings", "href" => "/settings", "perm_key" => "system_security"],
  ],

  "branch_admin" => [
    ["label" => "Dashboard", "icon" => "layout-dashboard", "href" => "/dashboard", "perm_key" => "dashboard"],
    ["label" => "Record Requests", "icon" => "folder-sync", "href" => "/record-requests", "perm_key" => "record_requests"],
    ["label" => "Branch X-ray Cases", "icon" => "folder-open", "href" => "/branch-xray-cases", "perm_key" => "patient_history"],
    ["label" => "Patient Feedback", "icon" => "message-square", "href" => "/feedback", "perm_key" => "global_reports"],
    ["label" => "Audit Logs", "icon" => "file-search", "href" => "/audit-logs", "perm_key" => "audit_logs"],
    ["label" => "Reports", "icon" => "bar-chart-3", "href" => "/reports", "perm_key" => "global_reports"],
  ],

  "it_admin" => [
    ["label" => "Dashboard", "icon" => "layout-dashboard", "href" => "/dashboard", "perm_key" => "dashboard"],
    ["label" => "Security Settings", "icon" => "shield-check", "href" => "/security-settings", "perm_key" => "system_security"],
    ["label" => "User Role Settings", "icon" => "user-check", "href" => "/user-role-settings", "perm_key" => "system_security"],
    ["label" => "Backup & Maintenance", "icon" => "database-backup", "href" => "/backup-maintenance", "perm_key" => "backup_mgmt"],
    ["label" => "Audit Logs", "icon" => "file-search", "href" => "/audit-logs", "perm_key" => "audit_logs"],
  ],

  "patient" => [
    ["label" => "Dashboard", "icon" => "layout-dashboard", "href" => "/dashboard", "perm_key" => "dashboard"],
    ["label" => "X-ray Status", "icon" => "activity", "href" => "/xray-status", "perm_key" => "xray_status"],
    ["label" => "My Records", "icon" => "folder-open", "href" => "/my-records", "perm_key" => "my_records"],
    ["label" => "Registration", "icon" => "user-plus", "href" => "/registration", "perm_key" => "patient_reg"],
  ],
];