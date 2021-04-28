{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* this file displays a widget div - the contents of the div are loaded later using javascript *}

{assign var="homepagedashboard_title" value='Home Page Dashboard'|@getTranslatedString:'Home'}
{assign var="keymetrics_title" value='Key Metrics'|@getTranslatedString:'Home'}

{if $tablestuff.Stufftype eq 'SDKIframe'}
	{assign var="stitle" value=$tablestuff.Stufftitle|getTranslatedString}
{else}
	{assign var="stitle" value=$tablestuff.Stufftitle}
{/if}

<script type="text/javascript">var vtdashboard_defaultDashbaordWidgetTitle = '{$homepagedashboard_title}';</script>

<div id="stuff_{$tablestuff.Stuffid}" class="{if $tablestuff.Stufftype eq 'URL'}MatrixLayerURL {else}MatrixLayer {if $tablestuff.Stufftitle eq $homepagedashboard_title}twoColumnWidget{/if}{/if}">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" class="stuffHeader">
		<tr id="headerrow_{$tablestuff.Stuffid}" style="height:50px">
			<td align="left" class="homePageMatrixHdr stuffTitle headerrow" width="70%">
				<span>{$stitle}</span>
			</td>
			<td align="right" class="homePageMatrixHdr" width="30%" nowrap>
				{if $tablestuff.Stufftitle eq 'ModComments'|getTranslatedString:'ModComments'}
					<div class="form-group basicSearch">
						<span class="vcenter" id="refresh_{$tablestuff.Stuffid}">&nbsp;&nbsp;</span>
						<input id="modcomments_widg_search_text" class="form-control searchBox vcenter" type="text" value="{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}" onclick="clearTextModComments(this,'modcomments_widg_search')" onblur="restoreDefaultTextModComments(this, '{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}', 'modcomments_widg_search')" name="search_text" onkeypress="launchModCommentsSearch(event,'modcomments_widg_search');">
						<span class="cancelIcon">
							<i class="vteicon md-link md-sm" id="modcomments_widg_search_icn_canc" style="display:none" title="Reset" onclick="cancelSearchTextModComments('{$APP.LBL_SEARCH_TITLE}{'ModComments'|getTranslatedString:'ModComments'}','modcomments_widg_search','url_contents_{$tablestuff.Stuffid}','refresh_{$tablestuff.Stuffid}')">cancel</i>&nbsp;
						</span>
						<span class="searchIcon">
							<i id="modcomments_widg_search_icn_go" class="vteicon md-link" title="{$APP.LBL_FIND}" onclick="loadModCommentsNews(eval(jQuery('#url_contents_{$tablestuff.Stuffid}').contents().find('#max_number_of_news').val()),'url_contents_{$tablestuff.Stuffid}','refresh_{$tablestuff.Stuffid}',parent.jQuery('#modcomments_widg_search_text').val());" >search</i>
						</span>
					</div>
				{else}
					<div class="dropdown">
						{if $tablestuff.Stufftype eq "Module" || ($tablestuff.Stufftype eq "Default" &&  $tablestuff.Stufftitle neq "Key Metrics" && $tablestuff.Stufftitle neq $homepagedashboard_title && $tablestuff.Stufftitle neq "My Group Allocation" ) || $tablestuff.Stufftype eq "RSS" || $tablestuff.Stufftype eq "DashBoard"}
							<a class="vcenter" id="a_{$tablestuff.Stuffid}" href="#">
								<i data-toggle="tooltip" data-placement="bottom" title="{$MOD.LBL_MORE}" class="vteicon valign-middle">list</i>
							</a>
						{/if}
						
						<span class="vcenter" id="refresh_{$tablestuff.Stuffid}">&nbsp;&nbsp;</span>
						
						<i id="toggle_{$tablestuff.Stuffid}" class="vteicon valign-middle md-link dropdown-toggle" data-toggle="dropdown">more_vert</i>
						
						<ul class="dropdown-menu dropdown-menu-right dropdown-autoclose">
							{if ($tablestuff.Stufftype neq "Default" || $tablestuff.Stufftitle neq $keymetrics_title) && ($tablestuff.Stufftype neq "Default" || $tablestuff.Stufftitle neq $homepagedashboard_title) && ($tablestuff.Stufftype neq "Tag Cloud") && ($tablestuff.Stufftype neq "Iframe") && ($tablestuff.Stufftype neq "SDKIframe")}
								<li>
									<a href="javascript:void(0);" id="editlink" onclick="VTE.Homestuff.showEditrow({$tablestuff.Stuffid});">
										<i class="vteicon valign-middle">tune</i> {'LBL_EDIT'|@getTranslatedString}
									</a>
								</li>
							{/if}
							<li>
								{* crmv@208472 *}
								<a href="javascript:void(0);" onclick="VTE.Homestuff.loadStuff({$tablestuff.Stuffid},'{$tablestuff.Stufftype}');">
									<i class="vteicon valign-middle">refresh</i> {'Refresh'|@getTranslatedString}
								</a>
								{* crmv@208472e *}
							</li>
							{if $tablestuff.Stufftype eq "Default" || $tablestuff.Stufftype eq "Tag Cloud"}
								<li>
									<a href="javascript:void(0);" onclick="VTE.Homestuff.HideDefault({$tablestuff.Stuffid});">
										<i class="vteicon valign-middle">remove</i> {'LBL_HIDE'|@getTranslatedString}
									</a>
								</li>
							{/if}
							{if $tablestuff.Stufftype neq "Default" && $tablestuff.Stufftype neq "Tag Cloud" && $tablestuff.Stufftype neq "Iframe" && ($tablestuff.Stufftype neq "SDKIframe")}	{* crmv@25314 crmv@25466 *}
								<li>
									<a href="javascript:void(0);" id="deletelink" onclick="VTE.Homestuff.DelStuff({$tablestuff.Stuffid});">
										<i class="vteicon valign-middle">clear</i> {'LBL_HIDE'|@getTranslatedString}
									</a>
								</li>
							{/if}
						</ul>
					</div>
				{/if}
			</td>
		</tr>
	</table>
	
	<div class="{if $tablestuff.Stufftype eq 'URL'}MatrixBorderURL{else}MatrixBorder{/if}" {if $tablestuff.Stufftitle eq 'MODCOMMENTS'|getTranslatedString:'Home'}style="{if isMobile() eq true}overflow:scroll;-webkit-overflow-scrolling:touch;{/if}"{/if}>
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
	</div>
</div>

<script type="text/javascript">
	window.onresize = function(){ldelim}VTE.Homestuff.positionDivInAccord('stuff_{$tablestuff.Stuffid}','{$tablestuff.Stufftitle}','{$tablestuff.Stufftype}','{$tablestuff.Stuffsize}');{rdelim};
	VTE.Homestuff.positionDivInAccord('stuff_{$tablestuff.Stuffid}','{$tablestuff.Stufftitle}','{$tablestuff.Stufftype}','{$tablestuff.Stuffsize}');
</script>