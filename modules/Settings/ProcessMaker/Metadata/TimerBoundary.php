<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@97566 */

include('modules/Settings/ProcessMaker/Metadata/TimerIntermediate.php');

$smarty->assign("START_LABEL", getTranslatedString('LBL_PM_AFTER','Settings'));
$smarty->assign("END_LABEL", getTranslatedString('LBL_PM_GO_TO_NEXT_STEP','Settings'));