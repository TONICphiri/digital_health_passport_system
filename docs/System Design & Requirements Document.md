# MUBAS  
## Malawi University of Business and Applied Sciences  

# SYSTEM DESIGN & REQUIREMENTS  
# DOCUMENT  

## Digital Health Passport System

**Prepared by:** Tonic Adam Phiri  
**Programme:** Bachelor of Information Systems / Management Information Systems  
**Department:** Computer Science and Information Systems  
**Academic Year:** 2025 – 2026  
**Version:** 1.0  

---

# 1. Executive Summary

Ndirande Community Hospital currently relies heavily on paper-based health passports for recording and retrieving patient health information. Patients are required to carry their physical health passports whenever they visit the hospital. These records can easily be lost, damaged, misplaced or become difficult to read over time. The paper-based approach also makes it difficult for authorized health personnel to access a patient's previous medical information efficiently.

The proposed **Digital Health Passport System (DHPS)** is a web-based application designed to digitally manage patient health information and provide a secure, accessible and organized alternative to the traditional paper health passport.

The system will allow authorized health personnel to register patients, record clinical information, manage vaccinations, laboratory results, medical certificates and other relevant health records. Patients will be able to access their own health information through a secure patient account.

A key feature of the system is the **digital health passport QR credential**. The QR credential allows authorized verification of a patient's digital health credential without exposing the patient's complete medical history. The system will therefore separate health-record access from credential verification.

The system will use **Laravel and PHP** for the application logic, **MySQL 8.x** for data storage, **Blade** for the web interface and **Tailwind CSS** for responsive user-interface design. Development will follow the **Rapid Application Development (RAD)** methodology, allowing the system to be developed iteratively and improved through continuous feedback.

The prototype is designed primarily for a registered health-facility environment and is not intended to replace national Electronic Medical Record (EMR), Electronic Health Record (EHR), civil-registration or national health-information systems.

---

# 2. Problem Statement

The existing paper-based health passport process creates several challenges in the management and continuity of patient health information:

- Paper health passports can be lost, damaged or destroyed.
- Previous medical information may not be immediately available when the physical passport is unavailable.
- Health workers must manually read and update patient records.
- Paper records can become difficult to read as information accumulates.
- Sharing health information between authorized health personnel is difficult.
- Patients may have to repeat information or investigations when previous records are unavailable.
- Paper records provide limited visibility into who accessed or changed patient information.
- Health credentials and certificates can be difficult to verify and may be vulnerable to forgery.
- Maintaining and retrieving large volumes of paper records requires physical storage and manual searching.

These challenges create the need for a secure digital system that can organize patient information, improve accessibility for authorized users and provide verifiable digital health credentials.

---

# 3. Project Objectives

The objectives of the Digital Health Passport System are:

1. To analyse the existing paper-based health passport process and identify its major challenges and user requirements.

2. To design a web-based Digital Health Passport System using Laravel, PHP, MySQL, Blade and Tailwind CSS.

3. To develop a centralized digital repository for authorized patient health records.

4. To provide role-based access so that patients, practitioners, facility administrators and other authorized users can access only the information appropriate to their responsibilities.

5. To provide digital management of health records including vaccinations, laboratory results and medical certificates.

6. To provide secure QR-based digital health credentials that can be verified without exposing the patient's complete medical history.

7. To maintain an audit trail of important system activities such as enrollment, record creation, approval and credential verification.

8. To evaluate the developed prototype in terms of functionality, usability and user acceptance.

---

# 4. System Overview

The Digital Health Passport System is a web-based application accessed through a modern web browser. It provides different interfaces depending on the role of the logged-in user.

The system maintains a central user catalogue linked to a verified **National Identity Number (NIN)**. Users are enrolled through a registered health facility rather than through unrestricted public self-registration.

The system manages the lifecycle of a patient's digital health information from enrollment through clinical record creation, record approval, patient access and digital credential verification.

## 4.1 User Roles

| Role | Description | Key Responsibilities |
|---|---|---|
| **Super Admin** | Highest-level system administrator | Manage system security policies, backups and overall system configuration |
| **System Admin** | System administrator | Manage users, roles, reference data, audit logs and system information |
| **Facility Admin** | Administrator of a health facility | Approve enrollments, manage practitioners and perform identity services |
| **Doctor / Practitioner** | Authorized health worker | View authorized patient records and create or approve health records |
| **Patient** | Owner of the digital health passport | View personal health records, manage QR credentials and appointments |
| **Verifier** | Third-party credential checker | Scan and verify a patient's QR health credential |

---

# 5. Full System Workflow

## Step 1 — Facility Setup

The system administrator establishes registered health facilities and creates the required administrative and practitioner accounts.

Facility administrators can then manage practitioners associated with their facility.

---

## Step 2 — Patient Enrollment

A patient visits a registered health facility to create an account.

The authorized enrolling staff member:

- Captures the patient's National Identity Number.
- Captures the patient's personal information.
- Records contact information.
- References the national ID document.
- Physically verifies the identity document.
- Records the staff member responsible for verification.
- Submits the enrollment.

The system checks whether the NIN has already been registered.

If the NIN already exists, the system prevents creation of a duplicate identity.

New accounts enter a **Pending Approval** state.

---

## Step 3 — Enrollment Approval

The Facility Admin reviews pending enrollments.

The administrator can:

- Approve the enrollment.
- Reject the enrollment.
- Provide a reason for rejection.

Every approval or rejection is recorded in the audit log.

Once approved, the patient receives activation instructions.

---

## Step 4 — Patient Account Activation

The patient uses the provided activation mechanism to access the system for the first time.

The patient:

- Sets a personal password.
- May configure two-factor authentication where enabled.
- Completes account activation.

The account then becomes active.

---

## Step 5 — Patient Login

Users access the system using:

**National Identity Number + Password**

The system verifies the supplied identity information and password before granting access.

After successful authentication, the user is redirected to the dashboard appropriate to their role.

---

## Step 6 — Patient Health Record

The patient can access their own digital health passport.

The patient dashboard provides access to:

- Personal information.
- Vaccination history.
- Laboratory results.
- Medical certificates.
- Other approved health records.
- Appointments.
- Notifications.
- Digital QR credential.

Patients can only access their own records.

---

## Step 7 — Practitioner Retrieves Patient

A practitioner can search for a patient using the patient's NIN.

Before accessing a patient's record, the system applies the appropriate authorization and consent rules.

Where consent is required, the patient grants permission for the practitioner to access the relevant health information.

The access event is recorded.

---

## Step 8 — Practitioner Records Health Information

Authorized practitioners can create health records such as:

- Vaccination records.
- Laboratory test results.
- Medical certificates.
- Other approved health records.

The practitioner provides the required information and submits the record.

Records may enter a pending state before approval.

---

## Step 9 — Record Approval

The authorized practitioner reviews and approves or rejects pending health records.

Approved records become part of the patient's digital health passport.

The system records the practitioner responsible for the record and the relevant timestamp.

---

## Step 10 — QR Credential Generation

The system generates a digital QR credential associated with the patient's verified identity.

The QR credential contains a secure signed token rather than exposing the patient's complete medical history.

The patient can:

- View the QR credential.
- Share it.
- Print it.
- Revoke it where applicable.

Credentials may expire and can be rejected when revoked or expired.

---

## Step 11 — QR Credential Verification

A verifier accesses the verification portal and either:

- Scans the patient's QR credential, or
- Enters the credential identifier manually.

The system validates the credential's signature, status and expiry.

The verifier receives only verification-level information such as:

- Holder name.
- Credential type.
- Validity status.
- Expiry date.
- Issuing authority.

The verifier does **not** receive the patient's complete medical history.

Each verification event is recorded in the system.

---

## Step 12 — Appointment Management

Patients can:

- View available appointments.
- Book appointments.
- Reschedule appointments.
- Cancel appointments.

Practitioners can manage their appointment schedules and update appointment statuses.

---

## Step 13 — Notifications

The system provides notifications relating to:

- Enrollment approval.
- Account activation.
- New health records.
- Appointments.
- Password changes.
- QR credential expiry.
- Other important account events.

---

## Step 14 — Audit Logging

Important activities are recorded in the audit trail.

Examples include:

- Login attempts.
- Patient enrollment.
- Enrollment approval.
- Health-record creation.
- Health-record modification.
- Health-record approval.
- QR credential generation.
- QR verification.
- Account changes.

The audit trail helps provide accountability and supports security monitoring.

---

# 6. Technology Stack

The system will use the following technologies:

| Technology | Purpose |
|---|---|
| **Laravel** | Main web application framework and system logic |
| **PHP 8.x** | Server-side programming language |
| **MySQL 8.x** | Relational database management system |
| **Blade** | Laravel's server-side templating engine for web pages |
| **Tailwind CSS** | Responsive and utility-based interface design |
| **JavaScript / Alpine.js** | Client-side interactions and dynamic interface behaviour |
| **Eloquent ORM** | Database interaction within Laravel |
| **Laravel Authentication** | User authentication and account management |
| **Role/Permission System** | Role-based access control |
| **QR Code Technology** | Digital credential generation and verification |
| **Laravel Jobs/Queues** | Background processing such as notifications and PDF generation |
| **PDF Generation** | Printable health certificates and reports |
| **PHPUnit** | Automated application testing |
| **Laravel Dusk** | Browser-based testing of important workflows |
| **Git/GitHub** | Source-code version control |

The **core development stack is Laravel, PHP, MySQL, Blade and Tailwind CSS**.

---

# 7. Database Design

The system uses **MySQL 8.x** as its relational database.

The main database entities include:

### users

Stores system user accounts and identity information.

Key information includes:

- User ID
- NIN hash
- Last four digits of NIN
- Full name
- Date of birth
- Gender
- Phone
- Email
- Password
- Account status
- Facility
- Enrollment information
- Approval information

### facilities

Stores registered health facilities.

### practitioners

Stores practitioner-specific information such as:

- Practitioner identity
- Facility
- Professional licence number
- Specialty

### patients

Stores patient-specific information linked to the user account.

### health_records

Stores the patient's health records.

### vaccinations

Stores vaccination-specific information.

### test_results

Stores laboratory test information.

### qr_credentials

Stores patient QR credentials, their status and expiry information.

### verification_logs

Records QR credential verification activities.

### consents

Records permissions granted by patients to practitioners.

### appointments

Stores patient-practitioner appointment information.

### notifications

Stores system notifications.

### audit_logs

Stores important system activities for accountability and security.

### reference tables

The system also contains reference data such as:

- Vaccine types
- Test types
- Credential templates
- Facilities
- Announcements

---

# 8. Main Database Relationships

The major relationships are:

- One **User** can have one Patient or Practitioner profile.
- One **Patient** can have many Health Records.
- One **Health Record** can represent a vaccination, laboratory result or certificate.
- One **Patient** can have multiple QR credentials.
- One **QR Credential** can have multiple verification records.
- One **Patient** can have multiple Appointments.
- One **Patient** can grant consent to multiple Practitioners.
- One **Facility** can have multiple Users.
- One **User** can perform multiple audit activities.

The system uses primary keys, foreign keys, unique constraints and indexes to maintain data integrity and improve database performance.

---

# 9. Role-Based Access Control

The system implements Role-Based Access Control (RBAC).

Users are assigned permissions according to their responsibilities.

For example:

| Function | Super Admin | System Admin | Facility Admin | Practitioner | Patient | Verifier |
|---|---:|---:|---:|---:|---:|---:|
| Manage system security | ✓ | | | | | |
| Manage users | ✓ | ✓ | ✓ | | | |
| Approve enrollment | ✓ | ✓ | ✓ | | | |
| Manage practitioners | | | ✓ | | | |
| Create health records | | | | ✓ | | |
| View own records | | | | | ✓ | |
| Approve health records | | | | ✓ | | |
| Manage own QR | | | | | ✓ | |
| Verify QR | | | | | | ✓ |
| View audit logs | ✓ | ✓ | Limited | | | |
| Manage appointments | | | | ✓ | ✓ | |

Authorization is enforced on the server side. Hiding a button in the interface is not treated as sufficient security.

---

# 10. System Architecture

The system follows a layered Laravel MVC architecture with a service layer.

```text
        User
         |
         v
 Browser / Mobile Web
         |
       HTTPS
         |
         v
 Laravel Routes & Middleware
         |
   Authentication / RBAC
         |
         v
     Controllers
         |
    Form Requests
         |
         v
    Service Layer
         |
   Policies / Gates
         |
         v
    Eloquent Models
         |
         v
       MySQL
```

### Presentation Layer

The presentation layer uses:

- Laravel Blade
- Tailwind CSS
- JavaScript / Alpine.js

### Application Layer

The application layer contains controllers and services responsible for business processes.

Examples include:

- Enrollment Service
- NIN Lookup Service
- Credential Service
- Verification Service
- Audit Service
- Notification Service

### Data Layer

The data layer uses:

- Eloquent ORM
- Laravel migrations
- MySQL 8.x
- Foreign keys
- Database indexes
- Transactions

---

# 11. Security Design

Security is a major requirement because the system handles personal and health information.

The system will implement:

- HTTPS for communication.
- Secure password hashing.
- Role-based access control.
- Server-side authorization.
- NIN protection.
- Input validation.
- CSRF protection.
- XSS protection through Blade escaping.
- SQL injection protection through Eloquent and parameterized queries.
- Secure QR credentials.
- Audit logging.
- Session timeout.
- Login rate limiting.
- Account lockout after repeated failed login attempts.
- Secure storage of uploaded documents.
- Minimum-necessary information for QR verification.

The raw NIN is not intended to be stored directly. A keyed HMAC-SHA256 hash is used for identity lookup while the displayed NIN is masked.

---

# 12. Website Design

The website will follow a **mobile-first responsive design**.

## Main Pages

### Public Landing Page

Provides:

- System introduction.
- Login access.
- Credential verification access.
- Information about the Digital Health Passport.

### Login Page

Contains:

- NIN field.
- Password field.
- Password visibility control.
- Authentication messages.
- Account activation/reset options.

### Patient Dashboard

Displays:

- Patient name.
- Digital passport.
- QR credential.
- Recent health records.
- Upcoming appointments.
- Notifications.

### Practitioner Dashboard

Displays:

- Today's appointments.
- Patient search.
- Pending records.
- Record creation functions.

### Facility Admin Dashboard

Displays:

- Pending enrollments.
- Practitioners.
- Facility statistics.
- Identity services.

### Verifier Portal

Provides:

- QR scanner.
- Manual credential lookup.
- Verification result.
- Credential status.
- Expiry information.
- Verification history.

### System Administration Dashboard

Provides:

- User management.
- Roles and permissions.
- Reference data.
- Audit logs.
- System statistics.
- Security settings.

---

# 13. Key Features and Innovations

## 13.1 Digital Patient Health Passport

The system provides a digital alternative to the traditional paper health passport by organizing a patient's approved health information electronically.

## 13.2 NIN-Based Identity

The National Identity Number provides a unique identity reference for users and prevents duplicate accounts.

## 13.3 Facility-Based Enrollment

Patients cannot freely create accounts without identity verification. Enrollment is performed through a registered facility.

## 13.4 QR-Based Health Credential

Patients receive a secure QR credential that can be verified without exposing their complete medical history.

## 13.5 Consent-Based Access

Practitioner access to patient information can be controlled through patient consent and authorization rules.

## 13.6 Audit Trail

Important system activities are recorded, providing accountability and supporting security investigations.

## 13.7 Role-Based Dashboards

Each user receives an interface appropriate to their role and responsibilities.

## 13.8 Digital Health Certificates

The system can generate printable digital certificates containing QR-based verification information.

---

# 14. Non-Functional Requirements

| Requirement | Description |
|---|---|
| **Security** | Patient information must be protected through authentication, authorization and encryption mechanisms. |
| **Accessibility** | The system should be accessible through modern web browsers. |
| **Responsiveness** | The interface should work on desktops, tablets and mobile devices. |
| **Performance** | Core pages and patient searches should respond within an acceptable period under student-scale usage. |
| **Reliability** | Database transactions should protect important multi-step operations. |
| **Usability** | The system should provide simple and understandable interfaces for different user roles. |
| **Privacy** | Users should only access information appropriate to their permissions. |
| **Auditability** | Important system activities should be recorded with user and timestamp information. |
| **Maintainability** | Laravel conventions, structured services and database migrations should be used to simplify maintenance. |
| **Compatibility** | The system should support current versions of major web browsers. |

---

# 15. Testing Strategy

The system will be tested at several levels.

### Functional Testing

Testing will verify that individual system functions operate according to requirements.

Examples:

- User registration.
- Login.
- Enrollment approval.
- Patient search.
- Health-record creation.
- QR generation.
- QR verification.
- Appointment management.

### Security Testing

Security tests will include:

- Invalid login attempts.
- Account lockout.
- Unauthorized page access.
- Patient record access by another patient.
- SQL injection attempts.
- Cross-site scripting attempts.
- CSRF protection.
- QR credential manipulation.
- Duplicate NIN registration.

### Usability Testing

The system will be evaluated with intended or representative users to determine whether the interface is understandable and easy to use.

The **System Usability Scale (SUS)** may be used to obtain a standardized usability score.

### User Acceptance Testing

Representative users will perform important workflows such as:

1. Patient enrollment.
2. Enrollment approval.
3. Patient login.
4. Practitioner record creation.
5. Patient record viewing.
6. QR credential verification.

---

# 16. Development Methodology — RAD

The project will use **Rapid Application Development (RAD)**.

## Phase 1 — Requirements Planning

Activities include:

- Understanding the existing health passport process.
- Gathering user requirements.
- Defining system scope.
- Identifying system users.
- Defining functional and non-functional requirements.

## Phase 2 — User Design

Activities include:

- Interface design.
- Database design.
- System architecture.
- Role and permission design.
- Workflow design.

## Phase 3 — Rapid Construction

The system will be developed incrementally.

### Iteration 1

- Database structure.
- Authentication.
- User catalogue.
- Facility enrollment.
- RBAC.

### Iteration 2

- Patient profiles.
- Health records.
- Vaccination records.
- Laboratory records.
- Practitioner workflows.

### Iteration 3

- QR credentials.
- Credential verification.
- Appointments.
- Notifications.

### Iteration 4

- Audit logs.
- Reports.
- Security hardening.
- Usability improvements.
- User acceptance testing.

## Phase 4 — Cutover

Activities include:

- Final testing.
- Defect correction.
- Demonstration.
- Deployment.
- User documentation.
- Final evaluation.

---

# 17. Constraints and Assumptions

The student prototype has the following constraints:

- The prototype is primarily designed for a registered health-facility environment.
- The system does not integrate directly with the national civil registry.
- The system does not replace a national EMR/EHR infrastructure.
- Native Android/iOS applications are outside the initial prototype.
- Payment and insurance functionality are outside the project scope.
- The availability of internet connectivity may affect access to the web application.
- National-scale deployment would require additional infrastructure, security assessment and government-level integration.

---

# 18. Future Enhancements

Possible future improvements include:

- Integration with the national civil registration system for real-time identity verification.
- Integration with national health-information systems.
- Inter-facility health-record sharing.
- Native Android and iOS applications.
- SMS and push notifications.
- Advanced biometric authentication.
- National-scale QR credential verification.
- Integration with health-insurance systems.
- Offline synchronization for facilities with unreliable internet connectivity.
- Integration with additional healthcare facilities.

---

# 19. Conclusion

The Digital Health Passport System is proposed as a secure web-based solution for improving the management and accessibility of patient health information at the health-facility level.

By replacing important paper-based processes with a digital system, the project aims to improve the organization, accessibility, security and verification of patient health information.

The combination of **Laravel, PHP, MySQL, Blade and Tailwind CSS** provides the technical foundation for developing the prototype, while the RAD methodology supports iterative development and continuous improvement.

The system's combination of facility-based identity verification, role-based access control, patient-owned health records, consent management, audit logging and QR-based credential verification provides the foundation for a more secure and accessible digital health passport.

The prototype will be evaluated through functional, security, usability and user-acceptance testing to determine whether it meets the defined project requirements.