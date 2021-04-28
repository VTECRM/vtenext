{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
  * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
*}

<div id="customfield" style="display:block;" class="layerPopup">
    <script language="JavaScript" type="text/javascript" src="include/js/customview.js"></script>
    <form action="index.php" method="post" name="addtodb"
          onSubmit="if(validate('{$BLOCKID}')) {VteJS_DialogBox.block();}else{return false;}">
        <input type="hidden" name="module" value="Settings">
        <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
        <input type="hidden" name="fld_module" value="{$FLD_MODULE}">
        <input type="hidden" name="activity_type" value="{$ACTIVITYTYPE}">
        <input type="hidden" name="parenttab" value="Settings">
        <input type="hidden" name="action" value="AddCustomFieldToDB">
        <input type="hidden" name="fieldid" value="{$FIELDID}">
        <input type="hidden" name="column" value="{$CUSTOMNAMEFIELD_COLUMNNAME}">
        <input type="hidden" name="mode" id="cfedit_mode" value={$MODE}>
        <input type="hidden" name="cfcombo" id="selectedfieldtype" value="">
        <input type="hidden" name="blockid" value="{$BLOCKID}">


        <table width="100%" border="0" cellpadding="5" cellspacing="0" class="layerHeadingULine">
            <tr>
                {if $LDAP_SERVER_MODE neq 'edit'}
                    <td width="60%" align="left" class="layerPopupHeading">{$MOD['LBL_EDIT_FIELD_TYPE']}
                        - {$CUSTOMNAMEFIELD_TYPENAME}</td>
                {else}
                    <td width="60%" align="left" class="layerPopupHeading">{$MOD['LBL_ADD_FIELD']}</td>
                    <td width="40%" align="right"><a href="javascript:fninvsh('customfield');"><img
                                    src="{resourcever('close.gif')}" border="0" align="absmiddle"/></a></td>
                {/if}
            </tr>
        </table>
        <table border=0 cellspacing=0 cellpadding=5 width=95% align=center>
            <tr>
                <td class=small>
                    <table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
                        <tr>';
                            {if $MODE neq 'edit'}
                                <td>
                                    <table>
                                        <tr>
                                            <td>{$MOD['LBL_SELECT_FIELD_TYPE']}</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div name="cfcombo" id="cfcombo" class=small
                                                     style="width:205px;height:150px;overflow-y:auto;overflow-x:hidden;overflow:auto;border:1px solid #CCCCCC;">{$COMBOOUTPUT}</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            {/if}
                            <td width="50%">
                                <table width="100%" border="0" cellpadding="5" cellspacing="0">
                                    <tr>
                                        <td class="dataLabel" nowrap="nowrap" align="right" width="30%">
                                            <b>{$MOD['LBL_LABEL']}</b></td>
                                        <td align="left" width="70%"><input name="fldLabel_{$BLOCKID}"
                                                                            id="fldLabel_{$BLOCKID}"
                                                                            value="{$CUSTOMNAMEFIELD_FIELDLABEL}"
                                                                            type="text" class="txtBox"></td>
                                    </tr>
                                    ';
                                    {if $MODE neq 'edit'}
                                        <tr id="lengthdetails_{$BLOCKID}">
                                            <td class="dataLabel" nowrap="nowrap" align="right">
                                                <b>{$MOD['LBL_LENGTH']}</b></td>
                                            <td align="left"><input type="text" name="fldLength_{$BLOCKID}"
                                                                    id=="fldLength_{$BLOCKID}"
                                                                    value="{$FIELDLENGTH}" {$READONLY} class="txtBox">
                                            </td>
                                        </tr>
                                        <tr id="decimaldetails_{$BLOCKID}" style="visibility:hidden;">
                                            <td class="dataLabel" nowrap="nowrap" align="right">
                                                <b>{$MOD['LBL_DECIMAL_PLACES']}</b></td>
                                            <td align="left"><input type="text" name="fldDecimal_{$BLOCKID}"
                                                                    id="fldDecimal_{$BLOCKID}"
                                                                    value="{DECIMALVALUE}" {$READONLY} class="txtBox">
                                            </td>
                                        </tr>
                                        <tr id="picklistdetails_{$BLOCKID}" style="visibility:hidden;">
                                            <td class="dataLabel" nowrap="nowrap" align="right" valign="top">
                                                <b>{$MOD['LBL_PICK_LIST_VALUES']}</b></td>
                                            <td align="left" valign="top"><textarea name="fldPickList_{$BLOCKID}"
                                                                                    id=="fldPickList_{$BLOCKID}"
                                                                                    rows="10"
                                                                                    class="txtBox" {$READONLY}>{$FLDVAL}</textarea>
                                            </td>
                                        </tr>
                                    {/if}
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
            <tr>
                <td align="center">
                    <input type="submit" name="save" value=" &nbsp; {$APP['LBL_SAVE_BUTTON_LABEL']} &nbsp; "
                           class="crmButton small save"/>&nbsp;
                    <input type="button" name="cancel" value="{$APP['LBL_CANCEL_BUTTON_LABEL']}"
                           class="crmButton small cancel" onclick="fninvsh('customfield');"/>
                </td>
            </tr>
        </table>
        <input type="hidden" name="fieldType_{$BLOCKID}" id="fieldType_{$BLOCKID}" value="{$SELECTEDVALUE}">
        <input type="hidden" name="selectedfieldtype_{$BLOCKID}" id="selectedfieldtype_{$BLOCKID}" value="">
    </form>
</div>