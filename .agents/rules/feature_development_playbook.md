# ASENA Enterprise - Feature Development Playbook & Architecture Standard

This rule provides the standard architectural workflow for introducing any new feature into the ASENA Enterprise platform. Follow these steps consistently to maintain system stability, backward compatibility across license tiers, and high-performance Persian RTL user experience.

---

## 1. Architecture Overview & Lifecycle

Every feature in ASENA Enterprise passes through the following standardized layers:

```
┌───────────────────────────────────────────────────────────┐
│ 1. Tier & Capability Matrix: config/tiers.php              │
│    Gated via: Feature::has('feature_key')                 │
└─────────────────────────────┬─────────────────────────────┘
                              │
┌─────────────────────────────▼─────────────────────────────┐
│ 2. Database Schema: database/migrations/                  │
│    MySQL InnoDB utf8mb4_unicode_ci, indexed foreign keys  │
└─────────────────────────────┬─────────────────────────────┘
                              │
┌─────────────────────────────▼─────────────────────────────┐
│ 3. Domain Service Layer: includes/<Feature>Service.php    │
│    Encapsulated business logic, PDO transactions          │
└─────────────────────────────┬─────────────────────────────┘
                              │
┌─────────────────────────────▼─────────────────────────────┐
│ 4. HTTP / AJAX Controller: actions/<feature>_action.php    │
│    CSRF checks, input sanitization, standard JSON response│
└─────────────────────────────┬─────────────────────────────┘
                              │
┌─────────────────────────────▼─────────────────────────────┐
│ 5. Client & UI Layer: assets/js/ & enterprise-ui.css      │
│    Persian RTL, Optimistic UI, Glass Toast, Material Icons│
└─────────────────────────────┬─────────────────────────────┘
                              │
┌─────────────────────────────▼─────────────────────────────┐
│ 6. Multi-Surface Sync: Storefront, Admin, User Profile    │
│    Single source of truth across all dashboards           │
└───────────────────────────────────────────────────────────┘
```

---

## 2. Step-by-Step Implementation Guide

### Step 1: Register in the Tier Matrix (`config/tiers.php`)
All capabilities must be declared as a unique key (e.g. `'pet_calorie_calc'`, `'site_reviews'`, `'autoship'`).
Assign it to the appropriate tiers:
- `basic`: Essentials (booking, catalog, basic cart).
- `standard`: Adds blog, loyalty points, user reviews.
- `premium`: Adds autoship, custom boxes, bayesian reviews, telehealth, charity.
- `pharmacy`: Adds prescription Rx, cold-chain dispatch, pharmacist panel.
- `enterprise`: Super-set enabling all features.

**Usage in PHP:**
```php
if (Feature::has('my_feature_key')) {
    // Render UI or execute logic
}
```

### Step 2: Database Schema & Migrations (`database/migrations/`)
- Place a new SQL file in `database/migrations/XX_feature_name.sql`.
- Follow strict naming conventions:
  - Table names: plural, lowercase snake_case (`site_testimonials`, `pet_nutrition_logs`).
  - Charset: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`.
  - Always index foreign keys and columns frequently used in `WHERE` / `ORDER BY`.

```sql
CREATE TABLE IF NOT EXISTS `site_testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `author_name` VARCHAR(120) NOT NULL,
    `author_title` VARCHAR(150) NULL,
    `content` TEXT NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_approved` (`is_approved`),
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 3: Backend Domain Service (`includes/<Feature>Service.php`)
Encapsulate all database queries and business rules into an OOP class.
- Always use **PDO prepared statements**.
- Wrap multi-table operations in transactions (`$pdo->beginTransaction()`).
- Never echo directly from service classes; throw exceptions or return structured arrays/booleans.

```php
<?php
class TestimonialService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getApproved(int $limit = 6): array {
        $stmt = $this->pdo->prepare("SELECT * FROM site_testimonials WHERE is_approved = 1 ORDER BY id DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### Step 4: HTTP / AJAX Endpoint (`actions/<feature>_action.php`)
Handles frontend requests (fetch/form POST):
1. Set JSON headers: `header('Content-Type: application/json; charset=utf-8')`.
2. Verify session or guest token.
3. Validate and sanitize input variables.
4. Execute via the Service class.
5. Return standardized JSON response:

```json
{
  "status": "success",
  "message": "پیام شما با موفقیت ثبت شد.",
  "data": { ... }
}
```

### Step 5: Frontend UI & Client Integration
- **Persian & RTL**: Ensure `dir="rtl" lang="fa"`, use Vazirmatn and Persian localized numbers/dates (`jdf.php`).
- **Brand Colors**:
  - Primary Navy: `#001a48` (`text-primary`, `bg-primary`)
  - Accent Orange: `#fd8100` (`bg-secondary-container`, `text-on-secondary-container`)
- **Optimistic UI**: Provide immediate feedback (0ms visual change, micro-animation) before network completion; gracefully revert if the API call fails.
- **Glassmorphic Toast**: Use `enterprise-ui.css` styling (`.wishlist-toast`, `.floating-glass`) rather than intrusive native alerts.
- **JavaScript Module**: Store client code in `assets/js/<feature>.js` and include via `includes/footer.php` or page footer.

### Step 6: Multi-Surface Integration
Ensure all surfaces remain in sync:
1. **Storefront**: `index.php`, `shop.php`, `pharmacy.php`, etc.
2. **User Profile**: `profile.php` reflects customer interactions.
3. **Admin Dashboard**: `admin/` provides moderation, toggles, or reports.

### Step 7: Packaging & Verification (`bin/asena`)
Before shipping or deploying:
- Run status check:
  ```bash
  /opt/lampp/bin/php bin/asena status
  ```
- Package distribution for specific tiers:
  ```bash
  /opt/lampp/bin/php bin/asena package --tier=enterprise --name=ClientName
  ```
