# Enterprise Landing Page UI/UX Standards

This document defines the mandatory design standards, layout hierarchy, aesthetics, and interaction patterns for landing pages within the ASENA Enterprise platform. All future revisions and newly added sections to the landing page (`index.php` or dedicated landing surfaces) must strictly comply with this standard.

---

## 1. The 7-Beat Narrative Hierarchy

Every enterprise landing surface must follow this cohesive, story-driven architectural rhythm to eliminate visitor fatigue and clearly convey platform authority:

1. **The Hero Authority Zone (Above-the-Fold)**:
   - **Outcome-Driven H1**: State the comprehensive ecosystem outcome clearly (e.g., «زیست‌بوم جامع درمان، داروخانه و سلامت حیوانات خانگی»), not an isolated product name or generic marketing buzzword.
   - **Dual High-Intent Actions**:
     - *Primary Action (High Conversion)*: Immediate access to care/consultation (e.g., «رزرو آنلاین نوبت و مشاوره دامپزشکی» with radiant glowing primary styling).
     - *Secondary Action (Low Friction)*: Seamless exploration of the ecosystem (e.g., «ورود به داروخانه تخصصی و پت‌شاپ»).
   - **Interactive Proof Widget**: An interactive preview widget (e.g., live clinic slot finder, cold-chain prescription status tracker, or interactive pet profile switcher) rather than a flat static product image.
   - **Social Proof Validation Strip**: Positioned immediately beneath the CTAs with real customer avatars, 4.9★ rating, and active trust metrics («مورد اعتماد بیش از ۲۵,۰۰۰ سرپرست پت و بیش از ۵۰ مرکز درمانی تخصصی کشور»).

2. **Institutional Proof & Marquee Ticker**:
   - A clean, infinite-scrolling horizontal marquee displaying accredited veterinary hospitals, university faculties, animal shelters (e.g., Vafa, Payetakht, Razi, Caspian, Shiraz).
   - Subtle institutional styling with hover color illumination.

3. **The Core Ecosystem Bento Grid (The 3 Pillars)**:
   - Asymmetric bento grid layout representing ASENA's operational pillars:
     - **Pillar 1: Veterinary Clinics & Telehealth** (Verified doctors, instant appointment booking, electronic health records).
     - **Pillar 2: Cold-Chain Specialized Pharmacy** (Pharmacist verification, temperature-controlled delivery, rare pet medications).
     - **Pillar 3: Autonomous Subscriptions (Chewy Model Autoship)** (10-15% recurring discount, automatic delivery intervals, 1-click pause/resume).

4. **High-Conversion Interactive Utility Hub**:
   - Embedded interactive tools (e.g., Pet Calorie & Nutrition Calculator adhering to FEDIAF & WSAVA standards, Breed Health Advisor).

5. **Verified Specialists & Healthcare Centers Showcase**:
   - Doctor cards showing medical council numbers, board specialties, real patient review counts, and instant booking CTAs.
   - Clean filtering tabs by medical specialty (Surgery, Internal Medicine, Dental, Exotic Birds, Emergency ICU).

6. **Clinical Case Stories & Verified Social Proof**:
   - Authentic pet parent stories, treated conditions, pet species, and verified outcomes.

7. **Closing High-Value Conversion Hub**:
   - Zero-risk onboarding, mobile PWA installation incentive, and 24/7 emergency support access.

---

## 2. Color Palette & Visual Depth

- **Brand Primary Navy (`#001a48`)**: Dominant branding, deep container surfaces, primary typography.
- **Brand Amber / Orange (`#fd8100`)**: Primary conversion buttons, promotional badges, active highlights.
- **Medical Trust Emerald (`#059669`)**: Clinical verification badges, doctor online status, cold-chain safety indicators.
- **Glassmorphic Cards**: `bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/60 dark:border-white/10 shadow-xl rounded-3xl`.
- **Ambient Lighting**: Subtle radial gradient backdrops behind key hero elements (`bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))]`), avoiding flat, monotone surfaces.

---

## 3. Typography & RTL Ergonomics

- **Font Pairing**:
  - `Vazirmatn`: Headings (`font-black`, tight tracking `tracking-tight`), subheadings, body text.
  - `Geist`: Numbers, prices, ratings, timestamps, and order/coupon codes.
- **Directionality**: Strictly Right-To-Left (`dir="rtl" lang="fa"`). All directional icons (arrows, chevrons) must be properly mirrored for RTL eye-tracking.
- **Heading Line Heights**: Headings must use tight line-height (`leading-tight` or `leading-snug`) to avoid excessive vertical space.

---

## 4. Micro-Interactions & Optimistic UI

- **Interactive Elevation**: Subtle lift on hover (`hover:-translate-y-1 hover:shadow-2xl transition-all duration-300`).
- **Live Status Pulsing**: Real-time status indicators (e.g., green dot for on-duty emergency clinics) using pulsing animations (`relative flex h-2.5 w-2.5`).
- **Zero Layout Shifts**: Pre-set aspect ratios and skeleton loaders for all dynamic elements.
