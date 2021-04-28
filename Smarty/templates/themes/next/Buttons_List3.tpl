{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<div id="Buttons_List_3">
	<table id="bl3" border=0 cellspacing=0 cellpadding=2 width=100% class="small">
		<tr>
			<td>
                {include file="Buttons_List_Contestual.tpl"}
			</td>
		</tr>
	</table>
</div>

<script type="text/javascript">
	calculateButtonsList3();
	{if $smarty.request.query eq true && $smarty.request.searchtype eq 'BasicSearch' && !empty($smarty.request.search_text)}
		clearText(jQuery('#basic_search_text'));
		jQuery('#basic_search_text').data('restored', false); // crmv@104119
		jQuery('#basic_search_text').val('{$smarty.request.search_text}');
		basic_search_submitted = true;
	{/if}
</script>