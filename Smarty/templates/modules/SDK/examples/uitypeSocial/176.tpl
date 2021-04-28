{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{if $sdk_mode eq 'detail'}
	{if $keyreadonly eq 99}
		<td width=25% class="dvtCellInfo" align="left">
			&nbsp;<span id ="dtlview_{$label}">
			<img src="modules/SDK/examples/uitypeSocial/img/orico.png" align="left" alt="Orkut" title="Orkut" />
			{if $keyval neq ''}
			  <a target="_blank" href="http://www.orkut.com/Main#Profile?uid={$keyval}">http://www.orkut.com/Main#Profile?uid={$keyval}</a>
			{/if}
			</span>
		</td>
	{else}
		<td width="25%" class="dvtCellInfo" align="left" id="mouseArea_{$label}" onmouseover="hndMouseOver({$keyid},'{$label}');" onmouseout="fnhide('crmspanid');">
			&nbsp;&nbsp;<span id="dtlview_{$label}">
			<img src="modules/SDK/examples/uitypeSocial/img/orico.png" align="left" alt="Orkut" title="Orkut" />
			{if $keyval neq ''}
			  <a target="_blank" href="http://www.orkut.com/Main#Profile?uid={$keyval}">http://www.orkut.com/Main#Profile?uid={$keyval}</a>
			{/if}
			</span>
			<div id="editarea_{$label}" style="display:none;">
				<input class="detailedViewTextBox" onFocus="this.className='detailedViewTextBoxOn'" onBlur="this.className='detailedViewTextBox'" type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
				<br><input name="button_{$label}" type="button" class="crmbutton small save" value="{$APP.LBL_SAVE_LABEL}" onclick="dtlViewAjaxSave('{$label}','{$MODULE}',{$keyid},'{$keytblname}','{$keyfldname}','{$ID}');fnhide('crmspanid');"/> {$APP.LBL_OR}
				<a href="javascript:;" onclick="hndCancel('dtlview_{$label}','editarea_{$label}','{$label}')" class="link">{$APP.LBL_CANCEL_BUTTON_LABEL}</a>
			</div>
		</td>
	{/if}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		<td width=20% class="dvtCellLabel" align=right>
			<font color="red">*</font>{$fldlabel}
		</td>
		<td width=30% align=left class="dvtCellInfo">
		    <img src="modules/SDK/examples/uitypeSocial/img/orico.png" align="left" alt="Orkut" title="Orkut" /><span>&nbsp;http://www.orkut.com/Main#Profile?uid=&nbsp;</span>
			<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class=detailedViewTextBox >
			{$fldvalue}
		</td>
	{elseif $readonly eq 100}
		<input type="hidden" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class=detailedViewTextBox >
	{else}
		<td width=20% class="dvtCellLabel" align=right>
			<font color="red">{$mandatory_field}</font>{$usefldlabel} {if $MASS_EDIT eq '1'}<input type="checkbox" name="{$fldname}_mass_edit_check" id="{$fldname}_mass_edit_check" class="small">{/if}
		</td>
		<td width=30% align=left class="dvtCellInfo">
			<img src="modules/SDK/examples/uitypeSocial/img/orico.png" align="left" alt="Orkut" title="Orkut" /><span>&nbsp;http://www.orkut.com/Main#Profile?uid=&nbsp;</span>
			<input style="width:30%;" type="text" name="{$fldname}" tabindex="{$vt_tab}" value="{$fldvalue}" tabindex="{$vt_tab}" class=detailedViewTextBox onFocus="this.className='detailedViewTextBoxOn'" onBlur="this.className='detailedViewTextBox'">
		</td>
	{/if}
{/if}