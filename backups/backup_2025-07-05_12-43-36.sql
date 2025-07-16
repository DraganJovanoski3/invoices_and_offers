-- Invoicing System Database Backup
-- Generated: 2025-07-05 12:43:36


-- Table structure for table `clients`
DROP TABLE IF EXISTS `clients`;
;

-- Data for table `clients`
INSERT INTO `clients` VALUES ('1', 'dragan jovanoski', 'draganjovanoski54@gmail.com', '071372385', 'aco shopov 56\r\n51', '2025-07-04 02:28:21');


-- Table structure for table `company_settings`
DROP TABLE IF EXISTS `company_settings`;
;

-- Data for table `company_settings`
INSERT INTO `company_settings` VALUES ('1', 'ДИНАМИК ДЕВЕЛОПМЕНТ СОЛУТИОНС ДООЕЛ', 'aco shopov 56', '071372385', 'draganjovanoski54@gmail.com', 'https://ddsolutions.com.mk/', '12223123', '12312312412312312', 'uploads/company_logo_1751682036.png', '2025-07-04 02:40:07', '2025-07-05 04:20:36');


-- Table structure for table `invoice_items`
DROP TABLE IF EXISTS `invoice_items`;
;

-- Data for table `invoice_items`
INSERT INTO `invoice_items` VALUES ('1', '8', 'Website maintenance and updates', '2', '30.00', '0.00', '15', 'Maintenance', '2025-07-05 03:47:25', '60.00');
INSERT INTO `invoice_items` VALUES ('2', '8', 'Search engine optimization', '4', '50.00', '0.00', '8', 'SEO Optimization', '2025-07-05 03:47:25', '200.00');
INSERT INTO `invoice_items` VALUES ('3', '9', 'Website maintenance and updates', '3', '30.00', '0.00', '10', 'Maintenance', '2025-07-05 03:51:04', '90.00');
INSERT INTO `invoice_items` VALUES ('4', '9', 'Search engine optimization', '4', '50.00', '0.00', '8', 'SEO Optimization', '2025-07-05 03:51:04', '200.00');
INSERT INTO `invoice_items` VALUES ('5', '10', 'Custom website development', '2', '75.00', '0.00', '11', 'Web Development', '2025-07-05 12:42:02', '150.00');
INSERT INTO `invoice_items` VALUES ('6', '10', 'Professional logo design', '1', '150.00', '0.00', '2', 'Logo Design', '2025-07-05 12:42:02', '150.00');


-- Table structure for table `invoices`
DROP TABLE IF EXISTS `invoices`;
;

-- Data for table `invoices`
INSERT INTO `invoices` VALUES ('8', '1', '2025-001', '2025-07-05', '2025-08-04', '299.00', '15.00', '39.00', '260.00', 'draft', '2025-07-05 03:47:25', 'asdasdasdasdasd');
INSERT INTO `invoices` VALUES ('9', '1', '2025-002', '2025-07-05', '2025-08-04', '333.50', '15.00', '43.50', '290.00', 'paid', '2025-07-05 03:51:04', 'asdasdasd');
INSERT INTO `invoices` VALUES ('10', '1', '2025-003', '2025-07-05', '2025-08-22', '330.00', '10.00', '30.00', '300.00', 'draft', '2025-07-05 12:42:02', 'izrabotka na web stranica');


-- Table structure for table `offer_items`
DROP TABLE IF EXISTS `offer_items`;
;


-- Table structure for table `offers`
DROP TABLE IF EXISTS `offers`;
;

-- Data for table `offers`
INSERT INTO `offers` VALUES ('1', '1', '2123', '2025-06-30', '2025-07-17', '12312412.00', '0.00', '0.00', '0.00', 'accepted', '2025-07-04 02:33:29', NULL);


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
INSERT INTO `services` VALUES ('21', 'da go eba andrej', '1.00', 'vgaz', '2025-07-05 12:43:13');


-- Table structure for table `users`
DROP TABLE IF EXISTS `users`;
;

-- Data for table `users`
INSERT INTO `users` VALUES ('3', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@yourdomain.com', '2025-07-04 03:29:54');

