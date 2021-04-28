{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@104853 *}
 
<script language="JavaScript" type="text/javascript" src="{"include/js/customview.js"|resourcever}"></script>
<script language="javascript">
function getColoredListView(customField)
{ldelim}
	var modulename = customField.options[customField.options.selectedIndex].value;
	var modulelabel = customField.options[customField.options.selectedIndex].text;
	document.reload.clv_module.value =  modulename;
	document.reload.submit();
{rdelim}

function hide_all()
{ldelim}
	var obj = null;
	{foreach key=id item=fields from=$STATUS_FIELD_ARRAY}
		obj = getObj("status_field_{$fields.fieldname}");
		obj.style.display = "none";
	{/foreach}
{rdelim}

{literal}
function showFieldValue(fieldname) {
	hide_all();
	if (fieldname.value == '--none--')
		getObj("remove_all").value = 'true';
	if (fieldname.selectedIndex > 0){
		var obj = getObj("status_field_"+fieldname.value);
		obj.style.display = "block";
		document.coloration_form.fieldname.value = fieldname.value;	
	}
}
var gselected_fieldtype = '';
{/literal}
</script>
	<form action="index.php" method="post" name="reload">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="clv_module" value="">
	<input type="hidden" name="module" value="Settings">
	<input type="hidden" name="parenttab" value="Settings">
	<input type="hidden" name="action" value="ColoredListView">
	</form>

<div id="createcf" style="display:block;position:absolute;width:500px;"></div> 

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *} 
			<!-- DISPLAY -->
			{if $MODE neq 'edit'}
			<b><font color=red>{$DUPLICATE_ERROR} </font></b>
			{/if}
			
				<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
				<tbody><tr>
					<td rowspan="2" valign="top" width="50"><img src="{'colored_listview.gif'|resourcever}" alt="{$MOD.LBL_USERS}" title="{$MOD.LBL_USERS}" border="0" height="48" width="48"></td>
					<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_COLORED_LISTVIEW_EDITOR}</b></td> <!-- crmv@30683 -->
				</tr>

				<tr>
					<td class="small" valign="top">{$MOD.LBL_COLORED_LISTVIEW_DESCRIPTION}</td>
				</tr>
				</tbody></table>
				
				<br>
				<table border="0" cellpadding="10" cellspacing="0" width="100%">
				<tbody><tr>
				<td>

				<table class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
				<tbody><tr>
					{* <td class="big" nowrap><strong><span id="module_info">{$MOD.LBL_STATE_FIELDS_ON} "{$APP.$MODULE}" {$APP.LBL_MODULE}</span></strong></td> *}
					<td>
						{$MOD.LBL_SELECT_CF_TEXT}
						<div class="dvtCellInfo" style="width:30%">
							<select name="pick_module" class="detailedViewTextBox" onChange="getColoredListView(this)">
								{* crmv@29752 crmv@105538 *}
								{foreach key=value item=label from=$MODULES}
									{if $MODULE eq $value}
										{assign var="selected_val" value="selected"}
									{else}
										{assign var="selected_val" value=""}
									{/if}
									<option value="{$value}" {$selected_val}>{$label}</option>
								{/foreach}
								{* crmv@29752e crmv@105538e *}
							</select>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<b>{$MOD.LBL_STATE_FIELD_SELECT}</b>:
						<span id="picklist_fields" name="picklist_fields">
							<div class="dvtCellInfo" style="width:30%">
								<select name="pick_field" class="detailedViewTextBox" onChange="showFieldValue(this)">
									<option value="--none--" selected>{'LBL_NONE'|getTranslatedString}</option>
									{foreach key=id item=fields from=$STATUS_FIELD_ARRAY}
										{if $STATUS_FIELD eq $fields.fieldname}
											{assign var="selected_val" value="selected"}
										{else}
											{assign var="selected_val" value=""}
										{/if}
										<option value="{$fields.fieldname}" {$selected_val}>{$fields.fieldlabel|@getTranslatedString:$MODULE}</option>
									{/foreach}
								</select>
							</div>
						</span>
					</td>
					</tr>
				</tbody>
				</table>
				<div class="spacer-20"></div>
				<div id="cfList">
					{include file="ColoredListContent.tpl"}
                </div>	
			{include file="Settings/ScrollTop.tpl"}
			</td>
			</tr>
			</table>
		<!-- End of Display -->
		
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
<br>