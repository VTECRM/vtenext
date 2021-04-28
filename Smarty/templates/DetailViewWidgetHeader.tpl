{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<td class="dvInnerHeader" style="font-weight:bold;">
			{assign var=WIDGET_TITLE value=$CUSTOM_LINK_DETAILVIEWWIDGET|vtlib_widget_title}
			{if empty($WIDGET_TITLE)}
				{assign var=WIDGET_TITLE value=$CUSTOM_LINK_DETAILVIEWWIDGET->linklabel|getTranslatedString:$MODULE}
			{/if}
			{$WIDGET_TITLE}
		</td>
	</tr>
</table>