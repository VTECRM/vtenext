{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@83340 *}

{if !empty($QFILTERS) && strlen($QFILTERS) > 0} {* crmv@194673 *} {* crmv@204903 *}
	<table width="100%" cellspacing="5" cellpadding="2" border="0">
		<tr>
			<td align="right" width="50%">
				{$APP.LBL_FILTER}
			</td>
			<td align="left" width="50%">
			<select name="select_qfilter" id="select_qfilter">
				{$QFILTERS}
			</select>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align="right" colspan="2">
				<button type="button" class="crmbutton save" onclick="ModuleHome.addBlock('{$MODHOMEID}', 'QuickFilter')">{$APP.LBL_SAVE_LABEL}</button>
			</td>
		</tr>
	</table>
{else}
	<center>
		<p>{$APP.LBL_NO_AVAILABLE_FILTERS}</p>
	</center>
{/if}