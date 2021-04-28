<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $table_prefix;
if ($_REQUEST['return_module']=='Project' && $_REQUEST['popuptype']=='detailview' 
    && $_REQUEST['form']=='EditView') {
    $where=$table_prefix."_projecttask.projectid in ('',NULL)";
}
require_once('Popup.php');
?>