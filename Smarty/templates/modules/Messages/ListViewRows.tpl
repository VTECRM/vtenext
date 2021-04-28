{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@OPER8279 crmv@192843 *}
{if $smarty.request.appendlist eq 'yes'}
	@#@#@#{$NAVIGATION}@#@#@#{$LV_ERROR}@#@#@#
{/if}
{foreach item=entity key=entity_id from=$LISTENTITY}
	<!-- crmv@7230 -->
	{assign var=color value=$entity.clv_color}
	<tr id="row_{$entity_id}"> {* class="lvtColDataHover" *}
	 <!-- DS-ED VlMe 27.3.2008 -->
	 {* <!-- KoKr bugfix add (check_object) idlist for csv export --> *}
	<td width="30px" class="listMessageRowPrefix">
		<table border="0" cellspacing="0" cellpadding="0" width=100%>
		<tr height="20px">
			<td>
			{if $entity.thread eq 0}
				<input style="display:none" type="checkbox" name="selected_id" id="{$entity_id}" value="{$entity_id}" onClick="update_selected_ids(this.checked,'{$entity_id}',this.form,true);"
				{if is_array($SELECTED_IDS_ARRAY) && count($SELECTED_IDS_ARRAY) > 0} {* crmv@167234 *}
					{if $ALL_IDS eq 1 && !in_array($entity_id,$SELECTED_IDS_ARRAY)}
						checked
					{else}
						{if ($ALL_IDS neq 1 and $SELECTED_IDS neq "" and in_array($entity_id,$SELECTED_IDS_ARRAY))}
							checked
						{/if}
					{/if}
				{else}
					{if $ALL_IDS eq 1}
						checked
					{/if}
				{/if}
				>
			{/if}
			</td>
		</tr>
		<tr valign="middle">
			<td>
				{assign var="style_flag" value="style='display:none;'"}
				{assign var="unseenThreadFlag" value=false}
				{if $entity.thread neq ''}
					{assign var="unseenThreadFlag" value=$FOCUS->checkThreadFlag('unseen',$entity_id,$entity.thread)}
				{/if}
				{if $entity.seen eq 'no' || $unseenThreadFlag eq true}
					{assign var="style_flag" value=""}
				{/if}
				<div id="flag_{$entity_id}_unseen" {$style_flag}><i class="vteicon" title="{'Seen'|@getTranslatedString:$MODULE}" style="color:#2c80c8">fiber_manual_record</i></div>
				
				{assign var="style_flag" value="style='display:none;'"}
				{assign var="flaggedThreadFlag" value=false}
				{if $entity.thread neq ''}
					{assign var="flaggedThreadFlag" value=$FOCUS->checkThreadFlag('flagged',$entity_id,$entity.thread)}
				{/if}
				{if $entity.flagged eq 'yes' || $flaggedThreadFlag eq true}
					{assign var="style_flag" value=""}
				{/if}
				<div id="flag_{$entity_id}_flagged" {$style_flag}><i class="vteicon checkko" title="{'Flagged'|@getTranslatedString:$MODULE}">flag</i></div>
										
				{assign var="style_flag_answered_forwarded" value="style='display:none;'"}
				{assign var="style_flag_answered" value="style='display:none;'"}
				{assign var="style_flag_forwarded" value="style='display:none;'"}
				{if $entity.answered eq 'yes' && $entity.forwarded eq 'yes'}
					{assign var="style_flag_answered_forwarded" value=""}
				{elseif $entity.answered eq 'yes'}
					{assign var="style_flag_answered" value=""}
				{elseif $entity.forwarded eq 'yes'}
					{assign var="style_flag_forwarded" value=""}
				{/if}
				<div id="flag_{$entity_id}_answered_forwarded" {$style_flag_answered_forwarded}>
					<i class="vteicon" title="{'Answered'|@getTranslatedString:$MODULE}">reply</i>
					<i class="vteicon" title="{'Forwarded'|@getTranslatedString:$MODULE}">forward</i>
				</div>
				<div id="flag_{$entity_id}_answered" {$style_flag_answered}><i class="vteicon" title="{'Answered'|@getTranslatedString:$MODULE}">reply</i></div>
				<div id="flag_{$entity_id}_forwarded" {$style_flag_forwarded}><i class="vteicon" title="{'Forwarded'|@getTranslatedString:$MODULE}">forward</i></div>
				
				{* crmv@59094 *}
				{assign var="style_flag_attachments" value="style='display:none;'"}
				{if $entity.has_attachments eq 'yes'}
					{assign var="style_flag_attachments" value=""}
				{/if}
				<div id="flag_{$entity_id}_attachments" {$style_flag_attachments}><i class="vteicon" title="{'LBL_FLAG_ATTACHMENTS'|@getTranslatedString:$MODULE}">attachment</i></div>
				{* crmv@59094e *}
				
				{assign var="style_flag" value="style='display:none;'"}
				{if empty($entity.ghost) && $FOCUS->haveRelations($entity_id,'','',$entity.thread)}
					{assign var="style_flag" value=""}
				{/if}
				<div id="flag_{$entity_id}_relations" {$style_flag}><i class="vteicon" title="{'LBL_FLAG_LINK'|@getTranslatedString:$MODULE}">open_in_new</i></div>
				
				{assign var="style_flag" value="style='display:none;'"}
				{if empty($entity.ghost) && $FOCUS->haveRelations($entity_id,'ModComments','',$entity.thread)}
					{assign var="style_flag" value=""}
				{/if}
				<div id="flag_{$entity_id}_talks" {$style_flag}><i class="vteicon" title="{'LBL_MODCOMMENTS_COMMUNICATIONS'|@getTranslatedString:'ModComments'}">chat</i></div>
			</td>
		</tr>
		</table>
	</td>
	 <!-- DS-END -->
	 {assign var=thread_count value=$FOCUS->getChildren($entity.thread,'',true)}
	 <td bgcolor="{$color}" class="listMessageRow" {if !empty($entity.ghost)}onClick="fetchMessage('{$entity.account}','{$entity.folder}','{$entity.xuid}');"{elseif $entity.thread eq 0}onClick="selectRecord({$entity_id});"{else}onClick="selectThread({$entity_id},{$entity.thread},'{$thread_count}','{'Messages'|getTranslatedString:'Messages'|strtolower}');"{/if}>
		<table border="0" cellspacing="0" cellpadding="0" width=100%>
		<tr>
			<td align="right" style="font-size:11px; color: gray;" colspan="2">{$entity.mdate}</td>
		</tr>
		<tr>
			<td class="listMessageFrom" colspan="2">
				{if $CURRENT_FOLDER eq $SPECIAL_FOLDERS.Sent or $CURRENT_FOLDER eq $SPECIAL_FOLDERS.Drafts or ($CURRENT_ACCOUNT eq 'all' && $CURRENT_FOLDER eq 'Sent')}
					{assign var="mto" value=$entity.mto|strip_tags}
					{assign var="mto_n" value=$entity.mto_n|strip_tags}
					{assign var="mto_f" value=$entity.mto_f|strip_tags}
					{$FOCUS->getAddressName($mto,$mto_n,$mto_f,true)}
				{else}
					{assign var="mfrom" value=$entity.mfrom|strip_tags}
					{assign var="mfrom_n" value=$entity.mfrom_n|strip_tags}
					{assign var="mfrom_f" value=$entity.mfrom_f|strip_tags}
					{$FOCUS->getAddressName($mfrom,$mfrom_n,$mfrom_f,true)}
				{/if}
			</td>
		</tr>
		<tr>
			{if $entity.thread eq 0}
				<td class="listMessageSubject" colspan="2">{$entity.subject}</td>
			{else}
				<td class="listMessageSubject" width="100%">{$entity.subject}</td>
				<td class="listMessageSubject" align="right" nowrap>
					{include file="BubbleNotification.tpl" COUNT=$thread_count}
				</td>
			{/if}
		</tr>
		{* crmv@93095 *}
		{if !empty($entity.cleaned_body)}
			<tr>
				<td class="style_Gray" colspan="2" style="word-wrap:break-word;">
					<div class="descriptionPreview" style="overflow:auto;">
						{$entity.cleaned_body}
					</div>
				</td>
			</tr>
		{/if}
		{* crmv@93095e *}
		</table>
	</td>
	</tr>
	{* <tr style="height:1px;" bgcolor="black"><td colspan="2"></td></tr> *}
	<!-- crmv@7230e -->
{foreachelse}
	{* crmv@87055 *}
	{if $smarty.request.start gt 1 and $SEARCHING eq false}
		{* se sto scrollando e sto caricando il successivo slot di messaggi non ha senso mostrare il messaggio di lista vuota *}
	{else}
		{if $SEARCHING eq true and ($smarty.request.start lt ($FOCUS->search_intervals|@count-1) || $MESSAGES_RESULTS_PREV_SEARCH gt 0)}
			{* mostro il messaggio solo all'ultimo giro di ricerca se non ho avuto risultati *}
		{elseif !empty($MSG_EMPTY_LIST)}
			<tr><td colspan="2" style="height:340px" align="center" colspan="{$smarty.foreach.listviewforeach.iteration+1}">
				<div style="width: 100%; position: relative;">	<!-- crmv@18592 -->
					<table border="0" cellpadding="5" cellspacing="0" width="100%">
						<tr>
		                	<td align="center">{$MSG_EMPTY_LIST}</td>	{* crmv@48159 *}
						</tr>
					</table>
				</div>
			</td></tr>
		{/if}
	{/if}
	{* crmv@87055e *}
{/foreach}