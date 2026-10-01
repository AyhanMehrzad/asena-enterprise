# ASENA Enterprise - Tenant Showcase & Visual Site Builder Standards

This standard defines the mandatory architectural, aesthetic, and mobile ergonomics requirements for tenant microsites and the no-code site builder studio across all platform roles (Doctor, Clinic, Pharmacist, Seller).

---

## 1. Multi-Tier Website Archetypes

Every tenant website must reflect one of the 5 official ASENA capability tiers defined in `config/tiers.php`:

1. **Basic Clinic & Shop (`basic`)**:
   - Fast 1-click appointment calendar, essentials inventory showcase, local practice credentials, verified phone and address.
2. **Standard Commercial (`standard`)**:
   - Everything in Basic plus scientific health knowledge base/blog articles, customer loyalty points banner, Bayesian verified review badges.
3. **Premium Full Clinic (`premium`)**:
   - Adds Autoship periodic recurring delivery widgets (10-15% discount), Telehealth live video consultation launcher, emergency 24/7 care hotline with pulse animation, and custom wellness boxes.
4. **Pharmacy Care (`pharmacy`)**:
   - Direct electronic prescription (Rx) photo upload prompt, cold-chain assurance badges (2-8°C temperature control), OTC medicine and pet supplement catalogs, and pharmacist consultation.
5. **Enterprise Ecosystem (`enterprise`)**:
   - All-in-one luxury ecosystem unifying hospital departments, surgical theater bento grid, specialist physician rosters, cold-chain pharmacy dispatch, and full pet shop catalog.

---

## 2. Mobile Ergonomics & Thumb Zone Priority

- **Sticky Bottom Action Bar**: Websites must feature a floating, glassmorphic conversion bar on screens under 768px (`fixed bottom-0 inset-x-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-t border-slate-200/80 p-3`).
- **Thumb-Zone CTAs**: Primary action button ("رزرو آنلاین نوبت" / "خرید کالا") must have a minimum touch target of 52dp height, accompanied by direct 1-tap phone and navigation buttons.
- **Responsive Customizer Studio**: On mobile viewports, the split-screen customizer studio must transform into a tabbed layout toggling seamlessly between "تنظیمات و بلوک‌ها" and "پیش‌نمایش زنده".

---

## 3. High-Value Visual Depth & Agency-Grade Aesthetics

- **Ambient Lighting & Glassmorphism**: Avoid flat monotone white/gray containers. Use ambient radial gradients, subtle border highlights (`border border-white/60`), and rounded-3xl cards with soft layered shadows.
- **Social Proof Counter Strip**: Showcase real-time operational metrics (`+۱۵,۰۰۰ ویزیت موفق`، `۴.۹★ رضایت مراجعین`، `۱۰۰٪ پرداخت امن درگاه شاپرک آسنا`).
- **Asymmetric Bento Grid**: Organize clinic departments, surgical facilities, and modern diagnostics into an engaging bento grid.
- **Live Duty Pulsing**: Prominently display active clinic status with animated pulsing indicators (`🟢 پذیرش فعال - نوبت‌دهی آنلاین`).

---

## 4. Financial & Regulatory Guardrails

- All transactions must remain strictly bound to ASENA's centralized gateway (`asena.company`) and escrow engine.
- Tenants cannot inject independent bank gateways; ASENA acts as the licensed brokerage platform handling 10% VAT and settling 85% net earnings into the provider's wallet.

---

## 5. Bespoke Luxury Feel & Anti-Generic Mandates

- **Symbiotic Brand Aura (Preserve ASENA Trust + Bespoke Tenant Identity)**:
  - **Never totally disconnect from ASENA DNA:** The tenant website must maintain ASENA's foundational credibility:
    - Official Verified Member Badge (`عضو رسمی شبکه یکپارچه سلامت آسنا`).
    - Shaparak & Escrow Payment Security (`پرداخت امن بانکی و امانت‌داری مالی سامانه آسنا`).
    - Harmonious typography (`Geist` & `Vazirmatn`) and core luxury palette foundation (`#001a48` corporate navy, `#fd8100` warm accent).
  - **Distinct Tenant Aura:** The clinic/practitioner must feel independent and prestigious:
    - Dedicated hero storytelling, practitioner signature, custom photography, and tailored service menus.
    - Bespoke Curated Auras: Offer distinct visual atmospheres (Emerald Clinical, Hospital Corporate Navy, Vibrant Pet Companion, Midnight Velvet Luxury, Pure Aurora) that customize accent lighting without breaking brand prestige.
    - High-conversion interactive widgets (Cost Estimator, Duty Countdown, Emergency Hotline, Before/After Interactive Sliders).

- **Layered Visual Depth & Ambient Lighting**: Never render tenant websites as flat gray-and-white card stacks. Every section must have clear visual depth, subtle mesh radial lighting, and glassmorphic micro-borders.
- **Layered Floating Badges over Imagery**: Every clinical hero section must feature layered floating trust badges (e.g. `⭐️ ۴.۹ از ۱۸۰ نظر`, `🩺 بورد تخصصی جراحی و داخلی`, `⚡ پاسخگویی فوری در ۵ دقیقه`).
- **Interactive High-Intent Conversion Tools**:
  1. **Service Cost Estimator / Price Calculator Widget**: Pet parents can select pet type (Dog, Cat, Bird, Exotic) and service (General checkup, comprehensive vaccination, dental cleaning, surgery, ultrasound) to receive an instant transparent fee estimate and 1-click discount booking.
  2. **Interactive Before/After Comparison Slider**: High-conversion visual comparison tool with touch/mouse draggable divider for dental scaling, surgical recovery, and grooming transformations.
  3. **Dynamic Open/Closed Duty Widget**: Automatically computes whether the clinic is currently open, remaining minutes until shift closure, and the next available booking slot.
  4. **24/7 Red Emergency Care Banner**: Prominent crimson alert banner with 1-tap dialer for poisonings, vehicular accidents, and urgent clinical triage.
  5. **Structured FAQ Accordion**: Addresses top pet owner questions (fasting rules, pet passport requirements, home visits, medication cold-chain shipping).
  6. **1-Tap Navigation Hub**: Direct modal routing to Neshan, Balad, Waze, and Google Maps, paired with instant chat buttons for WhatsApp, Telegram, Etaa, and Bale.
- **Studio Drag/Arrow Block Reordering & Visibility Toggles**: The site builder studio must empower users to reorder any section via Up/Down buttons and toggle section visibility with single-click switches.


