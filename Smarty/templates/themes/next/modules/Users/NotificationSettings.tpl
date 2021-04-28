{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}


<table id="home_page_components" class="tableHeading" width="100%">
	<tr>
		<td class="big">
			<strong>9. {'LBL_NOTIFICATION_MODULE_SETTINGS'|getTranslatedString:'ModNotifications'}</strong>	{* crmv@164190 *}
		</td>
		<td class="small" align="right">&nbsp;</td>
		<input type="hidden" name="notification_module_settings" value="yes">
	</tr>
</table>

<table class="table">
	<tbody>
		{assign var="NOTIFICATION_MODULE_SETTINGS" value=$ID|getNotificationsModuleSettings} 
		{foreach item=FLAGS key=MODULE_NAME from=$NOTIFICATION_MODULE_SETTINGS}
			<tr>
				<td class="align-middle" align="right" width="30%">{$MODULE_NAME|@getTranslatedString:$MODULE_NAME}</td>
				
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
				
				<td class="align-middle" align="center" width="5%">
					<div class="checkbox">
						<label>
							<input type="checkbox" id="{$MODULE_NAME}_notify_create" name="{$MODULE_NAME}_notify_create"{$create_flag}>
						</label>
					</div>
				</td>
				
				<td class="align-middle" align="left" width="20%" nowrap>
					<label for="{$MODULE_NAME}_notify_create">{'LBL_CREATE_NOTIFICATION'|getTranslatedString:'ModNotifications'}</label>
				</td>
				
				<td class="align-middle" align="center" width="5%">
					<div class="checkbox">
						<label>
							<input type="checkbox" id="{$MODULE_NAME}_notify_edit" name="{$MODULE_NAME}_notify_edit"{$edit_flag}>
						</label>
					</div>
				</td>
				
				<td class="align-middle" align="left" nowrap>
					<label for="{$MODULE_NAME}_notify_edit">{'LBL_EDIT_NOTIFICATION'|getTranslatedString:'ModNotifications'}</label>
				</td>
			</tr>
		{/foreach}
	</tbody>
</table>