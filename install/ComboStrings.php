<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* default strings for picklist used during install */
 
global $combo_strings;

$combo_strings = array(
	'accounttype_dom' => array(
		'' => '', 'Analyst' => 'Analyst', 'Competitor' => 'Competitor', 'Customer' => 'Customer', 'Integrator' => 'Integrator', 'Investor' => 'Investor', 'Partner' => 'Partner', 'Press' => 'Press', 'Prospect' => 'Prospect', 'Reseller' => 'Reseller', 'Other' => 'Other'
	),
	'industry_dom' => array(
		'' => '', 'Apparel' => 'Apparel', 'Banking' => 'Banking', 'Biotechnology' => 'Biotechnology', 'Chemicals' => 'Chemicals', 'Communications' => 'Communications', 'Construction' => 'Construction', 'Consulting' => 'Consulting', 'Education' => 'Education', 'Electronics' => 'Electronics', 'Energy' => 'Energy', 'Engineering' => 'Engineering', 'Entertainment' => 'Entertainment', 'Environmental' => 'Environmental', 'Finance' => 'Finance', 'Food & Beverage' => 'Food & Beverage', 'Government' => 'Government', 'Healthcare' => 'Healthcare', 'Hospitality' => 'Hospitality', 'Insurance' => 'Insurance', 'Machinery' => 'Machinery', 'Manufacturing' => 'Manufacturing', 'Media' => 'Media', 'Not For Profit' => 'Not For Profit', 'Recreation' => 'Recreation', 'Retail' => 'Retail', 'Shipping' => 'Shipping', 'Technology' => 'Technology', 'Telecommunications' => 'Telecommunications', 'Transportation' => 'Transportation', 'Utilities' => 'Utilities', 'Other' => 'Other'
	),
	'leadsource_dom' => array(
		'' => '', 'Cold Call' => 'Cold Call', 'Existing Customer' => 'Existing Customer', 'Self Generated' => 'Self Generated', 'Employee' => 'Employee', 'Partner' => 'Partner', 'Public Relations' => 'Public Relations', 'Direct Mail' => 'Direct Mail', 'Conference' => 'Conference', 'Trade Show' => 'Trade Show', 'Web Site' => 'Web Site', 'Word of mouth' => 'Word of mouth', 'Other' => 'Other', 'Mail Converter' => 'Mail Converter'
	),
	'leadstatus_dom' => array(
		'' => '', 'Attempted to Contact' => 'Attempted to Contact', 'Cold' => 'Cold', 'Contact in Future' => 'Contact in Future', 'Contacted' => 'Contacted', 'Hot' => 'Hot', 'Junk Lead' => 'Junk Lead', 'Lost Lead' => 'Lost Lead', 'Not Contacted' => 'Not Contacted', 'Pre Qualified' => 'Pre Qualified', 'Qualified' => 'Qualified', 'Warm' => 'Warm'
	),
	'rating_dom' => array(
		'' => '', 'Acquired' => 'Acquired', 'Active' => 'Active', 'Market Failed' => 'Market Failed', 'Project Cancelled' => 'Project Cancelled', 'Shutdown' => 'Shutdown'
	),
	'opportunity_type_dom' => array(
		'' => '', 'Existing Business' => 'Existing Business', 'New Business' => 'New Business'
	),
	'sales_stage_dom' => array(
		'Prospecting' => 'Prospecting', 'Qualification' => 'Qualification', 'Needs Analysis' => 'Needs Analysis', 'Value Proposition' => 'Value Proposition', 'Id. Decision Makers' => 'Id. Decision Makers', 'Perception Analysis' => 'Perception Analysis', 'Proposal/Price Quote' => 'Proposal/Price Quote', 'Negotiation/Review' => 'Negotiation/Review', 'Closed Won' => 'Closed Won', 'Closed Lost' => 'Closed Lost',
		'PotentialOpen' => 'PotentialOpen',
		'PotentialBudgeting' => 'PotentialBudgeting',
	),
	'salutationtype_dom' => array(
		'' => '', 'Mr.' => 'Mr.', 'Ms.' => 'Ms.', 'Mrs.' => 'Mrs.', 'Dr.' => 'Dr.', 'Prof.' => 'Prof.'
	),
	'eventstatus_dom' => array(
		'Planned' => 'Planned', 'Held' => 'Held', 'Not Held' => 'Not Held'
	),
	'taskstatus_dom' => array(
		'Not Started' => 'Not Started', 'In Progress' => 'In Progress', 'Completed' => 'Completed', 'Pending Input' => 'Pending Input', 'Deferred' => 'Deferred', 'Planned' => 'Planned'
	),
	'taskpriority_dom' => array(
		'High' => 'High', 'Medium' => 'Medium', 'Low' => 'Low'
	),
	'duration_minutes_dom' => array(
		'00' => '00', '15' => '15', '30' => '30', '45' => '45'
	),
	'productcategory_dom' => array(
		'' => '', 'Hardware' => 'Hardware', 'Software' => 'Software', 'CRM Applications' => 'CRM Applications'
	),
	'manufacturer_dom' => array(
		'' => '', 'AltvetPet Inc.' => 'AltvetPet Inc.', 'LexPon Inc.' => 'LexPon Inc.', 'MetBeat Corp' => 'MetBeat Corp'
	),
	'ticketcategories_dom' => array(
		'Big Problem' => 'Big Problem', 'Small Problem' => 'Small Problem', 'Other Problem' => 'Other Problem'
	),
	'ticketpriorities_dom' => array(
		'Low' => 'Low', 'Normal' => 'Normal', 'High' => 'High', 'Urgent' => 'Urgent'
	),
	'ticketseverities_dom' => array(
		'Minor' => 'Minor', 'Major' => 'Major', 'Feature' => 'Feature', 'Critical' => 'Critical'
	),
	
	'ticketstatus_dom' => array(
		'Open' => 'Open', 'In Progress' => 'In Progress', 'Wait For Response' => 'Wait For Response', 'Answered by customer' => 'Answered by customer'	//crmv@126830
		, 'Closed' => 'Closed'
	),
	
	'activitytype_dom' => array(
		'Call' => 'Call', 'Meeting' => 'Meeting'
	),
	//crmv@36871
	'exp_duration_dom' => array(
		'Duration15' => 'Duration15',
		'Duration30' => 'Duration30',
		'Duration45' => 'Duration45',
		'Duration60' => 'Duration60',
		'DurationMore' => 'DurationMore',
	),
	//crmv@36871e
	
	'faqcategories_dom' => array('General' => 'General'),
	
	'faqstatus_dom' => array(
		'Draft' => 'Draft', 'Reviewed' => 'Reviewed', 'Published' => 'Published', 'Obsolete' => 'Obsolete'
	),
	
	
	'currency_dom' => array(
		'Rupees' => 'Rupees',
		'Dollar' => 'Dollar',
		'Euro' => 'Euro'
	),
	//crmv@17001
	'visibility_dom' => array(
		'Standard' => 'Standard',
		'Private' => 'Private',
		'Public' => 'Public'
	),
	//crmv@17001e
	'usageunit_dom' => array(
		'Box' => 'Box',
		'Carton' => 'Carton',
		'Dozen' => 'Dozen',
		'Each' => 'Each',
		'Hours' => 'Hours',
		'Impressions' => 'Impressions',
		'Lb' => 'Lb',
		'M' => 'M',
		'Pack' => 'Pack',
		'Pages' => 'Pages',
		'Pieces' => 'Pieces',
		'Quantity' => 'Quantity',
		'Reams' => 'Reams',
		'Sheet' => 'Sheet',
		'Spiral Binder' => 'Spiral Binder',
		'Sq Ft' => 'Sq Ft'
	),
	
	'glacct_dom' => array(
		'300-Sales-Software' => '300-Sales-Software',
		'301-Sales-Hardware' => '301-Sales-Hardware',
		'302-Rental-Income' => '302-Rental-Income',
		'303-Interest-Income' => '303-Interest-Income',
		'304-Sales-Software-Support' => '304-Sales-Software-Support',
		'305-Sales Other' => '305-Sales Other',
		'306-Internet Sales' => '306-Internet Sales',
		'307-Service-Hardware Labor' => '307-Service-Hardware Labor',
		'308-Sales-Books' => '308-Sales-Books'
	),
	
	'quotestage_dom' => array(
		'Created' => 'Created',
		'Delivered' => 'Delivered',
		'Reviewed' => 'Reviewed',
		'Accepted' => 'Accepted',
		'Rejected' => 'Rejected'
	),
	
	'carrier_dom' => array(
		'FedEx' => 'FedEx',
		'UPS' => 'UPS',
		'USPS' => 'USPS',
		'DHL' => 'DHL',
		'BlueDart' => 'BlueDart'
	),
	
	'taxclass_dom' => array(
		'SalesTax' => 'SalesTax',
		'Vat' => 'Vat'
	),
	
	'recurringtype_dom' => array(
		'' => '',
		'Daily' => 'Daily',
		'Weekly' => 'Weekly',
		'Monthly' => 'Monthly',
		'Yearly' => 'Yearly'
	),
	
	'invoicestatus_dom' => array(
		'AutoCreated' => 'AutoCreated',
		'Created' => 'Created',
		'Approved' => 'Approved',
		'Sent' => 'Sent',
		'Credit Invoice' => 'Credit Invoice',
		'Paid' => 'Paid'
	),
	
	'postatus_dom' => array(
		'Created' => 'Created',
		'Approved' => 'Approved',
		'Delivered' => 'Delivered',
		'Cancelled' => 'Cancelled',
		'Received Shipment' => 'Received Shipment'
	),
	
	'sostatus_dom' => array(
		'Created' => 'Created',
		'Approved' => 'Approved',
		'Delivered' => 'Delivered',
		'Cancelled' => 'Cancelled'
	),
	
	'campaignstatus_dom' => array(
		'' => '',
		'Planning' => 'Planning',
		'Active' => 'Active',
		'Inactive' => 'Inactive',
		'Completed' => 'Completed',
		'Cancelled' => 'Cancelled',
	),
	
	
	'campaigntype_dom' => array(
		'' => '',
		'Conference' => 'Conference',
		'Webinar' => 'Webinar',
		'Trade Show' => 'Trade Show',
		'Public Relations' => 'Public Relations',
		'Partners' => 'Partners',
		'Referral Program' => 'Referral Program',
		'Advertisement' => 'Advertisement',
		'Banner Ads' => 'Banner Ads',
		'Direct Mail' => 'Direct Mail',
		'Email' => 'Email',
		'Telemarketing' => 'Telemarketing',
		'GDPR' => 'GDPR', // crmv@161554
		'Others' => 'Others'
	),
	
	'expectedresponse_dom' => array(
		'' => '',
		'Excellent' => 'Excellent',
		'Good' => 'Good',
		'Average' => 'Average',
		'Poor' => 'Poor'
	),
	'status_dom' => array(
		'Active' => 'Active',
		'Inactive' => 'Inactive'
	),
	//crmv@17001
	'activity_view_dom' => array(
		'Today' => 'Today',
		'This Week' => 'This Week',
		'This Month' => 'This Month'
	),
	//crmv@17001e
	'lead_view_dom' => array(
		'Today' => 'Today',
		'Last 2 Days' => 'Last 2 Days',
		'Last Week' => 'Last Week'
	),
	'date_format_dom' => array(
		'dd-mm-yyyy' => 'dd-mm-yyyy',
		'mm-dd-yyyy' => 'mm-dd-yyyy',
		'yyyy-mm-dd' => 'yyyy-mm-dd'
	),
	'reminder_interval_dom' => array(
		'None' => 'None',
		'1 Minute' => '1 Minute',
		'5 Minutes' => '5 Minutes',
		'15 Minutes' => '15 Minutes',
		'30 Minutes' => '30 Minutes',
		'45 Minutes' => '45 Minutes',
		'1 Hour' => '1 Hour',
		'1 Day' => '1 Day'
	),
	
	'recurring_frequency_dom' => array(
		'--None--' => '--None--',
		'Daily' => 'Daily',
		'Weekly' => 'Weekly',
		'Monthly' => 'Monthly',
		'Quarterly' => 'Quarterly',
		'Yearly' => 'Yearly'
	),
	'payment_duration_dom' => array(
		'Net 30 days' => 'Net 30 days',
		'Net 45 days' => 'Net 45 days',
		'Net 60 days' => 'Net 60 days'
	),
	'campaignrelstatus_dom' => array(
		'--None--' => '--None--',
		'Contacted - Successful' => 'Contacted - Successful',
		'Contacted - Unsuccessful' => 'Contacted - Unsuccessful',
		'Contacted - Never Contact Again' => 'Contacted - Never Contact Again'
	),
	//crmv@22622
	'menu_view_dom' => array(
		'Large Menu' => 'Large Menu',
		'Small Menu' => 'Small Menu'
	),
	//crmv@22622e
	//crmv@29617
	'notify_me_via_dom' => array(
		'ModNotifications' => 'ModNotifications',
		'Emails' => 'Emails'
	),
	//crmv@33465
	'notify_summary_dom' => array(
		'Never' => 'Never',
		'Every week' => 'Every week',
		'Every 2 days' => 'Every 2 days',
		'Every day' => 'Every day',
		'Every 4 hours' => 'Every 4 hours',
		'Every 2 hours' => 'Every 2 hours',
		'Hourly' => 'Hourly'
	),
	//crmv@33465e
	//crmv@29617e
	//crmv@25610
	'user_timezone_dom' => array(
		"Pacific/Midway" => "Pacific/Midway",
		"America/Adak" => "America/Adak",
		"Etc/GMT+10" => "Etc/GMT+10",
		"Pacific/Marquesas" => "Pacific/Marquesas",
		"Pacific/Gambier" => "Pacific/Gambier",
		"America/Anchorage" => "America/Anchorage",
		"America/Ensenada" => "America/Ensenada",
		"Etc/GMT+8" => "Etc/GMT+8",
		"America/Los_Angeles" => "America/Los_Angeles",
		"America/Denver" => "America/Denver",
		"America/Chihuahua" => "America/Chihuahua",
		"America/Dawson_Creek" => "America/Dawson_Creek",
		"America/Belize" => "America/Belize",
		"America/Cancun" => "America/Cancun",
		"Chile/EasterIsland" => "Chile/EasterIsland",
		"America/Chicago" => "America/Chicago",
		"America/New_York" => "America/New_York",
		"America/Havana" => "America/Havana",
		"America/Bogota" => "America/Bogota",
		"America/Caracas" => "America/Caracas",
		"America/Santiago" => "America/Santiago",
		"America/La_Paz" => "America/La_Paz",
		"Atlantic/Stanley" => "Atlantic/Stanley",
		"America/Campo_Grande" => "America/Campo_Grande",
		"America/Goose_Bay" => "America/Goose_Bay",
		"America/Glace_Bay" => "America/Glace_Bay",
		"America/St_Johns" => "America/St_Johns",
		"America/Araguaina" => "America/Araguaina",
		"America/Montevideo" => "America/Montevideo",
		"America/Miquelon" => "America/Miquelon",
		"America/Godthab" => "America/Godthab",
		"America/Argentina/Buenos_Aires" => "America/Argentina/Buenos_Aires",
		"America/Sao_Paulo" => "America/Sao_Paulo",
		"America/Noronha" => "America/Noronha",
		"Atlantic/Cape_Verde" => "Atlantic/Cape_Verde",
		"Atlantic/Azores" => "Atlantic/Azores",
		"Europe/Belfast" => "Europe/Belfast",
		"Europe/Dublin" => "Europe/Dublin",
		"Europe/Lisbon" => "Europe/Lisbon",
		"Europe/London" => "Europe/London",
		"Africa/Abidjan" => "Africa/Abidjan",
		"Europe/Rome" => "Europe/Rome",
		"Europe/Belgrade" => "Europe/Belgrade",
		"Europe/Brussels" => "Europe/Brussels",
		"Africa/Algiers" => "Africa/Algiers",
		"Africa/Windhoek" => "Africa/Windhoek",
		"Asia/Beirut" => "Asia/Beirut",
		"Africa/Cairo" => "Africa/Cairo",
		"Asia/Gaza" => "Asia/Gaza",
		"Africa/Blantyre" => "Africa/Blantyre",
		"Asia/Jerusalem" => "Asia/Jerusalem",
		"Europe/Minsk" => "Europe/Minsk",
		"Asia/Damascus" => "Asia/Damascus",
		"Europe/Moscow" => "Europe/Moscow",
		"Africa/Addis_Ababa" => "Africa/Addis_Ababa",
		"Asia/Tehran" => "Asia/Tehran",
		"Asia/Dubai" => "Asia/Dubai",
		"Asia/Yerevan" => "Asia/Yerevan",
		"Asia/Kabul" => "Asia/Kabul",
		"Asia/Yekaterinburg" => "Asia/Yekaterinburg",
		"Asia/Tashkent" => "Asia/Tashkent",
		"Asia/Kolkata" => "Asia/Kolkata",
		"Asia/Katmandu" => "Asia/Katmandu",
		"Asia/Dhaka" => "Asia/Dhaka",
		"Asia/Novosibirsk" => "Asia/Novosibirsk",
		"Asia/Rangoon" => "Asia/Rangoon",
		"Asia/Bangkok" => "Asia/Bangkok",
		"Asia/Krasnoyarsk" => "Asia/Krasnoyarsk",
		"Asia/Hong_Kong" => "Asia/Hong_Kong",
		"Asia/Irkutsk" => "Asia/Irkutsk",
		"Australia/Perth" => "Australia/Perth",
		"Australia/Eucla" => "Australia/Eucla",
		"Asia/Tokyo" => "Asia/Tokyo",
		"Asia/Seoul" => "Asia/Seoul",
		"Asia/Yakutsk" => "Asia/Yakutsk",
		"Australia/Adelaide" => "Australia/Adelaide",
		"Australia/Darwin" => "Australia/Darwin",
		"Australia/Brisbane" => "Australia/Brisbane",
		"Australia/Hobart" => "Australia/Hobart",
		"Asia/Vladivostok" => "Asia/Vladivostok",
		"Australia/Lord_Howe" => "Australia/Lord_Howe",
		'Australia/Sydney' => 'Australia/Sydney', // crmv@187535
		"Etc/GMT-11" => "Etc/GMT-11",
		"Asia/Magadan" => "Asia/Magadan",
		"Pacific/Norfolk" => "Pacific/Norfolk",
		"Asia/Anadyr" => "Asia/Anadyr",
		"Pacific/Auckland" => "Pacific/Auckland",
		"Etc/GMT-12" => "Etc/GMT-12",
		"Pacific/Chatham" => "Pacific/Chatham",
		"Pacific/Tongatapu" => "Pacific/Tongatapu",
		"Pacific/Kiritimati" => "Pacific/Kiritimati",
	),
	//crmv@25610e
	// crmv@42024
	'decimal_separator_dom' => array(
		'SeparatorDot' => 'SeparatorDot',
		'SeparatorComma' => 'SeparatorComma',
	),
	'thousands_separator_dom' => array(
		'SeparatorDot' => 'SeparatorDot',
		'SeparatorComma' => 'SeparatorComma',
		'SeparatorSpace' => 'SeparatorSpace',
		'SeparatorQuote' => 'SeparatorQuote',
		'SeparatorNone' => 'SeparatorNone',
	),
	// crmv@42024e
	'contact_roles_dom' => array(
		'BusinessUser' => 'BusinessUser',
		'Manager' => 'Manager',
		'PurchaseAgent' => 'PurchaseAgent',
		'PurchaseManager' => 'PurchaseManager',
		'Examiner' => 'Examiner',
		'SponsorManager' => 'SponsorManager',
		'Consultant' => 'Consultant',
		'TechPurchaseAgent' => 'TechPurchaseAgent',
		'Other' => 'Other',
	),
	'partner_roles_dom' => array(
		'Advertiser' => 'Advertiser',
		'Agency' => 'Agency',
		'Broker' => 'Agency',
		'Consultant' => 'Consultant',
		'Dealer' => 'Dealer',
		'Developer' => 'Dealer',
		'Distributor' => 'Distributor',
		'Institution' => 'Institution',
		'Supplier' => 'Supplier',
		'SystemIntegrator' => 'Supplier',
		'Reseller' => 'Supplier',
		'Other' => 'Supplier',
	),
);
