# Enterprise User Profile & Identity Architecture Standards

## 1. Non-Siloed Profile Information
- Personal information (نام و نام خانوادگی، شماره موبایل، کد ملی، ایمیل، کلمه عبور) and residential addresses (آدرس‌ها و لوکیشن نقشه) must be first-class, easily discoverable sections inside the main User Profile workspace (`profile.php`).
- Never bury primary identity or shipping coordinates in an isolated subpage without direct, prominent navigation links from the user dashboard.

## 2. Digikala-Style Identity Bento Grid
- Present user identity in a clean, structured 2-column or 3-column card grid:
  - **Full Name (نام و نام خانوادگی)** with inline/modal quick edit.
  - **National Code (کد ملی)** with 10-digit format validation.
  - **Mobile Phone (شماره تلفن همراه)** with a green "تأیید شده" badge.
  - **Email (پست الکترونیک)** with verification state.
  - **Password (کلمه عبور)** with a dedicated secure change-password modal.
  - **Sheba / Bank Account (شماره شبا)** with Shetab card visual preview.

## 3. Interactive Location & Address Management
- Address management must include:
  - Full postal address, city, and 10-digit postal code.
  - Interactive Leaflet OpenStreetMap with pin drag-and-drop, map-click positioning, and Nominatim reverse geocoding.
  - Instant map size invalidation (`map.invalidateSize()`) upon tab display to prevent rendering glitches.

## 4. Seamless Tab/View Routing & Mobile First Navigation
- On Desktop: Navigation sidebar must maintain active tab state and support direct URL hashes (`#overview`, `#personal-info`, `#addresses`, etc.).
- On Mobile: Implement a sticky, horizontally scrollable pill-tab bar (`backdrop-blur-md bg-surface/90`) allowing quick touch switching between views without page reloads.

## 5. App Shell Ergonomics & Desktop Collapsible Sidebars
- **Distinction Between App Shell and Marketing Pages:**
  - Profile dashboards (`profile.php`) and operational role panels (`admin/`, `doctor/`, `organization/`, `seller/`, `pharmacist/`) are high-density application shells, NOT marketing landing pages.
  - Marketing footers (5-column e-commerce link catalogs, trust seals, newsletter forms) MUST be omitted from dashboards and panels (`$hideMarketingFooter = true`). Only a clean, minimal 1-line copyright bar or app-status strip may be displayed if needed.

- **Collapsible Desktop Sidebars (Desktop & Mobile Parity):**
  - Sidebars on desktop (`lg:`) must support collapsible states: toggling smoothly between full expanded width (`w-64`) and compact icon-rail (`w-20` with hover tooltips) or full collapse (`w-0`), with main container margin auto-adjusting (`lg:mr-64` <-> `lg:mr-20` / `lg:mr-0`).
  - The user's collapse/expand preference must persist across page loads and tab transitions using `localStorage.getItem('asena_sidebar_collapsed')`.
  - Transitions must be hardware-accelerated (`transition-all duration-300 ease-in-out`).
