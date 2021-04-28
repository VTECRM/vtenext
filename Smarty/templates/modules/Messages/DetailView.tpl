{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@59094 crmv@175371 *}

<script type="text/javascript">
var gVTModule = '{$smarty.request.module|@vtlib_purify}';
var messageMode = '{$MESSAGE_MODE}';
</script>

<div id="lstRecordLayout" class="layerPopup crmvDiv" style="display:none;width:320px;height:300px;z-index:21;position:fixed;"></div>	{*<!-- crmv@18592 -->*}

<div class="container-fluid messages-detailview-container">
	<div class="row">
		<div class="col-sm-12">
			<div class="messages-detailview-container-inner">
				<form action="index.php" method="post" name="DetailView" id="form">
				{include file='DetailViewHidden.tpl'}
				
				{if $HEADER_BLOCK neq ''}
					{include file="modules/Messages/DetailViewHeader.tpl"}
				{/if}
				
				{* crmv@68357 *}
				{if $ICALS && count($ICALS) > 0}
					{include file="modules/Messages/IcalMainHeader.tpl"}
					{foreach item=ICAL key=ICALID from=$ICALS}
						{include file="modules/Messages/IcalDisplay.tpl"}
					{/foreach}
				{/if}
				{* crmv@68357e *}
				
				{if $DESCRIPTION neq '' || $BUTTON_DOWNLOAD_DESCRIPTION}
					<div class="row">
						<div class="col-sm-12">
							{if $DESCRIPTION neq ''}
								<div id="DetailViewContentDescr" class="messages-detailview" style="word-wrap:break-word;">
									<div class="messages-detailview-content">
										{$DESCRIPTION}
									</div>
								</div>
							{elseif $BUTTON_DOWNLOAD_DESCRIPTION}
								<div align="center" style="height:50px;"><a href="javascript:;" onClick="fetchBody({$ID});"><br />{'LBL_DOWNLOAD_BODY_MESSAGE'|getTranslatedString:'Messages'}</a></div>
							{/if}
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							{if !empty($INLINE_ATTACHMENTS)}
								{foreach key=contentid item=attach from=$INLINE_ATTACHMENTS}
									<div class="row">
										<div class="col-sm-12">
											<img border="0" src="index.php?module=Messages&action=MessagesAjax&file=Download&record={$ID}&contentid={$contentid}&mode=inline" style="max-width:400px">	{* crmv@65648 crmv@80250 *}
										</div>
									</div>
								{/foreach}
							{/if}
						</div>
					</div>
				{/if}
				
				{if !empty($ATTACHMENTS)}
					<div style="padding: 5px; border-bottom:1px solid #E0E0E0;">
					<table width="100%" cellspacing="0" cellpadding="0">
					<tr height="30px" valign="top">
						<td colspan="2" style="color: gray;">
							{* crmv@187622 *}
							{assign var=ATTACH_COUNT value=0}
							{foreach item=attach from=$ATTACHMENTS}
								{if $attach.action_download}
									{capture assign=ATTACH_COUNT}{$ATTACH_COUNT+1}{/capture}
								{/if}
							{/foreach}
							{if $ATTACH_COUNT eq 1}
								{assign var="ATTACH_LABEL" value=$MOD.LBL_ATTACHMENT}
							{else}
								{assign var="ATTACH_LABEL" value=$MOD.LBL_ATTACHMENTS}							
							{/if}
							<i class="vteicon valign-middle">attach_file</i>&nbsp;{if $ATTACH_COUNT gt 0}{$ATTACH_COUNT} {/if}{$ATTACH_LABEL} {* crmv@192843 *}
							{* crmv@62340 *}
							{if $ATTACH_COUNT gt 1} 
								<a href="index.php?module=Messages&action=MessagesAjax&file=DownloadAttachments&record={$ID}" ><i class="vteicon md-text" title="{$MOD.LBL_DOWNLOAD_ALL}" style="vertical-align:middle;">file_download</i> {$MOD.LBL_DOWNLOAD_ALL}</a>
							{/if}
							{* crmv@62340e *}
							{* crmv@187622e *}
						</td>
					</tr>
					{foreach item=attach from=$ATTACHMENTS}
						<tr class="lvtColData" onMouseOver="this.className='lvtColDataHover'" onMouseOut="this.className='lvtColData'">
							<td nowrap style="padding-right:10px;">
								{if $attach.action_download}
									<a id="att{$attach.contentid}" href="{$attach.link}" {if $attach.target neq ''}target="{$attach.target}{/if} "><i class="vteicon md-text" title="{$MOD.LBL_DOWNLOAD}">file_download</i></a>{* crmv@204525 *}
									<script type="text/javascript">checkAttachment('{$attach.link}', '{$attach.name}', 'att{$attach.contentid}', 'detailview')</script> {* crmv@204525 *}
								{/if}
								{* crmv@112756 *}
								{if $attach.action_download_tnef}
									<a href="{$attach.link}" {if $attach.target neq ''}target="{$attach.target}{/if}"><i class="vteicon2 md-text fa-file-archive-o" title="{$MOD.LBL_DOWNLOAD_TNEF}" style="padding-bottom:2px; padding-right:2px;"></i></a>
								{/if}
								{* crmv@112756e *}
								{if $attach.action_save}
									{if empty($attach.document)}
										<a href="javascript:;" onClick="saveDocument({$ID},'{$attach.contentid}','','','yes');"><i class="vteicon md-text" title="{$MOD.LBL_SAVE_IN_DOCUMENTS_ACTION}">folder</i></a>
									{else}
										<a href="javascript:;" onClick="preView('Documents','{$attach.document}');"><i class="vteicon md-text" title="{$MOD.LBL_DOCUMENT_ALREADY_CREATED}">folder_special</i></a> {* crmv@62414 *}
									{/if}
								{/if}
								{if $attach.action_link}
									<a href="javascript:;" onClick="saveDocumentAndLink({$ID},'{$attach.contentid}');"><i class="vteicon md-text" title="{$MOD.LBL_SAVE_AND_LINK_ACTION}">open_in_new</i></a>
								{/if}
								{* crmv@62414 *}
								{if $attach.action_view}
									<a href="javascript:;" onClick="{$attach.action_view_JSfunction}({$ID},'{$attach.contentid}');"><i class="vteicon md-text" title="{$MOD[$attach.action_view_label]}">remove_red_eye</i></a>
								{/if}
								{* crmv@62414e *}
							</td>
							<td width="100%">
								<b>{$attach.name}</b>
							</td>
						</tr>
					{/foreach}
					</table>
					</div>
				{/if}
				
				{* crmv@181170 *}
				{* vtlib Customization: Embed DetailViewWidget block:// type if any *}
				{if $CUSTOM_LINKS && !empty($CUSTOM_LINKS.DETAILVIEWWIDGET)}
					<table border=0 cellspacing=0 cellpadding=5 width=100% id="DetailViewWidgets">
						{foreach item=CUSTOM_LINK_DETAILVIEWWIDGET from=$CUSTOM_LINKS.DETAILVIEWWIDGET}
							{if !$CUSTOM_LINK_DETAILVIEWWIDGET->validateDisplayWidget($ID)}
								{continue}
							{/if}
							<tr>
								<td style="padding:5px;">
									{$CUSTOM_LINK_DETAILVIEWWIDGET->displayWidgetContent($ID)}
								</td>
							</tr>
						{/foreach}
					</table>
				{/if}
				{* END *}
				{* crmv@181170e *}
				
				</form>
			</div>
		</div>
	</div>
</div>

<form name="SendMail" onsubmit="VteJS_DialogBox.block();"><div id="sendmail_cont" style="z-index:100001;position:absolute;"></div></form>
<form name="SendFax" onsubmit="VteJS_DialogBox.block();"><div id="sendfax_cont" style="z-index:100001;position:absolute;width:300px;"></div></form>
<!-- crmv@16703 -->
<form name="SendSms" id="SendSms" onsubmit="VteJS_DialogBox.block();" method="POST" action="index.php"><div id="sendsms_cont" style="z-index:100001;position:absolute;width:300px;"></div></form>
<!-- crmv@16703e -->

<div id="Button_List_Detail_Container" style="display:none;">
	{include file='modules/Messages/DetailViewButtons.tpl'}
</div>
<script language="javascript">
jQuery('#Button_List_Detail').html(jQuery('#Button_List_Detail_Container').html());
</script>