{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@43942 *}

<form name="SaveArea" action="index.php" onSubmit="VteJS_DialogBox.block(); selectModuleLists();">
	<input type="hidden" name="module" value="Area">
	<input type="hidden" name="action" value="AreaAjax">
	<input type="hidden" name="file" value="SaveSettings">
	<input type="hidden" name="area" value="{$AREAID}">
	<input type="hidden" name="mode" value="{$MODE}">
	<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			{if $MODE neq 'create' && ($AREAID eq 0 || $AREAID eq -1)}
				<td nowrap><span class="helpmessagebox" style="font-style: italic;">{'LBL_AREAS_SETTINGS_NOTE'|getTranslatedString}</span></td>
			{/if}
			<td width="100%" align="right">
				{if $PERMISSION_DELETE}
					<input type="button" class="crmbutton delete" value="{'LBL_DELETE_BUTTON_LABEL'|getTranslatedString}" onClick="this.form.mode.value='delete'; this.form.submit();">
				{/if}
				<input type="submit" class="crmbutton save" value="{'LBL_SAVE_BUTTON_LABEL'|getTranslatedString}">
			</td>
		</tr>
	</table>
	{if $MODE eq 'create'}
		<table class="small" border="0" cellpadding="5" cellspacing="0" width="50%" align="center">
			<tr>
				<td colspan="2">
					{include file="EditViewUI.tpl" MODULE="Area" DIVCLASS="dvtCellInfo" uitype=1 fldlabel="Nome Area" fldname="areaname" fldvalue=""}
				</td>
			</tr>
		</table>
	{/if}
	<table class="small" border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr>
			<td width=45% align=center colspan="2">
				<div>
					<select name="other_modules[]" id="right" class="detailedViewTextBox" size=5 multiple style="height:300px;width:100%">
				    {foreach key=id item=info from=$OTHERMODULES}
						<option value="{$info.tabid}">{$info.translabel}</option>
			      	{/foreach}
				    </select>
				</div>
			</td>
			<td width=10% align=center>
			  	<div>
					<i name="right2left" class="vteicon md-link" onclick="moveLeftRight(this)">arrow_forward</i><br/><br/>
				    <i name="left2right" class="vteicon md-link" onclick="moveLeftRight(this)">arrow_back</i>
			  	</div>
			</td>
			<td width=40% align=center>
				<div>
					<select name="modules[]" id="left" class="detailedViewTextBox" size=5 multiple style="height:300px;width:100%">
					{foreach key=id item=info from=$CURRENTMODULES}
						<option value="{$info.tabid}" {if $info.name|@in_array:$HIGHTLIGHT_FIXED_MODULES || $info.name|@in_array:$HIDE_FIXED_MODULES}disabled{/if}>{$info.translabel}</option>
					{/foreach}
			    	</select>
			 	</div>
			 </td>
			 <td width=5% align=center>
				{if $AREAID neq -1}
					<a href="javascript:;"><i class="vteicon" onclick="ModuleAreaManager.moveUp('left')">arrow_upward</i></a><br /><br />
					<a href="javascript:;"><i class="vteicon" onclick="ModuleAreaManager.moveDown('left')">arrow_downward</i></a>
				{/if}
			</td>
		</tr>
	</table>
</form>
<script type="text/javascript">
{literal}
function moveLeftRight(self) {
    var arr = jQuery(self).attr("name").split("2");
    var from = arr[0];
    var to = arr[1];
    console.log(from, to);
    jQuery("#" + from + " option:selected").each(function(){
      jQuery("#" + to).append(jQuery(this).clone());
      jQuery(this).remove();
    });
    return false;
}
function selectModuleLists() {
	jQuery("select[name='modules[]'] option").each(function () {
		jQuery(this).selected(true);
	});
	jQuery("select[name='other_modules[]'] option").each(function () {
		jQuery(this).selected(true);
	});
}
{/literal}
</script>