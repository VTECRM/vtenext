<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

require_once('config.php');
require_once('modules/Contacts/contactSeedData.php');
require_once('include/database/PearDatabase.php');
require_once('include/utils/utils.php');
require_once('include/utils/WizardUtils.php'); // crmv@96233
require_once('install/ComboStrings.php');

global $first_name_array;
global $first_name_count;
global $last_name_array;
global $last_name_count;
global $company_name_array;
global $company_name_count;
global $street_address_array;
global $street_address_count;
global $city_array;
global $city_array_count;
global $campaign_name_array,$campaign_type_array,$campaign_status_array;

global $adb, $table_prefix;
global $current_user;

$adb = PearDatabase::getInstance();

function add_digits($quantity, &$string, $min = 0, $max = 9)
{
	for($i=0; $i < $quantity; $i++)
	{
		$string .= rand($min,$max);
	}
}

function create_phone_number()
{
	$phone = "(";
	$phone = $phone; // This line is useless, but gets around a code analyzer warning.  Bug submitted 4/28/04
	add_digits(3, $phone);
	$phone .= ") ";
	add_digits(3, $phone);
	$phone .= "-";
	add_digits(4, $phone);

	return $phone;
}

function create_date()
{
	$date = "";
	$date .= date('Y');
	$date .= "-";
	$date .= rand(1,9);
	$date .= "-";
	$date .= rand(1,28);

	return $date;
}

$account_ids = Array();
$opportunity_ids = Array();
$vendor_ids = Array();
$contact_ids = Array();
$product_ids = Array();
$pricebook_ids = Array();
$quote_ids = Array();
$salesorder_ids = Array();
$purchaseorder_ids = Array();
$invoice_ids = Array();
$email_ids = Array();

// Assigned user for all demo data.
$assigned_user_name = "admin";

// Look up the user id for the assigned user
$seed_user = CRMEntity::getInstance('Users');

$assigned_user_id = $seed_user->retrieve_user_id($assigned_user_name);

$current_user = CRMEntity::getInstance('Users');
$result = $current_user->retrieve_entity_info($assigned_user_id,'Users');

$InventoryUtils = InventoryUtils::getInstance();	//crmv@57339

$tagkey = 1;

// Get _dom arrays
$comboFieldNames = Array('leadsource'=>'leadsource_dom'
		      ,'leadstatus'=>'lead_status_dom'
		      ,'industry'=>'industry_dom'
		      ,'rating'=>'rating_dom'
                      ,'opportunity_type'=>'opportunity_type_dom'
                      ,'sales_stage'=>'sales_stage_dom');
$comboFieldArray = getComboArray($comboFieldNames);

$adb->println("company_name_array");
$adb->println($company_name_array);

for($i = 0; $i < $company_name_count; $i++) {

	$account_name = $company_name_array[$i];

	// Create new accounts.
	$account = CRMEntity::getInstance('Accounts');
	$account->column_fields["accountname"] = $account_name;
	$account->column_fields["phone"] = create_phone_number();
	$account->column_fields["assigned_user_id"] = $assigned_user_id;

	$whitespace = array(" ", ".", "&", "\/");
	$website = str_replace($whitespace, "", strtolower($account->column_fields["accountname"]));
	$account->column_fields["website"] = "www.".$website.".com";

	$account->column_fields["bill_street"] = $street_address_array[rand(0,$street_address_count-1)];
	$account->column_fields["bill_city"] = $city_array[rand(0,$city_array_count-1)];
	$account->column_fields["bill_state"] = "CA";
	$account->column_fields["bill_code"] = rand(10000, 99999);
	$account->column_fields["bill_country"] = 'USA';

	$account->column_fields["ship_street"] = $account->column_fields["bill_street"];
	$account->column_fields["ship_city"] = $account->column_fields["bill_city"];
	$account->column_fields["ship_state"] = $account->column_fields["bill_state"];
	$account->column_fields["ship_code"] = $account->column_fields["bill_code"];
	$account->column_fields["ship_country"] = $account->column_fields["bill_country"];

	$key = array_rand($comboFieldArray['industry_dom']);
	$account->column_fields["industry"] = $comboFieldArray['industry_dom'][$key];

	$account->column_fields["account_type"] = "Customer";

	$account->save("Accounts", false, false, false);

	$account_ids[] = $account->id;

	//Create new opportunities
	$opp = CRMEntity::getInstance('Potentials');

	$opp->column_fields["assigned_user_id"] = $assigned_user_id;
	$opp->column_fields["potentialname"] = $account_name." - 1000 units";
	$opp->column_fields["closingdate"] = & create_date();

	$key = array_rand($comboFieldArray['leadsource_dom']);
	$opp->column_fields["leadsource"] = $comboFieldArray['leadsource_dom'][$key];

	$comboSalesStageArray = Array ("Closed Won","Needs Analysis","Value Proposition","Qualification","Prospecting","Id. Decision Makers");
	$key = array_rand($comboSalesStageArray);
	$opp->column_fields["sales_stage"] = $comboSalesStageArray[$key];

	$key = array_rand($comboFieldArray['opportunity_type_dom']);
	$opp->column_fields["opportunity_type"] = $comboFieldArray['opportunity_type_dom'][$key];

	$amount = array("10000", "25000", "50000", "75000");
	$key = array_rand($amount);
	$opp->column_fields["amount"] = $amount[$key];
	$opp->column_fields["related_to"] = $account->id;

	$opp->save("Potentials", false, false, false);

	$opportunity_ids[] = $opp->id;
}


for($i=0; $i<10; $i++)
{
	$contact = CRMEntity::getInstance('Contacts');
	$contact->column_fields["firstname"] = ucfirst(strtolower($first_name_array[$i]));
	$contact->column_fields["lastname"] = ucfirst(strtolower($last_name_array[$i]));
	$contact->column_fields["assigned_user_id"] = $assigned_user_id;

	$contact->column_fields["email"] = strtolower($contact->column_fields["firstname"])."_".strtolower($contact->column_fields["lastname"])."@company.com";

	$contact->column_fields["phone"] = create_phone_number();
	$contact->column_fields["homephone"] = create_phone_number();
	$contact->column_fields["mobile"] = create_phone_number();

	// Fill in a bogus address
	$key = array_rand($street_address_array);
	$contact->column_fields["mailingstreet"] = $street_address_array[$key];
	$key = array_rand($city_array);
	$contact->column_fields["mailingcity"] = $city_array[$key];
	$contact->column_fields["mailingstate"] = "CA";
	$contact->column_fields["mailingzip"] = '99999';
	$contact->column_fields["mailingcountry"] = 'USA';

	$key = array_rand($comboFieldArray['leadsource_dom']);
	$contact->column_fields["leadsource"] = $comboFieldArray['leadsource_dom'][$key];

	$titles = array("President",
					"VP Operations",
					"VP Sales",
					"Director Operations",
					"Director Sales",
					"Mgr Operations",
					"IT Developer",
					"");
	$key = array_rand($titles);
	$contact->column_fields["title"] = $titles[$key];

	$account_key = array_rand($account_ids);
	$contact->column_fields["account_id"] = $account_ids[$account_key];

	//$contact->saveentity("Contacts");
	$contact->save("Contacts", false, false, false);
	$contact_ids[] = $contact->id;

	// This assumes that there will be one opportunity per company in the seed data.
	$opportunity_key = array_rand($opportunity_ids);

	$query = "insert into ".$table_prefix."_contpotentialrel ( contactid, potentialid ) values (?,?)";
	$adb->pquery($query, array($contact->id, $opportunity_ids[$opportunity_key]));
}

$company_count=0;
for($i=0; $i<10; $i++)
{
	$lead = CRMEntity::getInstance('Leads');
	$lead->column_fields["firstname"] = ucfirst(strtolower($first_name_array[$i]));
	$lead->column_fields["lastname"] = ucfirst(strtolower($last_name_array[$i]));

	if($i<5)
       	{
        	$lead->column_fields["company"] = ucfirst(strtolower($company_name_array[$i]));
       	}
       	else
       	{
               	$lead->column_fields["company"] = ucfirst(strtolower($company_name_array[$company_count]));
               	$company_count++;
       	}

	$lead->column_fields["assigned_user_id"] = $assigned_user_id;

	$lead->column_fields["email"] = strtolower($lead->column_fields["firstname"])."_".strtolower($lead->column_fields["lastname"])."@company.com";

	$website = str_replace($whitespace, "", strtolower(ucfirst(strtolower($company_name_array[$i]))));
        $lead->column_fields["website"] = "www.".$website.".com";

	$lead->column_fields["phone"] = create_phone_number();
	$lead->column_fields["mobile"] = create_phone_number();

	// Fill in a bogus address
	$key = array_rand($street_address_array);
	//$lead->address_street = $street_address_array[$key];
	$lead->column_fields["lane"] = $street_address_array[$key];
	$key = array_rand($city_array);
	$lead->column_fields["city"] = $city_array[$key];
	$lead->column_fields["state"] = "CA";
	$lead->column_fields["code"] = '99999';
	$lead->column_fields["country"] = 'USA';

	$key = array_rand($comboFieldArray['leadsource_dom']);
	$lead->column_fields["leadsource"] = $comboFieldArray['leadsource_dom'][$key];

	$key = array_rand($comboFieldArray['lead_status_dom']);
	$lead->column_fields["leadstatus"] = $comboFieldArray['lead_status_dom'][$key];

	$key = array_rand($comboFieldArray['rating_dom']);
	$lead->column_fields["rating"] = $comboFieldArray['rating_dom'][$key];

	$titles = array("President",
					"VP Operations",
					"VP Sales",
					"Director Operations",
					"Director Sales",
					"Mgr Operations",
					"IT Developer",
					"");
	$key = array_rand($titles);
	$lead->column_fields["designation"] = $titles[$key];

	$lead->save("Leads", false, false, false);
}

//Populating Vendor Data
for($i=0; $i<10; $i++)
{
	$vendor = CRMEntity::getInstance('Vendors');
	$vendor->column_fields["vendorname"] = ucfirst(strtolower($first_name_array[$i]));
	$vendor->column_fields["phone"] = create_phone_number();
	$vendor->column_fields["email"] = strtolower($vendor->column_fields["vendorname"])."@company.com";
	$website = str_replace($whitespace, "", strtolower(ucfirst(strtolower($company_name_array[$i]))));
	$vendor->column_fields["website"] = "www.".$website.".com";

	$vendor->column_fields["assigned_user_id"] = $assigned_user_id;

	// Fill in a bogus address
	$vendor->column_fields["street"] = $street_address_array[rand(0,$street_address_count-1)];
	$key = array_rand($city_array);
	$vendor->column_fields["city"] = $city_array[$key];
	$vendor->column_fields["state"] = "CA";
	$vendor->column_fields["postalcode"] = '99999';
	$vendor->column_fields["country"] = 'USA';

	$vendor->save("Vendors", false, false, false);
	$vendor_ids[] = $vendor->id;
}

//Populating Product Data

$product_name_array= array( "Vte Single User Pack", "Vte 5 Users Pack", "Vte 10 Users Pack",
        "Vte 25 Users Pack", "Vte 50 Users Pack", "Double Panel See-thru Clipboard",
        "abcd1234", "Cd-R CD Recordable", "Sharp - Plain Paper Fax" , "Brother Ink Jet Cartridge");
$product_code_array= array("001","002","003","023","005","sg-106","1324356","sg-108","sg-119","sg-125");
$subscription_rate=array("149","699","1299","2999","4995");
$subscription_cost=array("100","400","800","1000","1800");
//added by jeri to populate product images
$product_image_array = array("","","","");
for($i=0; $i<10; $i++) {
	$product = CRMEntity::getInstance('Products');
	if($i>4) {
		$parent_key = array_rand($opportunity_ids);
		$product->column_fields["parent_id"]=$opportunity_ids[$parent_key];

		$usageunit	=	"Each";
		$qty_per_unit	=	1;
		$qty_in_stock	=	rand(10000, 99999);
		$category 	= 	"Hardware";
		$website 	=	"";
		$manufacturer	= 	"";
		$commission_rate=	rand(10,20);
		$unit_price	=	rand(100,999);
		$unit_cost = rand(50, 800);
		$product_image_name = '';
	} else {
		$account_key = array_rand($account_ids);
		$product->column_fields["parent_id"]=$account_ids[$account_key];

		$usageunit	=	"Each";
		$qty_per_unit	=	1;
		$qty_in_stock	=	rand(10000, 99999);
		$category 	= 	"Software";
		$website 	=	$enterprise_website[0];
		$manufacturer	= 	"LexPon Inc.";
		$commission_rate=	rand(1,10);
		$unit_price	=	$subscription_rate[$i];
		$unit_cost	=	$subscription_cost[$i];
		$product_image_name = $product_image_array[$i];
	}

    $product->column_fields["productname"] 	= 	$product_name_array[$i];
    $product->column_fields["productcode"] 	= 	$product_code_array[$i];
    $product->column_fields["manufacturer"]	= 	$manufacturer;
    $product->column_fields["discontinued"]	= 	1;

	$product->column_fields["productcategory"] = 	$category;
    $product->column_fields["website"] 	=	$website;
    $product->column_fields["productsheet"] =	"";

	$vendor_key = array_rand($vendor_ids);
    $product->column_fields["vendor_id"] 	= 	$vendor_ids[$vendor_key];
	$contact_key = array_rand($contact_ids);
    $product->column_fields["contact_id"] 	= 	$contact_ids[$contact_key];

    $product->column_fields["start_date"] 	= 	& create_date();
    $product->column_fields["sales_start_date"] 	= & create_date();

    $product->column_fields["unit_price"] 	= 	$unit_price;
    $product->column_fields["unit_cost"] 	= 	$unit_cost;
    $product->column_fields["commissionrate"] = 	$commission_rate;
    $product->column_fields["taxclass"] 	= 	'SalesTax';
    $product->column_fields["usageunit"]	= 	$usageunit;
 	$product->column_fields["qty_per_unit"] = 	$qty_per_unit;
    $product->column_fields["qtyinstock"] 	= 	$qty_in_stock;
	$product->column_fields["imagename"] =  $product_image_name;
	$product->column_fields["assigned_user_id"] = 	1;

	$product->save("Products", false, false, false);
	
	//crmv@57339
	$curid = 1;
	$conversion_rate = 1;
	$product_base_conv_rate = $InventoryUtils->getBaseConversionRateForProduct($product->id, '');
	$actual_conversion_rate = $product_base_conv_rate * $conversion_rate;
	$converted_price = $actual_conversion_rate * $unit_price;
	$actual_price = $converted_price;
	$query = "insert into ".$table_prefix."_productcurrencyrel values(?,?,?,?)";
	$adb->pquery($query, array($product->id,$curid,$converted_price,$actual_price));
	//crmv@57339e
	
	$product_ids[] = $product->id;
}

//Populating HelpDesk- FAQ Data

	$status_array=array ("Draft","Reviewed","Published","Draft","Reviewed","Draft","Reviewed","Draft","Reviewed","Draft","Reviewed","Draft");
	$question_array=array (
	"How to migrate data from previous versions to the latest version?",
	"Error message: The file is damaged and could not be repaired.",
	"A program is trying to access e-mail addresses you have stored in Outlook. Do you want to allow this? If this is unexpected, it may be a virus and you should choose No when trying to add Email to vitger CRM ",
	"When trying to merge a template with a contact, First I was asked allow installation of ActiveX control. I accepted. After it appears a message that it will not be installed because it can't verify the publisher. Do you have a workarround for this issue ?",
	" Error message - please close all instances of word before using the vte word plugin. Do I need to close all Word and Outlook instances first before I can reopen Word and sign in?",
	"How to migrate data from previous versions to the latest version?",
	"A program is trying to access e-mail addresses you have stored in Outlook. Do you want to allow this? If this is unexpected, it may be a virus and you should choose No when trying to add Email to vitger CRM ",
	" Error message - please close all instances of word before using the vte word plugin. Do I need to close all Word and Outlook instances first before I can reopen Word and sign in?",
	"Error message: The file is damaged and could not be repaired.",
	"When trying to merge a template with a contact, First I was asked allow installation of ActiveX control. I accepted. After it appears a message that it will not be installed because it can't verify the publisher. Do you have a workarround for this issue ?",
	" Error message - please close all instances of word before using the vte word plugin. Do I need to close all Word and Outlook instances first before I can reopen Word and sign in?",
	"How to migrate data from previous versions to the latest version?",
	);

	$answer_array=array (
		"The above error message is due to version incompatibility between FPDF and PHP5. Use PHP 4.3.X version","Published",
	"The above error message is displayed if you have installed the Microsoft(R) Outlook(R) E-mail Security Update. Please refer to the following URL for complete details:

http://support.microsoft.com/default.aspx?scid=kb%3BEN-US%3B263074

If you want to continue working with vte Outlook Plug-in, select the Allow access for check box and select the time from drop-down box.",
	" Since, vteCRM & all plugins are open source, it is not signed up with third party vendors and IE will ask to download even though the plugin are not signed.

This message if produced by Microsoft Windows XP. I English Windows XP with the SP2 and the last updates. I told IE to accept installation of the ActiveX, but after it, this message has appeared. Provably there is a place where to tall to WinXP to not validate if the code is signed... but I don\'t know where.

In IE from Tools->Internet Options->Security->Custom Level, there you can see various options for downloading plugins which are not signed and you can adjust according to your need, so relax your security settings for a while and give a try to vte Office Plugin.",
	"Before modifying any templates, please ensure that you don\'t have any documents open and only one instance of word is available in your memory."
	);

$num_array=array(0,1,2,3,4,6,7,8,9,10,11,12);
for($i=0;$i<12;$i++) {
	$faq = CRMEntity::getInstance('Faq');

	$rand=array_rand($num_array);
	$faq->column_fields["product_id"]	= $product_ids[$i];
	$faq->column_fields["faqcategories"]	= "General";
	$faq->column_fields["faqstatus"] 	= $status_array[$i];
	$faq->column_fields["question"]		= $question_array[$i];
	$faq->column_fields["faq_answer"]	= $answer_array[$i];

	$faq->save("Faq", false, false, false);
	$faq_ids[] = $faq ->id;
}

//Populate Quote Data

$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

$sub_array = array ("Prod_Quote", "Cont_Quote", "SO_Quote", "PO_Quote", "Vendor_Quote");
$stage_array = array ("Created", "Reviewed", "Delivered", "Accepted" , "Rejected");
$carrier_array = array ("FedEx", "UPS", "USPS", "DHL", "BlueDart");
$validtill_array = array ("2007-09-21", "2007-10-29", "2007-12-11", "2007-03-29", "2007-06-18");

for($i=0;$i<5;$i++)
{
	$quote = CRMEntity::getInstance('Quotes');

	$quote->column_fields["assigned_user_id"] = $assigned_user_id;
	$account_key = array_rand($account_ids);
	$quote->column_fields["account_id"] = $account_ids[$account_key];
	$op_key = array_rand($opportunity_ids);
	$quote->column_fields["potential_id"] = $opportunity_ids[$op_key];
	$contact_key = array_rand($contact_ids);
        $quote->column_fields["contact_id"] = $contact_ids[$contact_key];
	$rand = array_rand($num_array);
	$quote->column_fields["subject"] = $sub_array[$i];
	$quote->column_fields["quotestage"] = $stage_array[$i];
	$quote->column_fields["carrier"] = $carrier_array[$i];
	$quote->column_fields["validtill"] = $validtill_array[$i];

	$quote->column_fields["bill_street"] = $street_address_array[rand(0,$street_address_count-1)];
	$quote->column_fields["bill_city"] = $city_array[rand(0,$city_array_count-1)];
	$quote->column_fields["bill_state"] = "CA";
	$quote->column_fields["bill_code"] = rand(10000, 99999);
	$quote->column_fields["bill_country"] = 'USA';

	$quote->column_fields["ship_street"] = $account->column_fields["bill_street"];
	$quote->column_fields["ship_city"] = $account->column_fields["bill_city"];
	$quote->column_fields["ship_state"] = $account->column_fields["bill_state"];
	$quote->column_fields["ship_code"] = $account->column_fields["bill_code"];
	$quote->column_fields["ship_country"] = $account->column_fields["bill_country"];

	$quote->column_fields["currency_id"] = '1';
	$quote->column_fields["conversion_rate"] = '1';

	$quote->save("Quotes", false, false, false);

	$quote_ids[] = $quote->id;

	$product_key = array_rand($product_ids);
	$productid = $product_ids[$product_key];

	//set the inventory product details in request then just call the saveInventoryProductDetails function
	$_REQUEST['totalProductCount']	 = 1;

	$_REQUEST['hdnProductId1'] = $productid;
	$_REQUEST['qty1'] = $qty = 1;
	$_REQUEST['listPrice1'] = $listprice = 130;
	$_REQUEST['comment1'] = "This is test comment for product of Quotes";

	$_REQUEST['deleted1'] = 0;
	$_REQUEST['discount_type1'] = 'amount';
	$_REQUEST['discount_amount1'] = $discount_amount = 20;
	$_REQUEST['netPriceInput1'] = ($_REQUEST['listPrice1']*$_REQUEST['qty1'])-$_REQUEST['discount_amount1'];

	$_REQUEST['taxtype'] = $taxtype = 'individual';
	$_REQUEST['subtotal'] = $subtotal = $qty*$listprice-$discount_amount;
	$_REQUEST['discount_type_final'] = 'amount';
	$_REQUEST['discount_amount_final'] = $discount_amount_final = 10;

	$_REQUEST['shipping_handling_charge'] = $shipping_handling_charge = 50;
	$_REQUEST['adjustmenttype'] = '+';
	$_REQUEST['adjustment'] = $adjustment = 10;

	$_REQUEST['total'] = $subtotal-$discount_amount_final+$shipping_handling_charge+$adjustment;

	//Upto this added to set the request values which will be used to save the inventory product details

	//Now call the saveInventoryProductDetails function
	$InventoryUtils->saveInventoryProductDetails($quote, 'Quotes');
}

//Populate SalesOrder Data

$subj_array = array ("SO_vtecrm", "SO_zoho", "SO_vte5usrp", "SO_vt100usrpk", "SO_vendtl");
$status_array = array ("Created",  "Delivered", "Approved" , "Cancelled" , "Created");
$carrier_array = array ("FedEx", "UPS", "USPS", "DHL", "BlueDart");
$duedate_array = array ("2007-04-21", "2007-05-29", "2007-08-11", "2007-09-09", "2007-02-28");

for($i=0;$i<5;$i++)
{
	$so = CRMEntity::getInstance('SalesOrder');

	$so->column_fields["assigned_user_id"] = $assigned_user_id;
	$account_key = array_rand($account_ids);
	$so->column_fields["account_id"] = $account_ids[$account_key];
	$quote_key = array_rand($quote_ids);
	$so->column_fields["quote_id"] = $quote_ids[$quote_key];
	$contact_key = array_rand($contact_ids);
        $so->column_fields["contact_id"] = $contact_ids[$contact_key];
	$rand = array_rand($num_array);
	$so->column_fields["subject"] = $subj_array[$i];
	$so->column_fields["sostatus"] = $status_array[$i];
	$so->column_fields["hdnGrandTotal"] = $sototal_array[$i];
	$so->column_fields["carrier"] = $carrier_array[$i];
	$so->column_fields["duedate"] = $duedate_array[$i];

	$so->column_fields["bill_street"] = $street_address_array[rand(0,$street_address_count-1)];
	$so->column_fields["bill_city"] = $city_array[rand(0,$city_array_count-1)];
	$so->column_fields["bill_state"] = "CA";
	$so->column_fields["bill_code"] = rand(10000, 99999);
	$so->column_fields["bill_country"] = 'USA';

	$so->column_fields["ship_street"] = $account->column_fields["bill_street"];
	$so->column_fields["ship_city"] = $account->column_fields["bill_city"];
	$so->column_fields["ship_state"] = $account->column_fields["bill_state"];
	$so->column_fields["ship_code"] = $account->column_fields["bill_code"];
	$so->column_fields["ship_country"] = $account->column_fields["bill_country"];

	$so->column_fields["currency_id"] = '1';
	$so->column_fields["conversion_rate"] = '1';

	$so->save("SalesOrder", false, false, false);

	$salesorder_ids[] = $so->id;

	$product_key = array_rand($product_ids);
	$productid = $product_ids[$product_key];

	//set the inventory product details in request then just call the saveInventoryProductDetails function
	$_REQUEST['totalProductCount']	 = 1;

	$_REQUEST['hdnProductId1'] = $productid;
	$_REQUEST['qty1'] = $qty = 1;
	$_REQUEST['listPrice1'] = $listprice = 1230;
	$_REQUEST['comment1'] = "This is test comment for product of SalesOrder";

	$_REQUEST['deleted1'] = 0;
	$_REQUEST['discount_type1'] = 'amount';
	$_REQUEST['discount_amount1'] = $discount_amount = 200;
	$_REQUEST['netPriceInput1'] = ($_REQUEST['listPrice1']*$_REQUEST['qty1'])-$_REQUEST['discount_amount1'];

	$_REQUEST['taxtype'] = $taxtype = 'individual';
	$_REQUEST['subtotal'] = $subtotal = $qty*$listprice-$discount_amount;
	$_REQUEST['discount_type_final'] = 'amount';
	$_REQUEST['discount_amount_final'] = $discount_amount_final = 100;

	$_REQUEST['shipping_handling_charge'] = $shipping_handling_charge = 50;
	$_REQUEST['adjustmenttype'] = '+';
	$_REQUEST['adjustment'] = $adjustment = 100;

	$_REQUEST['total'] = $subtotal-$discount_amount_final+$shipping_handling_charge+$adjustment;

	//Upto this added to set the request values which will be used to save the inventory product details

	//Now call the saveInventoryProductDetails function
	$InventoryUtils->saveInventoryProductDetails($so, 'SalesOrder');
}

//Populate PurchaseOrder Data

$psubj_array = array ("PO_vte", "PO_zoho", "PO_vte5usrp", "PO_vt100usrpk", "PO_vendtl");
$pstatus_array = array ("Created",  "Delivered", "Approved" , "Cancelled", "Received Shipment");
$carrier_array = array ("FedEx", "UPS", "USPS", "DHL", "BlueDart");
$trkno_array = array ("po1425", "po2587", "po7974", "po7979", "po6411");
$duedate_array = array ("2007-04-21", "2007-05-29", "2007-07-11", "2007-04-09", "2006-08-18");

for($i=0;$i<5;$i++)
{
	$po = CRMEntity::getInstance('PurchaseOrder');

	$po->column_fields["assigned_user_id"] = $assigned_user_id;
	$vendor_key = array_rand($vendor_ids);
	$po->column_fields["vendor_id"] = $vendor_ids[$vendor_key];
	$contact_key = array_rand($contact_ids);
        $po->column_fields["contact_id"] = $contact_ids[$contact_key];
	$rand = array_rand($num_array);
	$po->column_fields["subject"] = $psubj_array[$i];
	$po->column_fields["postatus"] = $pstatus_array[$i];
	$po->column_fields["carrier"] = $carrier_array[$i];
	$po->column_fields["tracking_no"] = $trkno_array[$i];
	$po->column_fields["duedate"] = $duedate_array[$i];

	$po->column_fields["bill_street"] = $street_address_array[rand(0,$street_address_count-1)];
	$po->column_fields["bill_city"] = $city_array[rand(0,$city_array_count-1)];
	$po->column_fields["bill_state"] = "CA";
	$po->column_fields["bill_code"] = rand(10000, 99999);
	$po->column_fields["bill_country"] = 'USA';

	$po->column_fields["ship_street"] = $account->column_fields["bill_street"];
	$po->column_fields["ship_city"] = $account->column_fields["bill_city"];
	$po->column_fields["ship_state"] = $account->column_fields["bill_state"];
	$po->column_fields["ship_code"] = $account->column_fields["bill_code"];
	$po->column_fields["ship_country"] = $account->column_fields["bill_country"];

	$po->column_fields["currency_id"] = '1';
	$po->column_fields["conversion_rate"] = '1';

	$po->save("PurchaseOrder", false, false, false);

	$purchaseorder_ids[] = $po->id;

	$product_key = array_rand($product_ids);
	$productid = $product_ids[$product_key];

	//set the inventory product details in request then just call the saveInventoryProductDetails function
	$_REQUEST['totalProductCount']	 = 1;

	$_REQUEST['hdnProductId1'] = $productid;
	$_REQUEST['qty1'] = $qty = 1;
	$_REQUEST['listPrice1'] = $listprice = 2200;
	$_REQUEST['comment1'] = "This is test comment for product of PurchaseOrder";

	$_REQUEST['deleted1'] = 0;
	$_REQUEST['discount_type1'] = 'amount';
	$_REQUEST['discount_amount1'] = $discount_amount = 200;

	$_REQUEST['taxtype'] = $taxtype = 'individual';
	$_REQUEST['subtotal'] = $subtotal = $qty*$listprice-$discount_amount;
	$_REQUEST['discount_type_final'] = 'amount';
	$_REQUEST['discount_amount_final'] = $discount_amount_final = 100;

	$_REQUEST['shipping_handling_charge'] = $shipping_handling_charge = 50;
	$_REQUEST['adjustmenttype'] = '+';
	$_REQUEST['adjustment'] = $adjustment = 100;

	$_REQUEST['total'] = $subtotal-$discount_amount_final+$shipping_handling_charge+$adjustment;

	//Upto this added to set the request values which will be used to save the inventory product details

	//Now call the saveInventoryProductDetails function
	$InventoryUtils->saveInventoryProductDetails($po, 'PurchaseOrder');
}

//Populate Invoice Data

$isubj_array = array ("vte_invoice201", "zoho_inv7841", "vte5usrp_invoice71134", "vt100usrpk_inv113", "vendtl_inv214");
$istatus_array = array ("Created",  "Sent", "Approved" , "Credit Invoice", "Paid");
$itotal_array = array ("4842.000", "4842.000", "4842.000", "4842.000", "4842.000");

for($i=0;$i<5;$i++)
{
	$invoice = CRMEntity::getInstance('Invoice');

	$invoice->column_fields["assigned_user_id"] = $assigned_user_id;
	$account_key = array_rand($account_ids);
	$invoice->column_fields["account_id"] = $account_ids[$account_key];
	$so_key = array_rand($salesorder_ids);
	$invoice->column_fields["salesorder_id"] = $salesorder_ids[$so_key];
	$contact_key = array_rand($contact_ids);
        $invoice->column_fields["contactid"] = $contact_ids[$contact_key];
	$rand = array_rand($num_array);
	$invoice->column_fields["subject"] = $isubj_array[$i];
	$invoice->column_fields["invoicestatus"] = $istatus_array[$i];
	$invoice->column_fields["hdnGrandTotal"] = $itotal_array[$i];

	$invoice->column_fields["bill_street"] = $street_address_array[rand(0,$street_address_count-1)];
	$invoice->column_fields["bill_city"] = $city_array[rand(0,$city_array_count-1)];
	$invoice->column_fields["bill_state"] = "CA";
	$invoice->column_fields["bill_code"] = rand(10000, 99999);
	$invoice->column_fields["bill_country"] = 'USA';

	$invoice->column_fields["ship_street"] = $account->column_fields["bill_street"];
	$invoice->column_fields["ship_city"] = $account->column_fields["bill_city"];
	$invoice->column_fields["ship_state"] = $account->column_fields["bill_state"];
	$invoice->column_fields["ship_code"] = $account->column_fields["bill_code"];
	$invoice->column_fields["ship_country"] = $account->column_fields["bill_country"];

	$invoice->column_fields["currency_id"] = '1';
	$invoice->column_fields["conversion_rate"] = '1';

	$invoice->save("Invoice", false, false, false);

	$invoice_ids[] = $invoice->id;

	$product_key = array_rand($product_ids);
	$productid = $product_ids[$product_key];

	//set the inventory product details in request then just call the saveInventoryProductDetails function
	$_REQUEST['totalProductCount']	 = 1;

	$_REQUEST['hdnProductId1'] = $productid;
	$_REQUEST['qty1'] = $qty = 1;
	$_REQUEST['listPrice1'] = $listprice = 4300;
	$_REQUEST['comment1'] = "This is test comment for product of Invoice";

	$_REQUEST['deleted1'] = 0;
	$_REQUEST['discount_type1'] = 'amount';
	$_REQUEST['discount_amount1'] = $discount_amount = 300;
	$_REQUEST['netPriceInput1'] = ($_REQUEST['listPrice1']*$_REQUEST['qty1']) - $_REQUEST['discount_amount1'];

	$_REQUEST['taxtype'] = $taxtype = 'individual';
	$_REQUEST['subtotal'] = $subtotal = $qty*$listprice - $discount_amount;
	$_REQUEST['discount_type_final'] = 'amount';
	$_REQUEST['discount_amount_final'] = $discount_amount_final = 100;

	$_REQUEST['shipping_handling_charge'] = $shipping_handling_charge = 50;
	$_REQUEST['adjustmenttype'] = '+';
	$_REQUEST['adjustment'] = $adjustment = 100;

	$_REQUEST['total'] = $subtotal - $discount_amount_final + $shipping_handling_charge + $adjustment;

	//Upto this added to set the request values which will be used to save the inventory product details

	//Now call the saveInventoryProductDetails function
	$InventoryUtils->saveInventoryProductDetails($invoice, 'Invoice');

}

//Populate PriceBook data

$PB_array = array ("Cd-R PB", "Vte PB", "Gator PB", "Kyple PB", "Pastor PB", "Zoho PB", "PB_100", "Per_PB", "CST_PB", "GATE_PB", "Chevron_PB", "Pizza_PB");
$Active_array = array ("0", "1", "1", "0", "1","0", "1", "1", "0", "1","0","1");

//$num_array = array(0,1,2,3,4);
for($i=0;$i<12;$i++)
{
	$pricebook = CRMEntity::getInstance('PriceBooks');

	$rand = array_rand($num_array);
	$pricebook->column_fields["bookname"]   = $PB_array[$i];
	$pricebook->column_fields["active"]     = $Active_array[$i];
	$pricebook->column_fields["currency_id"]     = '1';

	$pricebook->save("PriceBooks", false, false, false);
	$pricebook_ids[] = $pricebook ->id;
}

// Populate Ticket data

$status_array=array("Open","In Progress","Wait For Response","Open","Closed");
$category_array=array("Big Problem","Small Problem","Other Problem","Small Problem","Other Problem");
$ticket_title_array=array("Upload Attachment problem",
			"Individual Customization -Menu and RSS","Export Output query",
		"Import Error CSV Leads","How to automatically add a lead from a web form to VTE");

for($i=0;$i<5;$i++)
{
	$helpdesk = CRMEntity::getInstance('HelpDesk');

	$rand=array_rand($num_array);
	$contact_key = array_rand($contact_ids);
    $helpdesk->column_fields["parent_id"] 	= 	$contact_ids[$contact_key];

	$helpdesk->column_fields["ticketpriorities"]= "Normal";
	$helpdesk->column_fields["product_id"]	= 	$product_ids[$i];

	$helpdesk->column_fields["ticketseverities"]	= "Minor";
	$helpdesk->column_fields["ticketstatus"]	= $status_array[$i];
	$helpdesk->column_fields["ticketcategories"]	= $category_array[$i];
	$helpdesk->column_fields["ticket_title"]	= $ticket_title_array[$i];
	$helpdesk->column_fields["assigned_user_id"] = $assigned_user_id;

	$helpdesk->save("HelpDesk", false, false, false);
	$helpdesk_ids[] = $helpdesk->id;
}

// Populate Activities Data
$task_array=array("Tele Conference","Call user - John","Send Fax to Mary Smith");
$event_array=array("","","Call Smith","Team Meeting","Call Richie","Meeting with Don");
$task_status_array=array("Planned","In Progress","Completed");
$task_priority_array=array("High","Medium","Low");
$visibility=array("","","Private","Public","Private","Public");

for($i=0;$i<4;$i++)
{
	$event = CRMEntity::getInstance('Activity');

	$rand_num=array_rand($num_array);

	$rand_date = & create_date();
	$en=explode("-",$rand_date);
	if($en[1]<10)
		$en[1]="0".$en[1];
	if($en[2]<10)
		$en[2]="0".$en[2];
	$recur_daily_date=date('Y-m-d',mktime(0,0,0,date($en[1]),date($en[2])+5,date($en[0])));
	$recur_week_date=date('Y-m-d',mktime(0,0,0,date($en[1]),date($en[2])+30,date($en[0])));


	$start_time_hr=rand(00,23);
	$start_time_min=rand(00,59);
	$end_time_hr=rand(00,23);
	$end_time_min=rand(00,59);
	if($start_time_hr<10)
		$start_time_hr="0".$start_time_hr;
	if($start_time_min<10)
		$start_time_min="0".$start_time_min;
	if($end_time_hr<10)
		$end_time_hr="0".$end_time_hr;
	if($end_time_min<10)
		$end_time_min="0".$end_time_min;
	$end_time=$end_time_hr.":".$end_time_min;
	$start_time=$start_time_hr.":".$start_time_min;
	if($i<2)
	{
		$event->column_fields["subject"]	= $task_array[$i];
		if($i==1)
		{
			$account_key = array_rand($account_ids);
			$event->column_fields["parent_id"]	= $account_ids[$account_key];;
		}
		$event->column_fields["taskstatus"]	= $task_status_array[$i];
		$event->column_fields["taskpriority"]	= $task_priority_array[$i];
		$event->column_fields["activitytype"]	= "Task";
		$event->column_fields["visibility"] = $visibility[$i];

	}
	else
	{
		$event->column_fields["subject"]	= $event_array[$i];
		$event->column_fields["visibility"] = $visibility[$i];
		$event->column_fields["duration_hours"]	= rand(0,3);
		$event->column_fields["duration_minutes"]= rand(0,59);
		$event->column_fields["eventstatus"]	= "Planned";
		$event->column_fields["time_end"]     = $end_time;
	}
	$event->column_fields["date_start"]	= $rand_date;
	$_REQUEST["date_start"] = $rand_date;
	$event->column_fields["time_start"]	= $start_time;
	$event->column_fields["due_date"]	= $rand_date;
	$_REQUEST["due_date"] = $rand_date;
	$contact_key = array_rand($contact_ids);
        $event->column_fields["contact_id"]	= 	$contact_ids[$contact_key];
	if($i==4)
	{
        	$event->column_fields["recurringtype"] 	= "Daily";
		$_REQUEST["recurringtype"]  = "Daily";
		$event->column_fields["activitytype"]	= "Meeting";
		$event->column_fields["due_date"]	= $recur_daily_date;
	}
	elseif($i==5)
	{
        	$event->column_fields["recurringtype"] 	= "Weekly";
		$_REQUEST["recurringtype"]  = "Weekly";
		$event->column_fields["activitytype"]	= "Meeting";
		$event->column_fields["due_date"]	= $recur_week_date;
	}
	elseif($i>1)
	{
		$event->column_fields["activitytype"]	= "Call";
	}
	$event->column_fields["assigned_user_id"] = $assigned_user_id;
	$event->save("Calendar", false, false, false);
        $event_ids[] = $event->id;

}
// Turn-off Popup reminders for demo events
$adb->query("UPDATE ".$table_prefix."_act_reminder_popup set status = 1");

$adb->pquery("update ".$table_prefix."_crmentity set smcreatorid=?", array($assigned_user_id));

$expected_revenue = Array("250000","750000","500000");
$budget_cost = Array("25000","50000","90000");
$actual_cost = Array("23500","45000","80000");
$num_sent = Array("2000","2500","3000");
$clo_date = Array('2003-01-02','2004-02-03','2005-04-12');

$expected_response_count = Array("2500","7500","5000");
$expected_sales_count = Array("25000","50000","90000");
$expected_roi = Array("23","45","82");

$actual_response_count = Array("250","750","1500");
$actual_sales_count = Array("1250","5200","2390");
$actual_roi = Array("21","14","12");

$sponsor = Array("Finace","Marketing","Sales");
$targetsize = Array("210000","13390","187424");
$targetaudience = Array("Managers","CEOs","Rookies");

//$expected_response = Array(null,null,null);
for($i=0;$i<count($campaign_name_array);$i++)
{
	$campaign = CRMEntity::getInstance('Campaigns');
	$campaign_name = $campaign_name_array[$i];
	$campaign->column_fields["campaignname"] = $campaign_name;
	$campaign->column_fields["campaigntype"] = $campaign_type_array[$i];
	$campaign->column_fields["campaignstatus"] = $campaign_status_array[$i];
	$campaign->column_fields["numsent"] = $num_sent[$i];
	$campaign->column_fields["expectedrevenue"] = $expected_revenue[$i];
	$campaign->column_fields["budgetcost"] = $budget_cost[$i];
	$campaign->column_fields["actualcost"] = $actual_cost[$i];
	$campaign->column_fields["closingdate"] = $clo_date[$i];
	$campaign->column_fields["expectedresponse"] = $expected_response[$i];
	$campaign->column_fields["assigned_user_id"] = $assigned_user_id;

	$campaign->column_fields["expectedresponsecount"] = $expected_response_count[$i];
	$campaign->column_fields["expectedsalescount"] = $expected_sales_count[$i];
	$campaign->column_fields["expectedroi"] = $expected_roi[$i];
	$campaign->column_fields["actualresponsecount"] = $actual_response_count[$i];
	$campaign->column_fields["actualsalescount"] = $actual_sales_count[$i];
	$campaign->column_fields["actualroi"] = $actual_roi[$i];
	$campaign->column_fields["sponsor"] = $sponsor[$i];
	$campaign->column_fields["targetsize"] = $targetsize[$i];
	$campaign->column_fields["targetaudience"] = $targetaudience[$i];

	$campaign->save("Campaigns", false, false, false);
}


// crmv@208199
// Populate ProjectPlan
$status_array=array("prospecting","initiated","in progress","waiting for feedback","on hold","completed","delivered","archived");
$type_array=array("administrative","operative","other");
$plan_title_array=array("Projcet No.1", "Project No.2", "Project No.3");
$startdate_array = array ("2020-02-21", "2020-05-29", "2020-07-11");
$target_budget_array = array("250000","750000","500000");
$progress_array = array("10%","30%","60%");
for ($i=0; $i<3; $i++) {
	// Take the instance
	$projectPlan = CRMEntity::getInstance('ProjectPlan');

	// Assigned to
	$projectPlan->column_fields["assigned_user_id"] = $assigned_user_id;

	// Related to
	$account_key = array_rand($account_ids);
	$projectPlan->column_fields["linktoaccountscontacts"] = $account_ids[$account_key];

	// Project status
	$projectPlan->column_fields["projectstatus"] = $status_array[$i];

	// Project type
	$projectPlan->column_fields["projecttype"] = $type_array[$i];

	// Project name 
	$projectPlan->column_fields["projectname"] = $plan_title_array[$i];

	// Start date
	$projectPlan->column_fields["startdate"] = $startdate_array[$i];

	// Target end date
	$projectPlan->column_fields["targetenddate"] = date('Y-m-d', strtotime($startdate_array[$i]. ' + 1 month'));

	// Actual end date
	$projectPlan->column_fields["actualenddate"] = date('Y-m-d', strtotime($startdate_array[$i]. ' + 15 days'));

	// Target budget
	$projectPlan->column_fields["targetbudget"] = $target_budget_array[$i];

	// Progress
	$projectPlan->column_fields["progress"] = $progress_array[$i];

	$projectPlan->save("ProjectPlan", false, false, false);
	$projectPlan_ids[] = $projectPlan->id;

}
// crmv@208199e

//Populate My Sites Data
$portalname = array ($enterprise_mode);
$portalurl = array ($enterprise_website[0]);
for($i=0;$i<1;$i++)
{
	$portalid = $adb->getUniqueId($table_prefix.'_portal');
	$portal_qry = "insert into ".$table_prefix."_portal values (?,?,?,?,?)";
	$portal_params = array($portalid, $portalname[$i], $portalurl[$i], 0, 0);
	$result_qry = $adb->pquery($portal_qry, $portal_params);
}

//Populate RSS Data
$rssname = array("vte - Forums","vte development - Active Tickets");
$rssurl = array("http://forums.vtecrm.com/rss.php?name=forums&file=rss","http://trac.vtecrm.com/cgi-bin/trac.cgi/report/8?format=rss&USER=anonymous");
for($i=0;$i<2;$i++)
{
	$rssid = $adb->getUniqueId($table_prefix.'_rss');
	$rss_qry = "insert into ".$table_prefix."_rss values (?,?,?,?,?)";
	$rss_params = array($rssid, $rssurl[$i], $rssname[$i], 0, 0);
	$result_qry = $adb->pquery($rss_qry, $rss_params);
}

$adb->query("DELETE FROM com_".$table_prefix."_workflowtask_queue");

// crmv@96233 insert the example wizards
$WU = WizardUtils::getInstance();
$WU->populateDefaultWizards();
// crmv@96233e
