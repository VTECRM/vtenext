{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<input type="text" class="detailedViewTextBox" id="templateemail_name" name="templateemail_name" readonly="" value="{$TEMPLATE_NAME}">

<i class="vteicon md-text md-link" onclick="openPopup('index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailList&record={$RECORD}','TemplateEmailList','top=100,left=200,height=400,width=500,resizable=yes,scrollbars=yes,menubar=no,addressbar=no,status=yes','auto')" title="{'LBL_SELECT'|getTranslatedString}">view_list</i>
<i class="vteicon md-text md-link" onclick="openPopup('index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailEdit&record={$RECORD}','TemplateEmailList','top=100,left=200,height=400,width=500,resizable=yes,scrollbars=yes,menubar=no,addressbar=no,status=yes','auto')" title="{'LBL_CREATE'|getTranslatedString}">add</i>
{if $EDIT_PERMISSION}
	<i class="vteicon md-text md-link" onclick="openPopup('index.php?module=Newsletter&action=NewsletterAjax&file=widgets/TemplateEmailEdit&record={$RECORD}&mode=edit','TemplateEmailList','top=100,left=200,height=400,width=500,resizable=yes,scrollbars=yes,menubar=no,addressbar=no,status=yes','auto')" title="{'LBL_EDIT'|getTranslatedString}">edit</i>
{/if}