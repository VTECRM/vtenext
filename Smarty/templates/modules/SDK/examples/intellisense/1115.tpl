{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{if $sdk_mode eq 'detail'}
	{if $keyreadonly eq 99}
		{foreach item=arr from=$keyoptions}
			{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
				{assign var=keyval value=$APP.LBL_NOT_ACCESSIBLE}
				{assign var=fontval value='red'}
			{else}
				{assign var=fontval value=''}
			{/if}
			{if $keyval eq $arr[1]}
				{assign var=translated_val value=$arr[0]}
			{/if}
		{/foreach}
		{if $translated_val neq ''}
			<td width=25% class="dvtCellInfo" align="left">&nbsp;<font color="{$fontval}">{$translated_val}</font>
			</td>
		{else}
             <td width=25% class="dvtCellInfo" align="left">&nbsp;<font color="{$fontval}">{if $APP.$keyval!=''}{$APP.$keyval}{elseif $MOD.$keyval!=''}{$MOD.$keyval}{else}{$keyval}{/if}</font>
             </td>
		{/if}
	{else}
		{foreach item=arr from=$keyoptions}
			{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
				{assign var=keyval value=$APP.LBL_NOT_ACCESSIBLE}
				{assign var=fontval value='red'}
			{else}
				{assign var=fontval value=''}
			{/if}
			{if $keyval eq $arr[1]}
				{assign var=translated_val value=$arr[0]}
			{/if}
		{/foreach}
		{if $translated_val neq ''}
			<td width=25% class="dvtCellInfo" align="left">&nbsp;<font color="{$fontval}">{$translated_val}</font>
			</td>
		{else}
             <td width=25% class="dvtCellInfo" align="left">&nbsp;<font color="{$fontval}">{if $APP.$keyval!=''}{$APP.$keyval}{elseif $MOD.$keyval!=''}{$MOD.$keyval}{else}{$keyval}{/if}</font>
             </td>
		{/if}
	{/if}
{elseif $sdk_mode eq 'edit'}
	{if $readonly eq 99}
		<!-- //crmv@8982e --> 
		<td width="20%" class="dvtCellLabel" align=right>
			{if $uitype eq 16}
				<font color="red">*</font>
			{/if}
			{$fldlabel}
		</td>
		<td width="30%" align=left class="dvtCellInfo" >
		{foreach item=arr from=$fldvalue}
			{if $arr[2] eq 'selected'}
				{assign var="value_15" value=$arr[1]}
				{assign var="value_15_label" value=$arr[0]}
			{/if}	
		{/foreach}
		<input  type="hidden" name="{$fldname}" tabindex="{$vt_tab}" class="small" value="{$value_15}">
			{$value_15_label}
		</td>
	{elseif $readonly eq 100}
		{foreach item=arr from=$fldvalue}
			{if $arr[2] eq 'selected'}
				{assign var="value_15" value=$arr[1]}
				{assign var="value_15_label" value=$arr[0]}
			{/if}	
		{/foreach}
		<input  type="hidden" name="{$fldname}" tabindex="{$vt_tab}" class="small" value="{$value_15}">
	{else}
		<td width="20%" class="dvtCellLabel" align=right>
			<font color="red">{$mandatory_field}</font>
			{$usefldlabel} {if $MASS_EDIT eq '1'}<input type="checkbox" name="{$fldname}_mass_edit_check" id="{$fldname}_mass_edit_check" class="small" >{/if}
		</td>
		<td width="30%" align=left class="dvtCellInfo">
			{if $MASS_EDIT eq '1'}
				{if $MODULE eq 'Calendar'}
			   		<select name="{$fldname}" tabindex="{$vt_tab}" class="small" style="width:160px;">
				{else}
			   		<select name="{$fldname}" tabindex="{$vt_tab}" class="small">
			   	{/if}
				{foreach item=arr from=$fldvalue}
					{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
					<option value="{$arr[0]}" {$arr[2]}>
						{$arr[0]}
					</option>
					{else}
					<option value="{$arr[1]}" {$arr[2]}>
						{$arr[0]}
					</option>
					{/if}
				{foreachelse}
					<option value=""></option>
					<option value="" style='color: #777777' disabled>{$APP.LBL_NONE}</option>
				{/foreach}
			   </select>
			{else}
				{foreach item=arr from=$fldvalue}
					{if $arr[2] eq 'selected'}
						{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
							{assign var=autosuggest_value value=$arr[0]}
							{assign var=autosuggest_label value=$arr[0]}
						{else}
							{assign var=autosuggest_value value=$arr[1]}
							{assign var=autosuggest_label value=$arr[0]}
						{/if}
					{/if}
				{/foreach}
				<input type="hidden" id="{$fldname}" name="{$fldname}" value="{$autosuggest_value}">
				<input type="hidden" id="{$fldname}_watermark" name="{$fldname}_watermark" value="{$watermark}">
				<input type="text" name="{$fldname}_label" id="{$fldname}_label" class="detailedViewTextBox" style="width:80%;" value="{$autosuggest_label}" onchange="getObj('{$fldname}').value = ''" tabindex="{$vt_tab}">
				<script type="text/javascript" id="autosuggest_javascript_{$fldname}">
					var options = {ldelim}
						script: function (val) {ldelim}
							return "index.php?module=SDK&action=SDKAjax&file=examples/intellisense/search_engine/search&json=true&limit=8&plugin={$uitype}&modulename={$MODULE}&fieldname={$fldname}&input="+val;
						{rdelim}
						,
						varname:"input",
						json:true,						// Returned response type
						delay:500,									
						shownoresults:true,				// If disable, display nothing if no results
						noresults:"{'LBL_NO_RECORDS'|@getTranslatedString:'SDK'}",	// String displayed when no results
						maxresults:8,					// Max num results displayed
						cache:true,						// To enable cache
						minchars:2,						// Start AJAX request with at leat 2 chars
						timeout:8000,					// AutoHide in XX ms
						callback: function (obj) {ldelim} 		// Callback after click or selection
							getObj('{$fldname}').value=obj['id'];
						{rdelim}
					{rdelim};
					// Init autosuggest
					var as_json = new bsn.AutoSuggest('{$fldname}_label', options);
					// Display a little watermak	
					{assign var="watermark" value='LBL_WATERMARK'|@getTranslatedString:'SDK'}
					jQuery("#{$fldname}_label").Watermark("{$watermark}");
				</script>
			{/if}
		</td>
	{/if}
{/if}