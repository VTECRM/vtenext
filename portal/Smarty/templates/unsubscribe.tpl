{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<!-- Bootstrap Core CSS-->
<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery-1.11.0.js"></script>
<script src="js/bootstrap.min.js"></script>
{literal}
<script>
setTimeout(function(){location.href="index.php?logout=true"} , 3000);  
</script> 
{/literal}
<div class="row" style="margin-top: 5px; margin-bottom: 30px;">
	{if $UNSUBSCRIBE eq "ok"}
		<h1 class="page-header">{'MSG_UNSUBSCRIBE'|getTranslatedString}</h1>
	{elseif $UNSUBSCRIBE eq "id empty"}
		<h1 class="page-header">{'ER_MSG_UNSUBSCRIBE'|getTranslatedString}</h1>
	{/if}
</div>	