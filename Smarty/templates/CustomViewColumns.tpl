{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<table cellspacing="5" cellpadding="0" width="100%">
	<tr>
		<td>
			<div class="dvtCellInfo">
				<select name="column1" id="column1" onChange="checkDuplicate();" class="detailedViewTextBox">
                	<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN1}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="column2" id="column2" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN2}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="column3" id="column3" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN3}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="column4" id="column4" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN4}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td></td>
	</tr>
	<tr>
		<td>
			<div class="dvtCellInfo">
				<select name="column5" id="column5" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN5}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="column6" id="column6" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN6}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="column7" id="column7" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN7}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td>
			<div class="dvtCellInfo">
				<select name="column8" id="column8" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN8}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
		<td></td>
	</tr>
	<tr>
		<td>
			<div class="dvtCellInfo">
				<select name="column9" id="column9" onChange="checkDuplicate();" class="detailedViewTextBox">
					<option value="">{$MOD.LBL_NONE}</option>
					{foreach item=filteroption key=label from=$CHOOSECOLUMN9}
						<optgroup label="{$label}">
						{foreach item=text from=$filteroption}
							{assign var=option_values value=$text.text}
							<option {$text.selected} value={$text.value}>
							{if $MOD.$option_values neq ''}
								{if $DATATYPE.0.$option_values eq 'M'}
									{$MOD.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
								{else}
                                        {$MOD.$option_values}
                                {/if}
                        	{elseif $APP.$option_values neq ''}
                                {if $DATATYPE.0.$option_values eq 'M'}
                                	{$APP.$option_values}   {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$APP.$option_values}
                                {/if}
                        	{else}
                                {if $DATATYPE.0.$option_values eq 'M'}
                            		{$option_values}    {$APP.LBL_REQUIRED_SYMBOL}
                                {else}
									{$option_values}
                                {/if}
							{/if}
							</option>
						{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>
		</td>
	     <td>&nbsp;</td>
	     <td>&nbsp;</td>
	     <td>&nbsp;</td>
	     <td>&nbsp;</td>
	</tr>
</table>