{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<div style="padding: 5px; ">
<table border=0 cellspacing=0 cellpadding=0 width="100%" class="small">
<tr>
	<td colspan="2" align="left" class="listMessageFrom">{$HEADER_BLOCK.subject.value}</td>
</tr>
<tr>
	<td align="left" style="color: gray;">{$HEADER_BLOCK.mdate.value}</td>
	{if $MESSAGE_MODE neq 'Print'} {* crmv@107654 *}
		<td align="right">
			<a href="javascript:;" onClick="showHeaderDetails('large');" id="header_detail_large_link">{$MOD.LBL_HEADER_SHOW_DETAIL}</a>
			<a href="javascript:;" onClick="showHeaderDetails('small');" id="header_detail_small_link" style="display:none;">{$MOD.LBL_HEADER_HIDE_DETAIL}</a>
			{* crmv@42801 *}
			<span class="header_detail_options">-</span>
			<a href="javascript:;" onClick="printPreview({$ID});" class="header_detail_options" title="{$APP.LNK_PRINT}"><i class="vteicon md-sm md-text">print</i></a>
			<a href="javascript:;" onClick="downloadMessage({$ID});" class="header_detail_options" title="{'LBL_DOWNLOAD'|getTranslatedString:'Settings'}"><i class="vteicon md-sm md-text">file_download</i></a>
			{* crmv@42801e *}
			{* crmv@44775 *}
			{if $MESSAGE_MODE neq 'Detach'}
				<a href="javascript:;" onClick="detachMessage({$ID});" class="header_detail_options" title="{'LBL_DETACH_MESSAGE'|getTranslatedString:'Messages'}"><i class="vteicon md-sm md-text">open_in_new</i></a>
			{/if}
			{* crmv@44775e *}
		</td>
	{/if} {* crmv@107654 *}
</tr>
{* crmv@64383 crmv@70254 crmv@105048 *}
{if !$DISABLE_TRANSLATE}
<tr>
	<td colspan="2" align="center"><a href="https://translate.google.it/#auto/{$LANGUAGE}/{$TRANS_DESCRIPTION|urlencode}" target="_blank">{$MOD.TRANSLATE_MESSAGE}</a></td>
</tr>
{/if}
{* crmv@64383e crmv@70254e crmv@105048e *}
</table>
</div>

<div id="header_detail_small" style="padding: 5px; ">
{if $FOLDER eq $SPECIAL_FOLDERS.Sent or $FOLDER eq $SPECIAL_FOLDERS.Drafts}
	{assign var="ADDRESS_IMAGE" value=$FOCUS->getAddressImage('addrecipient',$HEADER_BLOCK.mto.value,$BUSINESS_CARD.0)}
	<table border=0 cellspacing=0 cellpadding=0 width="100%" class="small lvtBg">
	<tr valign="top">
		<td rowspan="2" style="width:32px;padding-right:10px;">{$ADDRESS_IMAGE}</td>
		<td>
			<table border=0 cellspacing=0 cellpadding=0 width="100%">
			<tr>
				<td>
					{$FOCUS->getAddressName($HEADER_BLOCK.mfrom.value,$HEADER_BLOCK.mfrom_n.value,$HEADER_BLOCK.mfrom_f.value)|htmlentities} {* crmv@196013 *}
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<span style="color: gray;">{$HEADER_BLOCK.mto_n.label}:</span>
					{foreach name=BUF item=BUC from=$BUSINESS_CARD}
						{if $smarty.foreach.BUF.iteration > 1} ,{/if}
						{if $BUC.name neq ''}
							<b>
							{if $BUC.module_permitted}
								<a href="javascript:;" onClick="preView('{$BUC.module}','{$BUC.id}');">{$BUC.name}</a>
								{* crmv@69922 *}
								{if $BUC.module neq 'Users'}
									<input type="hidden" name="buc_relcrmid" id="buc_relcrmid_{$smarty.foreach.BUF.iteration}" value="{$BUC.id}" />
								{/if}
								{* crmv@69922e *}
							{else}
								{$BUC.name}
							{/if}
							</b>
						{else}
							{$BUC.email}
						{/if}
					{foreachelse}
						{$FOCUS->getAddressName($HEADER_BLOCK.mto.value,$HEADER_BLOCK.mto_n.value,$HEADER_BLOCK.mto_f.value)|htmlentities} {* crmv@196013 *}
					{/foreach}
					{if $HEADER_BLOCK.mcc.value neq ''}
						, <span style="color: gray;">{$MOD.Cc}:</span> {$FOCUS->getAddressName($HEADER_BLOCK.mcc.value,$HEADER_BLOCK.mcc_n.value,$HEADER_BLOCK.mcc_f.value)|htmlentities} {* crmv@196013 *}
					{/if}
				</td>
			</tr>
			</table>
		</td>
		<td width="200px">
			{include file="modules/Messages/BusinessCard.tpl" BUSINESS_CARD=$BUSINESS_CARD.0}
		</td>
	</tr>
	</table>
{else}
	{assign var="ADDRESS_IMAGE" value=$FOCUS->getAddressImage('addsender',$HEADER_BLOCK.mfrom.value,$BUSINESS_CARD)}
	<table border=0 cellspacing=0 cellpadding=0 width="100%" class="small lvtBg">
	<tr valign="top">
		<td rowspan="2" style="width:32px;padding-right:10px;">{$ADDRESS_IMAGE}</td>
		<td>
			<table border=0 cellspacing=0 cellpadding=0 width="100%">
			<tr>
				<td><b>
					{if $BUSINESS_CARD.name neq ''}
						{if $BUSINESS_CARD.module_permitted}
							<a href="javascript:;" onClick="preView('{$BUSINESS_CARD.module}','{$BUSINESS_CARD.id}');">{$BUSINESS_CARD.name}</a>
							{* crmv@69922 *}
							{if $BUSINESS_CARD.module neq 'Users'}
								<input type="hidden" name="buc_relcrmid" id="buc_relcrmid_0" value="{$BUSINESS_CARD.id}" />
							{/if}
							{* crmv@69922e *}
						{else}
							{$BUSINESS_CARD.name}
						{/if}
					{else}
						{$FOCUS->getAddressName($HEADER_BLOCK.mfrom.value,$HEADER_BLOCK.mfrom_n.value,$HEADER_BLOCK.mfrom_f.value)|htmlentities} {* crmv@196013 *}
					{/if}
				</b></td>
			</tr>
			<tr>
				<td colspan="2">
					<span style="color: gray;">{$HEADER_BLOCK.mto_n.label}:</span> {$FOCUS->getAddressName($HEADER_BLOCK.mto.value,$HEADER_BLOCK.mto_n.value,$HEADER_BLOCK.mto_f.value)|htmlentities} {* crmv@196013 *}
					{if $HEADER_BLOCK.mcc.value neq ''}
						, <span style="color: gray;">{$MOD.Cc}:</span> {$FOCUS->getAddressName($HEADER_BLOCK.mcc.value,$HEADER_BLOCK.mcc_n.value,$HEADER_BLOCK.mcc_f.value)|htmlentities} {* crmv@196013 *}
					{/if}
				</td>
			</tr>
			</table>
		</td>
		<td width="200px">
			{include file="modules/Messages/BusinessCard.tpl"}
		</td>
	</tr>
	</table>
{/if}
</div>

<div id="header_detail_large" style="padding: 5px; display:none;">
<table border=0 cellspacing=0 cellpadding=0 width="100%" class="small lvtBg">
<tr valign="top">
	<td rowspan="11" style="width:32px;padding-right:10px;">{$ADDRESS_IMAGE}</td>
	<td><span style="color: gray;">{$HEADER_BLOCK.mfrom_f.label}</span>: {$HEADER_BLOCK.mfrom_f.value|htmlentities}</td>
</tr>
<tr>
	<td><span style="color: gray;">{$HEADER_BLOCK.mto_f.label}:</span> {$HEADER_BLOCK.mto_f.value|htmlentities}</td>
</tr>
{if $HEADER_BLOCK.mcc.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.mcc_f.label}:</span> {$HEADER_BLOCK.mcc_f.value|htmlentities}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.mbcc.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.mbcc_f.label}:</span> {$HEADER_BLOCK.mbcc_f.value|htmlentities}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.mreplyto.value neq '' && $HEADER_BLOCK.mreplyto.value neq $HEADER_BLOCK.mfrom.value}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.mreplyto_f.label}:</span> {$HEADER_BLOCK.mreplyto_f.value|htmlentities}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.xuid.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.xuid.label}:</span> {$HEADER_BLOCK.xuid.value}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.messageid.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.messageid.label}:</span> {$HEADER_BLOCK.messageid.value|htmlentities}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.in_reply_to.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.in_reply_to.label}:</span> {$HEADER_BLOCK.in_reply_to.value|htmlentities}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.mreferences.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.mreferences.label}:</span> {$HEADER_BLOCK.mreferences.value|htmlentities}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.thread_index.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.thread_index.label}:</span> {$HEADER_BLOCK.thread_index.value|htmlentities}</td>
	</tr>
{/if}
{if $HEADER_BLOCK.xmailer.value neq ''}
	<tr>
		<td><span style="color: gray;">{$HEADER_BLOCK.xmailer.label}:</span> {$HEADER_BLOCK.xmailer.value}</td>
	</tr>
{/if}
{if $ID neq ''}
	<tr>
		<td><span style="color: gray;">{'Record'|getTranslatedString:'Messages'}:</span> {$ID}</td>	{* crmv@59091 *}
	</tr>
{/if}
</table>
</div>