[//]: # (Leula Events — Universal Digital Invitation & Gate Verification Platform)

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=FF7A00&height=220&section=header&text=Leula%20Events&fontSize=56&fontColor=FFFFFF&animation=fadeIn&fontAlignY=38&desc=Universal%20Digital%20Invitation%20%26%20Gate%20Verification%20Platform&descAlignY=58&descAlign=50&descSize=18" width="100%"/>

<br/>

<a href="https://git.io/typing-svg"><img src="https://readme-typing-svg.herokuapp.com?font=Fira+Code&size=20&pause=1000&color=FF7A00&center=true&vCenter=true&random=false&width=750&lines=%E2%9C%A8+Canva-Style+Interactive+Invitation+Card+Designer;%E2%9A%A1+High-Speed+QR+Gate+Scanner+%26+Anti-Passback;%E2%86%BA+Reversible+RSVP+%26+Controlled+Check-in+Reversal;%F0%9F%9B%A1%EF%B8%8F+Enterprise+Role-Based+Access+Control+(RBAC);%F0%9F%92%8E+Liquid+Glass+Notifications+%26+Confirmation+Engine" alt="Typing SVG"/></a>

<br/>

<p align="center">
  <img src="https://img.shields.io/badge/Version-2.0.0-FF7A00?style=for-the-badge&logo=v&logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/SQLite-Fallback%20Ready-003B57?style=for-the-badge&logo=sqlite&logoColor=white"/>
  <img src="https://img.shields.io/badge/XAMPP-Optimized-FB7A24?style=for-the-badge&logo=apache&logoColor=white"/>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Security-Anti--Passback%20Active-059669?style=for-the-badge&logo=shield&logoColor=white"/>
  <img src="https://img.shields.io/badge/RBAC-3%20Tiers%20Enforced-DC2626?style=for-the-badge&logo=keycloak&logoColor=white"/>
  <img src="https://img.shields.io/badge/UI-Liquid%20Glass-FF7A00?style=for-the-badge&logo=css3&logoColor=white"/>
  <img src="https://img.shields.io/badge/Responsive-Mobile%20%26%20Desktop-17A2B8?style=for-the-badge&logo=google-chrome&logoColor=white"/>
</p>

</div>

---

## 📌 Table of Contents

<div align="center">

| | Section | | Section |
|:---:|:---|:---:|:---|
| 🏗️ | [Architecture & Overview](#️-architecture--overview) | 📋 | [Core Modules](#-core-modules) |
| ✨ | [Key Features & Highlights](#-key-features--highlights) | 🛡️ | [Role-Based Access Control](#️-role-based-access-control-rbac) |
| 🛠️ | [Setup Instructions](#️-setup-instructions) | 🎨 | [Design System](#-design-system) |

</div>

---

## 🏗️ Architecture & Overview

<div align="center">

```
┌────────────────────────────────────────────────────────────────────────┐
│                        LEULA EVENTS PLATFORM                           │
│       Universal Digital Invitation Design & High-Speed Gate Access     │
├────────────────────┬──────────────────────┬────────────────────────────┤
│   Card Studio      │   Verification Gates │   Audience & RSVP          │
│  (Canva Cloner)    │  (Anti-Passback QR)  │  (Multi-Channel Broadcast) │
├────────────────────┴──────────────────────┴────────────────────────────┤
│        RBAC Engine · 3 Access Tiers · Fine-Grained Route Guards        │
├────────────────────────────────────────────────────────────────────────┤
│  Events │ Guests │ Passes │ Gate Scanners │ Live Ingress │ Audit Trail │
├────────────────────────────────────────────────────────────────────────┤
│          Liquid Glass UI Engine  ·  Zero Browser Popups                │
├────────────────────────────────────────────────────────────────────────┤
│                     PHP 8.2+  ·  MySQL 8.0 / SQLite                    │
└────────────────────────────────────────────────────────────────────────┘
```

</div>

**Leula Events** is a modern, enterprise-grade digital invitation design, multi-channel distribution, and optical QR gate access management platform. Designed specifically for high-profile galas, corporate leadership summits, VIP conferences, and private celebrations, it unites creative design with military-grade access control.

---

## ✨ Key Features & Highlights

<details open>
<summary><b>🎨 1. Canva-Style Digital Invitation Designer</b></summary>

<br/>

- **Canva Card Cloner**: Upload any custom flyer or Canva export (`PNG`/`JPG`) and transform it into an interactive digital pass with live encrypted QR badges.
- **12 Luxury Presets**: Instant access to ready-made designs (Royal Gold Gala, Midnight Executive, Diamond White, Emerald Sovereign, Sunset Velvet, Cyberpunk Neon, Minimalist Noir, and more).
- **Precision Typography & Drag-and-Drop**: Real-time font scaling, luxury fonts (Cinzel, Cormorant Garamond, Sora, Space Grotesk, Playfair Display), custom badges, quote ribbons, and seal watermarks.
- **Ultra HD Card Export**: Generates 3x high-resolution card PNGs via client-side rendering for physical printing and instant download.

</details>

<details open>
<summary><b>⚡ 2. High-Speed QR Gate Verification Terminal</b></summary>

<br/>

- **Hardware Camera Scanner**: Continuous optical scanning with laser target viewport and high-speed auto-decode powered by `html5-qrcode`.
- **Anti-Passback Concurrency Protection**: Automatically detects duplicate scans in real time. If a pass has already been admitted, the terminal raises an immediate high-visibility red alert and records the attempt.
- **Web Audio Chime Synthesizer**: Emits distinct low/high frequency audio feedback for approved admissions versus rejected or duplicate scans.
- **Manual Token & Barcode Fallback**: Search by guest name, reference code (`INV-XXXX`), or 64-bit cryptographic token when an attendee's mobile battery is depleted.

</details>

<details open>
<summary><b>↺ 3. Reversible RSVP & Controlled Check-In Reversals</b></summary>

<br/>

- **Reversible Guest RSVP**: Guests can update their attendance response (`Accepted ⇄ Declined`) on their pass page without needing a new link.
- **Instant State Synchronization**: Updates the database within an isolated transaction and updates the DOM immediately with verified server state—eliminating notification/display discrepancies.
- **Controlled Check-In Reversals**: Authorized gate officers and administrators can reverse accidental admissions with a **mandatory justification reason**.
- **Re-Entry Eligibility**: Once reversed, the guest's pass is instantly eligible for re-scan and admission without destroying attendance telemetry.

</details>

<details open>
<summary><b>💎 4. Liquid Glass System Notifications Engine</b></summary>

<br/>

- **Zero Browser Popups**: Completely eliminates browser-native `alert()`, `confirm()`, and `prompt()` dialogs across the entire platform.
- **Glassmorphic Toast Stack**: Modern floating notifications with background blur, translucent surface highlights, and semantic status indicators:
  - 🟢 **Success**: Action verified and synchronized
  - 🔴 **Error**: Transaction or connection alert
  - 🟠 **Warning**: Attention or authorization notice
  - 🔵 **Info**: General telemetry and status update
- **Application Confirmation Modals**: Promise-based interactive dialogs for destructive actions, RSVP changes, and check-in reversals with reason inputs.

</details>

<details open>
<summary><b>🛡️ 5. Permanent Security & Action Audit Trail</b></summary>

<br/>

- All critical actions (`RSVP_CHANGE`, `GATE_CHECKIN`, `CHECKIN_REVERSAL`, `GUEST_MANAGEMENT`, `TEAM_MANAGEMENT`) are permanently logged in `audit_logs`.
- Records actor identity, staff IDs, IP addresses, previous states, new states, and detailed justification notes.
- Accessible through the dedicated searchable **Action History & Reversible Audit Trail** console.

</details>

---

## 🛡️ Role-Based Access Control (RBAC)

The platform enforces strict server-side and client-side permissions across three operational tiers:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        ROLE PRIVILEGE MATRIX                           │
├────────────────────┬────────────────────┬──────────────────────────────┤
│ Super Admin        │ Event Director     │ Gate Verification Officer    │
│ (Full Control)     │ (Events & Passes)  │ (Scanner Terminal Only)      │
└────────────────────┴────────────────────┴──────────────────────────────┘
```

| Module / Capability | Super Administrator | Event Director | Gate Verification Officer |
|:--------------------|:-------------------:|:--------------:|:-------------------------:|
| **QR Gate Scanner** | Full Access | Full Access | **Primary Dedicated Workspace** |
| **Live Attendance Monitor** | Full Access | Full Access | View & Reversal |
| **Executive Dashboard** | Full Access | Full Access | *Pruned & Route Blocked* |
| **Events Registry & Config** | Full Access | Create & Manage | *Pruned & Route Blocked* |
| **Guest Directory & Import** | Full Access | Add, Edit, Export | *Pruned & Route Blocked* |
| **Card Designer Studio** | Full Access | Create & Export | *Pruned & Route Blocked* |
| **Multi-Channel Distribution**| Full Access | Batch Dispatch | *Pruned & Route Blocked* |
| **Reports & Analytics** | Full Access | View Analytics | *Pruned & Route Blocked* |
| **Action & Security Audit Log**| Full Audit Trail | Full Audit Trail| *Restricted to Station Alerts* |
| **Team Management (`users.php`)** | **Exclusive Access** | *Pruned & Route Blocked* | *Pruned & Route Blocked* |
| **System Settings (`settings.php`)** | **Exclusive Access** | *Pruned & Route Blocked* | *Pruned & Route Blocked* |
| **Organization Profiles** | **Exclusive Access** | *Pruned & Route Blocked* | *Pruned & Route Blocked* |

---

## 📋 Core Modules

<div align="center">

| Module | Description | Access Level |
|:-------|:------------|:------------:|
| 📊 **Dashboard** | Operational overview — gate throughput, arrival rate, check-in velocity | Admin / Director |
| 📅 **Events Registry** | Multi-event scheduling, venue addresses, and gate checkpoint allocations | Admin / Director |
| 👥 **Guest Directory** | VIP callset roster, phone/email contact lists, and CSV export | Admin / Director |
| ✉️ **RSVP Roster** | Real-time headcount confirmations, dietary preferences, and declines | Admin / Director |
| 🎨 **Card Studio** | Interactive Canva-style card cloning, 12 presets, and typography controls | Admin / Director |
| 🎫 **Digital Passes** | Generated passes, personalized `INV-XXXX` codes, and cryptographic tokens | Admin / Director |
| 📲 **Distribution Hub** | Multi-channel pass dispatch and WhatsApp integration link generator | Admin / Director |
| ⚡ **Gate Scanner** | Optical QR camera scanner with duplicate anti-passback detection | **All Roles** |
| 📈 **Live Attendance** | Real-time ingress telemetry, gate load balance, and admission logs | **All Roles** |
| 📈 **Reports & Analytics**| Post-event turnout efficiency, gate speed metrics, and delivery ratios | Admin / Director |
| 🔔 **Notifications & Audit**| System alerts and permanent chronological action log | Admin / Director |
| 🛡️ **Team Members** | User accounts, role assignment, and access revocation | **Super Admin** |
| ⚙️ **System Settings** | Anti-passback enforcement rules, audio chimes, and gate allocations | **Super Admin** |

</div>

---

## 🛠️ Setup Instructions

> [!IMPORTANT]
> Ensure **PHP 8.2+**, **MySQL 8.0** (or XAMPP with Apache), and **PDO extensions** are enabled on your server.

### Step 1 — Clone the Repository

```bash
git clone https://github.com/Msollajr5/Event-Invitation.git
cd Event-Invitation
```

### Step 2 — Web Server Placement

Copy the project into your local web root directory:

- **XAMPP (Windows)**: `C:\xampp\htdocs\Invitation`
- **Linux / Apache**: `/var/www/html/Invitation`

### Step 3 — Database Setup

1. Create a MySQL database named `invitation_system`.
2. Import the schema file:
```bash
mysql -u root -p invitation_system < database.sql
```

> [!TIP]
> **Zero-Configuration Fallback**: If MySQL is unavailable, the application automatically initializes a self-healing SQLite database (`config/invitation_system.sqlite`) with full demo data and audit logging.

### Step 4 — Launch Application

Open your browser and navigate to:
```
http://localhost/Invitation
```

---

## 🎨 Design System

<div align="center">

| Token | Hex | Role |
|:------|:----|:-----|
| 🟧 **Brand Primary** | `#FF7A00` | Navigation accents, primary CTA buttons, active badges |
| 🟧 **Accent Dark** | `#E96800` | Hover states, gradient card headers, focus rings |
| ⬜ **Page Canvas** | `#F0F2F5` | Neutral high-contrast background canvas |
| 🟩 **Card Surface** | `#FFFFFF` | Elevated cards, tables, modal dialogs |
| 🟢 **Success** | `#059669` | Valid ticket scan, accepted RSVP, active terminals |
| 🔴 **Danger** | `#DC2626` | Duplicate alerts, pass revocation, declined RSVP |
| 🟡 **Warning** | `#F59E0B` | Pending RSVPs, gate caution alerts |
| ⬛ **Typography Dark**| `#09090B` | Deep contrast text, headings, dark badges |

</div>

---

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=FF7A00&height=120&section=footer&animation=fadeIn" width="100%"/>

<sub>Built with ❤️ for enterprise-grade digital invitation design and high-security gate access operations.</sub>

</div>
