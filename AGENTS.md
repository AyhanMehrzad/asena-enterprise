# ASENA Enterprise - Agent Guidelines & Development Standards

This document establishes the foundational architectural rules, coding standards, and development workflows for the ASENA Enterprise platform.

## Key Rules & References

1. **Feature Development Playbook**:
   - For all new features and capabilities, follow [`.agents/rules/feature_development_playbook.md`](file:///.agents/rules/feature_development_playbook.md).
   - Features must be declared in [`config/tiers.php`](file:///config/tiers.php) and verified via `Feature::has('feature_key')`.

2. **Project Guidelines & Aesthetics**:
   - Review [`PROJECT_GUIDELINES.md`](file:///PROJECT_GUIDELINES.md) for brand colors (`#001a48`, `#fd8100`), typography (`Geist` & `Vazirmatn`), and component design patterns.
   - For all landing and public surfaces, follow [`.agents/rules/enterprise_landing_page_ui_ux.md`](file:///.agents/rules/enterprise_landing_page_ui_ux.md) for 7-beat narrative hierarchy and bento grid layout.
   - All client-facing surfaces must be RTL (`dir="rtl" lang="fa"`).
   - Use Optimistic UI updates with micro-animations and non-blocking floating glass toasts.

3. **Backend Service & Action Standards**:
   - Business logic belongs in `includes/<Feature>Service.php` using PDO prepared statements.
   - AJAX endpoints belong in `actions/<feature>_action.php` returning standardized UTF-8 JSON.
   - Database migrations belong in `database/migrations/`.

4. **Multi-Tier Compatibility & Packaging**:
   - Use `bin/asena` CLI to check status (`php bin/asena status`) and build client bundles (`php bin/asena package --tier=<tier>`).

5. **Context Maintenance & Change Log**:
   - Whenever any feature or file is modified/added, immediately append a concise summary of the changes to Section 3 (Change Log) of [`PROJECT_CONTEXT.md`](file:///PROJECT_CONTEXT.md) to keep context lightweight, persistent, and synchronized.

6. **Git Branch & Production Deployment (`master`)**:
   - Always commit and push changes to `master` (in `asena.company` to `origin master`, and in `asena-enterprise` to `origin master` and `origin main`). cPanel automated production deployment runs off the `master` branch of `asena.company`.

7. **Marketplace Promo Code & Financial Privacy Standards**:
   - Strictly follow [`.agents/rules/marketplace_promo_and_financial_rules.md`](file:///.agents/rules/marketplace_promo_and_financial_rules.md).
   - Never expose the 15% platform commission to customers (display only item costs, promo discounts, and 10% statutory VAT).
   - Platform coupons must be absorbed from ASENA's 15% commission margin, preserving 85% provider net earnings.
   - Apply 10% VAT strictly on post-discount subtotals.

8. **Progressive Disclosure & App Simplification Standards**:
   - Strictly follow [`.agents/rules/progressive_disclosure_and_app_simplification.md`](file:///.agents/rules/progressive_disclosure_and_app_simplification.md).
   - Keep landing page (`index.php`) as a concise showcase; never embed heavyweight multi-step interactive tools (calculators, full booking calendars) directly inline.
   - Relocate tools to dedicated, single-purpose pages (`calculator.php`, `interactions.php`, `subscriptions.php`) and link to them prominently via Bento teasers and the global navigation bar.
