{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@191067 *}

<script type="text/javascript" src="modules/Transitions/Transitions.js"></script>

<div id="createcf" style="display:block;position:absolute;width:500px;"></div>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">
				   <form action="index.php" method="post" name="EditView" id="form">
					<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
					<input type='hidden' name='module' value='Transitions'>
					<input type='hidden' name='action' value='Transitions/EditView'>
					<input type='hidden' name='return_action' value='index'>
					<input type='hidden' name='return_module' value='Transitions'>
					<input type='hidden' name='return_mode' value='StateTransitions'>
					<input type='hidden' name='parenttab' value='Settings'>

					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'} {* crmv@30683 *}

					<!-- DISPLAY -->
					{if $TMODE neq 'edit'}
						<b><font color=red>{$DUPLICATE_ERROR} </font></b>
					{/if}


					<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
						<tbody>
							<tr>
								<td rowspan="2" valign="top" width="50"><img src="{'Transitions.gif'|resourcever}" alt="{$TMOD.LBL_ST_MANAGER}" title="{$TMOD.LBL_ST_MANAGER}" border="0" height="48" width="48"></td>
								<td class="heading2" valign="bottom"><b> {$SMOD.LBL_SETTINGS} &gt; {$TMOD.LBL_ST_MANAGER}</b></td> <!-- crmv@30683 -->
							</tr>
							<tr>
								<td class="small" valign="top">{$TMOD.LBL_ST_MANAGER_DESCRIPTION}</td>
							</tr>
						</tbody>
					</table>
					   <table border=0 cellspacing=0 cellpadding=3 width=100%>
						   <tr>
							   <td colspan="6" align="right">
								   <a href="index.php?module=Transitions&action=index&parenttab=Settings&reset_session_menu=true" class="crmbutton small create">{$APP.LBL_BACK}</a>
							   </td>
						   </tr>
					   </table>
					<br>

					<table border=0 cellspacing=0 cellpadding=10 width=100% class="listTableTopButtons">
						<tr>
							<td>
							
								<b>{$APP.LBL_MODULE}:</b>
							</td>
							<td>	
								<select onChange="module_selection_change();" name="module_name" id="moduleName" style="width: 200px;">
									<option value="-1" selected>{$APP.LBL_NONE}</option>
									{foreach from=$modules_list key=module_name item=module_name_show name=modules}
										{assign var="module_name_show" value=$module_name|@getTranslatedString:$module_name}	<!-- crmv@16886 -->
										<option value="{$module_name}"{if $ST_PIECE_DATA.ModuleName eq $module_name} selected{/if}>{$module_name_show}</option>
									{/foreach}
								</select>
							</td>
						</tr>
						<tr id="field_line" style="visibility:collapse;">
							<td>
								<b>{$TMOD.LBL_CURR_ST_FIELD}:</b>
							</td>
							<td id="field_select"></td>
							<td colspan="2">
								<div id="make_field_transition" style="visibility:collapse;">
									<input type="button" value="{$TMOD.LBL_MAKE_TRANSITION}" onclick="makefieldTransition();" class="crmButton delete small">
								</div>
								<div id="unmake_field_transition" style="visibility:collapse;">
									<input type="button" value="{$TMOD.LBL_UNMAKE_TRANSITION}" onclick="unmakefieldTransition();" class="crmButton delete small">
								</div>
							</td>
						</tr>
						<tr id="roles_line" style="visibility:collapse;">
							<td>
								<b>{$APP.LBL_ROLE}:</b>
							</td>
							<td>
								{$ROLE_CHECK_PICKLIST}
							</td>
							<td>
								<b>{$TMOD.COPY_FROM} {$APP.LBL_ROLE}:</b>
							</td>
							<td>
								{$COPY_ROLE_CHECK_PICKLIST}
								&nbsp;
								<input type="button" value="{$TMOD.LBL_COPY}" onclick="sttCopy();" class="crmButton delete small">
							</td>
						</tr>
						<tr id="copy_roles_line"></tr>
						<tr>
							<td colspan="5">
								<div id="st_table_content">
									{include file="modules/Transitions/ListViewContents.tpl"}
								</div>
							</td>
						</tr>
					</table>


					   <!-- End of Display -->
					{* SetMenu.tpl *}
					</td>
					</tr>
					</table>
					</td>
					</tr>
					</table>
				</form>
			</td>
			<td valign="top"></td>
		</tr>
	</tbody>
</table>
<!-- crmv@191067e -->