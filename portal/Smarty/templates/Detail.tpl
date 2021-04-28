{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@128933 crmv@173271 *}

{* TODO: remove this shit *}
<script type="text/javascript" src="js/raty/lib/jquery.raty.js"></script> <!-- Star -->
<script type="text/javascript" src="js/raty/lib/pregis_script_raty.js"></script>

{if !empty($MODULE_JS)}
	{foreach item=JS from=$MODULE_JS}
	<script type="text/javascript" src="{$JS}"></script> {* crmv@160733 *}
	{/foreach}
{/if}

<div class="row" style="margin-top: 5px; margin-bottom: 30px;">
	<div class="col-sm-6 text-left">
		<input align="left" class="btn btn-default" type="button"
			value="{'LBL_BACK_BUTTON'|getTranslatedString}"
			onclick="window.history.back();" />
	</div>
	<div class="col-sm-6 text-right">
		{* crmv@173271 *}
		{if $PERMISSION && $PERMISSION.perm_write}
			
				<input align="right" class="btn btn-primary" type="button"
					value="{'LBL_EDIT'|getTranslatedString}"
					{* for my contact, go to my settings page *}
					{if $MODULE == 'Contacts' && $ID == $CUSTOMERID}
					onclick="location.href='index.php?module={$MODULE}&amp;action=index&amp;id={$ID}&amp;profile=yes&amp;update=yes';"
					{else}
					onclick="location.href='index.php?module={$MODULE}&amp;action=Edit&amp;id={$ID}';"
					{/if}
				/>
		{/if}
		{if $SHOW_SOLVED_BUTTON}
			<button class="btn btn-primary" name="srch" type="button" value="{'LBL_CLOSE_TICKET'|getTranslatedString}" onClick="location.href='index.php?module={$MODULE}&amp;action=index&amp;fun=close_ticket&amp;ticketid={$ID}'">{'LBL_CLOSE_TICKET'|getTranslatedString}</button>
		{/if}
	</div>
	{* crmv@173271e *}
</div>

{if $MODULE == 'Contacts' && $CONTACTPROFILE eq 'yes'}
	{include file="EditProfileBox.tpl"}
{/if}

{if $SHOW_SOLVED_BANNER}
	<div class="alert alert-success alert-dismissable">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		{'Closed'|getTranslatedString}
	</div>
{/if}

{* crmv@90004 *}
<div class="table row fieldrow">
	{foreach from=$FIELDLIST item=LIST key=BLOCK}
		{if $DISPLAY_COLUMNS eq 1}
		<div class="col-md-12 col-xs-12" style="border: 0px">
		{else}
		<div class="col-md-6 col-xs-12" style="border: 0px">
		{/if}
			<h3 class="value" style="padding:5px 0px">
				<small>{$LIST.fieldlabel|getTranslatedString}</small>
				{if $LIST.fieldname eq 'filename'}
					<i class="material-icons-download icon_file_download"></i>
				{elseif $LIST.fieldname eq 'valutation_support'}
					<input id="valuestars" type="hidden" value="{$VALUE.1}">
					<div id="stars"></div>
				{elseif $LIST.fieldname eq 'status'}
					<input type="hidden" value="{$VALUE.1}" id="status">
				{/if}
			</h3>
			<div class="linerow">
				<h3>&nbsp;{$LIST.fieldvalue|getTranslatedString}</h3>
			</div>
		</div>
	{/foreach}
</div>

{* crmv@90004e *}

{* useless spacer *}
<table class="col-md-12">
	<tr><td class="col-md-6"></td></tr>
</table>

{* crmv@189270 *}
{if !empty($OTHERBLOCKS)}
	<div class="panel panel-default">
	{foreach from=$OTHERBLOCKS key=keyotherblocks item=itemotherblocks}
		{if $itemotherblocks eq 'LBL_NOT_AVAILABLE' || $itemotherblocks eq 'MODULE_INACTIVE'}
			{* do nothing *}
		{else}
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 panel-heading ">
				{foreach from=$itemotherblocks.HEADER item=collotherblocks }
					<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">{$collotherblocks}</div>
				{/foreach}
			</div>
			<div class="col-lg-12">
				{foreach from=$itemotherblocks.ENTRIES item=entriesotherblocks }
					{if $entriesotherblocks neq 'LBL_NOT_AVAILABLE'}
					<div class="row">
					{foreach from=$entriesotherblocks item=entryotherblock }
						<div class="col-lg-2 col-md-2 col-sm-2 col-xs-2">{$entryotherblock}</div>
					{/foreach}	
					</div>
					{/if}
				{/foreach}
			</div>
		{/if}
	{/foreach}
</div>
{/if}
{* crmv@189270e *}

{if $CAN_ADD_COMMENTS}
	{include file="AddCommentBox.tpl"}
{/if}

{if $CAN_SEE_COMMENTS}
	{include file="CommentsBox.tpl"}
{/if}

{if $VIEW_ATTACHMENTS}
	{include file="ListAttachmentsBox.tpl"}
{/if}

{if $UPLOAD_ATTACHMENTS}
	{include file="AttachBox.tpl"}
{/if}

{* TODO: get rid of this crap! *}
{literal}
<script type="text/javascript">
	jQuery(document).ready(function() {
		// Closes Open comments
		jQuery("#comments-close").click(function(e) {
			e.preventDefault();
			jQuery("#panel-comments").toggleClass("active");
		});

		// Stars crmv@80441
		if (jQuery('#valuestars').length > 0) {
			var punteggio = document.getElementById("valuestars").value;
			var status = document.getElementById("status").value;
			
			if (punteggio == 0.00 && (status == 'Closed' || status == 'Chiuso' || status == 'Richiesta risolta')) {
				readOnlyValue = false;
			}else{
				readOnlyValue = true;
			}
			
			jQuery('#stars').raty({
				path: 'js/raty/lib/img/',
				score: punteggio,
				readOnly: readOnlyValue,
			});
			
			if (punteggio == 0.00 && (status == 'Closed' || status == 'Chiuso' || status == 'Richiesta risolta')) {
				var ticketid = document.getElementById("ticketid").value; 
				jQuery('#stars').raty({
					path: 'js/raty/lib/img',
					click: function(score, evt) {
						var res = getFile('index.php?mode=saveTicketStars&valutation_support='+score+'&ticketid='+ticketid);
						jQuery('#stars').raty({
							path: 'js/raty/lib/img/',
							readOnly: true,
						});
					}
				});
			}
		}
		// crmv@80441e
	});
</script>
{/literal}