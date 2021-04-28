<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$block = 'HelpDesk';

require_once("include/Zend/Json.php");
@include("../PortalConfig.php");
if(!isset($_SESSION['customer_id']) || $_SESSION['customer_id'] == '')
{
	@header("Location: $Authenticate_Path/login.php");
	exit;
}

global $result;
$username = $_SESSION['customer_name'];
$customerid = $_SESSION['customer_id'];
$sessionid = $_SESSION['customer_sessionid'];

$onlymine=$_REQUEST['onlymine'];
if($onlymine == 'true') {
    $mine_selected = 'selected';
    $all_selected = '';
} else {
    $mine_selected = '';
    $all_selected = 'selected';
}

if($_REQUEST['fun'] == '' || $_REQUEST['fun'] == 'home' || $_REQUEST['fun'] == 'search')
{
	include("VteCore/List.php"); // crmv@173271
}
elseif($_REQUEST['fun'] == 'newticket')
{
	include("VteCore/Create.php"); // crmv@173271
}
elseif($_REQUEST['fun'] == 'updatecomment' || $_REQUEST['fun'] == 'close_ticket' || $_REQUEST['fun'] == 'uploadfile' || $_REQUEST['fun'] == 'provideconfinfo') // crmv@160733
{
	if($_REQUEST['fun'] == 'updatecomment')	{
		UpdateComment();
	// crmv@160733
	} elseif($_REQUEST['fun'] == 'provideconfinfo') {
		provideConfidentialInfo();
	}
	// crmv@160733e
	
	if($_REQUEST['fun'] == 'close_ticket')
	{
		$ticketid = $_REQUEST['ticketid'];
		$res = Close_Ticket($ticketid);
	}
	if($_REQUEST['fun'] == 'uploadfile')
	{
		$ticketid = $_REQUEST['ticketid'];
		$upload_status = AddAttachment($ticketid);
		// crmv@173153
		if (isset($_REQUEST['output_format']) && !empty($_REQUEST['output_format'])) {
			if ($_REQUEST['output_format'] === 'json') {
				header('Content-type: application/json');
				$success = !empty($upload_status) ? false : true;
				$error = !empty($upload_status) ? $upload_status : null;
				
				$data = array('success' => $success, 'error' => $error);
				echo Zend_Json::encode($data);
				exit();
			}
		}
		// crmv@173153e
		if($upload_status != ''){
			echo $upload_status;
			exit(0);
		} 
	}

	?>
	<script>
		var ticketid = <?php echo Zend_Json::encode($_REQUEST['ticketid']); ?>;
		window.location.href = "index.php?module=HelpDesk&action=index&fun=detail&ticketid="+ticketid
	</script>
	<?php
	
}
elseif($_REQUEST['fun'] == 'detail')
{	
	// crmv@173271
	$id = $ticketid = Zend_Json::decode($_REQUEST['ticketid']);
	include("VteCore/Detail.php");
	// crmv@173271e
}
elseif($_REQUEST['fun'] == 'saveticket')
{
	include("SaveTicket.php");
}

(file_exists("$block/footer.html")) ? $footer = "$block/footer.html" : $footer = 'VteCore/footer.html';
include($footer);
?>