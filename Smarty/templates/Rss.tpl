{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script type="text/javascript" src="{"modules/Rss/Rss.js"|resourcever}"></script>

<!-- Contents -->
{include file="Buttons_List1.tpl"}

<div id="temp_alert" style="display:none"></div>

<table border=0 cellspacing=0 cellpadding=0 width=98% align=center>
	<tr>
		<td valign=top align=right width=8></td>
		<td class="showPanelBg" valign="top" width="100%" align=center >	
			<!-- RSS Reader UI Starts here-->
			<br>
			<table width="100%"  border="0" cellspacing="0" cellpadding="5" class="mailClient mailClientBg">
				<tr>
					<td align=left>
						<table width="100%"  border="0" cellspacing="0" cellpadding="0">
							<tr>
								<td width=95% align=left>
									<i class="vteicon md-lg nohover" style="vertical-align:middle">rss_feed</i>
									<a href="javascript:;" onClick="showFloatingDiv('PopupLay', this); jQuery('#rssurl').focus();" title='{$APP.LBL_ADD_RSS_FEEDS}'>{$MOD.LBL_ADD_RSS_FEED}</a>
								</td>
								<td class="componentName" nowrap></td>
							</tr>
							<tr>
								<td colspan="2">
									<table border=0 cellspacing=0 cellpadding=2 width=100%>
										<tr>
											<td width=30% valign=top>
											<!-- Feed Folders -->
												<table border=0 cellspacing=0 cellpadding=0 width=100%>
													<tr><td class="mailSubHeader" height="25"><b>{$MOD.LBL_FEED_SOURCES}</b></td></tr>
													<tr><td class="hdrNameBg" bgcolor="#fff" height=225><div id="rssfolders" style="height:100%;overflow:auto;">{$RSSFEEDS}</div></td></tr>
												</table>
											</td>
											<td width=1%>&nbsp;</td>
											<td width=69% valign=top>
											<!-- Feed Header List -->
												<table border=0 cellspacing=0 cellpadding=0 width=100%>
													<tr>
														<td>
															<div id="rssfeedscont">
																{include file='RssFeeds.tpl'}	
															</div>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>		
								<td>&nbsp;</td>
								<td>&nbsp;</td>
								<td height="5"></td>
							</tr>
							<tr>
								<td colspan="3" class="mailSubHeader" id="rsstitle">&nbsp;</td>
							</tr>
							<tr>
								<!-- RSS Display -->
								<td colspan="3" style="padding:2px">
								<iframe width="100%" height="250" frameborder="0" id="mysite" scrolling="auto" marginheight="0" marginwidth="0" style="background-color:#FFFFFF;"></iframe>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<!-- RSS Reader UI ends here -->
		</td>
		<td valign=top align=right width=8></td>			
	</tr>
</table>
	
{assign var="FLOAT_TITLE" value=$MOD.LBL_ADD_RSS_FEED}
{assign var="FLOAT_WIDTH" value="300px"}
{capture assign="FLOAT_CONTENT"}
<form onSubmit="VTE.Rss.SaveRssFeeds(); return false;">
	<table border=0 cellspacing=0 cellpadding=5 width=95% align=center> 
		<tr>
			<td>
				{* popup specific content fill in starts *}
				<table border=0 celspacing=0 cellpadding=5 width=100% align=center>
					<tr>
						<td align="right" width="25%"><b>{$MOD.LBL_FEED}</b></td>
						<td align="left" width="75%"><input type="text" id="rssurl" class="detailedViewTextBox" value="" /></td>
					</tr>
				</table>
				{* popup specific content fill in ends *}
			</td>
		</tr>
	</table>
	<table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
		<tr>
			<td align="center">
				<button type="submit" name="save" class="crmbutton save">{$APP.LBL_SAVE_BUTTON_LABEL}</button>
			</td>
		</tr>
	</table>
</form>
{/capture}

{include file="FloatingDiv.tpl" FLOAT_ID="PopupLay" FLOAT_BUTTONS=""}