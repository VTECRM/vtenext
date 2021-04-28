{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{assign var="FLOAT_TITLE" value="`$MOD.LBL_ADD` `$MOD.LBL_BOOKMARK`"}
{assign var="FLOAT_WIDTH" value="500px"}
{capture assign="FLOAT_CONTENT"}
<form onSubmit="SaveSite('{$PORTALID}'); return false;" > {* crmv@173029 *}
<table border="0" cellspacing="0" cellpadding="5" width="95%" align="center"> 
	<tr>
	<td class="small" >
		<table border="0" celspacing="0" cellpadding="5" width="100%" align="center" bgcolor="white">
		
		<tr>

			<td align="right" width="40%" ><b>{$MOD.LBL_BOOKMARK} {$MOD.LBL_URL} </b></td>
			<td align="left" width="60%"><input name="portalurl" id="portalurl" class="txtBox" value="{$PORTALURL}" type="text"></td> {* crmv@173029 *}
		</tr>
		<tr>
			<td align="right" width="40%"> <b>{$MOD.LBL_BOOKMARK} {$MOD.LBL_NAME} </b></td>
			<td align="left" width="60%"><input name="portalname" id="portalname" value="{$PORTALNAME}" class="txtBox" type="text"></td>
		</tr>
		</table>
	</td>
	</tr>
</table>
<table border="0" cellspacing="0" cellpadding="5" width="100%" class="layerPopupTransport">
	<tr>
	<td align="center">
			<input name="save" value=" &nbsp;{$APP.LBL_SAVE_BUTTON_LABEL}&nbsp; " class="crmbutton small save"  type="submit">&nbsp;&nbsp;
			<input name="cancel" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " class="crmbutton small cancel" onclick="hideFloatingDiv('editMySite');" type="button">
	</td>
	</tr>
</table>
</form>
{/capture}
{include file="FloatingDiv.tpl" FLOAT_ID="editMySite" FLOAT_BUTTONS=""}