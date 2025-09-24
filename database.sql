-- SQL Schema for the SaaS Project
-- This script creates the necessary tables, relationships, and initial data.

-- SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
-- SET time_zone = "+00:00";

--
-- Table structure for table `subscriptions`
-- جدول الاشتراكات: يخزن تفاصيل خطط الاشتراك المختلفة.
--
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_limit` int(11) NOT NULL,
  `video_limit` int(11) NOT NULL,
  `resolution` varchar(50) NOT NULL,
  `device_limit` int(11) NOT NULL,
  `has_ads` tinyint(1) NOT NULL DEFAULT 1,
  `paypal_plan_id` varchar(255) DEFAULT NULL,
  `duration_months` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `subscriptions`
-- بيانات أولية لجدول الاشتراكات: إضافة الخطط الافتراضية.
--
INSERT INTO `subscriptions` (`id`, `name`, `price`, `image_limit`, `video_limit`, `resolution`, `device_limit`, `has_ads`, `paypal_plan_id`, `duration_months`) VALUES
(1, 'Free', 0.00, 20, 5, '2K', 1, 1, NULL, NULL),
(2, 'Monthly Basic', 5.00, 1000, 250, '4K', 3, 0, 'P-6ML527490D2009848NAFY5WA', 1),
(3, 'Yearly Basic', 50.00, 1000, 250, '4K', 3, 0, 'P-6V5326030C814122HNAFZAFI', 12),
(4, 'Premium Annual', 100.00, 5000, 1000, '8K', 10, 0, 'P-5VS57764X8846254FNCLRMMA', 12);

--
-- Table structure for table `users`
-- جدول المستخدمين: يخزن معلومات المستخدمين الأساسية وتفاصيل اشتراكاتهم.
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `subscription_id` int(11) DEFAULT 1,
  `subscription_end_date` datetime DEFAULT NULL,
  `device_fingerprint` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
-- بيانات أولية لجدول المستخدمين: إضافة حساب المدير الافتراضي.
-- The password is 'Admin@1979#'
--
INSERT INTO `users` (`id`, `email`, `password`, `subscription_id`, `subscription_end_date`, `is_admin`) VALUES
(1, 'ads@4dads.pro', '$2y$10$vR.fV10z2i.f8g6A5.b3UuL3w9x7Y.r5t1o3p9s1q7w9z3A5B7C9D', 4, NULL, 1);

--
-- Table structure for table `usage`
-- جدول الاستخدام: يتتبع عدد الصور والفيديوهات التي أنشأها كل مستخدم خلال فترة معينة.
--
CREATE TABLE `usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `image_count` int(11) NOT NULL DEFAULT 0,
  `video_count` int(11) NOT NULL DEFAULT 0,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_month_year` (`user_id`, `month`, `year`),
  CONSTRAINT `usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `password_resets`
-- جدول إعادة تعيين كلمة المرور: يخزن التوكنات المستخدمة في عملية استعادة كلمة المرور.
--
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `offers`
-- جدول العروض: يخزن أكواد الخصم والعروض الترويجية.
--
CREATE TABLE `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `revenue`
-- جدول الإيرادات: يسجل جميع معاملات الدفع التي تتم عبر PayPal.
--
CREATE TABLE `revenue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paypal_transaction_id` varchar(255) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `subscription_id` (`subscription_id`),
  CONSTRAINT `revenue_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `revenue_ibfk_2` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Set AUTO_INCREMENT for tables
ALTER TABLE `subscriptions` AUTO_INCREMENT = 5;
ALTER TABLE `users` AUTO_INCREMENT = 2;
COMMIT;