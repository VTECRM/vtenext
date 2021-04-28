{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div class="row" style="margin-top:50px">
	<input class="btn btn-success" style="float:right" name="newticket" type="submit" value="{'NEW_TICKET'|getTranslatedString}" onClick="window.location.href='?module=HelpDesk&action=index&fun=newticket'">
	<input class="btn btn-success" name="newpotentials" type="submit" value="{'NEW_POTENTIALS'|getTranslatedString}" onClick="window.location.href='?module=Potentials&action=index&fun=newpotentials'">
</div>

<!--{foreach from=$ENTRIES key=ID item=ENTRY}
	<div class="row" id="TickestList" onClick="window.location.href='{$LINKS.$ID}'">
		<div class="col-md-12" >
			<h3>{$ENTRY.0}</h3>
			<small>{$ENTRY.2|getTranslatedString}</small>
		</div>
	</div>
{/foreach} -->

<div class="row" id="PotentialsList">
{foreach from=$ENTRIES2 key=ID2 item=ENTRY2}
<div class="col-md-12 PotentialsValue">
	{foreach from=$ENTRY2 key=POINT item=VALUE}
		{if $POINT eq 0}
			<h3>{$VALUE.fielddata}</h3>
		{elseif $POINT eq 2}
			<small>{$VALUE.fielddata|getTranslatedString}</small>
		{/if}
	{/foreach}
</div>
{/foreach}
</div>
