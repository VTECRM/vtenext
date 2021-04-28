{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@161554 *}

<input type="hidden" name="accesstoken" value="{$ACCESS_TOKEN}" />
				
{foreach from=$STRUCTURE item=BLOCK}
	{assign var=fieldCounter value=0}
	
	<fieldset class="form-group">
		<legend>
			<strong>{$BLOCK.label|_T}</strong><br>
		</legend>

		{foreach from=$BLOCK.fields item=FIELD name=blockFields}
			{assign var=fieldName value=$FIELD.name}
			{assign var=fieldLabel value=$FIELD.label}
	
			{if $fieldCounter eq 0}
				<div class="form-group">
				<div class="form-row">
			{/if}
	
			{assign var=fieldCounter value=$fieldCounter+1}
			
			<div class="col-md-6">
				<label for="{$fieldName}">{$fieldLabel|_T}</label>
				<input type="text" class="form-control" id="{$fieldName}" name="{$fieldName}" />
			</div>
			
			{if $fieldCounter eq 2}
				</div>
				</div>
				{assign var=fieldCounter value=0}
			{/if}
		{/foreach}
	</fieldset>
{/foreach}

<script type="text/javascript">
	{if $CONTACT_DATA}
		var contactData = {$CONTACT_DATA|replace:"'":"\'"};
	{else}
		var contactData = {ldelim}{rdelim};
	{/if}
</script>