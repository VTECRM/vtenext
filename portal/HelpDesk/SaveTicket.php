<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@173271 */

$ticketid = $moduleObj->createRecord($_REQUEST);
 
if ($ticketid > 0) {
	?>
	<script>
		window.location.href = "index.php?module=HelpDesk&action=index&fun=detail&ticketid=<?php echo $ticketid; ?>";
	</script>
	<?php

} else {
	echo getTranslatedString('LBL_PROBLEM_IN_TICKET_SAVING');
	include("VteCore/Create.php");
}