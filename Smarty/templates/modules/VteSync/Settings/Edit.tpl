{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@176547 *}

<script type="text/javascript" src="{"modules/VteSync/Settings/VteSyncConfig.js"|resourcever}"></script>
{literal}
<style>
	.selectabletable {
		width: 90%;
	}
	.selectabletable tr {
		cursor: pointer;
	}
	.selectabletable tr:hover {
		background-color: #e0e0e0;
	}
	.selectabletable tr.selected {
		background-color: #c0c0c0;
	}
	.selectabletable tr.selected:hover {
		background-color: #b0b0b0;
	}
	
</style>
{/literal}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
<td valign="top"></td>
<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">

	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'}
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'vtesync.png'|resourcever}" alt="{$MOD.LBL_SYNC_SETTINGS}" width="48" height="48" border=0 title="{$MOD.LBL_SYNC_SETTINGS}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_SYNC_SETTINGS}</b></td>
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_SYNC_SETTINGS_DESC} </td>
				</tr>
				</table>
				<br>
				
				<table width="100%">
					<tr>
						{if $MODE eq 'create'}
							<td class="big"><h4>{$MOD.LBL_VTESYNC_CREATE}</h4></td>
						{else}
							<td class="big"><h4>{$MOD.LBL_VTESYNC_EDIT}</h4></td>
						{/if}
						<td align="right">
							<button type="button" class="crmbutton small save" onclick="VteSyncConfig.save()">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
							<button type="button" class="crmbutton small cancel" onclick="window.history.back()">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
						</td>
					</tr>
				</table>
				<br>
				
				<form method="POST" action="index.php" id="VteSyncEditForm">
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="action" value="SettingsAjax"> {* avoid headers *}
				<input type="hidden" name="file" value="VteSync">
				<input type="hidden" name="mode" value="save">
				<input type="hidden" name="syncid" value="{$SYNCID}">
				
				<table class="configTable" width="90%" border="0" align="center" cellpadding="5">
				
					<tr>
						<td colspan="3" style="border-bottom:1px solid #f0f0f0"><h5>{$MOD.LBL_GENERAL_SETTINGS}</h5></td>
					</tr>
					
					<tr>
						<td align="right" width="20%"><label>{$MOD.LBL_VTESYNC_TYPE}</label></td>
						<td width="40%">
							<div class="dvtCellInfo">
								<select name="synctype" id="synctype" class="detailedViewTextBox" onchange="VteSyncConfig.onTypeChange(this)">
									<option value="">{$APP.LBL_PLEASE_SELECT}</option>
									{foreach key="STYPE" item="LABEL" from=$SYNCTYPES}
										<option value="{$STYPE}">{$LABEL}</option>
									{/foreach}
								</select>
							</div>
							
						</td>
						<td>{$MOD.LBL_VTESYNC_TYPE_DESC}</td>
					</tr>
					
					{* crmv@190016 *}
					<tr class="system_url" style="display:none">
						<td colspan="3">&nbsp;</td>
					</tr>
					
					<tr class="system_url" style="display:none">
						<td align="right" width="20%"><label>{$MOD.LBL_VTESYNC_SYSTEMURL}</label></td>
						<td>
							<div class="dvtCellInfo">
								<input type="text" class="detailedViewTextBox" name="system_url" id="system_url">
							</div>
						</td>
						<td>&nbsp;</td>
					</tr>
					{* crmv@190016e *}
					
					<tr class="sync_modules" style="display:none">
						<td align="right"><label>{$MOD.LBL_VTESYNC_MODULES}</label></td>
						<td colspan="2">
							<br>
							<table class="table" width="100%" style="min-height:150px">
								<thead>
									<tr>
										<td><b>{$MOD.LBL_VTESYNC_AVAILMODS}</b></td>
										<td></td>
										<td><b>{$MOD.LBL_VTESYNC_SELECTEDMODS}</b></td>
									</tr>
								</thead>
								<tr>
									<td style="vertical-align:top">
										<table id="availModules" class="selectabletable"></table>
									</td>
									<td style="vertical-align:middle;text-align:center">
										<a href="javascript:void(0);" onclick="VteSyncConfig.addModules()"><i class="vteicon" title="{$APP.LBL_ADD_ITEM}">arrow_forward</i></a><br><br>
										<a href="javascript:void(0);" onclick="VteSyncConfig.removeModules()"><i class="vteicon" title="{$APP.LBL_REMOVE_ITEM}">arrow_back</i></a>
									</td>
									<td style="vertical-align:top">
										<table id="selModules" class="selectabletable"></table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					
					<tr class="auth_types" style="display:none">
						<td colspan="3" style="border-bottom:1px solid #f0f0f0"><h5>{$MOD.LBL_VTESYNC_AUTH_SETTINGS}</h5></td>
					</tr>
					
					<tr class="auth_types" style="display:none">
						<td align="right" width="20%"><label>{$MOD.LBL_VTESYNC_AUTHTYPE}</label></td>
						<td>
							<div class="dvtCellInfo">
								<select name="authtype" class="detailedViewTextBox" onchange="VteSyncConfig.onAuthTypeChange(this)">
									<option value="">{$APP.LBL_PLEASE_SELECT}</option>
									{* foreach key="ATYPE" item="LABEL" from=$AUTHTYPES}
										<option value="{$ATYPE}">{$LABEL}</option>
									{/foreach *}
								</select>
							</div>
							
						</td>
						<td>{$MOD.LBL_VTESYNC_AUTHTYPE_DESC}</td>
					</tr>
					
					{* crmv@196666 *}
					<tr class="auth_oauth2flow" style="display:none">
						<td align="right" width="20%"><label>{'LBL_OAUTH2_FLOW'|getTranslatedString}</label></td>
						<td>
							<div class="dvtCellInfo">
								<select name="oauthtypeflow" id="oauthtypeflow" class="detailedViewTextBox" onchange="VteSyncConfig.onOauthFlowChange(this)">
									<option value="">{$APP.LBL_PLEASE_SELECT}</option>
									{foreach key="IDTYPE" item="TYPEFLOW" from=$SYNCTYPESFLOW}
										<option id="{$IDTYPE}" value="{$TYPEFLOW}">{$TYPEFLOW|getTranslatedString}</option> {* crmv@197996 *}
									{/foreach }
								</select>
							</div>
							
						</td>
						<td></td>
					</tr>
					{* crmv@196666e *}
					
					{* crmv@190016 *}
					<!-- auth http -->
					
					<tr class="auth_type_http" style="display:none">
						<td align="right" width="20%"><label>{$MOD.LBL_VTESYNC_USERNAME}</label></td>
						<td>
							<div class="dvtCellInfo">
								<input type="text" class="detailedViewTextBox" name="http_username" id="http_username">
							</div>
						</td>
						<td>{$MOD.LBL_VTESYNC_USERNAME_DESC}</td>
					</tr>
					<tr class="auth_type_http" style="display:none">
						<td align="right" width="20%"><label>{$MOD.LBL_VTESYNC_PASSWORD}</label></td>
						<td>
							<div class="dvtCellInfo">
								<input type="password" class="detailedViewTextBox" name="http_password" id="http_password">
							</div>
						</td>
						<td>&nbsp;</td>
					</tr>
					{* crmv@190016e *}
					
					<!-- auth oauth2 -->
					
					<tr class="auth_type_oauth2" style="display:none">
						<td align="right" width="20%"><label>{$MOD.LBL_VTESYNC_CLIENTID}</label></td>
						<td>
							<div class="dvtCellInfo">
								<input type="text" class="detailedViewTextBox" name="client_id" id="client_id">
							</div>
						</td>
						<td>{$MOD.LBL_VTESYNC_CLIENTID_DESC} <i class="vteicon md-sm md-link valign-bottom" onclick="VteSyncConfig.showOAuthHelp()">help</i></td>
					</tr>
					<tr class="auth_type_oauth2" style="display:none">
						<td align="right" width="20%"><label>{$MOD.LBL_VTESYNC_CLIENTSECRET}</label></td>
						<td>
							<div class="dvtCellInfo">
								<input type="text" class="detailedViewTextBox" name="client_secret" id="client_secret">
							</div>
						</td>
						<td>{$MOD.LBL_VTESYNC_CLIENTSECRET_DESC}</td>
					</tr>
					
					<tr class="auth_type_oauth2" style="display:none">
						<td align="right" width="20%"><label></label></td>
						<td style="padding-top:10px">
							<div id="oauth2_auth_ok" class="alert alert-success green" style="display:none;padding:6px;padding-left:16px">
								<h5><b>{$MOD.LBL_VTESYNC_AUTHORIZED}</b></h5>
							</div>
							<div id="oauth2_auth_ko" class="alert alert-danger" style="display:none;padding:6px;padding-left:16px">
								<h5><b>{$MOD.LBL_VTESYNC_NOT_AUTHORIZED}</b></h5>
							</div>
							<div class="dvtCellInfo">
								<button type="button" id="authorizeButton" class="crmbutton small edit" onclick="VteSyncConfig.oauthAuthorize()">{$MOD.LBL_VTESYNC_AUTHORIZE}</button>
								<button type="button" id="revokeButton" class="crmbutton small edit" onclick="VteSyncConfig.oauthRevoke()">{$MOD.LBL_VTESYNC_REVOKE}</button>
							</div>
							<input type="hidden" id="oauth2_saveid" name="oauth2_saveid" value="">
							<input type="hidden" name="scope" value="{$OAUTH_SCOPES|@implode:' '}">
						</td>
						<td>{$MOD.LBL_VTESYNC_AUTHORIZE_DESC}</td>
					</tr>
					
				</table>
				
				</form>
				
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		
	</div>
</td>
<td valign="top"></td>
</tr>
</tbody>
</table>

{assign var="FLOAT_TITLE" value=$MOD.LBL_VTESYNC_CONFIG_MODULE|cat:' <span name="modulename"></span>'}
{assign var="FLOAT_WIDTH" value="500px"}
{capture assign="FLOAT_CONTENT"}
	<input type="hidden" id="modconfigName" value="">
	<table border="0" celspacing="0" cellpadding="5" width="100%" align="center">
		<tr>
			<td>
				<table border="0" celspacing="0" cellpadding="5" width="100%">
					<tr>
						<td>{$MOD.LBL_VTESYNC_MODCFG_DIRECTION}</td>
						<td>
							<select class="detailedViewTextBox" name="sync_direction">
								<option value="both">{$MOD.LBL_VTESYNC_MODCFG_DIR_BOTH}</option>
								<option value="to_vte">{$MOD.LBL_VTESYNC_MODCFG_DIR_TO_VTE}</option>
								<option value="from_vte">{$MOD.LBL_VTESYNC_MODCFG_DIR_FROM_VTE}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{$MOD.LBL_VTESYNC_MODCFG_DELETIONS}</td>
						<td>
							<select class="detailedViewTextBox" name="deletions">
								<option value="none">{$MOD.LBL_VTESYNC_MODCFG_DEL_NONE}</option>
								<option value="both">{$MOD.LBL_VTESYNC_MODCFG_DIR_BOTH}</option>
								<option value="in_vte">{$MOD.LBL_VTESYNC_MODCFG_DIR_TO_VTE}</option>
								<option value="in_external">{$MOD.LBL_VTESYNC_MODCFG_DIR_FROM_VTE}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{$MOD.LBL_VTESYNC_MODCFG_SYNC_PLIST}</td>
						<td>
							<select class="detailedViewTextBox" name="sync_picklist">
								<option value="none">{$MOD.LBL_VTESYNC_MODCFG_DEL_NONE}</option>
								<option value="to_vte">{$MOD.LBL_VTESYNC_MODCFG_DIR_TO_VTE}</option>
							</select>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="right">
				<br>
				<button type="button" onclick="VteSyncConfig.hideModuleConfig(true)" class="crmbutton small cancel">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
				<button type="button" onclick="VteSyncConfig.saveModuleConfig()" class="crmbutton small save">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
			</td>
		</tr>
	</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="ConfigModuleDiv" FLOAT_BUTTONS=""}

{assign var="FLOAT_TITLE" value=$APP.LNK_HELP}
{assign var="FLOAT_WIDTH" value="500px"}
{capture assign="FLOAT_CONTENT"}	
	<p>{$MOD.LBL_VTESYNC_OAUTH_HELP_1}</p>
	
	<p><a class="oauth_help_link" id="oauth_link_1" href="https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/intro_defining_remote_access_applications.htm" target="_blank" style="display:hidden">https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/intro_defining_remote_access_applications.htm</a></p>
	<p><a class="oauth_help_link" id="oauth_link_3" href="https://developers.hubspot.com/docs/faq/how-do-i-create-an-app-in-hubspot" target="_blank" style="display:hidden">https://developers.hubspot.com/docs/faq/how-do-i-create-an-app-in-hubspot</a></p> {* crmv@195073 *}
	
	<p>{$MOD.LBL_VTESYNC_OAUTH_HELP_2}</p>
	<p><b>{$CALLBACK_URL}</b></p>
	
	<p>{$MOD.LBL_VTESYNC_OAUTH_HELP_3}</p>
	<ul class="oauth_help_scopes" id="oauth_scopes_1" style="display:none">
		<li><b>api (Access and manage your data)</b></li>
		<li><b>refresh_token, offline_access (Perform requests on your behalf at any time)</b></li>
	</ul>
	{* crmv@195073 *}
	<ul class="oauth_help_scopes" id="oauth_scopes_3" style="display:none">
		<li><b>Contacts (This includes prospects and lists)</b></li>
		<li><b>Tickets (This includes access to tickets.)</b></li>
	</ul>
	{* crmv@195073e *}
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="OAuthHelpDiv" FLOAT_BUTTONS=""}

{if $MODE eq 'edit'}
{literal}
<script type="text/javascript">
	(function() {
		var syncdata = {/literal}{$SYNCDATA|@json_encode}{literal};
		VteSyncConfig.populateData(syncdata);
	})();
	// replace handler to closebutton
	jQuery('#ConfigModuleDiv').find('.closebutton').click(function() {
		VteSyncConfig.hideModuleConfig(true);
	});
</script>
{/literal}
{/if}