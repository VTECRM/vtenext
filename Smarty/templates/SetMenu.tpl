{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 ********************************************************************************/
*}

{* crmv@181170 *}
{assign var=BLOCKS value=SettingsUtils::getBlocks()}
{assign var=FIELDS value=SettingsUtils::getFields()}
{assign var=THEME value=CRMVUtils::getApplicationTheme()}

{SettingsUtils::resetMenuState()}
{* crmv@181170e *}

<table border=0 cellspacing=0 cellpadding=5 width="100%" class="settingsUI">
	<tr>
		<td valign=top>
			<table border=0 cellspacing=0 cellpadding=0 width=100%>
				<tr>
					<td valign=top id="settingsSideMenu" width="10%" >
						<!--Left Side Navigation Table-->
						<table border=0 cellspacing=0 cellpadding=0 width="100%">
{assign var=test value=''}
{foreach key=BLOCKID item=BLOCK from=$BLOCKS} {* crmv@140887 *}
	{assign var=BLOCKLABEL value=$BLOCK.label} {* crmv@140887 *}
	{if $BLOCKLABEL neq 'LBL_MODULE_MANAGER'}
	{assign var=blocklabel value=$BLOCKLABEL|@getTranslatedString:'Settings'}
										<tr>
								<td class="settingsTabHeader" nowrap>
									{$blocklabel}
								</td>
							</tr>
							
		{foreach item=data from=$FIELDS.$BLOCKID}
			{if $data.link neq ''}
				{assign var=label_original value=$data.name} {* crmv@30683 *}
				{assign var=label value=$data.name|@getTranslatedString:'Settings'}
				{* crmv@22660 *}
				{assign var='settingsTabClass' value='settingsTabList'}
				{if $smarty.request.module_settings eq 'true' && $smarty.request.formodule eq $data.formodule
					&& $smarty.request.action eq $data.action && $smarty.request.module eq $data.module}
					{assign var='settingsTabClass' value='settingsTabSelected'}
					{VteSession::set('settings_last_menu', $label_original)} {* crmv@30683 *} {* crmv@181170 *}
				{elseif $smarty.request.module_settings eq '' && $data.formodule eq ''
					&& $smarty.request.action eq $data.action && $smarty.request.module eq $data.module}
					{assign var='settingsTabClass' value='settingsTabSelected'}
					{VteSession::set('settings_last_menu', $label_original)} {* crmv@30683 *} {* crmv@181170 *}
				{* crmv@30683 *}	
				{elseif $smarty.session.settings_last_menu eq $data.name}
					{assign var='settingsTabClass' value='settingsTabSelected'}
				{* crmv@30683e  *}
				{/if}
				<tr>
					<td class="{$settingsTabClass}" nowrap>
						{*//crmv@31817*}
						<a href="{$data.link}&reset_session_menu=true">
							{if $data.icon|strpos:".png" !== false}
								{assign var=icon value=$data.icon|@replace:'.png':'_small.png'}
								<img border="0" src="{$icon|@vtecrm_imageurl:$THEME}" align="top">
							{elseif $data.icon|strpos:".gif" !== false}	
								{assign var=icon value=$data.icon|@replace:'.gif':'_small.png'}
								<img border="0" src="{$icon|@vtecrm_imageurl:$THEME}" align="top">
							{else}
								<i class="vteicon">{$data.icon}</i>
							{/if}
						</a>			
						<a href="{$data.link}&reset_session_menu=true">
						<!-- crmv@30683  -->
							{$label} 
						<!-- crmv@30683e  -->
						</a>
						{*//crmv@31817e*}
					</td>
				</tr>
				{* crmv@22660e *}
			{/if}
		{/foreach}
	{/if}
{/foreach}
						</table>
						<!-- Left side navigation table ends -->
		
					</td>
					<td width="8px" valign="top"> 
						<i class="vteicon" title="Hide Menu" id="hideImage" style="display:inline;cursor:pointer;" onclick="toggleShowHide_panel('showImage','settingsSideMenu'); toggleShowHide_panel('showImage','hideImage');">arrow_back</i>
						<i class="vteicon" title="Show Menu" id="showImage" style="display:none;cursor:pointer;" onclick="toggleShowHide_panel('settingsSideMenu','showImage'); toggleShowHide_panel('hideImage','showImage');">arrow_forward</i>
					</td>
					<td class="small settingsSelectedUI" valign=top align=left>
						<script type="text/javascript">
{literal}
							function toggleShowHide_panel(showid, hideid){
								var show_ele = document.getElementById(showid);
								var hide_ele = document.getElementById(hideid);
								if(show_ele != null){ 
									show_ele.style.display = "";
									}
								if(hide_ele != null) 
									hide_ele.style.display = "none";
							}
{/literal}
						</script>