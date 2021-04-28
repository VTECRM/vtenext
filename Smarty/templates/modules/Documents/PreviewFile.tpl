{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@62414 *}
{if $FILE_STATUS eq 1}
	{if $FILE_SUPPORTED}
		<table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
			<tr>
				<td align="center" class="rightMailMergeContent">
					<input type="button" class="crmbutton small edit" onclick="{$JS_ACTION}('{$RECORD}');" value="{$MOD.DOC_PREVIEW_BUTTON}" />
				</td>
			</tr>
		</table>
	{else}
		{$MOD.DOC_NOT_SUPP}
	{/if}
{else}
	{$MOD.DOC_NOT_ACTIVE}
{/if}