{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JavaScript" type="text/javascript" src="{"include/js/customview.js"|resourcever}"></script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        {include file='SettingsMenu.tpl'}
        <td width="75%" valign="top">
            <table width="99%" border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td class="showPanelBg" valign="top" style="padding-left:20px; "><br/>
                        <span class="lvtHeaderText">{$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_STUDIO} &gt; {$MOD.LBL_CUSTOM_FIELD_SETTINGS} </span>
                        <hr noshade="noshade" size="1"/>
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    </td>
                    <form action="index.php?module=Settings&action=LeadCustomFieldMapping&parenttab=Settings"
                          method="post" name="index" onsubmit="VteJS_DialogBox.block();">
                        <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
                        <table class="leadTable" align="center" cellpadding="5" cellspacing="0" width="95%">
                            <tr>
                                <td style="border-bottom: 2px dotted rgb(204, 204, 204); padding: 5px;" width="5%">
                                    <img src="{'mapping.gif'|resourcever}" align="middle" height="48" width="48">
                                </td>
                                <td style="border-bottom: 2px dotted rgb(170, 170, 170); padding: 5px;">
                                    <span class="genHeaderGrayBig">{$MOD.LBL_LEADS_CUSTOM_FIELD_MAPPING}</span><br>
                                    <span>{'leadCustomFieldDescription'|@getTranslatedString:$MODULE}}</span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="right">&nbsp;</td>
                                <td><b class="lvtHeaderText">Mapping Information </b></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td>
                                    <table style="background-color: rgb(204, 204, 204);" class="small" border="0"
                                           cellpadding="5" cellspacing="1" width="95%">
                                        <tr>
                                            <td class="lvtCol" width="20%">Leads CustomField</td>
                                            <td class="lvtCol" width="20%">Accounts CustomField</td>
                                            <td class="lvtCol" width="20%">Contacts CustomField</td>
                                            <td class="lvtCol" width="20%">Potential CustomField</td>
                                            <td class="lvtCol" width="20%">Tool</td>
                                        <tr>
                                            {foreach item=cfarray from=$CUSTOMFIELDMAPPING}
                                        <tr class="lvtColData" bgcolor="white">
                                            <td>{$cfarray.leadlabel}</td>
                                            <td>{$cfarray.accountlabel}</td>
                                            <td>{$cfarray.contactlabel}</td>
                                            <td>{$cfarray.potentiallabel}</td>
                                            <td>{$cfarray.del}</td>
                                        </tr>
                                        {/foreach}
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border-bottom: 2px dotted rgb(170, 170, 170); padding: 5px;">
                                    &nbsp;
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border-bottom: 2px dotted rgb(170, 170, 170); padding: 5px;"
                                    align="center">
                                    <input title="{$APP.LBL_EDIT_BUTTON_LABEL}" accessKey="{$APP.LBL_EDIT_BUTTON_KEY}"
                                           class="crmbutton small edit" type="submit" name="button"
                                           value="{$APP.LBL_EDIT_BUTTON_LABEL}">
                                    <input title="{$APP.LBL_CANCEL_BUTTON_LABEL}>"
                                           accessKey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="crmbutton small cancel"
                                           type="button"
                                           onclick="gotourl('index.php?action=CustomFieldList&module=Settings&fld_module=Leads&parenttab=Settings')"
                                           name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                        </table>
                    </form>
                    </td>
                </tr>
                </td>
                </tr></table>
            <script>
                function confirmdelete(url) {ldelim}
                    if (confirm("{$APP.ARE_YOU_SURE}")) {ldelim}
                        document.location.href = url;
                        {rdelim}
                    {rdelim}

                function gotourl(url) {ldelim}
                    document.location.href = url;
                    {rdelim}
            </script>
			