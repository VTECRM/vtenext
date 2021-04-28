<?php 

// crmv@173271

if (isModuleInstalled('CustomerPortal')) {
	$_SESSION['modules_to_update']['CustomerPortal'] = 'packages/vte/optional/CustomerPortal.zip';
}

// add soap webservices

$SWSMan = SOAPWSManager::getInstance();

$SWSMan->addWebservice(
	'get_conditionals',
	'soap/SOAPWebservices.php',
	'SOAPWebservices', 
	'tns:common_array',
	array(
		array('name' => 'customerid', 'type' => 'xsd:string'),
		array('name' => 'module', 'type' => 'xsd:string'),
	)
);

$SWSMan->addWebservice(
	'get_modules_permissions',
	'soap/SOAPWebservices.php',
	'SOAPWebservices', 
	'tns:common_array',
	array(
		array('name' => 'customerid', 'type' => 'xsd:string'),
	)
);

$SWSMan->addWebservice(
	'is_edit_permitted',
	'soap/SOAPWebservices.php',
	'SOAPWebservices', 
	'xsd:string',
	array(
		array('name' => 'customerid', 'type' => 'xsd:string'),
		array('name' => 'module', 'type' => 'xsd:string'),
		array('name' => 'id', 'type' => 'xsd:string'),
	)
);

$SWSMan->addWebservice(
	'is_delete_permitted',
	'soap/SOAPWebservices.php',
	'SOAPWebservices', 
	'xsd:string',
	array(
		array('name' => 'customerid', 'type' => 'xsd:string'),
		array('name' => 'module', 'type' => 'xsd:string'),
		array('name' => 'id', 'type' => 'xsd:string'),
	)
);

$SWSMan->addWebservice(
	'update_record',
	'soap/SOAPWebservices.php',
	'SOAPWebservices', 
	'tns:common_array',
	array(
		array('name' => 'customerid', 'type' => 'xsd:string'),
		array('name' => 'module', 'type' => 'xsd:string'),
		array('name' => 'id', 'type' => 'xsd:string'),
		array('name' => 'fields', 'type' => 'tns:common_array'),
		array('name' => 'files', 'type' => 'tns:common_array'),
	)
);

$SWSMan->addWebservice(
	'delete_record',
	'soap/SOAPWebservices.php',
	'SOAPWebservices', 
	'tns:common_array',
	array(
		array('name' => 'customerid', 'type' => 'xsd:string'),
		array('name' => 'module', 'type' => 'xsd:string'),
		array('name' => 'id', 'type' => 'xsd:string'),
	)
);

$SWSMan->addWebservice(
	'get_fields_structure',
	'soap/SOAPWebservices.php',
	'SOAPWebservices', 
	'tns:common_array',
	array(
		array('name' => 'module', 'type' => 'xsd:string'),
		array('name' => 'id', 'type' => 'xsd:string'),
		array('name' => 'language', 'type' => 'xsd:string'),
	)
);

// get rid of obsolete files
$toRemove = array(
	'Accounts/header.html',
	'Accounts/index.html',
	'Accounts/AccountDetail.php',
	'Assets/index.html',
	'Assets/AssetsList.php',
	'Assets/AssetDetail.php',
	'Assets/index.php',
	'Assets',
	'Contacts/index.html',
	'Contacts/ContactsList.php',
	'Contacts/ContactsDetail.php',
	'Contacts/Detail.php',
	'Documents/index.html',
	'Documents/DocumentList.php',
	'Documents/DocumentDetail.php',
	'HelpDesk/TicketsList.php',
	'HelpDesk/TicketDetail.php',
	'HelpDesk/NewTicket.php',
	'HelpDesk/SearchForm.php',
	'HelpDesk/TicketSearch.php',
	'Invoice/index.html',
	'Invoice/index.php',
	'Invoice/InvoiceList.php',
	'Invoice/InvoiceDetail.php',
	'Project/index.html',
	'Project/index.php',
	'Project/ProjectDetail.php',
	'Project/ProjectsList.php',
	'Project',
	'ProjectPlan/index.html',
	'ProjectPlan/index.php',
	'ProjectPlan/ProjectsList.php',
	'ProjectPlan/ProjectDetail.php',
	'ProjectPlan/Detail.php',
	'Quotes/index.html',
	'Quotes/index.php',
	'Quotes/QuoteDetail.php',
	'Quotes/QuotesList.php',
	'Quotes',
	'Services/index.html',
	'Services/index.php',
	'Services/ServiceList.php',
	'Services/ServiceDetail.php',
	'Products/index.html',
	'Products/index.php',
	'Products/ProductList.php',
	'Products/ProductDetail.php',
);

foreach ($toRemove as $file) {
	$path = 'portal/'.$file;
	if (is_file($path)) {
		@unlink($path);
	} elseif (is_dir($path)) {
		@rmdir($path);
	}
}
