{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@173153 crmv@173271 *}

{if !empty($MODULE_JS)}
	{foreach item=JS from=$MODULE_JS}
	<script type="text/javascript" src="{$JS}"></script> {* crmv@160733 *}
	{/foreach}
{/if}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$CREATE_TITLE}</h1>
	</div>
	<div class="col-sm-12">
		<form name="Save" method="post" action="index.php" enctype="multipart/form-data">
			<input type="hidden" name="module" value="{$MODULE}">
			<input type="hidden" name="action" value="index">
			<input type="hidden" name="fun" value="saveticket">
			<input type="hidden" name="projectid" value="{$PROJECTID}">
	
			<div class="row">
				<div class="col-sm-12 text-right">
					<button class="btn btn-success" accesskey="S" name="button" type="submit" >{'LBL_SEND_REQUEST'|getTranslatedString}</button> {* crmv@117223 *}
					<button class="btn btn-danger" accesskey="X" name="button" type="button" onclick="window.history.back()">{'LBL_CANCEL'|getTranslatedString}</button>
				</div>
			</div>
			
			
			{* crmv@90004 *}
			{* TODO: unify with edit view *}
<div class="table col-md-12">
	{foreach from=$FIELDSTRUCT item=FIELD}
		{assign var="NAME" value=$FIELD.name}
		{assign var="LABEL" value=$FIELD.label}
		{assign var="WSTYPE" value=$FIELD.type.name}
		{assign var="UITYPE" value=$FIELD.uitype}
		{assign var="VALUE" value=$FIELDLIST.$NAME.fieldvalue}
		
		{if $DISPLAY_COLUMNS eq 1}
		<div class="form-group col-md-12 col-xs-12" style="border: 0px">
		{else}
		<div class="form-group col-md-6 col-xs-12" style="border: 0px">
		{/if}
			<h4 class="value" style="padding:5px 0px">
				{* header *}
				<label class="control-label">
					{$LABEL}{if $SHOW_MANDATORY_SYMBOL && $FIELD.mandatory} *{/if}
				</label>
			</h4>
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
						<textarea class="form-control" name="{$NAME}" {if $FIELD.mandatory}required="true"{/if} rows="{if $NAME eq 'description'}12{else}5{/if}">{$VALUE}</textarea>
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
						{* not realy double, but integer is ok too *}
						<input type="number" class="form-control" name="{$NAME}" value="{$VALUE}" {if $FIELD.mandatory}required="true"{/if} />
					{/if}
				{/if}
			</div>
		</div>
	{/foreach}
	
	{if $UPLOAD_ATTACHMENTS}
		<div class="row">
			<div class="col-sm-12 form-group">
				<h4><label for="customerfile">{'LBL_UPLOAD_PICTURE'|getTranslatedString}</label></h4>
				<div class="alert alert-info">{'LBL_DROP_INFO'|getTranslatedString}</div>
				<input type="file" class="detailedViewTextBox form-control" name="customerfile[]" id="customerfile" multiple />
			</div>
		</div>
	{/if}

</div>
{* crmv@90004e *}

		</form>
	</div>
</div>

{if $UPLOAD_ATTACHMENTS}
	{include file="DropArea.tpl"}
{/if}