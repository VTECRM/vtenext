<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $table_prefix;
$customviews = Array(Array('viewname'=>'All',
			   'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
			   'cvmodule'=>'Leads','stdfilterid'=>'','advfilterid'=>''),
			/*
		     Array('viewname'=>'Hot Leads',
			   'setdefault'=>'0','setmetrics'=>'1','status'=>'3','userid'=>'1',
			   'cvmodule'=>'Leads','stdfilterid'=>'','advfilterid'=>'0'),

		     Array('viewname'=>'This Month Leads',
			   'setdefault'=>'0','setmetrics'=>'0','status'=>'3','userid'=>'1',
			   'cvmodule'=>'Leads','stdfilterid'=>'0','advfilterid'=>''),
			*/
		     Array('viewname'=>'All',
                           'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                           'cvmodule'=>'Accounts','stdfilterid'=>'','advfilterid'=>''),
			/*
		     Array('viewname'=>'Prospect Accounts',
                           'setdefault'=>'0','setmetrics'=>'1','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Accounts','stdfilterid'=>'','advfilterid'=>'1'),

		     Array('viewname'=>'New This Week',
                           'setdefault'=>'0','setmetrics'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Accounts','stdfilterid'=>'1','advfilterid'=>''),
			*/
		     Array('viewname'=>'All',
                           'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                           'cvmodule'=>'Contacts','stdfilterid'=>'','advfilterid'=>''),
			/*
		     Array('viewname'=>'Contacts Address',
                           'setdefault'=>'0','setmetrics'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Contacts','stdfilterid'=>'','advfilterid'=>''),

		     Array('viewname'=>'Todays Birthday',
                           'setdefault'=>'0','setmetrics'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Contacts','stdfilterid'=>'2','advfilterid'=>''),
			*/
		     Array('viewname'=>'All',
                           'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                           'cvmodule'=>'Potentials','stdfilterid'=>'','advfilterid'=>''),

		     Array('viewname'=>'Potentials Won',
                           'setdefault'=>'0','setmetrics'=>'1','setmobile'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Potentials','stdfilterid'=>'','advfilterid'=>'2'),

		     Array('viewname'=>'Prospecting',
                           'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Potentials','stdfilterid'=>'','advfilterid'=>'3'),

		     Array('viewname'=>'All',
                           'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                           'cvmodule'=>'HelpDesk','stdfilterid'=>'','advfilterid'=>''),
			/*
             Array('viewname'=>'Open Tickets',
                           'setdefault'=>'0','setmetrics'=>'1','status'=>'3','userid'=>'1',
                           'cvmodule'=>'HelpDesk','stdfilterid'=>'','advfilterid'=>'4'),

		     Array('viewname'=>'High Prioriy Tickets',
                           'setdefault'=>'0','setmetrics'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'HelpDesk','stdfilterid'=>'','advfilterid'=>'5'),
			*/
		     Array('viewname'=>'All',
                           'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                           'cvmodule'=>'Quotes','stdfilterid'=>'','advfilterid'=>''),

		     Array('viewname'=>'Open Quotes',
                           'setdefault'=>'0','setmetrics'=>'1','setmobile'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Quotes','stdfilterid'=>'','advfilterid'=>'6'),

		     Array('viewname'=>'Rejected Quotes',
                           'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
                           'cvmodule'=>'Quotes','stdfilterid'=>'','advfilterid'=>'7'),

		    Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Calendar','stdfilterid'=>'','advfilterid'=>''),

		    Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Emails','stdfilterid'=>'','advfilterid'=>''),

		    Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Invoice','stdfilterid'=>'','advfilterid'=>''),

		    Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Documents','stdfilterid'=>'','advfilterid'=>''),

	            Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'PriceBooks','stdfilterid'=>'','advfilterid'=>''),

	            Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Products','stdfilterid'=>'','advfilterid'=>''),

	            Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'PurchaseOrder','stdfilterid'=>'','advfilterid'=>''),

	            Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'SalesOrder','stdfilterid'=>'','advfilterid'=>''),

	            Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Vendors','stdfilterid'=>'','advfilterid'=>''),

		    Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Faq','stdfilterid'=>'','advfilterid'=>''),

		    Array('viewname'=>'All',
                          'setdefault'=>'1','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Campaigns','stdfilterid'=>'','advfilterid'=>''),

		    Array('viewname'=>'Drafted FAQ',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
                          'cvmodule'=>'Faq','stdfilterid'=>'','advfilterid'=>'8'),

		    Array('viewname'=>'Published FAQ',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
			  'cvmodule'=>'Faq','stdfilterid'=>'','advfilterid'=>'9'),

	            Array('viewname'=>'Open Purchase Orders',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
                          'cvmodule'=>'PurchaseOrder','stdfilterid'=>'','advfilterid'=>'10'),

	            Array('viewname'=>'Received Purchase Orders',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
                          'cvmodule'=>'PurchaseOrder','stdfilterid'=>'','advfilterid'=>'11'),

		    Array('viewname'=>'Open Invoices',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
			  'cvmodule'=>'Invoice','stdfilterid'=>'','advfilterid'=>'12'),

		    Array('viewname'=>'Paid Invoices',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
			  'cvmodule'=>'Invoice','stdfilterid'=>'','advfilterid'=>'13'),

	            Array('viewname'=>'Pending Sales Orders',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'3','userid'=>'1',
                          'cvmodule'=>'SalesOrder','stdfilterid'=>'','advfilterid'=>'14'),

	            //crmv@17001
	            Array('viewname'=>'Events',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'1','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Calendar','stdfilterid'=>'','advfilterid'=>'15'),
	            Array('viewname'=>'Tasks',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Calendar','stdfilterid'=>'','advfilterid'=>'16'),
	            //crmv@17001e
	            //crmv@62394
				Array('viewname'=>'Current Month Tracked Activities',
                          'setdefault'=>'0','setmetrics'=>'0','setmobile'=>'0','status'=>'0','userid'=>'1',
                          'cvmodule'=>'Calendar','stdfilterid'=>'3','advfilterid'=>'17'),
				//crmv@62394e
				
		    );


$cvcolumns = Array(Array($table_prefix.'_leaddetails:lead_no:lead_no:Leads_Lead_No:V',
					$table_prefix.'_leaddetails:lastname:lastname:Leads_Last_Name:V',
					$table_prefix.'_leaddetails:firstname:firstname:Leads_First_Name:V',
					$table_prefix.'_leaddetails:company:company:Leads_Company:V',
					$table_prefix.'_leadaddress:phone:phone:Leads_Phone:V',
					$table_prefix.'_leadsubdetails:website:website:Leads_Website:V',
					$table_prefix.'_leaddetails:email:email:Leads_Email:E',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Leads_Assigned_To:V'),
					/*
					Array($table_prefix.'_leaddetails:firstname:firstname:Leads_First_Name:V',
					$table_prefix.'_leaddetails:lastname:lastname:Leads_Last_Name:V',
					$table_prefix.'_leaddetails:company:company:Leads_Company:V',
					$table_prefix.'_leaddetails:leadsource:leadsource:Leads_Lead_Source:V',
					$table_prefix.'_leadsubdetails:website:website:Leads_Website:V',
					$table_prefix.'_leaddetails:email:email:Leads_Email:E'),

					Array($table_prefix.'_leaddetails:firstname:firstname:Leads_First_Name:V',
					$table_prefix.'_leaddetails:lastname:lastname:Leads_Last_Name:V',
					$table_prefix.'_leaddetails:company:company:Leads_Company:V',
					$table_prefix.'_leaddetails:leadsource:leadsource:Leads_Lead_Source:V',
					$table_prefix.'_leadsubdetails:website:website:Leads_Website:V',
					$table_prefix.'_leaddetails:email:email:Leads_Email:E'),
					*/
					Array($table_prefix.'_account:account_no:account_no:Accounts_Account_No:V',
					$table_prefix.'_account:accountname:accountname:Accounts_Account_Name:V',
					$table_prefix.'_accountbillads:bill_city:bill_city:Accounts_City:V',
					$table_prefix.'_account:website:website:Accounts_Website:V',
					$table_prefix.'_account:phone:phone:Accounts_Phone:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Accounts_Assigned_To:V'),
					/*
					Array($table_prefix.'_account:accountname:accountname:Accounts_Account_Name:V',
					$table_prefix.'_account:phone:phone:Accounts_Phone:V',
					$table_prefix.'_account:website:website:Accounts_Website:V',
					$table_prefix.'_account:rating:rating:Accounts_Rating:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Accounts_Assigned_To:V'),

					Array($table_prefix.'_account:accountname:accountname:Accounts_Account_Name:V',
					$table_prefix.'_account:phone:phone:Accounts_Phone:V',
					$table_prefix.'_account:website:website:Accounts_Website:V',
					$table_prefix.'_accountbillads:bill_city:bill_city:Accounts_City:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Accounts_Assigned_To:V'),
					*/
					Array($table_prefix.'_contactdetails:contact_no:contact_no:Contacts_Contact_Id:V',
					$table_prefix.'_contactdetails:firstname:firstname:Contacts_First_Name:V',
					$table_prefix.'_contactdetails:lastname:lastname:Contacts_Last_Name:V',
					$table_prefix.'_contactdetails:title:title:Contacts_Title:V',
					$table_prefix.'_contactdetails:accountid:account_id:Contacts_Account_Name:I',
					$table_prefix.'_contactdetails:email:email:Contacts_Email:E',
					$table_prefix.'_contactdetails:phone:phone:Contacts_Office_Phone:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Contacts_Assigned_To:V'),
					/*
					Array($table_prefix.'_contactdetails:firstname:firstname:Contacts_First_Name:V',
					$table_prefix.'_contactdetails:lastname:lastname:Contacts_Last_Name:V',
					$table_prefix.'_contactaddress:mailingstreet:mailingstreet:Contacts_Mailing_Street:V',
					$table_prefix.'_contactaddress:mailingcity:mailingcity:Contacts_Mailing_City:V',
					$table_prefix.'_contactaddress:mailingstate:mailingstate:Contacts_Mailing_State:V',
					$table_prefix.'_contactaddress:mailingzip:mailingzip:Contacts_Mailing_Zip:V',
					$table_prefix.'_contactaddress:mailingcountry:mailingcountry:Contacts_Mailing_Country:V'),

					Array($table_prefix.'_contactdetails:firstname:firstname:Contacts_First_Name:V',
					$table_prefix.'_contactdetails:lastname:lastname:Contacts_Last_Name:V',
					$table_prefix.'_contactdetails:title:title:Contacts_Title:V',
					$table_prefix.'_contactdetails:accountid:account_id:Contacts_Account_Name:I',
					$table_prefix.'_contactdetails:email:email:Contacts_Email:E',
					$table_prefix.'_contactsubdetails:otherphone:otherphone:Contacts_Phone:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Contacts_Assigned_To:V'),
					*/
					Array($table_prefix.'_potential:potential_no:potential_no:Potentials_Potential_No:V',
					$table_prefix.'_potential:potentialname:potentialname:Potentials_Potential_Name:V',
					$table_prefix.'_potential:related_to:related_to:Potentials_Related_To:V',
					$table_prefix.'_potential:sales_stage:sales_stage:Potentials_Sales_Stage:V',
					$table_prefix.'_potential:leadsource:leadsource:Potentials_Lead_Source:V',
					$table_prefix.'_potential:closingdate:closingdate:Potentials_Expected_Close_Date:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Potentials_Assigned_To:V'),

					Array($table_prefix.'_potential:potentialname:potentialname:Potentials_Potential_Name:V',
					$table_prefix.'_potential:related_to:related_to:Potentials_Related_To:V',
					$table_prefix.'_potential:amount:amount:Potentials_Amount:N',
					$table_prefix.'_potential:closingdate:closingdate:Potentials_Expected_Close_Date:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Potentials_Assigned_To:V'),

					Array($table_prefix.'_potential:potentialname:potentialname:Potentials_Potential_Name:V',
					$table_prefix.'_potential:related_to:related_to:Potentials_Related_To:V',
					$table_prefix.'_potential:amount:amount:Potentials_Amount:N',
					$table_prefix.'_potential:leadsource:leadsource:Potentials_Lead_Source:V',
					$table_prefix.'_potential:closingdate:closingdate:Potentials_Expected_Close_Date:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Potentials_Assigned_To:V'),

					Array(//$table_prefix.'_crmentity:crmid::HelpDesk_Ticket_ID:I',
					$table_prefix.'_troubletickets:ticket_no:ticket_no:HelpDesk_Ticket_No:V',
					$table_prefix.'_troubletickets:title:ticket_title:HelpDesk_Title:V',
					$table_prefix.'_troubletickets:parent_id:parent_id:HelpDesk_Related_To:I',
					$table_prefix.'_troubletickets:status:ticketstatus:HelpDesk_Status:V',
					$table_prefix.'_troubletickets:priority:ticketpriorities:HelpDesk_Priority:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:HelpDesk_Assigned_To:V'),
					/*
					Array($table_prefix.'_troubletickets:title:ticket_title:HelpDesk_Title:V',
					$table_prefix.'_troubletickets:parent_id:parent_id:HelpDesk_Related_To:I',
					$table_prefix.'_troubletickets:priority:ticketpriorities:HelpDesk_Priority:V',
					$table_prefix.'_troubletickets:product_id:product_id:HelpDesk_Product_Name:I',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:HelpDesk_Assigned_To:V'),

					Array($table_prefix.'_troubletickets:title:ticket_title:HelpDesk_Title:V',
					$table_prefix.'_troubletickets:parent_id:parent_id:HelpDesk_Related_To:I',
					$table_prefix.'_troubletickets:status:ticketstatus:HelpDesk_Status:V',
					$table_prefix.'_troubletickets:product_id:product_id:HelpDesk_Product_Name:I',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:HelpDesk_Assigned_To:V'),
					*/
					Array($table_prefix.'_quotes:quote_no:quote_no:Quotes_Quote_No:V',
					$table_prefix.'_quotes:subject:subject:Quotes_Subject:V',
					$table_prefix.'_quotes:quotestage:quotestage:Quotes_Quote_Stage:V',
					$table_prefix.'_quotes:potentialid:potential_id:Quotes_Potential_Name:I',
					$table_prefix.'_quotes:accountid:account_id:Quotes_Account_Name:I',
					$table_prefix.'_quotes:total:hdnGrandTotal:Quotes_Total:I',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Quotes_Assigned_To:V'),

					Array($table_prefix.'_quotes:subject:subject:Quotes_Subject:V',
					$table_prefix.'_quotes:quotestage:quotestage:Quotes_Quote_Stage:V',
					$table_prefix.'_quotes:potentialid:potential_id:Quotes_Potential_Name:I',
					$table_prefix.'_quotes:accountid:account_id:Quotes_Account_Name:I',
					$table_prefix.'_quotes:validtill:validtill:Quotes_Valid_Till:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Quotes_Assigned_To:V'),

					Array($table_prefix.'_quotes:subject:subject:Quotes_Subject:V',
					$table_prefix.'_quotes:potentialid:potential_id:Quotes_Potential_Name:I',
					$table_prefix.'_quotes:accountid:account_id:Quotes_Account_Name:I',
					$table_prefix.'_quotes:validtill:validtill:Quotes_Valid_Till:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Quotes_Assigned_To:V'),

					Array($table_prefix.'_activity:status:taskstatus:Calendar_Status:V',
					$table_prefix.'_activity:activitytype:activitytype:Calendar_Type:V',
					$table_prefix.'_activity:subject:subject:Calendar_Subject:V',
					$table_prefix.'_seactivityrel:crmid:parent_id:Calendar_Related_to:V',
					$table_prefix.'_activity:date_start:date_start:Calendar_Start_Date:D',
					$table_prefix.'_activity:due_date:due_date:Calendar_End_Date:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Calendar_Assigned_To:V'),

					Array($table_prefix.'_activity:subject:subject:Emails_Subject:V',
					$table_prefix.'_emaildetails:to_email:saved_toid:Emails_To:V',
					$table_prefix.'_activity:date_start:date_start:Emails_Date_Sent:D'),

					Array($table_prefix.'_invoice:invoice_no:invoice_no:Invoice_Invoice_No:V',
					$table_prefix.'_invoice:subject:subject:Invoice_Subject:V',
					$table_prefix.'_invoice:invoicedate:invoicedate:Invoice_Invoice_Date:D',
					$table_prefix.'_invoice:salesorderid:salesorder_id:Invoice_Sales_Order:I',
					$table_prefix.'_invoice:invoicestatus:invoicestatus:Invoice_Status:V',
					$table_prefix.'_invoice:total:hdnGrandTotal:Invoice_Total:I',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Invoice_Assigned_To:V'),

					Array($table_prefix.'_notes:note_no:note_no:Notes_Note_No:V',
					$table_prefix.'_notes:title:notes_title:Notes_Title:V',
					$table_prefix.'_notes:filename:filename:Notes_File:V',
					$table_prefix.'_crmentity:modifiedtime:modifiedtime:Notes_Modified_Time:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Notes_Assigned_To:V'),

					Array($table_prefix.'_pricebook:pricebook_no:pricebook_no:PriceBooks_PriceBook_No:V',
					$table_prefix.'_pricebook:bookname:bookname:PriceBooks_Price_Book_Name:V',
					$table_prefix.'_pricebook:active:active:PriceBooks_Active:V',
					$table_prefix.'_pricebook:currency_id:currency_id:PriceBooks_Currency:I'),

					Array($table_prefix.'_products:product_no:product_no:Products_Product_No:V',
			  		$table_prefix.'_products:productname:productname:Products_Product_Name:V',
			  		$table_prefix.'_products:productcode:productcode:Products_Part_Number:V',
			  		$table_prefix.'_products:commissionrate:commissionrate:Products_Commission_Rate:V',
			  		$table_prefix.'_products:qtyinstock:qtyinstock:Products_Quantity_In_Stock:V',
			  		$table_prefix.'_products:qty_per_unit:qty_per_unit:Products_Qty/Unit:V',
			  		$table_prefix.'_products:unit_price:unit_price:Products_Unit_Price:V'),

			  		Array($table_prefix.'_purchaseorder:purchaseorder_no:purchaseorder_no:PurchaseOrder_PurchaseOrder_No:V',
			  		$table_prefix.'_purchaseorder:subject:subject:PurchaseOrder_Subject:V',
			  		$table_prefix.'_purchaseorder:vendorid:vendor_id:PurchaseOrder_Vendor_Name:I',
			  		$table_prefix.'_purchaseorder:tracking_no:tracking_no:PurchaseOrder_Tracking_Number:V',
					$table_prefix.'_purchaseorder:total:hdnGrandTotal:PurchaseOrder_Total:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:PurchaseOrder_Assigned_To:V'),

					Array($table_prefix.'_salesorder:salesorder_no:salesorder_no:SalesOrder_SalesOrder_No:V',
					$table_prefix.'_salesorder:subject:subject:SalesOrder_Subject:V',
					$table_prefix.'_salesorder:accountid:account_id:SalesOrder_Account_Name:I',
					$table_prefix.'_salesorder:quoteid:quote_id:SalesOrder_Quote_Name:I',
					$table_prefix.'_salesorder:total:hdnGrandTotal:SalesOrder_Total:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:SalesOrder_Assigned_To:V'),

					Array($table_prefix.'_vendor:vendor_no:vendor_no:Vendors_Vendor_No:V',
					$table_prefix.'_vendor:vendorname:vendorname:Vendors_Vendor_Name:V',
					$table_prefix.'_vendor:phone:phone:Vendors_Phone:V',
					$table_prefix.'_vendor:email:email:Vendors_Email:E',
					$table_prefix.'_vendor:category:category:Vendors_Category:V'),

					Array(//$table_prefix.'_faq:id::Faq_FAQ_Id:I',
					$table_prefix.'_faq:faq_no:faq_no:Faq_Faq_No:V',
					$table_prefix.'_faq:question:question:Faq_Question:V',
					$table_prefix.'_faq:category:faqcategories:Faq_Category:V',
					$table_prefix.'_faq:product_id:product_id:Faq_Product_Name:I',
					$table_prefix.'_crmentity:createdtime:createdtime:Faq_Created_Time:D',
					$table_prefix.'_crmentity:modifiedtime:modifiedtime:Faq_Modified_Time:D'),
					//this sequence has to be maintained
					Array($table_prefix.'_campaign:campaign_no:campaign_no:Campaigns_Campaign_No:V',
					$table_prefix.'_campaign:campaignname:campaignname:Campaigns_Campaign_Name:V',
					$table_prefix.'_campaign:campaigntype:campaigntype:Campaigns_Campaign_Type:N',
					$table_prefix.'_campaign:campaignstatus:campaignstatus:Campaigns_Campaign_Status:N',
					$table_prefix.'_campaign:expectedrevenue:expectedrevenue:Campaigns_Expected_Revenue:V',
					$table_prefix.'_campaign:closingdate:closingdate:Campaigns_Expected_Close_Date:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Campaigns_Assigned_To:V'),

					Array ($table_prefix.'_faq:question:question:Faq_Question:V',
					$table_prefix.'_faq:status:faqstatus:Faq_Status:V',
					$table_prefix.'_faq:product_id:product_id:Faq_Product_Name:I',
					$table_prefix.'_faq:category:faqcategories:Faq_Category:V',
					$table_prefix.'_crmentity:createdtime:createdtime:Faq_Created_Time:T'),

					Array( $table_prefix.'_faq:question:question:Faq_Question:V',
					$table_prefix.'_faq:answer:faq_answer:Faq_Answer:V',
					$table_prefix.'_faq:status:faqstatus:Faq_Status:V',
					$table_prefix.'_faq:product_id:product_id:Faq_Product_Name:I',
					$table_prefix.'_faq:category:faqcategories:Faq_Category:V',
					$table_prefix.'_crmentity:createdtime:createdtime:Faq_Created_Time:T'),

					Array(	 $table_prefix.'_purchaseorder:subject:subject:PurchaseOrder_Subject:V',
					$table_prefix.'_purchaseorder:postatus:postatus:PurchaseOrder_Status:V',
					$table_prefix.'_purchaseorder:vendorid:vendor_id:PurchaseOrder_Vendor_Name:I',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:PurchaseOrder_Assigned_To:V',
					$table_prefix.'_purchaseorder:duedate:duedate:PurchaseOrder_Due_Date:V'),

					Array ($table_prefix.'_purchaseorder:subject:subject:PurchaseOrder_Subject:V',
					$table_prefix.'_purchaseorder:vendorid:vendor_id:PurchaseOrder_Vendor_Name:I',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:PurchaseOrder_Assigned_To:V',
					$table_prefix.'_purchaseorder:postatus:postatus:PurchaseOrder_Status:V',
					$table_prefix.'_purchaseorder:carrier:carrier:PurchaseOrder_Carrier:V',
					$table_prefix.'_poshipads:ship_street:ship_street:PurchaseOrder_Shipping_Address:V'),

					Array(  $table_prefix.'_invoice:invoice_no:invoice_no:Invoice_Invoice_No:V',
					$table_prefix.'_invoice:subject:subject:Invoice_Subject:V',
					$table_prefix.'_invoice:accountid:account_id:Invoice_Account_Name:I',
					$table_prefix.'_invoice:salesorderid:salesorder_id:Invoice_Sales_Order:I',
					$table_prefix.'_invoice:invoicestatus:invoicestatus:Invoice_Status:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Invoice_Assigned_To:V',
					$table_prefix.'_crmentity:createdtime:createdtime:Invoice_Created_Time:T'),

					Array(	 $table_prefix.'_invoice:invoice_no:invoice_no:Invoice_Invoice_No:V',
					$table_prefix.'_invoice:subject:subject:Invoice_Subject:V',
					$table_prefix.'_invoice:accountid:account_id:Invoice_Account_Name:I',
					$table_prefix.'_invoice:salesorderid:salesorder_id:Invoice_Sales_Order:I',
					$table_prefix.'_invoice:invoicestatus:invoicestatus:Invoice_Status:V',
					$table_prefix.'_invoiceshipads:ship_street:ship_street:Invoice_Shipping_Address:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Invoice_Assigned_To:V'),

					Array(	 $table_prefix.'_salesorder:subject:subject:SalesOrder_Subject:V',
					$table_prefix.'_salesorder:accountid:account_id:SalesOrder_Account_Name:I',
					$table_prefix.'_salesorder:sostatus:sostatus:SalesOrder_Status:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:SalesOrder_Assigned_To:V',
					$table_prefix.'_soshipads:ship_street:ship_street:SalesOrder_Shipping_Address:V',
					$table_prefix.'_salesorder:carrier:carrier:SalesOrder_Carrier:V'),

					//crmv@17001
					Array(	$table_prefix.'_activity:eventstatus:eventstatus:Calendar_Status:V',
					$table_prefix.'_activity:activitytype:activitytype:Calendar_Activity_Type:V',
					$table_prefix.'_activity:subject:subject:Calendar_Subject:V',
					$table_prefix.'_seactivityrel:crmid:parent_id:Calendar_Related_To:I',
					$table_prefix.'_activity:date_start:date_start:Calendar_Start_Date_&amp;_Time:DT',
					$table_prefix.'_activity:due_date:due_date:Calendar_Due_Date:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Calendar_Assigned_To:I'
					),
					Array(	$table_prefix.'_activity:status:taskstatus:Calendar_Status:V',
					$table_prefix.'_activity:activitytype:activitytype:Calendar_Activity_Type:V',
					$table_prefix.'_activity:subject:subject:Calendar_Subject:V',
					$table_prefix.'_seactivityrel:crmid:parent_id:Calendar_Related_To:I',
					$table_prefix.'_activity:date_start:date_start:Calendar_Start_Date_&amp;_Time:DT',
					$table_prefix.'_activity:due_date:due_date:Calendar_Due_Date:D',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Calendar_Assigned_To:I'
					),
					//crmv@17001e
					//crmv@62394
					Array(	$table_prefix.'_activity:subject:subject:Calendar_Subject:V',
					$table_prefix.'_crmentity:smownerid:assigned_user_id:Calendar_Assigned_To:V',
					$table_prefix.'_activity:date_start:date_start:Calendar_Start_Date:DT',
					$table_prefix.'_activity:time_start:time_start:Calendar_Time_Start:T',
					$table_prefix.'_activity:due_date:due_date:Calendar_End_Date:D',
					$table_prefix.'_activity:time_end:time_end:Calendar_End_Time:T',
					$table_prefix.'_seactivityrel:crmid:parent_id:Calendar_Related_to:V'
					),
					//crmv@62394e
);


$cvstdfilters = Array(Array('columnname'=>$table_prefix.'_crmentity:modifiedtime:modifiedtime:Leads_Modified_Time',
                            'datefilter'=>'thismonth',
                            'startdate'=>'2005-06-01',
                            'enddate'=>'2005-06-30'),

		      Array('columnname'=>$table_prefix.'_crmentity:createdtime:createdtime:Accounts_Created_Time',
                            'datefilter'=>'thisweek',
                            'startdate'=>'2005-06-19',
                            'enddate'=>'2005-06-25'),

		      Array('columnname'=>$table_prefix.'_contactsubdetails:birthday:birthday:Contacts_Birthdate',
                            'datefilter'=>'today',
                            'startdate'=>'2005-06-25',
                            'enddate'=>'2005-06-25'),
			//crmv@62394
			Array('columnname'=>$table_prefix.'_activity:date_start:date_start:Calendar_Start_Date_&_Time',
                            'datefilter'=>'thismonth',
                            'startdate'=>'2015-01-01',
                            'enddate'=>'2015-01-31'),
			//crmv@62394e

                     );

$cvadvfilters = Array(
                	Array(
               			 Array('columnname'=>$table_prefix.'_leaddetails:leadstatus:leadstatus:Leads_Lead_Status:V',
		                      'comparator'=>'e',
        		              'value'=>'Hot'
                     			)
                     	 ),
		      		Array(
                          Array('columnname'=>$table_prefix.'_account:account_type:accounttype:Accounts_Type:V',
                                'comparator'=>'e',
                                 'value'=>'Prospect'
                                 )
                           ),
				     Array(
                            Array('columnname'=>$table_prefix.'_potential:sales_stage:sales_stage:Potentials_Sales_Stage:V',
                                  'comparator'=>'e',
                                  'value'=>'Closed Won'
                                 )
                           ),
				     Array(
                            Array('columnname'=>$table_prefix.'_potential:sales_stage:sales_stage:Potentials_Sales_Stage:V',
                                  'comparator'=>'e',
                                  'value'=>'Prospecting'
                                 )
                           ),
				     Array(
                            Array('columnname'=>$table_prefix.'_troubletickets:status:ticketstatus:HelpDesk_Status:V',
                                  'comparator'=>'n',
                                  'value'=>'Closed'
                                 )
                           ),
				     Array(
                            Array('columnname'=>$table_prefix.'_troubletickets:priority:ticketpriorities:HelpDesk_Priority:V',
                                  'comparator'=>'e',
                                  'value'=>'High'
                                 )
                           ),
				     Array(
	                        Array('columnname'=>$table_prefix.'_quotes:quotestage:quotestage:Quotes_Quote_Stage:V',
                                  'comparator'=>'n',
                                  'value'=>'Accepted'
                                 ),
						    Array('columnname'=>$table_prefix.'_quotes:quotestage:quotestage:Quotes_Quote_Stage:V',
                                  'comparator'=>'n',
                                  'value'=>'Rejected'
                                 )
                           ),
				     Array(
                            Array('columnname'=>$table_prefix.'_quotes:quotestage:quotestage:Quotes_Quote_Stage:V',
                                  'comparator'=>'e',
                                  'value'=>'Rejected'
                                 )
			 ),

			Array(
                          Array('columnname'=>$table_prefix.'_faq:status:faqstatus:Faq_Status:V',
                                'comparator'=>'e',
                                 'value'=>'Draft'
                                 )
			 ),

			Array(
                          Array('columnname'=>$table_prefix.'_faq:status:faqstatus:Faq_Status:V',
                                'comparator'=>'e',
                                 'value'=>'Published'
                                 )
			 ),

			Array(
                          Array('columnname'=>$table_prefix.'_purchaseorder:postatus:postatus:PurchaseOrder_Status:V',
                                'comparator'=>'e',
                                 'value'=>'Created, Approved, Delivered'
                                 )
			 ),

			Array(
                          Array('columnname'=>$table_prefix.'_purchaseorder:postatus:postatus:PurchaseOrder_Status:V',
                                'comparator'=>'e',
                                 'value'=>'Received Shipment'
                                 )
			 ),

			Array(
                          Array('columnname'=>$table_prefix.'_invoice:invoicestatus:invoicestatus:Invoice_Status:V',
                                'comparator'=>'e',
                                 'value'=>'Created, Approved, Sent'
                                 )
			 ),

			Array(
                          Array('columnname'=>$table_prefix.'_invoice:invoicestatus:invoicestatus:Invoice_Status:V',
                                'comparator'=>'e',
                                 'value'=>'Paid'
                                 )
			 ),

			Array(
                          Array('columnname'=>$table_prefix.'_salesorder:sostatus:sostatus:SalesOrder_Status:V',
                                'comparator'=>'e',
                                 'value'=>'Created, Approved'
                                 )
             //crmv@17001
			 ),
			 Array(
                          Array('columnname'=>$table_prefix.'_activity:activitytype:activitytype:Calendar_Activity_Type:V',
                                'comparator'=>'n',
                                 'value'=>'Task'
                                 )
			 ),
			 Array(
                          Array('columnname'=>$table_prefix.'_activity:activitytype:activitytype:Calendar_Activity_Type:V',
                                'comparator'=>'e',
                                 'value'=>'Task'
                                 )
			 ),
			 //crmv@17001e
			// crmv@62394
			Array(
				Array('columnname'=>$table_prefix.'_activity:activitytype:activitytype:Calendar_Activity_Type:V',
					'comparator'=>'e',
					'value'=>'Tracked'
				)
			),
			// crmv@62394e

);

foreach($customviews as $key=>$customview)
{
	$queryid = insertCustomView($customview['viewname'],$customview['setdefault'],$customview['setmetrics'],$customview['setmobile'],$customview['cvmodule'],$customview['status'],$customview['userid']); // crmv@49398
	insertCvColumns($queryid,$cvcolumns[$key]);

	if(isset($cvstdfilters[$customview['stdfilterid']]))
	{
		$i = $customview['stdfilterid'];
		insertCvStdFilter($queryid,$cvstdfilters[$i]['columnname'],$cvstdfilters[$i]['datefilter'],$cvstdfilters[$i]['startdate'],$cvstdfilters[$i]['enddate']);
	}
	if(isset($cvadvfilters[$customview['advfilterid']]))
	{
		insertCvAdvFilter($queryid,$cvadvfilters[$customview['advfilterid']]);
	}
}

	/** to store the details of the customview in vte_customview table
	  * @param $viewname :: Type String
	  * @param $setdefault :: Type Integer
	  * @param $setmetrics :: Type Integer
	  * @param $cvmodule :: Type String
	  * @returns  $customviewid of the stored custom view :: Type integer
	 */
function insertCustomView($viewname,$setdefault,$setmetrics,$setmobile,$cvmodule,$status,$userid) // crmv@49398
{
	global $adb,$table_prefix;

	$genCVid = $adb->getUniqueID($table_prefix."_customview");

	if($genCVid != "")
	{
		// crmv@49398
		$customviewsql = "insert into ".$table_prefix."_customview(cvid,viewname,setdefault,setmetrics,setmobile,entitytype,status,userid) values(?,?,?,?,?,?,?,?)";
		$customviewparams = array($genCVid, $viewname, $setdefault, $setmetrics, $setmobile, $cvmodule, $status, $userid);
		$customviewresult = $adb->pquery($customviewsql, $customviewparams);
		// crmv@49398e
	}
	return $genCVid;
}

	/** to store the custom view columns of the customview in vte_cvcolumnlist table
	  * @param $cvid :: Type Integer
	  * @param $columnlist :: Type Array of columnlists
	 */
function insertCvColumns($CVid,$columnslist)
{
	global $adb,$table_prefix;
	if($CVid != "")
	{
		for($i=0;$i<count($columnslist);$i++)
		{
			$columnsql = "insert into ".$table_prefix."_cvcolumnlist (cvid,columnindex,columnname) values(?,?,?)";
			$columnparams = array($CVid, $i, $columnslist[$i]);
			$columnresult = $adb->pquery($columnsql, $columnparams);
		}
	}
}

	/** to store the custom view stdfilter of the customview in vte_cvstdfilter table
	  * @param $cvid :: Type Integer
	  * @param $filtercolumn($tablename:$columnname:$fieldname:$fieldlabel) :: Type String
	  * @param $filtercriteria(filter name) :: Type String
	  * @param $startdate :: Type String
	  * @param $enddate :: Type String
	  * returns nothing
	 */
function insertCvStdFilter($CVid,$filtercolumn,$filtercriteria,$startdate,$enddate)
{
	global $adb,$table_prefix;
	if($CVid != "")
	{
		$stdfiltersql = "insert into ".$table_prefix."_cvstdfilter(cvid,columnname,stdfilter,startdate,enddate) values (?,?,?,?,?)";
		$stdfilterparams = array($CVid, $filtercolumn, $filtercriteria, $startdate, $enddate);
		$stdfilterresult = $adb->pquery($stdfiltersql, $stdfilterparams);
	}
}

	/** to store the custom view advfilter of the customview in vte_cvadvfilter table
	  * @param $cvid :: Type Integer
	  * @param $filters :: Type Array('columnname'=>$tablename:$columnname:$fieldname:$fieldlabel,'comparator'=>$comparator,'value'=>$value)
	  * returns nothing
	 */

function insertCvAdvFilter($CVid,$filters)
{
	global $adb,$table_prefix;
	if($CVid != "")
	{
		foreach($filters as $i=>$filter)
		{
			$advfiltersql = "insert into ".$table_prefix."_cvadvfilter(cvid,columnindex,columnname,comparator,value) values (?,?,?,?,?)";
			$advfilterparams = array($CVid, $i, $filter['columnname'], $filter['comparator'], $filter['value']);
			$advfilterresult = $adb->pquery($advfiltersql, $advfilterparams);
		}
	}
}
?>