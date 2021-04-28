{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* this file displays a widget div - the contents of the div are loaded later usnig javascript *}

{assign var="homepagedashboard_title" value='Home Page Dashboard'|@getTranslatedString:'Home'}
{assign var="keymetrics_title" value='Key Metrics'|@getTranslatedString:'Home'}

{if $tablestuff.Stufftype eq 'SDKIframe'}
	{assign var="stitle" value=$tablestuff.Stufftitle|getTranslatedString}
{else}
	{assign var="stitle" value=$tablestuff.Stufftitle}
{/if}

<script type="text/javascript">var vtdashboard_defaultDashbaordWidgetTitle = '{$homepagedashboard_title}';</script>

<div id="stuff_{$tablestuff.Stuffid}" class="{if $tablestuff.Stufftype eq 'URL'}MatrixLayerURL {else}MatrixLayer {if $tablestuff.Stufftitle eq $homepagedashboard_title}twoColumnWidget{/if}{/if}" style="float:left;overflow-x:hidden;{if $tablestuff.Stufftype eq 'Iframe' || $tablestuff.Stufftype eq 'SDKIframe'}overflow-y:hidden{else}overflow-y:auto;{/if};"> {* crmv@25314 crmv@25466 *}
	<table width="100%" cellpadding="0" cellspacing="0" class="">
		<tr id="headerrow_{$tablestuff.Stuffid}" class="dvInnerHeader headerrow"> {* crmv@61937 *}
			<td align="left" class="homePageMatrixHdr" style="height:30px;" nowrap width=60%><b>&nbsp;{$stitle}</b></td>
			<td align="right" class="homePageMatrixHdr" style="height:30px;" width=5%>
				<span id="refresh_{$tablestuff.Stuffid}" style="position:relative;">&nbsp;&nbsp;</span>
			</td>
			<td align="right" class="homePageMatrixHdr" style="height:30px;" width=35% nowrap>
				{if $tablestuff.Stufftitle eq 'ModComments'|getTranslatedString:'ModComments'}
					{* crmv@82419 *}
					<div class="form-group basicSearch">
						<input id="modcomments_widg_search_text" class="form-control searchBox" type="text" value="{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}" onclick="clearTextModComments(this,'modcomments_widg_search')" onblur="restoreDefaultTextModComments(this, '{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}', 'modcomments_widg_search')" name="search_text" onkeypress="launchModCommentsSearch(event,'modcomments_widg_search');">
						<span class="cancelIcon">
							<i class="vteicon md-link md-sm" id="modcomments_widg_search_icn_canc" style="display:none" title="Reset" onclick="cancelSearchTextModComments('{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}','modcomments_widg_search','url_contents_{$tablestuff.Stuffid}','refresh_{$tablestuff.Stuffid}')">cancel</i>&nbsp;
						</span>
						<span class="searchIcon">
							<i id="modcomments_widg_search_icn_go" class="vteicon md-link" title="{$APP.LBL_FIND}" onclick="loadModCommentsNews(eval(jQuery('#url_contents_{$tablestuff.Stuffid}').contents().find('#max_number_of_news').val()),'url_contents_{$tablestuff.Stuffid}','refresh_{$tablestuff.Stuffid}',parent.jQuery('#modcomments_widg_search_text').val());" >search</i>
						</span>
					</div>
					{* crmv@82419e *}
				{else}
					{if ($tablestuff.Stufftype neq "Default" || $tablestuff.Stufftitle neq $keymetrics_title) && ($tablestuff.Stufftype neq "Default" || $tablestuff.Stufftitle neq $homepagedashboard_title) && ($tablestuff.Stufftype neq "Tag Cloud") && ($tablestuff.Stufftype neq "Iframe") && ($tablestuff.Stufftype neq "SDKIframe")}	{* crmv@25314 crmv@25466 *}
						<a id="editlink" style='cursor:pointer;' onclick="VTE.Homestuff.showEditrow({$tablestuff.Stuffid})">
							<i class="vteicon" title="{'LBL_EDIT'|@getTranslatedString}">tune</i>
						</a>
					{else}
						<i class="vteicon disabled" title="{'LBL_EDIT'|@getTranslatedString}">tune</i>
					{/if}
					{* crmv@208472 *}
					<a style='cursor:pointer;' onclick="VTE.Homestuff.loadStuff({$tablestuff.Stuffid},'{$tablestuff.Stufftype}');">
						<i class="vteicon" title="{'Refresh'|@getTranslatedString}">refresh</i>
					</a>
					{* crmv@208472e *}
					{if $tablestuff.Stufftype eq "Default" || $tablestuff.Stufftype eq "Tag Cloud"}
						<a style='cursor:pointer;' onclick="VTE.Homestuff.HideDefault({$tablestuff.Stuffid})">
							<i class="vteicon" title="{'LBL_HIDE'|@getTranslatedString}">remove</i>
						</a>
					{else}
						<i class="vteicon disabled" title="{'LBL_HIDE'|@getTranslatedString}">remove</i>
					{/if}
					{if $tablestuff.Stufftype neq "Default" && $tablestuff.Stufftype neq "Tag Cloud" && $tablestuff.Stufftype neq "Iframe" && ($tablestuff.Stufftype neq "SDKIframe")}	{* crmv@25314 crmv@25466 *}
						<a id="deletelink" style='cursor:pointer;' onclick="VTE.Homestuff.DelStuff({$tablestuff.Stuffid})">
							<i class="vteicon" title="{'LBL_HIDE'|@getTranslatedString}">clear</i>
						</a>
					{else}
						<i class="vteicon disabled" title="{'LBL_HIDE'|@getTranslatedString}">clear</i>
					{/if}
				{/if}
			</td>
		</tr>
	</table>

	<div class="{if $tablestuff.Stufftype eq 'URL'}MatrixBorderURL{else}MatrixBorder{/if}" {if $tablestuff.Stufftitle eq 'MODCOMMENTS'|getTranslatedString:'Home'}style="height:650px;{if isMobile() eq true}overflow:scroll;-webkit-overflow-scrolling:touch;{/if}"{/if}>
		{if $tablestuff.Stufftype eq "Module"}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{elseif $tablestuff.Stufftype eq "Default" && $tablestuff.Stufftitle neq $homepagedashboard_title}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{elseif $tablestuff.Stufftype eq "RSS"}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{elseif $tablestuff.Stufftype eq "DashBoard"}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{elseif $tablestuff.Stufftype eq "Default" && $tablestuff.Stufftitle eq $homepagedashboard_title}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{elseif $tablestuff.Stufftype eq "Tag Cloud"}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{elseif $tablestuff.Stufftype eq "URL" || $tablestuff.Stufftype eq "Iframe" || $tablestuff.Stufftype eq "SDKIframe"}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{else}
			<div id="maincont_row_{$tablestuff.Stuffid}">
		{/if}
			<div id="stuffcont_{$tablestuff.Stuffid}"></div>
		</div>

		<table width="100%" cellpadding="0" cellspacing="5" class="small scrollLink">
			<tr>
				{if $tablestuff.Stufftype neq "URL" && $tablestuff.Stufftype neq "Charts"} {* crmv@30014 *}
					<td align="left">
						<a href="javascript:;" onclick="VTE.Homestuff.addScrollBar({$tablestuff.Stuffid});">
							{$MOD.LBL_SCROLL}
						</a>
					</td>
				{/if}
				{if $tablestuff.Stufftype eq "Module" || ($tablestuff.Stufftype eq "Default" &&  $tablestuff.Stufftitle neq "Key Metrics" && $tablestuff.Stufftitle neq $homepagedashboard_title && $tablestuff.Stufftitle neq "My Group Allocation" ) || $tablestuff.Stufftype eq "RSS" || $tablestuff.Stufftype eq "DashBoard"}
					<td align="right">
						<a href="#" id="a_{$tablestuff.Stuffid}">
							{$MOD.LBL_MORE}
						</a>
					</td>
				{/if}
			</tr>
		</table>
	</div>
</div>

<script type="text/javascript">
	window.onresize = function(){ldelim}VTE.Homestuff.positionDivInAccord('stuff_{$tablestuff.Stuffid}','{$tablestuff.Stufftitle}','{$tablestuff.Stufftype}','{$tablestuff.Stuffsize}');{rdelim};
	VTE.Homestuff.positionDivInAccord('stuff_{$tablestuff.Stuffid}','{$tablestuff.Stufftitle}','{$tablestuff.Stufftype}','{$tablestuff.Stuffsize}');
</script>