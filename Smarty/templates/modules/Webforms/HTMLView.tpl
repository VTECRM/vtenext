{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* NOTE: PLEASE MAKE SURE THE SPACES BESIDE TAGS ARE STRIPPED TO PRESEVE FORMATTING OF THE OUTPUT *}
{* crmv@32257 crmv@55014 crmv@93389 *}
<form name="VTEForm_{$WEBFORMMODEL->getpublicId()}" action="{$ACTIONPATH}/modules/Webforms/capture.php" method="post" accept-charset="utf-8">
	<p>
		<input type="hidden" name="publicid" value="{$WEBFORMMODEL->getpublicId()}" />
		<input type="hidden" name="name" value="{$WEBFORMMODEL->getName()}" />
	</p>
	{foreach item=field from=$WEBFORMFIELDS name=fieldloop}
		{if $field->getHidden() eq 1}
			<input type="hidden" value="{$field->getDefaultValue()}" name="{$field->getNeutralizedField()}" />
		{else}
			{assign var=fieldinfo value=$WEBFORM->getFieldInfo($WEBFORMMODEL->getTargetModule(), $field->getFieldName())}
			<p>
				<label>{$fieldinfo.label}</label>
				{if $fieldinfo.type.name eq 'picklist' || $fieldinfo.type.name eq 'multipicklist'} {* crmv@167234 *}
					<select name="{$field->getNeutralizedField()}[]" {if $field->getRequired() eq 1}required="true"{/if}{if $fieldinfo.type.name eq 'multipicklist'}multiple="multiple" size="5"{/if}>
						{foreach item=option from=$fieldinfo.type.picklistValues name=optionloop}
							{assign var=value value=$option.value|escape:'html'}
							<option value="{$value}" {if $field->getDefaultValue() eq $value}selected{/if}>{$option.label|escape:'html'}</option>
						{/foreach}
					</select>
				{elseif $fieldinfo.type.name eq 'boolean'}
					{* crmv@162158 *}
					{if $field->getRequired() eq 1}
						<select name="{$field->getNeutralizedField()}" required="true">
							<option value="">{'LBL_PLEASE_SELECT'|getTranslatedString}</option>
							<option value="off">{'LBL_NO'|getTranslatedString}</option>
							<option value="on" {if $field->getDefaultValue() eq 'on'}selected{/if}>{'LBL_YES'|getTranslatedString}</option>
						</select>
					{else}
						<input type="checkbox" name="{$field->getNeutralizedField()}" >
					{/if}
					{* crmv@162158e *}
				{* crmv@179693 *}
				{elseif $fieldinfo.type.name eq 'date'}
					<input type="date" value="{$field->getDefaultValue()}" name="{$field->getNeutralizedField()}" {if $field->getRequired() eq 1}required="true"{/if} />
				{* crmv@179693e *}
				{else}
					{if $field->getNeutralizedField() eq 'salutationtype'}
						<select name="{$field->getNeutralizedField()}" {if $field->getRequired() eq 1}required="true"{/if}>
							<option value="" {if $field->getDefaultValue() eq ''}selected{/if}>--None--</option>
							<option value="Mr." {if $field->getDefaultValue() eq 'Mr.'}selected{/if}>Mr.</option>
							<option value="Ms." {if $field->getDefaultValue() eq 'Ms.'}selected{/if}>Ms.</option>
							<option value="Mrs." {if $field->getDefaultValue() eq 'Mrs.'}selected{/if}>Mrs.</option>
							<option value="Dr." {if $field->getDefaultValue() eq 'Dr.'}selected{/if}>Dr.</option>
							<option value="Prof." {if $field->getDefaultValue() eq 'Prof.'}selected{/if}>Prof</option>
						</select>
					{else}
						<input type="text" value="{$field->getDefaultValue()}" name="{$field->getNeutralizedField()}" {if $field->getRequired() eq 1}required="true"{/if} />
					{/if}
				{/if}
			</p>
		{/if}
	{/foreach}
	<p>
		<input type="submit" value="Submit" />
	</p>
</form>
{literal}
<script type="text/javascript">
(function() {
	var M = navigator.userAgent.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];

	if(M[1] == 'Safari'){ 
		var vte_key = document.getElementsByName('publicid');
		if (!vte_key) return;

		var forms = document.getElementsByName('VTEForm_'+vte_key[0].value);

		for (var i = 0; i < forms.length; i++) {
			forms[i].noValidate = true;
			forms[i].addEventListener('submit', function(event) {
				//Prevent submission if checkValidity on the form returns false.
				if (!event.target.checkValidity()) {
					event.preventDefault();
					var inputs = event.target.getElementsByTagName('input');
				
					for (var j = 0; j< inputs.length; ++j) {
						if (inputs[j].validity && !inputs[j].validity.valid){
							var label = inputs[j].previousElementSibling.innerHTML;
							alert('Non valido '+label);
							return false;
						}
					}
					var selects = event.target.getElementsByTagName('select');
					for (var j = 0; j< selects.length; ++j) {
						if (selects[j].validity && !selects[j].validity.valid){
							var label = selects[j].previousElementSibling.innerHTML;
							alert('Non valido '+label);
							return false;
						}
					}
					var tarea = event.target.getElementsByTagName('textarea');
					for (var j = 0; j< tarea.length; ++j) {
						if (tarea[j].validity && !tarea[j].validity.valid){
							var label = tarea[j].previousElementSibling.innerHTML;
							alert('Non valido '+label);
							return false;
						}
					}
				}
			}, false);
		}
	} 
})();
</script>
{/literal}