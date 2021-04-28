<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("modules/Faq/Faq.php");
global $adb, $app_strings, $table_prefix;
$focus = CRMEntity::getInstance('Faq');
//Map the vte_fields like ticket column => vte_faq column where ticket column is the troubletikcets vte_field name & vte_faq - column_fields
$ticket_faq_mapping_fields = Array(
	'title' => 'question',
	'product_id' => 'product_id',
	'description' => 'faq_answer',
	//'ticketstatus'=>'faqstatus',
	//'ticketcategories'=>'faqcategories'
);
// crmv@150773
$sql = " select ticketid, title, product_id,{$table_prefix}_troubletickets.description, solution," . $table_prefix . "_troubletickets.status, category 
from " . $table_prefix . "_troubletickets 
inner join " . $table_prefix . "_crmentity on " . $table_prefix . "_crmentity.crmid=" . $table_prefix . "_troubletickets.ticketid 
where ticketid=?";
// crmv@150773e
$res = $adb->pquery($sql, array($_REQUEST['record']));

//set all the ticket values to FAQ
foreach($ticket_faq_mapping_fields as $ticket_column => $faq_column)
{
	$focus->column_fields[$faq_column] = $adb->query_result($res,0,$ticket_column);
}

$focus->save("Faq");

if ($focus->id != '') {
	$description = $adb->query_result($res, 0, 'description');
	$solution = $adb->query_result($res, 0, 'solution');

	//Add the solution of the ticket with the FAQ answer
	$answer = $description;
	if ($solution != '') {
		$answer .= "\r\n\r\n" . $app_strings['LBL_SOLUTION'] . ":\r\n" . $solution;
	}

	//Retrive the ticket comments from the vte_ticketcomments vte_table and added into the vte_faq answer
	$sql = "select ticketid, comments, createdtime from " . $table_prefix . "_ticketcomments where ticketid=?";
	$res = $adb->pquery($sql, array($_REQUEST['record']));
	$numberOfRows = $adb->num_rows($res);

	if ($numberOfRows > 0)
		$answer .= "\r\n\r\n" . $app_strings['LBL_COMMENTS'] . ":";
	for ($i = 0; $i < $numberOfRows; $i++) {
		$comments = $adb->query_result($res, $i, 'comments');
		if ($comments != '') {
			$answer .= "\r\n" . $comments;
		}
	}

	$sql1 = "update " . $table_prefix . "_faq set answer=? where id=?";
	$adb->pquery($sql1, array($answer, $focus->id));
}

header("Location:index.php?module=Faq&action=DetailView&record=$focus->id&return_module=Faq&return_action=DetailView&return_id=$focus->id");

?>
