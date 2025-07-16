-- Invoicing System Database Backup
-- Generated: 2025-07-05 03:40:57


-- Table structure for table `clients`
DROP TABLE IF EXISTS `clients`;
;

-- Data for table `clients`
INSERT INTO `clients` VALUES ('1', 'dragan jovanoski', 'draganjovanoski54@gmail.com', '071372385', 'aco shopov 56\r\n51', '2025-07-04 02:28:21');


-- Table structure for table `company_settings`
DROP TABLE IF EXISTS `company_settings`;
;

-- Data for table `company_settings`
INSERT INTO `company_settings` VALUES ('1', 'ДИНАМИК ДЕВЕЛОПМЕНТ СОЛУТИОНС ДООЕЛ', 'aco shopov 56', '071372385', 'draganjovanoski54@gmail.com', 'https://ddsolutions.com.mk/', '12223123', '12312312412312312', 'uploads/company_logo_1751590074.png', '2025-07-04 02:40:07', '2025-07-04 02:47:54');


-- Table structure for table `invoice_items`
DROP TABLE IF EXISTS `invoice_items`;
;


-- Table structure for table `invoices`
DROP TABLE IF EXISTS `invoices`;
;


-- Table structure for table `offer_items`
DROP TABLE IF EXISTS `offer_items`;
;


-- Table structure for table `offers`
DROP TABLE IF EXISTS `offers`;
;

-- Data for table `offers`
INSERT INTO `offers` VALUES ('1', '1', '2123', '2025-06-30', '2025-07-17', '12312412.00', '0.00', '0.00', '0.00', 'accepted', '2025-07-04 02:33:29');


-- Table structure for table `services`
DROP TABLE IF EXISTS `services`;
;

-- Data for table `services`
INSERT INTO `services` VALUES ('1', 'Web Development', '75.00', 'Custom website development', '2025-07-05 02:56:54');
INSERT INTO `services` VALUES ('2', 'Logo Design', '150.00', 'Professional logo design', '2025-07-05 02:56:54');
INSERT INTO `services` VALUES ('3', 'SEO Optimization', '50.00', 'Search engine optimization', '2025-07-05 02:56:54');
INSERT INTO `services` VALUES ('4', 'Content Writing', '25.00', 'Professional content writing', '2025-07-05 02:56:54');
INSERT INTO `services` VALUES ('5', 'Maintenance', '30.00', 'Website maintenance and updates', '2025-07-05 02:56:54');
INSERT INTO `services` VALUES ('6', 'Web Development', '75.00', 'Custom website development', '2025-07-05 03:12:04');
INSERT INTO `services` VALUES ('7', 'Logo Design', '150.00', 'Professional logo design', '2025-07-05 03:12:04');
INSERT INTO `services` VALUES ('8', 'SEO Optimization', '50.00', 'Search engine optimization', '2025-07-05 03:12:04');
INSERT INTO `services` VALUES ('9', 'Content Writing', '25.00', 'Professional content writing', '2025-07-05 03:12:04');
INSERT INTO `services` VALUES ('10', 'Maintenance', '30.00', 'Website maintenance and updates', '2025-07-05 03:12:04');
INSERT INTO `services` VALUES ('11', 'Web Development', '75.00', 'Custom website development', '2025-07-05 03:14:27');
INSERT INTO `services` VALUES ('12', 'Logo Design', '150.00', 'Professional logo design', '2025-07-05 03:14:27');
INSERT INTO `services` VALUES ('13', 'SEO Optimization', '50.00', 'Search engine optimization', '2025-07-05 03:14:27');
INSERT INTO `services` VALUES ('14', 'Content Writing', '25.00', 'Professional content writing', '2025-07-05 03:14:27');
INSERT INTO `services` VALUES ('15', 'Maintenance', '30.00', 'Website maintenance and updates', '2025-07-05 03:14:27');
INSERT INTO `services` VALUES ('16', 'Web Development', '75.00', 'Custom website development', '2025-07-05 03:16:35');
INSERT INTO `services` VALUES ('17', 'Logo Design', '150.00', 'Professional logo design', '2025-07-05 03:16:35');
INSERT INTO `services` VALUES ('18', 'SEO Optimization', '50.00', 'Search engine optimization', '2025-07-05 03:16:35');
INSERT INTO `services` VALUES ('19', 'Content Writing', '25.00', 'Professional content writing', '2025-07-05 03:16:35');
INSERT INTO `services` VALUES ('20', 'Maintenance', '30.00', 'Website maintenance and updates', '2025-07-05 03:16:35');


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
;

-- Data for table `users`
INSERT INTO `users` VALUES ('3', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yourdomain.com', '2025-07-04 03:29:54');

