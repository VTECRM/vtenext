{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@199352 *}

<script type="text/javascript" src="{"modules/Update/Update.js"|resourcever}"></script>

{include file='Buttons_List1.tpl'}

<div class="container-fluid text-center">
	<br>
	<h3>{$TITLE}</h3>
	<br>
	
	<h4>{'LBL_LAST_CHECK'|getTranslatedString:"Update"}<span title="{$LAST_CHECK}">{$LAST_CHECK_TEXT}</span></h4>
	<br><br>
	
	<div style="width:300px;margin:auto">
		<button type="button" class="btn btn-info btn-block btn-raised btn-round primary" onclick="VTE.Update.forceCheck()">{$MOD.LBL_CHECK_NOW} <i class="vteicon md-sm" style="color:white">refresh</i></button><br>
	</div>
</div>