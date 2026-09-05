-- =========================================================================
-- ASENA Enterprise - Migration 02: Multi-Role System & Organizations Schema
-- =========================================================================

-- 1. Extend Users table for role application tracking
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS pending_role VARCHAR(50) NULL AFTER role,
    ADD COLUMN IF NOT EXISTS verification_status ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none' AFTER pending_role;

-- 2. Organizations Table (Clinics, Hospitals, Specialty Centers, Shelters)
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    type ENUM('hospital', 'clinic', 'pharmacy', 'shelter_charity') DEFAULT 'clinic',
    license_number VARCHAR(100) NULL,
    license_document_url VARCHAR(255) NULL,
    manager_name VARCHAR(150) NULL,
    phone VARCHAR(50) NOT NULL,
    emergency_phone VARCHAR(50) NULL,
    email VARCHAR(150) NULL,
    website VARCHAR(255) NULL,
    instagram VARCHAR(100) NULL,
    province VARCHAR(100) NOT NULL DEFAULT 'تهران',
    city VARCHAR(100) NOT NULL DEFAULT 'تهران',
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    operating_hours VARCHAR(255) NOT NULL DEFAULT 'شنبه تا پنجشنبه ۸ الی ۲۲',
    is_24_7 TINYINT(1) DEFAULT 0,
    logo_url VARCHAR(255) NULL,
    banner_url VARCHAR(255) NULL,
    description TEXT NULL,
    facilities TEXT NULL,
    rating DECIMAL(2, 1) DEFAULT 5.0,
    review_count INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'approved',
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_org_slug (slug),
    INDEX idx_org_city (city),
    INDEX idx_org_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Organization-Doctor Affiliations (M:N)
CREATE TABLE IF NOT EXISTS organization_doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    doctor_id INT NOT NULL,
    is_head_physician TINYINT(1) DEFAULT 0,
    working_days VARCHAR(255) DEFAULT 'شنبه تا چهارشنبه',
    working_hours VARCHAR(100) DEFAULT '۱۶:۰۰ الی ۲۱:۰۰',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_org_doc (organization_id, doctor_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Organization Inventory (Specific stock and medicines at this facility)
CREATE TABLE IF NOT EXISTS organization_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    item_type ENUM('product', 'medicine') DEFAULT 'medicine',
    item_id INT NOT NULL,
    stock INT DEFAULT 10,
    custom_price DECIMAL(12, 2) NULL,
    is_in_stock TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_org_item (organization_id, item_type, item_id),
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Professional Role Applications & Degree Verification Queue
CREATE TABLE IF NOT EXISTS role_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    applied_role ENUM('doctor', 'pharmacist', 'organization', 'supplier') NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    license_number VARCHAR(100) NULL,
    specialty VARCHAR(150) NULL,
    degree_document_url VARCHAR(255) NULL,
    license_document_url VARCHAR(255) NULL,
    organization_name VARCHAR(255) NULL,
    organization_type VARCHAR(50) NULL,
    website VARCHAR(255) NULL,
    instagram VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    address TEXT NULL,
    status ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at DATETIME NULL,
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_app_status (status),
    INDEX idx_app_role (applied_role),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Seed Sample Enterprise Organizations
INSERT INTO organizations (name, slug, type, license_number, manager_name, phone, emergency_phone, email, website, instagram, province, city, address, operating_hours, is_24_7, logo_url, banner_url, description, facilities, rating, review_count, status)
VALUES 
(
    'بیمارستان فوق تخصصی دامپزشکی پایتخت',
    'payetakht-hospital',
    'hospital',
    'IR-VET-HOSP-9481',
    'دکتر کامران شایان',
    '02188776655',
    '09146676978',
    'info@payetakht-vet.ir',
    'https://payetakht-vet.ir',
    'payetakht_vet_hospital',
    'تهران',
    'تهران',
    'تهران، خیابان ولیعصر، بالاتر از پارک ساعی، نبش کوچه شمس',
    'شبانه روزی ۲۴/۷ (شامل اورژانس و ICU)',
    1,
    'assets/images/logo.png',
    'assets/images/cat-hero.jpg',
    'مجهزترین مرکز درمانی، جراحی و تشخیصی حیوانات خانگی کشور با کادر اساتید دانشگاهی و بخش‌های بستری مجزا برای سگ و گربه.',
    'بخش جراحی قلب و استخوان، رادیولوژی دیجیتال، سونوگرافی داپلر، آزمایشگاه تخصصی، بستری ایزوله عفونی، آمبولانس حیوانات',
    4.9,
    148,
    'approved'
),
(
    'کلینیک تخصصی و جراحی پرشین پت',
    'persian-pet-clinic',
    'clinic',
    'IR-VET-CLN-8812',
    'دکتر هما مهرزاد',
    '02122334455',
    '09121234567',
    'contact@persianpetclinic.com',
    'https://persianpetclinic.com',
    'persian_pet_clinic',
    'تهران',
    'تهران',
    'تهران، سعادت‌آباد، میدان کاج، خیابان سرو غربی، پلاک ۲۸',
    'شنبه تا پنجشنبه ۹ الی ۲۱',
    0,
    'assets/images/logo.png',
    'assets/images/dog-avatar.svg',
    'ارائه کلیه خدمات واکسیناسیون، دندانپزشکی، جراحی بافت نرم، عقیم‌سازی و مشاوره تغذیه با پیشرفته‌ترین دستگاه‌های بیهوشی استنشاقی.',
    'جراحی بافت نرم، یونیت دندانپزشکی اولتراسونیک، پت‌شاپ مجهز، آرایش و شستشوی بهداشتی',
    4.8,
    92,
    'approved'
)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- 7. Link Existing Doctors to Organizations
INSERT INTO organization_doctors (organization_id, doctor_id, is_head_physician, working_days, working_hours)
SELECT 1, id, 1, 'شنبه تا چهارشنبه', '۱۵:۰۰ الی ۲۱:۰۰' FROM doctors LIMIT 1
ON DUPLICATE KEY UPDATE is_head_physician=VALUES(is_head_physician);

INSERT INTO organization_doctors (organization_id, doctor_id, is_head_physician, working_days, working_hours)
SELECT 2, id, 0, 'یکشنبه و سه‌شنبه', '۱۰:۰۰ الی ۱۸:۰۰' FROM doctors ORDER BY id DESC LIMIT 1
ON DUPLICATE KEY UPDATE is_head_physician=VALUES(is_head_physician);

-- 8. Seed Hospital Clinic Pharmacy Inventory
INSERT INTO organization_inventory (organization_id, item_type, item_id, custom_price, stock, is_in_stock)
VALUES 
(1, 'medicine', 1, 280000, 20, 1),
(1, 'medicine', 2, 195000, 15, 1),
(1, 'product', 1, 650000, 8, 1),
(2, 'medicine', 3, 310000, 12, 1),
(2, 'product', 2, 95000, 40, 1)
ON DUPLICATE KEY UPDATE stock=VALUES(stock);
