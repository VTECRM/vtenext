{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<div style="position:relative;display: block;" class="layerPopup">
    <table border="0" cellpadding="5" cellspacing="0" class="layerHeadingULine">
        <tr>
            <td class="layerPopupHeading" align="left" nowrap>
                {$MOD.DELETE_PICKLIST_VALUES} - {$FIELDLABEL}
            </td>
        </tr>
    </table>

    <table border=0 cellspacing=0 cellpadding=5>
        <tr>
            <td valign=top align=left>
                <select id="delete_availPickList" multiple="multiple" wrap size="20" name="availList"
                        style="width:250px;border:1px solid #666666;font-family:Arial, Helvetica, sans-serif;font-size:11px;">
                    {foreach key=pick_realaval item=pick_val from=$PICKVAL}
                        <option value="{$pick_realaval}">{$pick_val}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td nowrap>
                <b>{$MOD.LBL_REPLACE_WITH}</b>&nbsp;
                <select id="replace_picklistval" name="replaceList"
                        style="border:1px solid #666666;font-family:Arial, Helvetica, sans-serif;font-size:11px;">
                    {foreach key=pick_realaval item=pick_val from=$PICKVAL}
                        <option value="{$pick_realaval}">{$pick_val}</option>
                    {/foreach}
                    {if $NONEDITPICKLIST_PRESENCE eq 'true'}
                        {foreach key=nonedit_realaval item=nonedit from=$NONEDITPICKLIST}
                            <option value="{$nonedit_realaval}">{$nonedit}</option>
                        {/foreach}
                    {/if}
                </select>
            </td>
        </tr>
        <tr>
            <td valign=top align=left>
                <input type="button" value="{$APP.LBL_DELETE_BUTTON_LABEL}" name="del" class="crmButton small delete"
                       onclick="validateDelete('{$FIELDNAME}','{$MODULE}');">
                <input type="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" name="cancel" class="crmButton small cancel"
                       onclick="fnhide('actiondiv');">
            </td>
        </tr>

        {if is_array($NONEDITPICKLIST)}
            <tr>
                <td colspan=3>
                    <table border=0 cellspacing=0 cellpadding=0 width=100%>
                        <tr>
                            <td><b>{$MOD.LBL_NON_EDITABLE_PICKLIST_ENTRIES} :</b></td>
                        </tr>
                        <tr>
                            <td>
                                <select id="nonEditablePicklistVal" name="nonEditablePicklistVal" multiple="multiple"
                                        wrap size="5" style="width: 100%">
                                    {foreach item=nonedit from=$NONEDITPICKLIST}
                                        <option value="{$nonedit}" disabled>{$nonedit}</option>
                                    {/foreach}
                                </select>
                    </table>
                </td>
            </tr>
        {/if}
    </table>
</div>