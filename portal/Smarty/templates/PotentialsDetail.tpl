{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<!-- <table> -->
<!-- 	<tr> -->
<!-- 		<td> -->
<!--			<input class="crmbutton small cancel" type="button" value="{'LBL_BACK_BUTTON'|getTranslatedString}" onclick="window.history.back();"/>-->
<!-- 		</td> -->
<!-- 	</tr> -->
<!-- </table> -->
{*$FIELDLIST*}
<div class="row" style="margin-top: 5px; margin-bottom: 30px;">
	<div class="col-md-2" style="float: left">
		<input align="left" class="btn btn-default" type="button"
			value="{'LBL_BACK_BUTTON'|getTranslatedString}"
			onclick="location.href='?module=Potentials&action=index&onlymine=true'" />
	</div>
	<div class="col-md-2" style="float: right">
		<input class="btn btn-success" style="float:right" name="newticket" type="submit" value="{'NEW_TICKET'|getTranslatedString}" onClick="window.location.href='?module=HelpDesk&action=index&fun=newticket'">
	</div>
</div>
<!--<div class="row">
{foreach from=$FIELDLIST item=LIST key=BLOCK}
	<div class="col-md-12">
		<h3 class="value">
			<small>{$LIST.fieldlabel}</small>
		</h3>
	</div>
	<div class="col-md-12 linerow">
		<h3>&nbsp;{$LIST.fieldvalue}</h3>
	</div>
{/foreach}
</div>-->
<script language="javascript" type="text/javascript">
  var e = document.getElementById('potential');
alert(e.style.width());
console.log(e.style.width());
</script>

<div class="row fieldrow">
{assign var="N" value=0}
{foreach from=$FIELDLIST item=LIST1 key=BLOCK1}
	{if $BLOCK1 neq $block_here || $block_here eq ''}
		<table class="table col-md-12" id="potential">
		<tr><td style="border: none !important"><h2>{$BLOCK1|getTranslatedString}</h2></td></tr>
	{/if}
{assign var="block_here" value=BLOCK1}
	{foreach from=$LIST1 item=LIST key=BLOCK}
		{if $N == 0 && $N%2 == 0}
			<tr>
		{/if}

			<td class="col-md-6" style="border: 0px">
				<h3 class="value" style="padding:5px 0px">
					<small>{$LIST.fieldname|getTranslatedString}</small>
				</h3>
				<div class="linerow">
					<h3>&nbsp;{$LIST.fieldvalue|getTranslatedString}</h3>
				</div>
			</td>

		{if $N != 0 && $N%2 != 0}
			</tr>
		{/if}
		{assign var="N" value=$N+1}
	{/foreach}
	{if $BLOCK1 neq $block_here}
		</table>
	{/if}
{/foreach}
</div>




<!--<div class="row fieldrow">
{assign var="N" value=0}
<table class="table col-md-12" id="potential">
	{foreach from=$FIELDLIST item=LIST key=BLOCK}
		{if $N == 0 && $N%2 == 0}
			<tr>
		{/if}

			<td class="col-md-6" style="border: 0px">
				<h3 class="value" style="padding:5px 0px">
					<small>{$LIST.fieldlabel}</small>
				</h3>
				<div class="linerow">
					<h3>&nbsp;{$LIST.fieldvalue|getTranslatedString}</h3>
				</div>
			</td>

		{if $N != 0 && $N%2 != 0}
			</tr>
		{/if}
		{assign var="N" value=$N+1}
	{/foreach}
</table>
</div> -->

<div class="panel panel-default">
	<div class="panel-heading">
		<i class="fa fa-upload"></i>
		{'LBL_ATTACHMENTS'|getTranslatedString}
	</div>
	{foreach from=$FILES item=FILE}
		<div class="row" id="TickestList">
			{if !empty($FILE.filelocationtype)} 	
				{if $FILE.filelocationtype eq "I" || $FILE.filelocationtype eq "B"}
						<a href="index.php?downloadfile=true&fileid={$FILE.fileid}&filename={$FILE.filename}&filetype={$FILE.filetype}&filesize={$FILE.filesize}&ticketid={$TICKETID}">
							{assign var=TMP value=$TICKETID|cat:'_'}
							{$FILE.filename|ltrim:$TMP}
						</a>
				{/if}
				{if $FILE.filelocationtype eq "E"}
					<a href="{$FILE.filename}">{$FILE.filename}</a>
				{/if}
			{else}
				{'NO_ATTACHMENTS'|getTranslatedString}
			{/if}
		</div>
	{/foreach}
</div>