{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<!-- BEGIN: main -->
<div id="roleLay" style="z-index:12;display:block;width:400px;" class="crmvDiv">
	<div class="closebutton" onclick="hideFloatingDiv('sendmail_cont');"></div>
	<table border=0 cellspacing=0 cellpadding=10 width=100% class="level3Bg">
		<tr>
			<td width="100%" id="sendmail_cont_Handle" style="font-weight:bold;">{$MOD.SELECT_EMAIL}
				{if $ONE_RECORD neq 'true'}
					({$MOD.LBL_MULTIPLE} {$APP[$FROM_MODULE]})
				{/if}
				&nbsp;
			</td>
		</tr>
	</table>
	<table border=0 cellspacing=0 cellpadding=5 width=95% align=center>
		<tr><td class="small">
			<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
				<tr>
					<td align="left">
						{if $ONE_RECORD eq 'true'}
							<b>{$ENTITY_NAME}</b> {$MOD.LBL_MAILSELECT_INFO}.<br><br>
						{else}
							{$MOD.LBL_MAILSELECT_INFO1} {$APP[$FROM_MODULE]}.{$MOD.LBL_MAILSELECT_INFO2}<br><br>
						{/if}
						<div style="height:120px;overflow-y:auto;overflow-x:hidden;" align="center">
							<table border="0" cellpadding="5" cellspacing="0" width="90%">
								{foreach name=emailids key=fieldid item=elements from=$MAILINFO}
								<tr>
									{if $smarty.foreach.emailids.iteration eq 1}	
										<td align="center"><input type="checkbox" checked value="{$fieldid}" name="semail" /></td>
									{else}
										<td align="center"><input type="checkbox" value="{$fieldid}" name="semail" /></td>
									{/if}
									{if $PERMIT eq '0'}
										{if $ONE_RECORD eq 'true'}	
											<td align="left"><b>{$elements.0}</b><br>{$MAILDATA[$smarty.foreach.emailids.index]}</td>
										{else}
											<td align="left"><b>{$elements.0}</b></td>
										{/if}
									{else}
										<td align="left"><b>{$elements.0}</b><br>{$MAILDATA[$smarty.foreach.emailids.index]}</td>
									{/if}
								</tr>
								{/foreach}
							</table>
						</div>
					</td>	
				</tr>
			</table>
		</td></tr>
	</table>
	<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
		<tr><td align=center class="small">
			<input type="button" name="{$APP.LBL_SELECT_BUTTON_LABEL}" value=" {$APP.LBL_SELECT_BUTTON_LABEL} " class="crmbutton small save" onClick="validate_sendmail('{$IDLIST}','{$FROM_MODULE}');"/>&nbsp;&nbsp;
			<input type="button" name="{$APP.LBL_CANCEL_BUTTON_LABEL}" value=" {$APP.LBL_CANCEL_BUTTON_LABEL} " class="crmbutton small cancel" onclick="hideFloatingDiv('sendmail_cont');" />
		</td></tr>
	</table>
</div>