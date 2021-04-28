<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

defined('BASEPATH') OR exit('No direct script access allowed');

global $language;

$languageFile = 'languages/lang_'.$language.'.php';

if (file_exists($languageFile) && is_readable($languageFile)) {
	$translations = require($languageFile);
}

function _T($label, $args = array()) {
	global $translations;
	return isset($translations[$label]) ? vsprintf($translations[$label], $args) : $label;
}