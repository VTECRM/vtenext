{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{assign var="MODS4ROW" value="2"}
{assign var="MODS4AREA" value="8"}
{math equation="x / y" x=50 y=$MODS4ROW assign="AREACOLWIDTH"}
{assign var="AREACOLWIDTH" value=$AREACOLWIDTH|cat:"%"}

<div id="{if !empty($UNIFIED_SEARCH_AREAS_ID)}{$UNIFIED_SEARCH_AREAS_ID}{else}UnifiedSearchAreas{/if}" class="{if !empty($UNIFIED_SEARCH_AREAS_CLASS)}{$UNIFIED_SEARCH_AREAS_CLASS}{else}drop_mnu_all{/if}">
	<table cellspacing="0" cellpadding="5" border="0" class="small" style="width:100%">	{* crmv@59091 *}
	
	{if !$SKIP_UNIFIED_SEARCH_AREAS}
		<tr id="UnifiedSearchAreasUnifiedRowInput" style="display:none;">
			<td colspan="{$MODS4ROW}" align="center">
				{* crmv@82419 *}
				<div class="globalSearch form-group basicSearch">
					<input type="text" id="unifiedsearchnew_query_string" name="query_string" value="{$UNIDIEDSEARCH_QUERY_STRING}" class="detailedViewTextBox" placeholder="{'LBL_SEARCH_STRING'|getTranslatedString}" onclick="clearText(this,'unified_search_icn_canc')">	{* crmv@31197 crmv@159559 *}
					<span class="cancelIcon" style="top:4px; right:5px">
						<i class="vteicon md-link md-sm" id="unified_search_icn_canc" style="display:none" title="Reset" onclick="cancelSearchText('','unifiedsearchnew_query_string','unified_search_icn_canc')">cancel</i>&nbsp;
					</span>
				</div>
				{* crmv@82419e *}
			</td>
		</tr>
		<tr id="UnifiedSearchAreasUnifiedRow" style="display:none;">
			<td colspan="{$MODS4ROW}" style="font-size:13px;font-weight:bold;">
				<form name="UnifiedSearch" method="post" action="index.php" target="_blank">
				<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<input type="hidden" name="action" value="UnifiedSearch">
				<input type="hidden" name="module" value="Home">
				<input type="hidden" name="parenttab" value="{$CATEGORY}">
				<input type="hidden" name="search_onlyin" value="--USESELECTED--">
				<input type="hidden" id="unifiedsearch_query_string" name="query_string" value="">
				<input type="button" class="crmbutton edit btn-block" value="{$APP.LBL_SEARCH_ALL}" onClick="jQuery('#unifiedsearch_query_string').val(jQuery('#unifiedsearchnew_query_string').val());this.form.submit();" />
				</form>
			</td>
		</tr>
		<tr id="UnifiedSearchAreasUnifiedRow1" style="display:none;">
			<td colspan="{$MODS4ROW}"></td>
		</tr>
	{/if}
	
	{assign var="count" value=0}
	{assign var="count_tmp" value=0}
	
	{foreach key=number item=info from=$AREAMODULELIST}
		{assign var="type" value=$info.type}
		{assign var="areainfo" value=$info.info}
		{assign var="areaid" value=$areainfo.id}
		{assign var="areaname" value=$areainfo.name}
		{assign var="arealabel" value=$areainfo.translabel}
		{assign var="areaurl" value=$areainfo.index_url}
		{assign var="areamodules" value=$areainfo.info}
		
		{if $count is div by $MODS4ROW}
			{assign var="count_tmp" value=1}
			<tr valign="top">
		{/if}
		
		<td width="{$AREACOLWIDTH}">
			<table cellspacing="0" cellpadding="3" border="0" width="100%">
			
				<tr height="25">
					<td style="padding:3px;font-size:13px;font-weight:bold;">
						{if $areaid neq 0 and $areaid neq -1}
							<input type="button" class="crmbutton edit" value="{$arealabel}" onClick="UnifiedSearchAreasObj.openArea('{$areaurl}');" style="width:100%" />
						{else}
							<span class="warning">{$arealabel}</span>
						{/if}
					</td>
				</tr>
				
				{assign var="count_modules" value=0}
				{foreach item=mod from=$areamodules}
					{if $count_modules gt 0 && $count_modules is div by $MODS4AREA}
						</table></td>
						{if $count_tmp is div by $MODS4ROW}
							</tr>
						{/if}
						{assign var="count" value=$count+1}
						{assign var="count_tmp" value=1}
						{if $count is div by $MODS4ROW}
							{assign var="count_tmp" value=1}
							<tr valign="top">
						{/if}
						<td width="{$AREACOLWIDTH}">
							<table cellspacing="0" cellpadding="3" border="0" width="100%">
								<tr height="25"><td style="font-size:13px;font-weight:bold;border-bottom:1px solid #E0E0E0;"></td></tr>
					{/if}
					{assign var="count_modules" value=$count_modules+1}
					<tr>
						<td width="25">
							<a href="javascript:;" onClick="UnifiedSearchAreasObj.openModule('{$mod.index_url}','{$mod.list_url}');">	{* crmv@107077 *}
								{assign var="first_letter" value=$mod.translabel|substr:0:1|strtoupper}
								<div class="UnifiedSearchAreasUnifiedItem" style="padding:5px">
									<div class="vcenter text-left circle">
										<i class="md-sm icon-module icon-{$mod.name|strtolower}" data-first-letter="{$first_letter}"></i>
									</div>
									<span class="vcenter">{$mod.translabel}</span>
								</div>
							</a>
						</td>
					</tr>
				{/foreach}
				
			</table>
		</td>
		
		{if $count_tmp is div by $MODS4ROW}
			</tr>
		{/if}
		
		{assign var="count" value=$count+1}
		{assign var="count_tmp" value=1}
	{/foreach}
	
	{if $IS_ADMIN eq '1' || $BLOCK_AREA_LAYOUT eq '0'}
		<tr>
			<td colspan="{$MODS4ROW}" align="right">
				<div class="divider"></div>
				<a href='javascript:void(0);' onclick="ModuleAreaManager.showSettings();">
					<i class="vteicon md-sm md-text md-link">settings</i>&nbsp;{'LBL_AREAS_SETTINGS'|getTranslatedString}
				</a>
			</td>
		</tr>
	{/if}
	</table>
</div>
{* crmv@159559 *}
{if !empty($UNIDIEDSEARCH_QUERY_STRING)}
<script type="text/javascript">
	jQuery('#unifiedsearchnew_query_string').data('restored',false)
</script>
{/if}
{* crmv@159559e *}