<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887

global $current_user;

require_once "data/Tracker.php";

$tracker = new Tracker();
$history = $tracker->get_recently_viewed($current_user->id);

$smarty = new VteSmarty();

$smarty->assign("HISTORY", $history);

$smarty->display("FastLastViewed.tpl");