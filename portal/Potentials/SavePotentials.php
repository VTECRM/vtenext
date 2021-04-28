<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $client;
global $result;

$ticket = Array(
		'potentialname'=>'potentialname',
		'sales_stage'=>'sales_stage',
		'owner'=>'owner',
		'description'=>'description'
		);
// 		'priority'=>'priority',
// 		'category'=>'category',
// 		'owner'=>'owner',
// 		'module'=>'module'
// 	       );

foreach($ticket as $key => $val)
	$ticket[$key] = $_REQUEST[$key];

$ticket['owner'] = $username;
// $ticket['productid'] = $_SESSION['combolist'][0]['productid'][$ticket['productid']];


$potentialname = str_replace('&nbsp;',' ',$_SESSION['name_potentials']); //$_REQUEST['potentialname']; // crmv@5946
$description = $_REQUEST['description'];
$sales_stage = $_REQUEST['sales_stage'];

// $priority = $_REQUEST['priority'];
// $severity = $_REQUEST['severity'];
// $category = $_REQUEST['category'];
$parent_id = $_SESSION['customer_id'];
// $productid = $_SESSION['combolist'][0]['productid'][$_REQUEST['productid']];

$module = $_REQUEST['module'];

$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];
$serviceid = $_REQUEST['servicename'];

//$projectid = $_REQUEST['projectid'];


$params = Array(Array(
		'id'=>"$customerid",
		'sessionid'=>"$sessionid",
		'potentialname'=>"$potentialname",
		'sales_stage'=>'PotentialOpen',
		'description'=>"$description",
	//	'priority'=>"$priority",
	//	'severity'=>"$severity",
	//	'category'=>"$category",
		'user_name' => "$username",
		'parent_id'=>"$parent_id",
	//	'product_id'=>"$productid",
		'module'=>"$module",
		'assigned_to'=>"$parent_id",
//		'serviceid'=>"$serviceid",
	//	'projectid'=>"$projectid"
	));
$record_result = $client->call('create_potentials', $params);

/*crmv@57342*/
if(isset($record_result[0]['new_potential']) && $record_result[0]['new_potential']['potentialid'] != '')
{
	$new_record = 1;
	$potentialid = $record_result[0]['new_potential']['potentialid'];
	$_REQUEST['potentialid'] = $potentialid;
	//$upload_status = AddAttachment2('potentialid',$potentialid);
	$upload_status = AddAttachmentStandard();
}
/*crmv@57342e*/
if($new_record == 1)
{
	?>
	<script>
		var potentialid = <?php echo $potentialid; ?>;
		window.location.href = "index.php?module=Potentials&action=index&fun=detail&id="+potentialid
	</script>
	<?php
}
else
{
	//getTranslatedString('LBL_PROBLEM_IN_TICKET_SAVING');
	include("NewPotentials.php");
}
?>