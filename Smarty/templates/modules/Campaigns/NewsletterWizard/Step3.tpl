{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="nlWizStep3" style="display:none">
	<div class="spacer-20"></div>
	<p>{$MOD.InsertNewsletterData}:</p>
	<form name="nlw_RecordFields" id="nlw_RecordFields" onsubmit="return false;">
	<table class="table borderless" style="width:80%">
		{foreach item=FLD from=$NLFIELDS}
		<tr>
			<td>{if $FLD.mandatory}<font color="red">*</font>{/if}{$FLD.label}</td>
			<td>
				{if $FLD.uitype eq '19'}
				<div class="cellInfo">
					<textarea class="detailedViewTextBox {if $FLD.mandatory}mandatoryField{/if} vertical" name="{$FLD.name}">{$FLD.value}</textarea>
				</div>
				{else}
				<div class="cellInfo">
					<input type="text" class="detailedViewTextBox {if $FLD.mandatory}mandatoryField{/if}" name="{$FLD.name}" value="{$FLD.value}" />
				</div>
				{/if}
			</td>
		</tr>
		{/foreach}
		{* crmv@151466 - removed field *}
	</table>
	</form>
</div>