<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@27096
$module = vtlib_purify($_REQUEST['selected_module']);
$ids = vtlib_purify($_REQUEST['selected_ids']);
if ($module != '' && $ids != '') {
	saveListViewCheck($module,$ids);
}
exit;
//crmv@27096e
?>