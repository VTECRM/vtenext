<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@32429
global $current_user;

require_once("data/Tracker.php");
$tracker = new Tracker();
$history = $tracker->get_recently_viewed($current_user->id);

$i = 0;
foreach($history as $row)
{
	$i++;
	//crmv@36504
	$entitytype = getTranslatedString('SINGLE_'.$row['module_type'],$row['module_name']);
	if (in_array($entitytype,array('','SINGLE_'.$row['module_type']))) {
		$entitytype = getTranslatedString($row['module_type'],$row['module_name']);
	}
	//crmv@36504 e
    echo <<< EOQ
        <tr>
			<td class="trackerListBullet small" align="center" width="12">$i</td>
			<td class="trackerList small">$entitytype</a></td>
			<td class="trackerList small" width="100%"><a href="index.php?module={$row['module_name']}&action=DetailView&record={$row['crmid']}">{$row['item_summary']}</a> </td><td class="trackerList small">&nbsp;</td>
		</tr>
EOQ;
}
//crmv@32429e
?>