{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@146671  *}

{include file='CachedValues.tpl'}	{* crmv@26316 *}

<script src="modules/com_workflow/resources/webservices.js" type="text/javascript" charset="utf-8"></script>
<script src="modules/com_workflow/resources/parallelexecuter.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" src="{"include/js/vtlib.js"|resourcever}"></script>	{* crmv@92272 *}
<script src="{"include/js/ListView.js"|resourcever}" type="text/javascript" charset="utf-8"></script>

{literal}
<style type="text/css">
	/* crmv@112299 */
	.populateField, .populateFieldGroup {
		font-size:12px;
	}
	.populateFieldGroup option {
		font-weight:bold;
	}
	.populateFieldGroup option:nth-child(1) {
		font-weight:normal;
	}
	/* crmv@112299e */
</style>
{/literal}

<table border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td align=right width=15% nowrap="nowrap">
			{include file="FieldHeader.tpl" mandatory=true label=$MOD.LBL_CHOOSE_EXTWS}
		</td>
		<td align="left">
			<div class="dvtCellInfo">
				<select name="extwsid" id="extwsid" class="detailedViewTextBox" onchange="ActionCallWSScript.loadForm('{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}','{$INVOLVED_RECORDS|replace:'"':'&quot;'}','{$OTHER_OPTIONS|replace:'"':'&quot;'}','{$ELEMENTS_ACTORS|replace:'"':'&quot;'}')">
					<option value="">{$APP.LBL_PLEASE_SELECT}</option>
					{foreach key=WSID item=WS from=$WSLIST}
						<option value="{$WSID}" {if $METADATA.extwsid eq $WSID}selected=""{/if}>{$WS}</option>
					{/foreach}
				</select>
			</div>
		</td>
		<td align=right width=15% nowrap="nowrap">&nbsp;</td>
	</tr>
</table>
<br>
<select id='task-fieldnames' class="notdropdown" style="display:none;">
	<option value="">{'LBL_PM_SELECT_OPTION_FIELD'|getTranslatedString:'Settings'}</option>
	<option value="back">{'LBL_PM_FIELD_GO_BACK'|getTranslatedString:'Settings'}</option> {* crmv@112299 *}
	{if !empty($SDK_CUSTOM_FUNCTIONS)}
		{foreach key=SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL item=SDK_CUSTOM_FUNCTIONS_BLOCK from=$SDK_CUSTOM_FUNCTIONS}
		<optgroup label="{$SDK_CUSTOM_FUNCTIONS_BLOCK_LABEL}">
			{foreach key=k item=i from=$SDK_CUSTOM_FUNCTIONS_BLOCK}
				<option value="{$k}">{$i}</option>
			{/foreach}
		</optgroup>
		{/foreach}
	{/if}
</select>
<div id="editForm"></div>
<script type="text/javascript">
{if $ACTIONID neq ''}
	jQuery(document).ready(function() {ldelim}
		ActionCallWSScript.loadForm('{$ID}','{$ELEMENTID}','{$ACTIONTYPE}','{$ACTIONID}');
	{rdelim});
{/if}
</script>