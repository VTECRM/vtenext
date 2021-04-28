<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@168297 */
/* Example of Portal SDK configuration */

// all the paths are relative to SDK/src/ folder, with the exception of 
// smarty templates, which are relative to Smarty/templates/SDK/
$sdk_config = array(

	'languages' => array(
		// add new labels or change existing ones
		'it_it' => array(
			'languages/customer-name.it_it.php',
			// other files...
		),
		'en_us' => array(
			'languages/customer-name.en_us.php',
			// other files...
		),
		// other languages...
	),
	
	'global_php' => array(
		// php files to be loaded in every page (should contain classes/functions)
		'customer-name.php',
		'MyUtilsFile.php',
	),
	
	'templates' => array(
		'login.tpl' => 'customer-login.tpl',
	),
	
	'global_js' => array(
		// js to be loaded globally
		'js/my-library.js',
		'js/customer-name.js',
	),
	
	'module_js' => array(
		// js to be loaded for specific module
		'HelpDesk' => array(
			'js/tickets.js',
		),
		// other modules...
	),
	
	'global_css' => array(
		// css to be loaded globally
		'css/customer-name.css',
	),	
	
);