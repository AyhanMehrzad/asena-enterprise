# Progressive Disclosure & Enterprise App Simplification Standard

This document establishes the mandatory architectural and UX principles for progressive disclosure, page simplicity, and information architecture across the ASENA Enterprise platform.

---

## 1. Core Invariant: The "Showcase vs. Workshop" Principle

1. **The Landing Page (`index.php`) is a Curated Showcase, Not a Warehouse**:
   - The landing page must only introduce the platform's core pillars, build institutional trust, and guide the user into high-intent conversion funnels with minimal friction.
   - Never embed heavyweight, multi-step interactive applications (such as 400-line nutrition calculators, drug interaction checkers, or full-blown multi-step calendar wizards) directly into the landing page scroll.
   - Instead, feature them as **Engaging Feature Cards / Bento Teasers** with high-contrast action buttons that route visitors to dedicated, single-purpose tool pages (e.g., `calculator.php`, `interactions.php`, `booking.php`).

2. **3-Tier Information Architecture (IA)**:
   - **Tier 1 (Core Consumer Flow)**:
     - Healthcare & Appointments (`booking.php`)
     - Pet Shop Catalog (`shop.php`)
     - Specialized Pharmacy & Prescription Upload (`pharmacy.php`)
   - **Tier 2 (Dedicated Utility Hubs & Subpages - Visible & Linked)**:
     - Interactive Pet Calorie & Nutrition Calculator (`calculator.php`)
     - Veterinary Drug Interaction Checker (`interactions.php`)
     - Periodic Delivery & Autoship Manager (`subscriptions.php`)
     - Medical Centers & Hospitals Directory (`organizations.php`)
     - Knowledge Base & Pet Health Articles (`knowledge_base.php`)
     - Charity & Stray Animal Rescue (`charity.php`)
     - Loyalty Club & Rewards (`rewards.php`)
   - **Tier 3 (B2B & Partner Onboarding - Cleanly Separated)**:
     - Providers (Clinics, Doctors, Pharmacies, Sellers) must have a dedicated entrance in the top bar or footer («ورود / پیوستن متخصصین» / `partner_register.php`), avoiding confusing normal pet owners with B2B vendor language on consumer landing pages.

3. **App-Wide Single-Responsibility Pages**:
   - Every page across the app must have one clear, dominant purpose:
     - `shop.php`: Find and purchase physical pet products.
     - `pharmacy.php`: Browse medications, upload prescriptions, view cold-chain assurance.
     - `booking.php`: Select veterinary specialty, doctor, date/time slot, and confirm visit.
     - `cart.php`: Review items, apply promo code, choose delivery, and proceed to payment.
     - `profile.php`: Manage pet profiles, health records, order history, and active subscriptions.

4. **Navigation & Discoverability Standards**:
   - "Not in the landing page" does NOT mean hidden. All Tier 2 utilities must be prominently discoverable via:
     - **Curated Bento Teasers on the Homepage** (1-click direct link).
     - **Global Header "ابزارها و خدمات" Dropdown** (Desktop).
     - **Mobile Bottom Navigation & Categories Bottom Sheet** (Mobile).
     - **Contextual Cross-Links** (e.g., Pet Shop checkout suggests Autoship; Product page links to Calorie Calculator).
