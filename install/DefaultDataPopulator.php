<?php

/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

include_once('config.php');
require_once('include/logging.php');
require_once('include/utils/utils.php');

/** Class to populate the default required data during installation
 */

class DefaultDataPopulator {

	function __construct() {
		$this->log = LoggerManager::getLogger('DefaultDataPopulator');
		$this->db = PearDatabase::getInstance();
	}

	var $new_schema = true;

	/** Function to populate the default required data during installation
 	*/
	function create_tables () {
		global $table_prefix;
		global $app_strings;

		$modules = array(//crmv@208472
			array(2,	'Potentials',	0,7,	'Potentials',	0,0,1),
			array(3,	'Home',			0,1,	'Home',			0,1,0),
			array(4,	'Contacts',		0,6,	'Contacts',		0,0,1),
			array(6,	'Accounts',		0,5,	'Accounts',		0,0,1),		
			array(7,	'Leads',		0,4,	'Leads',		0,0,1),
			array(8,	'Documents',	0,9,	'Documents',	0,0,1),
			array(9,	'Calendar',		0,3,	'Calendar',		0,0,1),
			array(10,	'Emails',		0,10,	'Emails',		0,1,1),
			array(13,	'HelpDesk',		0,11,	'HelpDesk',		0,0,1),
			array(14,	'Products',		0,8,	'Products',		0,0,1), // crmv@109663
			array(15,	'Faq',			0,14,	'Faq',			0,1,1),
			array(16,	'Events',		2,13,	'Events',		0,0,1),
			array(18,	'Vendors',		0,15,	'Vendors',		0,1,1),
			array(19,	'PriceBooks',	0,16,	'PriceBooks',	0,1,1),
			array(20,	'Quotes',		0,17,	'Quotes',		0,0,1),
			array(21,	'PurchaseOrder',0,18,	'PurchaseOrder',0,0,1),
			array(22,	'SalesOrder',	0,19,	'SalesOrder',	0,0,1),
			array(23,	'Invoice',		0,20,	'Invoice',		0,0,1),
			array(24,	'Rss',			0,21,	'Rss',			0,1,0),
			array(25,	'Reports',		0,22,	'Reports',		0,1,0),
			array(26,	'Campaigns',	0,23,	'Campaigns',	0,0,1),
			array(27,	'Portal',		0,24,	'Portal',		0,1,0),
			array(29,	'Users',		0,26,	'Users',		0,1,0),
		);
		$tabcolumns = array('tabid', 'name', 'presence', 'tabsequence', 'tablabel', 'customized', 'ownedby', 'isentitytype');
		$this->db->bulkInsert($table_prefix.'_tab', $tabcolumns, $modules);

		// set properties for modules
		// crmv@64542
		$this->db->query("INSERT INTO {$table_prefix}_tab_info (tabid,prefname,prefvalue) VALUES (20, 'is_inventory', '1')");
		$this->db->query("INSERT INTO {$table_prefix}_tab_info (tabid,prefname,prefvalue) VALUES (21, 'is_inventory', '1')");
		$this->db->query("INSERT INTO {$table_prefix}_tab_info (tabid,prefname,prefvalue) VALUES (22, 'is_inventory', '1')");
		$this->db->query("INSERT INTO {$table_prefix}_tab_info (tabid,prefname,prefvalue) VALUES (23, 'is_inventory', '1')");
		
		$this->db->query("INSERT INTO {$table_prefix}_tab_info (tabid,prefname,prefvalue) VALUES (14, 'is_product', '1')");
		// crmv@64542e

		// crmv@104568

		$panels = array(
			array(0,2,'LBL_TAB_MAIN',1),
			array(0,4,'LBL_TAB_MAIN',1),
			array(0,6,'LBL_TAB_MAIN',1),
			array(0,7,'LBL_TAB_MAIN',1),
			array(0,8,'LBL_TAB_MAIN',1),
			array(0,9,'LBL_TAB_MAIN',1),
			array(0,10,'LBL_TAB_MAIN',1),
			array(0,13,'LBL_TAB_MAIN',1),
			array(0,14,'LBL_TAB_MAIN',1),
			array(0,15,'LBL_TAB_MAIN',1),
			array(0,16,'LBL_TAB_MAIN',1),
			array(0,18,'LBL_TAB_MAIN',1),
			array(0,19,'LBL_TAB_MAIN',1),
			array(0,20,'LBL_TAB_MAIN',1),
			array(0,21,'LBL_TAB_MAIN',1),
			array(0,22,'LBL_TAB_MAIN',1),
			array(0,23,'LBL_TAB_MAIN',1),
			array(0,26,'LBL_TAB_MAIN',1),
			array(0,29,'LBL_TAB_MAIN',1),
		);

		$panelmap = array();
		$panelids = $this->db->getMultiUniqueID($table_prefix.'_panels', count($panels));
		// set the ids
		foreach ($panels as $idx => $panel) {
			$panels[$idx][0] = $panelids[$idx];
			$panelmap[$panel[1]] = $panelids[$idx];
		}
		$panelcolumns = array('panelid', 'tabid', 'panellabel', 'sequence');
		$this->db->bulkInsert($table_prefix.'_panels', $panelcolumns, $panels);


		// Populate the blocks table (use bulk insert, it's 10x times faster than single inserts)
		// Do not change the order of these blocks, because fields later use hardcoded blockids in some cases!!
		$blocks = array(
			array(0,2,'LBL_OPPORTUNITY_INFORMATION',1,0,0,0),
			array(0,2,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,2,'LBL_DESCRIPTION_INFORMATION',3,0,0,0),
			array(0,4,'LBL_CONTACT_INFORMATION',1,0,0,0),
			array(0,4,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,4,'LBL_CUSTOMER_PORTAL_INFORMATION',3,0,0,0),
			array(0,4,'LBL_ADDRESS_INFORMATION',4,0,0,0),
			array(0,4,'LBL_DESCRIPTION_INFORMATION',5,0,0,0),
			array(0,6,'LBL_ACCOUNT_INFORMATION',1,0,0,0),
			array(0,6,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,6,'LBL_ADDRESS_INFORMATION',3,0,0,0),
			array(0,6,'LBL_DESCRIPTION_INFORMATION',4,0,0,0),
			array(0,7,'LBL_LEAD_INFORMATION',1,0,0,0),
			array(0,7,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,7,'LBL_ADDRESS_INFORMATION',3,0,0,0),
			array(0,7,'LBL_DESCRIPTION_INFORMATION',4,0,0,0),
			array(0,8,'LBL_NOTE_INFORMATION',1,0,0,0),
			array(0,8,'LBL_FILE_INFORMATION',3,1,0,0),
			array(0,9,'LBL_TASK_INFORMATION',1,0,0,0),
			array(0,9,'',2,1,0,0),
			array(0,10,'LBL_EMAIL_INFORMATION',1,0,0,0),
			array(0,10,'',2,1,0,0),
			array(0,10,'',3,1,0,0),
			array(0,10,'',4,1,0,0),
			array(0,13,'LBL_TICKET_INFORMATION',1,0,0,0),
			array(0,13,'',2,1,0,0),
			array(0,13,'LBL_CUSTOM_INFORMATION',3,0,0,0),
			array(0,13,'LBL_DESCRIPTION_INFORMATION',4,0,0,0),
			array(0,13,'LBL_TICKET_RESOLUTION',5,0,0,1),
			array(0,13,'LBL_COMMENTS',6,0,0,1),
			// crmv@198024
			array(0,14,'LBL_PRODUCT_INFORMATION',1,0,0,0),
			array(0,14,'LBL_PRICING_INFORMATION',3,0,0,0),
			array(0,14,'LBL_STOCK_INFORMATION',4,0,0,0),
			array(0,14,'LBL_CUSTOM_INFORMATION',5,0,0,0),
			array(0,14,'LBL_IMAGE_INFORMATION',6,0,0,0),
			array(0,14,'LBL_DESCRIPTION_INFORMATION',7,0,0,0),
			// crmv@198024e
			array(0,15,'LBL_FAQ_INFORMATION',1,0,0,0),
			array(0,15,'LBL_COMMENT_INFORMATION',4,0,0,1),
			array(0,16,'LBL_EVENT_INFORMATION',1,0,0,0),
			array(0,16,'',2,1,0,0),
			array(0,16,'',3,1,0,0),
			array(0,18,'LBL_VENDOR_INFORMATION',1,0,0,0),
			array(0,18,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,18,'LBL_VENDOR_ADDRESS_INFORMATION',3,0,0,0),
			array(0,18,'LBL_DESCRIPTION_INFORMATION',4,0,0,0),
			array(0,19,'LBL_PRICEBOOK_INFORMATION',1,0,0,0),
			array(0,19,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,19,'LBL_DESCRIPTION_INFORMATION',3,0,0,0),
			array(0,20,'LBL_QUOTE_INFORMATION',1,0,0,0),
			array(0,20,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,20,'LBL_ADDRESS_INFORMATION',3,0,0,0),
			array(0,20,'LBL_RELATED_PRODUCTS',4,0,0,0),
			array(0,20,'LBL_TERMS_INFORMATION',5,0,0,0),
			array(0,20,'LBL_DESCRIPTION_INFORMATION',6,0,0,0),
			array(0,21,'LBL_PO_INFORMATION',1,0,0,0),
			array(0,21,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,21,'LBL_ADDRESS_INFORMATION',3,0,0,0),
			array(0,21,'LBL_RELATED_PRODUCTS',4,0,0,0),
			array(0,21,'LBL_TERMS_INFORMATION',5,0,0,0),
			array(0,21,'LBL_DESCRIPTION_INFORMATION',6,0,0,0),
			array(0,22,'LBL_SO_INFORMATION',1,0,0,0),
			array(0,22,'LBL_CUSTOM_INFORMATION',3,0,0,0),
			array(0,22,'LBL_ADDRESS_INFORMATION',4,0,0,0),
			array(0,22,'LBL_RELATED_PRODUCTS',5,0,0,0),
			array(0,22,'LBL_TERMS_INFORMATION',6,0,0,0),
			array(0,22,'LBL_DESCRIPTION_INFORMATION',7,0,0,0),
			array(0,23,'LBL_INVOICE_INFORMATION',1,0,0,0),
			array(0,23,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,23,'LBL_ADDRESS_INFORMATION',3,0,0,0),
			array(0,23,'LBL_RELATED_PRODUCTS',4,0,0,0),
			array(0,23,'LBL_TERMS_INFORMATION',5,0,0,0),
			array(0,23,'LBL_DESCRIPTION_INFORMATION',6,0,0,0),
			array(0,4,'LBL_IMAGE_INFORMATION',6,0,0,0),
			array(0,26,'LBL_CAMPAIGN_INFORMATION',1,0,0,0),
			array(0,26,'LBL_CUSTOM_INFORMATION',2,0,0,0),
			array(0,26,'LBL_EXPECTATIONS_AND_ACTUALS',3,0,0,0),
			array(0,29,'LBL_USERLOGIN_ROLE',1,0,0,0),
			array(0,29,'LBL_MORE_INFORMATION',2,0,0,0),
			array(0,29,'LBL_ADDRESS_INFORMATION',3,0,0,0),
			array(0,26,'LBL_DESCRIPTION_INFORMATION',4,0,0,0),
			array(0,29,'LBL_USER_IMAGE_INFORMATION',4,0,0,0),
			array(0,29,'LBL_USER_ADV_OPTIONS',8,0,0,0),
			array(0,8,'LBL_DESCRIPTION',2,0,0,0),
			array(0,22,'Recurring Invoice Information',2,0,0,0),
			array(0,9,'LBL_CUSTOM_INFORMATION',3,0,0,0),
			array(0,16,'LBL_CUSTOM_INFORMATION',4,0,0,0),
			array(0,29,'LBL_CALENDAR_CONFIGURATION',5,0,0,0),
			array(0,13,'LBL_SIGNATURE_BLOCK',7,0,0,1),
			array(0,14,'LBL_VARIANT_INFORMATION',2,0,0,0), // crmv@198024
		);
		// calculate the ids in advance
		$blockids = $this->db->getMultiUniqueID($table_prefix.'_blocks', count($blocks));
		// set them for the blocks and add the panelid
		foreach ($blocks as $idx => $block) {
			$blocks[$idx][0] = $blockids[$idx];
			$blocks[$idx][] = $panelmap[$block[1]];
		}
		// insert them!
		$blockcolumns = array('blockid', 'tabid', 'blocklabel', 'sequence', 'show_title', 'visible', 'create_view', 'panelid');
		$this->db->bulkInsert($table_prefix.'_blocks', $blockcolumns, $blocks);
		
		// save some blockids for later (variablename, tabid, blocklabel)
		$saveblockids = array(
			array('fileblockid', 8, 'LBL_FILE_INFORMATION'),
			array('vendorbasicinfo', 18, 'LBL_VENDOR_INFORMATION'),
			array('vendoraddressblock', 18, 'LBL_VENDOR_ADDRESS_INFORMATION'),
			array('vendordescriptionblock', 18, 'LBL_DESCRIPTION_INFORMATION'),
			array('pricebookbasicblock', 19, 'LBL_PRICEBOOK_INFORMATION'),
			array('pricebookdescription', 19, 'LBL_DESCRIPTION_INFORMATION'),
			array('quotesbasicblock', 20, 'LBL_QUOTE_INFORMATION'),
			array('quotesaddressblock', 20, 'LBL_ADDRESS_INFORMATION'),
			array('quotetermsblock', 20, 'LBL_TERMS_INFORMATION'),
			array('quotedescription', 20, 'LBL_DESCRIPTION_INFORMATION'),
			array('pobasicblock', 21, 'LBL_PO_INFORMATION'),
			array('poaddressblock', 21, 'LBL_ADDRESS_INFORMATION'),
			array('potermsblock', 21, 'LBL_TERMS_INFORMATION'),
			array('podescription', 21, 'LBL_DESCRIPTION_INFORMATION'),
			array('sobasicblock', 22, 'LBL_SO_INFORMATION'),
			array('soaddressblock', 22, 'LBL_ADDRESS_INFORMATION'),
			array('sotermsblock', 22, 'LBL_TERMS_INFORMATION'),
			array('sodescription', 22, 'LBL_DESCRIPTION_INFORMATION'),
			array('invoicebasicblock', 23, 'LBL_INVOICE_INFORMATION'),
			array('invoiceaddressblock', 23, 'LBL_ADDRESS_INFORMATION'),
			array('invoicetermsblock', 23, 'LBL_TERMS_INFORMATION'),
			array('invoicedescription', 23, 'LBL_DESCRIPTION_INFORMATION'),
			array('imageblockid', 4, 'LBL_IMAGE_INFORMATION'),
			array('campaignbasicblockid', 26, 'LBL_CAMPAIGN_INFORMATION'),
			array('campaigncustomblock', 26, 'LBL_CUSTOM_INFORMATION'),
			array('campaignexpectedandactualsblock', 26, 'LBL_EXPECTATIONS_AND_ACTUALS'),
			array('userloginandroleblockid', 29, 'LBL_USERLOGIN_ROLE'),
			array('usermoreinfoblock', 29, 'LBL_MORE_INFORMATION'),
			array('useraddressblock', 29, 'LBL_ADDRESS_INFORMATION'),
			array('campaidndescriptionblock', 26, 'LBL_DESCRIPTION_INFORMATION'),
			array('userblockid', 29, 'LBL_USER_IMAGE_INFORMATION'),
			array('useradvanceblock', 29, 'LBL_USER_ADV_OPTIONS'),
			array('desc_blockid', 8, 'LBL_DESCRIPTION'),
			array('sorecurringinvoiceblock', 22, 'Recurring Invoice Information'),
			array('calendaruserblock', 29, 'LBL_CALENDAR_CONFIGURATION'),
			array('signature_block', 13, 'LBL_SIGNATURE_BLOCK'),
			array('email_block', 10, 'LBL_EMAIL_INFORMATION'), // crmv@155585
			array('variant_block', 14, 'LBL_VARIANT_INFORMATION'), // crmv@198024
		);
		// populate the variables
		foreach ($saveblockids as $sbinfo) {
			foreach ($blocks as $binfo) {
				if ($binfo[1] == $sbinfo[1] && $binfo[2] == $sbinfo[2]) {
					$varname = $sbinfo[0];
					$$varname = $binfo[0];
					break;
				}
			}
		}
		// crmv@104568e
		

		//Account Details -- START
		//Block9

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'accountname','".$table_prefix."_account',1,'1','accountname','Account Name',1,0,0,100,1,9,1,'V~M',0,1,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'account_no','".$table_prefix."_account',1,'4','account_no','Account No',1,0,0,100,2,9,1,'V~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'phone','".$table_prefix."_account',1,'11','phone','Phone',1,2,0,100,4,9,1,'V~O',2,3,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'website','".$table_prefix."_account',1,'17','website','Website',1,2,0,100,3,9,1,'V~O',2,2,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'fax','".$table_prefix."_account',1,'1013','fax','Fax',1,2,0,100,6,9,1,'V~O',2,4,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'otherphone','".$table_prefix."_account',1,'11','otherphone','Other Phone',1,2,0,100,8,9,1,'V~O',1,null,'BAS',1,'')");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$fieldid.",'parentid','".$table_prefix."_account',1,'10','account_id','Member Of',1,2,0,100,7,9,1,'I~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Accounts', 'Accounts', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'email1','".$table_prefix."_account',1,'13','email1','Email',1,2,0,100,10,9,1,'E~O',2,5,'BAS',1,'')");	//crmv@16265
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'employees','".$table_prefix."_account',1,'7','employees','Employees',1,2,0,100,9,9,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'email2','".$table_prefix."_account',1,'13','email2','Other Email',1,2,0,100,11,9,1,'E~O',1,null,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'ownership','".$table_prefix."_account',1,'1','ownership','Ownership',1,2,0,100,12,9,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'rating','".$table_prefix."_account',1,'15','rating','Rating',1,2,0,100,14,9,1,'V~O',2,7,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'industry','".$table_prefix."_account',1,'15','industry','industry',1,2,0,100,13,9,1,'V~O',2,6,'BAS',1,'')");
		//$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'siccode','".$table_prefix."_account',1,'1','siccode','SIC Code',1,2,0,100,16,9,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'account_type','".$table_prefix."_account',1,'15','accounttype','Type',1,2,0,100,15,9,1,'V~O',2,8,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'annualrevenue','".$table_prefix."_account',1,'71','annual_revenue','Annual Revenue',1,2,0,100,18,9,1,'I~O',1,null,'BAS',1,'')");
		//crmv@16117
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'crmv_bankdetails','".$table_prefix."_account',1,'1','crmv_bankdetails','Bank Details',1,2,0,100,19,9,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'crmv_vat_registration_number','".$table_prefix."_account',1,'1','crmv_vat_registration_number','VAT Registration Number',1,2,0,100,20,9,1,'V~O',2,11,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'crmv_social_security_number','".$table_prefix."_account',1,'1','crmv_social_security_number','Social Security number',1,2,0,100,21,9,1,'V~O',2,10,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'daily_cost','".$table_prefix."_account',1,'71','daily_cost','Daily cost',1,2,0,100,22,9,1,'N~O',1,null,'BAS',0,'')"); // crmv@189362
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'external_code','".$table_prefix."_account',1,'1112','external_code','External Code',1,2,0,100,23,9,1,'V~O',1,null,'BAS',1,'')");
		//Added ".$table_prefix."_field emailoptout for ".$table_prefix."_accounts -- after 4.2 patch2
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'emailoptout','".$table_prefix."_account',1,'56','emailoptout','Email Opt Out',1,2,0,100,17,9,1,'C~O',2,9,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'newsletter_unsubscrpt','".$table_prefix."_account',1,'56','newsletter_unsubscrpt','Receive newsletter',1,2,0,100,24,9,1,'C~O',3,null,'BAS',0,'')");	//crmv@55961	
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,25,9,1,'I~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,27,9,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,26,9,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,28,9,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		//crmv@16117 end
		//Block 11
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'bill_street','".$table_prefix."_accountbillads',1,'21','bill_street','Billing Address',1,2,0,100,1,11,1,'V~O',2,12,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'ship_street','".$table_prefix."_accountshipads',1,'21','ship_street','Shipping Address',1,2,0,100,2,11,1,'V~O',2,13,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'bill_city','".$table_prefix."_accountbillads',1,'1','bill_city','Billing City',1,2,0,100,5,11,1,'V~O',2,14,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'ship_city','".$table_prefix."_accountshipads',1,'1','ship_city','Shipping City',1,2,0,100,6,11,1,'V~O',2,15,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'bill_state','".$table_prefix."_accountbillads',1,'1','bill_state','Billing State',1,2,0,100,7,11,1,'V~O',2,16,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'ship_state','".$table_prefix."_accountshipads',1,'1','ship_state','Shipping State',1,2,0,100,8,11,1,'V~O',2,17,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'bill_code','".$table_prefix."_accountbillads',1,'1','bill_code','Billing Code',1,2,0,100,9,11,1,'V~O',2,18,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'ship_code','".$table_prefix."_accountshipads',1,'1','ship_code','Shipping Code',1,2,0,100,10,11,1,'V~O',2,19,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'bill_country','".$table_prefix."_accountbillads',1,'1','bill_country','Billing Country',1,2,0,100,11,11,1,'V~O',2,20,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'ship_country','".$table_prefix."_accountshipads',1,'1','ship_country','Shipping Country',1,2,0,100,12,11,1,'V~O',2,21,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'bill_pobox','".$table_prefix."_accountbillads',1,'1','bill_pobox','Billing Po Box',1,2,0,100,3,11,1,'V~O',2,22,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'ship_pobox','".$table_prefix."_accountshipads',1,'1','ship_pobox','Shipping Po Box',1,2,0,100,4,11,1,'V~O',2,23,'BAS',1,'')");

		//Block12
		$this->db->query("insert into ".$table_prefix."_field values (6,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_account',1,'19','description','Description',1,2,0,100,1,12,1,'V~O',1,null,'BAS',1,'')");

		//Account Details -- END

		//Lead Details --- START
		//Block13 -- Start

		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'salutation','".$table_prefix."_leaddetails',1,'55','salutationtype','Salutation',1,0,0,100,1,13,3,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'firstname','".$table_prefix."_leaddetails',1,'55','firstname','First Name',1,2,0,100,2,13,1,'V~O',2,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'lead_no','".$table_prefix."_leaddetails',1,'4','lead_no','Lead No',1,0,0,100,3,13,1,'V~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'phone','".$table_prefix."_leadaddress',1,'11','phone','Phone',1,2,0,100,5,13,1,'V~O',2,3,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'lastname','".$table_prefix."_leaddetails',1,'255','lastname','Last Name',1,0,0,100,4,13,1,'V~M',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'mobile','".$table_prefix."_leadaddress',1,'1014','mobile','Mobile',1,2,0,100,7,13,1,'V~O',2,5,'BAS',1,'')");	
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'company','".$table_prefix."_leaddetails',1,'1','company','Company',1,2,0,100,6,13,1,'V~M',2,4,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'fax','".$table_prefix."_leadaddress',1,'1013','fax','Fax',1,2,0,100,9,13,1,'V~O',2,7,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'designation','".$table_prefix."_leaddetails',1,'1','designation','Designation',1,2,0,100,8,13,1,'V~O',2,6,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'email','".$table_prefix."_leaddetails',1,'13','email','Email',1,2,0,100,11,13,1,'E~O',2,9,'BAS',1,'')");	//crmv@16265
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'leadsource','".$table_prefix."_leaddetails',1,'15','leadsource','Lead Source',1,2,0,100,10,13,1,'V~O',2,8,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'website','".$table_prefix."_leadsubdetails',1,'17','website','Website',1,2,0,100,13,13,1,'V~O',2,11,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'industry','".$table_prefix."_leaddetails',1,'15','industry','Industry',1,2,0,100,12,13,1,'V~O',2,10,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'leadstatus','".$table_prefix."_leaddetails',1,'15','leadstatus','Lead Status',1,2,0,100,15,13,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'annualrevenue','".$table_prefix."_leaddetails',1,'71','annualrevenue','Annual Revenue',1,2,0,100,14,13,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'rating','".$table_prefix."_leaddetails',1,'15','rating','Rating',1,2,0,100,17,13,1,'V~O',2,12,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'noofemployees','".$table_prefix."_leaddetails',1,'1','noofemployees','No Of Employees',1,2,0,100,16,13,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'yahooid','".$table_prefix."_leaddetails',1,'13','yahooid','Yahoo Id',100,2,0,100,18,13,1,'E~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'newsletter_unsubscrpt','".$table_prefix."_leaddetails',1,'56','newsletter_unsubscrpt','Receive newsletter',1,2,0,100,19,13,1,'C~O',3,null,'BAS',0,'')");	//crmv@55961
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,20,13,1,'I~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,22,13,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,21,13,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,23,13,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		//Block13 -- End

		//Block15 -- Start
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'lane','".$table_prefix."_leadaddress',1,'21','lane','Street',1,2,0,100,1,15,1,'V~O',2,13,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'code','".$table_prefix."_leadaddress',1,'1','code','Postal Code',1,2,0,100,3,15,1,'V~O',2,15,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'city','".$table_prefix."_leadaddress',1,'1','city','City',1,2,0,100,4,15,1,'V~O',2,16,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'country','".$table_prefix."_leadaddress',1,'1','country','Country',1,2,0,100,5,15,1,'V~O',2,18,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'state','".$table_prefix."_leadaddress',1,'1','state','State',1,2,0,100,6,15,1,'V~O',2,17,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'pobox','".$table_prefix."_leadaddress',1,'1','pobox','Po Box',1,2,0,100,2,15,1,'V~O',2,14,'BAS',1,'')");
		//Block15 --End

		//Block16 -- Start
		$this->db->query("insert into ".$table_prefix."_field values (7,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_leaddetails',1,'19','description','Description',1,2,0,100,1,16,1,'V~O',1,null,'BAS',1,'')");
		//Block16 -- End

		//Lead Details -- END

		//Contact Details -- START
		//Block4 -- Start

		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'salutation','".$table_prefix."_contactdetails',1,'55','salutationtype','Salutation',1,0,0,100,1,4,3,'V~O',2,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'firstname','".$table_prefix."_contactdetails',1,'55','firstname','First Name',1,2,0,100,2,4,1,'V~O',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'contact_no','".$table_prefix."_contactdetails',1,'4','contact_no','Contact Id',1,0,0,100,3,4,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'phone','".$table_prefix."_contactdetails',1,'11','phone','Office Phone',1,2,0,100,5,4,1,'V~O',2,4,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'lastname','".$table_prefix."_contactdetails',1,'255','lastname','Last Name',1,0,0,100,4,4,1,'V~M',2,3,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'mobile','".$table_prefix."_contactdetails',1,'1014','mobile','Mobile',1,2,0,100,7,4,1,'V~O',2,5,'BAS',1,'')");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$fieldid.",'accountid','".$table_prefix."_contactdetails',1,'10','account_id','Account Name',1,0,0,100,6,4,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Contacts', 'Accounts', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'homephone','".$table_prefix."_contactsubdetails',1,'11','homephone','Home Phone',1,2,0,100,9,4,1,'V~O',2,7,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'leadsource','".$table_prefix."_contactsubdetails',1,'15','leadsource','Lead Source',1,2,0,100,8,4,1,'V~O',2,6,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'otherphone','".$table_prefix."_contactsubdetails',1,'11','otherphone','Other Phone',1,2,0,100,11,4,1,'V~O',2,8,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'title','".$table_prefix."_contactdetails',1,'1','title','Title',1,2,0,100,10,4,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'fax','".$table_prefix."_contactdetails',1,'1013','fax','Fax',1,2,0,100,13,4,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'department','".$table_prefix."_contactdetails',1,'1','department','Department',1,2,0,100,12,4,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'birthday','".$table_prefix."_contactsubdetails',1,'5','birthday','Birthdate',1,2,0,100,16,4,1,'D~O',2,10,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'email','".$table_prefix."_contactdetails',1,'13','email','Email',1,2,0,100,15,4,1,'E~O',2,9,'BAS',1,'')");	//crmv@16265
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$fieldid.",'reportsto','".$table_prefix."_contactdetails',1,'10','contact_id','Reports To',1,2,0,100,18,4,1,'I~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Contacts', 'Contacts', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'assistant','".$table_prefix."_contactsubdetails',1,'1','assistant','Assistant',1,2,0,100,17,4,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'yahooid','".$table_prefix."_contactdetails',1,'13','yahooid','Yahoo Id',100,2,0,100,20,4,1,'E~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'assistantphone','".$table_prefix."_contactsubdetails',1,'11','assistantphone','Assistant Phone',1,2,0,100,19,4,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'donotcall','".$table_prefix."_contactdetails',1,'56','donotcall','Do Not Call',1,2,0,100,22,4,1,'C~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'emailoptout','".$table_prefix."_contactdetails',1,'56','emailoptout','Email Opt Out',1,2,0,100,21,4,1,'C~O',2,11,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,26,4,1,'I~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'reference','".$table_prefix."_contactdetails',1,'56','reference','Reference',1,2,0,10,23,4,1,'C~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,27,4,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,28,4,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,29,4,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		//claudio - start
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$fieldid.",'vendor_id','".$table_prefix."_contactdetails',1,'10','vendor_id','Vendor Name',1,2,0,100,24,4,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Contacts', 'Vendors', NULL, NULL)");
		//claudio - end
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'newsletter_unsubscrpt','".$table_prefix."_contactdetails',1,'56','newsletter_unsubscrpt','Receive newsletter',1,2,0,100,25,4,1,'C~O',3,null,'BAS',0,'')");	//crmv@55961

		//Block4 -- End

		//Block6 - Begin Customer Portal

		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'portal','".$table_prefix."_customerdetails',1,'56','portal','Portal User',1,2,0,100,1,6,1,'C~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'support_start_date','".$table_prefix."_customerdetails',1,'5','support_start_date','Support Start Date',1,2,0,100,2,6,1,'D~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'support_end_date','".$table_prefix."_customerdetails',1,'5','support_end_date','Support End Date',1,2,0,100,3,6,1,'D~O~OTH~GE~support_start_date~Support Start Date',1,null,'BAS',1,'')");

		//Block6 - End Customer Portal

		//Block 7 -- Start

		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'mailingstreet','".$table_prefix."_contactaddress',1,'21','mailingstreet','Mailing Street',1,2,0,100,1,7,1,'V~O',2,12,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'otherstreet','".$table_prefix."_contactaddress',1,'21','otherstreet','Other Street',1,2,0,100,2,7,1,'V~O',2,13,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'mailingcity','".$table_prefix."_contactaddress',1,'1','mailingcity','Mailing City',1,2,0,100,5,7,1,'V~O',2,16,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'othercity','".$table_prefix."_contactaddress',1,'1','othercity','Other City',1,2,0,100,6,7,1,'V~O',2,17,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'mailingstate','".$table_prefix."_contactaddress',1,'1','mailingstate','Mailing State',1,2,0,100,7,7,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'otherstate','".$table_prefix."_contactaddress',1,'1','otherstate','Other State',1,2,0,100,8,7,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'mailingzip','".$table_prefix."_contactaddress',1,'1','mailingzip','Mailing Zip',1,2,0,100,9,7,1,'V~O',2,18,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'otherzip','".$table_prefix."_contactaddress',1,'1','otherzip','Other Zip',1,2,0,100,10,7,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'mailingcountry','".$table_prefix."_contactaddress',1,'1','mailingcountry','Mailing Country',1,2,0,100,11,7,1,'V~O',2,19,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'othercountry','".$table_prefix."_contactaddress',1,'1','othercountry','Other Country',1,2,0,100,12,7,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'mailingpobox','".$table_prefix."_contactaddress',1,'1','mailingpobox','Mailing Po Box',1,2,0,100,3,7,1,'V~O',2,14,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'otherpobox','".$table_prefix."_contactaddress',1,'1','otherpobox','Other Po Box',1,2,0,100,4,7,1,'V~O',2,15,'BAS',1,'')");
		//Block7 -- End

		//ContactImageInformation
 		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'imagename','".$table_prefix."_contactdetails',1,'69','imagename','Contact Image',1,2,0,100,1,$imageblockid,1,'V~O',3,null,'BAS',0,'')");


		//Block8 -- Start
		$this->db->query("insert into ".$table_prefix."_field values (4,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_contactdetails',1,'19','description','Description',1,2,0,100,1,8,1,'V~O',2,20,'BAS',1,'')");
		//Block8 -- End
		//Contact Details -- END

		//Potential Details -- START
		//Block1 -- Start
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'potentialname','".$table_prefix."_potential',1,'1','potentialname','Potential Name',1,0,0,100,1,1,1,'V~M',2,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'potential_no','".$table_prefix."_potential',1,'4','potential_no','Potential No',1,0,0,100,2,1,1,'V~O',2,2,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'amount','".$table_prefix."_potential',1,71,'amount','Amount',1,2,0,100,4,1,1,'N~O',2,3,'BAS',1,'')");
		//changed for b2c model
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (2,$fieldid,'related_to','".$table_prefix."_potential',1,'10','related_to','Related To',1,0,0,100,3,1,1,'V~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Potentials', 'Accounts', NULL, 0)");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Potentials', 'Contacts', NULL, 1)");
		//b2c model changes end
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'closingdate','".$table_prefix."_potential',1,'23','closingdate','Expected Close Date',1,2,0,100,7,1,1,'D~M',2,5,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'eff_closingdate','".$table_prefix."_potential',1,'5','eff_closingdate','EffClosingDate',1,2,0,100,8,1,1,'D~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'potentialtype','".$table_prefix."_potential',1,'15','opportunity_type','Type',1,2,0,100,6,1,1,'V~O',2,4,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'nextstep','".$table_prefix."_potential',1,'1','nextstep','Next Step',1,2,0,100,10,1,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'leadsource','".$table_prefix."_potential',1,'15','leadsource','Lead Source',1,2,0,100,9,1,1,'V~O',2,6,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'sales_stage','".$table_prefix."_potential',1,'15','sales_stage','Sales Stage',1,2,0,100,12,1,1,'V~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,2,0,100,11,1,1,'I~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'probability','".$table_prefix."_potential',1,'9','probability','Probability',1,2,0,100,14,1,1,'N~O',2,7,'BAS',1,'')");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$fieldid.",'campaignid','".$table_prefix."_potential',1,'10','campaignid','Campaign Source',1,2,0,100,13,1,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Potentials', 'Campaigns', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,16,1,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,15,1,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,19,1,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123

		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'contact_roles','".$table_prefix."_potential',1,'15','contact_roles','ContactRoles',100,1,0,100,17,1,2,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'partner_roles','".$table_prefix."_potential',1,'15','partner_roles','PartnerRoles',100,1,0,100,18,1,2,'V~O',3,null,'BAS',0,'')");



		//Block1 -- End

		//Block3 -- Start
		$this->db->query("insert into ".$table_prefix."_field values (2,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_potential',1,'19','description','Description',1,2,0,100,1,3,1,'V~O',2,8,'BAS',1,'')");
		//Block3 -- End
		//Potential Details -- END

		//campaign entries being added
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'campaignname','".$table_prefix."_campaign',1,'1','campaignname','Campaign Name',1,0,0,100,1,$campaignbasicblockid,1,'V~M',0,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'campaign_no','".$table_prefix."_campaign',1,'4','campaign_no','Campaign No',1,0,0,100,2,$campaignbasicblockid,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'campaigntype','".$table_prefix."_campaign',1,15,'campaigntype','Campaign Type',1,2,0,100,5,$campaignbasicblockid,1,'V~O',2,3,'BAS',1,'')");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$fieldid.",'product_id','".$table_prefix."_campaign',1,'10','product_id','Product',1,2,0,100,6,$campaignbasicblockid,1,'I~O',2,5,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Campaigns', 'Products', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'campaignstatus','".$table_prefix."_campaign',1,15,'campaignstatus','Campaign Status',1,2,0,100,4,$campaignbasicblockid,1,'V~O',2,6,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'closingdate','".$table_prefix."_campaign',1,'23','closingdate','Expected Close Date',1,2,0,100,8,$campaignbasicblockid,1,'D~M',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,3,$campaignbasicblockid,1,'I~M',0,7,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'numsent','".$table_prefix."_campaign',1,'9','numsent','Num Sent',1,2,0,100,12,$campaignbasicblockid,1,'N~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'sponsor','".$table_prefix."_campaign',1,'1','sponsor','Sponsor',1,2,0,100,9,$campaignbasicblockid,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'targetaudience','".$table_prefix."_campaign',1,'1','targetaudience','Target Audience',1,2,0,100,7,$campaignbasicblockid,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values(26,".$this->db->getUniqueID($table_prefix."_field").",'targetsize','".$table_prefix."_campaign',1,'1','targetsize','TargetSize',1,2,0,100,10,$campaignbasicblockid,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,11,$campaignbasicblockid,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,13,$campaignbasicblockid,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,14,$campaignbasicblockid,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123

		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'expectedresponse','".$table_prefix."_campaign',1,'15','expectedresponse','Expected Response',1,2,0,100,3,$campaignexpectedandactualsblock,1,'V~O',2,4,'BAS',1,'')");
		// crmv@38798
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'expectedrevenue','".$table_prefix."_campaign',1,'71','expectedrevenue','Expected Revenue',1,2,0,100,4,$campaignexpectedandactualsblock,1,'N~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'budgetcost','".$table_prefix."_campaign',1,'71','budgetcost','Budget Cost',1,2,0,100,1,$campaignexpectedandactualsblock,1,'N~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'actualcost','".$table_prefix."_campaign',1,'71','actualcost','Actual Cost',1,2,0,100,2,$campaignexpectedandactualsblock,1,'N~O',1,null,'BAS',1,'')");
		// crmv@38798e
		$this->db->query("insert into ".$table_prefix."_field values(26,".$this->db->getUniqueID($table_prefix."_field").",'expectedresponsecount','".$table_prefix."_campaign',1,'1','expectedresponsecount','Expected Response Count',1,2,0,100,7,$campaignexpectedandactualsblock,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values(26,".$this->db->getUniqueID($table_prefix."_field").",'expectedsalescount','".$table_prefix."_campaign',1,'1','expectedsalescount','Expected Sales Count',1,2,0,100,5,$campaignexpectedandactualsblock,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values(26,".$this->db->getUniqueID($table_prefix."_field").",'expectedroi','".$table_prefix."_campaign',1,'1','expectedroi','Expected ROI',1,2,0,100,9,$campaignexpectedandactualsblock,1,'N~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values(26,".$this->db->getUniqueID($table_prefix."_field").",'actualresponsecount','".$table_prefix."_campaign',1,'1','actualresponsecount','Actual Response Count',1,2,0,100,8,$campaignexpectedandactualsblock,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values(26,".$this->db->getUniqueID($table_prefix."_field").",'actualsalescount','".$table_prefix."_campaign',1,'1','actualsalescount','Actual Sales Count',1,2,0,100,6,$campaignexpectedandactualsblock,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values(26,".$this->db->getUniqueID($table_prefix."_field").",'actualroi','".$table_prefix."_campaign',1,'1','actualroi','Actual ROI',1,2,0,100,10,$campaignexpectedandactualsblock,1,'N~O',1,null,'BAS',1,'')");

		$this->db->query("insert into ".$table_prefix."_field values (26,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_campaign',1,'19','description','Description',1,2,0,100,1,$campaidndescriptionblock,1,'V~O',1,null,'BAS',1,'')");

		//entry to vte_field to maintain account,contact,lead relationships

		$this->db->query("INSERT INTO ".$table_prefix."_field(tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, selected, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable) VALUES (".getTabid('Contacts').",".$this->db->getUniqueID($table_prefix.'_field').", 'campaignrelstatus', '".$table_prefix."_campaignrelstatus', 1, '16', 'campaignrelstatus', 'Status', 1, 0, 0, 100, 1, NULL, 1, 'V~O', 1, NULL, 'BAS', 0)");
		$this->db->query("INSERT INTO ".$table_prefix."_field(tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, selected, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable) VALUES (".getTabid('Accounts').",".$this->db->getUniqueID($table_prefix.'_field').", 'campaignrelstatus', '".$table_prefix."_campaignrelstatus', 1, '16', 'campaignrelstatus', 'Status', 1, 0, 0, 100, 1, NULL, 1, 'V~O', 1, NULL, 'BAS', 0)");
		$this->db->query("INSERT INTO ".$table_prefix."_field(tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, selected, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable) VALUES (".getTabid('Leads').",".$this->db->getUniqueID($table_prefix.'_field').", 'campaignrelstatus', '".$table_prefix."_campaignrelstatus', 1, '16', 'campaignrelstatus', 'Status', 1, 0, 0, 100, 1, NULL, 1, 'V~O', 1, NULL, 'BAS', 0)");
		$this->db->query("INSERT INTO ".$table_prefix."_field(tabid, fieldid, columnname, tablename, generatedtype, uitype, fieldname, fieldlabel, readonly, presence, selected, maximumlength, sequence, block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, masseditable) VALUES (".getTabid('Campaigns').",".$this->db->getUniqueID($table_prefix.'_field').", 'campaignrelstatus', '".$table_prefix."_campaignrelstatus', 1, '16', 'campaignrelstatus', 'Status', 1, 0, 0, 100, 1, NULL, 1, 'V~O', 1, NULL, 'BAS', 0)");
		//Campaign entries end

		//Ticket Details -- START
		//Block25 -- Start

		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'ticket_no','".$table_prefix."_troubletickets',1,'4','ticket_no','Ticket No',1,0,0,100,13,25,1,'V~O',0,1,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,4,25,1,'I~M',0,4,'BAS',1,'')");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$fieldid.",'parent_id','".$table_prefix."_troubletickets',1,'10','parent_id','Related To',1,0,0,100,2,25,1,'I~O',1,null,'BAS',1,'')");	//crmv@29506
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'HelpDesk','Contacts',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'HelpDesk','Accounts',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'HelpDesk','Leads',NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'priority','".$table_prefix."_troubletickets',1,'15','ticketpriorities','Priority',1,2,0,100,6,25,1,'V~O',2,4,'BAS',1,'')");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$fieldid.",'product_id','".$table_prefix."_troubletickets',1,'10','product_id','Product Name',1,2,0,100,5,25,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'HelpDesk', 'Products', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'severity','".$table_prefix."_troubletickets',1,'15','ticketseverities','Severity',1,2,0,100,8,25,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'status','".$table_prefix."_troubletickets',1,'15','ticketstatus','Status',1,2,0,100,7,25,1,'V~M',1,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'category','".$table_prefix."_troubletickets',1,'15','ticketcategories','Category',1,2,0,100,10,25,1,'V~O',2,3,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'hours','".$table_prefix."_troubletickets',1,'1','hours','Hours',1,2,0,100,9,25,1,'N~O',1,null,'BAS',1,'')");	//crmv@14132
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'days','".$table_prefix."_troubletickets',1,'1','days','Days',1,2,0,100,10,25,1,'N~O',1,null,'BAS',1,'')");	//crmv@14132
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,9,25,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,12,25,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,17,25,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		//crmv@29506
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (13,$fieldid,'projectplanid','".$table_prefix."_troubletickets',1,'10','projectplanid','ProjectPlan',1,0,0,100,14,25,1,'V~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'HelpDesk', 'ProjectPlan', NULL, NULL)");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (13,$fieldid,'projecttaskid','".$table_prefix."_troubletickets',1,'10','projecttaskid','ProjectTask',1,0,0,100,15,25,1,'V~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'HelpDesk', 'ProjectTask', NULL, NULL)");
		//crmv@29506e
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'title','".$table_prefix."_troubletickets',1,'21','ticket_title','Title',1,0,0,100,1,25,1,'V~M',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_troubletickets',1,'19','description','Description',1,2,0,100,1,28,1,'V~O',2,5,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'solution','".$table_prefix."_troubletickets',1,'19','solution','Solution',1,0,0,100,1,29,1,'V~O',3,null,'BAS',0,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'comments','".$table_prefix."_ticketcomments',1,'19','comments','Add Comment',1,0,0,100,1,30,1,'V~O',3,null,'BAS',0,'')");
 		//crmv@56233
 		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'mailscanner_action','".$table_prefix."_troubletickets',1,'204','mailscanner_action','Mail Converter Action',99,2,0,100,16,25,1,'V~O',3,null,'BAS',1,'')");
 		//crmv@56233e
 		$this->db->query("insert into ".$table_prefix."_field values (13,".$this->db->getUniqueID($table_prefix."_field").",'signature','".$table_prefix."_troubletickets',1,'1016','signature','HelpDeskSignature',1,2,0,100,17,".$signature_block.",1,'V~O',1,null,'BAS',1,'')");

		//Block25-30 -- End
		//Ticket Details -- END

		//Product Details -- START
		//Block31-36 -- Start

		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'productname','".$table_prefix."_products',1,'1','productname','Product Name',1,0,0,100,1,31,1,'V~M',2,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'product_no','".$table_prefix."_products',1,'4','product_no','Product No',1,0,0,100,2,31,1,'V~O',2,2,'BAS',0,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'productcode','".$table_prefix."_products',1,'1','productcode','Part Number',1,2,0,100,3,31,1,'V~O',2,3,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'upc_code','".$table_prefix."_products',1,'1','upc_code','UPC Code',1,2,0,100,4,31,1,'V~O',2,3,'BAS',1,'')"); // crmv@198024
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'discontinued','".$table_prefix."_products',1,'56','discontinued','Product Active',1,2,0,100,5,31,1,'V~O',0,11,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'manufacturer','".$table_prefix."_products',1,'15','manufacturer','Manufacturer',1,2,0,100,6,31,1,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'productcategory','".$table_prefix."_products',1,'15','productcategory','Product Category',1,2,0,100,7,31,1,'V~O',2,4,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'sales_start_date','".$table_prefix."_products',1,'5','sales_start_date','Sales Start Date',1,2,0,100,8,31,1,'D~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'sales_end_date','".$table_prefix."_products',1,'5','sales_end_date','Sales End Date',1,2,0,100,9,31,1,'D~O~OTH~GE~sales_start_date~Sales Start Date',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'start_date','".$table_prefix."_products',1,'5','start_date','Support Start Date',1,2,0,100,10,31,1,'D~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'expiry_date','".$table_prefix."_products',1,'5','expiry_date','Support Expiry Date',1,2,0,100,11,31,1,'D~O~OTH~GE~start_date~Start Date',1,null,'BAS',1,'')");


 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'website','".$table_prefix."_products',1,'17','website','Website',1,2,0,100,14,31,1,'V~O',1,null,'BAS',1,'')");
 		$fieldid = $this->db->getUniqueID($table_prefix."_field");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$fieldid.",'vendor_id','".$table_prefix."_products',1,'10','vendor_id','Vendor Name',1,2,0,100,13,31,1,'I~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Products', 'Vendors', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'mfr_part_no','".$table_prefix."_products',1,'1','mfr_part_no','Mfr PartNo',1,2,0,100,16,31,1,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'vendor_part_no','".$table_prefix."_products',1,'1','vendor_part_no','Vendor PartNo',1,2,0,100,15,31,1,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'serialno','".$table_prefix."_products',1,'1','serial_no','Serial No',1,2,0,100,18,31,1,'V~O',2,5,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'productsheet','".$table_prefix."_products',1,'1','productsheet','Product Sheet',1,2,0,100,17,31,1,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'glacct','".$table_prefix."_products',1,'15','glacct','GL Account',1,2,0,100,20,31,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,19,31,2,'T~O',3,null,'BAS',0,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,21,31,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,22,31,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		
		// crmv@198024
		//Block Variant Information
		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'confprodinfo','".$table_prefix."_products',1,'1','confprodinfo','VariantInfo',100,2,0,100,1,{$variant_block},1,'V~O',2,1,'BAS',1,'')");
		// crmv@198024e

		//Block32 Pricing Information

		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'unit_price','".$table_prefix."_products',1,'71','unit_price','Unit Price',1,2,0,100,1,32,1,'N~O',2,6,'BAS',0,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'commissionrate','".$table_prefix."_products',1,'9','commissionrate','Commission Rate',1,2,0,100,2,32,1,'N~O',2,7,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'taxclass','".$table_prefix."_products',1,'83','taxclass','Tax Class',1,2,0,100,4,32,1,'V~O',2,8,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'unit_cost','".$table_prefix."_products',1,'71','unit_cost','UnitCost',1,2,0,100,5,32,1,'N~O',1,null,'BAS',0,'')");


		//Block 33 stock info

 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'usageunit','".$table_prefix."_products',1,'15','usageunit','Usage Unit',1,2,0,100,1,33,1,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'qty_per_unit','".$table_prefix."_products',1,'7','qty_per_unit','Qty/Unit',1,2,0,100,2,33,1,'N~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'qtyinstock','".$table_prefix."_products',1,'7','qtyinstock','Qty In Stock',1,2,0,100,3,33,1,'NN~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'reorderlevel','".$table_prefix."_products',1,'1','reorderlevel','Reorder Level',1,2,0,100,4,33,1,'I~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,5,33,1,'V~M',1,null,'BAS',1,'')"); // crmv@109663
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'qtyindemand','".$table_prefix."_products',1,'1','qtyindemand','Qty In Demand',1,2,0,100,6,33,1,'I~O',1,null,'BAS',1,'')");

		//ProductImageInformation

 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'imagename','".$table_prefix."_products',1,'69','imagename','Product Image',1,2,0,100,1,35,1,'V~O',2,9,'BAS',1,'')");

		//Block 36 Description Info
 		$this->db->query("insert into ".$table_prefix."_field values (14,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_products',1,'19','description','Description',1,2,0,100,1,36,1,'V~O',2,10,'BAS',1,'')");

		//Product Details -- END

		//Documents Details -- START
		//Block17 -- Start
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'title','".$table_prefix."_notes',1,'1','notes_title','Title',1,0,0,100,1,17,1,'V~M',0,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,5,17,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,6,17,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'filename','".$table_prefix."_notes',1,'28','filename','File Name',1,2,0,100,3,".$fileblockid.",1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,4,17,1,'I~M',0,3,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'notecontent','".$table_prefix."_notes',1,'210','notecontent','Note',1,2,0,100,1,$desc_blockid,1,'V~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'filetype','".$table_prefix."_notes',1,1,'filetype','File Type',1,2,0,100,5,".$fileblockid.",2,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'filesize','".$table_prefix."_notes',1,1,'filesize','File Size',1,2,0,100,4,".$fileblockid.",2,'I~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'filelocationtype','".$table_prefix."_notes',1,27,'filelocationtype','Download Type',1,0,0,100,1,".$fileblockid.",1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'backend_name','".$table_prefix."_notes',1,212,'backend_name','StorageBackend',1,0,0,63,1,".$fileblockid.",1,'V~O',3,null,'BAS',0,'')"); // crmv@95157
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'fileversion','".$table_prefix."_notes',1,1,'fileversion','Version',1,2,0,100,6,$fileblockid,1,'V~O',1,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'filestatus','".$table_prefix."_notes',1,56,'filestatus','Active',1,0,0,100,2,".$fileblockid.",1,'V~O',1,null,'BAS',1,'')");	//crmv@63336
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'filedownloadcount','".$table_prefix."_notes',1,1,'filedownloadcount','Download Count',1,2,0,100,7,".$fileblockid.",2,'I~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'active_portal','".$table_prefix."_notes',1,56,'active_portal','Portal Active',1,0,0,100,8,".$fileblockid.",1,'C~O',1,null,'BAS',1,'')");	//crmv@90004
		$this->db->query("insert into ".$table_prefix."_field values(8,".$this->db->getUniqueID($table_prefix."_field").",'folderid','".$table_prefix."_notes',1,26,'folderid','Folder Name',1,2,0,100,2,17,1,'I~O',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'note_no','".$table_prefix."_notes',1,'4','note_no','Document No',1,0,0,100,3,17,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (8,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,7,17,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		//Block17 -- End
		//Documents Details -- END

		//Email Details -- START
		//Block21 -- Start

		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'date_start','".$table_prefix."_activity',1,'6','date_start','Date & Time Sent',1,0,0,100,1,21,1,'DT~M~time_start~Time Start',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'semodule','".$table_prefix."_activity',1,'1','parent_type','Sales Enity Module',1,0,0,100,2,21,3,'',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'activitytype','".$table_prefix."_activity',1,'1','activitytype','Activtiy Type',1,0,0,100,3,21,3,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,5,21,1,'I~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'subject','".$table_prefix."_activity',1,'1','subject','Subject',1,0,0,100,1,23,1,'V~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'name','".$table_prefix."_attachments',1,'61','filename','Attachment',1,0,0,100,2,23,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_activity',1,'19','description','Description',1,0,0,100,1,24,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'time_start','".$table_prefix."_activity',1,'1','time_start','Time Start',1,0,0,100,9,23,1,'T~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,10,22,1,'T~O',3,null,'BAS',0,'')");
	 	$this->db->query("insert into ".$table_prefix."_field values (10,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,11,21,2,'T~O',3,null,'BAS',0,'')");
	 	$this->db->query("INSERT INTO ".$table_prefix."_field VALUES (10,".$this->db->getUniqueID($table_prefix."_field").", 'access_count', '".$table_prefix."_email_track', '1', '25', 'access_count', 'Access Count', '1', '0', '0', '100', '6', '21', '3', 'V~O', '1', NULL, 'BAS', 0,'')");

		//Block21 -- End
		//Email Details -- END

		//Task Details --START
		//Block19 -- Start
		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'subject','".$table_prefix."_activity',1,'1','subject','Subject',1,0,0,100,1,19,1,'V~M',0,1,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,10,19,1,'I~M',0,4,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'date_start','".$table_prefix."_activity',1,'6','date_start','Start Date & Time',1,0,0,100,4,19,1,'DT~M~time_start',0,2,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'time_start','".$table_prefix."_activity',1,'1','time_start','Time Start',1,0,0,100,5,19,3,'T~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'time_end','".$table_prefix."_activity',1,'1','time_end','End Time',1,0,0,100,7,19,3,'T~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'due_date','".$table_prefix."_activity',1,'23','due_date','Due Date',1,0,0,100,6,19,1,'D~M~OTH~GE~date_start~Start Date & Time',1,null,'BAS',1,'')");
 		$fieldid = $this->db->getUniqueID($table_prefix."_field");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$fieldid.",'crmid','".$table_prefix."_seactivityrel',1,'10','parent_id','Related To',1,0,0,100,14,19,1,'I~O',1,null,'BAS',1,'')");
 		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Accounts',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Campaigns',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','HelpDesk',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Invoice',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Leads',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Potentials',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','ProjectMilestone',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','ProjectPlan',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','ProjectTask',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','PurchaseOrder',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Quotes',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','SalesOrder',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Visitreport',NULL, NULL)");
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Vendors',NULL, NULL)");	//crmv@94717
		$this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Calendar','Employees',NULL, NULL)"); //crmv@187922
 		$fieldid = $this->db->getUniqueID($table_prefix."_field");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$fieldid.",'contactid','".$table_prefix."_cntactivityrel',1,'10','contact_id','Contact Name',1,0,0,100,15,19,1,'I~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Calendar', 'Contacts', NULL, NULL)");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'status','".$table_prefix."_activity',1,'15','taskstatus','Status',1,0,0,100,8,19,1,'V~M',0,3,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'eventstatus','".$table_prefix."_activity',1,'15','eventstatus','Status',1,0,0,100,9,19,3,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'priority','".$table_prefix."_activity',1,'15','taskpriority','Priority',1,0,0,100,11,19,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,12,19,2,'T~O',3,null,'BAS',0,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,13,19,2,'T~O',3,null,'BAS',0,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'activitytype','".$table_prefix."_activity',1,'15','activitytype','Activity Type',1,0,0,100,16,19,3,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("Insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'visibility','".$table_prefix."_activity',1,'16','visibility','Visibility',1,0,0,100,17,19,3,'V~O',1,null,'BAS',1,'')");

 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_activity',1,'19','description','Description',1,0,0,100,3,19,1,'V~O',1,null,'BAS',1,'')");


		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'duration_hours','".$table_prefix."_activity',1,'7','duration_hours','Duration',1,0,0,100,18,19,3,'I~O',1,null,'BAS',1,'')"); // crmv@30385
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'duration_minutes','".$table_prefix."_activity',1,'7','duration_minutes','Duration Minutes',1,0,0,100,19,19,3,'I~O',1,null,'BAS',1,'')"); // crmv@30385
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'location','".$table_prefix."_activity',1,'1','location','Location',1,0,0,100,20,19,3,'V~O',1,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'reminder_time','".$table_prefix."_activity_reminder',1,'30','reminder_time','Send Reminder',1,0,0,100,21,19,3,'I~O',1,null,'BAS',1,'')");

 		$this->db->query("insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'recurringtype','".$table_prefix."_activity',1,'16','recurringtype','Recurrence',1,0,0,100,22,19,3,'O~O',1,null,'BAS',1,'')");

 		$this->db->query("Insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'notime','".$table_prefix."_activity',1,56,'notime','No Time',1,0,0,100,23,19,3,'C~O',1,null,'BAS',1,'')");
		$this->db->query("Insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'exp_duration','".$table_prefix."_activity',1,15,'exp_duration','ExpDuration',1,0,0,100,24,19,1,'V~O',0,5,'BAS',1,'')"); // crmv@36871
		$this->db->query("Insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'ical_uuid','".$table_prefix."_activity',1,1,'ical_uuid','ICalUUID',99,0,0,100,25,19,3,'V~O',1,6,'BAS',0,'')"); // crmv@68357
		$this->db->query("Insert into ".$table_prefix."_field values (9,".$this->db->getUniqueID($table_prefix."_field").",'recurr_idx','".$table_prefix."_activity',1,7,'recurr_idx','RecurrentIdx',99,0,0,100,26,19,3,'I~O',1,6,'BAS',0,'')"); // crmv@81126
		//Block19 -- End
		//Task Details -- END

		//Event Details --START
		//Block41-43-- Start
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'subject','".$table_prefix."_activity',1,'1','subject','Subject',1,0,0,100,3,39,1,'V~M',0,1,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,11,39,1,'I~M',0,6,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'date_start','".$table_prefix."_activity',1,'6','date_start','Start Date & Time',1,0,0,100,5,39,1,'DT~M~time_start',0,2,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'time_start','".$table_prefix."_activity',1,'1','time_start','Time Start',1,0,0,100,6,39,3,'T~M',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'due_date','".$table_prefix."_activity',1,'23','due_date','End Date',1,0,0,100,7,39,1,'D~M~OTH~GE~date_start~Start Date & Time',0,5,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'time_end','".$table_prefix."_activity',1,'1','time_end','End Time',1,0,0,100,8,39,3,'T~M',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'recurringtype','".$table_prefix."_activity',1,'16','recurringtype','Recurrence',1,0,0,100,15,39,1,'O~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'duration_hours','".$table_prefix."_activity',1,'7','duration_hours','Duration',1,0,0,100,16,39,3,'I~M',1,null,'BAS',1,'')"); // crmv@30385
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'duration_minutes','".$table_prefix."_activity',1,'7','duration_minutes','Duration Minutes',1,0,0,100,17,39,3,'I~O',1,null,'BAS',1,'')"); // crmv@30385
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$fieldid.",'crmid','".$table_prefix."_seactivityrel',1,'10','parent_id','Related To',1,0,0,100,18,39,1,'I~O',1,null,'BAS',1,'')");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Accounts',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Campaigns',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','HelpDesk',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Invoice',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Leads',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Potentials',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','ProjectMilestone',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','ProjectPlan',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','ProjectTask',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','PurchaseOrder',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Quotes',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','SalesOrder',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Visitreport',NULL, NULL)");
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Vendors',NULL, NULL)");	//crmv@94717
		 $this->db->query("INSERT INTO ".$table_prefix."_fieldmodulerel (fieldid,module,relmodule,status,sequence) VALUES ($fieldid,'Events','Employees',NULL, NULL)"); //crmv@187922
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'eventstatus','".$table_prefix."_activity',1,'15','eventstatus','Status',1,0,0,100,10,39,1,'V~M',0,3,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'activitytype','".$table_prefix."_activity',1,'15','activitytype','Activity Type',1,0,0,100,1,39,1,'V~M',0,4,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'location','".$table_prefix."_activity',1,'1','location','Location',1,0,0,100,9,39,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,13,39,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,14,39,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("Insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'priority','".$table_prefix."_activity',1,15,'taskpriority','Priority',1,0,0,100,12,39,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("Insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'notime','".$table_prefix."_activity',1,56,'notime','No Time',1,0,0,100,19,39,3,'C~O',1,null,'BAS',1,'')");
		 $this->db->query("Insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'visibility','".$table_prefix."_activity',1,'16','visibility','Visibility',1,0,0,100,2,39,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'is_all_day_event','".$table_prefix."_activity',1,'56','is_all_day_event','All day',1,0,0,100,20,39,1,'C~O',1,null,'BAS',1,'')");	//crmv@17001

		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_activity',1,'19','description','Description',1,0,0,100,4,39,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'reminder_time','".$table_prefix."_activity_reminder',1,'30','reminder_time','Send Reminder',1,0,0,100,1,40,1,'I~O',1,null,'BAS',1,'')");
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$fieldid.",'contactid','".$table_prefix."_cntactivityrel',1,'10','contact_id','Contact Name',1,0,0,100,1,19,1,'I~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Events', 'Contacts', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'ical_uuid','".$table_prefix."_activity',1,1,'ical_uuid','ICalUUID',99,0,0,100,21,39,3,'V~O',1,null,'BAS',0,'')"); // crmv@68357
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'recurr_idx','".$table_prefix."_activity',1,7,'recurr_idx','RecurrentIdx',99,0,0,100,22,39,3,'I~O',1,null,'BAS',0,'')"); // crmv@81126
		 $this->db->query("insert into ".$table_prefix."_field values (16,".$this->db->getUniqueID($table_prefix."_field").",'email','".$table_prefix."_activity_organizer',1,49,'organizer','Organizer',99,0,0,100,23,39,1,'V~O',1,null,'BAS',0,'')"); // crmv@187823 crmv@200585

		//Block41-43 -- End
		//Event Details -- END

		//Faq Details -- START
		//Block37-40 -- Start

		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (15,".$fieldid.",'product_id','".$table_prefix."_faq',1,'10','product_id','Product Name',1,2,0,100,1,37,1,'I~O',3,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Faq', 'Products', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'faq_no','".$table_prefix."_faq',1,'4','faq_no','Faq No',1,0,0,100,2,37,1,'V~O',0,1,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'category','".$table_prefix."_faq',1,'15','faqcategories','Category',1,2,0,100,4,37,1,'V~O',3,null,'BAS',1,'')");
 		$this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'status','".$table_prefix."_faq',1,'15','faqstatus','Status',1,2,0,100,3,37,1,'V~M',0,2,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'question','".$table_prefix."_faq',1,'19','question','Question',1,2,0,100,7,37,1,'V~M',0,4,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'answer','".$table_prefix."_faq',1,'19','faq_answer','Answer',1,2,0,100,8,37,1,'V~M',0,5,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'comments','".$table_prefix."_faqcomments',1,'19','comments','Add Comment',1,0,0,100,1,38,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,5,37,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (15,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,6,37,2,'T~O',3,null,'BAS',0,'')");


		//Block37-40 -- End
		//Ticket Details -- END

		//Vendor Details --START
		//Block44-47

		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'vendorname','".$table_prefix."_vendor',1,'1','vendorname','Vendor Name',1,0,0,100,1,$vendorbasicinfo,1,'V~M',0,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'vendor_no','".$table_prefix."_vendor',1,'4','vendor_no','Vendor No',1,0,0,100,2,$vendorbasicinfo,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'email','".$table_prefix."_vendor',1,'13','email','Email',1,2,0,100,3,$vendorbasicinfo,1,'E~O',0,3,'BAS',1,'')");	//crmv@16265
	 	$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'phone','".$table_prefix."_vendor',1,'11','phone','Phone',1,2,0,100,4,$vendorbasicinfo,1,'V~O',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'fax','".$table_prefix."_vendor',1,'1013','fax','Fax',1,2,0,100,5,$vendorbasicinfo,1,'V~O',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'website','".$table_prefix."_vendor',1,'17','website','Website',1,2,0,100,6,$vendorbasicinfo,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'glacct','".$table_prefix."_vendor',1,'15','glacct','GL Account',1,2,0,100,7,$vendorbasicinfo,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'category','".$table_prefix."_vendor',1,'1','category','Category',1,2,0,100,8,$vendorbasicinfo,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,9,$vendorbasicinfo,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,10,$vendorbasicinfo,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,11,$vendorbasicinfo,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		
		//Block 46

		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'street','".$table_prefix."_vendor',1,'21','street','Street',1,2,0,100,1,$vendoraddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'pobox','".$table_prefix."_vendor',1,'1','pobox','Po Box',1,2,0,100,2,$vendoraddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'city','".$table_prefix."_vendor',1,'1','city','City',1,2,0,100,3,$vendoraddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'state','".$table_prefix."_vendor',1,'1','state','State',1,2,0,100,4,$vendoraddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'postalcode','".$table_prefix."_vendor',1,'1','postalcode','Postal Code',1,2,0,100,5,$vendoraddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'country','".$table_prefix."_vendor',1,'1','country','Country',1,2,0,100,6,$vendoraddressblock,1,'V~O',1,null,'BAS',1,'')");

		//Block 47

		$this->db->query("insert into ".$table_prefix."_field values (18,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_vendor',1,'19','description','Description',1,2,0,100,1,$vendordescriptionblock,1,'V~O',1,null,'BAS',1,'')");

		//Vendor Details -- END

		//PriceBook Details Start
		//Block48

		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'bookname','".$table_prefix."_pricebook',1,'1','bookname','Price Book Name',1,0,0,100,1,$pricebookbasicblock,1,'V~M',0,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'pricebook_no','".$table_prefix."_pricebook',1,'4','pricebook_no','PriceBook No',1,0,0,100,3,$pricebookbasicblock,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'active','".$table_prefix."_pricebook',1,'56','active','Active',1,2,0,100,2,$pricebookbasicblock,1,'C~O',2,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,4,$pricebookbasicblock,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,5,$pricebookbasicblock,2,'T~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'currency_id','".$table_prefix."_pricebook',1,'117','currency_id','Currency',1,0,0,100,5,$pricebookbasicblock,1,'I~M',0,3,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,6,$pricebookbasicblock,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123
		//Block50

		$this->db->query("insert into ".$table_prefix."_field values (19,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_pricebook',1,'19','description','Description',1,2,0,100,1,$pricebookdescription,1,'V~O',1,null,'BAS',1,'')");

		//PriceBook Details End


		//Quote Details -- START
		 //Block51

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'quote_no','".$table_prefix."_quotes',1,'4','quote_no','Quote No',1,0,0,100,3,$quotesbasicblock,1,'V~O',0,2,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'subject','".$table_prefix."_quotes',1,'1','subject','Subject',1,0,0,100,1,$quotesbasicblock,1,'V~M',0,1,'BAS',1,'')");
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$fieldid.",'potentialid','".$table_prefix."_quotes',1,'10','potential_id','Potential Name',1,2,0,100,2,$quotesbasicblock,1,'I~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Quotes', 'Potentials', NULL, NULL)");
 		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'quotestage','".$table_prefix."_quotes',1,'15','quotestage','Quote Stage',1,2,0,100,4,$quotesbasicblock,1,'V~M',0,3,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'validtill','".$table_prefix."_quotes',1,'5','validtill','Valid Till',1,2,0,100,5,$quotesbasicblock,1,'D~O',3,null,'BAS',1,'')");
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$fieldid.",'contactid','".$table_prefix."_quotes',1,'10','contact_id','Contact Name',1,2,0,100,6,$quotesbasicblock,1,'I~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Quotes', 'Contacts', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'carrier','".$table_prefix."_quotes',1,'15','carrier','Carrier',1,2,0,100,8,$quotesbasicblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'subtotal','".$table_prefix."_quotes',1,'1','hdnSubTotal','Sub Total',1,2,0,100,9,$quotesbasicblock,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'shipping','".$table_prefix."_quotes',1,'1','shipping','Shipping',1,2,0,100,10,$quotesbasicblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'inventorymanager','".$table_prefix."_quotes',1,'77','assigned_user_id1','Inventory Manager',1,2,0,100,11,$quotesbasicblock,1,'I~O',3,null,'BAS',1,'')");
		 //$this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'tax','".$table_prefix."_quotes',1,'1','txtTax','Sales Tax',1,0,0,100,13,51,3,'N~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'adjustment','".$table_prefix."_quotes',1,'1','txtAdjustment','Adjustment',1,2,0,100,20,$quotesbasicblock,3,'NN~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'total','".$table_prefix."_quotes',1,'1','hdnGrandTotal','Total',1,2,0,100,14,$quotesbasicblock,3,'N~O',3,null,'BAS',1,'')");
		//Added fields taxtype, discount percent, discount amount and S&H amount for Tax process
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'taxtype','".$table_prefix."_quotes',1,'16','hdnTaxType','Tax Type',1,2,0,100,14,$quotesbasicblock,3,'V~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'discount_percent','".$table_prefix."_quotes',1,'1','hdnDiscountPercent','Discount Percent',1,2,0,100,14,$quotesbasicblock,3,'N~O',3,null,'BAS',1,'LBL_DISCOUNT_PERCENT_INFO')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'discount_amount','".$table_prefix."_quotes',1,'1','hdnDiscountAmount','Discount Amount',1,2,0,100,14,$quotesbasicblock,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'s_h_amount','".$table_prefix."_quotes',1,'1','hdnS_H_Amount','S&H Amount',1,2,0,100,14,$quotesbasicblock,3,'N~O',3,null,'BAS',1,'')");

		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$fieldid.",'accountid','".$table_prefix."_quotes',1,'10','account_id','Account Name',1,2,0,100,16,$quotesbasicblock,1,'I~M',0,4,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Quotes', 'Accounts', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,17,$quotesbasicblock,1,'I~M',0,5,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,18,$quotesbasicblock,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,19,$quotesbasicblock,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,22,$quotesbasicblock,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'currency_id','".$table_prefix."_quotes',1,'117','currency_id','Currency',1,2,1,100,20,$quotesbasicblock,3,'I~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'conversion_rate','".$table_prefix."_quotes',1,'1','conversion_rate','Conversion Rate',1,2,1,100,21,$quotesbasicblock,3,'N~O',3,null,'BAS',1,'')");

		 //Block 53

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'bill_street','".$table_prefix."_quotesbillads',1,'21','bill_street','Billing Address',1,2,0,100,1,$quotesaddressblock,1,'V~M',0,6,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'ship_street','".$table_prefix."_quotesshipads',1,'21','ship_street','Shipping Address',1,2,0,100,2,$quotesaddressblock,1,'V~M',0,7,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'bill_city','".$table_prefix."_quotesbillads',1,'1','bill_city','Billing City',1,2,0,100,5,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'ship_city','".$table_prefix."_quotesshipads',1,'1','ship_city','Shipping City',1,2,0,100,6,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'bill_state','".$table_prefix."_quotesbillads',1,'1','bill_state','Billing State',1,2,0,100,7,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'ship_state','".$table_prefix."_quotesshipads',1,'1','ship_state','Shipping State',1,2,0,100,8,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'bill_code','".$table_prefix."_quotesbillads',1,'1','bill_code','Billing Code',1,2,0,100,9,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'ship_code','".$table_prefix."_quotesshipads',1,'1','ship_code','Shipping Code',1,2,0,100,10,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");


		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'bill_country','".$table_prefix."_quotesbillads',1,'1','bill_country','Billing Country',1,2,0,100,11,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'ship_country','".$table_prefix."_quotesshipads',1,'1','ship_country','Shipping Country',1,2,0,100,12,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'bill_pobox','".$table_prefix."_quotesbillads',1,'1','bill_pobox','Billing Po Box',1,2,0,100,3,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'ship_pobox','".$table_prefix."_quotesshipads',1,'1','ship_pobox','Shipping Po Box',1,2,0,100,4,$quotesaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 //Block55

		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_quotes',1,'19','description','Description',1,2,0,100,1,$quotedescription,1,'V~O',1,null,'BAS',1,'')");

		//Block 56
		 $this->db->query("insert into ".$table_prefix."_field values (20,".$this->db->getUniqueID($table_prefix."_field").",'terms_conditions','".$table_prefix."_quotes',1,'19','terms_conditions','Terms & Conditions',1,2,0,100,1,$quotetermsblock,1,'V~O',1,null,'BAS',1,'')");


		//Quote Details -- END

		//Purchase Order Details -- START
		 //Block57
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'purchaseorder_no','".$table_prefix."_purchaseorder',1,'4','purchaseorder_no','PurchaseOrder No',1,0,0,100,2,$pobasicblock,1,'V~O',0,2,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'subject','".$table_prefix."_purchaseorder',1,'1','subject','Subject',1,0,0,100,1,$pobasicblock,1,'V~M',0,1,'BAS',1,'')");
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$fieldid.",'vendorid','".$table_prefix."_purchaseorder',1,'10','vendor_id','Vendor Name',1,0,0,100,3,$pobasicblock,1,'I~M',0,3,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'PurchaseOrder', 'Vendors', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'requisition_no','".$table_prefix."_purchaseorder',1,'1','requisition_no','Requisition No',1,2,0,100,4,$pobasicblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'tracking_no','".$table_prefix."_purchaseorder',1,'1','tracking_no','Tracking Number',1,2,0,100,5,$pobasicblock,1,'V~O',1,null,'BAS',1,'')");
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$fieldid.",'contactid','".$table_prefix."_purchaseorder',1,'10','contact_id','Contact Name',1,2,0,100,6,$pobasicblock,1,'I~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'PurchaseOrder', 'Contacts', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'duedate','".$table_prefix."_purchaseorder',1,'5','duedate','Due Date',1,2,0,100,7,$pobasicblock,1,'D~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'carrier','".$table_prefix."_purchaseorder',1,'15','carrier','Carrier',1,2,0,100,8,$pobasicblock,1,'V~O',1,null,'BAS',1,'')");
		 //$this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'salestax','".$table_prefix."_purchaseorder',1,'1','txtTax','Sales Tax',1,0,0,100,10,57,3,'N~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'adjustment','".$table_prefix."_purchaseorder',1,'1','txtAdjustment','Adjustment',1,2,0,100,10,$pobasicblock,3,'NN~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'salescommission','".$table_prefix."_purchaseorder',1,'1','salescommission','Sales Commission',1,2,0,100,11,$pobasicblock,1,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'exciseduty','".$table_prefix."_purchaseorder',1,'1','exciseduty','Excise Duty',1,2,0,100,12,$pobasicblock,1,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'total','".$table_prefix."_purchaseorder',1,'1','hdnGrandTotal','Total',1,2,0,100,13,$pobasicblock,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'subtotal','".$table_prefix."_purchaseorder',1,'1','hdnSubTotal','Sub Total',1,2,0,100,14,$pobasicblock,3,'N~O',3,null,'BAS',1,'')");
		//Added fields taxtype, discount percent, discount amount and S&H amount for Tax process
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'taxtype','".$table_prefix."_purchaseorder',1,'16','hdnTaxType','Tax Type',1,2,0,100,14,$pobasicblock,3,'V~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'discount_percent','".$table_prefix."_purchaseorder',1,'1','hdnDiscountPercent','Discount Percent',1,2,0,100,14,$pobasicblock,3,'N~O',3,null,'BAS',1,'LBL_DISCOUNT_PERCENT_INFO')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'discount_amount','".$table_prefix."_purchaseorder',1,'1','hdnDiscountAmount','Discount Amount',1,0,0,100,14,$pobasicblock,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'s_h_amount','".$table_prefix."_purchaseorder',1,'1','hdnS_H_Amount','S&H Amount',1,2,0,100,14,$pobasicblock,3,'N~O',3,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'postatus','".$table_prefix."_purchaseorder',1,'15','postatus','Status',1,2,0,100,15,$pobasicblock,1,'V~M',0,4,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,16,$pobasicblock,1,'I~M',0,5,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,17,$pobasicblock,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,18,$pobasicblock,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,21,$pobasicblock,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'currency_id','".$table_prefix."_purchaseorder',1,'117','currency_id','Currency',1,2,1,100,19,$pobasicblock,3,'I~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'conversion_rate','".$table_prefix."_purchaseorder',1,'1','conversion_rate','Conversion Rate',1,2,1,100,20,$pobasicblock,3,'N~O',3,null,'BAS',1,'')");

		 //Block 59

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'bill_street','".$table_prefix."_pobillads',1,'21','bill_street','Billing Address',1,2,0,100,1,$poaddressblock,1,'V~M',0,6,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'ship_street','".$table_prefix."_poshipads',1,'21','ship_street','Shipping Address',1,2,0,100,2,$poaddressblock,1,'V~M',0,7,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'bill_city','".$table_prefix."_pobillads',1,'1','bill_city','Billing City',1,2,0,100,5,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'ship_city','".$table_prefix."_poshipads',1,'1','ship_city','Shipping City',1,2,0,100,6,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'bill_state','".$table_prefix."_pobillads',1,'1','bill_state','Billing State',1,2,0,100,7,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'ship_state','".$table_prefix."_poshipads',1,'1','ship_state','Shipping State',1,2,0,100,8,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'bill_code','".$table_prefix."_pobillads',1,'1','bill_code','Billing Code',1,2,0,100,9,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'ship_code','".$table_prefix."_poshipads',1,'1','ship_code','Shipping Code',1,2,0,100,10,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");


		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'bill_country','".$table_prefix."_pobillads',1,'1','bill_country','Billing Country',1,2,0,100,11,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'ship_country','".$table_prefix."_poshipads',1,'1','ship_country','Shipping Country',1,2,0,100,12,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'bill_pobox','".$table_prefix."_pobillads',1,'1','bill_pobox','Billing Po Box',1,2,0,100,3,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'ship_pobox','".$table_prefix."_poshipads',1,'1','ship_pobox','Shipping Po Box',1,2,0,100,4,$poaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 //Block61
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_purchaseorder',1,'19','description','Description',1,2,0,100,1,$podescription,1,'V~O',1,null,'BAS',1,'')");

		 //Block62
		 $this->db->query("insert into ".$table_prefix."_field values (21,".$this->db->getUniqueID($table_prefix."_field").",'terms_conditions','".$table_prefix."_purchaseorder',1,'19','terms_conditions','Terms & Conditions',1,2,0,100,1,$potermsblock,1,'V~O',1,null,'BAS',1,'')");

		//Purchase Order Details -- END

		//Sales Order Details -- START
		 //Block63
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'salesorder_no','".$table_prefix."_salesorder',1,'4','salesorder_no','SalesOrder No',1,0,0,100,4,$sobasicblock ,1,'V~O',0,2,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'subject','".$table_prefix."_salesorder',1,'1','subject','Subject',1,0,0,100,1,$sobasicblock ,1,'V~M',0,1,'BAS',1,'')");
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$fieldid.",'potentialid','".$table_prefix."_salesorder',1,'10','potential_id','Potential Name',1,2,0,100,2,$sobasicblock ,1,'I~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'SalesOrder', 'Potentials', NULL, NULL)");
 		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'customerno','".$table_prefix."_salesorder',1,'1','customerno','Customer No',1,2,0,100,3,$sobasicblock ,1,'V~O',1,null,'BAS',1,'')");
 		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$fieldid.",'quoteid','".$table_prefix."_salesorder',1,'10','quote_id','Quote Name',1,2,0,100,5,$sobasicblock ,1,'I~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'SalesOrder', 'Quotes', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'purchaseorder','".$table_prefix."_salesorder',1,'1','".$table_prefix."_purchaseorder','Purchase Order',1,2,0,100,5,$sobasicblock ,1,'V~O',1,null,'BAS',1,'')");

 		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$fieldid.",'contactid','".$table_prefix."_salesorder',1,'10','contact_id','Contact Name',1,2,0,100,6,$sobasicblock ,1,'I~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'SalesOrder', 'Contacts', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'duedate','".$table_prefix."_salesorder',1,'5','duedate','Due Date',1,2,0,100,8,$sobasicblock ,1,'D~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'carrier','".$table_prefix."_salesorder',1,'15','carrier','Carrier',1,2,0,100,9,$sobasicblock ,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'pending','".$table_prefix."_salesorder',1,'1','pending','Pending',1,2,0,100,10,$sobasicblock ,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'sostatus','".$table_prefix."_salesorder',1,'15','sostatus','Status',1,2,0,100,11,$sobasicblock ,1,'V~M',0,3,'BAS',1,'')");
		 //$this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'salestax','".$table_prefix."_salesorder',1,'1','txtTax','Sales Tax',1,0,0,100,12,63,3,'N~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'adjustment','".$table_prefix."_salesorder',1,'1','txtAdjustment','Adjustment',1,2,0,100,12,$sobasicblock ,3,'NN~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'salescommission','".$table_prefix."_salesorder',1,'1','salescommission','Sales Commission',1,2,0,100,13,$sobasicblock ,1,'N~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'exciseduty','".$table_prefix."_salesorder',1,'1','exciseduty','Excise Duty',1,2,0,100,13,$sobasicblock ,1,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'total','".$table_prefix."_salesorder',1,'1','hdnGrandTotal','Total',1,2,0,100,14,$sobasicblock ,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'subtotal','".$table_prefix."_salesorder',1,'1','hdnSubTotal','Sub Total',1,2,0,100,15,$sobasicblock ,3,'N~O',3,null,'BAS',1,'')");
		//Added fields taxtype, discount percent, discount amount and S&H amount for Tax process
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'taxtype','".$table_prefix."_salesorder',1,'16','hdnTaxType','Tax Type',1,2,0,100,15,$sobasicblock ,3,'V~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'discount_percent','".$table_prefix."_salesorder',1,'1','hdnDiscountPercent','Discount Percent',1,2,0,100,15,$sobasicblock ,3,'N~O',3,null,'BAS',1,'LBL_DISCOUNT_PERCENT_INFO')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'discount_amount','".$table_prefix."_salesorder',1,'1','hdnDiscountAmount','Discount Amount',1,0,0,100,15,$sobasicblock ,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'s_h_amount','".$table_prefix."_salesorder',1,'1','hdnS_H_Amount','S&H Amount',1,2,0,100,15,$sobasicblock ,3,'N~O',3,null,'BAS',1,'')");

		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$fieldid.",'accountid','".$table_prefix."_salesorder',1,'10','account_id','Account Name',1,2,0,100,16,$sobasicblock ,1,'I~M',0,4,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'SalesOrder', 'Accounts', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,17,$sobasicblock ,1,'I~M',0,5,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,18,$sobasicblock ,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,19,$sobasicblock ,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,22,$sobasicblock,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123

		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'currency_id','".$table_prefix."_salesorder',1,'117','currency_id','Currency',1,2,1,100,20,$sobasicblock ,3,'I~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'conversion_rate','".$table_prefix."_salesorder',1,'1','conversion_rate','Conversion Rate',1,2,1,100,21,$sobasicblock ,3,'N~O',3,null,'BAS',1,'')");


		 //Block 65

		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'bill_street','".$table_prefix."_sobillads',1,'21','bill_street','Billing Address',1,2,0,100,1,$soaddressblock,1,'V~M',0,6,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'ship_street','".$table_prefix."_soshipads',1,'21','ship_street','Shipping Address',1,2,0,100,2,$soaddressblock,1,'V~M',0,7,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'bill_city','".$table_prefix."_sobillads',1,'1','bill_city','Billing City',1,2,0,100,5,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'ship_city','".$table_prefix."_soshipads',1,'1','ship_city','Shipping City',1,2,0,100,6,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'bill_state','".$table_prefix."_sobillads',1,'1','bill_state','Billing State',1,2,0,100,7,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'ship_state','".$table_prefix."_soshipads',1,'1','ship_state','Shipping State',1,2,0,100,8,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'bill_code','".$table_prefix."_sobillads',1,'1','bill_code','Billing Code',1,2,0,100,9,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'ship_code','".$table_prefix."_soshipads',1,'1','ship_code','Shipping Code',1,2,0,100,10,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");


		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'bill_country','".$table_prefix."_sobillads',1,'1','bill_country','Billing Country',1,2,0,100,11,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'ship_country','".$table_prefix."_soshipads',1,'1','ship_country','Shipping Country',1,2,0,100,12,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'bill_pobox','".$table_prefix."_sobillads',1,'1','bill_pobox','Billing Po Box',1,2,0,100,3,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'ship_pobox','".$table_prefix."_soshipads',1,'1','ship_pobox','Shipping Po Box',1,2,0,100,4,$soaddressblock,1,'V~O',1,null,'BAS',1,'')");

		//Block67
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_salesorder',1,'19','description','Description',1,2,0,100,1,$sodescription,1,'V~O',1,null,'BAS',1,'')");

		 //Block68
		 $this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix."_field").",'terms_conditions','".$table_prefix."_salesorder',1,'19','terms_conditions','Terms & Conditions',1,2,0,100,1,$sotermsblock,1,'V~O',1,null,'BAS',1,'')");

	// Add fields for the Recurring Information block - Block 86
		$this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix.'_field').",'enable_recurring','".$table_prefix."_salesorder',1,'56','enable_recurring','Enable Recurring',1,0,0,100,1,$sorecurringinvoiceblock,1,'C~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix.'_field').",'recurring_frequency','".$table_prefix."_invoice_recurring_info',1,'16','recurring_frequency','Frequency',1,0,0,100,2,$sorecurringinvoiceblock,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix.'_field').",'start_period','".$table_prefix."_invoice_recurring_info',1,'5','start_period','Start Period',1,0,0,100,3,$sorecurringinvoiceblock,1,'D~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix.'_field').",'end_period','".$table_prefix."_invoice_recurring_info',1,'5','end_period','End Period',1,0,0,100,4,$sorecurringinvoiceblock,1,'D~O~OTH~G~start_period~Start Period',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix.'_field').",'payment_duration','".$table_prefix."_invoice_recurring_info',1,'16','payment_duration','Payment Duration',1,0,0,100,5,$sorecurringinvoiceblock,1,'V~O',3,null,'BAS',0,'')");
		$this->db->query("insert into ".$table_prefix."_field values (22,".$this->db->getUniqueID($table_prefix.'_field').",'invoice_status','".$table_prefix."_invoice_recurring_info',1,'15','invoicestatus','Invoice Status',1,0,0,100,6,$sorecurringinvoiceblock,1,'V~M',0,8,'BAS',0,'')");

		//Sales Order Details -- END

		//Invoice Details -- START
		 //Block69

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'subject','".$table_prefix."_invoice',1,'1','subject','Subject',1,0,0,100,1,$invoicebasicblock,1,'V~M',0,1,'BAS',1,'')");
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$fieldid.",'salesorderid','".$table_prefix."_invoice',1,'10','salesorder_id','Sales Order',1,2,0,100,2,$invoicebasicblock,1,'I~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Invoice', 'SalesOrder', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'customerno','".$table_prefix."_invoice',1,'1','customerno','Customer No',1,2,0,100,3,$invoicebasicblock,1,'V~O',1,null,'BAS',1,'')");


		//to include contact name ".$table_prefix."_field in Invoice-start
		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$fieldid.",'contactid','".$table_prefix."_invoice',1,'10','contact_id','Contact Name',1,2,0,100,4,$invoicebasicblock,1,'I~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Invoice', 'Contacts', NULL, NULL)");
		//end

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'invoicedate','".$table_prefix."_invoice',1,'5','invoicedate','Invoice Date',1,2,0,100,5,$invoicebasicblock,1,'D~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'duedate','".$table_prefix."_invoice',1,'5','duedate','Due Date',1,2,0,100,6,$invoicebasicblock,1,'D~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'purchaseorder','".$table_prefix."_invoice',1,'1','".$table_prefix."_purchaseorder','Purchase Order',1,2,0,100,8,$invoicebasicblock,1,'V~O',1,null,'BAS',1,'')");
		 //$this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'salestax','".$table_prefix."_invoice',1,'1','txtTax','Sales Tax',1,0,0,100,9,69,3,'N~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'adjustment','".$table_prefix."_invoice',1,'1','txtAdjustment','Adjustment',1,2,0,100,9,$invoicebasicblock,3,'NN~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'salescommission','".$table_prefix."_invoice',1,'1','salescommission','Sales Commission',1,2,0,10,13,$invoicebasicblock,1,'N~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'exciseduty','".$table_prefix."_invoice',1,'1','exciseduty','Excise Duty',1,2,0,100,11,$invoicebasicblock,1,'N~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'subtotal','".$table_prefix."_invoice',1,'1','hdnSubTotal','Sub Total',1,2,0,100,12,$invoicebasicblock,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'total','".$table_prefix."_invoice',1,'1','hdnGrandTotal','Total',1,2,0,100,13,$invoicebasicblock,3,'N~O',3,null,'BAS',1,'')");
		//Added fields taxtype, discount percent, discount amount and S&H amount for Tax process
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'taxtype','".$table_prefix."_invoice',1,'16','hdnTaxType','Tax Type',1,2,0,100,13,$invoicebasicblock,3,'V~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'discount_percent','".$table_prefix."_invoice',1,'1','hdnDiscountPercent','Discount Percent',1,2,0,100,13,$invoicebasicblock,3,'N~O',3,null,'BAS',1,'LBL_DISCOUNT_PERCENT_INFO')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'discount_amount','".$table_prefix."_invoice',1,'1','hdnDiscountAmount','Discount Amount',1,2,0,100,13,$invoicebasicblock,3,'N~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'s_h_amount','".$table_prefix."_invoice',1,'1','hdnS_H_Amount','S&H Amount',1,2,0,100,14,57,3,'N~O',3,null,'BAS',1,'')");

		 $fieldid = $this->db->getUniqueID($table_prefix."_field");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$fieldid.",'accountid','".$table_prefix."_invoice',1,'10','account_id','Account Name',1,2,0,100,14,$invoicebasicblock,1,'I~M',0,3,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Invoice', 'Accounts', NULL, NULL)");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'invoicestatus','".$table_prefix."_invoice',1,'15','invoicestatus','Status',1,2,0,100,15,$invoicebasicblock,1,'V~O',2,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'smownerid','".$table_prefix."_crmentity',1,'53','assigned_user_id','Assigned To',1,0,0,100,16,$invoicebasicblock,1,'I~M',0,4,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'createdtime','".$table_prefix."_crmentity',1,'70','createdtime','Created Time',1,0,0,100,17,$invoicebasicblock,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'modifiedtime','".$table_prefix."_crmentity',1,'70','modifiedtime','Modified Time',1,0,0,100,18,$invoicebasicblock,2,'T~O',3,null,'BAS',0,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'smcreatorid','".$table_prefix."_crmentity',1,'52','creator','Creator',1,2,0,100,21,$invoicebasicblock,2,'V~O',3,null,'BAS',0,'')");	//crmv@97123

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'currency_id','".$table_prefix."_invoice',1,'117','currency_id','Currency',1,2,1,100,19,$invoicebasicblock,3,'I~O',3,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'conversion_rate','".$table_prefix."_invoice',1,'1','conversion_rate','Conversion Rate',1,2,1,100,20,$invoicebasicblock,3,'N~O',3,null,'BAS',1,'')");

		 //Block 71

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'bill_street','".$table_prefix."_invoicebillads',1,'21','bill_street','Billing Address',1,2,0,100,1,$invoiceaddressblock,1,'V~M',0,5,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'ship_street','".$table_prefix."_invoiceshipads',1,'21','ship_street','Shipping Address',1,2,0,100,2,$invoiceaddressblock,1,'V~M',0,6,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'bill_city','".$table_prefix."_invoicebillads',1,'1','bill_city','Billing City',1,2,0,100,5,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'ship_city','".$table_prefix."_invoiceshipads',1,'1','ship_city','Shipping City',1,2,0,100,6,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'bill_state','".$table_prefix."_invoicebillads',1,'1','bill_state','Billing State',1,2,0,100,7,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'ship_state','".$table_prefix."_invoiceshipads',1,'1','ship_state','Shipping State',1,2,0,100,8,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'bill_code','".$table_prefix."_invoicebillads',1,'1','bill_code','Billing Code',1,2,0,100,9,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'ship_code','".$table_prefix."_invoiceshipads',1,'1','ship_code','Shipping Code',1,2,0,100,10,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");


		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'bill_country','".$table_prefix."_invoicebillads',1,'1','bill_country','Billing Country',1,2,0,100,11,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'ship_country','".$table_prefix."_invoiceshipads',1,'1','ship_country','Shipping Country',1,2,0,100,12,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");

		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'bill_pobox','".$table_prefix."_invoicebillads',1,'1','bill_pobox','Billing Po Box',1,2,0,100,3,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'ship_pobox','".$table_prefix."_invoiceshipads',1,'1','ship_pobox','Shipping Po Box',1,2,0,100,4,$invoiceaddressblock,1,'V~O',1,null,'BAS',1,'')");

		//Block73
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_invoice',1,'19','description','Description',1,2,0,100,1,$invoicedescription,1,'V~O',1,null,'BAS',1,'')");
		 //Block74
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'terms_conditions','".$table_prefix."_invoice',1,'19','terms_conditions','Terms & Conditions',1,2,0,100,1,$invoicetermsblock,1,'V~O',1,null,'BAS',1,'')");
		//Added for Custom invoice Number
		 $this->db->query("insert into ".$table_prefix."_field values (23,".$this->db->getUniqueID($table_prefix."_field").",'invoice_no','".$table_prefix."_invoice',1,'4','invoice_no','Invoice No',1,0,0,100,3,$invoicebasicblock,1,'V~O',0,2,'BAS',0,'')");

		//Invoice Details -- END
		 //users Details Starts Block 79,80,81
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'user_name','".$table_prefix."_users',1,'106','user_name','User Name',1,0,0,11,1,$userloginandroleblockid,1,'V~M',0,1,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'is_admin','".$table_prefix."_users',1,'156','is_admin','Admin',1,0,0,3,2,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'user_password','".$table_prefix."_users',1,'99','user_password','Password',1,0,0,30,3,$userloginandroleblockid,4,'P~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'confirm_password','".$table_prefix."_users',1,'99','confirm_password','Confirm Password',1,0,0,30,5,$userloginandroleblockid,4,'P~M',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'first_name','".$table_prefix."_users',1,'1','first_name','First Name',1,0,0,30,7,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'last_name','".$table_prefix."_users',1,'1','last_name','Last Name',1,0,0,30,9,$userloginandroleblockid,1,'V~M',0,3,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'roleid','".$table_prefix."_user2role',1,'98','roleid','Role',1,0,0,200,11,$userloginandroleblockid,1,'I~M',0,4,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'email1','".$table_prefix."_users',1,'104','email1','Email',1,0,0,100,4,$userloginandroleblockid,1,'E~M',0,2,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'status','".$table_prefix."_users',1,'115','status','Status',1,0,0,100,6,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'activity_view','".$table_prefix."_users',1,'16','activity_view','Default Activity View',1,0,0,100,1,$calendaruserblock,1,'V~O',1,null,'BAS',1,'')");	//crmv@20047
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'lead_view','".$table_prefix."_users',1,'16','lead_view','Default Lead View',1,0,0,100,10,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'currency_id','".$table_prefix."_users',1,'116','currency_id','Currency',1,0,0,100,8,$userloginandroleblockid,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'hour_format','".$table_prefix."_users',1,'1','hour_format','Calendar Hour Format',1,0,0,100,4,$calendaruserblock,3,'V~O',1,null,'BAS',1,'')");	//crmv@20047
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'end_hour','".$table_prefix."_users',1,'1','end_hour','Day ends at',1,0,0,100,6,$calendaruserblock,3,'V~O',1,null,'BAS',1,'')");	//crmv@20047
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'start_hour','".$table_prefix."_users',1,'1','start_hour','Day starts at',1,0,0,100,5,$calendaruserblock,1,'T~O',1,null,'BAS',1,'')");	//crmv@20047
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'title','".$table_prefix."_users',1,'1','title','Title',1,0,0,50,1,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'phone_work','".$table_prefix."_users',1,'1','phone_work','Office Phone',1,0,0,50,5,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'department','".$table_prefix."_users',1,'1','department','Department',1,0,0,50,3,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'phone_mobile','".$table_prefix."_users',1,'1014','phone_mobile','Mobile',1,0,0,50,7,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$fieldid = $this->db->getUniqueID($table_prefix."_field");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$fieldid.",'reports_to_id','".$table_prefix."_users',1,'10','reports_to_id','Reports To',1,0,0,50,8,$usermoreinfoblock,1,'I~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_fieldmodulerel (fieldid, module, relmodule, status, sequence) values ($fieldid, 'Users', 'Users', NULL, NULL)");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'phone_other','".$table_prefix."_users',1,'1','phone_other','Other Phone',1,0,0,50,11,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'email2','".$table_prefix."_users',1,'13','email2','Other Email',1,0,0,100,4,$usermoreinfoblock,1,'E~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'phone_fax','".$table_prefix."_users',1,'1013','phone_fax','Fax',1,0,0,50,2,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'yahoo_id','".$table_prefix."_users',1,'13','yahoo_id','Yahoo id',100,0,0,100,6,$usermoreinfoblock,1,'E~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'phone_home','".$table_prefix."_users',1,'1','phone_home','Home Phone',1,0,0,50,9,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'date_format','".$table_prefix."_users',1,'16','date_format','Date Format',1,0,0,30,2,$calendaruserblock,1,'V~O',1,null,'BAS',1,'')");	//crmv@20047
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'description','".$table_prefix."_users',1,'21','description','Documents',1,0,0,250,14,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'address_street','".$table_prefix."_users',1,'21','address_street','Street Address',1,0,0,250,1,$useraddressblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'address_city','".$table_prefix."_users',1,'1','address_city','City',1,0,0,100,3,$useraddressblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'address_state','".$table_prefix."_users',1,'1','address_state','State',1,0,0,100,5,$useraddressblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'address_postalcode','".$table_prefix."_users',1,'1','address_postalcode','Postal Code',1,0,0,100,4,$useraddressblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'address_country','".$table_prefix."_users',1,'1','address_country','Country',1,0,0,100,2,$useraddressblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'accesskey','".$table_prefix."_users',1,3,'accesskey','Webservice Access Key',1,0,0,100,2,$useradvanceblock,2,'V~O',1,null,'BAS',1,'')");
		//User Image Information
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'imagename','".$table_prefix."_users',1,'105','imagename','User Image',1,0,0,250,10,$userblockid,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'avatar','".$table_prefix."_users',1,'205','avatar','User Image',1,0,0,250,11,$userblockid,1,'V~O',1,null,'BAS',1,'')");	//crmv@29079
		//added for internl_mailer
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'internal_mailer','".$table_prefix."_users',1,'56','internal_mailer','INTERNAL_MAIL_COMPOSER',1,0,0,50,15,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'reminder_interval','".$table_prefix."_users',1,'16','reminder_interval','Reminder Interval',1,0,0,100,3,$calendaruserblock,1,'V~O',1,null,'BAS',1,'')");	//crmv@20047
		//user Details End
		//crmv@16326
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'use_ldap','".$table_prefix."_users',1,'56','use_ldap','Ldapuser',1,0,0,100,1,$userloginandroleblockid,1,'C~O',1,null,'BAS',1,'')");
		//crmv@16326 end
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'no_week_sunday','".$table_prefix."_users',1,56,'no_week_sunday','Disable Sunday on Week',1,2,0,100,7,$calendaruserblock,1,'C~O',1,null,'BAS',1,'')");	//crmv@20607
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'weekstart','".$table_prefix."_users',1,215,'weekstart','WeekStartDay',1,2,0,100,8,$calendaruserblock,1,'V~O',1,null,'BAS',1,'')");	//crmv@150808
		$holidayFieldid = $this->db->getUniqueID($table_prefix."_field"); // crmv@201442
		$this->db->query("insert into ".$table_prefix."_field values (29,".$holidayFieldid.",'holiday_countries','".$table_prefix."_users',1,311,'holiday_countries','Holiday Countries',1,2,0,100,9,$calendaruserblock,1,'V~O',1,null,'BAS',1,'')");	//crmv@201442
		
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'menu_view','".$table_prefix."_users',1,15,'menu_view','Menu View',1,2,0,100,12,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");	//crmv@22622 crmv@97209
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'default_module','".$table_prefix."_users',1,201,'default_module','Default Module',1,2,0,100,13,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");	//crmv@26523
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'default_language','".$table_prefix."_users',1,202,'default_language','DefaultLanguage',1,2,0,100,14,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");	//crmv@26809
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'default_theme','".$table_prefix."_users',1,203,'default_theme','DefaultTheme',1,2,0,100,15,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");	//crmv@26809
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'dark_mode','".$table_prefix."_users',1,56,'dark_mode','DarkMode',1,2,0,100,16,$userloginandroleblockid,1,'C~O',1,null,'BAS',1,'')"); // crmv@187406
		//crmv@29506
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'allow_generic_talks','".$table_prefix."_users',1,'56','allow_generic_talks','Allow generic talks',1,0,0,100,17,$userloginandroleblockid,1,'C~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'receive_public_talks','".$table_prefix."_users',1,'56','receive_public_talks','Receive public talks',1,0,0,100,18,$userloginandroleblockid,1,'C~O',1,null,'BAS',1,'')");
		//crmv@29506e
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'notify_me_via','".$table_prefix."_users',1,15,'notify_me_via','Notify me via',1,2,0,100,19,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");	//crmv@29617
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'user_timezone','".$table_prefix."_users',1,15,'user_timezone','Timezone',1,2,0,100,20,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'')");	//crmv@25610
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'notify_summary','".$table_prefix."_users',1,15,'notify_summary','Notification Summary',1,2,0,100,21,$userloginandroleblockid,1,'V~O',1,null,'BAS',1,'LBL_NOT_SUMMARY_INFO')");	//crmv@29617 //crmv@33465
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'enable_activesync','".$table_prefix."_users',1,56,'enable_activesync','Enable ActiveSync',1,2,0,100,22,$userloginandroleblockid,1,'C~O',1,null,'BAS',1,'')");	//crmv@34873
		// crmv@42024
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'decimal_separator','".$table_prefix."_users',1,15,'decimal_separator','DecimalSeparator',1,2,0,100,16,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'thousands_separator','".$table_prefix."_users',1,15,'thousands_separator','ThousandsSeparator',1,2,0,100,17,$usermoreinfoblock,1,'V~O',1,null,'BAS',1,'')");
		$this->db->query("insert into ".$table_prefix."_field values (29,".$this->db->getUniqueID($table_prefix."_field").",'decimals_num','".$table_prefix."_users',1,7,'decimals_num','DecimalsNumber',1,2,0,100,18,$usermoreinfoblock,1,'I~O',1,null,'BAS',1,'')");
		// crmv@42024e

		$tab_field_array = array(
		'Accounts'=>array('accountname'),
		'Contacts'=>array('imagename'),
		'Products'=>array('imagename','product_id'),
		'Invoice'=>array('invoice_no','salesorder_id'),
		'SalesOrder'=>array('quote_id','salesorder_no'),
		'PurchaseOrder'=>array('purchaseorder_no'),
		'Quotes'=>array('quote_no'),
		'HelpDesk'=>array('filename'),
		);
		foreach($tab_field_array as $index=>$value){
			$tabid = getTabid($index);
			$this->db->pquery("UPDATE ".$table_prefix."_field SET masseditable=0 WHERE tabid=? AND fieldname IN (".generateQuestionMarks($value).")",array($tabid,$value));
		}

		// crmv@155585
		//Emails field added here
		$email_Tabid = getTabid('Emails');
		$this->db->query("INSERT INTO ".$table_prefix."_field values($email_Tabid,".$this->db->getUniqueID($table_prefix."_field").",'from_email','".$table_prefix."_emaildetails',1,12,'from_email','From',1,2,0,100,1,$email_block,1,'V~M',3,NULL,'BAS',0,'')");	//crmv@2051m
		$this->db->query("INSERT INTO ".$table_prefix."_field values($email_Tabid,".$this->db->getUniqueID($table_prefix."_field").",'to_email','".$table_prefix."_emaildetails',1,8,'saved_toid','To',1,2,0,100,2,$email_block,1,'V~M',3,NULL,'BAS',0,'')");
		$this->db->query("INSERT INTO ".$table_prefix."_field values($email_Tabid,".$this->db->getUniqueID($table_prefix."_field").",'cc_email','".$table_prefix."_emaildetails',1,8,'ccmail','CC',1,2,0,1000,3,$email_block,1,'V~O',3,NULL,'BAS',0,'')");
		$this->db->query("INSERT INTO ".$table_prefix."_field values($email_Tabid,".$this->db->getUniqueID($table_prefix."_field").",'bcc_email','".$table_prefix."_emaildetails',1,8,'bccmail','BCC' ,1,2,0,1000,4,$email_block,1,'V~O',3,NULL,'BAS',0,'')");
		$this->db->query("INSERT INTO ".$table_prefix."_field values($email_Tabid,".$this->db->getUniqueID($table_prefix."_field").",'idlists','".$table_prefix."_emaildetails',1,357,'parent_id','Parent ID' ,1,2,0,1000,5,$email_block,1,'V~O',3,NULL,'BAS',0,'')");
		$this->db->query("INSERT INTO ".$table_prefix."_field values($email_Tabid,".$this->db->getUniqueID($table_prefix."_field").",'email_flag','".$table_prefix."_emaildetails',1,16,'email_flag','Email Flag' ,1,2,0,1000,6,$email_block,3,'V~O',3,NULL,'BAS',0,'')");
		$this->db->query("INSERT INTO ".$table_prefix."_field values($email_Tabid,".$this->db->getUniqueID($table_prefix."_field").",'send_mode','".$table_prefix."_emaildetails',1,1,'send_mode','Send Mode' ,1,2,0,100,7,$email_block,1,'V~O',3,NULL,'BAS',0,'LBL_SEND_MODE_INFO')");	//crmv@26639
		//Emails fields ends
		// crmv@155585e
		
		// insert into fieldinfo
		// crmv@201442
		$holidayInfo = json_encode(array(
			'show_flags' => true, 
			'default' => 'IT', 
			'only' => array("AT","AU","BE","BR","CA","CH","CZ","DE","DK","EE","ES","FI","FR","GB","GR","HU","IE","IN","IT","NL","LT","LV","NO","PL","PT","RU","SE","SK","US")
		));
		$this->db->pquery("INSERT INTO {$table_prefix}_fieldinfo (fieldid, info) VALUES (?,?)", array($holidayFieldid, $holidayInfo));
		// crmv@201442e

		//The Entity Name for the modules are maintained in this table
		$entityNames = array(
			array(7,'Leads',		$table_prefix."_leaddetails",	'lastname,firstname','leadid','leadid'),
			array(6,'Accounts',		$table_prefix."_account",		'accountname','accountid','account_id'),
			array(4,'Contacts',		$table_prefix."_contactdetails",'lastname,firstname','contactid','contact_id'),
			array(2,'Potentials',	$table_prefix."_potential",		'potentialname','potentialid','potential_id'),
			array(8,'Documents',	$table_prefix."_notes",			'notes_title','notesid','notesid'),	//crmv@24944
			array(13,'HelpDesk',	$table_prefix."_troubletickets",'ticket_title','ticketid','ticketid'),	//crmv@24944
			array(9,'Calendar',		$table_prefix."_activity",		'subject','activityid','activityid'),
			array(10,'Emails',		$table_prefix."_activity",		'subject','activityid','activityid'),
			array(16,'Events',		$table_prefix."_activity",		'subject','activityid','activityid'), // crmv@100399
			array(14,'Products',	$table_prefix."_products",		'productname','productid','product_id'),
			array(29,'Users',		$table_prefix."_users",			'last_name,first_name','id','id'),
			array(23,'Invoice',		$table_prefix."_invoice",		'subject','invoiceid','invoiceid'),
			array(20,'Quotes',		$table_prefix."_quotes",		'subject','quoteid','quote_id'),
			array(21,'PurchaseOrder',	$table_prefix."_purchaseorder",'subject','purchaseorderid','purchaseorderid'),
			array(22,'SalesOrder',	$table_prefix."_salesorder",	'subject','salesorderid','salesorder_id'),
			array(18,'Vendors',		$table_prefix."_vendor",		'vendorname','vendorid','vendor_id'),
			array(19,'PriceBooks',	$table_prefix."_pricebook",		'bookname','pricebookid','pricebookid'),
			array(26,'Campaigns',	$table_prefix."_campaign",		'campaignname','campaignid','campaignid'),
			array(15,'Faq',			$table_prefix."_faq",			'question','id','id'),
		);
		$columns = array('tabid', 'modulename', 'tablename', 'fieldname', 'entityidfield', 'entityidcolumn');
		$this->db->bulkInsert($table_prefix.'_entityname', $columns, $entityNames);


		//Inserting values into org share action mapping
		$orgShareActMap = array(
			array(0,'Public: Read Only'),
			array(1,'Public: Read, Create/Edit'),
			array(2,'Public: Read, Create/Edit, Delete'),
			array(3,'Private'),
			array(4,'Hide Details'),
			array(5,'Hide Details and Add Events'),
			array(6,'Show Details'),
			array(7,'Show Details and Add Events'),
			array(8,'Assigned'),	//crmv@61173
		);
		$columns = array('share_action_id', 'share_action_name');
		$this->db->bulkInsert($table_prefix.'_org_share_act_mapping', $columns, $orgShareActMap);


		//Inserting for all v_tabs
		$orgShareActTab = array();
        $def_org_tabid= Array(2,4,6,7,8,9,10,13,14,16,20,21,22,23,26); // crmv@109663
        foreach($def_org_tabid as $def_tabid) {
			$orgShareActTab[] = array(0, $def_tabid);
			$orgShareActTab[] = array(1, $def_tabid);
			$orgShareActTab[] = array(2, $def_tabid);
			$orgShareActTab[] = array(3, $def_tabid);
        }
        $columns = array('share_action_id', 'tabid');
		$this->db->bulkInsert($table_prefix.'_org_share_action2tab', $columns, $orgShareActTab);


		//Insert into default_org_sharingrule
		$orgShare = array(
			array(0,2,2,0),
			array(0,4,2,2),
			array(0,6,2,0),
			array(0,7,2,0),
			array(0,9,3,0),
			array(0,13,2,0),
			array(0,14,2,0),	//crmv@100731
			array(0,16,3,2),
			array(0,20,2,0),
			array(0,21,2,0),
			array(0,22,2,0),
			array(0,23,2,0),
			array(0,26,2,0),
			array(0,8,2,0),
		);
		// calculate the ids in advance
		$ruleids = $this->db->getMultiUniqueID($table_prefix.'_def_org_share', count($orgShare));
		foreach ($orgShare as $idx => $rule) $orgShare[$idx][0] = $ruleids[$idx];

		$columns = array('ruleid', 'tabid', 'permission', 'editstatus');
		$this->db->bulkInsert($table_prefix.'_def_org_share', $columns, $orgShare);


		//Populating the DataShare Related Modules
		$dataShare = array(
			//Account Related Module
			array(0,6,2),
			array(0,6,13),
			array(0,6,20),
			array(0,6,22),
			array(0,6,23),
			//Potential Related Module
			array(0,2,20),
			array(0,2,22),
			//Quote Related Module
			array(0,20,22),
			//SO Related Module
			array(0,22,23),
		);
		// calculate the ids in advance
		$ruleids = $this->db->getMultiUniqueID($table_prefix.'_datashare_relmod', count($dataShare));
		foreach ($dataShare as $idx => $rule) $dataShare[$idx][0] = $ruleids[$idx];

		$columns = array('datashare_relatedmodule_id', 'tabid', 'relatedto_tabid');
		$this->db->bulkInsert($table_prefix.'_datashare_relmod', $columns, $dataShare);

		$advRuleRel = array(
			//Account Related Module
			array(0,6,2),
			array(0,6,13),
			array(0,6,20),
			array(0,6,22),
			array(0,6,23),
			//Potential Related Module
			array(0,2,20),
			array(0,2,22),
			//Quote Related Module
			array(0,20,22),
			//SO Related Module
			array(0,22,23),
		);
		// calculate the ids in advance
		$ruleids = $this->db->getMultiUniqueID('tbl_s_advrule_relmodlist', count($advRuleRel));
		foreach ($advRuleRel as $idx => $rule) $advRuleRel[$idx][0] = $ruleids[$idx];

		$columns = array('datashare_relatedmodule_id', 'tabid', 'relatedto_tabid');
		$this->db->bulkInsert('tbl_s_advrule_relmodlist', $columns, $advRuleRel);




	//insert into related list ".$table_prefix."_table
	//Inserting for ".$table_prefix."_account related lists
	// crmv@31780 - rimozione "add" per get_history

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("Contacts").",'get_dependents_list',1,'Contacts',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("Potentials").",'get_dependents_list',2,'Potentials',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("Quotes").",'get_dependents_list',3,'Quotes',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("SalesOrder").",'get_dependents_list',4,'Sales Order',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("Invoice").",'get_dependents_list',5,'Invoice',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("Calendar").",'get_activities',6,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("Documents").",'get_attachments',9,'Documents',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("HelpDesk").",'get_tickets',10,'HelpDesk',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Accounts").",".getTabid("Products").",'get_products',11,'Products',0,'select')");

	//Inserting Lead Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Leads").",".getTabid("Calendar").",'get_activities',1,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Leads").",".getTabid("Documents").",'get_attachments',4,'Documents',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Leads").",".getTabid("Products").",'get_related_list',5,'Products',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Leads").",".getTabid("Campaigns").",'get_campaigns_newsletter',6,'Campaigns',0,'select')"); // crmv@38798
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Leads").",".getTabid("HelpDesk").",'get_dependents_list',7,'HelpDesk',0,'ADD')"); // crmv@56233

	//Inserting for contact related lists
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("Potentials").",'get_dependents_list',1,'Potentials',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("Calendar").",'get_activities',2,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("HelpDesk").",'get_dependents_list',4,'HelpDesk',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("Quotes").",'get_dependents_list',5,'Quotes',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("PurchaseOrder").",'get_dependents_list',6,'PurchaseOrder',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("SalesOrder").",'get_dependents_list',7,'Sales Order',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("Products").",'get_related_list',8,'Products',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("Documents").",'get_attachments',10,'Documents',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Contacts").",".getTabid("Campaigns").",'get_campaigns_newsletter',11,'Campaigns',0,'select')"); // crmv@38798
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid('Contacts').",".getTabid('Invoice').",'get_dependents_list',12,'Invoice',0, 'add')");

	//Inserting Potential Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Potentials").",".getTabid("Calendar").",'get_activities',1,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Potentials").",".getTabid("Contacts").",'get_contacts',2,'Contacts',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Potentials").",".getTabid("Products").",'get_related_list',3,'Products',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Potentials").",".getTabid("Documents").",'get_attachments',5,'Documents',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Potentials").",".getTabid("Quotes").",'get_dependents_list',6,'Quotes',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Potentials").",".getTabid("SalesOrder").",'get_dependents_list',7,'Sales Order',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Potentials").",".getTabid("Accounts").",'get_related_list',8,'Accounts',0,'select')");  // crmv@44187

	//Inserting Product Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("HelpDesk").",'get_dependents_list',1,'HelpDesk',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Documents").",'get_attachments',3,'Documents',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Quotes").",'get_dependents_list',4,'Quotes',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("PurchaseOrder").",'get_dependents_list',5,'PurchaseOrder',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("SalesOrder").",'get_dependents_list',6,'Sales Order',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Invoice").",'get_dependents_list',7,'Invoice',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("PriceBooks").",'get_product_pricebooks',8,'PriceBooks',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Leads").",'get_related_list',9,'Leads',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Accounts").",'get_related_list',10,'Accounts',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Contacts").",'get_related_list',11,'Contacts',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Potentials").",'get_related_list',12,'Potentials',0,'select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Products").",'get_products',13,'Product Bundles',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Products").",".getTabid("Products").",'get_parent_products',14,'Parent Product',0,'')");

	//Inserting HelpDesk Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("HelpDesk").",".getTabid("Calendar").",'get_activities',1,'Activities',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("HelpDesk").",".getTabid("Documents").",'get_attachments',2,'Documents',0,'add,select')");

	//Inserting PriceBook Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("PriceBooks").",14,'get_pricebook_products',2,'Products',0,'select')");

    // Inserting Vendor Related Lists
    $this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Vendors").",14,'get_dependents_list',1,'Products',0,'add,select')");
    $this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Vendors").",21,'get_dependents_list',2,'PurchaseOrder',0,'add')");
    $this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Vendors").",4,'get_dependents_list',3,'Contacts',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Vendors").",".getTabid("Documents").",'get_attachments',4,'Documents',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Vendors").",".getTabid("Calendar").",'get_activities',5,'Activities',0,'add,select')");	//crmv@94717

	// Inserting Quotes Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Quotes").",".getTabid("SalesOrder").",'get_salesorder',1,'Sales Order',0,'')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Quotes").",9,'get_activities',2,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Quotes").",".getTabid("Documents").",'get_attachments',3,'Documents',0,'add,select')");

	// Inserting Purchase order Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("PurchaseOrder").",9,'get_activities',1,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("PurchaseOrder").",".getTabid("Documents").",'get_attachments',2,'Documents',0,'add,select')");

	// Inserting Sales order Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("SalesOrder").",9,'get_activities',1,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("SalesOrder").",".getTabid("Documents").",'get_attachments',2,'Documents',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("SalesOrder").",".getTabid("Invoice").",'get_invoices',3,'Invoice',0,'')");

	// Inserting Invoice Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Invoice").",9,'get_activities',1,'Activities',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Invoice").",".getTabid("Documents").",'get_attachments',2,'Documents',0,'add,select')");

	// Inserting Activities Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Calendar").",0,'get_users',1,'Users',0,'')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Calendar").",4,'get_contacts',2,'Contacts',0,'')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Calendar").",".getTabid("Documents").",'get_attachments',3,'Documents',0,'add,select')"); // crmv@186446
	
	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",16,".getTabid("Documents").",'get_attachments',0,'Documents',0,'add,select')"); // crmv@186446

	// Inserting Campaigns Related Lists

	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Campaigns").",".getTabid("Contacts").",'get_contacts',1,'Contacts',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Campaigns").",".getTabid("Leads").",'get_leads',2,'Leads',0,'add,select')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Campaigns").",".getTabid("Potentials").",'get_dependents_list',3,'Potentials',0,'add')");
	$this->db->query("insert into ".$table_prefix."_relatedlists values(".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Campaigns").",".getTabid("Calendar").",'get_activities',4,'Activities',0,'add')");

	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').", ".getTabid("Accounts").", ".getTabid("Campaigns").", 'get_campaigns_newsletter', 13, 'Campaigns', 0, 'select')"); // crmv@38798
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').", ".getTabid("Campaigns").", ".getTabid("Accounts").", 'get_accounts', 6, 'Accounts', 0, 'add,select')");

	// Inserting Faq's Related Lists
	$this->db->query("insert into ".$table_prefix."_relatedlists values (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Faq").",".getTabid("Documents").",'get_attachments',1,'Documents',0,'add,select')");

	// crmv documents relatedlists
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Accounts").",'get_documents_dependents_list',1,'Accounts',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Leads").",'get_documents_dependents_list',2,'Leads',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Contacts").",'get_documents_dependents_list',3,'Contacts',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Potentials").",'get_documents_dependents_list',4,'Potentials',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Products").",'get_documents_dependents_list',5,'Products',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("HelpDesk").",'get_documents_dependents_list',7,'HelpDesk',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Quotes").",'get_documents_dependents_list',8,'Quotes',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("PurchaseOrder").",'get_documents_dependents_list',9,'PurchaseOrder',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("SalesOrder").",'get_documents_dependents_list',10,'SalesOrder',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Invoice").",'get_documents_dependents_list',11,'Invoice',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Faq").",'get_documents_dependents_list',13,'Faq',0,'select,add')");
	$this->db->query("INSERT INTO ".$table_prefix."_relatedlists VALUES (".$this->db->getUniqueID($table_prefix.'_relatedlists').",".getTabid("Documents").",".getTabid("Vendors").",'get_documents_dependents_list',14,'Vendors',0,'select,add')");

	//crmv@38592 - removed get_emails

	//crmv@20811
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_TASK_NOTIFICATION_DESCRITPION',1,'Notifica ritardo','Ritardo di oltre 24h','LBL_TASK_NOTIFICATION')");
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_BIG_DEAL_DESCRIPTION' ,1,'Affarone!','Siamo riusciti a fare una grossa vendita, complimenti a tutti!','LBL_BIG_DEAL')");
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_TICKETS_DESCRIPTION',1,'Notifica ticket pendenti','Il ticket e` in approvazione.','LBL_PENDING_TICKETS')");
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_MANY_TICKETS_DESCRIPTION',1,'Troppi ticket pendenti!','Troppi ticket aperti per la stessa entita`.','LBL_MANY_TICKETS')");
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label,type) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_START_DESCRIPTION' ,1,'Support Start Notification','10','LBL_START_NOTIFICATION','select')");
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label,type) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_SUPPORT_DESCRIPTION',1,'Support ending please','11','LBL_SUPPORT_NOTICIATION','select')");
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label,type) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_SUPPORT_DESCRIPTION_MONTH',1,'Support ending please','12','LBL_SUPPORT_NOTICIATION_MONTH','select')");
	$this->db->query("insert into ".$table_prefix."_notifyscheduler(schedulednotificationid,schedulednotificationname,active,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_notifyscheduler").",'LBL_ACTIVITY_REMINDER_DESCRIPTION' ,1,'Notifica promemoria attivita`','LBL_ACTIVITY_REMINDER_DESCRIPTION','LBL_ACTIVITY_NOTIFICATION')");
	//crmv@20811e

	//inserting actions for get_attachments
	addEntityFolder('Documents', 'Default', 'This is a Default Folder', 1, '', 1); // crmv@30967
	addEntityFolder('Documents', 'Message attachments', 'Contains message attachments', 1, '', 2);	//crmv@86304
	
	include('vteversion.php');
	$enterprise_version = $enterprise_mode.' '.$enterprise_current_version;
	$cloud_trial_version = 'http://vtenext.com/vtenext';
	$community_version = 'http://www.vtenext.org';
	$enterprise_company_address1 = 'VTENEXT SRL - Viale Sarca, 336/F - 20126 Milano';
	$enterprise_company_address2 = 'P.I. 09869110966 - Phone (+39) 02-37901352';

	//crmv@20812
	//Inserting Inventory Notifications
	$invoice_body = "Gentile {HANDLER},

La quantita`  in magazzino di {PRODUCTNAME} e` di {CURRENTSTOCK}. Si prega di ordinare un numero di prodotto inferiore al numero indicato di seguito: {REORDERLEVELVALUE}.

Consideri urgente questa comunicazione in quanto la fattura e` gia`  stata inoltrata.

Tipo di urgenza: ALTA

Grazie,
{CURRENTUSER}";
	$this->db->pquery("insert into ".$table_prefix."_inventorynotify(notificationid,notificationname,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_inventorynotify").",'InvoiceNotification','{PRODUCTNAME} Livello magazzino basso',?,'InvoiceNotificationDescription')",array($invoice_body));

	$quote_body = "Gentile {HANDLER},

Preventivo per un quantitativo di {QUOTEQUANTITY} {PRODUCTNAME}. L'attuale giacienza di {PRODUCTNAME} e` di {CURRENTSTOCK}.

Tipo di urgenza: BASSA

Grazie,
{CURRENTUSER}";
	$this->db->pquery("insert into ".$table_prefix."_inventorynotify(notificationid,notificationname,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_inventorynotify").",'QuoteNotification','Preventivo per {PRODUCTNAME}',?,'QuoteNotificationDescription')",array($quote_body));

	$so_body = "Gentile {HANDLER},

L'ordine di vendita e` stato generato per {SOQUANTITY}  {PRODUCTNAME}. L'attuale giacienza a magazzino di {PRODUCTNAME} e` di {CURRENTSTOCK}.

L'ordine di vendita e` stato generato, i dati devono essere trattati con la massima urgenza.

Tipo di urgenza: ALTA

Grazie,
{CURRENTUSER}";
	$this->db->pquery("insert into ".$table_prefix."_inventorynotify(notificationid,notificationname,notificationsubject,notificationbody,label) values (".$this->db->getUniqueID($table_prefix."_inventorynotify").",'SalesOrderNotification','Ordine di vendita per {PRODUCTNAME}',?,'SalesOrderNotificationDescription')",array($so_body));
	//crmv@20812e

//insert into inventory terms and conditions table
	$inv_tandc_text='';
	$this->db->query("insert into ".$table_prefix."_inventory_tandc(id,type,tandc) values (".$this->db->getUniqueID($table_prefix."_inventory_tandc").", 'Inventory', '".$inv_tandc_text."')");
//insert into email template vte_table

	//crmv@20774	//crmv@22700
	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<div id="gt-res-content">
												<div dir="ltr">
												<p><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Lo Staff </span><span title="Fai clic per visualizzare le traduzioni alternative">CRMVILLAGE.BIZ</span> <span title="Fai clic per visualizzare le traduzioni alternative">&egrave;</span> <span title="Fai clic per visualizzare le traduzioni alternative">lieto</span> <span title="Fai clic per visualizzare le traduzioni alternative">di</span> <span title="Fai clic per visualizzare le traduzioni alternative">annunciare il rilascio di</span> VTE 4<span title="Fai clic per visualizzare le traduzioni alternative">.</span> <span title="Fai clic per visualizzare le traduzioni alternative">Una delle nuove caratteristi pi&ugrave; di spicco &egrave; sicuramente </span><span title="Fai clic per visualizzare le traduzioni alternative"> la</span> <span title="Fai clic per visualizzare le traduzioni alternative">gestione</span> del <span title="Fai clic per visualizzare le traduzioni alternative">template</span> <span title="Fai clic per visualizzare le traduzioni alternative">per l&#39;invio </span> <span title="Fai clic per visualizzare le traduzioni alternative">e-mail di massa</span><span title="Fai clic per visualizzare le traduzioni alternative">,</span> <span title="Fai clic per visualizzare le traduzioni alternative">funzionalit&agrave;</span> <span title="Fai clic per visualizzare le traduzioni alternative">che permette una visualizzazione personalizzata di tutte le email/newsletter... ma non solo... venite a scoprirlo in CRMVILLAGE</span>!<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Ecco un elenco di alcune nuove caratteristiche degne di nota:</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">Integrazione</span><span title="Fai clic per visualizzare le traduzioni alternative">-mail</span> <span title="Fai clic per visualizzare le traduzioni alternative">client</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">Integrazione</span> <span title="Fai clic per visualizzare le traduzioni alternative">Trouble</span> <span title="Fai clic per visualizzare le traduzioni alternative">Ticket</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">Gestore Integrato Fatture</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">Rapporti</span> <span title="Fai clic per visualizzare le traduzioni alternative">di integrazione</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">Integrazione</span> <span title="Fai clic per visualizzare le traduzioni alternative">Portal</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">Word</span> <span title="Fai clic per visualizzare le traduzioni alternative">avanzato</span> <span title="Fai clic per visualizzare le traduzioni alternative"> per i plugin</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">Visualizzazione personalizzata generale</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Problemi noti</span><span title="Fai clic per visualizzare le traduzioni alternative">:</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">ABCD</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">EFGH</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">IJKL</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">mnop</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">-</span><span title="Fai clic per visualizzare le traduzioni alternative">QRST</span></span></p>
												</div>
												</div>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Nuova Release in uscita','Nuova Release in uscita','Nuova Release in uscita','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<div id="gt-res-content">
												<div dir="ltr">
												<p><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Nome</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Cognome</span></span></p>

												<p><span lang="it" xml:lang="it">Via<br />
												Citt&agrave;<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Provincia</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">CAP</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Si prega di</span> <span title="Fai clic per visualizzare le traduzioni alternative">verificare</span> <span title="Fai clic per visualizzare le traduzioni alternative">le</span> <span title="Fai clic per visualizzare le traduzioni alternative">seguenti fatture</span><span title="Fai clic per visualizzare le traduzioni alternative">, che</span> <span title="Fai clic per visualizzare le traduzioni alternative">devono ancora essere</span> <span title="Fai clic per visualizzare le traduzioni alternative">pagate:</span></span></p>

												<table border="0" width="100%">
													<tbody>
														<tr>
															<td><strong>No</strong></td>
															<td><strong>Data</strong></td>
															<td><strong>Importo</strong></td>
														</tr>
														<tr>
															<td>1</td>
															<td>1 / 1 / 01</td>
															<td>4000 &euro;uro</td>
														</tr>
														<tr>
															<td>2</td>
															<td>2 / 2 / 01</td>
															<td>5000 &euro;uro</td>
														</tr>
														<tr>
															<td>3</td>
															<td>3 / 3 / 01</td>
															<td>10000 &euro;uro</td>
														</tr>
														<tr>
															<td>4</td>
															<td>4 / 4 / 01</td>
															<td>23560 &euro;uro</td>
														</tr>
													</tbody>
												</table>

												<p><span lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Cortesemente</span> <span title="Fai clic per visualizzare le traduzioni alternative">ci faccia sapere</span> <span title="Fai clic per visualizzare le traduzioni alternative">se ci sono</span> <span title="Fai clic per visualizzare le traduzioni alternative">problemi per i pagamenti. Saremo felici di appianare ogni divergenza a riguardo. Siamo contenti dei risultati raggiunti sino ad ora con la vostra societ&agrave; e vorremmo continuare lo splendido rapporto sin qui avuto</span></span>.</p>
												</div>
												</div>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Fatture non pagate','Fatture non pagate','Pagamento dovuto','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<div id="gt-res-content">
												<div dir="ltr"><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">La vostra proposta</span> <span title="Fai clic per visualizzare le traduzioni alternative">sul</span> <span title="Fai clic per visualizzare le traduzioni alternative">progetto</span> <span title="Fai clic per visualizzare le traduzioni alternative">XYZW</span> <span title="Fai clic per visualizzare le traduzioni alternative">&egrave;</span> <span title="Fai clic per visualizzare le traduzioni alternative">stata valutata ed </span><span title="Fai clic per visualizzare le traduzioni alternative">accettata n</span><span title="Fai clic per visualizzare le traduzioni alternative">ella sua</span> <span title="Fai clic per visualizzare le traduzioni alternative">interezza.</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Siamo ansiosi di veder partire </span><span title="Fai clic per visualizzare le traduzioni alternative">questo</span> nuovo <span title="Fai clic per visualizzare le traduzioni alternative">progetto</span> <span title="Fai clic per visualizzare le traduzioni alternative">e</span> <span title="Fai clic per visualizzare le traduzioni alternative">siamo lieti</span> <span title="Fai clic per visualizzare le traduzioni alternative">di avere</span> <span title="Fai clic per visualizzare le traduzioni alternative">l&#39;opportunit&agrave;</span> <span title="Fai clic per visualizzare le traduzioni alternative">di lavorare</span> <span title="Fai clic per visualizzare le traduzioni alternative">assieme</span><span title="Fai clic per visualizzare le traduzioni alternative"> alla vostra zienda. Confessiamo che aspettavamo da un po&#39; questa opportunit&agrave; ed ora che si &egrave; presentata, ne siamo davvero orgogliosi.</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Cogliamo </span><span title="Fai clic per visualizzare le traduzioni alternative"> l&#39;occasione per</span> <span title="Fai clic per visualizzare le traduzioni alternative">invitarvi</span> <span title="Fai clic per visualizzare le traduzioni alternative">per</span> <span title="Fai clic per visualizzare le traduzioni alternative">una partita di golf</span> <span title="Fai clic per visualizzare le traduzioni alternative">alle</span> <span title="Fai clic per visualizzare le traduzioni alternative">09:00</span> di <span title="Fai clic per visualizzare le traduzioni alternative">Mercoled&igrave; mattina</span> <span title="Fai clic per visualizzare le traduzioni alternative">presso</span> <span title="Fai clic per visualizzare le traduzioni alternative">la</span> <span title="Fai clic per visualizzare le traduzioni alternative">Gemelli</span> <span title="Fai clic per visualizzare le traduzioni alternative">Ground</span><span title="Fai clic per visualizzare le traduzioni alternative"> per avere l&#39;opportunit&agrave; di conoscerci meglio.</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Nel frattempo porgiamo cordiali saluti.</span></span></div>
												</div>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Proposta accettata','Proposta accettata','Proposta accettata','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body= '<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<div id="gt-res-content">
												<div dir="ltr"><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Il</span> <span title="Fai clic per visualizzare le traduzioni alternative">sottoscritto </span> <span title="Fai clic per visualizzare le traduzioni alternative">conferma la ricezione</span> <span title="Fai clic per visualizzare le traduzioni alternative">della merce</span><span title="Fai clic per visualizzare le traduzioni alternative"> e si impegna a provvedere al</span><span title="Fai clic per visualizzare le traduzioni alternative"> pagamento</span> <span title="Fai clic per visualizzare le traduzioni alternative">della stessa quanto prima.</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Firmato</span> Mario Rossi</span></div>
												</div>

												<p>&nbsp;</p>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Merce ricevuta','Merce ricevuta','Merce ricevuta e pagamento','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body= '<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<div id="gt-res-content">
												<div dir="ltr"><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Abbiamo ricevuto il vostro ordine. L&#39;ordine &egrave; definitivo e vincolante per entrambe le parti.</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Per recedere dall&#39;ordine avete 10gg lavorativi di tempo e potete farlo inoltrando un&#39;email a '.$enterprise_website[2].', annotando il numero d&#39;ordine e la volont&agrave; di recedervi.</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Grazie per l&#39;attenzione e la preferenza accordataci.</span></span></div>
												</div>

												<p>&nbsp;</p>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Ordine accettato','Ordine accettato','Conferma ordine e diritto di recesso','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<div id="gt-res-content">
												<div dir="ltr"><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Ci stiamo</span> <span title="Fai clic per visualizzare le traduzioni alternative">trasferendo</span> nella nuova sede che si trova al seguente indirizzo:<br />
												<strong>Via Ciro Menotti 3, c/o Via Fontanelle - San Bonifacio (VR), 37047</strong><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Il nuovo numero</span> <span title="Fai clic per visualizzare le traduzioni alternative">di telefono</span> <span title="Fai clic per visualizzare le traduzioni alternative">&egrave;</span> 045 51 11 073<br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Aggiornate i vostri contatti e continuate a seguirci!</span></span></div>
												</div>

												<p>&nbsp;</p>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Cambio indirizzo','Cambio indirizzo','Cambio indirizzo','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<p><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">La presente per ringraziarvi per </span><span title="Fai clic per visualizzare le traduzioni alternative"> l&#39;opportunit&agrave; che ci avete dato di</span> potervi <span title="Fai clic per visualizzare le traduzioni alternative">incontrare</span> di persona.<br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Sappiamo</span> <span title="Fai clic per visualizzare le traduzioni alternative">che</span> <span title="Fai clic per visualizzare le traduzioni alternative">Mario Rossi </span> <span title="Fai clic per visualizzare le traduzioni alternative">&egrave; rimasto per molto tempo nella vostra azienda ed ha </span><span title="Fai clic per visualizzare le traduzioni alternative">personalmente</span> <span title="Fai clic per visualizzare le traduzioni alternative">discusso</span> <span title="Fai clic per visualizzare le traduzioni alternative">con noi la sua scelta. L</span><span title="Fai clic per visualizzare le traduzioni alternative">a sua profonda relazione</span> ed amicizia <span title="Fai clic per visualizzare le traduzioni alternative">che</span> <span title="Fai clic per visualizzare le traduzioni alternative">aveva</span> <span title="Fai clic per visualizzare le traduzioni alternative">con i membri della vostra</span> <span title="Fai clic per visualizzare le traduzioni alternative">azienda</span> &egrave; stata qualcosa di unico.<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Ci mancher&agrave; moltissimo e siamo sicuri che continuer&agrave; a</span> fornire un prezioso servizio anche nella vostra societ&agrave;.<br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">La vostra ospitalit&agrave; e la partenza del Sig.Rossi ci hanno veramente commosso.</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Vi ringraziamo</span> <span title="Fai clic per visualizzare le traduzioni alternative">ancora una volta</span><span title="Fai clic per visualizzare le traduzioni alternative">.</span></span></p>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Successione','Successione','Incontro','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<p><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Congratulazioni</span><span title="Fai clic per visualizzare le traduzioni alternative">!</span><br />
												<br />
												Siamo orgogliosi di annunciare che il totale vendite sino ad ora raggiunto, ammonta a 100.000,00 &euro;uro.</span></p>

												<p><span lang="it" xml:lang="it">Pe <span title="Fai clic per visualizzare le traduzioni alternative"> la</span> <span title="Fai clic per visualizzare le traduzioni alternative">prima volta</span> <span title="Fai clic per visualizzare le traduzioni alternative">abbiamo</span> <span title="Fai clic per visualizzare le traduzioni alternative">superato</span> <span title="Fai clic per visualizzare le traduzioni alternative">l&#39;obiettivo</span> <span title="Fai clic per visualizzare le traduzioni alternative">di quasi il</span> <span title="Fai clic per visualizzare le traduzioni alternative">30</span><span title="Fai clic per visualizzare le traduzioni alternative">%</span> ed a<span title="Fai clic per visualizzare le traduzioni alternative">bbiamo</span> <span title="Fai clic per visualizzare le traduzioni alternative">anche</span> <span title="Fai clic per visualizzare le traduzioni alternative">battuto il</span> <span title="Fai clic per visualizzare le traduzioni alternative">record</span> <span title="Fai clic per visualizzare le traduzioni alternative">precedente</span> riguardante il <span title="Fai clic per visualizzare le traduzioni alternative">trimestre</span> scorso del <span title="Fai clic per visualizzare le traduzioni alternative"> 75</span><span title="Fai clic per visualizzare le traduzioni alternative">%</span><span title="Fai clic per visualizzare le traduzioni alternative">!</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Dobbiamo festeggiare, questi sono risultati memorabili!</span></span></p>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Obiettivo raggiunto!','Obiettivo raggiunto!','Grande impennata delle vendite!','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px; font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<p><span id="result_box" lang="it" xml:lang="it"><span title="Fai clic per visualizzare le traduzioni alternative">Grazie per</span> <span title="Fai clic per visualizzare le traduzioni alternative">la </span> <span title="Fai clic per visualizzare le traduzioni alternative">fiducia accordataci</span><span title="Fai clic per visualizzare le traduzioni alternative">.</span><br />
												<span title="Fai clic per visualizzare le traduzioni alternative">Siamo lieti di annoverarvi tra i nostri clienti e siamo sicuri che questo sar&agrave; l&#39;inizio di una lunga collaborazione.</span><br />
												<br />
												<span title="Fai clic per visualizzare le traduzioni alternative">In caso di</span> <span title="Fai clic per visualizzare le traduzioni alternative">qualsiasi</span> <span title="Fai clic per visualizzare le traduzioni alternative">necessit&agrave;, la preghiamo di contattarci senza esitazione alcuna.</span></span></p>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Ringraziamenti','Ringraziamenti','E-Mail di ringraziamento','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

//Added for HTML Eemail templates..
//for Customer Portal Login details
	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">Area Riservata</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $contact_name$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">
												<p>Grazie per esserti iscritto<span style="font-weight: bold;">. Ecco le credenziali per l&#39;area riservata:</span></p>

												<table align="center" border="0" cellpadding="10" cellspacing="0" style="width: 300px;" width="75%">
													<tbody>
														<tr>
															<td><br />
															<span style="font-size:12px;"><span style="font-family: lucida sans unicode,lucida grande,sans-serif;">Email : <span style="color:#000000;"><strong> $login_name$</strong></span></span></span></td>
														</tr>
														<tr>
															<td><span style="font-size:12px;"><span style="font-family: lucida sans unicode,lucida grande,sans-serif;">Password: <span style="color:#000000;"><strong> $password$</strong></span></span></span></td>
														</tr>
														<tr>
															<td style="text-align: center; background-color: rgb(204, 204, 204);">$URL$</td>
														</tr>
													</tbody>
												</table>
												</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Dati di registrazione ed accesso','Dati di registrazione ed accesso','Inoltra al cliente i dettagli utente','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	//for Support end notification before a week
	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">Questa email notifica la fine del periodo di assistenza.<br />
												<span style="font-weight: bold;">Priorit&agrave;:</span> Normal<br />
												Il contratto sta per scadere<br />
												Per informazioni contatta '.$enterprise_website[2].'<br />
												<br />
												&nbsp;</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Il contratto di assistenza scade tra una settimana','Il contratto di assistenza scade tra una settimana','Manda una notifica al cliente che il suo contratto di assistenza scade entro una settimana','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);

	//for Support end notification before a month
	$body='<table align="center" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none;" width="700">
	<tbody>
		<tr>
			<td width="10">&nbsp;</td>
			<td>
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="background-color: #f3f3f3; font-family: Arial,Helvetica,sans-serif; font-size: 14px; font-weight: normal; line-height: 25px;" width="100%">
							<tbody>
								<tr>
									<td align="center" rowspan="4">$logo$</td>
									<td align="center">&nbsp;</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: #2c80c8; font-weight: bolder; line-height: 35px;">'.$enterprise_version.'</td>
								</tr>
								<tr>
									<td align="right" style="padding-right: 100px;color: #2c80c8;">'.$enterprise_website[1].'</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);" width="100%">
							<tbody>
								<tr>
									<td valign="top">
									<table border="0" cellpadding="5" cellspacing="0" width="100%">
										<tbody>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">&nbsp;</td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;">Gentile $Contacts||lastname$,</td>
											</tr>
											<tr>
												<td style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;">Questa email notifica la fine del periodo di assistenza.<br />
												<span style="font-weight: bold;">Priorit&agrave;:</span> Normal<br />
												Il contratto sta per scadere<br />
												Per informazioni contatta '.$enterprise_website[2].'<br />
												<br />
												&nbsp;</td>
											</tr>
											<tr>
												<td align="center">&nbsp;</td>
											</tr>
											<tr>
												<td align="right"><br />
												<br />
												<strong style="padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;">Cordialmente</strong></td>
											</tr>
											<tr>
												<td align="right" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;">Lo Staff VTENEXT</td>
											</tr>
											<tr>
												<td align="right"><a href="'.$enterprise_website[0].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);">'.$enterprise_website[1].'</a></td>
											</tr>
											<tr>
												<td>&nbsp;</td>
											</tr>
										</tbody>
									</table>
									</td>
									<td valign="top" width="1%">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table border="0" cellpadding="5" cellspacing="0" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: #666; font-weight: normal; line-height: 15px; background-color: #f3f3f3;" width="100%">
							<tbody>
								<tr>
									<td align="center">'.$enterprise_company_address1.'</td>
								</tr>
								<tr>
									<td align="center">'.$enterprise_company_address2.'</td>
								</tr>
								<tr>
									<td align="center">E-Mail: <a href="mailto:'.$enterprise_website[2].'" style="font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: #666;">'.$enterprise_website[2].'</a></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td width="10">&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Il contratto di assistenza scade tra un mese','Il contratto di assistenza scade tra un mese','Manda una email al cliente un mese prima che scada l''assistenza','".$this->db->getEmptyClob(false)."',0,$id,'Email',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);
	//crmv@20774e	//crmv@22700e

	//crmv@25391
	$body = '<table bgcolor="#f7f7f8" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td width="600">
			<table bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="600">
				<tbody>
					<tr>
						<td>
						<table border="0" cellpadding="0" cellspacing="0" height="19" width="624">
							<tbody>
								<tr>
									<td bgcolor="#f7f7f8" height="10" width="20">&nbsp;</td>
									<td bgcolor="#f7f7f8" style="text-align: center;" width="560">&nbsp;</td>
									<td bgcolor="#f7f7f8" width="34">&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
							<tbody>
								<tr>
									<td bgcolor="#ffffff" colspan="3"><img src="http://www.vtenext.com/newsletter/dilloamico1802/top.png" style="width: 621px; height: 330px;" /></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<table align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="600">
							<tbody>
								<tr>
									<td>
									<p><br />
									<br />
									<span style="color:#444444;"><span style="font-size:16px;"><span style="font-family:verdana,geneva,sans-serif;"><strong>Try VTENEXT and discover the features of the BPM Engine</strong></span></span></span><br />
									&nbsp;</p>

									<p style="text-align: justify;"><span style="color:#333333;"><span style="font-size:14px;"><span style="font-family:verdana,geneva,sans-serif;"><strong>VTENEXT</strong> is the CRM Open Source Enterprise advanced solution with the software <strong>BPM</strong> functions integrated into the CRM.</span></span></span></p>

									<p style="text-align: justify;"><span style="color:#333333;"><span style="font-size:14px;"><span style="font-family:verdana,geneva,sans-serif;">In addition to the CRM operations you can implement your processes and ensure them in an easy way through the intuitive editor of our &quot;Process Manager&quot; module.</span></span></span></p>

									<p style="text-align: justify;"><span style="color:#333333;"><span style="font-size:14px;"><span style="font-family:verdana,geneva,sans-serif;">You have just to draw the process, set the conditions and the actions to the various tasks, test it and enjoy the result.<br />
									<br />
									Plan and implement all the necessary strategies for a successful customer management,from &nbsp;new clients acquisition to the customers loyalization, through an unique environment where you can manage marketing, sales and post sales businesses in a full digital way.</span></span></span></p>

									<p style="text-align: justify;"><span style="font-family:verdana,geneva,sans-serif;"><span style="font-size:14px;">You can try the free <a href="'.$cloud_trial_version.'" target="_blank">Cloud Trial Version</a> or you can install the <a href="'.$community_version.'" target="_blank">Community Version</a>.</span></span></p>

									<p><span style="font-family:verdana,geneva,sans-serif;"><span style="font-size:14px;"><span style="color:#333333;">VTENEXT never leaves you alone! it is even available the APP version for iOS and ANDROID.</span></span></span></p>

									<p style="text-align: justify;"><span style="font-family:verdana,geneva,sans-serif;"><span style="font-size:14px;"><span style="color:#333333;">Try it and it will harder to turn back!</span></span></span><br />
									<br />
									<br />
									<a href="'.$enterprise_website[0].'" target="_blank"><img alt="" src="http://www.vtenext.com/newsletter/dilloamico1802/logo.png" style="width: 150px; height: 57px;" /></a></p>
									</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
					<tr>
						<td>
						<hr /></td>
					</tr>
					<tr>
						<td><img height="1" src="http://www.vtenext.com/newsletter/newsletter_2ore/images/placeholder.gif" width="20" /></td>
					</tr>
					<tr>
						<td align="center"><span style="font-size:12px;"><span style="font-family:verdana,geneva,sans-serif;">&nbsp;<strong>&copy; </strong><strong>VTENEXT</strong><br />
						Viale Fulvio Testi 223, 20162 Milano - (+39) 0237901352<br />
						<a href="'.$enterprise_website[0].'" target="_blank">'.$enterprise_website[1].'</a> - <a href="mailto:'.$enterprise_website[2].'">'.$enterprise_website[2].'</a></span></span></td>
					</tr>
					<tr>
						<td>
						<table align="center" border="0" cellpadding="0" cellspacing="0" height="5" width="618">
							<tbody>
								<tr>
									<td align="center" valign="middle" width="20"><img height="1" src="http://www.vtenext.com/newsletter/newsletter_2ore/images/placeholder.gif" width="20" /><img height="1" src="http://www.vtenext.com/newsletter/newsletter_2ore/images/placeholder.gif" width="20" /></td>
								</tr>
							</tbody>
						</table>
						</td>
					</tr>
				</tbody>
			</table>
			</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>';
	$id = $this->db->getUniqueID($table_prefix.'_emailtemplates');
	$this->db->query("insert into ".$table_prefix."_emailtemplates(foldername,templatename,subject,description,body,deleted,templateid,templatetype,overwrite_message) values ('Public','Tell a friend about VTE','Try VTENEXT and discover the features of the BPM Engine','Try VTENEXT and discover the features of the BPM Engine','".$this->db->getEmptyClob(false)."',0,$id,'Newsletter',1)");
	$this->db->updateClob($table_prefix.'_emailtemplates','body',"templateid=$id",$body);
	//crmv@25391e

	//Insert into vte_organizationdetails vte_table
	$id = $this->db->getUniqueID($table_prefix.'_organizationdetails');
	$this->db->query("insert into ".$table_prefix."_organizationdetails(organizationid,organizationname,address,city,state,country,code,phone,fax,website,logoname,crmv_vat_registration_number) values ({$id},'Acme','12 W. Coyote','San Antonio','Texas','U.S.A.','78201','(210) 280-0000','(210) 280-0001','www.acme.inc','logo.png','1234567890')");

	$actionMapping = array(
		array(0,'Save',0),
		array(1,'EditView',0),
		array(2,'Delete',0),
		array(3,'index',0),
		array(4,'DetailView',0),
		array(5,'Import',0),
		array(6,'Export',0),
		//array(7,'AddBusinessCard',0),
		array(8,'Merge',0),
		array(1,'VendorEditView',1),
		array(4,'VendorDetailView',1),
		array(0,'SaveVendor',1),
		array(2,'DeleteVendor',1),
		array(1,'PriceBookEditView',1),
		array(4,'PriceBookDetailView',1),
		array(0,'SavePriceBook',1),
		array(2,'DeletePriceBook',1),
		array(9,'ConvertLead',0),
		array(1,'DetailViewAjax',1),
		array(1,'QuickCreate',1),
		array(3,'Popup',1),
		array(10,'DuplicatesHandling',0),
		array(4,'DetailViewBlocks',1),
		array(4,'Turbolift',1),
	);
	$columns = array('actionid', 'actionname', 'securitycheck');
	$this->db->bulkInsert($table_prefix.'_actionmapping', $columns, $actionMapping);

	//Insert values for ".$table_prefix."_moduleowners ".$table_prefix."_table which contains the modules and their ".$table_prefix."_users. default user id admin - after 4.2 patch 2
	$module_array = Array('Potentials','Contacts','Accounts','Leads','Documents','Calendar','Emails','HelpDesk','Products','Faq','Vendors','PriceBooks','Quotes','PurchaseOrder','SalesOrder','Invoice','Reports','Campaigns');
	foreach($module_array as $mod)
	{
		$this->db->query("insert into ".$table_prefix."_moduleowners values(".getTabid($mod).",1)");
	}

	$parentTab = array(
		array(1,'My Home Page',1),
		array(2,'Marketing',2),
		array(3,'Sales',3),
		array(4,'Support',4),
		array(5,'Analytics',5),
		array(6,'Inventory',6),
		array(7,'Tools',7),
		array(8,'Settings',8),
	);
	$columns = array('parenttabid', 'parenttab_label', 'sequence');
	$this->db->bulkInsert($table_prefix.'_parenttab', $columns, $parentTab);

	$parentTabRel = array(
		array(1,9,2),
		array(1,3,1),
		array(3,7,1),
		array(3,6,2),
		array(3,4,3),
		array(3,2,4),
		array(3,20,5),
		array(3,22,6),
		array(3,23,7),
		array(3,19,8),
		array(3,8,9),
		array(4,13,1),
		array(4,15,2),
		array(4,6,3),
		array(4,4,4),
		array(4,8,5),
		array(5,1,2),
		array(5,25,1),
		array(6,14,1),
		array(6,18,2),
		array(6,19,3),
		array(6,21,4),
		array(6,22,5),
		array(6,20,6),
		array(6,23,7),
		array(7,24,1),
		array(7,27,2),
		array(7,8,3),
		array(2,26,1),
		array(2,6,2),
		array(2,4,3),
		array(2,7,5),
		array(2,9,6),
		array(4,9,8),
		array(2,8,8),
		array(3,9,11),
	);
	
	$columns = array('parenttabid', 'tabid', 'sequence');
	$this->db->bulkInsert($table_prefix.'_parenttabrel', $columns, $parentTabRel);

	// crmv@140903
	create_tab_data_file();
	create_parenttab_data_file();
	// crmv@140903e

	//add settings page to database starts
	$this->addEntriesForSettings();
	//add settings page to database end

	//Added to populate the default inventory tax informations
	$vatid = $this->db->getUniqueID($table_prefix."_inventorytaxinfo");
	$salesid = $this->db->getUniqueID($table_prefix."_inventorytaxinfo");
	$serviceid = $this->db->getUniqueID($table_prefix."_inventorytaxinfo");
	$this->db->query("insert into ".$table_prefix."_inventorytaxinfo values($vatid,'tax".$vatid."','VAT',4.50,'0')");
	$this->db->query("insert into ".$table_prefix."_inventorytaxinfo values($salesid,'tax".$salesid."','Sales',10.00,'0')");
	$this->db->query("insert into ".$table_prefix."_inventorytaxinfo values($serviceid,'tax".$serviceid."','Service',12.50,'0')");
	//After added these taxes we should add these taxes as columns in ".$table_prefix."_inventoryproductrel table
	$this->db->query("alter table ".$table_prefix."_inventoryproductrel add tax$vatid decimal(7,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventoryproductrel add tax$salesid decimal(7,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventoryproductrel add tax$serviceid decimal(7,3) default NULL");
	// crmv@67929
	$this->db->query("alter table ".$table_prefix."_inventorytotals add tax$vatid decimal(25,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventorytotals add tax$salesid decimal(25,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventorytotals add tax$serviceid decimal(25,3) default NULL");
	// crmv@67929e

	//Added to handle picklist uniqueid for the picklist values
	//$this->db->query("insert into vte_picklistvalues_seq values(1)");

	//Added to populate the default Shipping & Hanlding tax informations
	$shvatid = $this->db->getUniqueID($table_prefix."_shippingtaxinfo");
	$shsalesid = $this->db->getUniqueID($table_prefix."_shippingtaxinfo");
	$shserviceid = $this->db->getUniqueID($table_prefix."_shippingtaxinfo");
	$this->db->query("insert into ".$table_prefix."_shippingtaxinfo values($shvatid,'shtax".$shvatid."','VAT',4.50,'0')");
	$this->db->query("insert into ".$table_prefix."_shippingtaxinfo values($shsalesid,'shtax".$shsalesid."','Sales',10.00,'0')");
	$this->db->query("insert into ".$table_prefix."_shippingtaxinfo values($shserviceid,'shtax".$shserviceid."','Service',12.50,'0')");
	//After added these taxes we should add these taxes as columns in ".$table_prefix."_inventoryshippingrel table
	$this->db->query("alter table ".$table_prefix."_inventoryshippingrel add shtax$shvatid decimal(7,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventoryshippingrel add shtax$shsalesid decimal(7,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventoryshippingrel add shtax$shserviceid decimal(7,3) default NULL");
	// crmv@67929
	$this->db->query("alter table ".$table_prefix."_inventorytotals add shtax$shvatid decimal(25,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventorytotals add shtax$shsalesid decimal(25,3) default NULL");
	$this->db->query("alter table ".$table_prefix."_inventorytotals add shtax$shserviceid decimal(25,3) default NULL");
	// crmv@67929e

	//version file is included here because without including this file version cannot be get
	include('vteversion.php'); // crmv@181168
	$versionid = $this->db->getUniqueID($table_prefix.'_version');
	$this->db->query("insert into ".$table_prefix."_version (id,old_version,current_version,enterprise_project,hash_version) values($versionid,'".$vte_legacy_version."','".$vte_legacy_version."',null,".$this->db->getEmptyClob(true).")");
	$hash_version = file_get_contents('hash_version.txt');
	$this->db->updateClob($table_prefix.'_version','hash_version','id='.$versionid,$hash_version);
	@unlink('hash_version.txt');

	//Register default language Italian
	require_once('vtlib/Vtecrm/Language.php');
	$vtlanguage = new Vtecrm_Language();
	$vtlanguage->register('it_it','IT Italiano','Italian',true,true,true);
	//Register language English
	$vtlanguage = new Vtecrm_Language();
	$vtlanguage->register('en_us','US English','English',false,true,true);

	$this->initWebservices();

	/**
	 * Setup module sequence numbering.
	 */
	$modseq = array(
		'Leads'     =>'LEA',
		'Accounts'  =>'ACC',
		'Campaigns' =>'CAM',
		'Contacts'  =>'CON',
		'Potentials'=>'POT',
		'HelpDesk'  =>'TT',
		'Quotes'    =>'QUO',
		'SalesOrder'=>'SO',
		'PurchaseOrder'=>'PO',
		'Invoice'   =>'INV',
		'Products'  =>'PRO',
		'Vendors'   =>'VEN',
		'PriceBooks'=>'PB',
		'Faq'       =>'FAQ',
		'Documents' =>'DOC'
	);
	foreach($modseq as $modname => $prefix) {
		$this->addInventoryRows(
			array(
				array('semodule'=>$modname, 'active'=>'1','prefix'=>$prefix,'startid'=>'1','curid'=>'1')
			)
		);
	}

	// Adding Sharing Types for Reports
	$this->db->query("insert into ".$table_prefix."_reportvisibility values(1,'Private')");
	$this->db->query("insert into ".$table_prefix."_reportvisibility values(2,'Public')");
	$this->db->query("insert into ".$table_prefix."_reportvisibility values(3,'Shared')");

	//crmv@currencies
//		require('modules/Utilities/Currencies.php');
//		foreach($currencies as $key=>$value){
//			$this->db->query("insert into vte_currencies values(".$this->db->getUniqueID($table_prefix."_currencies").",'$key','".$value[0]."','".$value[1]."')");
//		}
	//crmv@currencies end

    $this->outlook_intializeChanges();	//crmv@392267

		// crmv@83340 - add default blocks
		require_once('include/utils/ModuleHomeView.php');
		$MHW = ModuleHomeView::getInstance('SDK');
		$MHW->deleteAllViews();
		$MHW->populateDefaultViews();
		// crmv@83340e

	}


	function initWebservices(){
		$this->vtws_addEntityInfo();
		$this->vtws_addOperationInfo();
		$this->vtws_addFieldTypeInformation();
	}

	function vtws_addOperationInfo(){
	global $table_prefix;
		$operationMeta = array(
			"login"=>array(
				"include"=>array(
					"include/Webservices/Login.php"
				),
				"handler"=>"vtws_login",
				"params"=>array(
					"username"=>"String",
					"accessKey"=>"String"
				),
				"prelogin"=>1,
				"type"=>"POST"
			),
			"retrieve"=>array(
				"include"=>array(
					"include/Webservices/Retrieve.php"
				),
				"handler"=>"vtws_retrieve",
				"params"=>array(
					"id"=>"String"
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'retrieve', //crmv@170283
			),
			"create"=>array(
				"include"=>array(
					"include/Webservices/Create.php"
				),
				"handler"=>"vtws_create",
				"params"=>array(
					"elementType"=>"String",
					"element"=>"encoded"
				),
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'create', //crmv@170283
			),
			"update"=>array(
				"include"=>array(
					"include/Webservices/Update.php"
				),
				"handler"=>"vtws_update",
				"params"=>array(
					"element"=>"encoded"
				),
				"prelogin"=>0,
				"type"=>"POST"
			),
			"delete"=>array(
				"include"=>array(
					"include/Webservices/Delete.php"
				),
				"handler"=>"vtws_delete",
				"params"=>array(
					"id"=>"String"
				),
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'delete', //crmv@170283
			),
			"sync"=>array(
				"include"=>array(
					"include/Webservices/GetUpdates.php"
				),
				"handler"=>"vtws_sync",
				"params"=>array(
					"modifiedTime"=>"DateTime",
					"elementType"=>"String"
				),
				"prelogin"=>0,
				"type"=>"GET"
			),
			"query"=>array(
				"include"=>array(
					"include/Webservices/Query.php"
				),
				"handler"=>"vtws_query",
				"params"=>array(
					"query"=>"String"
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'query', //crmv@170283
			),
			"logout"=>array(
				"include"=>array(
					"include/Webservices/Logout.php"
				),
				"handler"=>"vtws_logout",
				"params"=>array(
					"sessionName"=>"String"
				),
				"prelogin"=>0,
				"type"=>"POST"
			),
			"listtypes"=>array(
				"include"=>array(
					"include/Webservices/ModuleTypes.php"
				),
				"handler"=>"vtws_listtypes",
				"params"=>array(),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'listtypes', //crmv@170283
			),
			"getchallenge"=>array(
				"include"=>array(
					"include/Webservices/AuthToken.php"
				),
				"handler"=>"vtws_getchallenge",
				"params"=>array(
					"username"=>"String"
				),
				"prelogin"=>1,
				"type"=>"GET"
			),
			"describe"=>array(
				"include"=>array(
					"include/Webservices/DescribeObject.php"
				),
				"handler"=>"vtws_describe",
				"params"=>array(
					"elementType"=>"String"
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'describe', //crmv@170283
			),
			"extendsession"=>array(
				"include"=>array(
					"include/Webservices/ExtendSession.php"
				),
				"handler"=>"vtws_extendSession",
				'params'=>array(),
				"prelogin"=>1,
				"type"=>"POST"
			),
			'convertlead'=>array(
				"include"=>array(
					"include/Webservices/ConvertLead.php"
				),
				"handler"=>"vtws_convertlead",
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'convertlead', //crmv@170283
				'params'=>array(
					'leadId'=>'String',
					'assignedTo'=>'String',
					'accountName'=>'String',
					'avoidPotential'=>'Boolean',
					'potential'=>'Encoded'
				)
			),
			//crmv@24122
			'upload_files_ws'=>array(
				"include"=>array(
					"modules/Emails/ZTVFunctions.php"
				),
				"handler"=>"upload_files_ws",
				"prelogin"=>0,
				"type"=>"POST",
				'params'=>array(
					'record'=>'String',
					'module'=>'String',
					'userid'=>'String',
					'file'=>'Encoded',
					'email_id'=>'String',
					'zimbra_url'=>'String',
					'zimbra_user'=>'String',
				)
			),
			//crmv@24122e
			//crmv@26244
			'updateRecord'=>array(
				"include"=>array(
					"include/Webservices/Update2.php"
				),
				"handler"=>"vtws_update2",
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'update', //crmv@170283
				'params'=>array(
					'id'=>'string',
					'columns'=>'encoded',
				)
			),
			'retrieveInventory'=>array(
				"include"=>array(
					"include/Webservices/RetrieveInventory.php"
				),
				"handler"=>"vtws_retrieve_inventory",
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'retrieveinventory', //crmv@170283
				'params'=>array(
					'id'=>'string',
				)
			),
			//crmv@26244e
			//crmv@2390m
			'revise'=>array(
				"include"=>array(
					"include/Webservices/Revise.php"
				),
				"handler"=>"vtws_revise",
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'revise', //crmv@170283
				'params'=>array(
					'element'=>'encoded',
				)
			),
			'get_labels'=>array(
				"include"=>array(
					"include/Webservices/Language.php"
				),
				"handler"=>"vte_get_labels",
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'getlabels', //crmv@170283
				'params'=>array(
					'username'=>'string',
					'language'=>'string',
					'module'=>'string',
				)
			),
			'get_langs'=>array(
				"include"=>array(
					"include/Webservices/Language.php"
				),
				"handler"=>"vte_get_langs",
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'getlangs', //crmv@170283
				'params'=>array(
					'language'=>'string',
				)
			),
			'login_pwd'=>array(
				"include"=>array(
					"include/Webservices/Login.php"
				),
				"handler"=>"vtws_login_pwd",
				"prelogin"=>1,
				"type"=>"POST",
				'rest_name'=>'loginpwd', //crmv@170283
				'params'=>array(
					'username'=>'string',
					'password'=>'string',
				)
			),
			//crmv@42707
			"getmenulist"=>array(
				"include"=>array(
					"include/Webservices/MenuList.php"
				),
				"handler"=>"vtws_getmenulist",
				'params'=>array(),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'getmenulist', //crmv@170283
			),
			//crmv@42707e
			//crmv@2390me
			//crmv@OPER4380
			"retrieveExtra"=>array(
				"include"=>array(
					"include/Webservices/Extra/Retrieve.php"
				),
				"handler"=>"vtws_retrieveExtra",
				"params"=>array(
					"id"=>"String"
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'retrieveextra', //crmv@170283
			),
			"queryExtra"=>array(
				"include"=>array(
					"include/Webservices/Extra/Query.php"
				),
				"handler"=>"vtws_queryExtra",
				"params"=>array(
					"query"=>"String"
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'queryextra', //crmv@170283
			),
			"describeExtra"=>array(
				"include"=>array(
					"include/Webservices/Extra/DescribeObject.php"
				),
				"handler"=>"vtws_describeExtra",
				"params"=>array(
					"elementType"=>"String",
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'describeextra', //crmv@170283
			),
			"listtypesExtra"=>array(
				"include"=>array(
					"include/Webservices/Extra/ModuleTypes.php"
				),
				"handler"=>"vtws_listtypesExtra",
				"params"=>array(
					'fieldTypeList'=>'Encoded',
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'listtypesextra', //crmv@170283
			),		
			"getRelationsExtra"=>array(
				"include"=>array(
					"include/Webservices/Extra/Relations.php"
				),
				"handler"=>"vtws_getRelationsExtra",
				"params"=>array(
					"module"=>"String",
					"record"=>"String",
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'getrelationsextra', //crmv@170283
			),
			//crmv@OPER4380 e
			// crmv@5687
			"ol_get_filters"=>array(
				"include"=>array(
					"include/Webservices/OutlookWS.php"
				),
				"handler"=>"ol_get_filters",
				"params"=>array(
					"module"=>"String",
				),
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'olgetfilters', //crmv@170283
			),
			"ol_clientsearch"=>array(
				"include"=>array(
					"include/Webservices/OutlookWS.php"
				),
				"handler"=>"ol_clientsearch",
				"params"=>array(
					"modules"=>"Encoded",
					"search_text"=>"String",
				),
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'olclientsearch', //crmv@170283
			),
			"ol_is_sdk"=>array(
				"include"=>array(
					"include/Webservices/OutlookWS.php"
				),
				"handler"=>"ol_is_sdk",
				"params"=>array(
					"client_version"=>"String",
				),
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'olissdk', //crmv@170283
			),
			"ol_doquery"=>array(
				"include"=>array(
					"include/Webservices/OutlookWS.php"
				),
				"handler"=>"ol_doquery",
				"params"=>array(
					"module"=>"String",
					"search_fields"=>"Encoded",
					"search_value"=>"String",
				),
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'oldoquery', //crmv@170283
			),
			// crmv@5687e
			//crmv@120039
			"describe_all"=>array(
				"include"=>array(
					"include/Webservices/DescribeObject.php"
				),
				"handler"=>"vtws_describe_all",
				"params"=>array(
					"elementType"=>"string"
				),
				"prelogin"=>0,
				"type"=>"GET",
				'rest_name'=>'describeall', //crmv@170283
			),
			//crmv@120039e
			//crmv@195835
			"relate"=>array(
				"include"=>array(
					"include/Webservices/Relate.php"
				),
				"handler"=>"vtws_relate",
				"params"=>array(
					"id"=>"string",
					"relatelist"=>"encoded",
					"relationid"=>"string"
				),
				"prelogin"=>0,
				"type"=>"POST",
				'rest_name'=>'relate',
			),
			//crmv@195835e
		);
		$createOperationQuery = "insert into ".$table_prefix."_ws_operation(operationid,name,handler_path,handler_method,type,prelogin,rest_name) values (?,?,?,?,?,?,?)"; //crmv@170283
		$createOperationParamsQuery = "insert into ".$table_prefix."_ws_operation_parameters(operationid,name,type,sequence) values (?,?,?,?)";
		foreach ($operationMeta as $operationName => $operationDetails) {
			$operationId = $this->db->getUniqueID($table_prefix."_ws_operation");
			$result = $this->db->pquery($createOperationQuery,array($operationId,$operationName,$operationDetails['include'],$operationDetails['handler'],$operationDetails['type'],$operationDetails['prelogin'],$operationDetails['rest_name'])); //crmv@170283
			$params = $operationDetails['params'];
			$sequence = 1;
			foreach ($params as $paramName => $paramType) {
				$result = $this->db->pquery($createOperationParamsQuery,array($operationId,$paramName,$paramType,$sequence++));
			}
		}
	}

	function vtws_addEntityInfo(){
		global $table_prefix;
		require_once 'include/Webservices/Utils.php';
		$names = vtws_getModuleNameList();
		$moduleHandler = array('file'=>'include/Webservices/VtenextModuleOperation.php',
			'class'=>'VtenextModuleOperation');//crmv@207871
		foreach ($names as $tab){
			if(in_array($tab,array('Rss','Recyclebin'))){
				continue;
			}
			$entityId = $this->db->getUniqueID($table_prefix."_ws_entity");
			$this->db->pquery('insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
				array($entityId,$tab,$moduleHandler['file'],$moduleHandler['class'],1));
		}

		$entityId = $this->db->getUniqueID($table_prefix."_ws_entity");
		$this->db->pquery('insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
			array($entityId,'Events',$moduleHandler['file'],$moduleHandler['class'],1));


		$entityId = $this->db->getUniqueID($table_prefix."_ws_entity");
		$this->db->pquery('insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
			array($entityId,'Users',$moduleHandler['file'],$moduleHandler['class'],1));

		//crmv@30967
		$entityId = $this->db->getUniqueID($table_prefix."_ws_entity");
		$this->db->pquery('insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
			array($entityId,'Reports',$moduleHandler['file'],$moduleHandler['class'],1));
		//crmv@30967e
		
		// crmv@195745
		$entityId = $this->db->getUniqueID($table_prefix."_ws_entity");
		$this->db->pquery(
			'insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
			array($entityId,'ProductsBlock','include/Webservices/VteProdBlockOperation.php','VteProdBlockOperation',1)
		);
		// crmv@195745e

		vtws_addDefaultActorTypeEntity('Groups',array('fieldNames'=>'groupname',
			'indexField'=>'groupid','tableName'=>$table_prefix.'_groups'));

		require_once("include/Webservices/WebServiceError.php");
		require_once 'include/Webservices/VtenextWebserviceObject.php';//crmv@207871
		$webserviceObject = VtenextWebserviceObject::fromName($this->db,'Groups');//crmv@207871
		$this->db->pquery("insert into ".$table_prefix."_ws_entity_tables(webservice_entity_id,table_name) values
			(?,?)",array($webserviceObject->getEntityId(),$table_prefix.'_groups'));

		vtws_addDefaultActorTypeEntity('Currency',array('fieldNames'=>'currency_name',
			'indexField'=>'id','tableName'=>$table_prefix.'_currency_info'));

		$webserviceObject = VtenextWebserviceObject::fromName($this->db,'Currency');//crmv@207871
		$this->db->pquery("insert into ".$table_prefix."_ws_entity_tables(webservice_entity_id,table_name) values (?,?)",
			array($webserviceObject->getEntityId(),$table_prefix.'_currency_info'));

		vtws_addDefaultActorTypeEntity('DocumentFolders',array('fieldNames'=>'foldername',
			'indexField'=>'folderid','tableName'=>$table_prefix.'_crmentityfolder')); // crmv@30967
		$webserviceObject = VtenextWebserviceObject::fromName($this->db,'DocumentFolders');//crmv@207871
		$this->db->pquery("insert into ".$table_prefix."_ws_entity_tables(webservice_entity_id,table_name) values (?,?)",
			array($webserviceObject->getEntityId(),$table_prefix.'_crmentityfolder')); // crmv@30967

	}

	function vtws_addFieldTypeInformation(){
	 	//crmv@23575 : aggiunto uitype 70
	 	//crmv@31780 : aggiunto uitype 71
	 	//crmv@33545 : aggiunto uitype 1014
	 	//crmv@48861 : aggiunto uitype 300
		//crmv@80653 : aggiunto uitype 207
		$fieldTypeInfo = array(
			'picklist'=>array(15,16,300),
			'text'=>array(19,20,21,24),
			'autogenerated'=>array(3),
			'phone'=>array(11, 1014),
			'multipicklist'=>array(33),
			'url'=>array(17,207),
			'skype'=>array(85),
			'boolean'=>array(56,156),
			'owner'=>array(53),
			'file'=>array(61,28),
			'picklistmultilanguage'=>array(1015),
			'datetime'=>array(70),
			'currency'=>array(71),
			'string'=>array(98), // crmv@128656
			'date'=>array(5), //crmv@131239
		);
		foreach($fieldTypeInfo as $type=>$uitypes){
			global $table_prefix;
			foreach($uitypes as $uitype){
				$fieldtypeid = $this->db->getUniqueId($table_prefix.'_ws_fieldtype');
				$result = $this->db->pquery("insert into ".$table_prefix."_ws_fieldtype(fieldtypeid,uitype,fieldtype) values(?,?,?)",array($fieldtypeid,$uitype,$type));
				if(!is_object($result)){
					"Query for fieldtype details($uitype:uitype,$type:fieldtype)";
				}
			}
		}

		$this->vtws_addReferenceTypeInformation();
	}

	function vtws_addReferenceTypeInformation(){
		global $table_prefix;
		$referenceMapping = array(
			"52"=>array("Users"),
			"77"=>array("Users"),
			"357"=>array("Contacts","Accounts","Leads","Users","Vendors","Potentials"),
			"117"=>array('Currency'),
			"116"=>array('Currency'),
			'26'=>array('DocumentFolders'),
			'10'=>array()
		);

		foreach($referenceMapping as $uitype=>$referenceArray){
			$success = true;
			$fieldtypeid = $this->db->getUniqueId($table_prefix.'_ws_fieldtype');
			$result = $this->db->pquery("insert into ".$table_prefix."_ws_fieldtype(fieldtypeid,uitype,fieldtype) values(?,?,?)",array($fieldtypeid,$uitype,"reference"));
			if(!is_object($result)){
				$success=false;
			}
			$result = $this->db->pquery("select * from ".$table_prefix."_ws_fieldtype where uitype=?",array($uitype));
			$rowCount = $this->db->num_rows($result);
			for($i=0;$i<$rowCount;$i++){
				$fieldTypeId = $this->db->query_result($result,$i,"fieldtypeid");
				foreach($referenceArray as $index=>$referenceType){
					$result = $this->db->pquery("insert into ".$table_prefix."_ws_referencetype(fieldtypeid,type) values(?,?)",array($fieldTypeId,$referenceType));
					if(!is_object($result)){
						echo "failed for: $referenceType, uitype: $fieldTypeId";
						$success=false;
					}
				}
			}
			if(!$success){
				echo "Migration Query Failed";
			}
		}

		$success = true;
		$fieldTypeId = $this->db->getUniqueID($table_prefix."_ws_entity_fieldtype");
		$result = $this->db->pquery("insert into ".$table_prefix."_ws_entity_fieldtype(fieldtypeid,table_name,field_name,fieldtype) values(?,?,?,?)",
			array($fieldTypeId,$table_prefix.'_crmentityfolder','createdby',"reference")); // crmv@30967
		if(!is_object($result)){
			echo "failed fo init<br>";
			$success=false;
		}
		$result = $this->db->pquery("insert into ".$table_prefix."_ws_entity_referencetype(fieldtypeid,type) values(?,?)",array($fieldTypeId,'Users'));
		if(!is_object($result)){
			echo "failed for: Users, fieldtypeid: $fieldTypeId";
			$success=false;
		}
		if(!$success){
			echo "Migration Query Failed";
		}

	}

	function addInventoryRows($paramArray){
		global $adb;

		$fieldCreateCount = 0;

		for($index = 0; $index < count($paramArray); ++$index) {
			$criteria = $paramArray[$index];

			$semodule = $criteria['semodule'];

			$modfocus = CRMEntity::getInstance($semodule);
			$modfocus->setModuleSeqNumber('configure', $semodule, $criteria['prefix'], $criteria['startid']);
		}
	}

	/**
	 * this function adds the entries for settings page
	 * it assumes entries as were present on 10-12-208
	 */
	function addEntriesForSettings(){
		global $adb, $table_prefix;
		
		require_once('vtlib/Vtecrm/Module.php'); // crmv@181161
		
		// changed the settings array, now it's more maintanable
		$allSettings = array(
			'LBL_MODULE_MANAGER' => array(
				'fields' => array(
					array('name' => 'LBL_WORKFLOW_LIST', 'iconpath' => 'settingsWorkflow.png', 'description' => 'LBL_AVAILABLE_WORKLIST_LIST', 'linkto' => 'index.php?module=com_workflow&action=workflowlist', 'sequence' => '1', 'active' => '0')//crmv@207901
				)
			), 
			'LBL_USER_MANAGEMENT' => array(
				'fields' => array(
					array('name' => 'LBL_USERS', 'iconpath' => 'ico-users.gif', 'description' => 'LBL_USER_DESCRIPTION', 'linkto' => 'index.php?module=Administration&action=index&parenttab=Settings', 'sequence' => '1', 'active' => '0'), 
					array('name' => 'LBL_ROLES', 'iconpath' => 'ico-roles.gif', 'description' => 'LBL_ROLE_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=listroles&parenttab=Settings', 'sequence' => '2', 'active' => '0'), 
					array('name' => 'LBL_PROFILES', 'iconpath' => 'ico-profile.gif', 'description' => 'LBL_PROFILE_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=ListProfiles&parenttab=Settings', 'sequence' => '3', 'active' => '0'),
					array('name' => 'USERGROUPLIST', 'iconpath' => 'ico-groups.gif', 'description' => 'LBL_GROUP_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=listgroups&parenttab=Settings', 'sequence' => '4', 'active' => '0'),
					array('name' => 'LBL_SHARING_ACCESS', 'iconpath' => 'shareaccess.gif', 'description' => 'LBL_SHARING_ACCESS_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=OrgSharingDetailView&parenttab=Settings', 'sequence' => '5', 'active' => '0'),
					array('name' => 'LBL_FIELDS_ACCESS', 'iconpath' => 'orgshar.gif', 'description' => 'LBL_SHARING_FIELDS_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=DefaultFieldPermissions&parenttab=Settings', 'sequence' => '6', 'active' => '0'), 
					array('name' => 'LBL_ADV_RULE', 'iconpath' => 'ico-adv_rule.gif', 'description' => 'LBL_ADV_RULE_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=AdvRuleDetailView&parenttab=Settings', 'sequence' => '7', 'active' => '0'), 
					array('name' => 'LBL_AUDIT_TRAIL', 'iconpath' => 'audit.gif', 'description' => 'LBL_AUDIT_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=AuditTrailList&parenttab=Settings', 'sequence' => '8', 'active' => '0'), 
					array('name' => 'LBL_LOGIN_HISTORY_DETAILS', 'iconpath' => 'set-IcoLoginHistory.gif', 'description' => 'LBL_LOGIN_HISTORY_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=ListLoginHistory&parenttab=Settings', 'sequence' => '9', 'active' => '0'), 
					array('name' => 'LoginProtectionPanel', 'iconpath' => 'ico-profile.gif', 'description' => 'LoginProtectionPanel_description', 'linkto' => 'index.php?module=Settings&action=LoginProtectionPanel&parenttab=Settings', 'sequence' => '10', 'active' => '0')
				),
				'image' => 'people',
			),
			'LBL_STUDIO' => array(
				'fields' => array(
					// crmv@181177
					array('name' => 'LBL_PROCESS_MAKER', 'iconpath' => 'call_split', 'description' => 'LBL_PROCESS_MAKER_DESC', 'linkto' => 'index.php?module=Settings&action=ProcessMaker&parenttab=Settings', 'sequence' => '1', 'active' => '0'), 
					array('name' => 'LBL_LIST_WORKFLOWS', 'iconpath' => 'settingsWorkflow.png', 'description' => 'LBL_LIST_WORKFLOWS_DESCRIPTION', 'linkto' => 'index.php?module=com_workflow&action=workflowlist&parenttab=Settings', 'sequence' => '2', 'active' => '0'),//crmv@207901
					array('name' => 'LBL_MAIL_SCANNER', 'iconpath' => 'mailScanner.gif', 'description' => 'LBL_MAIL_SCANNER_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=MailScanner&parenttab=Settings', 'sequence' => '3', 'active' => '0'),
					array('name' => 'LBL_DATA_IMPORTER', 'iconpath' => 'data_import.png', 'description' => 'LBL_DATA_IMPORTER_DESC', 'linkto' => 'index.php?module=Settings&action=DataImporter&parenttab=Settings', 'sequence' => '4', 'active' => '0'), 
					array('name' => 'LBL_WIZARD_MAKER', 'iconpath' => 'module_maker.png', 'description' => 'LBL_WIZARD_MAKER_DESC', 'linkto' => 'index.php?module=Settings&action=WizardMaker&parenttab=Settings', 'sequence' => '5', 'active' => '0'), 
					array('name' => 'LBL_MODULE_MAKER', 'iconpath' => 'module_maker.png', 'description' => 'LBL_MODULE_MAKER_DESC', 'linkto' => 'index.php?module=Settings&action=ModuleMaker&parenttab=Settings', 'sequence' => '6', 'active' => '0'), 
					array('name' => 'VTLIB_LBL_MODULE_MANAGER', 'iconpath' => 'vtlib_modmng.gif', 'description' => 'VTLIB_LBL_MODULE_MANAGER_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=ModuleManager&parenttab=Settings', 'sequence' => '7', 'active' => '0'), 
					array('name' => 'LBL_PICKLIST_EDITOR', 'iconpath' => 'picklist.gif', 'description' => 'LBL_PICKLIST_DESCRIPTION', 'linkto' => 'index.php?module=PickList&action=PickList&parenttab=Settings', 'sequence' => '8', 'active' => '0'), 
					array('name' => 'LBL_PICKLIST_EDITOR_MULTI', 'iconpath' => 'picklist_multilanguage.gif', 'description' => 'LBL_PICKLIST_DESCRIPTION_MULTI', 'linkto' => 'index.php?module=Picklistmulti&action=Picklistmulti&parenttab=Settings', 'sequence' => '9', 'active' => '0'), 
					array('name' => 'LBL_EDIT_LINKED_PICKLIST', 'iconpath' => 'linkedpicklist.png', 'description' => 'LBL_EDIT_LINKED_PICKLIST_DESC', 'linkto' => 'index.php?module=Settings&action=LinkedPicklist&parenttab=Settings', 'sequence' => '10', 'active' => '0'), 
					array('name' => 'LBL_EDIT_UITYPE208', 'iconpath' => 'uitype208.png', 'description' => 'LBL_EDIT_UITYPE208_DESC', 'linkto' => 'index.php?module=Settings&action=EncryptedFields&parenttab=Settings', 'sequence' => '11', 'active' => '0'), 
					array('name' => 'LBL_MENU_TABS', 'iconpath' => 'menuSettings.gif', 'description' => 'LBL_MENU_TABS_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=menuSettings&parenttab=Settings', 'sequence' => '12', 'active' => '0'), 
					array('name' => 'LBL_COLORED_LISTVIEW_EDITOR', 'iconpath' => 'colored_listview.gif', 'description' => 'LBL_COLORED_LISTVIEW_EDITOR', 'linkto' => 'index.php?module=Settings&action=ColoredListView&parenttab=Settings', 'sequence' => '13', 'active' => '0'), 
					array('name' => 'LBL_EXTWS_CONFIG', 'iconpath' => 'extws_config.png', 'description' => 'LBL_EXTWS_CONFIG_DESC', 'linkto' => 'index.php?module=Settings&action=ExtWSConfig&parenttab=Settings', 'sequence' => '14', 'active' => '0'), // crmv@146670
					array('name' => 'LBL_CUSTOM_FIELDS', 'iconpath' => 'custom.gif', 'description' => 'LBL_CUSTOM_FIELDS_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=CustomFieldList&parenttab=Settings', 'sequence' => '30', 'active' => '1'), 
					// crmv@181177e
				),
				'image' => 'business',
			),
			// crmv@190834 crmv@197445
			'LBL_KLONDIKE_AI' => array(
				'fields' => array(
					array('name' => 'LBL_KLONDIKE_CONFIG', 'iconpath' => 'memory', 'description' => 'LBL_KLONDIKE_CONFIG_DESC', 'linkto' => 'index.php?module=Settings&action=KlondikeAI&parenttab=Settings', 'sequence' => '1', 'active' => '0'),
				),
				'image' => 'gavel',
			),
			// crmv@190834e crmv@197445e
			'LBL_COMMUNICATION_TEMPLATES' => array(
				'fields' => array(
					array('name' => 'EMAILTEMPLATES', 'iconpath' => 'ViewTemplate.gif', 'description' => 'LBL_EMAIL_TEMPLATE_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=listemailtemplates&parenttab=Settings', 'sequence' => '1', 'active' => '0')
				),
				'image' => 'public',
			), 
			'LBL_OTHER_SETTINGS' => array(
				'fields' => array(
					array('name' => 'LBL_COMPANY_DETAILS', 'iconpath' => 'company.gif', 'description' => 'LBL_COMPANY_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=OrganizationConfig&parenttab=Settings', 'sequence' => '1', 'active' => '0'), 
					array('name' => 'LBL_MAIL_SERVER_SETTINGS', 'iconpath' => 'ogmailserver.gif', 'description' => 'LBL_MAIL_SERVER_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=EmailConfig&parenttab=Settings', 'sequence' => '2', 'active' => '0'), 
					array('name' => 'LBL_FAX_SERVER_SETTINGS', 'iconpath' => 'ogfaxserver.gif', 'description' => 'LBL_FAX_SERVER_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=FaxConfig&parenttab=Settings', 'sequence' => '3', 'active' => '0'), 
					array('name' => 'LBL_SMS_SERVER_SETTINGS', 'iconpath' => 'ogsmsserver.gif', 'description' => 'LBL_SMS_SERVER_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=SmsConfig&parenttab=Settings', 'sequence' => '4', 'active' => '0'), 
					array('name' => 'LBL_SOFTPHONE_SERVER_SETTINGS', 'iconpath' => 'ogasteriskserver.gif', 'description' => 'LBL_SOFTPHONE_SERVER_SETTINGS_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=ModuleManager&module_settings=true&formodule=PBXManager&parenttab=Settings', 'sequence' => '5', 'active' => '0'),
					array('name' => 'LBL_LDAP_SERVER_SETTINGS', 'iconpath' => 'ldap.gif', 'description' => 'LBL_LDAP_SERVER_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=LdapConfig&parenttab=Settings', 'sequence' => '6', 'active' => '0'), 
					//array('name' => 'LBL_ASSIGN_MODULE_OWNERS', 'iconpath' => 'assign.gif', 'description' => 'LBL_MODULE_OWNERS_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=ListModuleOwners&parenttab=Settings', 'sequence' => '7', 'active' => '0'), // crmv@179144
					array('name' => 'LBL_CURRENCY_SETTINGS', 'iconpath' => 'currency.gif', 'description' => 'LBL_CURRENCY_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=CurrencyListView&parenttab=Settings', 'sequence' => '8', 'active' => '0'), 
					array('name' => 'LBL_TAX_SETTINGS', 'iconpath' => 'taxConfiguration.gif', 'description' => 'LBL_TAX_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=TaxConfig&parenttab=Settings', 'sequence' => '9', 'active' => '0'), 
					array('name' => 'LBL_SYSTEM_INFO', 'iconpath' => 'system.gif', 'description' => 'LBL_SYSTEM_DESCRIPTION', 'linkto' => 'index.php?module=System&action=listsysconfig&parenttab=Settings', 'sequence' => '10', 'active' => '1'),
					array('name' => 'LBL_PROXY_SETTINGS', 'iconpath' => 'proxy.gif', 'description' => 'LBL_PROXY_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=ProxyServerConfig&parenttab=Settings', 'sequence' => '11', 'active' => '0'), 
					array('name' => 'LBL_ANNOUNCEMENT', 'iconpath' => 'announ.gif', 'description' => 'LBL_ANNOUNCEMENT_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=Announcements&parenttab=Settings', 'sequence' => '12', 'active' => '1'), 
					array('name' => 'LBL_DEFAULT_MODULE_VIEW', 'iconpath' => 'set-IcoTwoTabConfig.gif', 'description' => 'LBL_DEFAULT_MODULE_VIEW_DESC', 'linkto' => 'index.php?module=Settings&action=DefModuleView&parenttab=Settings', 'sequence' => '13', 'active' => '1'), 
					array('name' => 'INVENTORYTERMSANDCONDITIONS', 'iconpath' => 'terms.gif', 'description' => 'LBL_INV_TANDC_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=OrganizationTermsandConditions&parenttab=Settings', 'sequence' => '14', 'active' => '0'),
					array('name' => 'LBL_CUSTOMIZE_MODENT_NUMBER', 'iconpath' => 'settingsInvNumber.gif', 'description' => 'LBL_CUSTOMIZE_MODENT_NUMBER_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=CustomModEntityNo&parenttab=Settings', 'sequence' => '15', 'active' => '0'), 
					array('name' => 'LBL_AUTOUPDATE', 'iconpath' => 'update', 'description' => 'LBL_AUTOUPDATE_DESCRIPTION', 'linkto' => 'index.php?module=Update&action=ViewUpdate&parenttab=Settings', 'sequence' => '16', 'active' => '0'), // crmv@199352
					array('name' => 'LBL_PRIVACY', 'iconpath' => 'themes/images/PrivacySettings.png', 'description' => 'LBL_PRIVACY_DES', 'linkto' => 'index.php?module=Settings&action=Privacy&parenttab=Settings', 'sequence' => '17', 'active' => '0'),
					array('name' => 'LBL_LOG_CONFIG', 'iconpath' => 'set-IcoLoginHistory.gif', 'description' => 'LBL_LOG_CONFIG_DESCRIPTION', 'linkto' => 'index.php?module=Settings&action=LogConfig&parenttab=Settings', 'sequence' => '18', 'active' => '0'), //crmv@173186
				),
				'image' => 'build',
			)
		);
		
		$blocksequence = 1;
		foreach ($allSettings as $blockname => $blockinfo) {
			$blockid = $adb->getUniqueID($table_prefix.'_settings_blocks');
			$image = $blockinfo['image'] ?: null;
			$adb->pquery("INSERT INTO {$table_prefix}_settings_blocks (blockid, label, sequence, image) VALUES (?,?,?,?)", array($blockid, $blockname, $blocksequence++, $image));
			foreach ($blockinfo['fields'] as $field) {
				$fieldid = $adb->getUniqueID($table_prefix.'_settings_field');
				$adb->pquery("INSERT INTO {$table_prefix}_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active) VALUES (?,?,?,?,?,?,?,?)", array($fieldid, $blockid, $field['name'], $field['iconpath'], $field['description'], $field['linkto'], $field['sequence'], $field['active']));
			}
		}
		
		//add update module
		$result = $adb->query("SELECT MAX(tabid) AS max_seq FROM ".$table_prefix."_tab");
		$tabid = $adb->query_result($result, 0, 'max_seq');
		$tabid++;
		$result = $adb->query("SELECT MAX(tabsequence) AS max_tabseq FROM ".$table_prefix."_tab");
		$sequence = $adb->query_result($result, 0, 'max_tabseq');
		$sequence++;
		$params = Array(
			'tabid' => $tabid,
			'name' => 'Update',
			'presence' => 0, // crmv@181161
			'tabsequence' => $sequence,
			'tablabel' => 'Update',
			'modifiedby' => NULL,
			'modifiedtime' => NULL,
			'customized' => 0,
			'ownedby' => 1,
			'version' => '1.1',
			'isentitytype' => 0
		);
		$adb->pquery("INSERT INTO ".$table_prefix."_tab (".implode(",",array_keys($params)).") VALUES (".generateQuestionMarks($params).")",$params);
		Vtecrm_Module::fireEvent('Update', Vtecrm_Module::EVENT_MODULE_POSTINSTALL); // crmv@181161
		
		//VTE 4.0
		if ($adb->table_exist('tbl_s_menu') == 0) {
			$flds = "type C(255) DEFAULT NULL";
			$sqlarray = $adb->datadict->CreateTableSQL('tbl_s_menu', $flds);
			$adb->datadict->ExecuteSQLArray($sqlarray);
			$adb->pquery('insert into tbl_s_menu (type) values (?)',array('modules'));
		}
		if ($adb->table_exist('tbl_s_menu_modules') == 0) {
			$flds = "tabid I(19) NOTNULL PRIMARY,
					fast I(1) DEFAULT 0,
					sequence I(19)";
			$sqlarray = $adb->datadict->CreateTableSQL('tbl_s_menu_modules', $flds);
			$adb->datadict->ExecuteSQLArray($sqlarray);

			$fast_modules = array('Home','Leads','Accounts','Contacts','Campaigns','HelpDesk','Potentials','Reports');
			$i = 0;
			foreach($fast_modules as $module) {
				if(vtlib_isModuleActive($module)) {
					$params = array(getTabid($module),1,$i);
					$adb->pquery('insert into tbl_s_menu_modules (tabid,fast,sequence) values (?,?,?)',$params);
					$i++;
				}
			}
			$res = $adb->query('SELECT '.$table_prefix.'_tab.tabid,'.$table_prefix.'_tab.name
								FROM '.$table_prefix.'_tab
								INNER JOIN (SELECT DISTINCT tabid FROM '.$table_prefix.'_parenttabrel) parenttabrel ON parenttabrel.tabid = '.$table_prefix.'_tab.tabid
								WHERE '.$table_prefix.'_tab.presence = 0');
			$i = 0;
			while($row=$adb->fetchByAssoc($res)) {
				if(vtlib_isModuleActive($row['name']) && !in_array($row['name'],$fast_modules)) {
					$params = array($row['tabid'],0,$i);
					$adb->pquery('insert into tbl_s_menu_modules (tabid,fast,sequence) values (?,?,?)',$params);
					$i++;
				}
			}
		}
		$adb->query("update ".$table_prefix."_settings_field set active=1 where name='LBL_ANNOUNCEMENT'");
		//VTE 4.0 e

		//crmv@sdk
		require_once('modules/SDK/InstallTables.php');
		$sdkModule = new Vtecrm_Module();
		$sdkModule->name = 'SDK';
		$sdkModule->isentitytype = false;
		$sdkModule->save();
		SDK::clearSessionValues();
		Vtecrm_Module::fireEvent($sdkModule->name, Vtecrm_Module::EVENT_MODULE_POSTINSTALL); // crmv@181161
		//crmv@sdk e

		//crmv@31197
		$img = array(
			'Accounts'=>'themes/images/qc_accounts.png',
			'Calendar'=>'themes/images/qc_calendar.png',
			'Events'=>'themes/images/qc_events.png',
			'Contacts'=>'themes/images/qc_contacts.png',
			'Documents'=>'themes/images/qc_documents.png',
			'Vendors'=>'themes/images/qc_vendors.png',
			'Leads'=>'themes/images/qc_leads.png',
			'Potentials'=>'themes/images/qc_potentials.png',
			'HelpDesk'=>'themes/images/qc_helpdesk.png',
		);
		$result = $adb->pquery('SELECT tabid, name FROM '.$table_prefix.'_tab WHERE name IN (?,?,?,?,?,?,?,?,?)',array_keys($img));
		while($row=$adb->fetchByAssoc($result)) {
			$adb->pquery('insert into '.$table_prefix.'_quickcreate (tabid,img) values (?,?)',array($row['tabid'],$img[$row['name']]));
		}
		//crmv@31197e
	}
	//crmv@392267
    function outlook_intializeChanges(){
       $this->addEmailFieldTypeInWs();
       $this->addFilterToListTypes();
	   $this->registerAssignToChangeWorkFlow();
    }

	function registerAssignToChangeWorkFlow(){
		$this->addDependencyColumnToEventHandler();
		$this->registerVTEntityDeltaApi();
		$this->addDepedencyToVTWorkflowEventHandler();
	}

	function registerVTEntityDeltaApi(){
		$db = PearDatabase::getInstance();
		$em = new VTEventsManager($db);
		$em->registerHandler('vte.entity.beforesave', 'data/VTEntityDelta.php', 'VTEntityDelta');
		$em->registerHandler('vte.entity.aftersave', 'data/VTEntityDelta.php', 'VTEntityDelta');
	}

	function addDependencyColumnToEventHandler(){
//		Vte_Utils::AlterTable($table_prefix.'_eventhandlers',"dependent_on C(255) NOT NULL DEFAULT '[]'");
	}

	function addDepedencyToVTWorkflowEventHandler(){
		global $table_prefix;
		$db = PearDatabase::getInstance();
		$dependentEventHandlers = array('VTEntityDelta');
		$dependentEventHandlersJson = Zend_Json::encode($dependentEventHandlers);
		$db->pquery('UPDATE '.$table_prefix.'_eventhandlers SET dependent_on=? WHERE event_name=? AND handler_class=?',
										array($dependentEventHandlersJson, 'vte.entity.aftersave', 'VTWorkflowEventHandler'));
	}
	function addEmailFieldTypeInWs(){
		global $table_prefix;
		$db = PearDatabase::getInstance();
		$checkQuery = "SELECT * FROM ".$table_prefix."_ws_fieldtype WHERE fieldtype=?";
		$params = array ("email");
		$checkResult = $db->pquery($checkQuery,$params);
		if($db->num_rows($checkResult) <= 0) {
			$fieldTypeId = $db->getUniqueID($table_prefix.'_ws_fieldtype');
			$params = Array($fieldTypeId,'13','email');
			$sql = "INSERT INTO ".$table_prefix."_ws_fieldtype(fieldtypeid,uitype,fieldtype) VALUES (".generateQuestionMarks($params).")";
			$db->pquery($sql,$params);
		}
	}
	function addFilterToListTypes() {
		global $table_prefix;
		$db = PearDatabase::getInstance();
		$query = "SELECT operationid FROM ".$table_prefix."_ws_operation WHERE name=?";
		$parameters = array("listtypes");
		$result = $db->pquery($query,$parameters);
		if($db->num_rows($result) > 0){
			$operationId = $db->query_result($result,0,'operationid');
			$operationName = 'fieldTypeList';
			$checkQuery = 'SELECT * FROM '.$table_prefix.'_ws_operation_parameters where operationid=? and name=?';
			$operationResult = $db->pquery($checkQuery,array($operationId,$operationName));
			if($db->num_rows($operationResult) <=0 ){
				$status = vtws_addWebserviceOperationParam($operationId,$operationName,
							'Encoded',0);
			}
		}
	}
	//crmv@392267e
}
