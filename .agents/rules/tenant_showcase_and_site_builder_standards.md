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
