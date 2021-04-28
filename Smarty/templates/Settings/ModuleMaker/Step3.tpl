{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 crmv@69398 *}

<div>
	<p>{$MOD.LBL_MMAKER_STEP3_INTRO}</p>
</div>
<br>

{assign var=COLPERLINE value=4}
{assign var=ROWS value=$MAXFILTERCOLUMNS/$COLPERLINE|ceil}
{assign var=FILTERNO value=0}
{assign var=ALLFILTER value=$STEPVARS.mmaker_filters[0]}

<input type="hidden" id="filter_no" value="{$FILTERNO}" />
<input type="hidden" id="filter_tot_columns" value="{$MAXFILTERCOLUMNS}" />

{* variables for the filter *} 
<input type="hidden" name="filter_{$FILTERNO}_label" value="{$ALLFILTER.label}" />
<input type="hidden" name="filter_{$FILTERNO}_name" value="{$ALLFILTER.name}" />
<input type="hidden" name="filter_{$FILTERNO}_all" value="{$ALLFILTER.all}" />
<table border="0" width="100%">
	{assign var=count value=0}
	{section name=row loop=$ROWS}
	<tr>
		{section name=col loop=$COLPERLINE}
			<td>
				{if $count < $MAXFILTERCOLUMNS}
					<div class="dvtCellInfo">
						<select class="detailedViewTextBox" id="filtercol_{$FILTERNO}_{$count}" name="filtercol_{$FILTERNO}_{$count}">
							<option value="">{$APP.LBL_NONE}</option>
							{foreach item=fblock from=$FILTERFIELDS}
								<optgroup label="{$fblock.blocklabel}">
								{foreach item=fcol from=$fblock.fields}
									<option value="{$fcol.fieldname}" {if $ALLFILTER.columns[$count] eq $fcol.fieldname}selected=""{/if}>{$fcol.label}</option>
								{/foreach}
								</optgroup>
							{/foreach}
						</select>
					</div>
				{/if}
			</td>
			{assign var=count value=$count+1}
		{/section}
	</tr>
	{/section}
</table>

<br>
<p>{$MOD.LBL_MMAKER_RELATED_FILTER}</p>

{* filter for related *}
{assign var=FILTERNO value=1}
{assign var=RELFILTER value=$STEPVARS.mmaker_filters[1]}

<input type="hidden" id="relfilter_no" value="{$FILTERNO}" />

{* variables for the filter *} 
<input type="hidden" name="filter_{$FILTERNO}_label" value="{$RELFILTER.label}" />
<input type="hidden" name="filter_{$FILTERNO}_name" value="{$RELFILTER.name}" />
<input type="hidden" name="filter_{$FILTERNO}_all" value="{$RELFILTER.all}" />
<table border="0" width="100%">
	{assign var=count value=0}
	{section name=row loop=$ROWS}
	<tr>
		{section name=col loop=$COLPERLINE}
			<td>
				{if $count < $MAXFILTERCOLUMNS}
					<div class="dvtCellInfo">
						<select class="detailedViewTextBox" id="filtercol_{$FILTERNO}_{$count}" name="filtercol_{$FILTERNO}_{$count}">
							<option value="">{$APP.LBL_NONE}</option>
							{foreach item=fblock from=$FILTERFIELDS}
								<optgroup label="{$fblock.blocklabel}">
								{foreach item=fcol from=$fblock.fields}
									<option value="{$fcol.fieldname}" {if $RELFILTER.columns[$count] eq $fcol.fieldname}selected=""{/if}>{$fcol.label}</option>
								{/foreach}
								</optgroup>
							{/foreach}
						</select>
					</div>
				{/if}
			</td>
			{assign var=count value=$count+1}
		{/section}
	</tr>
	{/section}
</table>