{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="change_{$FIELDNAME}_div" class="layerPopup" style="display:none;width:250px;z-index:100001;position:absolute;">
	<table width="100%" border="0" cellpadding="3" cellspacing="0" class="layerHeadingULine" >
		<tr>
			<td id="change_to_state_{$FIELDNAME}_div" name="change_to_state_{$FIELDNAME}_div" class="layerPopupHeading" align="left" width="100%" style="font-size:12px" >  </td> 
		</tr>
	</table>
	<table border=0 cellspacing=0 cellpadding=5 width=95% align=center> 
		<tr>
			<td class=small >
				<form name="change_{$FIELDNAME}_form">
			      	<input type="hidden" id="change_status_fieldlabel" 	name="change_status_fieldlabel" value="">
		      		<input type="hidden" id="change_status_module" 		name="change_status_module" 	value="">
		      		<input type="hidden" id="change_status_uitype" 		name="change_status_uitype" 	value="">
		      		<input type="hidden" id="change_status_tablename" 	name="change_status_tablename" 	value="">
		      		<input type="hidden" id="change_status_fieldname" 	name="change_status_fieldname" 	value="">
		      		<input type="hidden" id="change_status_crmid" 		name="change_status_crmid" 		value="">
		      		<input type="hidden" id="change_status_tagvalue" 	name="change_status_tagvalue" 	value="">
					<table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
						<tr>
							<td width="100%">
								<div class="dvtCellInfo">
									<textarea class="detailedViewTextBox" tabindex="2" onFocus="this.className='detailedViewTextBoxOn'" name="motivation_{$FIELDNAME}"  id="motivation_{$FIELDNAME}" onBlur="this.className='detailedViewTextBox'" width="100%" cols="25" rows="8"></textarea>
								</div>
							</td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
	</table>
	<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
		<tr>
			<td align="center">
				<input type="button" name="button" class="crmbutton small save" value="{$TMOD.LBL_STATUS_SAVE}" onClick="change_state()">
				<input type="button" name="button" class="crmbutton small cancel" value="{$TMOD.LBL_STATUS_CANCEL}" onClick="hide_question('change_{$FIELDNAME}_div')">
			</td>
		</tr>
	</table>
</div>
<table align="center" width=100%>
	{if $HISTORY_VOID neq 'true'}
		<tr>
			<td class="rightMailMergeContent" colspan=2>
				{if ($HISTORY.numrows) eq 1}
					{$TMOD.LBL_STATUS_CHANGED_USER} <b>{$HISTORY.username}</b> 
					{$TMOD.LBL_STATUS_CREATED} <b>{$HISTORY.new_status|@getTranslatedString:$MODULENAME}</b> 
					{$TMOD.LBL_STATUS_CHANGED_USER_ON} <b>{$HISTORY.date}</b>				
				{else}
					{$TMOD.LBL_STATUS_CHANGED_USER} <b>{$HISTORY.username}</b> 
					{$TMOD.LBL_STATUS_CHANGED_USER_FROM} <b>{$HISTORY.old_status|@getTranslatedString:$MODULENAME}</b> 
					{$TMOD.LBL_STATUS_CHANGED_USER_TO} <b>{$HISTORY.new_status|@getTranslatedString:$MODULENAME}</b> 
					{$TMOD.LBL_STATUS_CHANGED_USER_ON} <b>{$HISTORY.date}</b>
				{/if}
			</td>
		</tr>
		<tr>
			<td class="rightMailMergeContent" colspan=2>
				<b>{$TMOD.LBL_STATUS_CHANGED_MOTIVATION}</b>{$HISTORY.motivation}
			</td>
		</tr>
	{/if}
	<tr>
		<td align='left' colspan=2>
			<b>{$TITLE}</b>
		</td>
	</tr>
    {foreach from=$PERMITTED_STATUS key=num item=status name=permitted_status_foreach}
    	<tr>
    		<td class='lvtColData' align='left'>
    			{assign var="status_label" value=$status|@getTranslatedString:$MODULENAME}
    			{if $STATES_DISABLED eq	'true'}
    				{$status_label}
    			{elseif $ACTUAL_STATUS eq $status}
    				<b>{$status_label} [{$TMOD.LBL_STATUS_BLOCK_ACTUAL_STATE}]</b>
    			{else}
    				<a href="javascript:void(0);" onClick="query_change_state_motivation('{$status_label}','{$MODULENAME}','{$UITYPE}','vte_{$FIELDNAME}','{$FIELDNAME}',{$ACTUAL_ID},'{$status}')">{$status_label}</a>
    			{/if}
    		</td>
    	</tr>
    {/foreach}
    {if $STATES_DISABLED eq 'true'}
		<tr>
			<td class='rightMailMergeHeader' align='left' colspan=2>
				<b>{'LBL_STATUS_MANDATORY_FIELDS'|@getTranslatedString:$MODULE}</b> 
			</td>
		</tr>
		{foreach from=$MANDATORY_FIELDS key=num item=mandatory_field name=mandatory_fields_foreach}
			<tr>
				<td class='lvtColData' align='left' colspan=2>
					<font color=red><b>{$mandatory_field.1|@getTranslatedString:$MODULENAME}</b></font>
				</td>
			</tr>	
		{/foreach}
	{/if}	
</table>
{$EXTRA_MESSAGE} {* crmv@sdk-27926 *}