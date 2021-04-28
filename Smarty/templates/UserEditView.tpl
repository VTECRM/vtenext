{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@192033 *}

<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>

<script language="JavaScript" type="text/javascript">
{literal}

function open_Popup() {
	openPopup("index.php?module=Users&action=UsersAjax&file=RolePopup&parenttab=Settings","roles_popup_window","height=425,width=640,toolbar=no,menubar=no,dependent=yes,resizable=no",'',640,425);
}	

function check_duplicate() {

	var user_name = window.document.EditView.user_name.value;
	var status = CharValidation(user_name,'name');
	VteJS_DialogBox.block();
	if(status) {
		jQuery.ajax({
			url: 'index.php',
			method: 'POST',
			data: 'module=Users&action=UsersAjax&file=Save&ajax=true&dup_check=true&userName='+user_name,
			success: function(result) {
				if (result.indexOf("SUCCESS") > -1) {
					document.EditView.submit();
				} else {
					VteJS_DialogBox.unblock();
					alert(response.responseText);
				}
			}
		});
	} else {
		VteJS_DialogBox.unblock();
		alert(alert_arr.NO_SPECIAL+alert_arr.IN_USERNAME)
	}
}

<!-- crmv@9010 -->

// sCommand = "LdapSearchUser" --> search a user which meets the name entered by the admin --> fill Drop Down box
// sCommand = "LdapSelectUser" --> retrieve the details of the user --> Fill all fields
function QueryLdap(sCommand)
{
	sUser = document.getElementById(sCommand).value;
	
	if (sCommand == "LdapSearchUser") // hide Drop-Down box
		document.getElementById("LdapSelectUser").style.visibility="hidden";
	
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Users&action=UsersAjax&file=QueryLdap&command='+sCommand+'&user='+sUser,
		success: function(result) {
			if (result.indexOf("Warn=") == 0)
			{
				sError = result.substring(6);
				alert (sError);
			}
			else if (result.indexOf("Error=") == 0)
			{
				sError = result.substring(6);
				alert (sError);
			}
			else if (result.indexOf("Options=") == 0)
			{
				sOptions = result.substring(8).split("\n");
				var oSelBox = document.getElementById("LdapSelectUser");
				oSelBox.innerHTML = "";
				for (o=0; o<sOptions.length; o++)
				{
					sParts = sOptions[o].split("\t");
					// Using DOM here because assigning innerHTML does not work on MSIE 6.0
					var oOption = document.createElement("OPTION");
					oOption.value = sParts[0];
					oOption.text  = sParts[1];
					if (sParts[0].length) oOption.text += " (" + sParts[0] + ")";
					try
					{
						oSelBox.add(oOption, null); // Standard compliant
					}
					catch (ex)
					{
						oSelBox.add(oOption); // Internet Explorer
					}
				}
				oSelBox.style.visibility="visible";
			}
			else if (result.indexOf("Values=") == 0)
			{
				sValues = result.substring(7).split("\n");
				for (v=0; v<sValues.length; v++)
				{
					sParts = sValues[v].split("\t");
					try { document.EditView[sParts[0]].value = sParts[1]; }
					catch (ex) {}
				}
				{/literal}
				document.EditView['role_name'].value="{$secondvalue}";
				document.EditView['user_role'].value="{$roleid}";
			    document.EditView['use_ldap'].checked = true;
			    {literal}
			}
		}
	});
}	
{/literal} 
<!-- crmv@9010e -->

</script>
{include file='Buttons_List1.tpl'}	{* crmv@20054 *} 
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg"  valign="top" width="100%" style="padding:0px"> <!-- crmv@30683 -->
        {include file='Buttons_List_Edit.tpl'}	{* crmv@20054 *}

	<div align=center>
	{if $PARENTTAB eq 'Settings'}
		{include file='SetMenu.tpl'}
	{/if}

		<form name="EditView" method="POST" action="index.php" ENCTYPE="multipart/form-data" onsubmit="VteJS_DialogBox.block();">
		<input type="hidden" name="module" value="Users">
		<input type="hidden" name="record" value="{$ID}">
		<input type="hidden" name="mode" value="{$MODE}">
		<input type='hidden' name='parenttab' value='{$PARENTTAB}'>
		<input type="hidden" name="activity_mode" value="{$ACTIVITYMODE}">
		<input type="hidden" name="action">
		<input type="hidden" name="return_module" value="{$RETURN_MODULE}">
		<input type="hidden" name="return_id" value="{$RETURN_ID}">
		<input type="hidden" name="return_action" value="{$RETURN_ACTION}">			
		<input type="hidden" name="tz" value="Europe/Berlin">			
		<input type="hidden" name="holidays" value="de,en_uk,fr,it,us,">			
		<input type="hidden" name="workdays" value="0,1,2,3,4,5,6,">			
		<input type="hidden" name="namedays" value="">			
		<input type="hidden" name="weekstart" value="1">
		<input type="hidden" name="hour_format" value="{$HOUR_FORMAT}">

	<table width="100%"  border="0" cellspacing="0" cellpadding="0" class="settingsSelUITopLine">
	<tr><td align="left">
		<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr>
			<td rowspan="2" width="50"><i class="vteicon md-text md-xlg">person</i></td>	{* crmv@20054 *}
			<td>	
				<span class="lvtHeaderText">
				{if $PARENTTAB neq ''}	
				<b>{$MOD.LBL_SETTINGS} &gt; <a href="index.php?module=Administration&action=index&parenttab=Settings">{$MOD.LBL_USERS}</a> &gt; <!-- crmv@30683 -->
					{if $MODE eq 'edit'}
						{$UMOD.LBL_EDITING} "{$USERNAME}" 
					{else}
						{if $DUPLICATE neq 'true'}
						{$UMOD.LBL_CREATE_NEW_USER}
						{else}
						{$APP.LBL_DUPLICATING} "{$USERNAME}"
						{/if}
					{/if}
					</b></span>
				{else}
                                <span class="lvtHeaderText">
                                <b>{$APP.LBL_MY_PREFERENCES}</b>
                                </span>
                                {/if}
			</td>
			<td rowspan="2" nowrap>&nbsp;
			</td>
	 	</tr>
		<tr>
			{if $MODE eq 'edit'}
				<td><b class="small">{$UMOD.LBL_EDIT_VIEW} "{$USERNAME}"</b>
			{else}
				{if $DUPLICATE neq 'true'}
				<td><b class="small">{$UMOD.LBL_CREATE_NEW_USER}</b>
				{/if}
			{/if}
			</td>
                </tr>
		</table>
	</td>
	</tr>
	<tr><td class="padTab" align="left">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">

		<tr><td colspan="2">
			<table align="center" border="0" cellpadding="0" cellspacing="0" width="99%">
			<tr>
			    <td align="left" valign="top">
			    
			    <!-- crmv@9010 -->
			    {if $LDAP_BUTTON neq ''}
			        <br>
			        <table class="rightMailMerge" border="0" cellpadding="5" cellspacing="0" width="100%">
			        <tr valign="top">
				        <td width="50%" nowrap>
				        	<div class="dvtCellLabel">{$UMOD.LBL_FORE_LASTNAME}</div>
				        	<table border="0" cellpadding="0" cellspacing="0" width="100%">
				        		<tr>
				        			<td width="100%">
				        				<div class="dvtCellInfo">
							        		<input type="text" id="LdapSearchUser" class="detailedViewTextBox">
							        	</div>
				        			</td>
				        			<td nowrap>
				        				<input type="button" class="crmbutton small create" value="{$UMOD.LBL_QUERY} {$LDAP_BUTTON}" onClick="QueryLdap('LdapSearchUser');">
				        			</td>
				        		</tr>
				        	</table>
				        </td>
				        <td width="50%">
				        	<select id="LdapSelectUser" class="small" style="width:250px; visibility:hidden;" onChange="QueryLdap('LdapSelectUser');"></select>
				        </td>
			        </tr>
			        </table>
			        {/if}
			        <!-- crmv@9010e -->
			        
					<table border="0" cellpadding="0" cellspacing="0" width="100%">
					<tr><td align="left">
						{* crmv@104568 *}
						{foreach name=blockforeach item=data from=$BLOCKS}
						{assign var=header value=$data.label}
						<br>
							<table id="{$header|replace:' ':'_'}" class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%"> {* crmv@20209 *}
                                <tr>
                                    {strip}
                                     <td class="big">
                                        <strong>{$smarty.foreach.blockforeach.iteration}. {$header}</strong>
                                     </td>
                                     <td class="small" align="right">&nbsp;</td>
                                  {/strip}
                              	</tr>
							</table>
							<table border="0" cellspacing="0" cellpadding="{if $OLD_STYLE eq true}2{else}5{/if}" width=100% class="small">	{* crmv@57221 *}
								<!-- Handle the ui types display -->
								{include file="DisplayFields.tpl" data=$data.fields}
							</table>
							{* crmv@20209 *}
							{if $header eq "LBL_CALENDAR_CONFIGURATION"|getTranslatedString:'Users'}
								{$CALENDAR_SHARE_CONTENT} {* crmv@181170 *}
							{/if}
							{* crmv@20209e *}
					   	{/foreach}
					   	{* crmv@104568e *}
				<br>
				<table id="home_page_components" class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%"> {* crmv@20054 *}
					<tr>
						<td class="big"><strong>7. {$UMOD.LBL_HOME_PAGE_COMP}</strong>		{* crmv@164190 *}
						</td>
						<td class="small" align="right">&nbsp;</td>
					</tr>
				</table>
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
					{foreach item=homeitems key=values from=$HOMEORDER name="homeitems"}
						{assign var=homeidx value=$smarty.foreach.homeitems.iteration}
						<td class="dvtCellLabel" align="right" width="25%" height="30">{if $UMOD.$values eq ''}{$values|@getTranslatedString:'Home'}{else}{$UMOD.$values|@getTranslatedString:'Home'}{/if}</td> {* crmv@3079m *}
					    {if $homeitems neq ''}
					    	{assign var="homeitems_true_check" value="checked"}
							{assign var="homeitems_false_check" value=""}
					    {else}
					    	{assign var="homeitems_true_check" value=""}
							{assign var="homeitems_false_check" value="checked"}
					    {/if}
				    	<td align="center" width="15%">
							<div class="togglebutton">
								<label>
									<input id="{$values}_homeitems" name="{$values}" value="{$values}" type="checkbox" {$homeitems_true_check}>
								</label>
							</div>
						</td>
						{if $homeidx % 2 == 0}
						</tr><tr>
						{/if}
					{/foreach}
					</tr>
		    	</table>
				
				{* crmv@29617 *}
				<table id="home_page_components" class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
						<td class="big"><strong>9. {'LBL_NOTIFICATION_MODULE_SETTINGS'|getTranslatedString:'ModNotifications'}</strong></td>	{* crmv@164190 *}
						<td class="small" align="right">&nbsp;</td>
						<input type="hidden" name="notification_module_settings" value="yes">
					</tr>
				</table>
				<table border="0" cellpadding="5" cellspacing="0" width="100%">
				{assign var="NOTIFICATION_MODULE_SETTINGS" value=$ID|getNotificationsModuleSettings}
				{foreach item=FLAGS key=MODULE_NAME from=$NOTIFICATION_MODULE_SETTINGS}
					<tr><td class="dvtCellLabel" align="right" width="30%">{$MODULE_NAME|@getTranslatedString:$MODULE_NAME}</td>
					    {if $FLAGS.create eq 1}
					    	{assign var="create_flag" value="checked"}
					    {else}
					    	{assign var="create_flag" value=""}
					    {/if}
					    {if $FLAGS.edit eq 1}
					    	{assign var="edit_flag" value="checked"}
					    {else}
					    	{assign var="edit_flag" value=""}
					    {/if}
				    	<td align="center" width="5%">
							<div class="checkbox"><label>
								<input type="checkbox" id="{$MODULE_NAME}_notify_create" name="{$MODULE_NAME}_notify_create" {$create_flag}>
							</label></div>
						</td>
						<td align="left" width="20%" nowrap>
							<label for="{$MODULE_NAME}_notify_create">{'LBL_CREATE_NOTIFICATION'|getTranslatedString:'ModNotifications'}</label>
						</td>
				    	<td align="center" width="5%">
							<div class="checkbox"><label>
								<input type="checkbox" id="{$MODULE_NAME}_notify_edit" name="{$MODULE_NAME}_notify_edit" {$edit_flag}>
							</label></div>
						</td>
						<td align="left" nowrap>
							<label for="{$MODULE_NAME}_notify_edit">{'LBL_EDIT_NOTIFICATION'|getTranslatedString:'ModNotifications'}</label>
						</td>
					</tr>
				{/foreach}
				</table>
			    {* crmv@29617e *}
				
					    </table>
					 </td></tr>
					</table>
			  	   </td></tr>
				   </table>
				 <br>
				  </td></tr>
				</table>
				{include file='Settings/ScrollTop.tpl'}
			</td>
			</tr>
			</table>
			</form>	
</td>
</tr>
</table>
</td></tr></table>
<br>
{$JAVASCRIPT}
{* crmv@20054 *}
{if $smarty.request.scroll neq ''}
	{assign var=scroll value=$smarty.request.scroll}
	<script type="text/javascript">
	jQuery(document).ready(function() {ldelim}
		pos = findPosY(getObj('{$scroll}'))-jQuery('#vte_menu_white').height()-jQuery('#{$scroll}').height();
		window.scrollBy(0,pos);
	{rdelim});
	</script>
{/if}
{* crmv@20054e *}
<!-- vtlib customization: Help information assocaited with the fields -->
{if $FIELDHELPINFO}
<script type='text/javascript'>
{literal}var fieldhelpinfo = {}; {/literal}
{foreach item=FIELDHELPVAL key=FIELDHELPKEY from=$FIELDHELPINFO}
	fieldhelpinfo["{$FIELDHELPKEY}"] = "{$FIELDHELPVAL}";
{/foreach}
</script>
{/if}
<!-- END -->