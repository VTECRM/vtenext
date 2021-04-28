{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@OPER6317 crmv@96233 crmv@98866 *}

{include file="SmallHeader.tpl" HEAD_INCLUDE="all" BODY_EXTRA_CLASS="popup-wizard"}
{include file='CachedValues.tpl'}

<script type="text/javascript" src="{"include/js/Wizard.js"|resourcever}"></script>
<script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="modules/SDK/SDK.js"></script>

<input type="hidden" name="wizardid" id="wizardid" value="{$WIZARD_ID}" />
<input type="hidden" name="module" id="module" value="{$MODULE}" />
<input type="hidden" name="wizard_parent_module" id="wizard_parent_module" value="{$PARENT_MODULE}"/>
<input type="hidden" name="wizard_parent_id" id="wizard_parent_id" value="{$PARENT_ID}"/>

{* popup status *}
<div id="status" name="status" style="display:none;position:fixed;right:20px;top:16px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>