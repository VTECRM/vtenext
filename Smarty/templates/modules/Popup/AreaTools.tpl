{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@54707 *}
<table cellpadding="5" cellspacing="0" border="0" width="70%" align="center">
	<tr>
		<td valign="top">-</td>
		<td>
			{'LBL_PROPAGATE_AREA'|getTranslatedString|sprintf:$link}
			<input type="button" value="{'LBL_PROPAGATE_AREA_BUTTON'|getTranslatedString|sprintf:$link}" class="crmButton small edit" onClick="ModuleAreaManager.propagateLayout();" >
		</td>
	</tr>
	<tr>
		<td valign="top">-</td>
		<td>
			<label for="block_area_layout">{'LBL_BLOCK_AREA_LAYOUT'|getTranslatedString}</label>&nbsp;<input type="checkbox" id="block_area_layout" onClick="ModuleAreaManager.blockLayout(this.checked);" {$BLOCK_AREA_LAYOUT} />
		</td>
	</tr>
</table>