<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once('include/utils/utils.php');
include_once('modules/SDK/LangUtils.php');
$langinfo = vtlib_getToggleLanguageInfo();
$languages = array_keys($langinfo);
 if (empty($languages)) {
	$languages = array('en_us','it_it');
}
foreach ($languages as $language){
	@SDK::importJsLanguage($language);
}
?>