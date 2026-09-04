# ASENA Enterprise — Next-Generation Veterinary & Pet Care Platform

> **Modern Single-Core Multi-Tier Architecture**  
> Unified codebase with dynamic feature flags, Persian Jalali localization, resilient Gemini AI triage, and zero-downtime multi-tenant cPanel deployment pipeline.

---

## 🚀 Overview

**ASENA Enterprise** solves the legacy multi-folder codebase fragmentation (`basic/`, `standard/`, `premium/`, `pharmacy/`) by centralizing all core veterinary clinic, pet shop, pharmacy, and hospital modules into a single, unified enterprise codebase.

### Key Architectural Advantages:
1. **Single Source of Truth**: All bug fixes, security patches, and feature upgrades happen in one place.
2. **Dynamic Feature Flags (`Feature::has(...)`)**: Features activate or deactivate dynamically at runtime based on the customer tier defined in `config/tiers.php` and `.env`.
3. **Automated cPanel Packaging (`bin/asena package`)**: Generates tailor-made, tier-locked deployment zip bundles with automated database migration dumps in seconds.
4. **Resilient AI Veterinary Assistant**: Context-aware triage engine powered by Google Gemini with network timeout guards and offline rule-based clinical fallback.
5. **SEO & Persian Localization**: Full Jalali calendar integration (`jdf.php`), Persian numeral conversion, dynamic schema markup, and automatic XML sitemap generation.

---

## 📦 Tier Hierarchy & Matrix

All tiers inherit features cumulatively from lower tiers:

```
basic ➔ standard ➔ premium ➔ pharmacy
```

| Feature Key | Description | Basic | Standard | Premium | Pharmacy |
| :--- | :--- | :---: | :---: | :---: | :---: |
| `catalog` | Product Showcase & Pricing | ✅ | ✅ | ✅ | ✅ |
| `cart` | E-commerce Shopping Cart & Orders | ❌ | ✅ | ✅ | ✅ |
| `booking` | Online Veterinary Appointments | ❌ | ✅ | ✅ | ✅ |
| `pet_profiles` | Medical Records & Pet Health Card | ❌ | ❌ | ✅ | ✅ |
| `ai_vet` | Gemini AI Virtual Assistant | ❌ | ❌ | ✅ | ✅ |
| `charity` | Pet Adoption & Donation Campaigns | ❌ | ❌ | ✅ | ✅ |
| `tickets` | Customer Support Ticketing System | ❌ | ❌ | ✅ | ✅ |
| `pharmacy` | Specialized Veterinary Rx Catalog | ❌ | ❌ | ❌ | ✅ |
| `prescriptions`| Digital Prescription Verification | ❌ | ❌ | ❌ | ✅ |
| `dosage_calc` | Weight-based Dosage Calculator | ❌ | ❌ | ❌ | ✅ |

---

## 🛠️ Tech Stack & Environment

- **Backend**: PHP 8.2+ (Procedural + OOP Core Services)
- **Database**: MySQL / MariaDB 10.4+ (InnoDB, UTF8mb4)
- **Styling**: Modern Vanilla CSS, Glassmorphism, Tailwind-compatible tokens, Persian Typography (`Vazirmatn`)
- **CLI**: Native PHP CLI (`bin/asena`)
- **AI Integration**: Google Gemini 2.5 Flash API with cURL resilient proxying

---

## ⚙️ Quick Start & Local Setup

### 1. Clone the Repository
```bash
git clone https://github.com/AyhanMehrzad/asena-enterprise.git
cd asena-enterprise
```

### 2. Configure Environment
```bash
cp .env.example .env
```
Edit `.env` to configure your database credentials and active tier:
```ini
APP_NAME="ASENA Enterprise"
APP_ENV="local"
APP_TIER="pharmacy"
APP_URL="http://localhost/asena/asena-enterprise"

DB_HOST="127.0.0.1"
DB_NAME="asena_premium"
DB_USER="root"
DB_PASS=""
DB_CHARSET="utf8mb4"

GEMINI_API_KEY="your_api_key_here"
```

### 3. Database Migration
Import database schemas from `database/`:
- Core schema: `database/sample_data.sql`
- Pharmacy medicines: `database/pharmacy_schema.sql`

---

## 💻 ASENA CLI Toolkit (`bin/asena`)

Manage configurations and generate client deployment packages effortlessly:

### View System Status
```bash
/opt/lampp/bin/php bin/asena status
```

### Switch Active Tier
```bash
/opt/lampp/bin/php bin/asena set-tier --tier=premium
/opt/lampp/bin/php bin/asena set-tier --tier=pharmacy
```

### Build Client Deployment Bundle
```bash
# Package for a specific tier and customer
/opt/lampp/bin/php bin/asena package --tier=pharmacy --customer="Razi_Pet_Pharmacy"
/opt/lampp/bin/php bin/asena package --tier=premium --customer="Aras_Clinic"
```
The output zip bundle is saved into `dist/` ready for one-click upload to cPanel.

---

## 📁 Project Structure

```
asena-enterprise/
├── actions/             # Business logic handlers (auth, cart, booking, ai_chat)
├── admin/               # Administrative dashboard & management modules
├── api/                 # REST / JSON endpoints
├── assets/              # CSS stylesheets, JavaScript modules, fonts, logos
├── bin/                 # Enterprise CLI tools (bin/asena)
├── config/              # Tier matrices and application configuration
├── database/            # SQL migration scripts & schema seeds
├── dist/                # Output directory for cPanel deployment packages (ignored in git)
├── includes/            # Core system (db.php, Env.php, Feature.php, functions.php, jdf.php)
├── uploads/             # User and product media uploads
├── index.php            # Dynamic homepage adapted to active tier
├── pharmacy.php         # Dedicated veterinary pharmacy & medicine catalog
├── booking.php          # Appointment scheduling module
├── pet_profiles.php     # Pet medical passports
├── sitemap.php          # Dynamic XML sitemap generator
├── robots.txt           # Search crawler directives
├── .env.example         # Template for environment variables
└── README.md            # System documentation
```

---

## 🛡️ Security & Quality Standards

- **SQL Injection Prevention**: PDO prepared statements across all active modules.
- **XSS Mitigation**: Contextual sanitization (`htmlspecialchars`) and input validation.
- **Secrets Isolation**: Sensitives stored strictly in `.env`, never tracked in source control.
- **Persian Calendar Accuracy**: Jalali algorithms verified for leap years and Persian month names.

---

## 📄 License
Proprietary — Developed for **ASENA Platform**. All rights reserved.
