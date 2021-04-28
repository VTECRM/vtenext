{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
<script language="javascript" type="text/javascript" src="modules/Home/Homestuff.js"></script>

{if $ALLOW_CHARTS eq 'yes'}
	<script language="javascript" type="text/javascript" src="modules/Charts/Charts.js"></script>
{/if}

<input id="homeLayout" type="hidden" value="{$LAYOUT}">

{include file="Home/HomeButtons.tpl"}

<div id="vtbusy_homeinfo" style="display:none;">
	{include file="LoadingIndicator.tpl" LIOLDMODE=true}
</div>

<table width="97%" class="" cellpadding="0" cellspacing="0" border="0" align="center" valign="top">
	<tr><td>{include file="Buttons_List_Contestual.tpl"}</td></tr>
	<tr>
		<td width="100%" align="center" valign="top" height="350">
			<div id="MainMatrix" class="topMarginHomepage" style="padding:0px;width:100%">
				{foreach item=tablestuff from=$HOMEFRAME name="homeframe"}
					{* create divs for each widget - the contents will be loaded dynamically from javascript *}
					{include file="Home/MainHomeBlock.tpl"}
					{* load contents for the widget *}
					<script type="text/javascript">
						{* crmv@208472 *}
						{if $tablestuff.Stufftype eq 'DashBoard'}
							VTE.Homestuff.loadStuff({$tablestuff.Stuffid},'{$tablestuff.Stufftype}');
						{/if}
						{* crmv@208472e *}
					</script>
				{/foreach}
			</div>
		</td>
	</tr>
</table>

<script type="text/javascript">
	var Vt_homePageWidgetInfoList = [
		{foreach item=tablestuff key=index from=$HOMEFRAME_RESTRICTED name="homeframe"}
			{ldelim}
				'widgetId': {$tablestuff.Stuffid},
				'widgetType': '{$tablestuff.Stufftype}'
			{rdelim}
			{if $index+1 < $HOMEFRAME_RESTRICTED|@count},{/if}
		{/foreach}
	];
	
	VTE.Homestuff.loadAllWidgets(Vt_homePageWidgetInfoList, 1);
	VTE.Homestuff.initHomePage();

	{* crmv@146652 *}
	{if $OPEN_MYNOTES_POPUP > 0}
		jQuery(document).ready(function(){ldelim}
			openPopup('index.php?module=MyNotes&action=SimpleView&record={$OPEN_MYNOTES_POPUP}');
		{rdelim});
	{/if}
	{* crmv@146652e *}
</script>