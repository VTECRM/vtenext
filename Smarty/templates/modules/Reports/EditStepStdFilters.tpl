{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@97862 crmv@100585 *}

<div class="stepTitle" style="width=100%">
	<span class="genHeaderGray">{$MOD.LBL_FILTERS}</span><br>
	<span style="font-size:90%">{$MOD.LBL_SELECT_FILTERS_TO_STREAMLINE_REPORT_DATA}</span><hr>
</div>

{$BLOCKJS}

<input type="hidden" id="jscal_dateformat" value="{$DATEFORMAT|strtoupper}">
<input type="hidden" id="jscal_language" value="{$APP.LBL_JSCALENDAR_LANG}">

<br>

<table id="stdFiltersTable" border="0" width="100%" {if !$STDFILTERS || count($STDFILTERS) == 0}style="display:none"{/if}>

	<tr id="stdFilterMasterRow0" style="display:none">
		<td><b>{$APP.LBL_MODULE}</b></td>
		<td colspan="4" class="rptChainContainer">
			<div class="dvtCellInfo chainFirst">
				<span class="chainMainModule"></span>
				<span class="chainArrow">&gt;</span>
			</div>
			<div class="dvtCellInfo chainOthers">
				<select class="detailedViewTextBox chainModule" onchange="EditReport.changeModulesPicklist(this, '#stdFilterFields', 'stdfilter')"></select>
			</div>
		</td>
	</tr>
	
	{* header *}
	<tr id="stdFilterMasterRow1" style="display:none" height="30">
		<td width="80"></td>
		<td width="22%" valign="bottom"><b>{$MOD.LBL_SELECT_COLUMN}</b></td>
		<td width="22%" valign="bottom"><b>{$MOD.LBL_SELECT_TIME}</b></td>
		<td width="22%" valign="bottom"><b>{$MOD.LBL_SF_STARTDATE}</b></td>
		<td width="22%" valign="bottom"><b>{$MOD.LBL_SF_ENDDATE}</b></td>
		{*<td></td>*}
	</tr>
	
	<tr id="stdFilterMasterRow2" style="display:none">
		<td><b>{$APP.Field}</b></td>
		<td>
			<select class="detailedViewTextBox filterFields" style="min-width:200px" ></select>
		</td>
		<td>
			<select name="stdDateFilter" class="detailedViewTextBox" onchange='showDateRange( this.options[ this.selectedIndex ].value )'>
				{foreach item=stdfilter from=$STDFILTEROPTIONS}
					<option value="{$stdfilter.value}">{$stdfilter.text}</option>
				{/foreach}
			</select>
		</td>
		<td nowrap>
			<input name="startdateTpl" type="text" class="detailedViewTextBox" value="" style="width:75%">
			<i class="vteicon md-link iconDateStart">events</i><br>
			<font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
		</td>
		<td nowrap>
			<input name="enddateTpl" type="text" class="detailedViewTextBox" value="" style="width:75%">
			<i class="vteicon md-link iconDateEnd">events</i><br>
			<font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
		</td>

		{* not removable
		<td align="right">
			<i class="vteicon md-link" onclick="EditReport.removeStdFilter(this)">delete</i>
		</td>
		*}
	</tr>
	
	{if $STDFILTERS}
	{foreach item=COND key=CONDIDX from=$STDFILTERS}
		<tr>
			<td><b>{$APP.LBL_MODULE}</b></td>
			<td colspan="4" class="rptChainContainer">
				<div class="dvtCellInfo chainFirst">
					<span class="chainMainModule"></span>
					<span class="chainArrow">&gt;</span>
				</div>
				<div id="stdFilterChainModules_{$CONDIDX}" class="dvtCellInfo chainOthers">
					{foreach item=listmod from=$COND.listmodules}
						<select class="detailedViewTextBox chainModule" style="min-width:200px" onchange="EditReport.changeModulesPicklist(this, '#stdFilterFields', 'stdfilter')">
						{foreach item=mod from=$listmod.list}
							<option value="{$mod.value}" {if $mod.value eq $listmod.selected}selected=""{/if}>{$mod.label}</option>
						{/foreach}
						</select>
					{/foreach}
				</div>
			</td>
		</tr>
		
		{* header *}
		<tr height="30">
			<td width="80"></td>
			<td width="22%" valign="bottom"><b>{$MOD.LBL_SELECT_COLUMN}</b></td>
			<td width="22%" valign="bottom"><b>{$MOD.LBL_SELECT_TIME}</b></td>
			<td width="22%" valign="bottom"><b>{$MOD.LBL_SF_STARTDATE}</b></td>
			<td width="22%" valign="bottom"><b>{$MOD.LBL_SF_ENDDATE}</b></td>
			{*<td></td>*}
		</tr>
		
		<tr>
			<td><b>{$APP.Field}</b></td>
			<td>
				<select id="stdFilterFields" class="detailedViewTextBox filterFields" style="min-width:200px">
					{foreach item=block from=$COND.listfields}
						{foreach item=fld from=$block.fields}
							<option value="{'"'|str_replace:'&quot;':$fld.value}" {if $COND.name eq $fld.value}selected=""{/if} 
								data-wstype="{$fld.wstype}" 
								data-uitype="{$fld.uitype}"
								data-module="{$fld.module}"
								data-fieldname="{$fld.fieldname}"
							>{$fld.label}</option>
						{/foreach}
					{/foreach}
				</select>
			</td>
			
			<td>
				<select name="stdDateFilter" class="detailedViewTextBox" onchange='showDateRange( this.options[ this.selectedIndex ].value )'>
					{foreach item=stdfilter from=$STDFILTEROPTIONS}
						<option value="{$stdfilter.value}" {if $COND.value eq $stdfilter.value}selected=""{/if}>{$stdfilter.text}</option>
					{/foreach}
				</select>
			</td>
			
			<td nowrap>
				<input name="startdate" type="text" class="detailedViewTextBox" value="{$COND.startdate}" style="width:75%" {if $COND.value != 'custom'}readonly=""{/if} />
				<i class="vteicon md-link iconDateStart" id="jscal_trigger_date_start" {if $COND.value != 'custom'}style="visibility:hidden"{/if}>events</i><br>
				<font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
				<script type="text/javascript">
					(function() {ldelim}
						var field = jQuery(document.currentScript).closest('td').find('input[name^=startdate]');
						var trigger = jQuery(document.currentScript).closest('td').find('i.vteicon');
						setupDatePicker(field, {ldelim}
							trigger: trigger,
							date_format: "{$DATEFORMAT|strtoupper}",
							language: "{$APP.LBL_JSCALENDAR_LANG}",
						{rdelim});
					{rdelim})();
				</script>
			</td>
			<td nowrap>
				<input name="enddate" type="text" class="detailedViewTextBox" value="{$COND.enddate}" style="width:75%" {if $COND.value != 'custom'}readonly=""{/if} />
				<i class="vteicon md-link iconDateEnd" id="jscal_trigger_date_end" {if $COND.value != 'custom'}style="visibility:hidden"{/if}>events</i><br>
				<font size="1"><em old="(yyyy-mm-dd)">({$DATEFORMAT|getTranslatedString:'Users'})</em></font>
				<script type="text/javascript">
					(function() {ldelim}
						var field = jQuery(document.currentScript).closest('td').find('input[name^=enddate]');
						var trigger = jQuery(document.currentScript).closest('td').find('i.vteicon');
						setupDatePicker(field, {ldelim}
							trigger: trigger,
							date_format: "{$DATEFORMAT|strtoupper}",
							language: "{$APP.LBL_JSCALENDAR_LANG}",
						{rdelim});
					{rdelim})();
				</script>
			</td>
			
			{* not removable!
			<td align="right">
				<i class="vteicon md-link" onclick="EditReport.removeStdFilter(this)">delete</i>
			</td>
			*}
		</tr>
	{/foreach}
	{/if}
	
</table>

{* removed, a std filter is always visible
<table width="100%" id="stdFilterAddButton" {if count($STDFILTERS) >= 1}style="display:none"{/if}>
	<tr>
		<td align="center">
			<br>
			<button type="button" class="crmbutton edit" onclick="EditReport.addStdFilter()">{"LBL_ADD_NEW_FIELD"|getTranslatedString:'Settings'}</button>
		</td>
	</tr>
</table>
*}

<br>
<hr>
<br>
<p>{$MOD.LBL_STDFILTER_EDITABLE}</p>

<script type="text/javascript">
EditReport.alignStdFilterFields(true); // crmv@106510
</script>