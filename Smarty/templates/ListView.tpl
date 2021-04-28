{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<!-- module header -->
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/search.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/Merge.js"|resourcever}"></script> {* crmv@8719 *}
<script language="JavaScript" type="text/javascript" src="{"modules/`$MODULE`/`$MODULE`.js"|resourcever}"></script>

{* crmv@160778 *}
{if $MODULE eq 'Calendar'}
	<script language="JavaScript" type="text/javascript" src="{"include/js/ModuleHome.js"|resourcever}"></script>
{/if}
{* crmv@160778e *}

{* crmv@43835 *}
<script type="text/javascript">
var gridsearch;
</script>
{* crmv@43835e *}

<input type="hidden" id="user_dateformat" name="user_dateformat" value="{$DATEFORMAT}">
<textarea name="select_ids" id="select_ids" style="display:none;"></textarea>

{include file='Buttons_List.tpl'}

<!-- Contents -->
<table border=0 cellspacing=0 cellpadding=0 width=100% align=center>	{*<!-- crmv@18592 -->*}
	<tr>
        <td valign=top></td>
		<td class="showPanelBg" valign="top" width=100% style="padding:0px;">
		
			{include file="Buttons_List3.tpl"}	{* crmv@102334 *}
		
			{include file="AdvancedSearch.tpl"}
			{* crmv@159559 *}
			<script type="text/javascript">
			populateAdvancedSearchForm(advanced_search_params);
			</script>
			{* crmv@159559e *}
		
			{* crmv@8719 *}
			<div id="mergeDup" class="menuSeparation" style="z-index:1;display:none;position:relative;padding:10px;">
				{include file="MergeColumns.tpl"}
			</div>
			{* crmv@8719e *}

			<!-- PUBLIC CONTENTS STARTS-->
		    <div id="ListViewContents" class="small" style="width:100%;position:relative;">
		    	{include file="ListViewEntries.tpl" MOD=$MOD} {* crmv@vte10usersFix *}
		    </div>
		</td>
		<td valign=top></td>
	</tr>
</table>

<form name="SendMail"><div id="sendmail_cont" style="z-index:100001;position:absolute;"></div></form>
<form name="SendFax"><div id="sendfax_cont"></div></form>
<form name="SendSms" id="SendSms" method="POST" action="index.php"><div id="sendsms_cont"></div></form>	{* crmv@16703 *}