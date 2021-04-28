{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
<script type="text/javascript" src="{"modules/Settings/resources/FieldAccess.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
				<form action="index.php" method="post" name="new" id="form" onsubmit="VteJS_DialogBox.block();" autocomplete="off">
					<input type="hidden" name="module" value="Settings">
					<input type="hidden" name="parenttab" value="Settings">
					<input type="hidden" name="fld_module" id="fld_module" value="{$DEF_MODULE}">

					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'} {* crmv@30683 *} 

					{if $MODE neq 'view'}
						<input type="hidden" name="action" value="UpdateDefaultFieldLevelAccess">
					{else}
						<input type="hidden" name="action" value="EditDefOrgFieldLevelAccess">
					{/if}

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img src="{'orgshar.gif'|resourcever}" alt="{$MOD.LBL_MODULE_NAME}" width="48" height="48" border=0 title="{$MOD.LBL_MODULE_NAME}"></td>
							<td colspan=2 class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$MOD.LBL_FIELDS_ACCESS}</b></td> <!-- crmv@30683 -->
							<td rowspan=2 align=right>&nbsp;</td>
						</tr>
						<tr>
							<td valign=top>{$MOD.LBL_SHARING_FIELDS_DESCRIPTION}</td>
						</tr>
					</table>

					<br>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
						<tr>
							<td class="big"><strong>{$CMOD.LBL_GLOBAL_FIELDS_MANAGER}</strong></td>
							<td align=right>
								{if $MODE neq 'edit'}
									<button name="Edit" type="submit" class="crmbutton edit">{$APP.LBL_EDIT_BUTTON}</button>
								{else}
									<button class="crmbutton save" type="submit" name="Save">{$APP.LBL_SAVE_LABEL}</button>
									<button class="crmButton cancel" type="button" name="Cancel" onclick="window.history.back();">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
								{/if}
							</td>
						</tr>
					</table>

					<table width="100%" border="0" cellpadding="10" cellspacing="0" class="listTableTopButtons">
						<tr>
							<td width="50%">
								<div class="dvtCellInfo">
									<select name="Screen" class="detailedViewTextBox input-inline" onchange="VTE.Settings.FieldAccess.changemodules(this);">
										{foreach key=module item=modulelabel from=$FIELD_INFO}
											{if $module == $DEF_MODULE}
												<option selected value="{$module}">{$modulelabel}</option>
											{else}		
												<option value="{$module}">{$modulelabel}</option>
											{/if}
										{/foreach}
									</select>
								</div>
							</td>
							<td width="50%"></td>
						</tr>
					</table>

					<br>

					{foreach key=module item=info name=allmodules from=$FIELD_LISTS}
						{if $module eq $DEF_MODULE}
							<div id="{$module}_fields" style="display:block">
						{else}
							<div id="{$module}_fields" style="display:none">
						{/if}
							<table class="vtetable">
								<thead>
									<tr>
										<th colspan="8" class="text-nowrap">
											{$CMOD.LBL_FIELDS_AVLBL} {$module|@getTranslatedString:$module}
										</th>
									</tr>
								</thead>
								<tbody>
									{foreach item=elements name=groupfields from=$info}
										<tr>
											{foreach item=elementinfo name=curvalue from=$elements}
												<td width="5%" id="{$smarty.foreach.allmodules.iteration}_{$smarty.foreach.groupfields.iteration}_{$smarty.foreach.curvalue.iteration}">{$elementinfo.1}</td>
												<td class="text-nowrap">{$elementinfo.0}</td> {* crmv@192033 *}
											{/foreach}
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					{/foreach}

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

<script type="text/javascript">
	var def_field = '{$DEF_MODULE}_fields';
</script>