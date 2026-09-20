# CitiLife System Documentation

**System Title:** A Web-Based Radiology Reporting System with Shared Data Repository and Patient Status Tracking of CitiLife Diagnostic Center  
**Target Organization:** CitiLife Diagnostic Center  
**Document Type:** Technical and Functional System Documentation  

---

## 1. SYSTEM OVERVIEW

### 1.1 Project Description
CitiLife Diagnostic Center operates multiple clinical branches providing diagnostic imaging services to patients. Previously, radiological workflows, examination records, and diagnostic reports were managed through decentralized, manual, or branch-isolated processes. This led to operational bottlenecks such as delayed report turnaround times, difficulty in accessing a patient's historical records taken at another branch, and lack of real-time tracking for patients regarding the status of their examinations.

The **Web-Based Radiology Reporting System with Shared Data Repository and Patient Status Tracking** is an integrated software platform developed specifically for CitiLife Diagnostic Center. It centralizes patient records, diagnostic imaging files, and radiologist reports into a shared repository while enforcing branch-level isolation, role-based access control (RBAC), and accountability. The system provides real-time examination status tracking for patients and staff, coordinates inter-branch record transfers through a formal review process, and standardizes the generation and release of official diagnostic X-Ray reports.

### 1.2 System Objectives
1. **Centralize Patient and Diagnostic Records:** Establish a unified, shared repository of patient demographic profiles and past radiological examinations accessible across all accredited CitiLife branches subject to access controls.
2. **Streamline the Radiology Workflow:** Digitize and automate the diagnostic pipeline from patient registration, case queuing, technician image upload, radiologist reading and reporting, to final report sign-off.
3. **Enable Real-Time Patient Status Tracking:** Provide a dedicated patient portal where patients can monitor the lifecycle of their examination (from pending intake to completed reading) and securely view or download official diagnostic reports.
4. **Facilitate Secure Inter-Branch Record Sharing:** Implement a structured cross-branch record request and approval workflow, allowing authorized medical staff to request prior imaging studies from other branches with administrative oversight.
5. **Ensure Traceability and Audit Compliance:** Maintain an automated, tamper-evident audit trail capturing all user activities, status transitions, report approvals, and record accesses with user IDs, branch IDs, IP addresses, and timestamps.
6. **Support Administrative Operations:** Provide centralized branch management, staff account controls, backup utilities, and statistical activity monitoring for administrative and technical personnel.

### 1.3 Scope and Limitations

#### In Scope:
- **Multi-Branch Management:** Centralized administration of facility profiles for all CitiLife branches (e.g., Gapan, Bongabon, Peñaranda, General Tinio, Sto. Domingo, San Antonio, Pantabangan).
- **Role-Based Access Control:** Distinct privilege enforcement across six (6) actual system actor roles: Central Administrator, IT Administrator, Branch Administrator, Radiologic Technologist (RadTech), Radiologist, and Patient.
- **Patient Registration and Onboarding:** Dual intake mechanism supporting walk-in registration by staff and online patient self-registration with token-based email verification and branch approval gates.
- **Diagnostic Case Lifecycle:** Complete tracking of examination cases for X-Ray services, including examination classification (e.g., Chest PA, Extremities, Spine, Skull), urgency priority, and PhilHealth membership capture.
- **Medical Image Upload and Storage:** Uploading and associating digital radiological image files with corresponding examination cases.
- **Radiological Reading and Reporting:** Dedicated review worklist for radiologists to inspect images, encode clinical findings, diagnostic impressions, and recommendations, with automated PDF report generation featuring digital signatures.
- **Inter-Branch Record Requests:** Formal request submission, justification capture, and branch administrator approval/denial workflow for transferring patient records between branches.
- **Patient Portal:** Self-service dashboard for patients to view examination status, view past diagnostic history, download released PDF reports, and submit feedback.
- **System Accountability and Auditing:** Automated system-wide logging of all data modifications, administrative events, and authentication attempts.
- **System Administration and Maintenance:** Centralized user account management, security parameter configuration (OTP, lockout thresholds), and database backup utilities.

#### Limitations / Out of Scope:
- **Direct DICOM/PACS Hardware Integration:** The system does not directly interface with physical X-Ray modalities or PACS servers via DICOM/HL7 network protocols. Image files are digitized and uploaded manually by Radiologic Technologists in standard web-supported image formats.
- **Billing and Payment Gateway:** The system does not process automated online financial transactions, credit card payments, or digital wallet integrations. Financial settlements are handled through physical cashier procedures at the clinic.
- **SMS Gateway Notifications:** The system does not dispatch automated SMS alerts. Notifications are delivered through in-app notification counters, user dashboards, and transactional email verification.
- **Non-Radiological Modalities:** The active operational focus of the system is strictly tailored to X-Ray radiological services and standard diagnostic reporting.

---

## 2. SYSTEM REQUIREMENTS

### 2.1 Hardware Requirements

#### Server Requirements
- **Processor:** Intel Core i5 or equivalent
- **RAM:** 8 GB or higher
- **Storage:** 250 GB SSD or higher, depending on the volume of stored patient records and radiological images
- **Network:** Stable internet connection with sufficient bandwidth for multi-branch access

#### Client Workstation Requirements
- **Processor:** Intel Core i3 or equivalent
- **RAM:** 4 GB or higher
- **Display:** 1920 × 1080 resolution recommended
- **Input:** Standard keyboard and mouse
- **Network:** Stable internet connection

### 2.2 Software Requirements

#### Server Environment
- **Operating System:** Windows 10/11 or Ubuntu Linux
- **Web Server:** Apache 2.4 or higher
- **PHP:** PHP 8.2 or higher
- **Database Management System:** MySQL 8.0 or higher
- **Required PHP Extensions:** PDO, OpenSSL, GD, and MBString

#### Client Environment
- **Web Browser:** Google Chrome, Microsoft Edge, or Mozilla Firefox
- **PDF Reader:** Required for viewing and printing generated diagnostic reports

---

## 3. SYSTEM ARCHITECTURE AND TECHNOLOGY STACK

### 3.1 Architectural Pattern
The system is built on a custom lightweight **Model-View-Controller (MVC)** architectural pattern developed in native PHP. The MVC architecture enforces strict separation of concerns, ensuring maintainability, code reusability, and modularity:

- **Model:** Encapsulates business logic, data structures, and database interactions. Models execute parameterized SQL queries via PHP Data Objects (PDO), ensuring data integrity, transactional consistency, and protection against injection attacks. Examples include `UserModel`, `PatientModel`, `CaseModel`, `BranchModel`, `RecordRequestModel`, and `AuditLogModel`.
- **View:** Responsible for presentation layout and user interface rendering. Views display structured information received from controllers using HTML5 and Tailwind CSS utility styling. Role-specific dashboards and partial layouts (sidebars, navigation headers) ensure users only see interface elements appropriate to their authorized role.
- **Controller:** Acts as the orchestrator between Models and Views. Controllers process incoming HTTP GET and POST requests routed from the main entry point, validate inputs, verify user session status through middleware, execute business rules, invoke corresponding models, and dispatch appropriate views or JSON responses. Examples include `AuthController`, `PageController`, and role-specific controllers.

### 3.2 Technology Stack

| Layer / Component | Technology | Purpose |
|---|---|---|
| Backend Programming Language | PHP 8.2 (Native) | Handles core application logic, request handling, and server-side processing without heavy third-party web frameworks. |
| Application Architecture | Custom MVC Pattern | Enforces organized code separation between data access, business workflows, and UI presentation. |
| Database Management System | MySQL 8.0 / MariaDB 10.4 | Stores all relational application data, enforces foreign key constraints, unique indexes, and referential integrity. |
| Database Abstraction | PHP Data Objects (PDO) | Secure database connectivity utilizing prepared statements and bound parameters. |
| Frontend Styling & Layout | Tailwind CSS / PostCSS | Responsive, utility-first CSS framework providing clean, modern UI components and responsive views. |
| Diagnostic Report Generation | Dompdf | Server-side rendering of structured HTML/CSS templates into official, downloadable, and printable PDF diagnostic reports. |
| Tabular Data Export | PhpSpreadsheet | Generates standardized spreadsheet exports (.xlsx) for administrative reporting and case log analysis. |
| Transactional Emailing | PHPMailer | Delivers automated account verification links, password reset tokens, and security OTP codes via SMTP. |
| Web Server Environment | Apache 2.4 | Web HTTP server utilizing `.htaccess` rewrite rules to route all traffic cleanly through `index.php`. |

---

## 4. USER ROLES AND ACCESS CONTROL

The system supports six (6) distinct user roles. Each role is designed with a specific operational scope, access permissions, and functional boundaries to prevent unauthorized data access across branches and modules.

### 4.1 Role Specifications

#### 1. Central Administrator (`admin_central`)
- **Scope:** System-wide (All branches).
- **Main Responsibilities:** System governance, overall branch performance monitoring, user account provisioning, role assignment, and organization-wide compliance oversight.
- **Main Permissions:** Full read, create, update, and deactivation permissions for all staff users; branch profile configuration; access to system-wide audit trails and global reports; viewing patient records across all branches.
- **Accessible Modules:** Dashboard, User Management, Branch Management, Patient Records (All Branches), Audit Logs, System Reports, Feedback Review.

#### 2. IT Administrator (`it_admin`)
- **Scope:** System-wide (Infrastructure and Technical Operations).
- **Main Responsibilities:** System health monitoring, security policy configuration, database maintenance, backup execution, and technical troubleshooting.
- **Main Permissions:** Execute manual on-demand database backups; inspect infrastructure health and technical audit logs; configure login lockout parameters, OTP attempt limits, and session policies.
- **Accessible Modules:** IT Dashboard, Backup & Maintenance, Security Settings, System Health, Audit Logs.

#### 3. Branch Administrator (`branch_admin`)
- **Scope:** Branch-specific (Restricted to assigned facility).
- **Main Responsibilities:** Branch staff supervision, local patient account approvals, inter-branch record request authorization, and local operational tracking.
- **Main Permissions:** Approve or reject pending patient self-registrations for the branch; approve or deny record requests initiated by other branches seeking access to local records; monitor branch-specific audit logs; view branch performance metrics and patient feedback.
- **Accessible Modules:** Branch Dashboard, Patient Approvals, Record Requests Management, Branch Patient Records, Branch Audit Logs, Branch Feedback.

#### 4. Radiologic Technologist (`radtech`)
- **Scope:** Branch-specific (Clinical Examination Floor).
- **Main Responsibilities:** Patient intake, examination case creation, physical X-Ray conduction, diagnostic image uploading, queue monitoring, and report releasing.
- **Main Permissions:** Encode walk-in patient information; create new examination cases; upload radiological image files; view branch examination queue (`worklist`); submit inter-branch record requests when patient history from another branch is required; release approved reports to patients.
- **Accessible Modules:** Patient Registration, Worklist / Examination Queue, Case Status, Image Upload, Record Request Submission, Patient Records History, Report Release.

#### 5. Radiologist (`radiologist`)
- **Scope:** Assigned Diagnostic Cases (Clinical Reading).
- **Main Responsibilities:** Examination review, image analysis, radiological interpretation, drafting findings, clinical impressions, recommendations, and official report sign-off.
- **Main Permissions:** Access dedicated reading worklist (`case-review`); inspect patient clinical history and uploaded X-Ray scans; encode structured diagnostic findings, impressions, and recommendations; submit finalized diagnostic reports; request report revision/unlock when clinical corrections are necessary.
- **Accessible Modules:** Radiologist Dashboard, Case Review, Pending Reading Worklist, Completed Reports Archive, Patient Diagnostic History.

#### 6. Patient (`patient`)
- **Scope:** Personal Records Only.
- **Main Responsibilities:** Account self-registration, credential management, examination request submission, personal status monitoring, and report retrieval.
- **Main Permissions:** View personal profile; verify email address via secure token; track status of personal examination cases in real-time; view and download finalized, released PDF diagnostic reports; submit service feedback.
- **Accessible Modules:** Patient Portal Dashboard, My Records, Examination Status Tracking, View/Download Diagnostic Report, Feedback Submission.

### 4.2 Role-Based Access Control Matrix

| Module / Feature | Central Admin | IT Admin | Branch Admin | RadTech | Radiologist | Patient |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| User Management (Staff Accounts) | Yes | No | No | No | No | No |
| Branch Profile Management | Yes | No | No | No | No | No |
| Backup & Maintenance | No | Yes | No | No | No | No |
| Security Policy Settings | No | Yes | No | No | No | No |
| Patient Account Approvals | Yes | No | Yes | No | No | No |
| Inter-Branch Request Authorization | No | No | Yes | No | No | No |
| Inter-Branch Request Submission | No | No | No | Yes | No | No |
| Patient Walk-In Registration | No | No | No | Yes | No | No |
| Case Creation & Queuing | No | No | No | Yes | No | No |
| X-Ray Image Upload | No | No | No | Yes | No | No |
| Case Reading & Diagnostic Entry | No | No | No | No | Yes | No |
| Diagnostic Report Sign-Off | No | No | No | No | Yes | No |
| Release Report to Patient | No | No | No | Yes | No | No |
| Download Own Report | No | No | No | No | No | Yes |
| View System-Wide Audit Logs | Yes | Yes | No | No | No | No |
| View Branch-Specific Audit Logs | Yes | No | Yes | No | No | No |
| Submit Service Feedback | No | No | No | No | No | Yes |

---

## 5. CORE FUNCTIONAL MODULES

### 5.1 Authentication and Access Control Module
- **Purpose:** Manages secure system entry, credential verification, session lifecycle, and route protection across all user roles.
- **Main Functions:**
  - Email and password credential validation using Bcrypt hashing.
  - Multi-guard session management distinguishing guest, authenticated staff, and patient sessions.
  - Self-registration for patients with email activation tokens.
  - Automated password recovery via time-limited email tokens and OTP verification.
  - Account lockout protection after repeated invalid authentication attempts.
  - Role-based route protection denying unauthorized URL manipulation (HTTP 403 Forbidden).
- **Users:** All roles (`admin_central`, `it_admin`, `branch_admin`, `radtech`, `radiologist`, `patient`).
- **Process:** The user submits credentials at the login endpoint. The controller queries the `users` table, verifies the Bcrypt hash, checks if the account status is `Active` and email is verified, sets session variables (`user_id`, `role`, `branch_id`), logs the event in the audit trail, and redirects the user to their role-specific dashboard.

### 5.2 Patient Management Module
- **Purpose:** Centralizes patient demographic profiles and maintains clinical history across all CitiLife branches.
- **Main Functions:**
  - Automated generation of unique institutional Patient Numbers (Format: `PAT-[BRANCH]-[YEAR]-[SEQUENCE]`).
  - Encoding of complete patient demographic data (Full Name, Date of Birth, Age, Sex, Contact Number, Home Address).
  - Patient search by name, contact number, or assigned patient number.
  - Aggregation of past examination history across multiple branches.
- **Users:** Radiologic Technologist, Branch Administrator, Central Administrator.
- **Process:** During patient intake, the staff searches for existing records to prevent duplication. If the patient is new, a demographic record is created and assigned a unique `patient_number` linked to the registering branch.

### 5.3 Case and Examination Management Module
- **Purpose:** Manages individual radiological examination orders, procedural details, and priority queuing.
- **Main Functions:**
  - Creation of diagnostic examination cases linked to verified patient records.
  - Categorization of X-Ray procedures (e.g., Chest PA, Extremities, Spine, Skull).
  - Priority assignment (`Normal`, `Routine`, `Priority`, `STAT`, `Urgent`).
  - PhilHealth insurance status tracking and PhilHealth ID capture.
  - Unique Case Number generation per branch and calendar year.
  - Dynamic status tracking from initial intake to final release.
- **Users:** Radiologic Technologist, Branch Administrator, Radiologist.
- **Process:** The RadTech initiates a case for a registered patient, inputs clinical indications, selects procedure type and priority, and submits the form. The system generates a `case_number`, sets status to `Pending`, and places the case in the active branch worklist.

### 5.4 Medical Imaging Management Module
- **Purpose:** Handles the uploading, storage, and linkage of digital X-Ray imaging files to examination cases.
- **Main Functions:**
  - Secure web interface for uploading radiological image files associated with open cases.
  - File extension and size validation to ensure proper storage handling.
  - Association of image file paths with the corresponding case record in the database.
  - Automated synchronization of case image status (`Uploaded` vs. `Pending Upload`).
- **Users:** Radiologic Technologist, Radiologist.
- **Process:** Once the physical X-Ray scan is completed, the RadTech selects the case in the queue, uploads the acquired image files, and confirms submission. The system stores the files in designated storage directories, updates `image_status` to `Uploaded`, transitions case status to `Under Reading`, and triggers a notification for the Radiologist.

### 5.5 Radiologist Review and Diagnostic Reporting Module
- **Purpose:** Provides radiologists with clinical diagnostic tools to evaluate imaging studies, encode findings, and generate official diagnostic reports.
- **Main Functions:**
  - Dedicated reading worklist filtered by availability and case priority.
  - Image viewer allowing inspection of uploaded X-Ray scans alongside clinical indications.
  - Standardized medical reporting fields: Clinical Findings, Diagnostic Impression, and Recommendations.
  - Electronic sign-off applying the radiologist's license information and digitized signature.
  - Automated generation of official, print-ready PDF radiology reports via Dompdf.
- **Users:** Radiologist, Radiologic Technologist (for printing), Patient (for viewing released reports).
- **Process:** The Radiologist opens a case marked `Under Reading`, reviews the patient's clinical history and image, types the official Findings, Impression, and Recommendations, and signs off. The system updates the case status to `Report Ready`, compiles the official PDF report, and logs the completion timestamp.

### 5.6 Inter-Branch Record Request Module
- **Purpose:** Enables structured coordination between CitiLife branches when a patient's historical records reside at another clinic location.
- **Main Functions:**
  - Submission of formal record requests specifying patient identity, requested exam, and clinical justification.
  - Centralized request dashboard for custodian branch administrators.
  - Structured approval or denial processing with documented remarks.
  - Automated notification delivery upon request resolution.
  - Transactional logging of all inter-branch data transfers.
- **Users:** Radiologic Technologist (Requester), Branch Administrator (Approver).
- **Process:** A RadTech at Branch A needing past X-Rays for a patient who previously visited Branch B submits a record request. The Branch Administrator at Branch B reviews the request and justification. If approved, access permissions are granted to view the requested historical records, and the requesting branch is notified.

### 5.7 Audit Logging and Accountability Module
- **Purpose:** Provides a centralized, tamper-evident audit trail capturing all system events for operational accountability, security compliance, and administrative inspection.
- **Main Functions:**
  - Automated recording of all state-changing operations across all modules.
  - Granular data capture: User ID, Branch ID, Target Module, Action Performed, Entity Identifier, Changes/Details, IP Address, and Timestamp.
  - Advanced administrative filtering by date range, branch, actor, and event type.
  - Immutable log persistence preventing modification or deletion by staff users.
- **Users:** Central Administrator, IT Administrator, Branch Administrator (branch scope).
- **Process:** Any critical action executed by a controller (e.g., login, case creation, image upload, report generation, record approval) automatically triggers the `AuditLogModel` to insert a persistent record into the `audit_logs` table before concluding the transaction.

---

## 6. SYSTEM OPERATIONAL WORKFLOW

The end-to-end operational procedure of the CitiLife system follows a sequential, clinical pipeline:

```
[Patient Intake / Registration]
       │
       ▼
[Case Creation & Queuing]
       │
       ▼
[Physical X-Ray Examination]
       │
       ▼
[Image Upload & File Storage]
       │
       ▼
[Radiologist Interpretation & Reporting]
       │
       ▼
[Report Sign-Off & Status: Report Ready]
       │
       ▼
[Staff Verification & Report Release]
       │
       ▼
[Patient Portal Access & Report Download]
```

### Detailed Chronological Steps:

1. **Patient Intake & Registration:**
   - **Walk-in Patients:** The patient arrives at a CitiLife branch. The Radiologic Technologist encodes demographic information via the `patient-registration` interface. A unique `patient_number` is generated.
   - **Online Self-Registration:** The patient registers via `patient-signup`, verifies their email through an automated activation link, and awaits approval by the Branch Administrator.
2. **Case Creation:**
   - The RadTech creates a new examination case linked to the patient record. Procedural details (e.g., Chest PA, Skull, Extremities), priority level, clinical history, and PhilHealth membership are encoded. The system assigns a unique `case_number`. The initial case status is `Pending`.
3. **Physical Examination:**
   - The patient undergoes the physical X-Ray procedure in the clinic's examination room.
4. **Image Upload:**
   - The RadTech acquires the digitized image files and uploads them through the case queue interface. The system updates the case `image_status` to `Uploaded` and transitions the workflow status to `Under Reading`.
5. **Radiologist Interpretation:**
   - The Radiologist accesses the case review portal, inspects the clinical information and uploaded X-Ray scans, and types the formal diagnostic report (Findings, Impression, Recommendations).
6. **Report Sign-Off:**
   - The Radiologist signs off on the study. The system embeds the radiologist's electronic signature and credentials, marks the status as `Report Ready`, and records the completion timestamp.
7. **Report Release:**
   - The clinic staff verifies report completion and toggles the `released` status to `1` (`Released`). This authorizes the report for external viewing.
8. **Patient Access & Printing:**
   - The patient logs into the patient portal, views the updated `Completed` status, and downloads the official PDF diagnostic report. Walk-in patients receive an official printed copy directly from clinic reception.

---

## 7. DATABASE DESIGN AND DATA DICTIONARY

The CitiLife database is built on MySQL/MariaDB with normalized relational tables, foreign key constraints, and cascading integrity rules.

### 7.1 Table: `branches`
Stores physical clinic branch profiles and operational locations.

| Column | Data Type | Key | Nullable | Description |
|---|---|---|---|---|
| `id` | INT(11) | PK | No | Auto-increment unique branch identifier |
| `name` | VARCHAR(100) | | No | Official branch name (e.g., Gapan, Bongabon) |
| `address` | VARCHAR(255) | | Yes | Primary street and city address |
| `additional_address` | VARCHAR(255) | | Yes | Supplemental address details |
| `contact_number_1` | VARCHAR(50) | | Yes | Primary contact phone number |
| `contact_number_2` | VARCHAR(50) | | Yes | Secondary contact phone number |
| `contact_number_3` | VARCHAR(50) | | Yes | Tertiary contact phone number |
| `status` | ENUM | | No | Branch status (`Active`, `Inactive`) |
| `created_at` | TIMESTAMP | | No | Record creation timestamp |

### 7.2 Table: `users`
Stores user credentials, profile information, and role assignments for staff and patients.

| Column | Data Type | Key | Nullable | Description |
|---|---|---|---|---|
| `id` | INT(11) | PK | No | Auto-increment unique user identifier |
| `email` | VARCHAR(100) | Unique | No | Account email address / login credential |
| `password` | VARCHAR(255) | | No | Bcrypt-hashed password string |
| `role` | VARCHAR(50) | | No | Role identifier (`admin_central`, `it_admin`, `branch_admin`, `radtech`, `radiologist`, `patient`) |
| `name` | VARCHAR(255) | | Yes | Display name of the user |
| `full_name_report` | VARCHAR(255) | | Yes | Formal name printed on clinical reports |
| `avatar` | VARCHAR(255) | | Yes | File path to user profile image |
| `signature` | VARCHAR(255) | | Yes | File path to digitized signature image (for Radiologists) |
| `professional_title` | VARCHAR(255) | | Yes | Medical title/credentials (e.g., MD, FPCR) |
| `branch_id` | INT(11) | FK | Yes | Assigned branch (References `branches.id`, NULL for central admins) |
| `patient_id` | INT(11) | FK | Yes | Linked patient record for patient accounts (References `patients.id`) |
| `status` | ENUM | | No | Account status (`Pending`, `Active`, `Rejected`, `Inactive`) |
| `is_email_verified` | TINYINT(1) | | No | Email verification status flag (0 = No, 1 = Yes) |
| `verification_token` | VARCHAR(255) | | Yes | Token string for email verification |
| `otp_code` | VARCHAR(10) | | Yes | One-time PIN code for authentication/recovery |
| `token_expires_at` | DATETIME | | Yes | Expiration timestamp for active tokens |
| `login_locked_until` | DATETIME | | Yes | Lockout timestamp after failed login attempts |
| `created_at` | TIMESTAMP | | No | Account creation timestamp |

### 7.3 Table: `patients`
Stores demographic and medical identity records of patients.

| Column | Data Type | Key | Nullable | Description |
|---|---|---|---|---|
| `id` | INT(11) | PK | No | Auto-increment unique patient record ID |
| `patient_number` | VARCHAR(50) | Unique | Yes | Formatted institutional ID (`PAT-[BRANCH]-[YEAR]-[SEQ]`) |
| `first_name` | VARCHAR(100) | | No | Patient given first name |
| `middle_name` | VARCHAR(100) | | Yes | Patient middle name |
| `last_name` | VARCHAR(100) | | No | Patient surname / family name |
| `birthdate` | DATE | | No | Patient date of birth |
| `sex` | ENUM | | No | Biological sex (`Male`, `Female`) |
| `contact_number` | VARCHAR(50) | | Yes | Primary contact phone number |
| `email` | VARCHAR(255) | | Yes | Patient email address |
| `home_address` | VARCHAR(255) | | Yes | Residential address |
| `branch_id` | INT(11) | FK | Yes | Primary registering branch (References `branches.id`) |
| `created_at` | TIMESTAMP | | No | Registration timestamp |

### 7.4 Table: `cases`
Stores examination cases, imaging file associations, clinical interpretations, and diagnostic report data.

| Column | Data Type | Key | Nullable | Description |
|---|---|---|---|---|
| `id` | INT(11) | PK | No | Auto-increment unique examination case ID |
| `case_number` | VARCHAR(50) | Unique | No | Formatted unique case tracking number |
| `patient_id` | INT(11) | FK | No | Target patient ID (References `patients.id`) |
| `branch_id` | INT(11) | FK | Yes | Branch conducting examination (References `branches.id`) |
| `service_type` | ENUM | | No | Diagnostic service category (`X-Ray`) |
| `exam_type` | VARCHAR(100) | | No | Specific procedure name (e.g., Chest PA, Skull, Spine) |
| `priority` | ENUM | | No | Examination priority (`Normal`, `Priority`, `STAT`, `Urgent`, `Routine`) |
| `philhealth_status` | ENUM | | No | PhilHealth insurance classification |
| `philhealth_id` | VARCHAR(50) | | Yes | PhilHealth membership ID number |
| `status` | ENUM | | No | Examination workflow status (`Pending`, `Under Reading`, `Report Ready`, `Completed`, `Rejected`) |
| `approval_status` | ENUM | | No | Clinical approval sign-off status (`Pending`, `Approved`, `Rejected`) |
| `image_status` | ENUM | | No | Imaging upload indicator (`Uploaded`, `—`) |
| `image_path` | TEXT | | Yes | Relative file system path to uploaded X-Ray image |
| `released` | TINYINT(1) | | No | Report release flag to patient (0 = Held, 1 = Released) |
| `clinical_information` | TEXT | | Yes | Referring doctor's notes and clinical indication |
| `findings` | TEXT | | Yes | Detailed radiological observations encoded by radiologist |
| `impression` | TEXT | | Yes | Clinical diagnostic conclusion |
| `recommendation` | TEXT | | Yes | Suggested follow-up procedures or clinical advice |
| `radiologist_id` | INT(11) | FK | Yes | Assigned Radiologist user ID (References `users.id`) |
| `radtech_id` | INT(11) | FK | Yes | Operating Radiologic Technologist ID (References `users.id`) |
| `date_completed` | DATETIME | | Yes | Timestamp of report finalization and sign-off |
| `created_at` | TIMESTAMP | | No | Case creation timestamp |

### 7.5 Table: `record_requests`
Tracks inter-branch requests for historical patient records and imaging studies.

| Column | Data Type | Key | Nullable | Description |
|---|---|---|---|---|
| `id` | INT(11) | PK | No | Auto-increment unique request record ID |
| `patient_no` | VARCHAR(50) | | No | Patient institutional identification number |
| `patient_name` | VARCHAR(255) | | No | Full name of patient whose records are requested |
| `exam_type` | VARCHAR(100) | | Yes | Procedure type of requested examination |
| `request_branch` | VARCHAR(100) | | Yes | Destination branch possessing the target records |
| `reason` | TEXT | | No | Documented clinical/operational justification |
| `branch_id` | INT(11) | FK | Yes | Requesting branch ID (References `branches.id`) |
| `status` | ENUM | | No | Request review state (`Pending`, `Approved`, `Denied`) |
| `created_at` | TIMESTAMP | | No | Request submission timestamp |

### 7.6 Table: `audit_logs`
Stores comprehensive, immutable records of system transactions and security events.

| Column | Data Type | Key | Nullable | Description |
|---|---|---|---|---|
| `id` | INT(11) | PK | No | Auto-increment unique log entry identifier |
| `user_id` | INT(11) | | No | Identifier of the user performing the action |
| `branch_id` | INT(11) | | Yes | Facility context identifier where event occurred |
| `module` | VARCHAR(100) | | Yes | Application module name (e.g., Auth, Case, Patient) |
| `action` | VARCHAR(100) | | No | Action performed (e.g., Create, Update, Sign, Approve) |
| `entity_type` | VARCHAR(50) | | No | Affected entity type (e.g., Patient, Case, User) |
| `entity_id` | INT(11) | | Yes | Identifier of the modified record |
| `details` | TEXT | | Yes | Additional event metadata or parameter summary |
| `ip_address` | VARCHAR(45) | | Yes | Client network IPv4 or IPv6 address |
| `created_at` | TIMESTAMP | | No | Exact event timestamp |

### 7.7 Key Database Relationships
- **Users to Branches (`N:1`):** Multiple users are assigned to a single branch via `users.branch_id = branches.id`. Central administrators have a `NULL` branch ID.
- **Patients to Branches (`N:1`):** Patients are registered under a primary branch via `patients.branch_id = branches.id`.
- **Cases to Patients (`N:1`):** A patient can have multiple diagnostic examination cases over time via `cases.patient_id = patients.id`.
- **Cases to Branches (`N:1`):** Each examination case is conducted by a specific branch facility via `cases.branch_id = branches.id`.
- **Cases to Radiologists (`N:1`):** Radiologists are linked to cases they interpret via `cases.radiologist_id = users.id`.
- **Record Requests to Branches (`N:1`):** Record requests originate from a specific branch via `record_requests.branch_id = branches.id`.

---

## 8. SECURITY AND SYSTEM ADMINISTRATION

### 8.1 Authentication and Password Security
- **One-Way Password Hashing:** User passwords are encrypted using PHP's native `password_hash()` utilizing the industry-standard Bcrypt algorithm with automated salt generation. Plaintext passwords are never stored or logged.
- **Session Management:** Authentication state is stored in secure server-side PHP sessions. Upon logout or session invalidation, session data is completely destroyed.
- **Account Lockout Controls:** The system monitors failed authentication attempts. Excessive invalid attempts trigger a temporary login lockout (`login_locked_until`) to mitigate brute-force password attacks.
- **Email Verification Tokens:** Newly registered patient accounts must verify email ownership through single-use cryptographic tokens sent via PHPMailer.

### 8.2 Authorization and Access Control
- **Role-Based Access Control (RBAC):** Middleware intercepts all page requests, verifying whether the logged-in user possesses the required permission level before controller execution.
- **Branch-Level Scoping:** Queries executed by branch personnel (`branch_admin`, `radtech`) automatically enforce branch filters (`WHERE branch_id = :branch_id`), strictly preventing cross-branch data leaks unless authorized through a formal record request.

### 8.3 SQL Injection Prevention
- **Parameterized Queries:** All database interactions are executed through PHP Data Objects (PDO) prepared statements with explicit parameter binding. Direct string concatenation of user inputs into SQL queries is strictly prohibited across all models.

### 8.4 Audit Trail Enforcement
- **Comprehensive Activity Logging:** State mutations in critical modules automatically write an entry to `audit_logs`. The log stores the user ID, branch context, module name, action performed, entity type and ID, client IP address, and timestamp. Logs are accessible only to administrators.

### 8.5 Database Backup and Maintenance
- **Structured SQL Backup Utility:** IT Administrators can initiate database backups directly from the `backup-maintenance` interface. The system generates structured SQL backup dumps of all schemas and data, saving them into a secure storage directory.

### 8.6 Medical Image File Storage
- **File System Segregation:** Uploaded X-Ray image files are stored in dedicated server storage directories separated from public script execution paths.
- **Database Path Indexing:** The database stores file path references (`cases.image_path`) rather than binary BLOBs, maintaining optimal database query performance. Direct execution of executable scripts (.php, .exe) in image upload directories is disabled via web server configuration.

---

## 9. SYSTEM STATUS AND RECORD HANDLING

The CitiLife system maintains clear, standardized lifecycle indicators across examination cases, clinical approvals, imaging uploads, and report releases.

### 9.1 Primary Case Statuses

| Status | Meaning | Next Possible Status |
|---|---|---|
| `Pending` | The case has been created by the RadTech and queued for examination. Physical imaging or image upload has not yet been finalized. | `Under Reading`, `Rejected` |
| `Under Reading` | The radiological images have been uploaded and the case is actively available in the Radiologist's reading worklist. | `Report Ready`, `Pending` |
| `Report Ready` | The Radiologist has encoded findings, impressions, and recommendations, and signed off on the diagnostic study. | `Completed` |
| `Completed` | The diagnostic report has been finalized, verified, and released for patient viewing or printing. | Terminal state (Archived) |
| `Rejected` | The examination case was invalidated, cancelled, or rejected due to procedural or patient intake discrepancies. | Terminal state |

### 9.2 Supplemental Record Indicators

- **Approval Status (`approval_status`):**
  - `Pending`: Initial sign-off state awaiting final clinical sign-off.
  - `Approved`: Diagnostic findings have been formally signed off by the licensed Radiologist.
  - `Rejected`: Study rejected or flagged for administrative correction.
- **Image Status (`image_status`):**
  - `—`: No radiological image has been uploaded yet.
  - `Uploaded`: Digital imaging files have been attached to the case record.
- **Release Status (`released`):**
  - `0` (Held): The diagnostic report is completed internally but withheld from the patient portal pending staff release.
  - `1` (Released): The diagnostic report is published and immediately accessible to the patient online.

---

## 10. SYSTEM OUTPUTS AND REPORTS

The system generates structured clinical, operational, and administrative outputs tailored to specific stakeholder needs:

| Output Document / Report | Purpose | Authorized Users | Format |
|---|---|---|---|
| **Official Radiology Diagnostic Report** | Standardized medical report containing patient profile, exam type, clinical findings, radiologist impression, recommendations, and verified signature. | Radiologist, RadTech, Branch Admin, Patient | Printable PDF (Dompdf) |
| **Patient Examination History** | Consolidated chronological record of a patient's past diagnostic X-Ray examinations across branches. | Radiologist, RadTech, Branch Admin, Patient | Web View / Tabular Display |
| **Branch Operational Activity Summary** | Statistical overview of completed, pending, and urgent examinations conducted by a branch. | Branch Admin, Central Admin | Web View / Excel (.xlsx) |
| **System Audit Trail Log** | Granular export of administrative events, security transactions, and clinical data modifications. | Central Admin, IT Admin | Web View / Filterable Table |
| **Inter-Branch Record Request Log** | Formal record of cross-branch patient file requests, justifications, and administrative dispositions. | Branch Admin, Central Admin | Web View / Tabular Display |
| **Patient Feedback Report** | Aggregated patient ratings and qualitative comments regarding facility services. | Branch Admin, Central Admin | Web View / Filterable Table |

---

## 11. SYSTEM MAINTENANCE AND ADMINISTRATION

The system defines clear separation of administrative authority across organizational and technical boundaries:

### 11.1 Central Administrator Responsibilities
- **Staff User Provisioning:** Create, update, assign roles to, and deactivate personnel accounts across all branches.
- **Branch Configuration:** Register new CitiLife branch clinics and maintain facility contact information.
- **System-Wide Oversight:** Inspect aggregate operational metrics, patient distributions, and cross-branch audit trails.
- **Feedback Governance:** Review critical patient service ratings and qualitative feedback to ensure consistent clinical care standards.

### 11.2 IT Administrator Responsibilities
- **Database Preservation:** Initiate on-demand database backups and verify integrity of SQL dump files.
- **Security Parameter Tuning:** Configure lockout thresholds, session timeouts, and OTP verification parameters.
- **Technical Log Monitoring:** Inspect system errors, unauthorized access attempts, and audit logs to resolve infrastructure issues.

### 11.3 Branch Administrator Responsibilities
- **Patient Registration Verification:** Review and approve or reject online patient self-registration requests for the local branch.
- **Inter-Branch Request Adjudication:** Review incoming requests from other branches seeking access to local patient records and grant or deny access based on clinical justifications.
- **Local Staff Tracking:** Monitor daily examination throughput and local branch audit logs to ensure operational efficiency and policy adherence.

---

## 12. SYSTEM LIMITATIONS AND FUTURE CONSIDERATIONS

### 12.1 Actual System Limitations
1. **Manual Image Acquisition:** The platform relies on technologists to upload digitized X-Ray scans manually. It does not pull image studies directly from physical modalities via DICOM C-STORE or PACS archives.
2. **Manual Payment Handling:** Patient billing and transaction receipts are conducted physically at clinic cashiers; no automated online payment processing is integrated into the web system.
3. **No Direct SMS Gateway:** Outgoing notifications are delivered through web-based notification feeds and transactional emails; automated cellular SMS text messages are not supported.

### 12.2 Future Considerations (Planned Enhancements)
1. **PACS / DICOM Direct Integration:** Future releases may explore standard DICOMweb or HL7 integration to automatically ingest medical images directly from X-Ray modality consoles.
2. **Online Payment Processing:** Evaluating integration with local Philippine payment gateways (e.g., GCash, Maya) for contactless patient bill settlement.
3. **Automated SMS Dispatch:** Exploring integration with third-party SMS APIs to notify patients via text message when their diagnostic reports are ready for download.

---

## 13. DOCUMENTATION NOTES

This technical documentation describes the functional features, architecture, database schemas, and workflows of the **CitiLife Radiology Reporting System** as currently implemented. System specifications, user interfaces, and database structures are maintained under version control and are subject to continuous refinement in accordance with CitiLife Diagnostic Center operational requirements and technical updates.
