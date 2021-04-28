{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@96233 *}

<div>
	<p>{$MOD.LBL_WMAKER_STEP3_INTRO}</p>
</div>
<br>

{assign var=FIELDS value=$STEPVARS.wmaker_fields}
{assign var=FIELDCOUNT value=$FIELDS|@count}

{math equation="ceil(x/2)" x=$FIELDCOUNT assign="ROWCOUNT"}

{* labels for visible and mandatory *}


<table class="small" border="0" width="100%">
	
	<tr>
		<td colspan="12">{$APP.LBL_FIELDS}</td>
	</tr>
	
	{*<tr>
		<td colspan="4"></td>
		<td colspan=""></td>
	</tr>*}
	
	<tr>
	{foreach item="FIELD" key=idx from=$FIELDS}
		{if $idx < 2}
		<td rowspan="{$ROWCOUNT}" width="2%" align="center" style="background-color:#E8E8E8;">{$LBL_VISIBLE}</td>
		{/if}
		<td width="2%" style="background-color:#E8E8E8;">
			<input type="checkbox" id="field_{$FIELD.fieldid}" name="field_{$FIELD.fieldid}" {if $FIELD.visible || $FIELD.parent}checked="checked"{/if} {if !$FIELD.editable || $FIELD.parent}disabled="disabled"{/if} onchange="WizardMaker.step3_changeVisible(this)" />
		</td>
		{if $idx < 2}
		<td rowspan="{$ROWCOUNT}" width="2%" align="center" style="background-color:#EFD5D5;">{$LBL_MANDATORY}</td>
		{/if}
		<td width="2%" style="background-color:#EFD5D5;">
			<input type="checkbox" id="field_mand_{$FIELD.fieldid}" name="field_mand_{$FIELD.fieldid}" {if $FIELD.mandatory}checked="checked"{/if} {if !$FIELD.editable}disabled="disabled"{/if} onchange="WizardMaker.step3_changeMand(this)" />
		</td>
		<td width="24%">
			{$FIELD.label}
		</td>
		<td width="18%">
			{*<div class="dvtCellInfo">
			<input type="text" class="detailedViewTextBox" name=""/>
			</div>
			*}
		</td>
		{if $idx > 0 && $idx mod 2 == 1}
		</tr>
		<tr>
		{/if}
	{/foreach}
	</tr>
	

</table>