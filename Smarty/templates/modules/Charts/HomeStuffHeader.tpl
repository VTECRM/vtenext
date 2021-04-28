{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@82770 *}

<div class="hide_tab" id="editRowmodrss_{$HOME_STUFFID}">
	<table width="100%" border="0" cellpadding="0" cellspacing="0" valign="top">
		<tr>
			<td class="homePageMatrixHdr text-left">
				{$MOD.LBL_SIZE}&nbsp;
				<select id="selChartHomeSize_{$HOME_STUFFID}" class="detailedViewTextBox input-inline">
					<option value="1" {if $HOME_STUFFSIZE eq 1}selected{/if}>1</option>
					<option value="2" {if $HOME_STUFFSIZE eq 2}selected{/if}>2</option>
					<option value="3" {if $HOME_STUFFSIZE eq 3}selected{/if}>3</option>
					<option value="4" {if $HOME_STUFFSIZE eq 4}selected{/if}>4</option>
				</select>
			</td>
			<td class="homePageMatrixHdr text-right text-nowrap" width="40%">
				<button type="button" name="save" class="crmbutton save" onclick="VTE.Homestuff.saveHomeChart('selChartHomeSize_{$HOME_STUFFID}')">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
				<button type="button" name="cancel" class="crmbutton cancel" onclick="VTE.Homestuff.cancelEntries('editRowmodrss_{$HOME_STUFFID}')">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
		</tr>
	</table>
</div> 