{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<TABLE width="95%" border=0 cellPadding=0 cellSpacing=1 class="formOuterBorder">
   <tr>
	<td  colspan="5" class="formSecHeader">{$MOD.LBL_TICKET_CUMULATIVE_STATISTICS}</td>
   </tr>
   <tr>
	<td class="dataLabel" width="15%" noWrap><div align="left"><b> {$MOD.LBL_CASE_TOPIC}</div></b></td>
	<TD  class="dataLabel" width="15%" noWrap ><div align="left"><b>{$MOD.LBL_TICKET}</b></div></TD>
        <TD  class="dataLabel" width="20%" noWrap ><div align="left"><b>{$MOD.LBL_OPEN}</b></div></TD>
        <TD  class="dataLabel" width="20%" noWrap ><div align="left"><b>{$MOD.LBL_CLOSED}</b></div></TD>
        <TD  class="dataLabel" width="25%" noWrap ><div align="left"><b>{$MOD.LBL_TOTAL}</b></div></TD>
   </tr>
   <tr>
	<td class="dataLabel" width="10%" noWrap><div align="left">{$MOD.LBL_ALL}</div></td>
        <TD  class="dataLabel" width="10%" noWrap ><div align="left">{$MOD.LBL_ALL}</div></TD>
        <TD  width="25%" noWrap ><div align="left">{$ALLOPEN}</div></TD>
        <TD  width="25%" noWrap ><div align="left">{$ALLCLOSED}</div></TD>
        <TD  width="25%" noWrap ><div align="left">{$ALLTOTAL}</div></TD>
   </tr>
	{$PRIORITIES}
	{$CATEGORIES}
	{$USERS}
</TABLE>
