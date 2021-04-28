{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@29463 *} 
<script language="javascript">
{literal}

function confirmAction(msg){
	return confirm(msg);
}

function deleteForm(formname,address){
	var status=confirmAction(alert_arr["SURE_TO_DELETE_CUSTOM_MAP"]);
	if(!status){
		return false;
	}
	submitForm(formname, address);
		return true;
}

function submitForm(formName,action){
		document.forms[formName].action=action;
		document.forms[formName].submit();
}
{/literal}
</script>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">

		<div align=center>
			{include file='SetMenu.tpl'}
				<form action="index.php?module=Settings&action=LeadEditFieldMapping" method="post" name="form" onsubmit="VteJS_DialogBox.block();">
				<input type="hidden" name="fld_module" value="{$MODULE}">
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="parenttab" value="Settings">
				<input type="hidden" name="mode">
				<table  class="listTableTopButtons" border="0" cellpadding="5" cellspacing="0" width="100%">
					<tr>
						<td class="big" align="left"><strong>{$MOD.LBL_LEADS_CUSTOM_FIELD_MAPPING}</strong> </td>
						<td align="right"><input type="button" class="crmButton edit small" onclick="javascript: submitForm('form','index.php?module=Settings&action=LeadEditFieldMapping');" alt="{$MOD.Edit}" title="{$MOD.Edit}" value="{$MOD.Edit}"/>
						</td>
					{if $MODULE eq 'Calendar'}
						<input type="radio" name="activitytype" value="E" checked>&nbsp;{$APP.Event}
						<input type="radio" name="activitytype" value="T">&nbsp;{$APP.Task}
					{/if}
					</tr>
				</table>
				</form>
				<table class="listTable" border="0" cellpadding="5" cellspacing="0" width="100%">
					{if $MODULE eq 'Leads'}
					<tr>
						<td rowspan="2" class="colHeader small" width="5%">#</td>
						<td rowspan="2" class="colHeader small" width="20%">{$MOD.FieldLabel}</td>
					    <td rowspan="2" class="colHeader small" width="20%">{$MOD.FieldType}</td>
						<td colspan="4" class="colHeader small" valign="top"><div align="center">{$MOD.LBL_MAPPING_OTHER_MODULES}</div></td>
					</tr>

					<tr>
					  <td class="colHeader small" valign="top" width="18%">{$APP.Accounts}</td>
					  <td class="colHeader small" valign="top" width="18%">{$APP.Contacts}</td>
					  <td class="colHeader small" valign="top" width="19%">{$APP.Potentials}</td>
					  <td class="colHeader small" width="20%">{$MOD.LBL_CURRENCY_TOOL}</td>

					</tr>
					{else}
					<tr>
                      	<td class="colHeader small" width="5%">#</td>
                      	<td class="colHeader small" width="20%">{$MOD.FieldLabel}</td>
                      	<td class="colHeader small" width="20%">{$MOD.FieldType}</td>
                    	{if $MODULE eq 'Calendar'}
                      		<td class="colHeader small" width="20%">{$APP.LBL_ACTIVITY_TYPE}</td>
                      	{/if}
						</tr>
					{/if}
					{foreach item=entries key=id from=$CFENTRIES}
					<tr>
						{foreach item=value from=$entries.map}
							<td class="listTableRow small" valign="top" nowrap>{$value}&nbsp;</td>
						{/foreach}
							<td class="listTableRow small" valign="top" nowrap>
									{if $entries.editable eq 1}
									<form name="form{$entries.cfmid}" method="post">
										<i class="vteicon md-link" title="{'LBL_DELETE_BUTTON_LABEL'|@getTranslatedString:$MODULE}" onclick="javascript: deleteForm('form{$entries.cfmid}','index.php?action=DeleteConvertLeadMapping&module=Settings&parenttab=Settings&cfmid={$entries.cfmid}' );">delete</i>
									</form>
									{/if}
							</td>
					</tr>
					{/foreach}
		</table>
		</form>
		<br>
		{if $MODULE eq 'Leads'}
			<strong>{$APP.LBL_NOTE}: </strong> {$MOD.LBL_CUSTOM_MAPP_INFO}
		{/if}
		</div>
		</td>
        </tr>
        <tr>
        <td valign="top"></td>
        </tr>
</tbody>
</table>
{* crmv@29463e *}