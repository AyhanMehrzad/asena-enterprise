-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: asena_premium
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pet_type` varchar(50) DEFAULT NULL,
  `pet_race` varchar(50) DEFAULT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `pet_name` varchar(255) DEFAULT NULL,
  `pet_gender` varchar(50) DEFAULT NULL,
  `pet_age` varchar(50) DEFAULT NULL,
  `pet_weight` decimal(5,2) DEFAULT NULL,
  `visit_purpose` varchar(255) DEFAULT NULL,
  `pet_notes` text DEFAULT NULL,
  `fee` int(11) NOT NULL DEFAULT 350000,
  `commission_amount` int(11) NOT NULL DEFAULT 0,
  `net_amount` int(11) NOT NULL DEFAULT 0,
  `service_type` varchar(50) DEFAULT 'consultation',
  `settlement_status` varchar(50) DEFAULT 'held_in_escrow',
  `settlement_batch_id` int(11) DEFAULT NULL,
  `doctor_diagnosis` text DEFAULT NULL,
  `doctor_prescription` text DEFAULT NULL,
  `reschedule_reason` varchar(500) DEFAULT NULL,
  `rescheduled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_doc_date` (`doctor_id`,`appointment_date`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_org_id` (`organization_id`),
  KEY `idx_settlement_batch` (`settlement_batch_id`),
  CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,NULL,2,4,NULL,'2026-07-26','17:30','completed','2026-07-24 15:53:19','گربه','persian',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','available_for_payout',NULL,NULL,NULL,NULL,NULL),(2,NULL,5,4,NULL,'2026-07-26','09:00','cancelled','2026-07-25 01:38:32','سگ','Husky',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL),(3,NULL,6,4,NULL,'2026-07-26','16:45','cancelled','2026-07-25 02:04:34','سگ','Husky',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL),(4,NULL,7,4,NULL,'2026-08-01','08:00','pending','2026-07-25 12:57:01','سگ','bulldog',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL),(5,NULL,2,4,NULL,'2026-08-02','09:45','cancelled','2026-07-31 19:10:41','سگ','germenshepert',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL),(6,NULL,2,4,NULL,'2026-08-08','08:45','pending','2026-08-01 17:03:51','گربه','persian',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL),(7,NULL,11,4,NULL,'2026-08-08','17:30','pending','2026-08-03 11:54:19','سگ','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL),(8,NULL,11,5,NULL,'2026-08-12','18:15','pending','2026-08-03 12:39:51','سگ','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL),(9,NULL,12,4,NULL,'2026-08-22','08:45','pending','2026-08-18 13:00:21','سگ','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,350000,0,0,'consultation','held_in_escrow',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autoship_plans`
--

DROP TABLE IF EXISTS `autoship_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoship_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `interval_months` int(11) NOT NULL,
  `discount_percent` int(11) NOT NULL DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autoship_plans`
--

LOCK TABLES `autoship_plans` WRITE;
/*!40000 ALTER TABLE `autoship_plans` DISABLE KEYS */;
INSERT INTO `autoship_plans` VALUES (1,'اشتراک ۳ ماهه',3,5,'2026-07-25 01:51:50'),(2,'اشتراک ۶ ماهه',6,10,'2026-07-25 01:51:50'),(3,'اشتراک ۱۲ ماهه',12,20,'2026-07-25 01:51:50');
/*!40000 ALTER TABLE `autoship_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `autoship_subscriptions`
--

DROP TABLE IF EXISTS `autoship_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `autoship_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `next_delivery_date` date NOT NULL,
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plan_id` (`plan_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `autoship_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `autoship_subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `autoship_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `autoship_subscriptions_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `autoship_subscriptions`
--

LOCK TABLES `autoship_subscriptions` WRITE;
/*!40000 ALTER TABLE `autoship_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `autoship_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_rfq_items`
--

DROP TABLE IF EXISTS `b2b_rfq_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_rfq_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rfq_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `is_pharmacy` tinyint(1) DEFAULT 0,
  `product_title` varchar(255) NOT NULL,
  `requested_quantity` int(11) NOT NULL,
  `target_unit_price` int(11) DEFAULT NULL,
  `quoted_unit_price` int(11) DEFAULT NULL,
  `quoted_notes` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rfq_item` (`rfq_id`),
  CONSTRAINT `fk_rfq_item` FOREIGN KEY (`rfq_id`) REFERENCES `b2b_rfqs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_rfq_items`
--

LOCK TABLES `b2b_rfq_items` WRITE;
/*!40000 ALTER TABLE `b2b_rfq_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `b2b_rfq_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_rfqs`
--

DROP TABLE IF EXISTS `b2b_rfqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_rfqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `clinic_name` varchar(255) NOT NULL,
  `vet_license_num` varchar(100) DEFAULT NULL,
  `contact_person` varchar(150) NOT NULL,
  `contact_phone` varchar(50) NOT NULL,
  `status` enum('submitted','under_review','quoted','accepted','rejected','expired') DEFAULT 'submitted',
  `target_delivery_date` date DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `proforma_invoice_num` varchar(100) DEFAULT NULL,
  `quoted_total_amount` int(11) DEFAULT NULL,
  `quote_valid_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rfq_user` (`user_id`),
  KEY `idx_rfq_status` (`status`),
  CONSTRAINT `fk_rfq_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_rfqs`
--

LOCK TABLES `b2b_rfqs` WRITE;
/*!40000 ALTER TABLE `b2b_rfqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `b2b_rfqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(500) NOT NULL,
  `short_desc` text DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'medical',
  `category_name` varchar(100) NOT NULL DEFAULT 'پزشکی و سلامت',
  `content` longtext NOT NULL,
  `faqs_json` longtext DEFAULT NULL,
  `author_name` varchar(255) NOT NULL DEFAULT 'آسنا',
  `author_role` varchar(255) DEFAULT 'تیم تخصصی آسنا',
  `icon` varchar(50) DEFAULT 'article',
  `accent_color` varchar(100) DEFAULT 'from-blue-600 to-indigo-700',
  `read_time` varchar(50) DEFAULT '۵ دقیقه مطالعه',
  `created_by_user_id` int(11) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `campaigns`
--

DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `goal_amount` int(11) NOT NULL,
  `current_amount` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','completed','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `campaigns`
--

LOCK TABLES `campaigns` WRITE;
/*!40000 ALTER TABLE `campaigns` DISABLE KEYS */;
INSERT INTO `campaigns` VALUES (1,'Save Homeless Animals','Please help us raise funds for our shelter to support homeless pets. Every small donation counts.',10000000,1000000,'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=500','active','2026-08-01 13:57:26'),(2,'پویش واکسیناسیون و عقیم‌سازی حیوانات خیابانی','تامین واکسن‌های هاری و هفت‌گانه و خدمات درمان فوری برای فرشتگان بی‌سرپرست در کلینیک تخصصی آسنا.',18000000,9700000,'https://images.unsplash.com/photo-1548767797-d8c844163c4c?w=800','active','2026-09-07 14:45:02'),(3,'تامین غذای گرم و جیره زمستانه پناهگاه','خرید و توزیع غذای خشک باکیفیت و مکمل‌های تقویتی برای سگ‌ها و گربه‌های آسیب‌دیده پناهگاه‌های حومه.',12000000,1450000,'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=800','active','2026-09-07 14:45:02');
/*!40000 ALTER TABLE `campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender_type` enum('user','admin','ai') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (1,6,'user','[AI] Hello, how can I feed my cat?',0,'2026-07-25 02:00:20'),(2,6,'ai','این یک پیام خودکار از دستیار هوشمند پت‌کر است. شما پرسیدید: \'Hello, how can I feed my cat?\'. در حال حاضر من در فاز آزمایشی هستم.',1,'2026-07-25 02:00:20');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curated_recommendations`
--

DROP TABLE IF EXISTS `curated_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curated_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slot_type` enum('banner','notification','cart_upsell','spotlight') NOT NULL,
  `product_id` int(11) NOT NULL,
  `custom_badge` varchar(100) DEFAULT NULL,
  `custom_title` varchar(255) DEFAULT NULL,
  `custom_subtitle` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_slot` (`slot_type`,`is_active`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_rec_prem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curated_recommendations`
--

LOCK TABLES `curated_recommendations` WRITE;
/*!40000 ALTER TABLE `curated_recommendations` DISABLE KEYS */;
INSERT INTO `curated_recommendations` VALUES (1,'banner',1,'🔥 پرفروش‌ترین محصول ماه','غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult','تغذیه کامل و بهینه شده، هم‌اکنون با ۱۰٪ تخفیف اشتراک خودکار',1,1,'2026-08-25 15:21:55'),(2,'notification',2,'⚡ شگفت‌انگیز هفتگی','کنسرو گربه گورمت گلد با طعم مرغ در تخفیف ویژه به مدت محدود!','خرید آنلاین و تحویل اکسپرس درب منزل',1,1,'2026-08-25 15:21:55'),(3,'cart_upsell',6,'⭐ مکمل پیشنهادی','قطره مولتی ویتامین سگ و گربه شایر','پیشنهاد طلایی برای شادابی و درخشش موی پت شما',1,1,'2026-08-25 15:21:55'),(4,'spotlight',7,'🐱 سرگرمی خانگی','درخت گربه ۳ طبقه کدیپک','بهترین انتخاب برای استراحت و اسکرچ گربه‌ها',1,1,'2026-08-25 15:21:55');
/*!40000 ALTER TABLE `curated_recommendations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_events`
--

DROP TABLE IF EXISTS `dashboard_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dashboard_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `event_time` varchar(5) NOT NULL,
  `color` varchar(20) DEFAULT 'primary',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_events`
--

LOCK TABLES `dashboard_events` WRITE;
/*!40000 ALTER TABLE `dashboard_events` DISABLE KEYS */;
INSERT INTO `dashboard_events` VALUES (1,'شروع شیفت کلینیک','08:00','primary','2026-07-25 02:30:42'),(2,'بررسی سفارشات','12:00','secondary','2026-07-25 02:30:42');
/*!40000 ALTER TABLE `dashboard_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctor_blocked_slots`
--

DROP TABLE IF EXISTS `doctor_blocked_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctor_blocked_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `block_date` date NOT NULL,
  `start_time` varchar(20) DEFAULT NULL,
  `end_time` varchar(20) DEFAULT NULL,
  `reason` varchar(255) DEFAULT 'نوبت تلفنی / خارج از سامانه',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc` (`doctor_id`),
  KEY `idx_org` (`organization_id`),
  CONSTRAINT `fk_block_doc` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctor_blocked_slots`
--

LOCK TABLES `doctor_blocked_slots` WRITE;
/*!40000 ALTER TABLE `doctor_blocked_slots` DISABLE KEYS */;
/*!40000 ALTER TABLE `doctor_blocked_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `specialty` varchar(255) NOT NULL,
  `provider_type` varchar(50) NOT NULL DEFAULT 'doctor',
  `rating` decimal(2,1) DEFAULT 5.0,
  `baseline_rating` decimal(3,1) DEFAULT 4.9,
  `review_count` int(11) DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `price` int(11) DEFAULT 450000,
  `clinic_name` varchar(255) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `is_emergency` tinyint(1) NOT NULL DEFAULT 0,
  `bio` text DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `services_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `schedule_info` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_provider_type` (`provider_type`),
  KEY `idx_org_id` (`organization_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES (4,'akbar nami','پزشک عمومی','doctor',5.0,4.9,0,'uploads/doctors/6a63b8fcdf04e_329748003990695799.jpeg',150000,NULL,NULL,0,NULL,NULL,NULL,'2026-07-23 20:12:40',4,'09990999','{\"sat\":{\"m_start\":\"08:00\",\"m_end\":\"14:00\",\"a_start\":\"16:00\",\"a_end\":\"21:00\"},\"sun\":{\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_start\":\"16:00\",\"a_end\":\"20:00\"}}'),(5,'ali','vet','doctor',5.0,4.9,0,'uploads/doctors/6a63b90c61a3d_doctor kitty.jpeg',600000,NULL,NULL,0,NULL,NULL,NULL,'2026-07-23 20:35:20',3,NULL,'{\"sat\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"sun\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"mon\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"tue\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"wed\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"thu\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"},\"fri\":{\"m_active\":true,\"m_start\":\"09:00\",\"m_end\":\"13:00\",\"a_active\":true,\"a_start\":\"16:00\",\"a_end\":\"20:00\"}}'),(8,'doctor','عمومی','doctor',5.0,4.9,0,NULL,450000,NULL,NULL,0,NULL,NULL,NULL,'2026-08-03 08:24:56',10,'user.doctor',NULL),(9,'دکتر سهراب علوی','بورد تخصصی جراحی ارتوپدی و ستون فقرات','doctor',4.9,4.9,84,'assets/images/vet-hero.png',380000,NULL,NULL,0,NULL,NULL,NULL,'2026-09-05 11:50:26',NULL,'09121112233','شنبه، دوشنبه، چهارشنبه از ساعت ۱۶:۰۰ الی ۲۱:۰۰'),(10,'دکتر هما مهرزاد','متخصص بیماری‌های داخلی و تصویربرداری تشخیصی','doctor',4.9,4.9,96,'assets/images/vet-hero.png',320000,NULL,NULL,0,NULL,NULL,NULL,'2026-09-05 11:50:26',24,'09122223344','یکشنبه، سه‌شنبه، پنجشنبه از ساعت ۱۰:۰۰ الی ۱۸:۰۰'),(11,'دکتر کامران شایان','متخصص جراحی بافت نرم و بیهوشی استنشاقی','doctor',4.8,4.9,67,'assets/images/vet-hero.png',350000,NULL,NULL,0,NULL,NULL,NULL,'2026-09-05 11:50:26',NULL,'09123334455','همه روزه به جز جمعه از ساعت ۱۴:۰۰ الی ۲۰:۰۰'),(12,'دکتر مریم صادقی','متخصص دندانپزشکی و جرم‌گیری اولتراسونیک پت','doctor',4.9,4.9,52,'assets/images/vet-hero.png',290000,NULL,NULL,0,NULL,NULL,NULL,'2026-09-05 11:50:26',NULL,'09124445566','شنبه تا چهارشنبه از ساعت ۹:۰۰ الی ۱۵:۰۰'),(13,'دکتر پوریا رستمی','فوق‌تخصص پرندگان زینتی، طوطی‌سانان و حیوانات اگزوتیک','doctor',4.8,4.9,73,'assets/images/vet-hero.png',310000,NULL,NULL,0,NULL,NULL,NULL,'2026-09-05 11:50:26',NULL,'09125556677','یکشنبه و چهارشنبه از ساعت ۱۵:۰۰ الی ۲۱:۰۰'),(14,'دکتر نیلوفر بختیاری','متخصص مراقبت‌های ویژه (ICU) و اورژانس دامپزشکی','doctor',5.0,4.9,41,'assets/images/vet-hero.png',360000,NULL,NULL,0,NULL,NULL,NULL,'2026-09-05 11:50:26',NULL,'09126667788','شیفت شب و روزهای فرد به صورت ۲۴ ساعته'),(15,'امید رضایی','شستشو، اصلاح ژورنالی و گرومینگ تخصصی پت','groomer',4.9,4.9,38,'assets/images/vet-hero.png',400000,'سالن زیبایی و گرومینگ پایتخت',1,0,NULL,'گرومر,آرایشگاه پت,کوتاهی مو','[\"اصلاح ژورنالی سگ و گربه\",\"حمام درمانی و ضدانگل\",\"کوتاهی ناخن و فرم‌دهی\"]','2026-09-07 15:21:40',NULL,NULL,'شنبه تا چهارشنبه ۱۰:۰۰ الی ۱۹:۰۰'),(16,'امید رضایی','شستشو، اصلاح ژورنالی و گرومینگ تخصصی پت','groomer',4.9,4.9,38,'assets/images/vet-hero.png',400000,'سالن زیبایی و گرومینگ پایتخت',1,0,NULL,'گرومر,آرایشگاه پت,کوتاهی مو','[\"اصلاح ژورنالی سگ و گربه\",\"حمام درمانی و ضدانگل\",\"کوتاهی ناخن و فرم‌دهی\"]','2026-09-07 15:21:58',NULL,NULL,'شنبه تا چهارشنبه ۱۰:۰۰ الی ۱۹:۰۰');
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `donor_name` varchar(255) DEFAULT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `status` enum('pending','successful','failed') DEFAULT 'pending',
  `payment_reference` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `campaign_id` (`campaign_id`),
  CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
INSERT INTO `donations` VALUES (1,2,'ناشناس',1,50000,'successful','360976201','2026-08-01 14:30:46'),(2,2,'Sina',1,100000,'successful','360977501','2026-08-01 14:31:47'),(3,NULL,'ناشناس',NULL,3000000,'successful','TRX-81076211','2026-09-07 14:26:08'),(4,NULL,'دکتر گلزاری',1,250000,'successful','TRX-62178166','2026-09-07 14:44:44'),(5,NULL,'سارا رادمهر',2,1200000,'successful','TRX-91028341','2026-09-07 12:45:02'),(6,NULL,'امیرحسین کیانی',3,850000,'successful','TRX-71928401','2026-09-07 10:45:02'),(7,NULL,'مهندس علوی',2,500000,'successful','TRX-82937401','2026-09-06 14:45:02'),(8,NULL,'نیکوکار مهرآیین',3,300000,'successful','TRX-19284729','2026-09-05 14:45:02'),(9,NULL,'پرهام شریفی',1,600000,'successful','TRX-66692679','2026-09-07 14:46:52'),(10,NULL,'ناشناس',2,3000000,'successful','TRX-98810815','2026-09-07 14:48:16'),(11,NULL,'ناشناس',3,300000,'successful','TRX-41079533','2026-09-07 14:48:35'),(12,NULL,'ali',2,5000000,'successful','TRX-12249284','2026-09-07 14:49:04');
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flash_sales`
--

DROP TABLE IF EXISTS `flash_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flash_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `is_pharmacy` tinyint(1) DEFAULT 0,
  `special_price` int(11) NOT NULL,
  `stock_quota` int(11) NOT NULL DEFAULT 10,
  `claimed_count` int(11) NOT NULL DEFAULT 0,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `badge_text` varchar(100) DEFAULT 'پیشنهاد شگفت‌انگیز',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_flash_dates` (`starts_at`,`ends_at`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flash_sales`
--

LOCK TABLES `flash_sales` WRITE;
/*!40000 ALTER TABLE `flash_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `flash_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` int(11) NOT NULL,
  `product_name_snapshot` varchar(500) NOT NULL DEFAULT '',
  `seller_id` int(11) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `commission_amount` bigint(20) DEFAULT 0,
  `seller_net_amount` bigint(20) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_order_prod` (`order_id`,`product_id`),
  KEY `idx_seller_order` (`seller_id`,`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,3,1,1,1980000,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult',NULL,10.00,0,0),(2,3,8,1,280000,'شامپو ضد ریزش موی سگ تریکسی',NULL,10.00,0,0),(3,3,31,1,100000,'Cat Toy Mouse Updated',NULL,10.00,0,0),(4,4,1,1,1980000,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult',NULL,10.00,0,0),(5,4,31,1,100000,'Cat Toy Mouse Updated',NULL,10.00,0,0),(6,5,1,1,1980000,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult',NULL,10.00,0,0),(7,5,31,1,100000,'Cat Toy Mouse Updated',NULL,10.00,0,0),(8,6,32,1,740000,'خمیر ضد انگل آیورمکتین مخصوص اسب اکولان',NULL,10.00,0,0),(9,6,38,1,690000,'بلوس آهسته‌رهش کلسیم و ویتامین D3 گاو تازه زا',NULL,10.00,0,0);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_logs`
--

DROP TABLE IF EXISTS `order_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) NOT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_logs`
--

LOCK TABLES `order_logs` WRITE;
/*!40000 ALTER TABLE `order_logs` DISABLE KEYS */;
INSERT INTO `order_logs` VALUES (1,2,'delivered','processing','2026-07-25 02:49:02'),(2,2,'processing','delivered','2026-07-25 02:49:07'),(3,1,'cancelled','pending_payment','2026-07-25 02:49:18'),(4,1,'pending_payment','processing','2026-07-25 02:49:24'),(5,1,'processing','shipped','2026-07-25 02:49:27'),(6,1,'shipped','delivered','2026-07-25 02:49:32'),(7,3,'processing','shipped','2026-07-25 13:01:51'),(8,4,'processing','shipped','2026-07-31 19:11:16'),(9,3,'shipped','delivered','2026-07-31 19:11:21');
/*!40000 ALTER TABLE `order_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_status_logs`
--

DROP TABLE IF EXISTS `order_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_status_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) NOT NULL,
  `actor_type` enum('system','admin','doctor','user','carrier') DEFAULT 'admin',
  `actor_id` int(11) DEFAULT NULL,
  `carrier_name` varchar(100) DEFAULT NULL,
  `tracking_code` varchar(150) DEFAULT NULL,
  `tracking_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sms_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_log_order` (`order_id`),
  CONSTRAINT `fk_log_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_status_logs`
--

LOCK TABLES `order_status_logs` WRITE;
/*!40000 ALTER TABLE `order_status_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` int(11) NOT NULL,
  `discount_amount` int(11) DEFAULT 0,
  `status` enum('pending_payment','processing','shipped','delivered','cancelled') DEFAULT 'pending_payment',
  `gateway_ref_id` varchar(100) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `carrier_name` varchar(100) DEFAULT NULL,
  `tracking_code` varchar(150) DEFAULT NULL,
  `shipping_cost` int(11) DEFAULT 0,
  `tax_amount` int(11) DEFAULT 0,
  `post_tracking_code` varchar(50) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `post_delivery_verified` tinyint(1) DEFAULT 0,
  `escrow_status` enum('pending_delivery','delivered_in_inspection','cleared_for_payout','settled','disputed') DEFAULT 'pending_delivery',
  `escrow_cleared_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `idx_escrow_status` (`escrow_status`,`post_delivery_verified`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,6,5490000,0,'delivered',NULL,NULL,'2026-07-25 02:13:27',NULL,NULL,0,0,NULL,NULL,0,'pending_delivery',NULL),(2,6,5490000,0,'delivered',NULL,NULL,'2026-07-25 02:18:18',NULL,NULL,0,0,NULL,NULL,0,'pending_delivery',NULL),(3,7,2360000,0,'delivered',NULL,NULL,'2026-07-25 12:58:00',NULL,NULL,0,0,NULL,NULL,0,'pending_delivery',NULL),(4,2,2080000,0,'shipped',NULL,NULL,'2026-07-31 19:10:57',NULL,NULL,0,0,NULL,NULL,0,'pending_delivery',NULL),(5,11,2080000,0,'processing',NULL,NULL,'2026-08-03 11:54:02',NULL,NULL,0,0,NULL,NULL,0,'pending_delivery',NULL),(6,13,1430000,0,'processing',NULL,NULL,'2026-08-24 20:56:07',NULL,NULL,0,0,NULL,NULL,0,'pending_delivery',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_admins`
--

DROP TABLE IF EXISTS `organization_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_role` varchar(50) NOT NULL DEFAULT 'manager',
  `title` varchar(100) DEFAULT NULL,
  `permissions_json` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_user` (`organization_id`,`user_id`),
  KEY `idx_org_admins_org` (`organization_id`),
  KEY `idx_org_admins_user` (`user_id`),
  KEY `idx_org_admins_status` (`status`),
  CONSTRAINT `organization_admins_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `organization_admins_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_admins`
--

LOCK TABLES `organization_admins` WRITE;
/*!40000 ALTER TABLE `organization_admins` DISABLE KEYS */;
INSERT INTO `organization_admins` VALUES (2,1,18,'assistant_manager','معاون اجرایی و سرپرست شیفت','[\"manage_appointments\",\"manage_doctors\",\"manage_shifts\",\"manage_profile\",\"manage_inventory\",\"manage_orders\",\"manage_tickets\"]','active',1,'2026-09-07 16:57:14','2026-09-07 16:57:14'),(9,1,5,'owner','مدیر ارشد و موسس','[\"all\"]','active',5,'2026-09-07 18:07:05','2026-09-07 18:07:05');
/*!40000 ALTER TABLE `organization_admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_doctors`
--

DROP TABLE IF EXISTS `organization_doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `is_head_physician` tinyint(1) DEFAULT 0,
  `role_type` varchar(50) DEFAULT 'doctor',
  `working_days` varchar(255) DEFAULT 'شنبه تا چهارشنبه',
  `working_hours` varchar(100) DEFAULT '۱۶:۰۰ الی ۲۱:۰۰',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_org_doc` (`organization_id`,`doctor_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `idx_role_type` (`role_type`),
  CONSTRAINT `organization_doctors_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `organization_doctors_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_doctors`
--

LOCK TABLES `organization_doctors` WRITE;
/*!40000 ALTER TABLE `organization_doctors` DISABLE KEYS */;
INSERT INTO `organization_doctors` VALUES (1,1,4,1,'doctor','شنبه تا چهارشنبه','۱۵:۰۰ الی ۲۱:۰۰','2026-09-05 11:32:08'),(2,2,8,0,'doctor','یکشنبه و سه‌شنبه','۱۰:۰۰ الی ۱۸:۰۰','2026-09-05 11:32:08'),(3,1,11,1,'doctor','شنبه تا چهارشنبه','۱۴:۰۰ الی ۲۰:۰۰','2026-09-05 11:50:26'),(4,1,9,0,'doctor','یکشنبه و سه‌شنبه','۱۶:۰۰ الی ۲۱:۰۰','2026-09-05 11:50:26'),(5,1,14,0,'doctor','همه روزه (شیفت اورژانس)','۲۱:۰۰ الی ۰۸:۰۰','2026-09-05 11:50:26'),(6,4,9,1,'doctor','شنبه، دوشنبه، چهارشنبه','۱۶:۰۰ الی ۲۱:۰۰','2026-09-05 11:50:26'),(7,4,14,0,'doctor','روزهای فرد و پنجشنبه','۱۴:۰۰ الی ۲۲:۰۰','2026-09-05 11:50:26'),(8,2,10,1,'doctor','شنبه تا پنجشنبه','۱۰:۰۰ الی ۱۸:۰۰','2026-09-05 11:50:26'),(9,2,12,0,'doctor','یکشنبه و سه‌شنبه','۱۴:۰۰ الی ۲۰:۰۰','2026-09-05 11:50:26'),(10,6,12,1,'doctor','شنبه تا چهارشنبه','۰۹:۰۰ الی ۱۵:۰۰','2026-09-05 11:50:26'),(11,6,10,0,'doctor','پنجشنبه‌ها','۱۰:۰۰ الی ۱۷:۰۰','2026-09-05 11:50:26'),(12,7,13,1,'doctor','شنبه تا پنجشنبه','۱۰:۰۰ الی ۲۰:۰۰','2026-09-05 11:50:26'),(13,9,11,1,'doctor','جمعه‌ها (امداد و جراحی)','۰۹:۰۰ الی ۱۸:۰۰','2026-09-05 11:50:26'),(14,1,15,0,'groomer','شنبه تا چهارشنبه','۱۰:۰۰ الی ۱۹:۰۰','2026-09-07 15:21:40'),(15,1,16,0,'groomer','شنبه تا چهارشنبه','۱۰:۰۰ الی ۱۹:۰۰','2026-09-07 15:21:58'),(17,2,16,0,'doctor','یکشنبه و سه‌شنبه','۱۰:۰۰ الی ۱۸:۰۰','2026-09-09 15:21:19');
/*!40000 ALTER TABLE `organization_doctors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organization_inventory`
--

DROP TABLE IF EXISTS `organization_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `item_type` enum('product','medicine') DEFAULT 'medicine',
  `item_id` int(11) NOT NULL,
  `stock` int(11) DEFAULT 10,
  `custom_price` decimal(12,2) DEFAULT NULL,
  `is_in_stock` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_org_item` (`organization_id`,`item_type`,`item_id`),
  CONSTRAINT `organization_inventory_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organization_inventory`
--

LOCK TABLES `organization_inventory` WRITE;
/*!40000 ALTER TABLE `organization_inventory` DISABLE KEYS */;
INSERT INTO `organization_inventory` VALUES (1,1,'medicine',1,20,280000.00,1,'2026-09-05 11:32:08'),(2,1,'medicine',2,15,195000.00,1,'2026-09-05 11:32:08'),(3,1,'product',1,8,650000.00,1,'2026-09-05 11:32:08'),(4,2,'medicine',3,12,310000.00,1,'2026-09-05 11:32:08'),(5,2,'product',2,40,95000.00,1,'2026-09-05 11:32:08'),(6,1,'medicine',1,20,280000.00,1,'2026-09-09 15:21:19'),(7,1,'medicine',2,15,195000.00,1,'2026-09-09 15:21:19'),(8,1,'product',1,8,650000.00,1,'2026-09-09 15:21:19'),(9,2,'medicine',3,12,310000.00,1,'2026-09-09 15:21:19'),(10,2,'product',2,40,95000.00,1,'2026-09-09 15:21:19'),(11,1,'medicine',1,20,280000.00,1,'2026-09-09 16:30:17'),(12,1,'medicine',2,15,195000.00,1,'2026-09-09 16:30:17'),(13,1,'product',1,8,650000.00,1,'2026-09-09 16:30:17'),(14,2,'medicine',3,12,310000.00,1,'2026-09-09 16:30:17'),(15,2,'product',2,40,95000.00,1,'2026-09-09 16:30:17');
/*!40000 ALTER TABLE `organization_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `organizations`
--

DROP TABLE IF EXISTS `organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organizations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('hospital','clinic','pharmacy','shelter_charity','emergency_center','diagnostic_lab') DEFAULT 'clinic',
  `license_number` varchar(100) DEFAULT NULL,
  `license_document_url` varchar(255) DEFAULT NULL,
  `manager_name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `emergency_phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `province` varchar(100) NOT NULL DEFAULT 'تهران',
  `city` varchar(100) NOT NULL DEFAULT 'تهران',
  `address` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `operating_hours` varchar(255) NOT NULL DEFAULT 'شنبه تا پنجشنبه ۸ الی ۲۲',
  `is_24_7` tinyint(1) DEFAULT 0,
  `hide_doctors_roster` tinyint(1) DEFAULT 0,
  `direct_booking_enabled` tinyint(1) DEFAULT 1,
  `consultation_fee` int(11) DEFAULT 250000,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_sheba` varchar(30) DEFAULT NULL,
  `bank_account_holder` varchar(150) DEFAULT NULL,
  `bank_card_number` varchar(20) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `facilities` text DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 5.0,
  `review_count` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected','suspended') DEFAULT 'approved',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_org_slug` (`slug`),
  KEY `idx_org_city` (`city`),
  KEY `idx_org_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `organizations`
--

LOCK TABLES `organizations` WRITE;
/*!40000 ALTER TABLE `organizations` DISABLE KEYS */;
INSERT INTO `organizations` VALUES (1,5,'بیمارستان فوق تخصصی دامپزشکی پایتخت','payetakht-hospital','hospital','IR-VET-HOSP-9481',NULL,'دکتر کامران شایان','02188776655','09121112233','info@payetakht-vet.ir','https://payetakht-vet.ir','payetakht_vet_hospital','تهران','تهران','خیابان ولیعصر، بالاتر از پارک ساعی، نبش کوچه شمس، پلاک ۱۲',35.73500000,51.41100000,'شبانه روزی ۲۴/۷ (شامل اورژانس و ICU)',1,0,1,250000,'بانک سامان','IR120560000000100234567891','بیمارستان فوق تخصصی دامپزشکی پایتخت','6219861012345678','assets/images/organizations/payetakht-hospital-logo.svg','assets/images/cat-hero.jpg','مجهزترین مرکز درمانی، جراحی و تشخیصی حیوانات خانگی کشور با کادر اساتید دانشگاهی و بخش‌های بستری مجزا برای سگ و گربه.','بخش جراحی قلب و ارتوپدی, رادیولوژی دیجیتال DR, سونوگرافی کالر داپلر, انکوباتور اکسیژن ICU, آزمایشگاه تخصصی خون, آمبولانس اختصاصی, داروخانه شبانه‌روزی',5.0,4,'approved',NULL,'2026-09-05 11:32:08'),(2,NULL,'کلینیک تخصصی و جراحی پرشین پت','persian-pet-clinic','clinic','IR-VET-CLN-8812',NULL,'دکتر هما مهرزاد','02122334455','09122223344','contact@persianpetclinic.com','https://persianpetclinic.com','persian_pet_clinic','تهران','تهران','سعادت‌آباد، میدان کاج، خیابان سرو غربی، پلاک ۲۸، طبقه همکف',35.78200000,51.37400000,'شنبه تا پنجشنبه ۹:۰۰ الی ۲۲:۰۰',0,0,1,250000,'بانک ملت','IR550120000000004567891011','کلینیک تخصصی و جراحی پرشین پت',NULL,'assets/images/organizations/persian-pet-clinic-logo.svg','assets/images/dog-avatar.svg','ارائه کلیه خدمات واکسیناسیون، دندانپزشکی، جراحی بافت نرم، عقیم‌سازی و مشاوره تغذیه با پیشرفته‌ترین دستگاه‌های بیهوشی استنشاقی.','جراحی بافت نرم و عقیم‌سازی, یونیت دندانپزشکی اولتراسونیک, پت‌شاپ دارویی, آرایش و شستشوی طبی, میکروچیپ و شناسنامه بین‌المللی',4.8,92,'approved',NULL,'2026-09-05 11:32:08'),(4,NULL,'بیمارستان مرکزی دامپزشکی شیراز','shiraz-central-hospital','hospital','IR-VET-HOSP-7201',NULL,'دکتر سهراب علوی','07136280000','09173339900','contact@shiraz-vethospital.com','https://shiraz-vethospital.com','shiraz_central_vet','فارس','شیراز','بلوار قصرالدشت، روبروی کوچه ۵۸، جنب مجتمع پزشکی نگین',29.63800000,52.51200000,'شبانه روزی ۲۴/۷ (اورژانس، ترومای جراحی و بستری)',1,0,1,250000,'بانک صادرات ایران','IR890190000000001234567890','بیمارستان مرکزی دامپزشکی شیراز',NULL,'assets/images/organizations/shiraz-hospital-logo.svg','assets/images/cat-hero.jpg','بزرگترین بیمارستان مرجع دامپزشکی جنوب کشور مجهز به بخش جراحی مغز و اعصاب حیوانات، سی‌تی‌اسکن، فیزیوتراپی و استخر آب‌درمانی، با ظرفیت بستری ۵۰ قلاده سگ و گربه در فضایی کاملاً استاندارد و استریل.','اورژانس شبانه‌روزی ۲۴ ساعته, جراحی ستون فقرات و مفاصل, فیزیوتراپی و هیدروتراپی, آندوسکوپی گوارشی, آزمایشگاه پاتولوژی, داروخانه تخصصی',4.9,112,'approved',NULL,'2026-09-05 11:50:26'),(6,NULL,'کلینیک تخصصی دامپزشکی باران اصفهان','baran-vet-clinic','clinic','IR-VET-CLN-5120',NULL,'دکتر مریم صادقی','03136691234','09132228811','info@baran-vet.ir','https://baran-vet.ir','baran_vet_isfahan','اصفهان','اصفهان','خیابان مرداویج، میدان برج، خیابان رسالت، پلاک ۱۴',32.61500000,51.66800000,'شنبه تا پنجشنبه ۸:۳۰ الی ۲۱:۳۰ (جمعه‌ها با هماهنگی قبلی)',0,0,1,250000,'بانک پاسارگاد','IR440570000000009876543210','کلینیک تخصصی دامپزشکی باران اصفهان',NULL,'assets/images/organizations/baran-clinic-logo.svg','assets/images/cat-hero.jpg','کلینیک پیشرو در استان اصفهان در زمینه چکاپ‌های منظم پیشگیرانه، واکسیناسیون استاندارد، دندانپزشکی بدون درد، چشم‌پزشکی و مراقبت‌های گوارشی گربه و سگ با محیطی آرامش‌بخش و بدون استرس (Fear-Free).','کلینیک دوستدار گربه (Cat Friendly), دندانپزشکی تخصصی, آزمایشگاه سریع و تست‌های ویروسی, داروخانه ملزومات, پانسیون روزانه',4.7,64,'approved',NULL,'2026-09-05 11:50:26'),(7,NULL,'کلینیک تخصصی پرندگان زینتی و اگزوتیک کاسپین','caspian-exotic-clinic','clinic','IR-VET-CLN-6390',NULL,'دکتر پوریا رستمی','05138405555','09151234567','info@caspian-birds.ir','https://caspian-birds.ir','caspian_exotic_vet','خراسان رضوی','مشهد','خیابان احمدآباد، نبش ملاصدرا ۲، ساختمان پزشکان سپهر',36.29700000,59.57500000,'شنبه تا چهارشنبه ۱۰:۰۰ الی ۲۰:۰۰',0,0,1,250000,'بانک تجارت','IR330180000000003456789012','کلینیک پرندگان زینتی کاسپین',NULL,'assets/images/organizations/caspian-exotic-logo.svg','assets/images/cat-hero.jpg','تنها مرکز فوق‌تخصصی شمال شرق کشور برای ویزیت، درمان بیماری‌های قارچی و تنفسی، جراحی ارتوپدی استخوان بال، منقار و بیهوشی ایمن طوطی کاسکو، مرغ عشق، خرگوش، همستر و خزندگان.','انکوباتور پرندگان, رادیوگرافی میکرو, آندوسکوپی تنفسی, تست‌های تعیین جنسیت DNA, آزمایشگاه تخصصی پرندگان',4.9,78,'approved',NULL,'2026-09-05 11:50:26'),(8,NULL,'داروخانه تخصصی دامپزشکی رازی','razi-vet-pharmacy','pharmacy','IR-VET-PHAR-3392',NULL,'دکتر بهنام فرهمند','02166442211','09127778899','order@razi-vetpharmacy.com','https://razi-vetpharmacy.com','razi_vet_pharmacy','تهران','تهران','خیابان انقلاب، ابتدای خیابان فلسطین جنوبی، پلاک ۸۲',35.70100000,51.40300000,'شبانه روزی ۲۴/۷ (تامین داروهای نایاب و مکمل‌های درمانی)',1,0,1,250000,'بانک ملی ایران','IR170170000000005678901234','داروخانه تخصصی دامپزشکی رازی',NULL,'assets/images/organizations/razi-pharmacy-logo.svg','assets/images/cat-hero.jpg','جامع‌ترین مرکز پخش و تامین داروهای تخصصی دامپزشکی، آنتی‌بیوتیک‌های کمیاب، داروهای قلبی و کلیوی، رژیم‌های درمانی رویال کنین و هیلز، و زنجیره سرد واکسن با ارسال فوری به سراسر کشور.','زنجیره سرد استاندارد واکسن, تایید آنلاین نسخ دامپزشکی, ارسال با پیک یخچالی, مشاوره داروساز دامی, رژیم‌های درمانی ویژه',4.9,135,'approved',NULL,'2026-09-05 11:50:26'),(9,NULL,'پناهگاه و نقاهتگاه حمایتی حیوانات وفا','vafa-animal-shelter','shelter_charity','IR-NGO-SHELTER-104',NULL,'مهندس آرش شریفی','02644229988','09359998877','help@vafa-shelter.org','https://vafa-shelter.org','vafa_animal_shelter','البرز','کرج','جاده مخصوص کرج، انتهای هشتگرد، دشت بهشت، مجتمع توانبخشی حیوانات',35.95200000,50.68100000,'همه روزه ۸:۰۰ الی ۱۸:۰۰ (پذیرش کیس امدادی ۲۴ ساعته)',1,0,1,250000,'بانک سپه','IR220150000000007890123456','پناهگاه حمایتی حیوانات وفا',NULL,'assets/images/organizations/vafa-shelter-logo.svg','assets/images/dog-avatar.svg','بزرگترین پناهگاه مردم‌نهاد و غیرانتفاعی جهت نجات، درمان، عقیم‌سازی و بازپروری سگ‌ها و گربه‌های آسیب‌دیده با کلینیک صحرایی و همکاری داوطلبانه مجرب‌ترین جراحان کشور.','کلینیک جراحی و عقیم‌سازی امدادی, بخش قرنطینه و واکسیناسیون, حیاط‌های بازی و توانبخشی, سامانه آنلاین سرپرستی رایگان, آمبولانس امداد',5.0,240,'approved',NULL,'2026-09-05 11:50:26');
/*!40000 ALTER TABLE `organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payout_cron_runs`
--

DROP TABLE IF EXISTS `payout_cron_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payout_cron_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cycle_key` varchar(50) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `total_amount` bigint(20) NOT NULL DEFAULT 0,
  `seller_count` int(11) NOT NULL DEFAULT 0,
  `run_type` varchar(20) NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cycle_key` (`cycle_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_cron_runs`
--

LOCK TABLES `payout_cron_runs` WRITE;
/*!40000 ALTER TABLE `payout_cron_runs` DISABLE KEYS */;
INSERT INTO `payout_cron_runs` VALUES (1,'PAYA-CYCLE-2026-37',7,1450000,1,'test','2026-09-11 01:06:44');
/*!40000 ALTER TABLE `payout_cron_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pet_documents`
--

DROP TABLE IF EXISTS `pet_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pet_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pet_documents`
--

LOCK TABLES `pet_documents` WRITE;
/*!40000 ALTER TABLE `pet_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `pet_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pet_health_records`
--

DROP TABLE IF EXISTS `pet_health_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pet_health_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pet_name` varchar(150) NOT NULL,
  `species` enum('dog','cat','bird','horse','cow','rabbit','other') DEFAULT 'dog',
  `breed` varchar(150) DEFAULT NULL,
  `gender` enum('male','female','neutered_male','spayed_female') DEFAULT 'male',
  `birth_date` date DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `microchip_id` varchar(100) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `chronic_conditions` text DEFAULT NULL,
  `rabies_tag_num` varchar(100) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pet_user` (`user_id`),
  CONSTRAINT `fk_pet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pet_health_records`
--

LOCK TABLES `pet_health_records` WRITE;
/*!40000 ALTER TABLE `pet_health_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `pet_health_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pet_vaccinations`
--

DROP TABLE IF EXISTS `pet_vaccinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pet_vaccinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `vaccine_name` varchar(200) NOT NULL,
  `administered_date` date NOT NULL,
  `next_due_date` date DEFAULT NULL,
  `vet_name` varchar(150) DEFAULT NULL,
  `clinic_name` varchar(200) DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reminder_sent_sms` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vac_pet` (`pet_id`),
  CONSTRAINT `fk_vac_pet` FOREIGN KEY (`pet_id`) REFERENCES `pet_health_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pet_vaccinations`
--

LOCK TABLES `pet_vaccinations` WRITE;
/*!40000 ALTER TABLE `pet_vaccinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `pet_vaccinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pharmacy_medicines`
--

DROP TABLE IF EXISTS `pharmacy_medicines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pharmacy_medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `discount_price` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `brand` varchar(100) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 10,
  `target_animal` varchar(50) DEFAULT 'all',
  `pharmacy_tag` varchar(100) DEFAULT NULL,
  `requires_cold_chain` tinyint(1) NOT NULL DEFAULT 0,
  `requires_prescription` tinyint(1) NOT NULL DEFAULT 0,
  `storage_temperature` varchar(50) DEFAULT '15-25°C',
  `dosage_instructions` text DEFAULT NULL,
  `is_autoship` tinyint(1) NOT NULL DEFAULT 0,
  `baseline_rating` decimal(3,1) DEFAULT 4.8,
  `autoship_discount` int(11) DEFAULT 10,
  `rating_cache` decimal(2,1) DEFAULT 4.5,
  `review_count_cache` int(11) DEFAULT 0,
  `moq` int(11) DEFAULT 1,
  `is_b2b_only` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_medicines_category` (`category`),
  KEY `idx_med_animal` (`category`,`target_animal`),
  KEY `idx_med_pharmacy_tag` (`pharmacy_tag`),
  KEY `idx_med_is_autoship` (`is_autoship`),
  KEY `idx_med_cold_chain` (`requires_cold_chain`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pharmacy_medicines`
--

LOCK TABLES `pharmacy_medicines` WRITE;
/*!40000 ALTER TABLE `pharmacy_medicines` DISABLE KEYS */;
INSERT INTO `pharmacy_medicines` VALUES (1,'مکمل ویتامینه و اسید آمینه بایوتین پلاس تقویت سم و موی اسب','Biotin + Zinc + Methionine Equine Supplement','مکمل درمانی اسب',1450000,1290000,'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?w=600&auto=format&fit=crop&q=80','مکمل تخصصی سم اسب حاوی دوز بالای بایوتین، متیونین و روی آلی جهت تسریع در ترمیم دیواره و کف سم و براقیت پوشش مو.','2026-08-27 09:47:32','وتوکینول (Vetoquinol)',15,'horse','hoof_care',0,0,'15-25°C',NULL,1,4.8,10,4.9,32,1,0),(2,'پماد موضعی ضد باکتری و ترمیم‌کننده زخم و ترک گوشت سم اسب','Antibacterial Equine Hoof Ointment','پماد و ضدعفونی',780000,690000,'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80','حاوی روغن درخت چای، اسید سالیسیلیک و اکسید روی با خاصیت ضدقارچ و ضد رطوبت شدید برای درمان گندیدگی شیار سم (Thrush).','2026-08-27 09:47:32','فارنام (Farnam)',20,'horse','hoof_care',0,0,'15-25°C',NULL,1,4.8,15,4.8,24,1,0),(3,'ژل ضد التهاب و مسکن مفاصل و تاندون‌های اسب مسابقه اکوافلکس','Menthol & Arnica Cooling Equine Gel','داروهای موضعی و تسکین‌دهنده',920000,840000,'https://images.unsplash.com/photo-1598974357801-cbca100e6571?w=600&auto=format&fit=crop&q=80','ژل خنک‌کننده گیاهی بر پایه منتول و آرنیکا برای کاهش ورم تاندون، رفع اسپاسم عضلانی و کوفتگی پس از تمرینات سنگین.','2026-08-27 09:47:32','اکواین آمریکا',12,'horse','pain_management',0,0,'15-25°C',NULL,1,4.8,10,5.0,41,1,0),(4,'خمیر خوراکی ضد انگل آیورمکتین اسب (اکوئالان دوتایی)','Ivermectin 1.87% Equine Oral Paste','ضد انگل و کرم‌کش',540000,480000,'https://images.unsplash.com/photo-1568640347023-a616a30bc3bd?w=600&auto=format&fit=crop&q=80','سرنگ مدرج دوز دقیق برای از بین بردن انواع انگل‌های دستگاه گوارش، لارو ربات و انگل‌های ریوی در اسب و کره اسب.','2026-08-27 09:47:32','بوریینگر اینگلهایم',30,'horse','dewormer',0,1,'15-25°C',NULL,1,4.8,12,4.9,56,1,0),(5,'سوسپانسیون پستانی آنتی‌بیوتیک دوره خشکی گاو شیری (مستیکس)','Cloxacillin + Ampicillin Dry Cow Intramammary Infusion','آنتی بیوتیک پستانی',320000,280000,'https://images.unsplash.com/photo-1546445317-29f4545e9d53?w=600&auto=format&fit=crop&q=80','سرنگ پستانی با اثر درازمدت جهت پیشگیری و درمان ورم پستان زیربالینی در دوره خشکی گله‌های صنعتی.','2026-08-27 09:47:32','زوئتیس (Zoetis)',50,'cow','antibiotics',0,1,'15-25°C',NULL,1,4.8,10,4.9,64,1,0),(6,'بولوس کلسیم دیرحل تقویتی پس از زایمان گاو شیری (کالسی‌بل)','Calcium Bolus for Dairy Cows','مکمل الکترولیت و کلسیم',890000,790000,'https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=600&auto=format&fit=crop&q=80','حاوی کلرید و سولفات کلسیم با جذب سریع و ماندگار جهت جلوگیری از فلج زایمان (تب شیر) و افت کلسیم خون.','2026-08-27 09:47:32','وت‌فارما',25,'cow','vitamins',0,0,'15-25°C',NULL,1,4.8,15,4.7,19,1,0),(7,'محلول غلیظ اسپری و غوطه‌وری ضدعفونی سم گاو (دیپ سم سولفات مس و روی)','Copper & Zinc Chelate Hoof Bath Solution','مراقبت سم و بهداشت دامپزشکی',1250000,1100000,'https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?w=600&auto=format&fit=crop&q=80','فرمولاسیون پایدار کلات روی و مس جهت درمان و کنترل درماتیت انگشتی، گندیدگی سم و لنگش گله.','2026-08-27 09:47:32','دلاوال (DeLaval)',18,'cow','hoof_care',0,0,'15-25°C',NULL,1,4.8,10,4.8,27,1,0),(8,'محلول تزریقی اکسی‌تتراسایکلین طولانی‌اثر ۲۰٪ (ال‌ای)','Oxytetracycline 20% LA Injectable','آنتی بیوتیک سیستمیک',430000,380000,'https://images.unsplash.com/photo-1500595046743-cd271d694d30?w=600&auto=format&fit=crop&q=80','آنتی‌بیوتیک وسیع‌الطیف تزریقی با اثر ۴۸ ساعته جهت عفونت‌های ریوی، پنومونی، آناپلاسموز و عفونت‌های رحمی دام.','2026-08-27 09:47:32','نصر داروی دامی',40,'cow','antibiotics',0,1,'15-25°C',NULL,0,4.8,5,4.9,48,1,0),(9,'محلول خوراکی مولتی ویتامین + الکترولیت + اسید آمینه طیور (ویتالیت)','Multi-Vitamin + Amino Acids + Electrolytes Solution','ویتامین و الکترولیت طیور',380000,330000,'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=600&auto=format&fit=crop&q=80','کاهش سریع استرس گرمایی، استرس واکسیناسیون، تقویت سیستم ایمنی و افزایش راندمان رشد جوجه گوشتی و تخم‌گذار.','2026-08-27 09:47:32','کیمیافام',60,'chick','vitamins',0,0,'15-25°C',NULL,1,4.8,12,4.8,52,1,0),(10,'پودر محلول در آب ضد کوکسیدیوز و اسهال خونی طیور (آمپرولیوم ۲۰٪)','Amprolium 20% Soluble Powder','ضد انگل گوارشی و کوکسیدیوز',290000,250000,'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=600&auto=format&fit=crop&q=80','داروی انتخابی در کنترل و ریشه‌کنی انواع گونه‌های ایمریا (کوکسیدیوز روده‌ای و سکومی) در گله‌های جوجه و بوقلمون.','2026-08-27 09:47:32','داروسازی دامپزشکی ایران',45,'chick','dewormer',0,1,'15-25°C',NULL,1,4.8,10,4.9,38,1,0),(11,'محلول برونکودیلاتور گیاهی تنفسی و ضد سرفه جوجه و پرندگان (منتوفین)','Menthol + Eucalyptus Respiratory Solution','تقویت سیستم تنفسی',510000,450000,'https://images.unsplash.com/photo-1596797882870-8c33deeac224?w=600&auto=format&fit=crop&q=80','عصاره خالص اکالیپتوس و نعناع فلفلی جهت اسپری محیطی یا آبخوری برای تسکین خس‌خس سینه و بازکردن مجاری هوایی.','2026-08-27 09:47:32','یوولس (Ewabo)',35,'chick','inflammation',0,0,'15-25°C',NULL,1,4.8,10,4.7,29,1,0),(12,'واکسن زنجیره سرد برونشیت عفونی و نیوکاسل جوجه (H120 + لاسونا)','Newcastle + Infectious Bronchitis Live Vaccine (Cold Chain 2-8°C)','واکسن و بیولوژیک (زنجیره سرد)',620000,550000,'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=600&auto=format&fit=crop&q=80','واکسن زنده لیوفیلیزه با ارسال فوق سریع با یخدان آیس‌پک استاندارد برای مصونیت‌بخشی قطره چشمی یا اسپری.','2026-08-27 09:47:32','مریال / بوهرینگر',20,'chick','cold_chain',1,1,'2-8°C (یخدان زنجیره سرد)',NULL,0,4.8,5,5.0,74,1,0),(13,'قرص تخصصی غضروف‌ساز و ضد درد مفاصل سگ آرتروفلکس پلاس','Glucosamine + Chondroitin + MSM Joint Care','غضروف ساز و استخوان',780000,690000,'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=600&auto=format&fit=crop&q=80','فرمول حاوی گلوکوزامین، کندرویتین و MSM جهت کاهش خشکی مفاصل، بهبود دیسپلازی مفصل ران و روان‌سازی حرکت سگ‌های سالخورده.','2026-08-27 09:47:32','بفار (Beaphar)',40,'dog','pain_management',0,0,'15-25°C',NULL,1,4.8,15,5.0,49,1,0),(14,'قرص جویدنی ضد انگل ۴ گانه سگ درونتال پلاس','Praziquantel + Pyrantel + Febantel (Drontal Plus)','ضد انگل گوارشی',380000,340000,'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80','طعم‌دار گوشتی با پذیرش بالا، ریشه‌کن‌کننده انواع کرم‌های نواری، قلابدار، گرد و ژیاردیا در سگ.','2026-08-27 09:47:32','بایر (Bayer)',55,'dog','dewormer',0,1,'15-25°C',NULL,1,4.8,10,4.9,61,1,0),(15,'محلول گوش‌پاک‌کن ضد باکتری و ضد قارچ سگ و گربه اتوفلوکس','Chlorhexidine + Tris-EDTA Otic Cleanser','قطره و مراقبت گوش',310000,260000,'https://images.unsplash.com/photo-1537151608828-ea2b11777ee8?w=600&auto=format&fit=crop&q=80','شستشوی عمقی کانال گوش، رفع بوی بد، تجزیه جرم‌های چرب و تسکین خارش ناشی از عفونت‌های اوتیت میانی.','2026-08-27 09:47:32','وتوکینول (Vetoquinol)',30,'dog','first_aid',0,0,'15-25°C',NULL,1,4.8,10,4.7,15,1,0),(16,'کپسول دارویی ضد درد و ضد التهاب غیر استروئیدی سگ کارپروفن ۵۰','Carprofen 50mg NSAID Analgesic','مسکن و ضد درد',590000,520000,'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600&auto=format&fit=crop&q=80','تسکین فوق‌العاده درد و التهاب پس از اعمال جراحی ارتوپدی و کنترل دردهای مزمن استئوآرتریت سگ.','2026-08-27 09:47:32','وت‌فارما',25,'dog','pain_management',0,1,'15-25°C',NULL,1,4.8,10,4.8,29,1,0),(17,'اسپری استنشاقی و ضد اسپاسم تنفسی سگ‌های نژاد پوزه‌کوتاه','Salbutamol + Beclomethasone Vet Inhaler','اسپری تنفسی و ضد التهاب',640000,560000,'https://images.unsplash.com/photo-1517849845537-4d257902454a?w=600&auto=format&fit=crop&q=80','اسپری تخصصی جهت بهبود تنفس، کاهش التهاب مجاری تنفسی و آسم در سگ‌های بولداگ، پاگ و شیتزو.','2026-08-27 09:47:32','پت‌مدیکال',18,'dog','inflammation',0,1,'15-25°C',NULL,1,4.8,10,4.9,21,1,0),(18,'کیت جامع کمک‌های اولیه اورژانسی سگ و حیوانات خانگی','Veterinary Emergency First Aid Kit','کمک‌های اولیه و پانسمان',890000,780000,'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600&auto=format&fit=crop&q=80','شامل بتادین حیوانی، بانداژ خودچسب، پنس کنه کش، دماسنج دیجیتال، پد گاز استریل و اسپری التیام زخم.','2026-08-27 09:47:32','تریکسی',30,'dog','first_aid',0,0,'15-25°C',NULL,0,4.8,5,4.9,35,1,0),(19,'بالم ارگانیک نرم‌کننده و محافظ پد پنجه سگ و گربه','Organic Paw Protection & Repair Balm','مراقبت پوست و پنجه',280000,230000,'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600&auto=format&fit=crop&q=80','بالم کاملاً طبیعی حاوی شی باتر و موم عسل برای بازسازی ترک خوردگی و خشکی پنجه ناشی از پیاده‌روی روی آسفالت گرم یا سرد.','2026-08-27 09:47:32','پت‌کر',35,'dog','hoof_care',0,0,'15-25°C',NULL,1,4.8,10,4.7,18,1,0),(20,'خمیر مالت و مکمل ویتامینه تقویت ایمنی گربه جیم کت','GimCat Multi-Vitamin & Hairball Paste','مکمل مالت و ویتامینه گربه',460000,390000,'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80','دفع آسان گلوله‌های مویی (Hairball) و تقویت پوشش مو و ناخن گربه با ویتامین‌های گروه B و زینک.','2026-08-27 09:47:32','جیم کت (GimCat)',45,'cat','vitamins',0,0,'15-25°C',NULL,1,4.8,15,5.0,78,1,0),(21,'قطره ضد استرس و فرومون آرامبخش درمانی گربه فلی‌وی','Feliway Feline Facial Pheromone Calming Spray','فرومون و آرامبخش درمانی',720000,640000,'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600&auto=format&fit=crop&q=80','کاهش اضطراب محیطی، ترس از سفر، پرخاشگری و رفتارهای نشانه‌گذاری با تقلید فرومون چهره‌ای مادر.','2026-08-27 09:47:32','فلی‌وی (Feliway)',20,'cat','therapy',0,0,'15-25°C',NULL,1,4.8,10,4.9,43,1,0),(22,'قطره موضعی ضد کک، کنه و انگل‌های پوستی گربه ادوکیت','Advocate Imidacloprid + Moxidectin Spot-On','ضد کک، کنه و انگل موضعی',520000,450000,'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=600&auto=format&fit=crop&q=80','محافظت ماهیانه پشت گردنی علیه طیف گسترده‌ای از انگل‌های خارجی و جرب گوش در گربه‌ها.','2026-08-27 09:47:32','بایر (Bayer)',35,'cat','dewormer',0,1,'15-25°C',NULL,1,4.8,12,4.8,37,1,0),(23,'قطره اشک شستشو و رفع عفونت و التهاب چشم گربه','Sterile Ophthalmic Eye Drops for Cats','قطره و شستشوی چشم',290000,240000,'https://images.unsplash.com/photo-1495360010541-f48722b34f7d?w=600&auto=format&fit=crop&q=80','محلول استریل پاک‌کننده لکه‌های اشک زیر چشم و تسکین سوزش و التهابات ملتحمه در گربه‌های پرشین و DSH.','2026-08-27 09:47:32','پت‌مدیکال',40,'cat','inflammation',0,0,'15-25°C',NULL,1,4.8,10,4.6,19,1,0);
/*!40000 ALTER TABLE `pharmacy_medicines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prescriptions`
--

DROP TABLE IF EXISTS `prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rx_file_url` varchar(500) NOT NULL,
  `clinic_name` varchar(255) DEFAULT NULL,
  `vet_name` varchar(150) DEFAULT NULL,
  `vet_phone` varchar(50) DEFAULT NULL,
  `vet_license_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected','expired') DEFAULT 'pending',
  `pharmacist_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rx_user` (`user_id`),
  KEY `idx_rx_status` (`status`),
  KEY `idx_prescriptions_doctor` (`doctor_id`),
  CONSTRAINT `fk_rx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prescriptions`
--

LOCK TABLES `prescriptions` WRITE;
/*!40000 ALTER TABLE `prescriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `prescriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_price_tiers`
--

DROP TABLE IF EXISTS `product_price_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_price_tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `is_pharmacy` tinyint(1) DEFAULT 0,
  `min_qty` int(11) NOT NULL DEFAULT 1,
  `max_qty` int(11) DEFAULT NULL,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `unit_price` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tier_product` (`product_id`,`is_pharmacy`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_price_tiers`
--

LOCK TABLES `product_price_tiers` WRITE;
/*!40000 ALTER TABLE `product_price_tiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_price_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) DEFAULT NULL,
  `seller_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `price` int(11) NOT NULL,
  `discount_price` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `brand` varchar(100) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 10,
  `low_stock_threshold` int(11) DEFAULT 5,
  `target_animal` varchar(50) DEFAULT 'all',
  `pharmacy_tag` varchar(100) DEFAULT NULL,
  `is_autoship` tinyint(1) NOT NULL DEFAULT 0,
  `baseline_rating` decimal(3,1) DEFAULT 4.8,
  `autoship_discount` int(11) DEFAULT 10,
  `rating_cache` decimal(2,1) DEFAULT 4.5,
  `review_count_cache` int(11) DEFAULT 0,
  `moq` int(11) DEFAULT 1,
  `is_b2b_only` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category`),
  KEY `idx_cat_animal` (`category`,`target_animal`),
  KEY `idx_pharmacy_tag` (`pharmacy_tag`),
  KEY `idx_is_autoship` (`is_autoship`),
  KEY `idx_seller` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,NULL,NULL,'غذای خشک سگ بالغ نژاد کوچک رویال کنین مدل Mini Adult','غذای سگ',2450000,1980000,'assets/images/products/royal-canin-mini-adult-dog.jpg',NULL,'2026-07-21 13:23:55','رویال کنین',7,5,'dog',NULL,1,4.8,15,4.8,0,1,0),(2,NULL,NULL,'کنسرو لذیذ سالمون و مرغ فیست اند فلیور مخصوص گربه','غذای گربه',420000,245000,'assets/images/products/gourmet-salmon-canned-cat.jpg',NULL,'2026-07-21 13:23:55','فیست اند فلیور',10,5,'cat',NULL,1,4.8,15,4.8,0,1,0),(3,NULL,NULL,'قلاده چرمی سگ زولاکس سایز لارج','لوازم بهداشتی',850000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','جوسرا',10,5,'all',NULL,0,4.8,10,4.8,0,1,0),(4,NULL,NULL,'توپ دندانی طناب‌دار','اسباب‌بازی',220000,180000,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','جوسرا',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(5,NULL,NULL,'خاک گربه پتوپیا ۱۰ کیلویی','لوازم بهداشتی',350000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رفلکس',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(6,NULL,NULL,'قطره مولتی‌ویتامین و تقویت ایمنی و مفاصل سگ و گربه وتری ویتالیتی','مکمل دارویی',650000,490000,'assets/images/products/vetri-vitality-pet-drops.jpg',NULL,'2026-07-21 13:23:55','وتری ویتالیتی',10,5,'all','therapy',1,4.8,15,4.8,0,1,0),(7,NULL,NULL,'درخت گربه ۳ طبقه کدیپک','اسباب‌بازی',4200000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','نوتری پت',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(8,NULL,NULL,'شامپو ضد ریزش موی سگ تریکسی','لوازم بهداشتی',280000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رفلکس',9,5,'all',NULL,0,4.8,10,4.5,0,1,0),(9,NULL,NULL,'غذای خشک گربه عقیم‌شده فلاین مدل Sterilised Adult','غذای گربه',2850000,2340000,'assets/images/products/feline-sterilised-cat-food.jpg',NULL,'2026-07-21 13:23:55','رویال کنین / ناریش',10,5,'cat',NULL,1,4.8,15,4.8,0,1,0),(10,NULL,NULL,'تشک خواب سگ سایز متوسط','لوازم بهداشتی',950000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رفلکس',10,5,'all',NULL,0,4.8,10,4.8,0,1,0),(11,NULL,NULL,'غذای خشک سگ بالغ مینی ادولت جوسرا مدل Miniwell','غذای سگ',2450000,1980000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','جوسرا',10,5,'dog',NULL,0,4.8,10,4.8,14,1,0),(12,NULL,NULL,'کنسرو گربه گورمت گلد با طعم مرغ','غذای گربه',150000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','رفلکس',10,5,'cat',NULL,1,4.8,10,4.9,22,1,0),(13,NULL,NULL,'قلاده چرمی سگ زولاکس سایز لارج','لوازم بهداشتی',850000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','نوتری پت',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(14,NULL,NULL,'توپ دندانی طناب‌دار','اسباب‌بازی',220000,180000,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(15,NULL,NULL,'خاک گربه پتوپیا ۱۰ کیلویی','لوازم بهداشتی',350000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','شایر',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(16,NULL,NULL,'قطره مولتی ویتامین سگ و گربه','مکمل دارویی',450000,390000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all','therapy',1,4.8,15,4.5,0,1,0),(17,NULL,NULL,'درخت گربه ۳ طبقه کدیپک','اسباب‌بازی',4200000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(18,NULL,NULL,'شامپو ضد ریزش موی سگ تریکسی','لوازم بهداشتی',280000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','پت‌کر',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(19,NULL,NULL,'غذای خشک گربه بالغ عقیم شده رویال کنین','غذای گربه',2850000,2600000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','شایر',10,5,'cat',NULL,0,4.8,10,4.9,22,1,0),(20,NULL,NULL,'تشک خواب سگ سایز متوسط','لوازم بهداشتی',950000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(21,NULL,NULL,'غذای خشک سگ بالغ نژاد کوچک نوتری پت مدل Nutri Dog','غذای سگ',2450000,1980000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','نوتری پت',10,5,'dog',NULL,0,4.8,10,4.8,14,1,0),(22,NULL,NULL,'کنسرو گربه گورمت گلد با طعم مرغ','غذای گربه',150000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'cat',NULL,1,4.8,10,4.9,22,1,0),(23,NULL,NULL,'قلاده چرمی سگ زولاکس سایز لارج','لوازم بهداشتی',850000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(24,NULL,NULL,'توپ دندانی طناب‌دار','اسباب‌بازی',220000,180000,'https://lh3.googleusercontent.com/aida-public/AB6AXuCsm_sg4VlKSHpcOfcLE3gOwdTo1GG1jQRBPPFrcDxaZ_Ns_46U7AAxqMN5-gRD8xhBefgAjzfkwncuFZOp2K-JXSR50lxWxmSDuf8Ed74RsAW1fVV2QL1qn4LJczSR4I0jzHfOPB_a8fqBit2odwrEv6KCyt32eWKnqVyyCmhUBtC1IcA-2hK-l61vUN3TSwd1YQ0FxeZ9WCvajbHU1fGvEeVU6Ym1pTNYos5Kn-gQ8J9PMfE8QyaR',NULL,'2026-07-21 13:23:55','رفلکس',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(25,NULL,NULL,'خاک گربه پتوپیا ۱۰ کیلویی','لوازم بهداشتی',350000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(26,NULL,NULL,'قطره مولتی ویتامین سگ و گربه','مکمل دارویی',450000,390000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all','therapy',1,4.8,15,4.5,0,1,0),(27,NULL,NULL,'درخت گربه ۳ طبقه کدیپک','اسباب‌بازی',4200000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رفلکس',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(28,NULL,NULL,'شامپو ضد ریزش موی سگ تریکسی','لوازم بهداشتی',280000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuAUJoM_Cb8R1bvWWSCz2yxdiA9nIKNsl1SEJ9R40MzslKtcd2CpKfpmIAZcO67KF-CzRp08fneiShCpaSD-lFf5yI0cBJgW8EBMMLu9Bb_kdV_XyZcTTLQ7Ll-bxo2aej9wGv4I-Bp2N1YVe7srdsKFyH9MWnVbZwG3Gk6iRCa-pbdIX3EujAQPnUqaL5L1ZxQC-kqtxZSWDfnZYGQhSc9YdnlaczRv_iTpZWScGJ9SopSETZfLui4o',NULL,'2026-07-21 13:23:55','رفلکس',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(29,NULL,NULL,'غذای خشک گربه بالغ عقیم شده رویال کنین','غذای گربه',2850000,2600000,'https://lh3.googleusercontent.com/aida-public/AB6AXuAQsPH2nC6QsVlQckvVzjRg1NfOR9A33iDk5dGkHxYPCwfOzHQVvfOUri1XiL-PiTdC_LcmvtQDPQZ_ZCRc9jFp7tIKRUjrKuBvyFNB5GPsRtmrt2P_LXzvwDO-Jx1iYXPnsw7GD-19aI9c5mIKUs8X2fWw5y41cnQAaJbQJZOQvw2wTIIreB1s0kuu6Wz9IjNLk1RjnmqDnEedi-C-7DV1UaxFXCFJG8VK6VOBmFqsr5B_MpUuQw-0',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'cat',NULL,0,4.8,10,4.9,22,1,0),(30,NULL,NULL,'تشک خواب سگ سایز متوسط','لوازم بهداشتی',950000,NULL,'https://lh3.googleusercontent.com/aida-public/AB6AXuDKtf4eimUY5E8Ts0ArAFpKGX0kSgRYgemNuYLV-0kjBKpzfhS6-kKgvCuKiQnancWtDDWmCEN5RC3MIJfRgG95ZbKtfr2KWlINH4pjcCZYLsis-Fh3vU1f3ZbjJuFIPd-HNjdvWp5TSVrA7qjOtxYWAcPCdDkxZZmjPq0-LdT8TFPis7trtHp1_QTeIXl3o5aGIXE0qRa473pZ6SnNwHpz80pdcljlNsNP8n13ppl_7ZZW4Z46c1bj',NULL,'2026-07-21 13:23:55','رویال کنین',10,5,'all',NULL,0,4.8,10,4.5,0,1,0),(31,NULL,NULL,'Cat Toy Mouse Updated','Toys',120000,100000,'assets/images/toy-mouse.jpg','Great toy for cats','2026-07-25 01:20:40','Test Brand',12,5,'all',NULL,0,4.8,10,4.5,0,1,0),(53,NULL,NULL,'خمیر ضد انگل آیورمکتین مخصوص اسب اکولان','داروخانه تخصصی',850000,740000,'https://images.unsplash.com/photo-1553284965-83fd3e82fa5a?w=600&auto=format&fit=crop&q=80','ژل خوراکی ضد انگل و کرم‌کش قوی برای کنترل انواع انگل‌های داخلی و روده‌ای اسب‌ها با اثرگذاری طولانی‌مدت.','2026-09-04 21:33:27','اکولان',15,5,'horse','dewormer',1,4.8,12,4.9,18,1,0),(54,NULL,NULL,'روغن و مرهم تقویتی سم اسب مدل Hoof Care Pro','داروخانه تخصصی',620000,540000,'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80','فرمولاسیون ویژه حاوی تار طبیعی و بیوتین جهت تقویت بافت شاخی سم اسب و جلوگیری از ترک خوردگی و خشکی.','2026-09-04 21:33:27','کاوامیرا',20,5,'horse','hoof_care',1,4.8,10,4.7,9,1,0),(55,NULL,NULL,'محلول مسکن و ضدالتهاب اسب فینیل بوتازون خوراکی','داروخانه تخصصی',980000,890000,'https://images.unsplash.com/photo-1598974357801-cbca100e6571?w=600&auto=format&fit=crop&q=80','داروی ضد درد و تسکین التهابات تاندونی و مفاصل اسب‌های کورس و پرش، موثر در بهبودی سریع صدمات عضلانی.','2026-09-04 21:33:28','وت‌فارما',12,5,'horse','pain_management',0,4.8,5,5.0,24,1,0),(56,NULL,NULL,'پودر مکمل الکترولیت و ویتامین E اسب اکواین','داروخانه تخصصی',1250000,1100000,'https://images.unsplash.com/photo-1566251037378-5e04e3bec343?w=600&auto=format&fit=crop&q=80','مکمل تامین املاح ضروری و ویتامین‌های آنتی‌اکسیدان پس از تمرینات سنگین، جلوگیری از دهیدراتاسیون و گرفتگی عضلات.','2026-09-04 21:33:28','نوترینت پرو',25,5,'horse','vitamins',1,4.8,15,4.8,16,1,0),(57,NULL,NULL,'پماد پستانی ضد ورم پستان حاد و تحت حاد گاو شیری','داروخانه تخصصی',450000,390000,'https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=600&auto=format&fit=crop&q=80','سوسپانسیون آنتی بیوتیکی فوق العاده قوی جهت درمان و کنترل ورم پستان با دوره پرهیز کوتاه مدت.','2026-09-04 21:33:28','وت‌مکس',30,5,'cow','inflammation',1,4.8,10,4.9,31,1,0),(58,NULL,NULL,'اسپری اکسید روی و تار ضد گندیدگی سم دام (Foot Rot)','داروخانه تخصصی',320000,280000,'https://images.unsplash.com/photo-1546445317-29f4545e9d53?w=600&auto=format&fit=crop&q=80','اسپری درمانی و ضدعفونی کننده لایه‌های شاخی سم گاو و گوسفند جهت پیشگیری از لنگش و عفونت سم.','2026-09-04 21:33:28','کاوامیرا',40,5,'cow','hoof_care',1,4.8,10,4.6,14,1,0),(59,NULL,NULL,'بلوس آهسته‌رهش کلسیم و ویتامین D3 گاو تازه زا','داروخانه تخصصی',780000,690000,'https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?w=600&auto=format&fit=crop&q=80','پیشگیری قطعی از تب شیر (Hypocalcemia) و فلجی زایمان با فراهمی زیستی بالا در شکمبه دام سنگین.','2026-09-04 21:33:28','فارماپرو',20,5,'cow','vitamins',1,4.8,15,5.0,27,1,0),(60,NULL,NULL,'واکسن کشته دامی آنتروتوکسمی و شاربن علامتی','داروخانه تخصصی',550000,NULL,'https://images.unsplash.com/photo-1588693951525-6b7a5ee2e3d3?w=600&auto=format&fit=crop&q=80','ایمن‌سازی فعال گله در برابر پرخوری و کلستریدیوزهای شایع با بالاترین تیتر آنتی‌بادی ایمنی‌بخش.','2026-09-04 21:33:28','رازی وت',50,5,'cow','vaccines',0,4.8,0,4.8,19,1,0),(61,NULL,NULL,'محلول خوراکی مولتی ویتامین + اسیدهای آمینه پرورشی طیور','داروخانه تخصصی',290000,245000,'https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=600&auto=format&fit=crop&q=80','تقویت ضریب تبدیل غذایی، بهبود رشد جوجه یک‌روزه و ارتقای مقاومت سیستم ایمنی در شرایط استرس گرمایی.','2026-09-04 21:33:28','اویسان',60,5,'chick','vitamins',1,4.8,10,4.9,42,1,0),(62,NULL,NULL,'پودر محلول در آب ضد کوکسیدیوز و عفونت‌های گوارشی جوجه','داروخانه تخصصی',380000,330000,'https://images.unsplash.com/photo-1563281577-a7be47e20db9?w=600&auto=format&fit=crop&q=80','داروی درمانی و کنترل‌کننده کوکسیدیوز روده‌ای و اسهال‌های خونی در مزارع پرورش جوجه و نیمچه گوشتی.','2026-09-04 21:33:28','کمی فارما',35,5,'chick','drugs',1,4.8,12,4.7,15,1,0),(63,NULL,NULL,'واکسن قطره چشمی نیوکاسل سویه لاسوتا + برونشیت طیور','داروخانه تخصصی',420000,NULL,'https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=600&auto=format&fit=crop&q=80','واکسیناسیون زنده جهت ایجاد ایمنی مخاطی و همورال فوق‌العاده قوی در سیستم تنفسی جوجه و طیور تخمگذار.','2026-09-04 21:33:28','رازی وت',80,5,'chick','vaccines',0,4.8,0,5.0,38,1,0),(64,NULL,NULL,'محلول ضدعفونی کننده و کمک‌های اولیه هوای سالن و آب طیور','داروخانه تخصصی',310000,260000,'https://images.unsplash.com/photo-1596704017254-9b121068fb31?w=600&auto=format&fit=crop&q=80','ضدعفونی کننده غیرسمی با پایه نانو نقره برای التیام زخم‌ها، استریل کردن خطوط آبرسانی و هوای سالن.','2026-09-04 21:33:28','نانووت',45,5,'chick','first_aid',1,4.8,10,4.6,11,1,0),(65,NULL,NULL,'قرص ضد انگل و کرم‌کش سگ درنتال پلاس بایر آلمان','داروخانه تخصصی',490000,420000,'https://images.unsplash.com/photo-1587300003388-59208cc962cb?w=600&auto=format&fit=crop&q=80','معتبرترین قرص ضدانگل طیف وسیع برای سگ‌ها جهت نابودی تضمینی کرم‌های نواری، گرد و ژیاردیا.','2026-09-04 21:33:28','بایر (Bayer)',50,5,'dog','dewormer',1,4.8,15,5.0,64,1,0),(66,NULL,NULL,'شربت ضد التهاب و مسکن ملئوکسیکام خوراکی سگ','داروخانه تخصصی',580000,495000,'https://images.unsplash.com/photo-1543466835-00a7907e9de1?w=600&auto=format&fit=crop&q=80','داروی ضد التهاب غیر استروئیدی (NSAID) برای کاهش سریع دردهای ناشی از استئوآرتریت و جراحی‌های ارتوپدی.','2026-09-04 21:33:28','وت‌فارما',25,5,'dog','pain_management',1,4.8,10,4.8,29,1,0),(67,NULL,NULL,'اسپری استنشاقی و ضد اسپاسم تنفسی سگ‌های نژاد پوزه‌کوتاه','داروخانه تخصصی',640000,560000,'https://images.unsplash.com/photo-1517849845537-4d257902454a?w=600&auto=format&fit=crop&q=80','اسپری تخصصی جهت بهبود تنفس، کاهش التهاب مجاری تنفسی و آسم در سگ‌های بولداگ، پاگ و شیتزو.','2026-09-04 21:33:28','پت‌مدیکال',18,5,'dog','inflammation',1,4.8,10,4.9,21,1,0),(68,NULL,NULL,'کیت جامع کمک‌های اولیه اورژانسی سگ و حیوانات خانگی','داروخانه تخصصی',890000,780000,'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600&auto=format&fit=crop&q=80','شامل بتادین حیوانی، بانداژ خودچسب، پنس کنه کش، دماسنج دیجیتال، پد گاز استریل و اسپری التیام زخم.','2026-09-04 21:33:28','تریکسی',30,5,'dog','first_aid',0,4.8,5,4.9,35,1,0),(69,NULL,NULL,'بالم ارگانیک نرم‌کننده و محافظ پد پنجه سگ و گربه','داروخانه تخصصی',280000,230000,'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600&auto=format&fit=crop&q=80','بالم کاملاً طبیعی حاوی شی باتر و موم عسل برای بازسازی ترک خوردگی و خشکی پنجه ناشی از پیاده‌روی روی آسفالت گرم یا سرد.','2026-09-04 21:33:28','پت‌کر',35,5,'dog','hoof_care',1,4.8,10,4.7,18,1,0),(70,NULL,NULL,'خمیر مالت و مکمل ویتامینه تقویت ایمنی گربه جیم کت','داروخانه تخصصی',460000,390000,'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600&auto=format&fit=crop&q=80','دفع آسان گلوله‌های مویی (Hairball) و تقویت پوشش مو و ناخن گربه با ویتامین‌های گروه B و زینک.','2026-09-04 21:33:28','جیم کت (GimCat)',45,5,'cat','vitamins',1,4.8,15,5.0,78,1,0),(71,NULL,NULL,'قطره ضد استرس و فرومون آرامبخش درمانی گربه فلی‌وی','داروخانه تخصصی',720000,640000,'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600&auto=format&fit=crop&q=80','کاهش اضطراب محیطی، ترس از سفر، پرخاشگری و رفتارهای نشانه‌گذاری با تقلید فرومون چهره‌ای مادر.','2026-09-04 21:33:28','فلی‌وی (Feliway)',20,5,'cat','therapy',1,4.8,10,4.9,43,1,0),(72,NULL,NULL,'قطره موضعی ضد کک، کنه و انگل‌های پوستی گربه ادوکیت','داروخانه تخصصی',520000,450000,'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?w=600&auto=format&fit=crop&q=80','محافظت ماهیانه پشت گردنی علیه طیف گسترده‌ای از انگل‌های خارجی و جرب گوش در گربه‌ها.','2026-09-04 21:33:28','بایر (Bayer)',35,5,'cat','dewormer',1,4.8,12,4.8,37,1,0),(73,NULL,NULL,'قطره اشک شستشو و رفع عفونت و التهاب چشم گربه','داروخانه تخصصی',290000,240000,'https://images.unsplash.com/photo-1495360010541-f48722b34f7d?w=600&auto=format&fit=crop&q=80','محلول استریل پاک‌کننده لکه‌های اشک زیر چشم و تسکین سوزش و التهابات ملتحمه در گربه‌های پرشین و DSH.','2026-09-04 21:33:28','پت‌مدیکال',40,5,'cat','inflammation',1,4.8,10,4.6,19,1,0);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_codes`
--

DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_percentage` int(11) NOT NULL,
  `points_cost` int(11) NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `promo_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_codes`
--

LOCK TABLES `promo_codes` WRITE;
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `target_type` enum('product','doctor','organization') NOT NULL,
  `target_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `is_verified_buyer` tinyint(1) DEFAULT 1,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_target_rating` (`target_type`,`target_id`,`rating`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,6,'product',31,5,'great product!',1,'approved','2026-07-25 01:59:27'),(3,5,'organization',1,5,'سگ من تصادف کرده بود و ساعت ۳ بامداد رسوندیمش بیمارستان پایتخت. بخش اورژانس و دکتر شایان فوق‌العاده سریع عمل جراحی لگن رو انجام دادن و الان کاملاً سلامته. دست مریزاد به کادر دلسوزتون.',1,'approved','2026-09-05 11:50:26'),(4,5,'organization',1,5,'نظافت و استریل بودن بخش بستری گربه‌ها واقعاً در حد بیمارستان‌های انسانی بود. گزارش‌های دوره‌ای با ویدیو برام ارسال می‌شد که خیلی خیالم رو راحت کرد.',1,'approved','2026-08-24 11:50:26'),(5,5,'organization',2,5,'برای عقیم‌سازی گربه‌ام به کلینیک پرشین مراجعه کردم. خانم دکتر مهرزاد با بیهوشی استنشاقی جراحی رو انجام دادن و بعد از ۳ ساعت کاملاً سرحال و بدون درد راه می‌رفت.',1,'approved','2026-08-18 11:50:26'),(6,5,'organization',2,4,'کادر پذیرش بسیار خوش‌برخورد، سیستم نوبت‌دهی آنلاین بدون هیچ معطلی اجرا شد. پت‌شاپ دارویی هم هر چی نیاز داشتیم داشت.',1,'approved','2026-08-19 11:50:26'),(7,5,'organization',4,5,'بهترین و مجهزترین مرکز درمانی در کل استان فارس. سونوگرافی داپلر با دقت عالی انجام شد و داروها رو بلافاصله از داروخانه داخلی تحویل گرفتیم.',1,'approved','2026-08-27 11:50:26'),(8,5,'organization',7,5,'کاسکوی من مشکل شدید تنفسی داشت و هیچ کلینیکی قبولش نمی‌کرد. آقای دکتر رستمی با مهارت عالی اکسیژن‌تراپی و نبولایزر انجام دادن و نجاتش دادن.',1,'approved','2026-08-20 11:50:26'),(9,5,'organization',8,5,'داروی کاردیولوژی برای سگم پیدا نمی‌شد، داروخانه رازی بلافاصله برام ارسال کرد با پک یخ و زنجیره سرد کامل. قیمت‌ها هم کاملاً منصفانه و شرکتی بود.',1,'approved','2026-08-25 11:50:26'),(10,14,'organization',1,5,'تست سیستم ثبت نظر مراکز درمانی',1,'approved','2026-09-05 11:58:06'),(11,16,'organization',1,5,'خدمات بسیار عالی و اورژانس دقیق',1,'approved','2026-09-07 15:22:28');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_applications`
--

DROP TABLE IF EXISTS `role_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `applied_role` enum('doctor','pharmacist','organization','supplier') NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `degree_document_url` varchar(255) DEFAULT NULL,
  `license_document_url` varchar(255) DEFAULT NULL,
  `organization_name` varchar(255) DEFAULT NULL,
  `organization_type` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `instagram` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_app_status` (`status`),
  KEY `idx_app_role` (`applied_role`),
  KEY `user_id` (`user_id`),
  KEY `idx_status_role` (`status`,`applied_role`),
  CONSTRAINT `role_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_applications`
--

LOCK TABLES `role_applications` WRITE;
/*!40000 ALTER TABLE `role_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_audit_logs`
--

DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(64) NOT NULL COMMENT 'auth_fail, auth_success, waf_blocked, privilege_escalation, file_upload, csrf_mismatch, rate_limit_exceeded, role_change',
  `severity` enum('info','warning','critical','emergency') DEFAULT 'info',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `request_uri` varchar(255) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `payload_summary` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_ip_time` (`ip_address`,`created_at`),
  KEY `idx_user_time` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_audit_logs`
--

LOCK TABLES `security_audit_logs` WRITE;
/*!40000 ALTER TABLE `security_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_banned_ips`
--

DROP TABLE IF EXISTS `security_banned_ips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_banned_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `threat_type` varchar(50) DEFAULT 'manual',
  `cf_ray` varchar(64) DEFAULT NULL,
  `banned_until` datetime NOT NULL,
  `banned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_banned_until` (`banned_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_banned_ips`
--

LOCK TABLES `security_banned_ips` WRITE;
/*!40000 ALTER TABLE `security_banned_ips` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_banned_ips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_escrow_ledger`
--

DROP TABLE IF EXISTS `seller_escrow_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_escrow_ledger` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `order_item_id` int(11) DEFAULT NULL,
  `seller_id` int(11) NOT NULL,
  `gross_amount` bigint(20) NOT NULL,
  `commission_amount` bigint(20) NOT NULL,
  `net_seller_amount` bigint(20) NOT NULL,
  `status` enum('held_in_escrow','released_to_available','settled_in_batch','refunded','disputed') DEFAULT 'held_in_escrow',
  `delivered_at` datetime DEFAULT NULL,
  `payout_eligible_at` datetime DEFAULT NULL COMMENT 'delivered_at + 7 days',
  `settlement_batch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_escrow_seller_status` (`seller_id`,`status`),
  KEY `idx_escrow_order` (`order_id`),
  KEY `idx_escrow_payout_time` (`payout_eligible_at`),
  KEY `idx_escrow_batch` (`settlement_batch_id`),
  KEY `idx_seller_eligible` (`seller_id`,`status`,`payout_eligible_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_escrow_ledger`
--

LOCK TABLES `seller_escrow_ledger` WRITE;
/*!40000 ALTER TABLE `seller_escrow_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_escrow_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_payout_batches`
--

DROP TABLE IF EXISTS `seller_payout_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_payout_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(50) NOT NULL,
  `total_payout_amount` bigint(20) NOT NULL,
  `seller_count` int(11) NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'completed',
  `paya_export_file_url` varchar(255) DEFAULT NULL,
  `paya_export_content` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_code` (`batch_code`),
  KEY `idx_batch_code` (`batch_code`),
  KEY `idx_batch_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_payout_batches`
--

LOCK TABLES `seller_payout_batches` WRITE;
/*!40000 ALTER TABLE `seller_payout_batches` DISABLE KEYS */;
INSERT INTO `seller_payout_batches` VALUES (1,'PAYA-20260911-F7BF',5000000,1,'completed',NULL,'IBAN	AMOUNT_TOMAN	RECIPIENT_NAME	BANK_NAME	DESCRIPTION	REFERENCE_BATCH\r\nIR120560000000012345678901	5000000	امید رضایی	بانک سامان	تسویه حساب هفتگی فروشگاه آسنا - کد پیگیری PAYA-20260911-F7BF	PAYA-20260911-F7BF',1,'2026-09-11 02:01:09','2026-09-11 00:01:09'),(2,'PAYA-SNGL-20260911-2C49',1000000,1,'completed',NULL,'IBAN	AMOUNT_TOMAN	RECIPIENT_NAME	BANK_NAME	DESCRIPTION	REFERENCE_BATCH\r\nIR880570000000001234567890	1000000	تستر آسنا	بانک پاسارگاد	تسویه حساب انفرادی آسنا - تستر آسنا - کد PAYA-SNGL-20260911-2C49	PAYA-SNGL-20260911-2C49',1,'2026-09-11 02:17:28','2026-09-11 00:17:28'),(3,'PAYA-SNGL-20260911-E6AB',1000000,1,'completed',NULL,'IBAN	AMOUNT_TOMAN	RECIPIENT_NAME	BANK_NAME	DESCRIPTION	REFERENCE_BATCH\r\nIR880570000000001234567890	1000000	تستر آسنا	بانک پاسارگاد	تسویه حساب انفرادی آسنا - تستر آسنا - کد PAYA-SNGL-20260911-E6AB	PAYA-SNGL-20260911-E6AB',1,'2026-09-11 02:17:51','2026-09-11 00:17:51'),(4,'PAYA-SNGL-20260911-274B',1500000,1,'completed',NULL,'IBAN	AMOUNT_TOMAN	RECIPIENT_NAME	BANK_NAME	DESCRIPTION	REFERENCE_BATCH\r\nIR880570000000001234567890	1500000	تستر آسنا	بانک پاسارگاد	تسویه حساب انفرادی آسنا - تستر آسنا - کد PAYA-SNGL-20260911-274B	PAYA-SNGL-20260911-274B',1,'2026-09-11 02:17:51','2026-09-11 00:17:51'),(5,'PAYA-20260911-5F02',4600000,2,'completed',NULL,'IBAN	AMOUNT_TOMAN	RECIPIENT_NAME	BANK_NAME	DESCRIPTION	REFERENCE_BATCH\r\nIR120560000000100234567891	3400000	بیمارستان پایتخت	بانک سامان	تسویه حساب هفتگی فروشگاه آسنا - کد پیگیری PAYA-20260911-5F02	PAYA-20260911-5F02\r\nIR880570000000001234567890	1200000	تستر آسنا	بانک پاسارگاد	تسویه حساب هفتگی فروشگاه آسنا - کد پیگیری PAYA-20260911-5F02	PAYA-20260911-5F02',1,'2026-09-11 02:17:57','2026-09-11 00:17:57'),(6,'PAYA-20260911-D925',6250000,2,'completed',NULL,'IBAN	AMOUNT_TOMAN	RECIPIENT_NAME	BANK_NAME	DESCRIPTION	REFERENCE_BATCH\r\nIR120560000000100234567891	4800000	بیمارستان پایتخت	بانک سامان	تسویه حساب هفتگی فروشگاه آسنا - کد پیگیری PAYA-20260911-D925	PAYA-20260911-D925\r\nIR880570000000001234567890	1450000	تستر آسنا	بانک پاسارگاد	تسویه حساب هفتگی فروشگاه آسنا - کد پیگیری PAYA-20260911-D925	PAYA-20260911-D925',1,'2026-09-11 03:06:10','2026-09-11 01:06:10'),(7,'PAYA-20260911-28AD',1450000,1,'completed',NULL,'IBAN	AMOUNT_TOMAN	RECIPIENT_NAME	BANK_NAME	DESCRIPTION	REFERENCE_BATCH\r\nIR880570000000001234567890	1450000	تستر آسنا	بانک پاسارگاد	تسویه حساب هفتگی فروشگاه آسنا - کد پیگیری PAYA-20260911-28AD	PAYA-20260911-28AD',1,'2026-09-11 03:06:44','2026-09-11 01:06:44');
/*!40000 ALTER TABLE `seller_payout_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_wallets`
--

DROP TABLE IF EXISTS `seller_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seller_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_holder` varchar(150) DEFAULT NULL,
  `bank_sheba` varchar(30) DEFAULT NULL COMMENT 'Format: IR followed by 24 digits',
  `bank_card_number` varchar(20) DEFAULT NULL,
  `balance_pending_escrow` bigint(20) DEFAULT 0 COMMENT 'Held in 7-day post-delivery guarantee escrow',
  `balance_available_for_payout` bigint(20) DEFAULT 0 COMMENT 'Released from escrow, eligible for weekly payout',
  `balance_settled_lifetime` bigint(20) DEFAULT 0 COMMENT 'Cumulative total settled through Paya',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sms_credits` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_id` (`seller_id`),
  KEY `idx_seller_wallet` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_wallets`
--

LOCK TABLES `seller_wallets` WRITE;
/*!40000 ALTER TABLE `seller_wallets` DISABLE KEYS */;
INSERT INTO `seller_wallets` VALUES (1,1,NULL,NULL,NULL,NULL,0,0,0,'2026-09-07 15:20:04','2026-09-07 15:20:04',0),(2,14,'بانک پاسارگاد','تستر آسنا','IR880570000000001234567890',NULL,0,0,10150000,'2026-09-07 15:22:07','2026-09-11 01:06:44',0),(3,5,'بانک سامان','بیمارستان پایتخت','IR120560000000100234567891',NULL,0,0,8200000,'2026-09-07 18:07:48','2026-09-11 01:06:10',0),(4,16,NULL,NULL,NULL,NULL,0,0,0,'2026-09-07 18:31:17','2026-09-07 18:31:17',0),(5,7,NULL,NULL,NULL,NULL,0,0,0,'2026-09-07 19:13:06','2026-09-07 19:13:06',0),(6,3,NULL,NULL,NULL,NULL,0,0,0,'2026-09-11 01:04:15','2026-09-11 02:11:35',23);
/*!40000 ALTER TABLE `seller_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipping_rates`
--

DROP TABLE IF EXISTS `shipping_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier_code` varchar(50) NOT NULL,
  `carrier_name_fa` varchar(100) NOT NULL,
  `base_weight_grams` int(11) DEFAULT 1000,
  `base_cost` int(11) NOT NULL DEFAULT 45000,
  `extra_kg_cost` int(11) NOT NULL DEFAULT 15000,
  `inter_provincial_surcharge` int(11) NOT NULL DEFAULT 20000,
  `cold_chain_surcharge` int(11) NOT NULL DEFAULT 50000,
  `estimated_days_min` int(11) DEFAULT 1,
  `estimated_days_max` int(11) DEFAULT 3,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipping_rates`
--

LOCK TABLES `shipping_rates` WRITE;
/*!40000 ALTER TABLE `shipping_rates` DISABLE KEYS */;
INSERT INTO `shipping_rates` VALUES (1,'pishtaz','پست پیشتاز جمهوری اسلامی ایران',1000,48000,15000,22000,0,2,4,1),(2,'tipax','تیپاکس اکسپرس (تحویل درب منزل / کلینیک)',1000,65000,20000,25000,0,1,2,1),(3,'express_courier','پیک موتوری فوری اختصاصی (الوپیک / اسنپ‌باکس)',5000,55000,10000,0,0,1,1,1),(4,'cold_chain_express','پیک ویژه زنجیره سرد دارویی (ایزوترمال + یخ خشک)',2000,120000,30000,45000,60000,1,1,1);
/*!40000 ALTER TABLE `shipping_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_settings`
--

DROP TABLE IF EXISTS `site_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_settings`
--

LOCK TABLES `site_settings` WRITE;
/*!40000 ALTER TABLE `site_settings` DISABLE KEYS */;
INSERT INTO `site_settings` VALUES ('admin_bank_card','6037991199223344','2026-09-11 01:04:46'),('admin_bank_holder','شرکت توسعه تجارت الکترونیک آسنا','2026-09-11 01:04:46'),('admin_bank_name','بانک سامان','2026-09-11 01:04:46'),('admin_bank_sheba','IR120560000000100000000001','2026-09-11 01:04:46'),('auto_payout_day','4','2026-09-11 01:04:46'),('auto_payout_enabled','1','2026-09-11 01:04:46'),('auto_payout_time','09:00','2026-09-11 01:04:46'),('melipayamak_api_key','MELI_SANDBOX_ASENA_PROD_KEY_2026','2026-09-11 01:05:09'),('melipayamak_from','50004001','2026-09-11 01:04:46'),('melipayamak_sandbox','0','2026-09-11 01:04:46'),('melipayamak_username','asena_enterprise','2026-09-11 01:05:09'),('platform_commission_percent','5','2026-09-11 01:04:46'),('sms_pack_100_price','85000','2026-09-11 01:38:40'),('sms_pack_1000_price','680000','2026-09-11 01:38:40'),('sms_pack_500_price','375000','2026-09-11 01:38:40'),('sms_price_per_unit','850','2026-09-11 01:38:40'),('tax_on_appointments_enabled','1','2026-09-11 01:04:46'),('tax_rate_percent','9','2026-09-11 01:04:46');
/*!40000 ALTER TABLE `site_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_delivery_logs`
--

DROP TABLE IF EXISTS `sms_delivery_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_delivery_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `action_tag` varchar(50) NOT NULL,
  `body_id` varchar(30) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_mock` tinyint(1) DEFAULT 0,
  `status` enum('sent','failed','mock_delivered') DEFAULT 'sent',
  `gateway_response` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sms_phone` (`phone`),
  KEY `idx_sms_tag` (`action_tag`),
  KEY `idx_sms_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_delivery_logs`
--

LOCK TABLES `sms_delivery_logs` WRITE;
/*!40000 ALTER TABLE `sms_delivery_logs` DISABLE KEYS */;
INSERT INTO `sms_delivery_logs` VALUES (1,'09129998877','DIRECT',NULL,'آسنا: حواله پایا به مبلغ 1,000,000 تومان به IR8805...7890 صادر شد. شناسه تسویه: PAYA-SNGL-20260911-E6AB',1,'mock_delivered','200 OK (Simulated Sandbox)','2026-09-11 00:17:51'),(2,'09129998877','DIRECT',NULL,'آسنا: حواله پایا به مبلغ 1,500,000 تومان به IR8805...7890 صادر شد. شناسه تسویه: PAYA-SNGL-20260911-274B',1,'mock_delivered','200 OK (Simulated Sandbox)','2026-09-11 00:17:51'),(3,'09000000001','DIRECT',NULL,'آسنا: حواله پایا به مبلغ 3,400,000 تومان به IR1205...7891 صادر شد. شناسه تسویه: PAYA-20260911-5F02',1,'mock_delivered','200 OK (Simulated Sandbox)','2026-09-11 00:17:57'),(4,'09129998877','DIRECT',NULL,'آسنا: حواله پایا به مبلغ 1,200,000 تومان به IR8805...7890 صادر شد. شناسه تسویه: PAYA-20260911-5F02',1,'mock_delivered','200 OK (Simulated Sandbox)','2026-09-11 00:17:57'),(5,'09129998877','DIRECT',NULL,'آسنا: حواله پایا به مبلغ 1,450,000 تومان به IR8805...7890 صادر شد. شناسه تسویه: PAYA-20260911-28AD',1,'mock_delivered','200 OK (Simulated Sandbox)','2026-09-11 01:06:44');
/*!40000 ALTER TABLE `sms_delivery_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_package_purchases`
--

DROP TABLE IF EXISTS `sms_package_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_package_purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `credits` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'wallet',
  `payment_ref` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_package_purchases`
--

LOCK TABLES `sms_package_purchases` WRITE;
/*!40000 ALTER TABLE `sms_package_purchases` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_package_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_usage_logs`
--

DROP TABLE IF EXISTS `sms_usage_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_usage_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `recipient` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `credits_deducted` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_usage_logs`
--

LOCK TABLES `sms_usage_logs` WRITE;
/*!40000 ALTER TABLE `sms_usage_logs` DISABLE KEYS */;
INSERT INTO `sms_usage_logs` VALUES (1,3,'2','تست ارسال پیامک نوبت دهی آزمایشگاهی',1,'2026-09-11 01:04:15'),(2,3,'09120000000','تست ارسال پیامک نوبت دهی آزمایشگاهی',2,'2026-09-11 01:05:25'),(3,3,'09120000000','تست ارسال پیامک نوبت دهی آزمایشگاهی',2,'2026-09-11 01:06:10'),(4,3,'09120000000','تست ارسال پیامک نوبت دهی آزمایشگاهی',2,'2026-09-11 01:06:44'),(5,3,'09120000000','تست ارسال پیامک نوبت دهی آزمایشگاهی',2,'2026-09-11 01:41:09'),(6,3,'09120000000','تست ارسال پیامک نوبت دهی آزمایشگاهی',2,'2026-09-11 01:41:50'),(7,3,'09120000000','تست ارسال پیامک نوبت دهی آزمایشگاهی',2,'2026-09-11 01:52:24'),(8,3,'09120000000','تست ارسال پیامک نوبت دهی آزمایشگاهی',2,'2026-09-11 02:11:35');
/*!40000 ALTER TABLE `sms_usage_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_deliveries`
--

DROP TABLE IF EXISTS `subscription_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `delivery_month` int(11) NOT NULL,
  `scheduled_date` date DEFAULT NULL,
  `status` enum('pending','shipped','delivered','not_received') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `subscription_deliveries_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_deliveries`
--

LOCK TABLES `subscription_deliveries` WRITE;
/*!40000 ALTER TABLE `subscription_deliveries` DISABLE KEYS */;
INSERT INTO `subscription_deliveries` VALUES (1,2,1,'2026-08-03','delivered'),(2,2,2,'2026-09-02','pending'),(3,2,3,'2026-10-02','pending'),(4,2,4,'2026-11-01','pending'),(5,2,5,'2026-12-01','pending'),(6,2,6,'2026-12-31','pending'),(7,3,1,'2026-08-24','pending'),(8,3,2,'2026-09-05','pending'),(9,3,3,'2026-10-05','pending'),(10,4,1,'2026-08-25','pending'),(11,4,2,'2026-09-24','pending'),(12,4,3,'2026-10-24','pending'),(13,5,1,'2026-08-15','shipped'),(14,5,2,'2026-08-28','pending'),(15,6,1,'2026-08-20','pending'),(16,6,2,'2026-09-20','pending'),(17,3,4,'2026-08-24','shipped'),(18,6,3,'2026-08-20','shipped');
/*!40000 ALTER TABLE `subscription_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_logs`
--

DROP TABLE IF EXISTS `subscription_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `old_status` enum('pending_payment','processing','shipped','delivered','cancelled') DEFAULT NULL,
  `new_status` enum('pending_payment','processing','shipped','delivered','cancelled') NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `subscription_logs_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_logs`
--

LOCK TABLES `subscription_logs` WRITE;
/*!40000 ALTER TABLE `subscription_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `frequency_days` int(11) NOT NULL,
  `next_delivery_date` date NOT NULL,
  `status` enum('active','paused','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_request_logs`
--

DROP TABLE IF EXISTS `system_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_request_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `action_type` varchar(50) DEFAULT 'page_view' COMMENT 'browse, auth, checkout, admin, api, suspicious_probe, ddos_flood',
  `request_method` varchar(10) NOT NULL,
  `request_uri` varchar(500) NOT NULL,
  `payload_summary` text DEFAULT NULL COMMENT 'Sanitized parameters with passwords/tokens redacted',
  `cf_ray` varchar(64) DEFAULT NULL COMMENT 'Cloudflare Ray ID',
  `cf_country` varchar(10) DEFAULT NULL COMMENT 'Cloudflare Two-Letter Country Code',
  `user_agent` varchar(255) DEFAULT NULL,
  `response_code` int(11) DEFAULT 200,
  `is_suspicious` tinyint(1) DEFAULT 0,
  `suspicion_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_req_ip_time` (`ip_address`,`created_at`),
  KEY `idx_req_user_time` (`user_id`,`created_at`),
  KEY `idx_req_session` (`session_id`),
  KEY `idx_req_suspicious` (`is_suspicious`,`created_at`),
  KEY `idx_req_action` (`action_type`),
  KEY `idx_req_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_request_logs`
--

LOCK TABLES `system_request_logs` WRITE;
/*!40000 ALTER TABLE `system_request_logs` DISABLE KEYS */;
INSERT INTO `system_request_logs` VALUES (1,'::1',NULL,NULL,'83964abur26gp0trglii540i4m','browse','HEAD','/asena/asena-enterprise/organizations.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:28'),(2,'::1',NULL,NULL,'fiuug842amnfas3gqc5vp0kb0g','browse','HEAD','/asena/asena-enterprise/organization_profile.php?slug=payetakht-hospital','{\"slug\":\"payetakht-hospital\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:55'),(3,'::1',NULL,NULL,'qdci2qppfqegd12sqdj54q0m5q','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=payetakht-hospital','{\"slug\":\"payetakht-hospital\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:59'),(4,'::1',NULL,NULL,'2bug4ssjasf9l1fsit9tehf2p9','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=shiraz-central-hospital','{\"slug\":\"shiraz-central-hospital\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:59'),(5,'::1',NULL,NULL,'njikiugb8f4l69pn031t6op6b6','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=persian-pet-clinic','{\"slug\":\"persian-pet-clinic\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:59'),(6,'::1',NULL,NULL,'5ch0478de8tck43dpcrhj9d74q','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=baran-vet-clinic','{\"slug\":\"baran-vet-clinic\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:59'),(7,'::1',NULL,NULL,'f8jmu0s6j3jqnlov5ds3qk4o7b','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=caspian-exotic-clinic','{\"slug\":\"caspian-exotic-clinic\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:59'),(8,'::1',NULL,NULL,'fgk7glpuaqap86eoq6m5mcccnq','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=razi-vet-pharmacy','{\"slug\":\"razi-vet-pharmacy\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:59'),(9,'::1',NULL,NULL,'3bc01o2jggbm8pm4q6nkfjvauk','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=vafa-animal-shelter','{\"slug\":\"vafa-animal-shelter\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:57:59'),(10,'::1',NULL,NULL,'sfkjjpp6l75406e23ttok81gcf','browse','GET','/asena/asena-enterprise/organizations.php?q=پایتخت','{\"q\":\"پایتخت\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:58:03'),(11,'::1',NULL,NULL,'k2ssg7i5il134pn2peum6cvs3s','browse','GET','/asena/asena-enterprise/organizations.php?city=تهران','{\"city\":\"تهران\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:58:03'),(12,'::1',NULL,NULL,'gt0jq1glfistbk5km7n8opnv91','browse','GET','/asena/asena-enterprise/organizations.php?city=شیراز','{\"city\":\"شیراز\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:58:03'),(13,'::1',NULL,NULL,'o3nd1ud87k33hd9mtfhl2jer44','browse','GET','/asena/asena-enterprise/organizations.php?type=hospital','{\"type\":\"hospital\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:58:03'),(14,'::1',NULL,NULL,'e8u67koves2se7lijfbp4so4f2','browse','GET','/asena/asena-enterprise/organizations.php?type=pharmacy','{\"type\":\"pharmacy\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:58:03'),(15,'::1',NULL,NULL,'bvundcgilakclarpom816p97cm','browse','GET','/asena/asena-enterprise/organizations.php?is_24_7=1','{\"is_24_7\":\"1\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:58:03'),(16,'::1',NULL,NULL,'cprtmdd82b419fdsgaosfevphi','browse','GET','/asena/asena-enterprise/organizations.php?sort=rating','{\"sort\":\"rating\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-05 11:58:03'),(17,'127.0.0.1',NULL,NULL,'h0ub66juo1iafp6f90l7fa0g0o','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-05 11:59:26'),(18,'127.0.0.1',NULL,NULL,'h0ub66juo1iafp6f90l7fa0g0o','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=vafa-animal-shelter','{\"slug\":\"vafa-animal-shelter\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-05 11:59:39'),(19,'127.0.0.1',NULL,NULL,'h0ub66juo1iafp6f90l7fa0g0o','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-05 11:59:47'),(20,'127.0.0.1',NULL,NULL,'h0ub66juo1iafp6f90l7fa0g0o','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-05 12:00:10'),(21,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:12:34'),(22,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:20:10'),(23,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','auth','GET','/asena/asena-enterprise/register.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:25:14'),(24,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','auth','GET','/asena/asena-enterprise/register.php?step=2&role=supplier','{\"step\":\"2\",\"role\":\"supplier\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:25:30'),(25,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','auth','GET','/asena/asena-enterprise/register.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:25:33'),(26,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:25:43'),(27,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:25:46'),(28,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:25:46'),(29,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:25:51'),(30,'::1',NULL,NULL,'kjb4060dhhutq392ae2m0m508u','browse','HEAD','/asena/asena-enterprise/index.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:25:55'),(31,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:26:08'),(32,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:26:14'),(33,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:26:26'),(34,'::1',NULL,NULL,'taoh2p9rs9gh9q2f63hd291meo','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:27:58'),(35,'::1',NULL,NULL,'sge0bpamadsfmq4ta548lat31t','browse','HEAD','/asena/asena-enterprise/index.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:31:13'),(36,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:40:53'),(37,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:40:58'),(38,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:41:41'),(39,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/shop.php?category=%D8%A7%D8%B3%D8%A8%D8%A7%D8%A8%E2%80%8C%D8%A8%D8%A7%D8%B2%DB%8C','{\"category\":\"اسباب‌بازی\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:42:01'),(40,'127.0.0.1',NULL,NULL,'fbapsqbc67q7bjj8ljo7qvflih','browse','GET','/asena/asena-enterprise/subscriptions.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:42:32'),(41,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','cart','GET','/asena/asena-enterprise/cart.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:42:41'),(42,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','cart','GET','/asena/asena-enterprise/cart.php?tab=standard','{\"tab\":\"standard\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:42:46'),(43,'::1',NULL,NULL,'rr4mle12phelkfv1kmn29u12q9','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:43:31'),(44,'::1',NULL,NULL,'bgshvptmpa9nsefv1oq2co6ta2','browse','HEAD','/asena/asena-enterprise/charity.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:46:38'),(45,'::1',NULL,NULL,'duaebdh2juupt31j5uarftv9gr','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:46:46'),(46,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','cart','GET','/asena/asena-enterprise/cart.php?tab=standard','{\"tab\":\"standard\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:47:40'),(47,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:47:42'),(48,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:47:52'),(49,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:48:22'),(50,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:48:35'),(51,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:48:41'),(52,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:49:04'),(53,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:50:04'),(54,'::1',NULL,NULL,'chalrdrtqfvio0tpracktusk56','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:51:24'),(55,'::1',NULL,NULL,'abh0iroq88ks70ub8dvu815ak3','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:54:29'),(56,'::1',NULL,NULL,'sal6ckki29r41qm0d6ir32g9dt','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 14:54:36'),(57,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/booking.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:58:07'),(58,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/subscriptions.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:58:28'),(59,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/knowledge_base.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:58:35'),(60,'127.0.0.1',NULL,NULL,'hrqlf4mpbsbq4f8fg74j1ikvu6','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 14:58:48'),(61,'::1',NULL,NULL,'fe07kek2g8c8oq7sb8uvv70qu7','browse','GET','/asena/asena-enterprise/charity.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:01:56'),(62,'::1',NULL,NULL,'u24sjtbriigtc5r57lpugu7rep','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:10:04'),(63,'::1',NULL,NULL,'ai1hcb2acej25e8inl9glgmvru','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:10:09'),(64,'::1',NULL,NULL,'q4kp2dta24ekbmhv6n2c71am17','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:12:53'),(65,'::1',NULL,NULL,'dodat84bm2apv4dl3vrpshs8uc','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:17:32'),(66,'::1',NULL,NULL,'57360khd652oqafpijp7kjq64d','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=payetakht-hospital','{\"slug\":\"payetakht-hospital\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:17:38'),(67,'::1',NULL,NULL,'7qrb4hbi91t6mlc5r5gumuuabe','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:22:15'),(68,'::1',NULL,NULL,'6ao6gqfu3leuqpj92ekqoes6du','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=payetakht-hospital','{\"slug\":\"payetakht-hospital\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:22:19'),(69,'127.0.0.1',NULL,NULL,'aji73o94fkfv2f8f4k9n8tdc58','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 15:24:46'),(70,'::1',NULL,NULL,'0m5c9550rhn5esiqaq890vl6qh','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:37:16'),(71,'::1',NULL,NULL,'ji9t46tg0kac1ug8lh9odb2oku','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=vafa-animal-shelter','{\"slug\":\"vafa-animal-shelter\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:37:21'),(72,'127.0.0.1',NULL,NULL,'aji73o94fkfv2f8f4k9n8tdc58','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 15:48:26'),(73,'127.0.0.1',NULL,NULL,'aji73o94fkfv2f8f4k9n8tdc58','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=payetakht-hospital','{\"slug\":\"payetakht-hospital\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 15:49:09'),(74,'::1',NULL,NULL,'bc2ivhajei57tk27jj8ufl6jai','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 15:52:45'),(75,'::1',NULL,NULL,'lm1en15dpetjoukpee7cbu92sh','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:54:46'),(76,'::1',NULL,NULL,'lm1en15dpetjoukpee7cbu92sh','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:54:48'),(77,'::1',NULL,NULL,'lm1en15dpetjoukpee7cbu92sh','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:54:48'),(78,'::1',NULL,NULL,'qu6si4rjn0f6ee39b0gcb9g1ee','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:54:57'),(79,'::1',NULL,NULL,'qu6si4rjn0f6ee39b0gcb9g1ee','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:54:59'),(80,'::1',NULL,NULL,'qu6si4rjn0f6ee39b0gcb9g1ee','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:54:59'),(81,'::1',NULL,NULL,'ciusgi8oegb7t7rskj82sduuuc','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:55:20'),(82,'::1',NULL,NULL,'ciusgi8oegb7t7rskj82sduuuc','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:55:22'),(83,'::1',NULL,NULL,'ciusgi8oegb7t7rskj82sduuuc','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:55:22'),(84,'::1',NULL,NULL,'ok7jf6j936p3c6r0eb023sh9ro','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=payetakht-hospital','{\"slug\":\"payetakht-hospital\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:55:48'),(85,'::1',NULL,NULL,'ok7jf6j936p3c6r0eb023sh9ro','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:55:59'),(86,'::1',NULL,NULL,'ok7jf6j936p3c6r0eb023sh9ro','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:55:59'),(87,'::1',NULL,NULL,'earj50fiola8tn6l33fg6jmilp','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=payetakht-hospital','{\"slug\":\"payetakht-hospital\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:56:42'),(88,'::1',NULL,NULL,'earj50fiola8tn6l33fg6jmilp','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:56:44'),(89,'::1',NULL,NULL,'earj50fiola8tn6l33fg6jmilp','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:56:44'),(90,'::1',NULL,NULL,'94hj55cktk6tbhviunmfcf2ai2','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=vafa-animal-shelter','{\"slug\":\"vafa-animal-shelter\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:57:05'),(91,'::1',NULL,NULL,'94hj55cktk6tbhviunmfcf2ai2','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:57:06'),(92,'::1',NULL,NULL,'94hj55cktk6tbhviunmfcf2ai2','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:57:06'),(93,'::1',NULL,NULL,'v21m67mi4g4qm60htkad0tbimv','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:57:25'),(94,'::1',NULL,NULL,'v21m67mi4g4qm60htkad0tbimv','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:57:34'),(95,'::1',NULL,NULL,'v21m67mi4g4qm60htkad0tbimv','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 15:57:34'),(96,'127.0.0.1',NULL,NULL,'ujtnlh0bauurprk9sp0ujbq94m','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 15:59:28'),(97,'127.0.0.1',NULL,NULL,'ujtnlh0bauurprk9sp0ujbq94m','browse','GET','/asena/asena-enterprise/organizations.php?q=&city=&type=shelter_charity&sort=featured&is_24_7=0','{\"q\":\"\",\"city\":\"\",\"type\":\"shelter_charity\",\"sort\":\"featured\",\"is_24_7\":\"0\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 16:03:28'),(98,'127.0.0.1',NULL,NULL,'ujtnlh0bauurprk9sp0ujbq94m','browse','GET','/asena/asena-enterprise/organizations.php?q=&city=&type=pharmacy&sort=featured&is_24_7=0','{\"q\":\"\",\"city\":\"\",\"type\":\"pharmacy\",\"sort\":\"featured\",\"is_24_7\":\"0\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 16:03:33'),(99,'127.0.0.1',NULL,NULL,'ujtnlh0bauurprk9sp0ujbq94m','browse','GET','/asena/asena-enterprise/organizations.php?q=&city=&type=&sort=featured&is_24_7=0','{\"q\":\"\",\"city\":\"\",\"type\":\"\",\"sort\":\"featured\",\"is_24_7\":\"0\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 16:03:37'),(100,'::1',NULL,NULL,'fvbrbem83o4dqn99phdf0jp9m7','browse','GET','/asena/asena-enterprise/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 16:04:40'),(101,'::1',NULL,NULL,'fvbrbem83o4dqn99phdf0jp9m7','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 16:04:42'),(102,'::1',NULL,NULL,'fvbrbem83o4dqn99phdf0jp9m7','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 16:04:42'),(103,'127.0.0.1',NULL,NULL,'ujtnlh0bauurprk9sp0ujbq94m','browse','GET','/asena/asena-enterprise/organizations.php?q=&city=&type=&sort=featured&is_24_7=0','{\"q\":\"\",\"city\":\"\",\"type\":\"\",\"sort\":\"featured\",\"is_24_7\":\"0\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 16:07:51'),(104,'127.0.0.1',NULL,NULL,'ujtnlh0bauurprk9sp0ujbq94m','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 16:14:14'),(105,'::1',NULL,NULL,'hlcreudpne3i6212kq9jsi3js6','browse','HEAD','/asena/asena-enterprise/index.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 16:22:46'),(106,'::1',NULL,NULL,'rksbloccg0tof0ed9qlhekq9uq','auth','GET','/asena/asena-enterprise/register.php?role=organization','{\"role\":\"organization\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 16:22:50'),(107,'127.0.0.1',NULL,NULL,'f00uslbvu8t4v35c9166j7d9to','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 16:56:42'),(108,'127.0.0.1',NULL,NULL,'f00uslbvu8t4v35c9166j7d9to','browse','GET','/asena/asena-enterprise/product_details.php?id=10','{\"id\":\"10\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:01:27'),(109,'127.0.0.1',NULL,NULL,'f00uslbvu8t4v35c9166j7d9to','browse','GET','/asena/asena-enterprise/product_details.php?id=10','{\"id\":\"10\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:01:32'),(110,'127.0.0.1',NULL,NULL,'f00uslbvu8t4v35c9166j7d9to','browse','GET','/asena/asena-enterprise/product_details.php?id=3','{\"id\":\"3\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:03:26'),(111,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:05:26'),(112,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:05:28'),(113,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:05:28'),(114,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/product_details.php?id=6','{\"id\":\"6\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:05:36'),(115,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:05:39'),(116,'127.0.0.1',NULL,NULL,'f00uslbvu8t4v35c9166j7d9to','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:06:46'),(117,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:07:35'),(118,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:08:58'),(119,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/product_details.php?id=10','{\"id\":\"10\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:13:02'),(120,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/product_details.php?id=10','{\"id\":\"10\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:13:51'),(121,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/product_details.php?id=10','{\"id\":\"10\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:16:11'),(122,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/product_details.php?id=1','{\"id\":\"1\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:16:39'),(123,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/product_details.php?id=10','{\"id\":\"10\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:17:42'),(124,'127.0.0.1',NULL,NULL,'f00uslbvu8t4v35c9166j7d9to','browse','GET','/asena/asena-enterprise/product_details.php?id=2','{\"id\":\"2\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:20:48'),(125,'::1',NULL,NULL,'3i462avpamq8es5s8rbk9lfq75','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:29:59'),(126,'::1',NULL,NULL,'pvqj29k3g5gb69du7qif2ondg7','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:33:48'),(127,'::1',NULL,NULL,'pvqj29k3g5gb69du7qif2ondg7','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:33:50'),(128,'::1',NULL,NULL,'pvqj29k3g5gb69du7qif2ondg7','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:33:50'),(129,'::1',NULL,NULL,'2g3bc8jgfogirra3fm0oddk1bo','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:35:15'),(130,'::1',NULL,NULL,'2g3bc8jgfogirra3fm0oddk1bo','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:35:17'),(131,'::1',NULL,NULL,'2g3bc8jgfogirra3fm0oddk1bo','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 17:35:17'),(132,'127.0.0.1',NULL,NULL,'v7n75hg54us167u2e0p9aa98dk','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:37:53'),(133,'127.0.0.1',NULL,NULL,'v7n75hg54us167u2e0p9aa98dk','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:38:02'),(134,'127.0.0.1',NULL,NULL,'v7n75hg54us167u2e0p9aa98dk','browse','GET','/asena/asena-enterprise/product_details.php?id=9','{\"id\":\"9\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:38:10'),(135,'127.0.0.1',1,NULL,'iofe0sfdbiapm0jaa9m0ml9674','browse','GET','/asena/asena-enterprise/organization_profile.php?slug=vafa-animal-shelter','{\"slug\":\"vafa-animal-shelter\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:49:28'),(136,'127.0.0.1',1,NULL,'iofe0sfdbiapm0jaa9m0ml9674','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:49:35'),(137,'127.0.0.1',1,NULL,'iofe0sfdbiapm0jaa9m0ml9674','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:49:48'),(138,'127.0.0.1',1,NULL,'iofe0sfdbiapm0jaa9m0ml9674','admin_action','GET','/asena/asena-enterprise/admin/sellers.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:50:17'),(139,'127.0.0.1',1,NULL,'iofe0sfdbiapm0jaa9m0ml9674','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:50:21'),(140,'::1',NULL,NULL,'ah0lpu16qsvjmug9udhkk5178l','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 17:50:45'),(141,'127.0.0.1',NULL,NULL,'iofe0sfdbiapm0jaa9m0ml9674','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 17:51:32'),(142,'::1',1,NULL,'1f24sjvf7hcek4jt2pc92n5ub6','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 17:54:22'),(143,'::1',1,NULL,'1f24sjvf7hcek4jt2pc92n5ub6','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 17:54:26'),(144,'::1',1,NULL,'1f24sjvf7hcek4jt2pc92n5ub6','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 17:55:31'),(145,'127.0.0.1',NULL,NULL,'iofe0sfdbiapm0jaa9m0ml9674','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:05:09'),(146,'127.0.0.1',NULL,NULL,'iofe0sfdbiapm0jaa9m0ml9674','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:05:09'),(147,'127.0.0.1',NULL,NULL,'iofe0sfdbiapm0jaa9m0ml9674','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:05:10'),(148,'127.0.0.1',NULL,NULL,'aa59nlmcn5uu07f3oatgnhqb29','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:05:49'),(149,'127.0.0.1',1,NULL,'i9fqcqphfnfd45jkmh9lat0vb2','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:06:05'),(150,'127.0.0.1',1,NULL,'i9fqcqphfnfd45jkmh9lat0vb2','admin_action','POST','/asena/asena-enterprise/admin/organizations.php','{\"action\":\"toggle_status\",\"org_id\":\"9\",\"status\":\"suspended\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:06:35'),(151,'127.0.0.1',1,NULL,'i9fqcqphfnfd45jkmh9lat0vb2','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:06:35'),(152,'127.0.0.1',1,NULL,'i9fqcqphfnfd45jkmh9lat0vb2','admin_action','POST','/asena/asena-enterprise/admin/organizations.php','{\"action\":\"toggle_status\",\"org_id\":\"9\",\"status\":\"approved\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:06:40'),(153,'127.0.0.1',1,NULL,'i9fqcqphfnfd45jkmh9lat0vb2','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:06:40'),(154,'127.0.0.1',1,NULL,'i9fqcqphfnfd45jkmh9lat0vb2','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:06:44'),(155,'127.0.0.1',NULL,NULL,'i9fqcqphfnfd45jkmh9lat0vb2','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:06:52'),(156,'127.0.0.1',NULL,NULL,'qajsld1rou8p399h5bp09d5i62','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:07:52'),(157,'127.0.0.1',NULL,NULL,'ogsr46ldtqvi7idrkjvp4u918o','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:08:30'),(158,'127.0.0.1',16,NULL,'9041aru27orlrriji08aqrr2ae','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:31:04'),(159,'127.0.0.1',16,NULL,'9041aru27orlrriji08aqrr2ae','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:31:17'),(160,'127.0.0.1',NULL,NULL,'9041aru27orlrriji08aqrr2ae','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:33:35'),(161,'127.0.0.1',16,NULL,'92v6rfd5c9nspl8a8jd5djv18l','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:33:59'),(162,'127.0.0.1',16,NULL,'92v6rfd5c9nspl8a8jd5djv18l','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:34:04'),(163,'127.0.0.1',NULL,NULL,'92v6rfd5c9nspl8a8jd5djv18l','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:34:14'),(164,'127.0.0.1',16,NULL,'3mk547fl51k09dhsnps9lmtej2','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:35:05'),(165,'127.0.0.1',16,NULL,'3mk547fl51k09dhsnps9lmtej2','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:35:20'),(166,'::1',NULL,NULL,'riahucdvughen797jmjks7c81j','api','GET','/asena/asena-enterprise/api/v1/pets.php?action=calculate_dosage&species=dog&weight_kg=12&medication_type=dewormer','{\"action\":\"calculate_dosage\",\"species\":\"dog\",\"weight_kg\":\"12\",\"medication_type\":\"dewormer\"}',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 18:36:46'),(167,'::1',1,NULL,'ta705so34vhh3btu9kmuc6dm2h','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 18:37:42'),(168,'127.0.0.1',NULL,NULL,'e47ju2el3ev0kr8j8uceq09on9','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:45:59'),(169,'127.0.0.1',NULL,NULL,'e47ju2el3ev0kr8j8uceq09on9','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:45:59'),(170,'127.0.0.1',NULL,NULL,'e47ju2el3ev0kr8j8uceq09on9','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:45:59'),(171,'127.0.0.1',NULL,NULL,'e47ju2el3ev0kr8j8uceq09on9','admin_action','GET','/asena/asena-enterprise/admin/sellers.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:45:59'),(172,'127.0.0.1',NULL,NULL,'e47ju2el3ev0kr8j8uceq09on9','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:45:59'),(173,'127.0.0.1',NULL,NULL,'vn41s6i9moqnf4nl73i7l1ohnn','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:46:20'),(174,'127.0.0.1',NULL,NULL,'vn41s6i9moqnf4nl73i7l1ohnn','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:46:20'),(175,'127.0.0.1',NULL,NULL,'vn41s6i9moqnf4nl73i7l1ohnn','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:46:20'),(176,'127.0.0.1',NULL,NULL,'vn41s6i9moqnf4nl73i7l1ohnn','admin_action','GET','/asena/asena-enterprise/admin/sellers.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:46:20'),(177,'127.0.0.1',NULL,NULL,'vn41s6i9moqnf4nl73i7l1ohnn','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 18:46:20'),(178,'127.0.0.1',16,NULL,'3mk547fl51k09dhsnps9lmtej2','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 18:56:17'),(179,'127.0.0.1',NULL,NULL,'f5sj63nkqus8503ppg5sgkfloi','browse','GET','/asena/asena-enterprise/booking.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 19:00:19'),(180,'127.0.0.1',NULL,NULL,'m76tidfo1vl6jemb69od495ggv','browse','GET','/asena/asena-enterprise/booking.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-07 19:00:25'),(181,'127.0.0.1',16,NULL,'3mk547fl51k09dhsnps9lmtej2','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 19:01:11'),(182,'127.0.0.1',16,NULL,'3mk547fl51k09dhsnps9lmtej2','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 19:02:03'),(183,'127.0.0.1',16,NULL,'hlcjj4ibtm81pa4okr7ctc0qg1','browse','GET','/asena/asena-enterprise/profile.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 19:14:49'),(184,'127.0.0.1',NULL,NULL,'hlcjj4ibtm81pa4okr7ctc0qg1','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1',200,0,NULL,'2026-09-07 19:22:42'),(185,'127.0.0.1',NULL,NULL,'hlcjj4ibtm81pa4okr7ctc0qg1','cart','GET','/asena/asena-enterprise/cart.php','',NULL,NULL,'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1',200,0,NULL,'2026-09-07 19:22:51'),(186,'127.0.0.1',NULL,NULL,'hlcjj4ibtm81pa4okr7ctc0qg1','browse','GET','/asena/asena-enterprise/booking.php','',NULL,NULL,'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1',200,0,NULL,'2026-09-07 19:23:03'),(187,'127.0.0.1',NULL,NULL,'hlcjj4ibtm81pa4okr7ctc0qg1','browse','GET','/asena/asena-enterprise/shop.php','',NULL,NULL,'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1',200,0,NULL,'2026-09-07 19:23:12'),(188,'127.0.0.1',NULL,NULL,'hlcjj4ibtm81pa4okr7ctc0qg1','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.0 Mobile/15E148 Safari/604.1',200,0,NULL,'2026-09-07 19:23:18'),(189,'::1',NULL,NULL,'g3uas93lopme90jt534cbpmgdo','cart','HEAD','/asena/asena-enterprise/cart.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-07 19:43:40'),(190,'::1',NULL,NULL,'chum603jlenremnme94s920t51','cart','GET','/asena/asena-enterprise/cart.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 19:44:03'),(191,'::1',NULL,NULL,'chum603jlenremnme94s920t51','cart','GET','/asena/asena-enterprise/cart.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 19:44:28'),(192,'::1',NULL,NULL,'chum603jlenremnme94s920t51','cart','GET','/asena/asena-enterprise/cart.php?tab=standard','{\"tab\":\"standard\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 19:44:40'),(193,'::1',NULL,NULL,'chum603jlenremnme94s920t51','cart','GET','/asena/asena-enterprise/cart.php?tab=standard','{\"tab\":\"standard\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 19:44:40'),(194,'::1',NULL,NULL,'chum603jlenremnme94s920t51','cart','GET','/asena/asena-enterprise/cart.php?tab=standard','{\"tab\":\"standard\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 19:46:45'),(195,'::1',NULL,NULL,'chum603jlenremnme94s920t51','cart','GET','/asena/asena-enterprise/cart.php?tab=standard','{\"tab\":\"standard\"}',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-07 19:49:15'),(196,'127.0.0.1',NULL,NULL,'hlcjj4ibtm81pa4okr7ctc0qg1','cart','GET','/asena/asena-enterprise/cart.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:151.0) Gecko/20100101 Firefox/151.0',200,0,NULL,'2026-09-07 19:49:25'),(197,'127.0.0.1',NULL,NULL,'p18n914tqbgk2kgg9gjmm94bef','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-08 14:13:40'),(198,'127.0.0.1',NULL,NULL,'k1s0mtu5aagf9mo5bgkm77ufea','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-08 15:43:33'),(199,'127.0.0.1',NULL,NULL,'k1s0mtu5aagf9mo5bgkm77ufea','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-08 15:51:09'),(200,'127.0.0.1',NULL,NULL,'uqqposkkl546hapf6dlr3lc96a','api','GET','/actions/print_shipping_label.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-09 14:25:43'),(201,'127.0.0.1',NULL,NULL,'625k64agkn5rmllf76ij321h6l','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-09 14:26:11'),(202,'127.0.0.1',NULL,NULL,'uqqposkkl546hapf6dlr3lc96a','api','GET','/actions/print_shipping_label.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-09 14:41:28'),(203,'127.0.0.1',1,'مدیر سیستم','4rm1s41dbc0m0gh1k4iveba1lk','api','GET','/actions/print_shipping_label.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-09 17:59:25'),(204,'::1',NULL,NULL,'8h7ol2nemhu6d5vfnqdhgktgsc','browse','HEAD','/asena/asena-enterprise/','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 12:41:30'),(205,'127.0.0.1',NULL,NULL,'o4j1s4mle9jbe92b5lqjvlciv4','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-10 12:42:17'),(206,'127.0.0.1',NULL,NULL,'o4j1s4mle9jbe92b5lqjvlciv4','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-10 12:42:17'),(207,'127.0.0.1',NULL,NULL,'o4j1s4mle9jbe92b5lqjvlciv4','admin_action','GET','/asena/asena-enterprise/admin/doctors.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-10 12:42:17'),(208,'127.0.0.1',NULL,NULL,'o4j1s4mle9jbe92b5lqjvlciv4','admin_action','GET','/asena/asena-enterprise/admin/sellers.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-10 12:42:17'),(209,'127.0.0.1',NULL,NULL,'o4j1s4mle9jbe92b5lqjvlciv4','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Unknown',200,0,NULL,'2026-09-10 12:42:18'),(210,'::1',NULL,NULL,'r2avi2dgqph506v02lf0oe2m91','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 12:43:17'),(211,'::1',NULL,NULL,'lskfju2rvciuv2r8aoq1cukjcm','cart','HEAD','/asena/asena-enterprise/cart.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 12:43:20'),(212,'::1',NULL,NULL,'1vpnjfct97cuqhq8aad6e64u03','auth','HEAD','/asena/asena-enterprise/register.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 12:43:20'),(213,'::1',NULL,NULL,'3ffpdukhj5scuhp2kmk4eafqc5','browse','HEAD','/asena/asena-enterprise/shop.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 12:43:20'),(214,'::1',NULL,NULL,'jajulafsbud4dslsooolf6iard','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 12:43:32'),(215,'127.0.0.1',NULL,NULL,'solbek6a2mm3nj19iiaivaoibr','browse','GET','/asena/asena-enterprise/','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-10 12:43:43'),(216,'127.0.0.1',1,'مدیر سیستم','18keh6mpdfa6l8lsf7mqp80u0s','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-10 14:25:11'),(217,'127.0.0.1',1,'مدیر سیستم','882ejq3fgf3sf6qq27trf04ili','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-10 15:27:29'),(218,'::1',NULL,NULL,'7tmja7a3cvhke6lsk0gggrdhe1','browse','GET','/asena/asena-enterprise/privacy.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-10 16:07:12'),(219,'::1',NULL,NULL,'7tmja7a3cvhke6lsk0gggrdhe1','browse','GET','/asena/asena-enterprise/terms.php','',NULL,NULL,'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',200,0,NULL,'2026-09-10 16:07:26'),(220,'::1',NULL,NULL,'pp09crfdlk662gq1bdo46tnmn8','browse','GET','/asena/asena-enterprise/terms.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 17:31:36'),(221,'::1',NULL,NULL,'2fbttm89pj72jvdo9p51egrsob','browse','GET','/asena/asena-enterprise/privacy.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 17:31:36'),(222,'::1',NULL,NULL,'trqikiimqdpv4kgvqet6hi19eb','browse','GET','/asena/asena-enterprise/terms.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 17:32:30'),(223,'::1',NULL,NULL,'m3biofhco41vrrmig1tvg1di51','browse','GET','/asena/asena-enterprise/privacy.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 17:32:30'),(224,'127.0.0.1',1,'مدیر سیستم','8rcdvpg4oqmbkrq1r6p163dnh8','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-10 23:55:18'),(225,'127.0.0.1',1,'مدیر سیستم','8rcdvpg4oqmbkrq1r6p163dnh8','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-10 23:59:20'),(226,'::1',NULL,NULL,'tqvp1nilqvnuvgqdl3t06aip5t','browse','GET','/asena/asena-enterprise/terms.php','',NULL,NULL,'curl/8.16.0',200,0,NULL,'2026-09-10 23:59:50'),(227,'127.0.0.1',1,'مدیر سیستم','8rcdvpg4oqmbkrq1r6p163dnh8','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 00:00:54'),(228,'127.0.0.1',1,'مدیر سیستم','8rcdvpg4oqmbkrq1r6p163dnh8','admin_action','POST','/asena/asena-enterprise/admin/payouts.php','{\"csrf_token\":\"[محرمانه - ردکت شده]\",\"action\":\"weekly_payout\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 00:01:09'),(229,'127.0.0.1',1,'مدیر سیستم','8rcdvpg4oqmbkrq1r6p163dnh8','admin_action','POST','/asena/asena-enterprise/admin/payouts.php','{\"csrf_token\":\"[محرمانه - ردکت شده]\",\"action\":\"sync_post\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 00:07:21'),(230,'127.0.0.1',1,'مدیر سیستم','8hnp4qemk3mmqvgch4vgqmi2pb','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 00:25:23'),(231,'127.0.0.1',1,'مدیر سیستم','8hnp4qemk3mmqvgch4vgqmi2pb','admin_action','GET','/asena/asena-enterprise/admin/payouts.php?download_batch=5','{\"download_batch\":\"5\"}',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 00:26:17'),(232,'127.0.0.1',1,'مدیر سیستم','8hnp4qemk3mmqvgch4vgqmi2pb','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 00:48:40'),(233,'127.0.0.1',1,'مدیر سیستم','upo3sjat8nkpsr4nu182hgl8vh','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 01:33:16'),(234,'127.0.0.1',1,'مدیر سیستم','upo3sjat8nkpsr4nu182hgl8vh','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 01:33:16'),(235,'127.0.0.1',1,'مدیر سیستم','upo3sjat8nkpsr4nu182hgl8vh','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 01:33:17'),(236,'127.0.0.1',1,'مدیر سیستم','upo3sjat8nkpsr4nu182hgl8vh','admin_action','GET','/asena/asena-enterprise/admin/organizations.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 01:33:17'),(237,'127.0.0.1',1,'مدیر سیستم','upo3sjat8nkpsr4nu182hgl8vh','admin_action','GET','/asena/asena-enterprise/admin/payouts.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 01:33:18'),(238,'127.0.0.1',5,'test user','s79m3c07fl8059cpjofdsmru7v','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 01:36:28'),(239,'127.0.0.1',1,'مدیر سیستم','d8e8adq0nf61d56eu35nkvarvs','browse','GET','/asena/asena-enterprise/index.php','',NULL,NULL,'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:155.0) Gecko/20100101 Firefox/155.0',200,0,NULL,'2026-09-11 01:48:41');
/*!40000 ALTER TABLE `system_request_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('user','ai','admin') NOT NULL,
  `message` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_messages`
--

LOCK TABLES `ticket_messages` WRITE;
/*!40000 ALTER TABLE `ticket_messages` DISABLE KEYS */;
INSERT INTO `ticket_messages` VALUES (1,1,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-07-31 12:31:35'),(2,2,'admin','درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.',NULL,'2026-07-31 12:31:51'),(3,1,'user','hi',NULL,'2026-07-31 12:51:15'),(4,1,'ai','خطا در برقراری ارتباط با مغز لئو.',NULL,'2026-07-31 12:51:17'),(5,2,'user','hello',NULL,'2026-07-31 12:51:31'),(6,2,'admin','hi',NULL,'2026-07-31 12:51:45'),(7,2,'admin','what is the problem with your pet',NULL,'2026-07-31 12:52:01'),(8,2,'user','hi',NULL,'2026-07-31 13:04:41'),(9,3,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-01 13:16:39'),(10,4,'admin','درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.',NULL,'2026-08-01 17:04:41'),(11,5,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-01 18:17:18'),(12,6,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 08:16:02'),(13,7,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 08:23:13'),(14,8,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 08:24:18'),(15,9,'admin','درخواست شما ثبت شد. یکی از کارشناسان ما به زودی پاسخگوی شما خواهد بود.',NULL,'2026-08-03 09:05:41'),(16,10,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 11:52:33'),(17,11,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-03 11:55:12'),(18,14,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-18 12:04:24'),(19,15,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-18 12:58:32'),(20,16,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-24 19:20:40'),(21,17,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-08-27 10:29:32'),(29,26,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-09-07 18:31:07'),(34,31,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-09-10 23:55:20'),(35,32,'ai','سلام! من لئو هستم، دستیار هوشمند شما. چطور می‌تونم به فرشته کوچولوت کمک کنم؟',NULL,'2026-09-11 01:36:31'),(36,31,'user','سگ من امروز خیلی عطسه میکنه، خطرناکه؟',NULL,'2026-09-11 03:49:45'),(37,31,'ai','سلام! عطسه زیاد سگ می‌تونه دلایل مختلفی مثل حساسیت فصلی، ورود گرد و غبار، سرماخوردگی یا حتی وجود یک جسم خارجی در بینی‌اش داشته باشه. \n\nاگر عطسه‌ها همراه با ترشحات زرد یا سبز، بی‌حالی، سرفه یا تب هست، حتماً باید توسط دامپزشک معاینه بشه و خطرناکه. در غیر این صورت، محیطش رو از دود و گرد و غبار دور نگه دارید. \n\nدر پت‌شاپ ASENA قطره‌های تقویت سیستم ایمنی و بخورهای مناسبی داریم که می‌تونه بهش کمک کنه. می‌خوای برات معرفی کنم؟',NULL,'2026-09-11 03:49:47');
/*!40000 ALTER TABLE `ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `mode` enum('ai','admin') DEFAULT 'admin',
  `status` enum('open','closed','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_tickets_status` (`status`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (1,2,'ai','closed','2026-07-31 12:31:35','2026-08-01 13:16:03'),(2,2,'admin','closed','2026-07-31 12:31:51','2026-08-01 13:16:03'),(3,2,'ai','closed','2026-08-01 13:16:39','2026-08-03 08:12:56'),(4,2,'admin','closed','2026-08-01 17:04:41','2026-08-03 08:12:56'),(5,9,'ai','closed','2026-08-01 18:17:18','2026-08-03 08:12:56'),(6,7,'ai','closed','2026-08-03 08:16:02','2026-08-24 19:31:37'),(7,2,'ai','closed','2026-08-03 08:23:13','2026-08-24 19:31:37'),(8,10,'ai','closed','2026-08-03 08:24:18','2026-08-24 19:31:37'),(9,2,'admin','closed','2026-08-03 09:05:41','2026-08-24 19:31:37'),(10,11,'ai','closed','2026-08-03 11:52:33','2026-08-24 19:31:37'),(11,3,'ai','closed','2026-08-03 11:55:12','2026-08-24 19:31:37'),(14,1,'ai','closed','2026-08-18 12:04:24','2026-08-24 19:31:37'),(15,12,'ai','closed','2026-08-18 12:58:32','2026-08-24 19:31:37'),(16,13,'ai','closed','2026-08-24 19:20:40','2026-09-10 15:27:05'),(17,5,'ai','closed','2026-08-27 10:29:32','2026-09-10 15:27:05'),(26,16,'ai','closed','2026-09-07 18:31:07','2026-09-10 15:27:05'),(31,1,'ai','open','2026-09-10 23:55:20','2026-09-10 23:55:20'),(32,5,'ai','open','2026-09-11 01:36:31','2026-09-11 01:36:31');
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_pets`
--

DROP TABLE IF EXISTS `user_pets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_pets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `race` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` varchar(20) DEFAULT NULL,
  `age` varchar(50) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `microchip_number` varchar(100) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `last_doctor_id` int(11) DEFAULT NULL,
  `clinical_verified_at` datetime DEFAULT NULL,
  `pending_doctor_proposal` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_pets`
--

LOCK TABLES `user_pets` WRITE;
/*!40000 ALTER TABLE `user_pets` DISABLE KEYS */;
INSERT INTO `user_pets` VALUES (1,2,'joei','سگ','germenshepert','2026-07-23 11:51:15','نر','8',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,2,'pisi','گربه','persian','2026-07-23 13:41:51','ماده','2',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,5,'Bobby2','سگ','Husky','2026-07-25 01:35:21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,7,'dogie','سگ','bulldog','2026-07-25 12:32:50',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,11,'akbar','سگ','','2026-08-03 11:52:49',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,33,'هاپو تستی','dog','ژرمن','2026-09-10 17:27:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `user_pets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_subscriptions`
--

DROP TABLE IF EXISTS `user_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `status` enum('active','ended','cancelled') DEFAULT 'active',
  `next_delivery_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration_months` int(11) DEFAULT 3,
  `payment_model` enum('monthly','upfront') DEFAULT 'monthly',
  `delivery_frequency` varchar(50) DEFAULT '1_month',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_subs_status` (`status`),
  CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_subscriptions`
--

LOCK TABLES `user_subscriptions` WRITE;
/*!40000 ALTER TABLE `user_subscriptions` DISABLE KEYS */;
INSERT INTO `user_subscriptions` VALUES (2,2,'اشتراک ۶ ماهه',2100000,'active','2026-09-02','2026-07-31 19:08:06',6,'monthly','1_month'),(3,11,'اشتراک ۳ ماهه',2500000,'active','2026-09-23','2026-08-03 11:53:44',3,'monthly','1_month'),(4,4,'اشتراک ۳ ماهه ویژه گربه',1850000,'active','2026-08-25','2026-08-20 08:00:00',3,'monthly','2_weeks'),(5,6,'اشتراک ماهانه داروهای قلبی سگ',950000,'active','2026-08-28','2026-08-10 10:00:00',1,'monthly','2_weeks'),(6,5,'اشتراک ۶ ماهه مکمل و سم اسب',3200000,'active','2026-09-19','2026-08-01 07:00:00',6,'monthly','1_month');
/*!40000 ALTER TABLE `user_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_wallets`
--

DROP TABLE IF EXISTS `user_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT 0,
  `currency` varchar(10) DEFAULT 'IRT',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_wallets`
--

LOCK TABLES `user_wallets` WRITE;
/*!40000 ALTER TABLE `user_wallets` DISABLE KEYS */;
INSERT INTO `user_wallets` VALUES (1,16,0,'IRT','2026-09-07 18:31:17','2026-09-07 18:31:17'),(2,1,0,'IRT','2026-09-07 18:37:42','2026-09-07 18:37:42'),(3,14,0,'IRT','2026-09-07 19:11:21','2026-09-07 19:11:21'),(4,7,0,'IRT','2026-09-07 19:13:06','2026-09-07 19:13:06');
/*!40000 ALTER TABLE `user_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `sms_code` varchar(10) DEFAULT NULL,
  `role` enum('user','admin','doctor','organization','pharmacist','seller') DEFAULT 'user',
  `pending_role` varchar(50) DEFAULT NULL,
  `verification_status` enum('none','pending','approved','rejected') DEFAULT 'none',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) DEFAULT NULL,
  `pet_type` varchar(50) DEFAULT NULL,
  `pet_race` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `loyalty_points` int(11) DEFAULT 0,
  `last_monthly_points_date` date DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `apple_id` varchar(255) DEFAULT NULL,
  `national_id` varchar(20) DEFAULT NULL,
  `vet_council_number` varchar(50) DEFAULT NULL,
  `is_verified_vet` tinyint(1) DEFAULT 0,
  `sheba_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `phone_2` (`phone`),
  UNIQUE KEY `google_id` (`google_id`),
  UNIQUE KEY `apple_id` (`apple_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'09123456789','مدیر سیستم',NULL,'admin',NULL,'none','2026-07-21 13:23:55','$2y$10$ZAeDLnSqy8Hn0ZcShAY6/O.mLBntXBzfnyAvcy8NCsbrQiOhhEzse',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,60,'2026-09-07',NULL,NULL,NULL,NULL,0,NULL),(2,'096154654','ayhan',NULL,'admin',NULL,'none','2026-07-21 17:16:18','$2y$10$wQ/UJ0eAxhmzvST6hlmYaOuNu/jWLHjPytzydJbiEit.l3.ZOx1D.',NULL,NULL,'mehrzad.ayhan@gmail.com','تبریز','','نارمک, Golgasht, مرز محله, Tabriz, بخش مرکزی شهرستان تبریز, Tabriz County, East Azerbaijan Province, 51639-17697, Iran',38.06273998,46.32526875,220,'2026-08-01',NULL,NULL,NULL,NULL,0,NULL),(3,'doctor@gmail.com','ali',NULL,'doctor',NULL,'none','2026-07-23 19:19:04','$2y$10$cseRCBybswwGyndV1Z4s6OqWMRgp5YK2l54SE2MzJgPYnczSIsLKO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,90,'2026-08-03',NULL,NULL,NULL,NULL,0,NULL),(4,'09990999','akbar nami',NULL,'doctor',NULL,'none','2026-07-23 19:21:54','$2y$10$GqI3ekTbgb9F8IiomfpCmec32eUnAJaSTwIsfTuJacVzTL7UJGcWu',NULL,NULL,'nami.akbar@gmail.com',NULL,NULL,'',NULL,NULL,0,'2026-07-23',NULL,NULL,NULL,NULL,0,NULL),(5,'09000000001','test user',NULL,'organization',NULL,'none','2026-07-25 01:33:28','$2y$10$y.vkUVuQAmW5rZZFDfgvcOLUto/d//fRDeApmu/zL0u1BVhqPATUK',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,130,'2026-09-11',NULL,NULL,NULL,NULL,0,NULL),(6,'09194360331','کاربر تستی OAuth',NULL,'admin',NULL,'none','2026-07-25 01:57:34',NULL,NULL,NULL,'',NULL,NULL,'',NULL,NULL,90,'2026-07-25','mock_123456',NULL,NULL,NULL,0,NULL),(7,'user.user@gmail.com','آیهان تستی دیجی‌کالا',NULL,'user',NULL,'none','2026-07-25 12:07:59','$2y$10$5wgtg5LAG.faU2Og.BmO8ed.Xu8G22XAOjTEWji3ZWokIMhjN6pyy',NULL,NULL,'ayhan.enterprise@test.com','تبریز','5138612345','خیابان ولیعصر، برج تجارت، طبقه ۴',38.07000000,46.30000000,160,'2026-09-07',NULL,NULL,'0012345678',NULL,0,NULL),(8,'user.admin@gmail.com','user.admin',NULL,'admin',NULL,'none','2026-07-25 12:59:15','$2y$10$eOfr0iUrovwG7ALhRvEijeLquk3DZ9RxP64GhHFPDWD8I1zZlqryy',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,70,'2026-07-25',NULL,NULL,NULL,NULL,0,NULL),(9,'cataloguser','Catalog User',NULL,'user',NULL,'none','2026-08-01 18:17:17','$2y$10$A1x/tZQKwG29pTij62yKhermzbVR0XpvsuZ.Zjm5bWHbd3PcfEFKC',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,70,'2026-08-01',NULL,NULL,NULL,NULL,0,NULL),(10,'user.doctor','doctor',NULL,'doctor',NULL,'none','2026-08-03 08:24:18','$2y$10$CxgspghNotQUWswEVjnZnegT4UdZ.X26hkw0/5KO8FAwtN0lTrGXe',NULL,NULL,'',NULL,NULL,'',NULL,NULL,70,'2026-08-03',NULL,NULL,NULL,NULL,0,NULL),(11,'user.more','user1',NULL,'user',NULL,'none','2026-08-03 11:52:32','$2y$10$k5PC7Jh7bgb/C99hRg5eHOD1kRAfqFctGHiJaSoLzF/UOrM2u/.ae',NULL,NULL,NULL,'تبریز','1234567890','پردیس ۲, Baghmisheh, Tabriz, بخش مرکزی شهرستان تبریز, Tabriz County, East Azerbaijan Province, 51584-46719, Iran',38.06741958,46.38874054,170,'2026-08-03',NULL,NULL,NULL,NULL,0,NULL),(12,'09144046728','ayxan mehrzad',NULL,'user',NULL,'none','2026-08-18 12:58:29','$2y$10$Ges1WajBHR5DqyKcJUhoH.LAvyrBk/ZPof7Mv18vK3qrdMbd4caIe',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,70,'2026-08-18',NULL,NULL,NULL,NULL,0,NULL),(13,'09146676978','ayhan',NULL,'admin',NULL,'none','2026-08-24 19:20:39','$2y$10$vX.FbOnuI2eP8BVVR./a1.oxGb.d4O.oywOLigJKuKLL/EkYvn25e',NULL,NULL,NULL,'تبریز','1234567890','امیرالمومنین, World trade, Vali asr, ولیعصر, Valiasr, Tabriz, بخش مرکزی شهرستان تبریز, Tabriz County, East Azerbaijan Province, 51578-48778, Iran',38.06606810,46.36505127,120,'2026-08-24',NULL,NULL,NULL,NULL,0,NULL),(14,'09129998877','تستر آسنا',NULL,'seller',NULL,'none','2026-09-05 11:58:06','$2y$10$SO/7sE8h.DzSOKmPG7ZFluFm9zqGPfmamFCHI.oR5TFNsYlye.MP6',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,20,'2026-09-07',NULL,NULL,NULL,NULL,0,NULL),(16,'09121234567','مهدی حسینی',NULL,'user',NULL,'none','2026-09-07 15:22:28','$2y$10$xq8AF0bzrW4.aizVo.L6MeZ./stKh1unGEHWm7Vi9IgybWWOzeaKu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,20,'2026-09-07',NULL,NULL,NULL,NULL,0,NULL),(18,'09121112233','مهندس رضا کریمی',NULL,'organization',NULL,'approved','2026-09-07 16:57:14','$2y$10$ucmz74L14E31oFTIe3Ja1u.hZvicFhDRGwrrKDUgQAyVG8fHwQ2ly',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,NULL),(23,'09128889900','دکتر رامین مهدوی (داروخانه رازی)',NULL,'pharmacist',NULL,'approved','2026-09-07 17:44:43','$2y$10$4a2gZbi82fZ.B7T8mVHXYOdQd12I9QJaDT1uyljKGf56EzOYMR2W6',NULL,NULL,'mahdavi.pharma@gmail.com',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,NULL),(24,'09122223344','دکتر هما مهرزاد',NULL,'doctor',NULL,'approved','2026-09-07 17:45:54','$2y$10$GxvDru2dms6P2diGg9qNnOvl9TiGGHkFju0glBpeyswsz0qMHvTmq',NULL,NULL,'homa.mehrzad@gmail.com',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,NULL),(35,'09000000000','کاربر ناشناس (حساب حذف‌شده)',NULL,'user',NULL,'approved','2026-09-10 17:28:37','ANONYMIZED_PLACEHOLDER',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `type` enum('deposit','withdrawal','refund','cashback','purchase') NOT NULL,
  `description` varchar(500) NOT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wallet_tx` (`wallet_id`),
  CONSTRAINT `fk_tx_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `user_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,6,31,'2026-07-25 01:58:06'),(2,6,2,'2026-07-25 02:31:06'),(3,7,31,'2026-07-25 12:08:41');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-11  7:47:34
