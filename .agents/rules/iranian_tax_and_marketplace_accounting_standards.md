# ASENA Enterprise - Iranian Tax Compliance, Marketplace Accounting & Payout Standards

This document establishes the mandatory standards for Iranian tax compliance, marketplace financial modeling, Paya payout integration, panel configuration, and legal tax optimization for the ASENA platform.

---

## 1. Core Marketplace Entity Invariant (The Anti-Tax-Trap Principle)

1. **Brokerage/Agency Identity (ماده ۱۰ قانون مدنی و قرارداد عاملیت فروش):**
   - ASENA is legally a **Technology Brokerage Platform (حق‌العمل‌کاری و واسطه‌گری فناوری)**, not a direct reseller or inventory holder of marketplace goods and veterinary services.
   - **Gross Merchandise Value (GMV) vs. Revenue:**
     $$\text{GMV (گردش ناخالص درگاه)} = \text{سهم تامین‌کننده (امانت)} + \text{کارمزد خالص آسنا} + \text{کرایه پستی} + \text{مالیات}$$
     $$\text{ASENA Recognized Revenue (درآمد عملیاتی آسنا)} = \text{Platform Commission (15\% or agreed fee)}$$
   - Under no circumstances shall the entire GMV deposited via ZarinPal/Shaparak be accounted as ASENA's gross sales in accounting records, financial statements, or tax declarations.
   - The 85% provider portion is an escrowed fiduciary liability (حساب‌های پرداختنی / وجوه امانی اشخاص ثالث - کد حسابداری بستانکاران تجاری).

---

## 2. Iranian Tax Pillars & Obligations

### 2.1. 10% Statutory VAT (مالیات بر ارزش افزوده قانون دائمی ۱۴۰۰ و قانون بودجه)
- **Standard Rate:** 10% (9% statutory general VAT + 1% health/education earmark).
- **Taxation Point:**
  - **Platform Service (کارمزد آسنا):** The 15% platform commission is subject to 10% VAT.
    $$\text{VAT on Commission} = \text{Commission Amount} \times 0.10$$
  - **Goods & Prescriptions (کالای پت‌شاپ و دارو):** Pet shop supplies are taxable at 10%. Veterinary medicines with authorized national health codes (IRC) may follow preferential or exempt rates according to Iranian Food and Drug Administration / Veterinary Organization directives.
- **Reporting Schedule:** Quarterly VAT return (اظهارنامه مالیات بر ارزش افزوده) submitted within 15 days following the end of each solar calendar season (بهار، تابستان، پاییز، زمستان) via `my.tax.gov.ir`.
- **Input Tax Offset (اعتبار مالیاتی خرید):** VAT paid on hosting (ParsPack), SMS gateways (Melipayamak), office rent, software tools, and corporate equipment shall be deducted from collected VAT before remittance.

### 2.2. Seasonal Transaction Reports (معاملات فصلی ماده ۱۶۹ و ۱۶۹ مکرر ق.م.م)
- **Filing Deadline:** Within 45 days after the end of each solar calendar season.
- **Small Transaction Threshold Exemption (حد نصاب معاملات کوچک):**
  - Transactions below 5% of the annual small transaction limit (حد نصاب معاملات کوچک مصوب هیئت وزیران) can be filed in aggregate (تجمیعی) as final end-consumers (مصرف‌کننده نهایی) without requiring national IDs.
  - Transactions exceeding the 5% threshold strictly require the buyer's/seller's verified National ID (کد ملی / شناسه ملی), Economic Code, and Postal Code.
- **Platform B2B Brokerage Submissions:**
  - In Article 169 submissions, ASENA reports the **Platform Commission Invoices** issued to vendors/clinics under the "Service Revenue / Brokerage Fee (ارائه خدمات و کارمزد)" section.

### 2.3. Electronic Tax Invoices & Taxpayer System (سامانه مودیان و پایانه‌های فروشگاهی)
- **POS / Payment Gateway Coupling:** Every active online gateway and bank account is linked to the National Tax Administration (سازمان امور مالیاتی کشور).
- **Electronic Invoice Types:**
  - Platform commission invoices must be digitally signed (RSA RS256/ECC) using ASENA's Corporate Tax Memory ID (شناسه یکتای حافظه مالیاتی) and transmitted to the Taxpayer Portal (سامانه مودیان) either directly or via certified Trust Service Providers (معتمد مالیاتی TSP).
  - Use appropriate standard transaction pattern codes (الگوی صورتحساب کارمزدی / واسطه‌ای).

### 2.4. Annual Corporate Income Tax Return (اظهارنامه عملکرد اشخاص حقوقی - ماده ۱۱۰ ق.م.م)
- **Filing Deadline:** Within 4 months following the corporate fiscal year-end (normally by Tir 31st).
- **Base Rate:** 25% on net taxable operating income (سود خالص عملیاتی پس از کسر هزینه‌های قابل قبول).
- **Statutory Books (دفاتر قانونی):** Mandatory maintenance of registered Journal and General Ledgers (دفتر روزنامه و دفتر کل پلمپ‌شده).

### 2.5. Withholding Tax & Social Security (مالیات تکلیفی و بیمه تامین اجتماعی)
- **Doctor Consultation Fees:** Deduct statutory withholding tax (10% علی‌الحساب پزشکان) when applicable per annual budget regulations and remit to the tax authority.
- **Social Security Contractor Liability (ماده ۳۸ قانون تامین اجتماعی):** Standard electronic vendor and clinic terms of service (ToS) must classify partnerships as non-exclusive e-commerce platform marketplace affiliations to preclude manual contractor insurance withholding deductions.

---

## 3. Payout Batch Integration & Double-Entry Invariants

1. **Central Bank Paya Cycle Alignment (حواله پایا):**
   - The weekly settlement engine compiles verified orders (7-day post-delivery inspection window passed) into Paya batches (`seller_payout_batches`).
   - Every Paya batch generation automatically produces two linked records:
     1. **Paya Remittance Receipt (`actions/generate_payout_receipt.php`):** Proving payment of 85% escrow to the vendor's registered IBAN (`bank_sheba`).
     2. **B2B Platform Service Fee Invoice:** Documenting ASENA's 15% net commission + 10% VAT on commission, providing the vendor with valid accounting proof for their own tax deductions.
2. **Autoship Zero-Commission Accounting:**
   - On Autoship replenishment orders, ASENA earns 0 Toman commission ($0.00\%$).
   - The seller receives 100% of the product revenue.
   - ASENA books 0 Toman revenue and 0 Toman commission VAT for Autoship items, eliminating tax liabilities on unearned margins.
3. **Platform-Funded Promo Code Deductions:**
   - Promo discounts absorbed by ASENA reduce the taxable platform commission and must be booked as commercial discounts (تخفیفات تجاری اعطایی) under Article 148 of the Direct Taxation Code, reducing corporate tax exposure.

---

## 4. Admin Panel & System Configuration Standards

1. **Finance Settings (`admin/finance_settings.php`):**
   - Provide explicit configuration for:
     - `tax_rate_percent`: 10.0%
     - `platform_commission_percent`: 15.0%
     - `tax_memory_id`: 6-character Unique Tax Memory ID for Samaneh Moadiyan.
     - `tax_private_key_pem`: Encrypted RSA key storage path.
     - `tsp_provider_driver`: Direct / Saman Kish / Novin / Paya TSP driver.
     - `tax_reporting_mode`: `net_commission_brokerage` (enforcing brokerage tax calculation).
2. **Automated TTMS / Article 169 Quarterly Export:**
   - The admin panel must provide a 1-click export of quarterly B2B transactions formatted for the TTMS / Tax Portal upload (National ID, Economic Code, Commission Subtotal, VAT Amount).
3. **National ID & Postal Code Verification:**
   - Vendor onboarding must require a verified National ID (کد ملی / شناسه ملی), Economic Code, and Postal Code prior to processing automated Paya payouts above statutory thresholds.

---

## 5. Legal Tax Optimization & Minimization Strategies (اجتناب قانونی و بهینه‌سازی مالیات)

1. **Net Commission Recognition (شناسایی خالص کارمزد):**
   - Prevent 10x over-taxation by strictly recording solely the 15% commission as gross revenue in financial statements (IFRS 15 / Standard 15 of Iran Accounting Standards).
2. **Input Tax Credit Maximum Exploitation (استفاده حداکثری از اعتبار ارزش افزوده):**
   - Collect certified official VAT invoices (فاکتور رسمی الکترونیکی با شناسه مودیان) for all operating expenses:
     - Cloud & Bare Metal Hosting (ParsPack)
     - SMS Services (Melipayamak)
     - Payment Gateway Commissions (ZarinPal / Shaparak)
     - Office rent, legal consulting, and developer hardware purchases.
   - Offset these against output VAT to minimize net VAT payable.
3. **Knowledge-Based Company Tax Exemption (معافیت دانش‌بنیان):**
   - Register ASENA's proprietary Telehealth AI routing engine and predictive Autoship subscription infrastructure with the Vice Presidency for Science and Technology (معاونت علمی و فناوری ریاست جمهوری).
   - Qualified Knowledge-Based products enjoy 0% corporate tax exemptions under Article 9 of the Knowledge-Based Production Leap Law (قانون جهش تولید دانش‌بنیان) for up to 15 years.
4. **Legitimate Deductible Expenses (هزینه‌های قابل قبول مالیاتی مواد ۱۴۷ و ۱۴۸ ق.م.م):**
   - Fully document software R&D amortization, marketing expenditure (Google Ads, local ads), employee insurance payroll, and server bandwidth to lower net profit subject to the 25% corporate tax.
5. **Charity & Social Responsibility Segregation:**
   - Contributions collected via `charity.php` must be deposited directly into designated non-profit accounts or clear trust accounts under Article 139 of the Direct Taxation Code, completely quarantined from platform operational revenue.
