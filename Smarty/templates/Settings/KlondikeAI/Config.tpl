{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@215354 crmv@215597 *}

<script type="text/javascript" src="{"modules/Settings/KlondikeAI/resources/KlondikeConfig.js"|resourcever}"></script>

<style>
.klondike_config_table td {
	padding: 10px;
}
.bigbutton {
	min-width: 200px;
	min-height: 50px;
	font-size: 125%;
	font-weight: 700;
	padding: 20px;
}

</style>
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
	<tbody>
	<tr>
        <td valign="top"></td>  
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
			<div align=center>

	
				{include file="SetMenu.tpl"}
				{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><i class="vteicon md-text md-xlg">memory</i></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS}  > {$MOD.LBL_KLONDIKE_CONFIG}</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_KLONDIKE_CONFIG_DESC}</td>
				</tr>
				</table>
				
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td>
				
				<div class="container">
					<div class="row">
						<div class="col-sm-12">
						
							<table width="100%" border="0" class="klondike_config_table" >
								<tr>
									<td width="20%"></td>
									<td width="40%"></td>
									<td width="40%"></td>
								</tr>
								
								<tr>
									<td colspan="3" align="center">
										{if $ERRORMSG}
											<span class="errorString">{$ERRORMSG}</span>
										{elseif $HAS_TOKEN}
											<span style="color:green;font-weight:700">{$MOD.LBL_KLONDIKE_TOKEN_OK}</span>
										{/if}
									<td>
								</tr>
								
								<tr>
									<td colspan="3" align="center">
										
										<br>
										{if $HAS_TOKEN}
											{if !$VALID_TOKEN}
											<button class="crmbutton edit" onclick="KlondikeConfig.authorize()">{$MOD.LBL_KLONDIKE_REFRESH_TOKEN}</button>
											{/if}
											<button class="crmbutton cancel" onclick="KlondikeConfig.unlink()">{$MOD.LBL_KLONDIKE_UNLINK}</button>
										{else}
											<h4>{$MOD.LBL_KLONDIKE_LINK_DESC}</h4><br>
											<button class="crmbutton save bigbutton" onclick="KlondikeConfig.authorize()">{$MOD.LBL_KLONDIKE_LINK}</button>
										{/if}
									</td>
								</tr>
								
								{if !$HAS_TOKEN}
								<tr>
									<td colspan="3" align="center">
										<br><br>
										<h3>{$MOD.LBL_KLONDIKE_AD_REGISTER}</h3><br>
										<h4>{$MOD.LBL_KLONDIKE_AD} <b><a href="https://www.klondike.ai" target="_blank">www.klondike.ai</a></b></h4>
									<td>
								</tr>
								{/if}
								
							</table>
						
							
							
						</div>
					</div>
				</div>
					
				</td>
				</tr>
				</table>
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>
		
	</div>

	</td>
    <td valign="top"></td>
   </tr>
</tbody>
</table>

