{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<!-- buttons for the home page -->
<!-- crmv@18549 -->
{include file="Buttons_List.tpl"}
<div id="vtbusy_info" style="display: none;">
	{include file="LoadingIndicator.tpl"}
</div>
<!-- crmv@18549e -->

<!--button related stuff -->
<form name="Homestuff" id="formStuff">
	<input type="hidden" name="action" value="homestuff">
	<input type="hidden" name="module" value="Home">
	
	<div id="addWidgetDropDown" onmouseover="VTE.Homestuff.fnShowWindow();" onmouseout="VTE.Homestuff.fnRemoveWindow();" style="display:none;">
		<ul class="widgetDropDownList">
			<li>
				<a href="javascript:VTE.Homestuff.chooseType('Module');VTE.Homestuff.fnRemoveWindow();" class="drop_down" id="addmodule">
					{$MOD.LBL_HOME_MODULE}
				</a>
			</li>
			{if $ALLOW_RSS eq "yes"}
				<li>
					<a href="javascript:VTE.Homestuff.chooseType('RSS');VTE.Homestuff.fnRemoveWindow();" class="drop_down" id="addrss">
						{$MOD.LBL_HOME_RSS}
					</a>
				</li>
			{/if}
			{* crmv@30014 removed dashboards, add charts *}
			{if $ALLOW_CHARTS eq "yes"}
				<li>
					<a href="javascript:VTE.Homestuff.chooseType('Charts');VTE.Homestuff.fnRemoveWindow();" class="drop_down" id="addchart">
						{$APP.SINGLE_Charts}
					</a>
				</li>
			{/if}
			{* crmv@30014e *}
			<!-- this has been commented as some websites are opening up in full page (they have a target="_top")-->
			<li>
				<a href="javascript:VTE.Homestuff.chooseType('URL');VTE.Homestuff.fnRemoveWindow();" class="drop_down" id="addURL">
					{$MOD.LBL_URL}
				</a>
			</li>
		</ul>
	</div>

	<!-- the following div is used to display the contents for the add widget window -->
	<div id="addWidgetsDiv" class="crmvDiv" style="z-index:2000;display:none;width:400px;">
		<input type="hidden" name="stufftype" id="stufftype_id">
		<div class="closebutton" onClick="VTE.Homestuff.hideWidget();"></div>
		<table border="0" cellpadding="5" cellspacing="0" width="100%">
			<tr style="cursor:move;" height="34">
				<td id="addWidgetsDiv_Handle" style="padding:5px" class="level3Bg">
					<table cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td width="50%" id="divHeader"></td>
							<td width="50%" align="right">
								<button type="button" name="save" id="savebtn" class="crmbutton save" onclick="VTE.Homestuff.frmValidate()">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		<!-- popup specific content fill in starts -->
		<table border="0" cellspacing="2" cellpadding="3" width="100%" align="center" bgcolor="white">
			{* crmv@30014 *}
			<tr id="chartRow" style="display:none">
				<td class="dvtCellLabel" align="right" width="110">{$MOD.LBL_HOME_CHART_NAME}</td>
				<td id="selChartName" class="dvtCellInfo" width="300" colspan="2"></td>
			</tr>
			{* crmv@30014e *}
			<tr id="StuffTitleId">
				<td class="dvtCellLabel"  width="110" align="right">
					{$MOD.LBL_HOME_STUFFTITLE}
					<font color='red'>*</font>
				</td>
				<td class="dvtCellInfo" colspan="2" width="300">
					<input type="text" name="stufftitle" id="stufftitle_id" class="detailedViewTextBox" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'">
				</td>
			</tr>
			<tr id="homeURLField">
				<td class="dvtCellLabel"  width="110" align="right">
					{$MOD.LBL_URL}
					<font color='red'>*</font>
				</td>
				<td class="dvtCellInfo" colspan="2" width="300">
					<input type="text" name="url" id="url_id" class="detailedViewTextBox" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'">
				</td>
			</tr>
			<tr id="showrow">
				<td class="dvtCellLabel"  width="110" align="right">{$MOD.LBL_HOME_SHOW}</td>
				<td class="dvtCellInfo" width="300" colspan="2">
					<select name="maxentries" id="maxentryid" class="detailedViewTextBox" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'" style="width:60%">
						{section name=iter start=1 loop=13 step=1}
						<option value="{$smarty.section.iter.index}">{$smarty.section.iter.index}</option>
						{/section}
					</select>&nbsp;&nbsp;{$MOD.LBL_HOME_ITEMS}
				</td>
			</tr>
			<tr id="moduleNameRow">
				<td class="dvtCellLabel"  width="110" align="right">{$MOD.LBL_HOME_MODULE}</td>
				<td width="300" class="dvtCellInfo" colspan="2">
					<select name="selmodule" id="selmodule_id" onchange="VTE.Homestuff.setFilter(this)" class="detailedViewTextBox" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'">
						{foreach item=arr from=$MODULE_NAME}
							{assign var="MODULE_LABEL" value=$arr.1|getTranslatedString:$arr.1}
							<option value="{$arr.1}">{$MODULE_LABEL}</option>
						{/foreach}
					</select>
					<input type="hidden" name="fldname">
				</td>
			</tr>
			<tr id="moduleFilterRow">
				<td class="dvtCellLabel" align="right" width="110" >{$MOD.LBL_HOME_FILTERBY}</td>
				<td id="selModFilter_id" colspan="2" class="dvtCellInfo" width="300">
				</td>
			</tr>
			<tr id="modulePrimeRow">
				<td class="dvtCellLabel" width="110" align="right" valign="top">{$MOD.LBL_HOME_Fields}</td>
				<td id="selModPrime_id" colspan="2" class="dvtCellInfo" width="300">
				</td>
			</tr>
			<tr id="rssRow" style="display:none">
				<td class="dvtCellLabel"  width="110" align="right">{$MOD.LBL_HOME_RSSURL}<font color='red'>*</font></td>
				<td width="300" colspan="2" class="dvtCellInfo"><input type="text" name="txtRss" id="txtRss_id" class="detailedViewTextBox" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'"></td>
			</tr>
			<tr id="dashNameRow" style="display:none">
				<td class="dvtCellLabel"  width="110" align="right">{$MOD.LBL_HOME_DASHBOARD_NAME}</td>
				<td id="selDashName" class="dvtCellInfo" colspan="2" width="300"></td>
			</tr>
			<tr id="dashTypeRow" style="display:none">
				<td class="dvtCellLabel" align="right" width="110">{$MOD.LBL_HOME_DASHBOARD_TYPE}</td>
				<td id="selDashType" class="dvtCellInfo" width="300" colspan="2">
					<select name="seldashtype" id="seldashtype_id" class="detailedViewTextBox" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'" style="width:60%">
						<option value="horizontalbarchart">{$MOD.LBL_HOME_HORIZONTAL_BARCHART}</option>
						<option value="verticalbarchart">{$MOD.LBL_HOME_VERTICAL_BARCHART}</option>
						<option value="piechart">{$MOD.LBL_HOME_PIE_CHART}</option>
					</select>
				</td>
			</tr>
		</table>
		<!-- popup specific content fill in ends -->
	</div>
</form>
<!-- add widget code ends -->

<div id="seqSettings" style="background-color:#E0ECFF;z-index:2000;display:none;"></div>	{* crmv@30406 *}

<div id="changeLayoutDiv" class="crmvDiv" style="z-index:2000;display:none;">
	<div class="closebutton" onClick="VTE.Homestuff.hideOptions('changeLayoutDiv');"></div>
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr style="cursor:move;" height="34">
			<td id="changeLayoutDiv_Handle" style="padding:5px" class="level3Bg">
				<table cellpadding="0" cellspacing="0" width="100%">
					<tr>
						<td width="80%"><b>{$MOD.LBL_HOME_LAYOUT}</b></td>
						<td width="20%" align="right" nowrap="">
							<button type="button" name="save" id="savebtn" class="crmbutton save" onclick="VTE.Homestuff.saveLayout();">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<table border="0" cellpadding="5" cellspacing="2" width="100%">
		<tr id="numberOfColumns">
			<td class="dvtCellLabel" align="right">
				{$MOD.LBL_NUMBER_OF_COLUMNS}
			</td>
			<td class="dvtCellInfo">
				<select id="layoutSelect" class="detailedViewTextBox" onfocus="this.className='detailedViewTextBoxOn'" onblur="this.className='detailedViewTextBox'">
					<option value="2">{$MOD.LBL_TWO_COLUMN}</option>
					<option value="3">{$MOD.LBL_THREE_COLUMN}</option>
					<option value="4">{$MOD.LBL_FOUR_COLUMN}</option>
				</select>
			</td>
		</tr>
	</table>
</div>

{* crmv@192014 *}
{literal}
<script>
	jQuery('#changeLayoutDiv').draggable({
		handle: '#changeLayoutDiv_Handle'
	});	
	jQuery('#addWidgetsDiv').draggable({
		handle: '#addWidgetsDiv_Handle'
	});
</script>
{/literal}
{* crmv@192014e *}