{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@182073 crmv@181161 *}

<script type="text/javascript" src="{"modules/Update/Update.js"|resourcever}"></script>

{include file='Buttons_List1.tpl'}

<div class="container-fluid">
	<br>
	<br><br>
	<div class="row">
		<div class="col-xs-10 col-xs-offset-1">
			<div class="alert" style="background-color:#ffbb45;color:white">
				<p><i class="vteicon md-text md-sm" style="color:white">report_problem</i> <b>{$MOD.LBL_OS_NOT_SUPPORTED_UPDATE}</b></p>
			</div>
			<br>
			<h4>{"LBL_PMH_DESCRIPTION"|getTranslatedString:"Settings"}</h4>
			<ol>
				<li>{$MOD.LBL_MANUAL_INFO_1|replace:"%s":$BACKUP_INFO_URL}</li> {* crmv@183486 *}
				<li>{$MOD.LBL_MANUAL_INFO_2|replace:"%s":$PACKAGE_URL}</li>
				<li>{$MOD.LBL_MANUAL_INFO_3|replace:"%s":$VTE_FOLDER}</li> {* crmv@183486 *}
				<li>{$MOD.LBL_MANUAL_INFO_4|replace:"%s":$UPDATE_URL}</li>
			</ol>
		</div>
	</div>
</div>