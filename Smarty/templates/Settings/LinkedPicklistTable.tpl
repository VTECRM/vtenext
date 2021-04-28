{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{assign var=picklist1 value=$PICKMATRIX.values1}
{assign var=picklist2 value=$PICKMATRIX.values2}
{assign var=matrix value=$PICKMATRIX.matrix}

<input type="hidden" name="input_picklist1" id="input_picklist1" value="{$PICKLIST1.name}" />
<input type="hidden" name="input_picklist2" id="input_picklist2" value="{$PICKLIST2.name}" />
<table class="small listTable" width="100%" cellspacing="0" cellpadding="2">
	<tr>
		<td class="colHeader"></td>
		<td align="center" class="colHeader">{"LBL_FIRST_PICKLIST"|getTranslatedString:"Settings"}: {$PICKLIST1.label}</td>
	<tr>

	<tr>
		<td align="center" class="colHeader">{"LBL_SECOND_PICKLIST"|getTranslatedString:"Settings"}:<br /> {$PICKLIST2.label}</td>
		<td class="listTableRow">
			<div>

				<table id="table_matrixvalues" class="small table_plistmatrix" width="100%">

						<tr>
							{foreach item=p1 key=key1 from=$picklist1}
								<td class="td_plistmatrix_head">{$p1}</td>
							{/foreach}
						</tr>

						{foreach item=p2 key=key2 from=$picklist2}
							<tr>
							<!-- td>{$p2}</td -->
							{foreach item=p1 key=key1 from=$picklist1}
								{assign var=mval value=$matrix[$key1][$key2]}
								{if $mval eq 0}
									<td class="plist_td_down" onclick="plistMatrixToggle(this)">
										{$p2}
										<input type="hidden" name="plistmatrix_{$key1}::{$key2}" value="0" />
									</td>
								{else}
									<td class="plist_td_up" onclick="plistMatrixToggle(this)">
										{$p2}
										<input type="hidden" name="plistmatrix_{$key1}::{$key2}" value="1" />
									</td>
								{/if}

							{/foreach}
							</tr>

						{/foreach}
				</table>

			</div>

		</td>
	</tr>

	<tr>

		<td colspan="2" align="right">
			<input type="button" class="small crmbutton edit" value="{'LBL_ENABLE_ALL'|getTranslatedString:'Settings'}" onclick="plistEnableAll()" />
			<input type="button" class="small crmbutton edit" value="{'LBL_DISABLE_ALL'|getTranslatedString:'Settings'}" onclick="plistDisableAll()" />
		</td>
	</tr>

</table>