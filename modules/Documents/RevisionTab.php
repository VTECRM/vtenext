<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@43147 crmv@53745 crmv@95157 */

require_once('modules/Documents/storage/StorageBackendUtils.php');

$record = intval($_REQUEST['record']);

$SBU = StorageBackendUtils::getInstance();
$revisions = $SBU->getRevisions('Documents', $record);

$html = '<div style="overflow:auto;"><table width="100%" border="0" cellspacing="1" cellpadding="3" class="lvt small">'; // crmv@176457
if (count($revisions) > 0) {
	$html .= '
	  <tr>
	    <td></td>
	    <td>'.getTranslatedString('LBL_FILE_NAME','Documents').'</td>
	    <td align="center">'.getTranslatedString('Revisionato Da','Documents').'</td>
	    <td align="center">'.getTranslatedString('Data Revisione','Documents').'</td>
	  </tr>';
	foreach ($revisions as $row){
		$name_reduced = $row['name'];
		$maxlen = 20;
		if (strlen($name_reduced) > $maxlen) {
			$name_reduced = substr($name_reduced,0,$maxlen/2-2).'...'.substr($name_reduced,-($maxlen/2-2));
		}
		$url = '<a href="index.php?module=uploads&action=downloadfile&return_module=Documents&fileid='.$row['attachmentid'].'&entityid='.$record.'" title="'.$row['name'].'" >'.$name_reduced.'</a>';
		$html .= '<tr bgcolor="white">';
		$html .= '<td>'.$row['revision'].'</td>';
		$html .= '<td>'.$url.'</td>';
		$html .= '<td align="center">'.(empty($row['user_email']) ? getUserName($row['userid']) : $row['user_email']).'</td>';
		$html .= '<td align="center">'.getDisplayDate(substr($row['revisiondate'], 0, 10)).'</td>';
		$html .= '</tr>';
	}
}else{
	$html = '<tr><td>'.getTranslatedString('NO_REVS','Documents').'</td></tr>';
}
$html .= '</table></div>'; // crmv@176457

echo $html;