# LEULA EVENTS
## Universal Digital Invitation & Event Guest Verification Management System

A production-ready enterprise SaaS web application for designing personalized digital invitations, distributing them via WhatsApp, SMS, and Email, and performing high-speed, multi-method gate admission verification with double check-in prevention.

---

## 1. System Architecture & Tech Stack

### Frontend
- **React.js 18** with **TypeScript**
- **Tailwind CSS 3** (Light & Dark theme support, SaaS-style elegant dark navy typography)
- **Fabric.js 6** (Canva-like interactive invitation card designer with dynamic placeholders)
- **Recharts 2** (Interactive charts for attendance trends, VIP tier distributions, and delivery telemetry)
- **html5-qrcode** (Hardware camera QR scanner with instant green/red/orange validation feedback)
- **Axios** (With interceptors, token persistence, and graceful demo mode fallback)

### Backend
- **Laravel 10 / PHP 8.2**
- **Laravel Sanctum** token-based API authentication
- **Eloquent ORM** with multi-tenant organization data isolation
- **MySQL Database** (22 normalized tables with strict foreign keys, indexes, and unique constraints)
- **Concurrency Protection**: Database transactions with `lockForUpdate()` to prevent duplicate check-ins across multiple simultaneous gates

### Verification Channels & Services
- **Official WhatsApp Business Cloud API** integration
- **Configurable SMS Gateway** integration (e.g. Africa's Talking, Twilio, Infobip, Beem)
- **Email SMTP** invitation delivery
- **Public Guest Portal** (`/invitation/:token`) allowing guests to view invitations and submit RSVPs without installing applications

---

## 2. User Roles & Permission Matrix

| Role | Permissions & Scope |
| :--- | :--- |
| **Super Administrator** | Oversee organizations, manage users and roles, audit system settings, monitor all platform activity |
| **Event Administrator** | Create and manage events, design cards, import guests, generate QR tokens, manage entrance gates and verification staff |
| **Verification Officer** | Gate access, scan QR codes, perform manual search verification, record guest admission, view check-in logs |
| **Guest** | Open personal invitation via secure link, view card & QR pass, get directions, submit RSVP |

---

## 3. Database Schema Overview

The database comprises 22 normalized relational tables:
1. `organizations` — Multi-tenant company/organizer entity
2. `roles` — Role definitions (Super Admin, Event Admin, Verification Officer)
3. `permissions` — Granular capability slugs
4. `permission_role` — Role-permission mapping
5. `users` — Authenticated accounts with role and organization references
6. `event_categories` — Weddings, Graduations, Birthdays, Funerals, Conferences, Corporate, etc.
7. `events` — Event details, schedules, capacities, venues, Google Maps links, and RSVP deadlines
8. `event_sessions` — Sub-sessions within an event
9. `guest_categories` — VIP tiers (VVIP, VIP, Regular, Staff, Family) with color badges
10. `guests` — Guest records with unique constraint on `[event_id, email]` and `[event_id, phone]`
11. `invitation_templates` — Reusable Canva-like invitation templates
12. `invitation_designs` — Event-specific canvas designs
13. `invitations` — Generated cards, personalized references (`INV-XXXXXX`), and statuses
14. `qr_tokens` — Cryptographic 64-character tokens (personal data is never stored in plaintext inside the QR code)
15. `gates` — Physical entrance gates
16. `gate_officer` — Officer gate assignments
17. `check_ins` — Admission audit records with unique constraint on `invitation_id` preventing double entries
18. `message_logs` — Dispatches and delivery outcomes across WhatsApp, SMS, and Email
19. `rsvp_records` — Guest responses (accepted, declined, tentative), headcounts, and dietary notes
20. `audit_logs` — Security and administrative modification trails
21. `notifications` — In-app alerts
22. `system_settings` — Organization branding and API credentials

---

## 4. Key Modules & Usage Guide

### 🎨 Canva-Like Digital Invitation Designer
- Access via **Invitations -> Card Designer**
- Interactive canvas powered by **Fabric.js**
- Add dynamic placeholders: `{guest_name}`, `{event_name}`, `{event_date}`, `{venue}`, `{guest_category}`, `{permitted_attendees}`, `{reference_code}`
- Custom fonts, colors, shapes, borders, and orientation (Portrait / Landscape)
- Save custom designs or pick from ready-made templates

### 📱 Multi-Channel Distribution
- Access via **Invitations -> Distribution**
- Deliver personalized portal links and cards via **WhatsApp Business Cloud API**, **SMS**, or **Email**
- Real-time delivery logs tracking `sent`, `delivered`, and `failed` attempts with retry controls

### ⚡ QR Scanner & Gate Verification
- Access via **Verification -> QR Scanner**
- Uses optical camera scanning via **html5-qrcode**
- Instant high-visibility status results:
  - 🟢 **VALID - ENTRY APPROVED**: Displays guest name, tier, permitted headcount, entrance gate, officer name
  - 🔴 **ALREADY CHECKED IN**: Displays previous check-in timestamp and gate to prevent duplicate or shared pass attempts
  - 🔴 **INVALID INVITATION / REVOKED**: Rejection banner
  - 🟠 **WRONG EVENT**: Displays event mismatch warning

### 🔍 Manual Verification Fallback
- Access via **Verification -> Manual Verify**
- Search by guest name, phone number, or reference ID when a guest's device is out of battery
- Follows the identical database transaction lock logic to maintain concurrency integrity

### 📊 Real-Time Attendance Console
- Access via **Attendance Live**
- Live turnout metrics, hourly check-in timeline (Recharts), gate loads, and arrival breakdown by VIP tier

---

## 5. Security & Legal Compliance

- **Tanzania Personal Data Protection Act (PDPA), 2022 compliant**:
  - Secure data isolation per organization
  - Cryptographically randomized tokens for QR passes
  - No personal identifiable information (PII) embedded directly inside QR payloads
  - Comprehensive audit logs tracking user operations and admissions

---

## 6. Default Demo Credentials

| Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@leulaevents.com` | `password123` |
| **Event Admin** | `organizer@leulaevents.com` | `password123` |
| **Verification Officer** | `officer@leulaevents.com` | `password123` |

