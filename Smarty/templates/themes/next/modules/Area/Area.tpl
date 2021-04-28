{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@43942 *}

{if !empty($smarty.request.ajax)}
	&#&#&#{$ERROR}&#&#&#
{else}
	<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="{"modules/Popup/Popup.js"|resourcever}"></script>
	{include file='Buttons_List.tpl'}
	<div id="Buttons_List_3_Container" style="display:none;">
		<table id="bl3" border=0 cellspacing=0 cellpadding=2 width=100% class="small">
			<tr height="34">
				<td align="right" width="100%" style="padding-right:5px;">
					{* crmv@82419 *}
					<form id="basicSearch" name="basicSearch" method="post" action="index.php" onSubmit="return callSearch('Area');">
						<div class="form-group basicSearch">
							<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
							<input type="hidden" name="area" id="basic_search_area" value="{$AREAID}" />
							<input type="hidden" name="module" value="Area" />
							<input type="hidden" name="action" value="index" />
							<input type="hidden" name="ajax" value="true" />
							<input type="text" class="form-control searchBox" id="basic_search_text" name="search_text" value="{if $QUERY_SCRIPT neq ''}{$QUERY_SCRIPT}{else}{$APP.LBL_SEARCH_TITLE}{$AREALABEL}{/if}" onclick="clearText(this)" onblur="restoreDefaultText(this, '{$APP.LBL_SEARCH_TITLE}{$AREALABEL}')" />
							<span class="cancelIcon">
								<i class="vteicon md-sm md-link" id="basic_search_icn_canc" onclick="cancelAreaSearchText('{$APP.LBL_SEARCH_TITLE}{$AREALABEL}')" title="Reset" style="display:none">cancel</i>&nbsp;
							</span>
							<span class="searchIcon">
								<i class="vteicon md-link" id="basic_search_icn_go" onclick="jQuery('#basicSearch').submit();" title="{$APP.LBL_FIND}">search</i>
							</span>
						</div>
					</form>
					{* crmv@82419e *}
				</td>
			</tr>
		</table>
	</div>
	<script type="text/javascript">calculateButtonsList3();</script>
{/if}
{if empty($smarty.request.ajax)}
	<div id="ListViewContents">
{/if}
{if !empty($MODULES)}
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small" align="center">
		<tr bgcolor="#FFFFFF" valign="top">
			{foreach name=areamodules item=module from=$MODULES}
				{math equation="x/y" x=100 y=$smarty.foreach.areamodules.total format="%d" assign=width} {* crmv@181170 *}
				<td width="{$width}%">
					{include file='modules/Area/Module.tpl'}
				</td>
			{/foreach}
		</tr>
	</table>
{/if}
{if empty($smarty.request.ajax)}
	</div>
{/if}
{if $QUERY_SCRIPT neq ''}
	<script type="text/javascript">
		jQuery(document).ready(function() {ldelim}
			{if $AJAXCALL eq true}
				jQuery('#basicSearch').submit();
			{else}
				basic_search_submitted = true;
				jQuery('#basic_search_icn_canc').show();
			{/if}
		{rdelim});
	</script>
{/if}