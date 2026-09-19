# ASENA Enterprise - Marketplace Promo Code & Financial Privacy Standards

This standard dictates how discount codes, platform commissions, taxation, and provider earnings are modeled across the ASENA platform.

## 1. Absolute Confidentiality of Platform Commission (15% Invariant)
- **Strict B2B Secrecy**: The 15% platform commission is strictly a private business-to-business agreement between ASENA and service/goods providers (clinics, doctors, pet shops, pharmacies, organizations).
- **Forbidden UI Elements**: Never mention "کارمزد پلتفرم", "سهم سایت", "درصد سامانه", or "15%" in any client-facing file or template (`cart.php`, checkout, `orders`, `profile.php`, receipts, SMS, or email templates).
- **Approved Customer Cost Breakdown**:
  1. جمع کل اقلام (Subtotal)
  2. تخفیف کد تخفیف (Promo Code Discount - green badge)
  3. مالیات بر ارزش افزوده (۱۰٪ مصوب قانونی) (10% Statutory VAT)
  4. مبلغ نهایی قابل پرداخت (Final Payable Amount via ZarinPal)

## 2. Promo Code Platform Margin Absorption Principle
- All marketplace promo codes (welcome codes, loyalty voucher redemptions, marketing vouchers) are funded directly from ASENA's 15% commission margin.
- **Provider Revenue Shield**:
  $$\text{Provider Net Payout} = \text{Item Gross Price} \times 0.85$$
  $$\text{ASENA Net Commission} = (\text{Item Gross Price} \times 0.15) - \text{Discount Amount}$$
- Providers must never suffer payout reductions or deductions because a buyer used an ASENA platform coupon.

## 3. Statutory VAT Sequence (Post-Discount Taxation)
- 10% VAT is calculated on the discounted subtotal:
  $$\text{Taxable Subtotal} = \max(0, \text{Gross Subtotal} - \text{Promo Discount})$$
  $$\text{VAT (10\%)} = \text{round}(\text{Taxable Subtotal} \times 0.10)$$
  $$\text{Final Total Payable} = \text{Taxable Subtotal} + \text{VAT} + \text{Shipping Cost}$$

## 4. Enterprise Promo Code Engine Constraints
- **Cap Enforcement**: All percentage-based promo codes must support a `max_discount_amount` (سقف تخفیف) to protect platform margins.
- **Min Subtotal Barrier**: Codes must enforce a `min_order_subtotal` threshold.
- **Per-User Limits**: Codes default to 1 use per user account / phone number unless explicitly configured otherwise.
- **Atomic Usage Ledger**: Every successful order application creates a permanent record in `promo_code_usages` to guarantee idempotency and auditability.
