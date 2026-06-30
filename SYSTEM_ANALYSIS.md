# CitiLife-System: Comprehensive Documentation

**Document Version:** 1.0  
**Generated:** June 30, 2026  
**Status:** Analysis Based on Actual Implementation

---

## 1. System Overview

### Purpose
CitiLife-System is a **multi-branch radiology and medical imaging management platform** designed to centralize patient care, radiological examinations, and professional report generation across multiple healthcare facilities. The system streamlines patient registration, case management, radiological technician workflows, radiologist assessments, and inter-branch communication.

### Target Organizations
- Multi-location healthcare providers operating multiple radiology branches
- Facilities requiring centralized patient records management
- Organizations needing PhilHealth integration and audit compliance
- Multi-stakeholder medical imaging coordination systems

### Core Value Propositions
1. **Centralized Patient Management:** Single patient database across all branches
2. **Automated Audit Logging:** Complete activity tracking for compliance
3. **Role-Based Access Control:** Secure multi-level user management
4. **Workflow Automation:** Case lifecycle management with approval gates
5. **Professional Reporting:** Standardized radiology report generation
6. **Inter-Branch Coordination:** Record requests between facilities

---

## 2. System Actors

Based on the database schema and controller structure, the following actors are identified:

### 2.1 Admin Central (`admin_central`)
**Responsibilities:**
- System-wide user and branch management
- Central access to all audit logs and system reports
- Global settings and security configurations
- User role assignment and permissions
- View all patient records across all branches
- System backup and maintenance oversight

**Access Level:** Highest privilege level, not branch-restricted

---

### 2.2 IT Administrator (`it_admin`)
**Responsibilities:**
- System backup and maintenance tasks (`backup-maintenance` permission)
- Database maintenance and recovery operations
- Security patches and system updates
- Audit log monitoring for security purposes

**Access Level:** High privilege, system-wide

---

### 2.3 Branch Administrator (`branch_admin`)
**Responsibilities:**
- Manage local branch staff and settings
- Approve/reject patient registrations for their branch
- Manage inter-branch record requests
- View branch-specific audit logs
- Handle branch-level operations and configurations

**Access Level:** Branch-scoped, cannot exceed branch boundaries

---

### 2.4 Radiologic Technician (`radtech`)
**Responsibilities:**
- Patient registration (manual and portal-assisted)
- Case creation and examination scheduling
- Radiological image uploading
- Queue and worklist management
- Submit inter-branch record requests
- View case status and examination history

**Access Level:** Branch-specific operations

---

### 2.5 Radiologist (`radiologist`)
**Responsibilities:**
- Review radiological cases (`case-review` page)
- Analyze uploaded images
- Enter radiological findings and impressions
- Write recommendations
- Generate professional radiology reports
- Sign off on completed examinations

**Access Level:** Case review and reporting functions

---

### 2.6 Patient (`patient`)
**Responsibilities:**
- Self-register with email verification
- View personal examination status and history
- Access downloaded radiology reports
- Track case progress through pipeline
- View appointment/examination records

**Access Level:** Limited to own records and profile management

---

## 3. Use Case Diagram

### 3.1 Use Cases by Actor

#### Admin Central
1. **Manage Users** - Create, read, update, delete staff accounts
2. **Manage Branches** - Configure and monitor all branch operations
3. **View Global Reports** - Generate system-wide radiology reports
4. **Monitor Audit Logs** - View all system activities with detailed filters
5. **Manage Security Settings** - Configure system security parameters
6. **Manage Backup** - Initiate and monitor system backups
7. **Approve/Reject Patient Accounts** - Gate patient registrations system-wide

#### IT Administrator
1. **Perform Backups** - Execute database and system backups
2. **Monitor System Health** - View audit logs for troubleshooting
3. **Manage Maintenance Windows** - Schedule backup maintenance

#### Branch Administrator
1. **Approve Patient Registrations** - Review pending patient registrations
2. **Manage Record Requests** - Approve or deny inter-branch record requests
3. **View Branch Audit Logs** - Monitor branch-specific activities
4. **Manage Branch Settings** - Configure branch-level options

#### Radiologic Technician
1. **Register Patients** - Create new patient records manually
2. **Create Cases** - Register radiological examinations
3. **Upload Images** - Submit radiological images for review
4. **Search Patient Records** - Locate existing patient information
5. **View Queue** - Monitor pending examinations
6. **Submit Record Requests** - Request patient records from other branches
7. **Download Reports** - Access finalized reports

#### Radiologist
1. **Review Cases** - View pending examinations
2. **Enter Findings** - Record radiological observations
3. **Write Impressions** - Provide clinical interpretation
4. **Generate Reports** - Create professional radiology reports
5. **View Case History** - Access examination archives

#### Patient
1. **Sign Up** - Create patient account via portal
2. **Login** - Access patient portal
3. **View Exam Status** - Check current and past examination progress
4. **Download Report** - Retrieve completed radiology reports
5. **View History** - Access medical imaging records

---

### 3.2 Mermaid Use Case Diagram

```mermaid
graph TB
    AdminCentral["👨‍💼 Admin Central"]
    ITAdmin["👨‍💻 IT Admin"]
    BranchAdmin["👤 Branch Admin"]
    RadTech["🔬 Radiologic Technician"]
    Radiologist["👨‍⚕️ Radiologist"]
    Patient["🧑‍🦱 Patient"]
    
    subgraph AdminCentralUseCases["Admin Central Use Cases"]
        UC1["Manage Users & Roles"]
        UC2["Manage Branches"]
        UC3["View Global Reports"]
        UC4["Monitor All Audit Logs"]
        UC5["Configure System Security"]
        UC6["Manage Backups"]
        UC7["Approve Patient Accounts"]
    end
    
    subgraph ITAdminUseCases["IT Admin Use Cases"]
        UC8["Perform Backups"]
        UC9["Monitor System Health"]
        UC10["Schedule Maintenance"]
    end
    
    subgraph BranchAdminUseCases["Branch Admin Use Cases"]
        UC11["Approve Patient Registrations"]
        UC12["Manage Record Requests"]
        UC13["View Branch Audit Logs"]
        UC14["Configure Branch Settings"]
    end
    
    subgraph RadTechUseCases["Radiologic Technician Use Cases"]
        UC15["Register Patients"]
        UC16["Create Cases"]
        UC17["Upload Images"]
        UC18["Search Patient Records"]
        UC19["View Queue"]
        UC20["Submit Record Requests"]
        UC21["Download Reports"]
    end
    
    subgraph RadiologistUseCases["Radiologist Use Cases"]
        UC22["Review Cases"]
        UC23["Enter Findings"]
        UC24["Write Impressions"]
        UC25["Generate Reports"]
        UC26["View Case History"]
    end
    
    subgraph PatientUseCases["Patient Use Cases"]
        UC27["Sign Up"]
        UC28["Login"]
        UC29["View Exam Status"]
        UC30["Download Report"]
        UC31["View History"]
    end
    
    AdminCentral --> AdminCentralUseCases
    ITAdmin --> ITAdminUseCases
    BranchAdmin --> BranchAdminUseCases
    RadTech --> RadTechUseCases
    Radiologist --> RadiologistUseCases
    Patient --> PatientUseCases
```

---

## 4. Data Flow Diagram (DFD)

### 4.1 Context Diagram (Level 0)

```mermaid
graph LR
    Patient["👤 Patients"]
    External["🏥 External Systems<br/>PhilHealth<br/>Other Branches"]
    AdminUsers["👥 System<br/>Administrators"]
    
    Patient -->|Register & Track Exams| CitiLife["🔷 CitiLife<br/>System"]
    External -->|Record Requests| CitiLife
    AdminUsers -->|Manage System| CitiLife
    
    CitiLife -->|Reports & Status| Patient
    CitiLife -->|Record Responses| External
    CitiLife -->|Audit Logs & Reports| AdminUsers
```

---

### 4.2 Level 1 Data Flow Diagram

```mermaid
graph TB
    Patient["👤 Patients"]
    AdminUsers["👥 Admin Staff"]
    RadTech["🔬 RadTech"]
    Radiologist["👨‍⚕️ Radiologist"]
    OtherBranches["🏥 Other Branches"]
    
    subgraph CitiLife["CitiLife System"]
        Auth["Authentication<br/>System"]
        PatientMgmt["Patient<br/>Management"]
        CaseMgmt["Case<br/>Management"]
        ImageUpload["Image<br/>Upload"]
        Reporting["Report<br/>Generation"]
        RecordReq["Record<br/>Request"]
        AuditLog["Audit<br/>Logs"]
        Notif["Notifications"]
    end
    
    DB["🗄️ Database"]
    
    Patient -->|Login Credentials| Auth
    Patient -->|Register Info| PatientMgmt
    Patient -->|View Status| CitiLife
    
    RadTech -->|Create Cases| CaseMgmt
    RadTech -->|Upload Images| ImageUpload
    RadTech -->|Request Records| RecordReq
    RadTech -->|Query Queue| CaseMgmt
    
    Radiologist -->|Review Cases| CaseMgmt
    Radiologist -->|View Images| ImageUpload
    Radiologist -->|Generate Reports| Reporting
    
    AdminUsers -->|Create Users| Auth
    AdminUsers -->|View Logs| AuditLog
    AdminUsers -->|Approve Requests| RecordReq
    
    OtherBranches -->|Record Requests| RecordReq
    OtherBranches -->|Receive Responses| RecordReq
    
    Auth -->|User Sessions| DB
    PatientMgmt -->|Patient Data| DB
    CaseMgmt -->|Case Data| DB
    ImageUpload -->|Image Paths| DB
    Reporting -->|Reports| DB
    RecordReq -->|Request Data| DB
    AuditLog -->|Log Records| DB
    
    Notif -->|Alerts| RadTech
    Notif -->|Alerts| Radiologist
    Notif -->|Alerts| Patient
    Notif -->|Alerts| AdminUsers
    
    CitiLife -->|Reports & Data| Patient
    CitiLife -->|Status Updates| Radiologist
```

---

### 4.3 Level 2 DFD - Patient Registration & Case Creation

```mermaid
graph TB
    Patient["👤 Patient/RadTech"]
    
    subgraph Registration["Patient Registration Process"]
        ValidateData["Validate Input<br/>Data"]
        CheckExists["Check Duplicate<br/>Email"]
        CreatePatient["Create Patient<br/>Record"]
        CreateUser["Create User<br/>Account"]
        LinkAccount["Link User to<br/>Patient"]
        NotifyAdmin["Notify Admin<br/>for Approval"]
    end
    
    subgraph Case["Case Creation Process"]
        CreateCase["Create Case<br/>Record"]
        GenerateCaseNo["Generate Case<br/>Number"]
        SetStatus["Set Exam Status<br/>to Pending"]
        AssignBranch["Assign to<br/>Branch"]
        NotifyRadTech["Notify RadTech<br/>Queue"]
    end
    
    DB["🗄️ Database"]
    Notifications["📬 Notification<br/>System"]
    
    Patient -->|Registration Data| ValidateData
    ValidateData -->|Valid| CheckExists
    CheckExists -->|New| CreatePatient
    CreatePatient -->|Patient ID| CreateUser
    CreateUser -->|User ID| LinkAccount
    LinkAccount -->|User + Patient| DB
    LinkAccount -->|Status: Pending| NotifyAdmin
    
    LinkAccount -->|Start Case Creation| CreateCase
    CreateCase -->|Generate ID| GenerateCaseNo
    GenerateCaseNo -->|Case Number| SetStatus
    SetStatus -->|Set Branch| AssignBranch
    AssignBranch -->|Case Data| DB
    AssignBranch -->|New Case Alert| NotifyRadTech
    NotifyRadTech -->|To Notification Queue| Notifications
```

---

## 5. Entity Relationship Diagram (ERD)

### 5.1 Mermaid ER Diagram

```mermaid
erDiagram
    USERS ||--o{ PATIENTS : "patient_id"
    USERS ||--o{ AUDIT_LOGS : "user_id"
    USERS ||--o{ NOTIFICATIONS : "user_id"
    USERS }o--|| BRANCHES : "branch_id"
    
    BRANCHES ||--o{ PATIENTS : "branch_id"
    BRANCHES ||--o{ CASES : "branch_id"
    BRANCHES ||--o{ RECORD_REQUESTS : "branch_id"
    BRANCHES ||--o{ AUDIT_LOGS : "branch_id"
    
    PATIENTS ||--o{ CASES : "patient_id"
    PATIENTS ||--o{ RECORD_REQUESTS : "patient_id"
    
    CASES ||--|| USERS : "radiologist_id"
    
    RECORD_REQUESTS }o--|| RECORD_REQUESTS : "related_records"
    
    USERS {
        int id PK
        string email UK
        string password
        string role FK
        int branch_id FK
        int patient_id FK
        timestamp created_at
        enum status
    }
    
    PATIENTS {
        int id PK
        string patient_number UK
        string first_name
        string last_name
        int age
        enum sex
        string contact_number
        int branch_id FK
        timestamp created_at
    }
    
    BRANCHES {
        int id PK
        string name UK
        timestamp created_at
    }
    
    CASES {
        int id PK
        string case_number UK
        int patient_id FK
        int branch_id FK
        string exam_type
        enum priority
        enum philhealth_status
        string philhealth_id
        enum status
        enum approval_status
        enum image_status
        string image_path
        boolean released
        text clinical_information
        text findings
        text impression
        text recommendation
        int radiologist_id FK
        datetime date_completed
        string report_template
        timestamp created_at
    }
    
    RECORD_REQUESTS {
        int id PK
        string patient_no FK
        string patient_name
        string exam_type
        string request_branch
        text reason
        int branch_id FK
        enum status
        timestamp created_at
    }
    
    AUDIT_LOGS {
        int id PK
        int user_id FK
        int branch_id FK
        string module
        string action
        string entity_type
        int entity_id
        text details
        string ip_address
        timestamp created_at
    }
    
    NOTIFICATIONS {
        int id PK
        int user_id FK
        string title
        string message
        string link
        boolean is_read
        timestamp created_at
    }
```

---

## 6. Database Schema Documentation

### 6.1 Table: USERS
**Purpose:** Stores system user accounts for all roles (admin_central, it_admin, branch_admin, radtech, radiologist, patient)

**Schema:**
| Column | Type | Key | Constraints | Purpose |
|--------|------|-----|-------------|---------|
| id | INT | PK | AUTO_INCREMENT | User identifier |
| email | VARCHAR(100) | UK | NOT NULL | Login credential |
| password | VARCHAR(255) | | NOT NULL | Hashed password |
| role | VARCHAR(50) | | NOT NULL | User role (admin_central, it_admin, branch_admin, radtech, radiologist, patient) |
| branch_id | INT | FK | NULLABLE | Branch assignment (NULL for system admins) |
| patient_id | INT | FK | NULLABLE | Link to patient record (for patient accounts) |
| status | ENUM | | DEFAULT 'Active' | Active / Pending / Rejected |
| created_at | TIMESTAMP | | DEFAULT NOW() | Account creation time |

**Relationships:**
- FK: `branch_id` → BRANCHES.id
- FK: `patient_id` → PATIENTS.id
- One User can have many AUDIT_LOGS
- One User can have many NOTIFICATIONS

---

### 6.2 Table: PATIENTS
**Purpose:** Stores patient demographic and identification information

**Schema:**
| Column | Type | Key | Constraints | Purpose |
|--------|------|-----|-------------|---------|
| id | INT | PK | AUTO_INCREMENT | Patient identifier |
| patient_number | VARCHAR(50) | UK | NULLABLE | Unique patient ID (format: PAT-BRANCH-YEAR-SEQ) |
| first_name | VARCHAR(100) | | NOT NULL | Patient first name |
| last_name | VARCHAR(100) | | NOT NULL | Patient last name |
| age | INT | | NOT NULL | Patient age |
| sex | ENUM('Male','Female') | | NOT NULL | Biological sex |
| contact_number | VARCHAR(50) | | NULLABLE | Phone number |
| branch_id | INT | FK | NULLABLE | Primary branch assignment |
| created_at | TIMESTAMP | | DEFAULT NOW() | Registration time |

**Relationships:**
- FK: `branch_id` → BRANCHES.id
- One Patient can have many CASES
- One Patient can have many RECORD_REQUESTS

---

### 6.3 Table: BRANCHES
**Purpose:** Defines organizational branch locations/facilities

**Schema:**
| Column | Type | Key | Constraints | Purpose |
|--------|------|-----|-------------|---------|
| id | INT | PK | AUTO_INCREMENT | Branch identifier |
| name | VARCHAR(100) | UK | NOT NULL | Branch name (Gapan, Bongabon, Peñaranda, etc.) |
| created_at | TIMESTAMP | | DEFAULT NOW() | Branch creation time |

**Sample Data:**
- Gapan, Bongabon, Peñaranda, General Tinio, Sto Domingo, San Antonio, Pantabangan

---

### 6.4 Table: CASES
**Purpose:** Tracks individual radiological examinations and their lifecycle

**Schema:**
| Column | Type | Key | Constraints | Purpose |
|--------|------|-----|-------------|---------|
| id | INT | PK | AUTO_INCREMENT | Case identifier |
| case_number | VARCHAR(50) | UK | NOT NULL | Unique case number |
| patient_id | INT | FK | NOT NULL | Patient being examined |
| branch_id | INT | FK | NULLABLE | Branch performing exam |
| exam_type | VARCHAR(100) | | NOT NULL | Type (X-Ray, CT, MRI, Ultrasound, etc.) |
| priority | ENUM | | DEFAULT 'Normal' | Normal / Priority / Emergency / Urgent / Routine |
| philhealth_status | ENUM | | NOT NULL | With PhilHealth Card / Without PhilHealth Card |
| philhealth_id | VARCHAR(50) | | NULLABLE | PhilHealth ID number |
| status | ENUM | | DEFAULT 'Pending' | Pending / Under Reading / Report Ready / Completed |
| approval_status | ENUM | | DEFAULT 'Pending' | Pending / Approved / Rejected |
| image_status | ENUM | | DEFAULT '—' | Uploaded / — |
| image_path | VARCHAR(255) | | NULLABLE | Storage path for images |
| released | TINYINT | | DEFAULT 0 | Boolean: Report released to patient |
| clinical_information | TEXT | | NULLABLE | Clinical context for exam |
| findings | TEXT | | NULLABLE | Radiological findings |
| impression | TEXT | | NULLABLE | Radiologist's interpretation |
| recommendation | TEXT | | NULLABLE | Follow-up recommendations |
| radiologist_id | INT | FK | NULLABLE | Assigned radiologist |
| date_completed | DATETIME | | NULLABLE | Completion timestamp |
| report_template | VARCHAR(100) | | NULLABLE | Report template used |
| created_at | TIMESTAMP | | DEFAULT NOW() | Case creation time |

**Relationships:**
- FK: `patient_id` → PATIENTS.id (REQUIRED)
- FK: `branch_id` → BRANCHES.id
- FK: `radiologist_id` → USERS.id

**Case Lifecycle:**
```
Pending → Under Reading → Report Ready → Completed
     ↓
  Rejected (approval_status)
```

---

### 6.5 Table: RECORD_REQUESTS
**Purpose:** Manages inter-branch patient record requests

**Schema:**
| Column | Type | Key | Constraints | Purpose |
|--------|------|-----|-------------|---------|
| id | INT | PK | AUTO_INCREMENT | Request identifier |
| patient_no | VARCHAR(50) | | NOT NULL | Patient number |
| patient_name | VARCHAR(255) | | NOT NULL | Patient full name |
| exam_type | VARCHAR(100) | | NULLABLE | Type of exam requested |
| request_branch | VARCHAR(100) | | NULLABLE | Destination branch name |
| reason | TEXT | | NOT NULL | Justification for request |
| branch_id | INT | FK | NULLABLE | Requesting branch |
| status | ENUM | | DEFAULT 'Pending' | Pending / Approved / Denied |
| created_at | TIMESTAMP | | DEFAULT NOW() | Request time |

**Relationships:**
- FK: `branch_id` → BRANCHES.id

**Workflow:**
```
Created (Pending) → [Branch Admin Reviews] → Approved or Denied
```

---

### 6.6 Table: AUDIT_LOGS
**Purpose:** Comprehensive activity logging for compliance and security

**Schema:**
| Column | Type | Key | Constraints | Purpose |
|--------|------|-----|-------------|---------|
| id | INT | PK | AUTO_INCREMENT | Log identifier |
| user_id | INT | FK | NULLABLE | User performing action |
| branch_id | INT | FK | NULLABLE | Branch context |
| module | VARCHAR(100) | | NULLABLE | System module (Patient, Case, User, etc.) |
| action | VARCHAR(100) | | NOT NULL | Action type (Create, Update, Delete, Approve, Reject) |
| entity_type | VARCHAR(100) | | NULLABLE | Entity affected (Patient, Case, User) |
| entity_id | INT | | NULLABLE | Entity ID |
| details | TEXT | | NULLABLE | Additional context/changes |
| ip_address | VARCHAR(50) | | NULLABLE | IP address of user |
| created_at | TIMESTAMP | | DEFAULT NOW() | Log timestamp |

**Relationships:**
- FK: `user_id` → USERS.id
- FK: `branch_id` → BRANCHES.id

---

### 6.7 Table: NOTIFICATIONS (Inferred from code)
**Purpose:** Stores user notifications for system events

**Likely Schema:**
| Column | Type | Key | Constraints | Purpose |
|--------|------|-----|-------------|---------|
| id | INT | PK | AUTO_INCREMENT | Notification ID |
| user_id | INT | FK | NOT NULL | Recipient user |
| title | VARCHAR(255) | | NOT NULL | Notification title |
| message | TEXT | | NOT NULL | Message content |
| link | VARCHAR(255) | | NULLABLE | Action link |
| is_read | TINYINT | | DEFAULT 0 | Read status |
| created_at | TIMESTAMP | | DEFAULT NOW() | Creation time |

**Relationships:**
- FK: `user_id` → USERS.id

---

## 7. Business Processes

### 7.1 Patient Registration Process

#### Scenario A: Portal-Based Patient Self-Registration
```
START
  ├─ Patient accesses /patient-signup
  ├─ Fills: Email, Password, Phone, Branch
  ├─ Email verification token sent
  ├─ Patient verifies email
  ├─ User account created (status: Pending)
  ├─ Notification: Branch Admin for approval
  ├─ Branch Admin reviews registration
  ├─ Admin approves/rejects
  ├─ If Approved:
  │   ├─ User status = Active
  │   ├─ Notification: "Account Approved - Login Now"
  │   └─ Patient can now register cases
  ├─ If Rejected:
  │   ├─ User status = Rejected
  │   └─ Notification: "Registration Rejected"
END
```

#### Scenario B: RadTech-Initiated Patient Registration
```
START
  ├─ RadTech accesses /patient-registration
  ├─ Enters: First Name, Last Name, Age, Sex, Contact, Branch
  ├─ Option 1: New Patient
  │   ├─ Patient record created (auto-generates patient_number)
  │   └─ Case created and assigned to Patient
  ├─ Option 2: Existing Patient
  │   ├─ Search existing patient in DB
  │   ├─ Select patient from results
  │   └─ Create case for existing patient
  ├─ Case status = Pending
  ├─ Image status = —
  ├─ Approval status = Pending
  ├─ AuditLog recorded
  └─ Case number displayed to RadTech
END
```

**Data Movement:**
- RadTech → System: Patient data + Exam info
- System → Database: PATIENTS record, USERS record (if portal), CASES record
- System → Notifications: Admin approval queue

---

### 7.2 Case Examination Workflow

```
START (Case Created by RadTech)
  ├─ Status: Pending
  ├─ Approval Status: Pending
  ├─ Image Status: —
  │
  ├─ RadTech uploads images
  │   ├─ Image Status: Uploaded
  │   ├─ Image Path stored in DB
  │   └─ AuditLog: "Image Upload"
  │
  ├─ Images ready for review
  │   ├─ Status → Under Reading
  │   └─ Notification: Radiologist new case
  │
  ├─ Radiologist reviews case
  │   ├─ Views: Patient info + Clinical data + Images
  │   ├─ Enters: Findings + Impression + Recommendation
  │   ├─ Status → Report Ready
  │   └─ AuditLog: "Report Generated"
  │
  ├─ Report approved (approval process)
  │   ├─ Approval Status → Approved
  │   ├─ Date Completed = NOW()
  │   └─ Released flag may be set
  │
  ├─ Report released to patient (if enabled)
  │   ├─ Released: 1
  │   ├─ Notification: Patient "Report Ready"
  │   └─ Patient can download
  │
  └─ Status → Completed
END
```

**Data Movement:**
- RadTech → System: Image files
- System: Stores paths in CASES.image_path
- Radiologist → System: Findings, Impression, Recommendation
- System → Database: CASES updates
- System → Notifications: Status alerts

---

### 7.3 Inter-Branch Record Request Workflow

```
START
  ├─ Source RadTech submits request
  │   ├─ Patient: Name, Number, Exam Type
  │   ├─ Reason: Why records needed
  │   ├─ Target Branch: Which branch has records
  │   └─ Status: Pending
  │
  ├─ Notification: Target Branch Admin
  │   ├─ "New Record Request from [Source Branch]"
  │   └─ Link: /record-requests
  │
  ├─ Target Branch Admin reviews
  │   ├─ Can view: Patient details, requested exam type
  │   ├─ Can: Approve or Deny
  │
  ├─ If Approved:
  │   ├─ Status: Approved
  │   ├─ Notification: Source Branch "Request Approved"
  │   └─ Records accessible/transferable
  │
  ├─ If Denied:
  │   ├─ Status: Denied
  │   ├─ Notification: Source Branch "Request Denied"
  │   └─ Reason: Justification for denial
  │
  └─ AuditLog: All approvals/denials recorded
END
```

**Data Movement:**
- Source RadTech → System: Record request
- System → Target Branch Admin: Notification + Request details
- Target Branch Admin → System: Approval decision
- System → Notifications: Notification to source branch

---

### 7.4 User & Role Management (Admin Central)

```
START
  ├─ Admin Central accesses /users
  ├─ Views: All staff users (non-patient)
  │   ├─ Email, Role, Branch, Status, Created Date
  │
  ├─ Create New User:
  │   ├─ Email (unique), Password, Role, Branch
  │   ├─ Password hashed (bcrypt)
  │   ├─ Status: Active
  │   └─ AuditLog: "User Created"
  │
  ├─ Update User:
  │   ├─ Email, Role, Branch, Password (optional)
  │   └─ AuditLog: "User Updated"
  │
  ├─ Delete User:
  │   ├─ Cannot delete admin_central
  │   └─ AuditLog: "User Deleted"
  │
  ├─ Patient Account Approval:
  │   ├─ View: Pending patient registrations
  │   ├─ Action: Approve → Status = Active
  │   ├─ Action: Reject → Status = Rejected
  │   ├─ Automatic: Reject all pending cases if account rejected
  │   └─ AuditLog: "Patient [Action]"
  │
  └─ Notifications sent to patients on approval/rejection
END
```

---

## 8. System Flow: Complete Workflow

### 8.1 End-to-End: Patient Registration to Report Download

```
ACTOR: Patient (Portal User)
  ├─ Accesses: https://citilife.com/patient-signup
  ├─ Enters: Email, Password, Phone, Branch
  ├─ System: Validates email uniqueness
  ├─ System: Sends verification email
  ├─ Patient: Clicks verification link
  ├─ System: Creates User account (status: Pending)
  └─ System: Notification → Branch Admin for approval

ACTOR: Branch Administrator
  ├─ Sees: Notification "New Patient Registration"
  ├─ Accesses: /patient-approvals
  ├─ Reviews: Patient email, branch, created date
  ├─ Actions: Approve or Reject
  ├─ If Approved:
  │   ├─ User status → Active
  │   └─ Notification → Patient "Account Approved"
  └─ Patient can now use portal

ACTOR: Patient (Returning)
  ├─ Accesses: /patient-login
  ├─ Enters: Email, Password
  ├─ System: Validates credentials
  ├─ Accesses: /registration (Patient Portal)
  ├─ Enters: Exam Type, PhilHealth Status, Clinical Info
  ├─ System: Creates Case (Pending status)
  ├─ System: Notification → RadTech "New Case Created"
  └─ Status displayed: "Pending Examination"

ACTOR: Radiologic Technician
  ├─ Sees: Notification "New Case from [Patient]"
  ├─ Accesses: /patient-queue or /case-status
  ├─ Finds: Case by patient name or case number
  ├─ Uploads: Radiological images
  ├─ System: image_status → Uploaded
  ├─ System: Case status → Under Reading
  └─ Notification → Radiologist "Images Ready for Review"

ACTOR: Radiologist
  ├─ Sees: Notification "Case [Case Number] Ready"
  ├─ Accesses: /case-review
  ├─ Views: Patient info, clinical data, uploaded images
  ├─ Enters: Findings, Impression, Recommendations
  ├─ System: Case status → Report Ready
  ├─ System: approval_status → Pending (for final approval)
  ├─ Saves report
  └─ System: AuditLog recorded

ACTOR: Patient
  ├─ Checks: /my-records
  ├─ Sees: Case status "Report Ready"
  ├─ Accesses: /view-report or /download-report
  ├─ Downloads: PDF radiology report
  ├─ Report includes: Findings, Impression, Recommendations, Date
  └─ Saves locally or prints

AUDIT SYSTEM
  ├─ Logs all actions: Registration, Case creation, Image upload
  ├─ Logs: Report generation, Download, User actions
  ├─ Accessible to: Admin Central via /audit-logs
  ├─ Filters: By user, role, module, date range
  └─ Compliance: Full traceability maintained
```

---

### 8.2 Authentication & Authorization Flow

```
START: User Access
  ├─ GET /login (guest-only)
  ├─ POST /login with email + password
  │   ├─ System: Query users table by email
  │   ├─ System: Verify password hash
  │   ├─ If valid:
  │   │   ├─ Session: Set user_id, email, role, branch_id
  │   │   ├─ AuditLog: Login attempt
  │   │   └─ Redirect: /dashboard
  │   └─ If invalid:
  │       ├─ AuditLog: Failed login
  │       └─ Redirect: /login with error
  │
  ├─ Session middleware checks:
  │   ├─ $_SESSION['user_id'] exists?
  │   ├─ If NO: Redirect to /login
  │   └─ If YES: Continue
  │
  ├─ Page Controller (PageController@dispatch):
  │   ├─ Determine role from session
  │   ├─ Check page permission via RBAC
  │   ├─ Load role-specific controller
  │   ├─ Execute handler()
  │   └─ Load role-specific view
  │
  ├─ RBAC Guard checks:
  │   ├─ 'users' page → 'user_mgmt' permission
  │   ├─ 'audit-logs' page → 'audit_logs' permission
  │   ├─ 'backup-maintenance' page → 'backup_mgmt' permission
  │   ├─ Restrict non-admin access
  │   └─ Throw 403 if unauthorized
  │
  └─ GET /logout:
      ├─ Destroy session
      ├─ AuditLog: Logout
      └─ Redirect: /login
```

---

## 9. Implemented Features (VALIDATED)

### 9.1 Core Modules

#### ✅ Authentication Module
- Email-based login for all roles
- Patient self-registration with email verification
- Password reset functionality
- OTP login capability (route exists)
- Session management
- Logout functionality

#### ✅ Patient Management
- Patient registration (manual by RadTech, self-service via portal)
- Patient search by name/patient number
- Patient record viewing
- Unique patient number generation (format: PAT-BRANCH-YEAR-SEQ)
- Patient information updates

#### ✅ Case Management
- Case creation with priority levels
- PhilHealth status tracking
- Image status tracking
- Case status workflow (Pending → Under Reading → Report Ready → Completed)
- Case approval/rejection
- Case numbering system

#### ✅ Image Upload & Management
- Radiological image upload
- Image path storage
- Image status flagging
- DICOM/image file handling

#### ✅ Radiological Reporting
- Findings entry
- Impression/interpretation recording
- Recommendations documentation
- Report generation
- Report approval workflow
- Patient report download/viewing

#### ✅ User & Role Management
- Role-based access control (6 roles)
- Branch-level user assignment
- User approval/rejection
- Staff account creation, update, deletion
- Patient account management

#### ✅ Audit Logging
- Comprehensive activity logging
- User, action, module, entity tracking
- IP address recording
- Filtering by role, module, date range
- Audit log viewing (admin-only)

#### ✅ Notifications System
- Real-time event notifications
- Notification routing by role and branch
- Links to relevant actions
- Notification history

#### ✅ Inter-Branch Communication
- Record request submission
- Record request approval/denial
- Branch-level request management
- Request history and tracking

#### ✅ Backup & Maintenance
- Backup management page accessible to IT Admin
- System maintenance capabilities

---

### 9.2 Incomplete/Partial Features

#### ⚠️ Appointment Scheduling
- Routes defined (`patient-queue`, `patient-history`)
- Database tables prepared (cases table)
- **Status:** UI implementation may be incomplete

#### ⚠️ Announcements System
- Not found in database schema
- No models or controllers identified
- **Status:** Not implemented

#### ⚠️ Billing/Payment System
- Not found in database schema
- No models or controllers identified
- **Status:** Not implemented

---

## 10. Technical Architecture

### 10.1 Framework & Technology Stack

**Backend:**
- Language: PHP 8.2.12
- Framework: Custom lightweight MVC framework (not Laravel, Symfony)
- Database: MySQL 10.4.32-MariaDB
- Composer dependencies:
  - dompdf/dompdf (PDF generation)
  - phpoffice/phpspreadsheet (Excel handling)
  - phpmailer/phpmailer (Email services)

**Frontend:**
- CSS Framework: Tailwind CSS
- Build Tool: PostCSS
- Node version: Modern (uses npm)

**Project Structure:**
```
CitiLife-System/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php (main dispatcher)
│   │   ├── PageController.php (page routing & RBAC)
│   │   ├── admin_central/ (admin-specific controllers)
│   │   ├── branch_admin/ (branch admin controllers)
│   │   ├── radtech/ (technician controllers)
│   │   ├── radiologist/ (radiologist controllers)
│   │   ├── patient/ (patient portal controllers)
│   │   └── it_admin/ (IT admin controllers)
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── PatientModel.php
│   │   ├── CaseModel.php (34KB, most complex)
│   │   ├── AuditLogModel.php
│   │   ├── RecordRequestModel.php
│   │   ├── BranchModel.php
│   │   └── NotificationModel.php
│   ├── Helpers/
│   │   ├── AuthHelper.php (RBAC functions)
│   │   ├── mailer_helper.php
│   │   ├── notification_helper.php
│   │   └── patient_helper.php
│   └── Api/ (legacy API endpoints)
├── views/
│   ├── layouts/dashboard.php (main layout)
│   ├── pages/ (role-based views)
│   ├── components/ (reusable UI components)
│   ├── errors/ (error pages)
│   └── partials/ (header, sidebar, etc.)
├── models/ (models directory, duplicated structure)
├── config/ (configuration files)
├── storage/ (file uploads, backups)
├── Framework/ (custom framework code)
├── routes.php (URL routing)
├── helpers.php (global helpers: loadView, redirect)
├── index.php (entry point)
├── citilife_db.sql (database dump)
└── package.json / composer.json (dependencies)
```

### 10.2 Routing System

**Type:** Custom router (similar to Laravel-style routing)

**Route Examples:**
```php
$router->get('/login', 'AuthController@login', ['guest']);
$router->post('/patient-signup', 'AuthController@patientSignup', ['guest']);
$router->get('/dashboard', 'PageController@dispatch', ['auth']);
$router->post('/app/api/notifications.php', 'App/Api/notifications.php');
```

**Middleware:**
- `['guest']` - Only unauthenticated users
- `['auth']` - Requires authenticated session

---

### 10.3 Database Connection

- **Method:** PDO (PHP Data Objects)
- **Global Variable:** `$pdo` injected into controllers
- **Prepared Statements:** Used throughout for SQL injection prevention
- **Transactions:** Used for multi-step operations

---

## 11. Security Observations

### 11.1 Implemented Security Measures ✅
1. **Password Hashing:** bcrypt (PASSWORD_DEFAULT)
2. **SQL Injection Prevention:** Prepared statements with parameter binding
3. **Session Management:** PHP sessions with user_id tracking
4. **RBAC:** Role-based access control with guardPermission() checks
5. **Audit Logging:** Complete activity tracking with IP recording
6. **Email Verification:** Token-based for patient registration
7. **Status-Based Access:** Users must be 'Active' to access system

### 11.2 Potential Security Considerations
1. **Session Timeout:** Not explicitly defined (session.gc_maxlifetime check needed)
2. **CSRF Protection:** No CSRF tokens observed in visible code
3. **Rate Limiting:** Not visible in current implementation
4. **File Upload Validation:** Image upload validation extent unclear
5. **XSS Prevention:** Depends on view layer sanitization (not visible)

---

## 12. Database Relationships Summary

| Relationship | Type | Tables | Cardinality |
|---|---|---|---|
| User → Patient | Link | USERS.patient_id → PATIENTS.id | 1:1 (for patient role) |
| User → Branch | Assignment | USERS.branch_id → BRANCHES.id | N:1 |
| User → Audit Logs | Records | USERS.id → AUDIT_LOGS.user_id | 1:N |
| User → Notifications | Receives | USERS.id → NOTIFICATIONS.user_id | 1:N |
| Patient → Cases | Examination | PATIENTS.id → CASES.patient_id | 1:N |
| Patient → Record Requests | Requests | PATIENTS.id → RECORD_REQUESTS.patient_id | 1:N |
| Case → Radiologist | Assignment | CASES.radiologist_id → USERS.id | N:1 |
| Case → Branch | Location | CASES.branch_id → BRANCHES.id | N:1 |
| Branch → Audit Logs | Context | BRANCHES.id → AUDIT_LOGS.branch_id | 1:N |
| Record Requests → Branch | Initiator | RECORD_REQUESTS.branch_id → BRANCHES.id | N:1 |

---

## 13. API Endpoints (Legacy)

**Discovered Legacy API Routes:**
```
GET/POST /app/api/case_activity.php       - Case activity tracking
GET/POST /app/api/notifications.php       - Notification retrieval/updates
GET/POST /app/api/search_branch_cases.php - Branch case search
POST     /app/api/update_profile.php      - User profile updates
POST     /app/api/request_password_reset.php - Password reset requests
POST     /app/config/update_patient.php   - Patient data updates
```

---

## 14. Code Quality & Conventions

### 14.1 Observed Patterns
- **Namespace Usage:** PSR-4 autoloading (App\Controllers, App\Models)
- **Method Names:** camelCase (getUserById, updatePatientStatus)
- **Exception Handling:** Try-catch blocks in transaction-heavy methods
- **Query Building:** Raw SQL with parameterized queries (no ORM)
- **Error Handling:** HTTP redirects and message passing via session/GET

### 14.2 Code Organization
- **Separation of Concerns:** Models handle DB, Controllers handle requests, Views handle display
- **Reusable Components:** Helpers for auth, notifications, patient operations
- **Role-Based Structure:** Controllers organized by role subdirectories
- **Consistency:** PDO prepared statements used consistently

---

## 15. Deployment & Configuration

**Environment Setup:**
- PHP 8.2+
- MySQL/MariaDB 10.4+
- Node.js for frontend build tools
- Composer for PHP dependencies

**Configuration Files:**
- `.htaccess` - Apache rewrite rules
- `tailwind.config.js` - Tailwind CSS configuration
- `postcss.config.js` - CSS processing configuration
- `package.json` - Node dependencies

**Database Setup:**
- Import `citilife_db.sql` to create all tables
- Default data: Branches, Demo users (admin, radiologist, technicians)

---

## 16. Glossary & Key Terms

| Term | Definition |
|------|-----------|
| **RadTech** | Radiologic Technician - conducts examinations, uploads images |
| **Radiologist** | Medical specialist - interprets images, generates reports |
| **Branch Admin** | Local branch administrator - approves patients, manages records |
| **Admin Central** | System-wide administrator - manages users, branches, global settings |
| **Case** | Individual radiological examination record |
| **PhilHealth** | Philippine Health Insurance Corporation - insurance tracking |
| **RBAC** | Role-Based Access Control - permission system |
| **Audit Log** | Complete record of system activities for compliance |
| **Record Request** | Inter-branch request for patient imaging records |

---

## 17. Conclusion

The **CitiLife-System** is a well-structured, multi-tenant radiology management platform serving multiple healthcare branches. The implementation demonstrates:

✅ **Strengths:**
- Complete RBAC implementation with 6 distinct roles
- Comprehensive audit logging for compliance
- Proper database normalization and relationships
- Clean MVC separation of concerns
- Role-based page access controls
- Transaction handling for critical operations
- Notification system for user engagement
- Inter-branch record request workflow

⚠️ **Areas for Enhancement:**
- Announcement/messaging system (not yet implemented)
- Billing/payment module (not yet implemented)
- Complete appointment scheduling UI
- Consider ORM adoption (Doctrine, Eloquent) for complex queries
- Add CSRF token protection
- Implement session timeout handling
- Enhance input validation on all endpoints

**Overall Assessment:** The system successfully implements core radiology management functionality with proper security measures, role-based access control, and audit compliance. The architecture is maintainable and extensible for future enhancements.

---

*Document prepared based on analysis of source code, database schema, routing configuration, and controller/model implementations. All documented features are based solely on implemented code, not assumptions or potential features.*

**Analysis Date:** June 30, 2026  
**Codebase Status:** Updated to latest commit on `updated-features` branch
