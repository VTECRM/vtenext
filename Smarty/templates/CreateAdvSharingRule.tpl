{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@104853 *}

<script type="text/javascript" src="{"modules/CustomView/CustomView.js"|resourcever}"></script>

<div id="sharingRule">
<form id="customview_form" name="EditView" action="index.php" method="post">
<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
<input type="hidden" name="module" value="Settings">
<input type="hidden" name="parenttab" value="Settings">	
<input type="hidden" name="action" value="SaveAdvSharingRule">
<input type="hidden" name="return_action" value="{$return_action}">
<input type="hidden" name="return_module" value="{$return_module}">
<input type="hidden" name="record" value="{$record}">
<input type="hidden" name="adv_sharing" value="{$adv_sharing}">
<input type="hidden" name="sharing_module" value="{$module}">
<input type="hidden" name="shareId" value="{$shareid}">
<input type="hidden" name="mode" value="{$mode}">
<input type="hidden" name="return" value="{$return}">
<input type="hidden" id="user_dateformat" name="user_dateformat" value="{$DATEFORMAT}">
<script language="javascript" type="text/javascript">
var typeofdata = new Array();
typeofdata['V'] = ['e','n','s','ew','c','k'];
typeofdata['N'] = ['e','n','l','g','m','h'];
typeofdata['T'] = ['e','n','l','g','m','h'];
typeofdata['I'] = ['e','n','l','g','m','h'];
typeofdata['C'] = ['e','n'];
typeofdata['DT'] = ['e','n','l','g','m','h'];
typeofdata['D'] = ['e','n','l','g','m','h'];
typeofdata['NN'] = ['e','n','l','g','m','h'];
typeofdata['E'] = ['e','n','s','ew','c','k'];
var fLabels = new Array();
fLabels['e'] = alert_arr.EQUALS;
fLabels['n'] = alert_arr.NOT_EQUALS_TO;
fLabels['s'] = alert_arr.STARTS_WITH;
fLabels['ew'] = alert_arr.ENDS_WITH;
fLabels['c'] = alert_arr.CONTAINS;
fLabels['k'] = alert_arr.DOES_NOT_CONTAINS;
fLabels['l'] = alert_arr.LESS_THAN;
fLabels['g'] = alert_arr.GREATER_THAN;
fLabels['m'] = alert_arr.LESS_OR_EQUALS;
fLabels['h'] = alert_arr.GREATER_OR_EQUALS;
var noneLabel;
var searchAdvFields = new Array();	{* crmv@29615 *}
</script>

<table border=0 cellspacing=0 cellpadding=5 width=95% align=center> 
<tr>
	<td class="small">
	<table border=0 cellspacing=0 cellpadding=5 width=100% align=center >
	<tr height="5">
		<td colspan="2"></td>
	</tr>
	<tr>
		<td width=20% class="dvtCellLabel"><b>{$mod_strings.LBL_ADV_TITLE}</td>
		<td width=80% align=left class="dvtCellInfo">
			<input type="text" name="title" id ="title" value="{$shareInfo.title}" class=detailedViewTextBox >
		</td>
	</tr>
	<tr height="5">
		<td colspan="2"></td>
	</tr>
	<tr>
		<td width=20% class="dvtCellLabel"><b>{$mod_strings.LBL_ADV_DESC}</td>
		<td width=80% align=left class="dvtCellInfo">
			<textarea cols="30" class="detailedViewTextBox vertical" name="description" id ="description" value="{$shareInfo.description}">{$shareInfo.description}</textarea>
		</td>
	</tr>	
	<tr height="10">
		<td colspan="2"></td>
	</tr>
	<tr>
		<td colspan="2" align="center" class="dvInnerHeader"><b>{$mod_strings.LBL_ADV_RULE_CONDITIONS}</b></td>

	</tr>
	<tr>
   <div id="mnuTab2" {$advdiv} >
      <table width="100%" cellspacing="0" cellpadding="5" class="dvtContentSpace">
       <tr><td>&nbsp;</td></tr>
       <tr><td class="dvtCellInfo">{$MOD.LBL_AF_HDR1}<br /><br />
	<li style="margin-left:30px;">{$MOD.LBL_AF_HDR2}</li>
	<li style="margin-left:30px;">{$MOD.LBL_AF_HDR3}</li>
	<br /><br />
       </td></tr>
       <tr><td>

	<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
	  
	<tr><td colspan="3" class="detailedViewHeader"><b>{$MOD.LBL_RULE}</b></td></tr>
	  
	<tr>
		<td width="50%">
			<div class="dvtCellInfo" style="max-width:50%;display:inline-block">
				<select name="fcol1" id="fcol1" onchange="updatefOptions(this, 'fop1', '{$MODULE}', 1);" class="detailedViewTextBox">	{* crmv@29615 *}
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK1}
						<optgroup label="{$label}" class=\"select\" style=\"border:none\">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
			&nbsp; 
			<div class="dvtCellInfo" style="max-width:40%;display:inline-block">
				<select name="fop1" id="fop1" class="detailedViewTextBox" onchange="updatefOptions(document.getElementById('fcol1'), 'fop1', '{$MODULE}', 1);">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION1}
 						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				{* crmv@29615 *}
				</select>
			</div>
			<input name="fval1" id="fval1" type="hidden" value="{$VALUE1}">
		</td>
		<td width="40%"><div id="Srch_adv_value1"></div></td>
		<td width="10%">
		{* crmv@29615e *}
			<span id="andfcol1">{$AND_TEXT1}</span></nobr>
		</td>
	</tr>
	
	<tr>
		<td width="50%">
			<div class="dvtCellInfo" style="max-width:50%;display:inline-block">
				<select name="fcol2" id="fcol2" onchange="updatefOptions(this, 'fop2', '{$MODULE}', 2);" class="detailedViewTextBox">	{* crmv@29615 *}
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK2}
						<optgroup label="{$label}" class=\"select\" style=\"border:none\">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
			&nbsp; 
			<div class="dvtCellInfo" style="max-width:40%;display:inline-block">
				<select name="fop2" id="fop2" class="detailedViewTextBox" onchange="updatefOptions(document.getElementById('fcol2'), 'fop2', '{$MODULE}', 2);">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION2}
 						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				{* crmv@29615 *}
				</select>
			</div>
			<input name="fval2" id="fval2" type="hidden" value="{$VALUE2}">
		</td>
		<td width="40%"><div id="Srch_adv_value2"></div></td>
		<td width="10%">
		{* crmv@29615e *}
			<span id="andfcol2">{$AND_TEXT2}</span></nobr>
		</td>
	</tr>
	
	<tr>
		<td width="50%">
			<div class="dvtCellInfo" style="max-width:50%;display:inline-block">
				<select name="fcol3" id="fcol3" onchange="updatefOptions(this, 'fop3', '{$MODULE}', 3);" class="detailedViewTextBox">	{* crmv@29615 *}
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK3}
						<optgroup label="{$label}" class=\"select\" style=\"border:none\">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
			&nbsp; 
			<div class="dvtCellInfo" style="max-width:40%;display:inline-block">
				<select name="fop3" id="fop3" class="detailedViewTextBox" onchange="updatefOptions(document.getElementById('fcol3'), 'fop3', '{$MODULE}', 3);">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION3}
 						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				{* crmv@29615 *}
				</select>
			</div>
			<input name="fval3" id="fval3" type="hidden" value="{$VALUE3}">
		</td>
		<td width="40%"><div id="Srch_adv_value3"></div></td>
		<td width="10%">
		{* crmv@29615e *}
			<span id="andfcol3">{$AND_TEXT3}</span></nobr>
		</td>
	</tr>
	
	<tr>
		<td width="50%">
			<div class="dvtCellInfo" style="max-width:50%;display:inline-block">
				<select name="fcol4" id="fcol4" onchange="updatefOptions(this, 'fop4', '{$MODULE}', 4);" class="detailedViewTextBox">	{* crmv@29615 *}
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK4}
						<optgroup label="{$label}" class=\"select\" style=\"border:none\">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
			&nbsp; 
			<div class="dvtCellInfo" style="max-width:40%;display:inline-block">
				<select name="fop4" id="fop4" class="detailedViewTextBox" onchange="updatefOptions(document.getElementById('fcol4'), 'fop4', '{$MODULE}', 4);">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION4}
 						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				{* crmv@29615 *}
				</select>
			</div>
			<input name="fval4" id="fval4" type="hidden" value="{$VALUE4}">
		</td>
		<td width="40%"><div id="Srch_adv_value4"></div></td>
		<td width="10%">
		{* crmv@29615e *}
			<span id="andfcol4">{$AND_TEXT4}</span></nobr>
		</td>
	</tr>
	
	<tr>
		<td width="50%">
			<div class="dvtCellInfo" style="max-width:50%;display:inline-block">
				<select name="fcol5" id="fcol5" onchange="updatefOptions(this, 'fop5', '{$MODULE}', 5);" class="detailedViewTextBox">	{* crmv@29615 *}
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$BLOCK5}
						<optgroup label="{$label}" class=\"select\" style=\"border:none\">
						{foreach item=text from=$filteroption}
							<option {$text.selected} value={$text.value}>{$text.text}</option>
						{/foreach}
					{/foreach}
				</select>
			</div>
			&nbsp; 
			<div class="dvtCellInfo" style="max-width:40%;display:inline-block">
				<select name="fop5" id="fop5" class="detailedViewTextBox" onchange="updatefOptions(document.getElementById('fcol5'), 'fop5', '{$MODULE}', 5);">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=criteria from=$FOPTION5}
 						<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
					{/foreach}
				{* crmv@29615 *}
				</select>
			</div>
			<input name="fval5" id="fval5" type="hidden" value="{$VALUE5}">
		</td>
		<td width="40%"><div id="Srch_adv_value5"></div></td>
		<td width="10%">
		{* crmv@29615e *}
			<span id="andfcol5">{$AND_TEXT5}</span></nobr>
		</td>
	</tr>

	  {*section name=advancedFilter start=1 loop=6 step=1}
	  <tr class="{cycle values="dvtCellInfo,dvtCellLabel"}">
	    <td align="left" width="33%">
	      <select name="fcol{$smarty.section.advancedFilter.index}" id="fcol{$smarty.section.advancedFilter.index}" onchange="updatefOptions(this, 'fop{$smarty.section.advancedFilter.index}');" class="detailedViewTextBox">
	      <option value="">{$MOD.LBL_NONE}</option>
	      {foreach item=filteroption key=label from=$BLOCK}
		<optgroup label="{$label}" class=\"select\" style=\"border:none\">
		{foreach item=text from=$filteroption}
		  <option {$text.selected} value={$text.value}>{$text.text}</option>
		{/foreach}
	      {/foreach}
	      </select>
	    </td>
	    <td align="left" width="33%">
	      <select name="fcol{$smarty.section.advancedFilter.index}" id="fcol{$smarty.section.advancedFilter.index}" class="detailedViewTextBox">
	      <option value="">{$MOD.LBL_NONE}</option>
	      {foreach item=criteria from=$FOPTION}
		<option {$criteria.selected} value={$criteria.value}>{$criteria.text}</option>
	      {/foreach}
	      </select>
	    </td>
	    <td width="34%" nowrap><input name="txt" value="" class="detailedViewTextBox" type="text"  onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'"/>&nbsp;And</td>
	  </tr>
	  {/section*}
	</table>
       </td></tr>
       <tr><td>&nbsp;</td></tr>
     </table>
   </div>
	</tr>
	<tr>
		<td style="white-space:normal;" colspan="2" id="relrules">&nbsp;
		</td>
	</tr>
	</table>
	</td>
</tr>
<tr>
</table>
<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
	<tr>
		<td colspan="2" align="center">
		<input type="submit" class="crmButton small save" name="add" value="{$mod_strings.LBL_ADD_RULE}" onclick="validateAdvRule(event)">&nbsp;&nbsp;	{* crmv@171507 *}
	</td>
	</tr>
</table>
</form></div>
{* crmv@29615 *}
<script language="javascript" type="text/javascript">
{if $mode eq 'edit'}
	var selObj = document.getElementById('fop1');
    var currOption = selObj.options[selObj.selectedIndex];
    var currField = document.getElementById('fcol1');
	if (currOption.value != '' && currField.value != '') changeColumnField('{$module}', currField.value, '1', currOption.value); // crmv@179860

	var selObj = document.getElementById('fop2');
    var currOption = selObj.options[selObj.selectedIndex];
    var currField = document.getElementById('fcol2');
	if (currOption.value != '' && currField.value != '') changeColumnField('{$module}', currField.value, '2', currOption.value); // crmv@179860

	var selObj = document.getElementById('fop3');
    var currOption = selObj.options[selObj.selectedIndex];
    var currField = document.getElementById('fcol3');
	if (currOption.value != '' && currField.value != '') changeColumnField('{$module}', currField.value, '3', currOption.value); // crmv@179860

	var selObj = document.getElementById('fop4');
    var currOption = selObj.options[selObj.selectedIndex];
    var currField = document.getElementById('fcol4');
	if (currOption.value != '' && currField.value != '') changeColumnField('{$module}', currField.value, '4', currOption.value); // crmv@179860

	var selObj = document.getElementById('fop5');
    var currOption = selObj.options[selObj.selectedIndex];
    var currField = document.getElementById('fcol5');
	if (currOption.value != '' && currField.value != '') changeColumnField('{$module}', currField.value, '5', currOption.value); // crmv@179860
{else}
	updatefOptions(document.getElementById("fcol1"), 'fop1', '{$module}', 1);
	updatefOptions(document.getElementById("fcol2"), 'fop2', '{$module}', 2);
	updatefOptions(document.getElementById("fcol3"), 'fop3', '{$module}', 3);
	updatefOptions(document.getElementById("fcol4"), 'fop4', '{$module}', 4);
	updatefOptions(document.getElementById("fcol5"), 'fop5', '{$module}', 5);
{/if}

{if $mode eq 'edit'}
	{assign var="titleLabel" value=$mod_strings.LBL_EDIT_CUSTOM_RULE}
{else}
	{assign var="titleLabel" value=$mod_strings.LBL_ADD_CUSTOM_RULE}
{/if}

{assign var="popupTitle" value=$app_strings.$module|cat:" - "|cat:$titleLabel}

var titleObj = jQuery('#tempdiv{if $adv_sharing}3{/if}_Handle_Title');
titleObj.html("<strong>{$popupTitle}</strong>");
titleObj.addClass('layerPopupHeading');

</script>
{* crmv@29615e *}