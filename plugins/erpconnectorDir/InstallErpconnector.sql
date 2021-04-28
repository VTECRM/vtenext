/*Table structure for table `erp_accounts` */
DROP TABLE IF EXISTS `erp_accounts`;
CREATE TABLE `erp_accounts` (
  `name` varchar(255) default NULL,
  `external_code` varchar(255) default NULL,
  `website` varchar(255) default NULL,
  `phone` varchar(255) default NULL,
  `other_phone` varchar(255) default NULL,
  `fax` varchar(255) default NULL,
  `email` varchar(255) default NULL,
  `other_email` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  `bank_details` varchar(255) default NULL,
  `vat_registration_number` varchar(255) default NULL,
  `social_security_number` varchar(255) default NULL,
  `bill_address` varchar(255) default NULL,
  `bill_zip_code` varchar(255) default NULL,
  `bill_city` varchar(255) default NULL,
  `bill_province` varchar(255) default NULL,
  `bill_nation` varchar(255) default NULL,
  `ship_address` varchar(255) default NULL,
  `ship_zip_code` varchar(255) default NULL,
  `ship_city` varchar(255) default NULL,
  `ship_province` varchar(255) default NULL,
  `ship_nation` varchar(255) default NULL,
  `user_external_code` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_contacts` */
DROP TABLE IF EXISTS `erp_contacts`;
CREATE TABLE `erp_contacts` (
  `first_name` varchar(255) default NULL,
  `last_name` varchar(255) default NULL,
  `external_code` varchar(255) default NULL,
  `account_external_code` varchar(255) default NULL,
  `phone` varchar(255) default NULL,
  `mobile` varchar(255) default NULL,
  `home_phone` varchar(255) default NULL,
  `other_phone` varchar(255) default NULL,
  `title` varchar(255) default NULL,
  `fax` varchar(255) default NULL,
  `email` varchar(255) default NULL,
  `birthday` varchar(255) default NULL,
  `address` varchar(255) default NULL,
  `zip_code` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `province` varchar(255) default NULL,
  `nation` varchar(255) default NULL,
  `other_address` varchar(255) default NULL,
  `other_zip_code` varchar(255) default NULL,
  `other_city` varchar(255) default NULL,
  `other_province` varchar(255) default NULL,
  `other_nation` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_invoice` */
DROP TABLE IF EXISTS `erp_invoice`;
CREATE TABLE `erp_invoice` (
  `name` varchar(255) default NULL,
  `external_code` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  `date` varchar(255) default NULL,
  `due_date` varchar(255) default NULL,
  `salesorder_external_code` varchar(255) default NULL,
  `contact_external_code` varchar(255) default NULL,
  `account_external_code` varchar(255) default NULL,
  `currency` varchar(255) default NULL,
  `bill_address` varchar(255) default NULL,
  `bill_zip_code` varchar(255) default NULL,
  `bill_city` varchar(255) default NULL,
  `bill_province` varchar(255) default NULL,
  `bill_nation` varchar(255) default NULL,
  `ship_address` varchar(255) default NULL,
  `ship_zip_code` varchar(255) default NULL,
  `ship_city` varchar(255) default NULL,
  `ship_province` varchar(255) default NULL,
  `ship_nation` varchar(255) default NULL,
  `terms_conditions` varchar(255) default NULL,
  `description` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_invoice_row` */
DROP TABLE IF EXISTS `erp_invoice_row`;
CREATE TABLE `erp_invoice_row` (
  `external_code` varchar(255) default NULL,
  `product_external_code` varchar(255) default NULL,
  `sequence_no` varchar(255) default NULL,
  `quantity` varchar(255) default NULL,
  `price` varchar(255) default NULL,
  `comment` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_log_script_content` */
DROP TABLE IF EXISTS `erp_log_script_content`;
CREATE TABLE `erp_log_script_content` (
  `id` int(19) NOT NULL,
  `type` varchar(255) default NULL,
  `date_start` timestamp NOT NULL default '0000-00-00 00:00:00',
  `date_end` timestamp NOT NULL default '0000-00-00 00:00:00',
  `records_created` int(19) default NULL,
  `records_updated` int(19) default NULL,
  `records_deleted` int(19) default NULL,
  `total_records` int(19) default NULL,
  `duration` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `type_idx` (`type`),
  KEY `date_start_idx` (`date_start`),
  KEY `date_end_idx` (`date_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_log_script_state` */
DROP TABLE IF EXISTS `erp_log_script_state`;
CREATE TABLE `erp_log_script_state` (
  `type` varchar(255) default NULL,
  `state` int(1) default '0',
  `working_id` int(19) default NULL,
  `enabled` int(1) default '1',
  `lastrun` timestamp NOT NULL default '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `erp_products` */
DROP TABLE IF EXISTS `erp_products`;
CREATE TABLE `erp_products` (
  `name` varchar(255) default NULL,
  `external_code` varchar(255) default NULL,
  `code` varchar(255) default NULL,
  `category` varchar(255) default NULL,
  `start_date` varchar(255) default NULL,
  `expiry_date` varchar(255) default NULL,
  `sales_start_date` varchar(255) default NULL,
  `sales_end_date` varchar(255) default NULL,
  `website` varchar(255) default NULL,
  `serial_no` varchar(255) default NULL,
  `glacct` varchar(255) default NULL,
  `price` varchar(255) default NULL,
  `description` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_quotes` */
DROP TABLE IF EXISTS `erp_quotes`;
CREATE TABLE `erp_quotes` (
  `name` varchar(255) default NULL,
  `external_code` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  `valid_till` varchar(255) default NULL,
  `contact_external_code` varchar(255) default NULL,
  `account_external_code` varchar(255) default NULL,
  `currency` varchar(255) default NULL,
  `shipping` varchar(255) default NULL,
  `bill_address` varchar(255) default NULL,
  `bill_zip_code` varchar(255) default NULL,
  `bill_city` varchar(255) default NULL,
  `bill_province` varchar(255) default NULL,
  `bill_nation` varchar(255) default NULL,
  `ship_address` varchar(255) default NULL,
  `ship_zip_code` varchar(255) default NULL,
  `ship_city` varchar(255) default NULL,
  `ship_province` varchar(255) default NULL,
  `ship_nation` varchar(255) default NULL,
  `terms_conditions` varchar(255) default NULL,
  `description` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `erp_quotes_row` */
DROP TABLE IF EXISTS `erp_quotes_row`;
CREATE TABLE `erp_quotes_row` (
  `external_code` varchar(255) default NULL,
  `product_external_code` varchar(255) default NULL,
  `sequence_no` varchar(255) default NULL,
  `quantity` varchar(255) default NULL,
  `price` varchar(255) default NULL,
  `comment` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_salesorder` */
DROP TABLE IF EXISTS `erp_salesorder`;
CREATE TABLE `erp_salesorder` (
  `name` varchar(255) default NULL,
  `external_code` varchar(255) default NULL,
  `status` varchar(255) default NULL,
  `due_date` varchar(255) default NULL,
  `quote_external_code` varchar(255) default NULL,
  `contact_external_code` varchar(255) default NULL,
  `account_external_code` varchar(255) default NULL,
  `currency` varchar(255) default NULL,
  `bill_address` varchar(255) default NULL,
  `bill_zip_code` varchar(255) default NULL,
  `bill_city` varchar(255) default NULL,
  `bill_province` varchar(255) default NULL,
  `bill_nation` varchar(255) default NULL,
  `ship_address` varchar(255) default NULL,
  `ship_zip_code` varchar(255) default NULL,
  `ship_city` varchar(255) default NULL,
  `ship_province` varchar(255) default NULL,
  `ship_nation` varchar(255) default NULL,
  `terms_conditions` varchar(255) default NULL,
  `description` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `erp_salesorder_row` */
DROP TABLE IF EXISTS `erp_salesorder_row`;
CREATE TABLE `erp_salesorder_row` (
  `external_code` varchar(255) default NULL,
  `product_external_code` varchar(255) default NULL,
  `sequence_no` varchar(255) default NULL,
  `quantity` varchar(255) default NULL,
  `price` varchar(255) default NULL,
  `comment` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*Table structure for table `erp_users` */
DROP TABLE IF EXISTS `erp_users`;
CREATE TABLE `erp_users` (
  `user_name` varchar(255) default NULL,
  `external_code` varchar(255) default NULL,
  `first_name` varchar(255) default NULL,
  `last_name` varchar(255) default NULL,
  `email` varchar(255) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;