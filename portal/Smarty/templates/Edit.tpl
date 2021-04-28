{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@173271 *}

{if !empty($MODULE_JS)}
	{foreach item=JS from=$MODULE_JS}
	<script type="text/javascript" src="{$JS}"></script> {* crmv@160733 *}
	{/foreach}
{/if}

<form method="POST" action="index.php" name="form_save" enctype="multipart/form-data">
<input type="hidden" name="module" value="{$MODULE}" />
<input type="hidden" name="action" value="Save" />
<input type="hidden" name="id" value="{$ID}" />

<div class="row" style="margin-top: 5px; margin-bottom: 30px;">
	<div class="col-md-2" style="float: left">
		<input align="left" class="btn btn-default" type="button"
			value="{'LBL_BACK_BUTTON'|getTranslatedString}"
			onclick="window.history.back();" />
	</div>
	{if $PERMISSION && $PERMISSION.perm_write}
		<div class="col-md-2" style="float: right">
			<input align="right" class="btn btn-primary" type="submit" value="{'LBL_SAVE'|getTranslatedString}" />
		</div>
	{/if}
</div>

{if $ERROR_MSG}
<div class="alert alert-danger" style="margin-top: 5px; margin-bottom: 10px;">
	<p>{$ERROR_MSG}</p>
</div>
{/if}

{* crmv@90004 *}
<div class="table col-md-12">
	{foreach from=$FIELDSTRUCT item=FIELD}
		{assign var="NAME" value=$FIELD.name}
		{assign var="LABEL" value=$FIELD.label}
		{assign var="WSTYPE" value=$FIELD.type.name}
		{assign var="UITYPE" value=$FIELD.uitype}
		{assign var="VALUE" value=$FIELDLIST.$NAME.fieldvalue}
		
		<div class="form-group col-md-6 col-xs-12" style="border: 0px">
			<h3 class="value" style="padding:5px 0px">
				{* header *}
				<label class="control-label">
					<small>{$LABEL}{if $FIELD.mandatory} *{/if}</small>
				</label>
			</h3>
			<div>
				{* value *}
				{* first check uitypes, which are more specific, then use the wstype *}
				{if !$FIELD.editable}
					{if $UITYPE == '209' || $WSTYPE == 'file'}
						<input class="form-control" type="text" readonly="true" disabled="" value="{$VALUE|basename}" />
					{else}
						<input class="form-control" type="text" readonly="true" disabled="" value="{$VALUE}" />
					{/if}
				{else}
					{if $UITYPE == '209' || $WSTYPE == 'file'}
						{* check if file has already been uploaded *}
						{if $VALUE}
							<input type="text" class="form-control" id="{$NAME}_display" readonly="" value="{$VALUE|basename}" />
							<input type="file" class="form-control" id="{$NAME}_input" name="{$NAME}" {if $FIELD.mandatory}required="true"{/if} disabled="true" style="display:none"/>
							<button type="button" id="{$NAME}_btn_show" onclick="Portal.enableFileInput('{$NAME}')">{'LBL_CHANGE'|getTranslatedString}</button>
							<button type="button" id="{$NAME}_btn_hide" style="display:none" onclick="Portal.disableFileInput('{$NAME}')">{'LBL_CANCEL'|getTranslatedString}</button>
						{else}
							<input type="file" class="form-control" name="{$NAME}{if $FIELD.type.multiple}[]{/if}" {if $FIELD.mandatory}required="true"{/if} {if $FIELD.type.multiple}multiple{/if} />
						{/if}
					{elseif $WSTYPE == 'string'}
						<input type="text" class="form-control" name="{$NAME}" value="{$VALUE}" {if $FIELD.mandatory}required="true"{/if} />
					{elseif $WSTYPE == 'text'}
						<textarea class="form-control" name="{$NAME}" {if $FIELD.mandatory}required="true"{/if} rows="5">{$VALUE}</textarea>
					{elseif $WSTYPE == 'picklist' || $WSTYPE == 'picklistmultilanguage'}
						<select name="{$NAME}" class="form-control">
							{foreach item=SELOPT from=$FIELD.type.picklistValues}
							<option value="{$SELOPT.value}" {if $VALUE == $SELOPT.label}selected=""{/if}>{$SELOPT.label}</option>
							{/foreach}
						</select>
					{elseif $WSTYPE == 'multipicklist'}
						{assign var=ARRVALUE value=', '|explode:$VALUE}
						<select name="{$NAME}[]" class="form-control" multiple="" size="3">
							{foreach item=SELOPT from=$FIELD.type.picklistValues}
								<option value="{$SELOPT.value}" {if in_array($SELOPT.value, $ARRVALUE)}selected=""{/if}>{$SELOPT.label}</option>
							{/foreach}
						</select>
					{elseif $WSTYPE == 'boolean' or $WSTYPE == 'checkbox'}
						<input type="checkbox" name="{$NAME}" {if $FIELD.mandatory}required="true"{/if} {if $VALUE == 1 || $VALUE == 'on'}checked=""{/if}/>
					{elseif $WSTYPE == 'date'}
						<input type="date" name="{$NAME}" value="{$VALUE}" {if $FIELD.mandatory}required="true"{/if} 
						{if $FIELD.mindate}min="{$FIELD.mindate}"{/if}
						{if $FIELD.maxdate}max="{$FIELD.maxdate}"{/if}
						/>
					{elseif $WSTYPE == 'email'}
						<input type="email" class="form-control" name="{$NAME}" value="{$VALUE}" {if $FIELD.mandatory}required="true"{/if} />
					{elseif $WSTYPE == 'phone'}
						<input type="tel" class="form-control" name="{$NAME}" value="{$VALUE}" {if $FIELD.mandatory}required="true"{/if} />
					{elseif $WSTYPE == 'double'}
						{* not realy double, but ok too *}
						<input type="number" class="form-control" name="{$NAME}" value="{$VALUE}" {if $FIELD.mandatory}required="true"{/if} />
					{/if}
				{/if}
			</div>
		</div>
	{/foreach}
</div>
{* crmv@90004e *}

</form>

{* TODO 
<script src="SDK/src/js/date-input-polyfill.dist.js"></script>
{literal}
<style type="text/css">
date-input-polyfill {
	z-index: 10000;
}
</style>
{/literal}

*}