{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{assign var="FLOAT_TITLE" value=$PDF.LBL_PDF_ACTIONS}
{assign var="FLOAT_WIDTH" value="350px"}
{capture assign="FLOAT_CONTENT"}
<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
    <tr><td class="small">
        <table border=0 cellspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
        {$POPUP_HTML}
        </table>
    </td></tr>
</table>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="PDFListViewDivCont" FLOAT_BUTTONS=""}