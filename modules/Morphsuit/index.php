<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
if ($_REQUEST['module'] == 'Morphsuit' && $_REQUEST['action'] == 'MorphsuitAjax' && !empty($_REQUEST['file'])) {
	// crmv@79022
	$file = strip_tags(str_replace(array('.', ':', '\\', '/'), '', $_REQUEST['file']).'.php');
	if (file_exists($file)) {
		include($file);
	}
	// crmv@79022e
}
exit;
?>