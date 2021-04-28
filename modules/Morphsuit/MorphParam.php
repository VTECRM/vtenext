<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include_once('../../config.inc.php');
chdir($root_directory);
require_once('include/utils/utils.php');
global $adb, $table_prefix;
$focus = CRMEntity::getInstance("Morphsuit");
$value = vtlib_purify($_POST['value']);
echo $focus->morph_par($value);
exit;
?>