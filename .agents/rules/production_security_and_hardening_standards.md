# ASENA Enterprise - Production Security & Hardening Standards

This document establishes mandatory production security guidelines, secrets hygiene, financial integrity checks, and frontend resilience patterns for the ASENA Enterprise platform.

## 1. Secrets & Credentials Zero-Leak Policy
- **No Credentials in Webroot:** Never store plain-text account credentials, temporary test account lists, or database credentials in public webroot directories or git-tracked files.
- **Strict Environment Loading:** All secrets, API credentials (MeliPayamak, ZarinPal, OAuth), and tokens must strictly be loaded via `.env` through `getenv()` or `$_ENV`.
- **Server File Masking:** Apache `.htaccess` and Nginx configurations must unconditionally block direct HTTP requests to `.env`, `.sql`, `.log`, and `.txt` credential files.

## 2. Server-Side Truth & Financial Integrity
- **Server Price Calculation:** Prices, discounts, commissions, and order totals must ALWAYS be calculated server-side from active database records. Never trust totals or pricing sent from client payloads.
- **4-Gate Payment Callback Verification:** Multi-step payment callbacks must enforce 4-gate verification:
  1. GET callback request validation.
  2. Gateway reported status verification.
  3. Authority-to-Session comparison (prevents authority hijacking).
  4. Server-to-server gateway verification (`verifyPayment()`) inside an ACID database transaction.

## 3. Concurrency & Double-Booking Prevention
- **Pessimistic Row Locking:** Critical state reservations (appointment slots, inventory depletion, wallet transactions) must execute inside an ACID transaction (`$pdo->beginTransaction()`) utilizing row-level locks (`SELECT ... FOR UPDATE`).
- **Slot Collision Detection:** Always cross-reference blocked slots and active non-cancelled appointments before committing a booking.

## 4. SVG Icon Constraints in Flexbox Layouts
- **Explicit Sizing:** Any SVG icon rendered or injected dynamically inside a flexbox container must have explicit dimensions (`width: 20px; height: 20px; flex-shrink: 0;` or `1.25em`) to prevent browser default intrinsic size explosions (`300px × 150px`).
- **Offline Independence:** Error pages and offline modes must rely exclusively on local embedded SVG sprites or inline SVG definitions with 0 external network dependencies.

## 5. Centralized Bootstrapping & Session Hardening
- **Universal Session Protection:** Cookie parameters (`session.cookie_httponly = 1`, `session.cookie_samesite = 'Lax'`) must be initialized before any `session_start()`.
- **Session Regeneration:** Whenever user privilege changes or password updates occur, immediately invoke `session_regenerate_id(true)` to prevent session fixation.
- **User Enumeration Prevention:** Authentication and password recovery endpoints must standardize response messages so attackers cannot enumerate valid user phone numbers.
