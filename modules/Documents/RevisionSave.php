<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@43147 crmv@95157 */

global $currentModule;

$record = intval($_REQUEST['record']);
$userEmail = $_REQUEST['user_email'];

if ($_FILES['filename']['name'] != '') {
	
	// crmv@109570
	$focus = CRMEntity::getInstance('Documents'); 
	$focus->retrieve_entity_info($record, 'Documents');
	$focus->id = $record;
	// crmv@109570e
	
	$r = $focus->uploadRevision($record, $userEmail);
	
	if ($r) {
		// crmv@167019
		$rformat = $_REQUEST['responseFormat'];
		if ($rformat === 'json') {
			$result = array('success' => true);
			header('Content-type: application/json');
			echo json_encode($result);
			exit();
		} else {
		// crmv@167019e
			echo '<script language="JavaScript" type="text/javascript" src="include/js/general.js"></script>';
			echo "<script type=\"text/javascript\">
					parent.document.location.reload();
				</script>";
		}
	}
	
} else {
	echo "<script type=\"text/javascript\">
			alert('".getTranslatedString('Nessun file selezionato','Documents')."');
			history.back();
		  </script>";
}