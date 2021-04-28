{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
  * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
*}

<div id="roleLay" style="display:block;" class="layerPopup">
    <form action="index.php" name="index" onSubmit="if(verify_data(index) == true) gotoUpdateListPrice('{$RETURN_ID}','{$PRICEBOOK_ID}','{$PRODUCT_ID}'); else document.getElementById('roleLay').style.display='inline'; return false;" >
        <input type="hidden" name="module" value="Products">
        <input type="hidden" name="action" value="UpdateListPrice">
        <input type="hidden" name="record" value="{$RETURN_ID}">
        <input type="hidden" name="pricebook_id" value="{$PRICEBOOK_ID}">
        <input type="hidden" name="product_id" value="{$PRODUCT_ID}">
        <table border=0 cellspacing=0 cellpadding=5 width=100% class=layerHeadingULine>
            <tr>
                <td class="layerPopupHeading" align="left">{$MOD.LBL_EDITLISTPRICE}</td>
                <td align="right" class="small"><img src="{'close.gif'|resourcever}" border=0 alt="{$APP.LBL_CLOSE}" title="{$APP.LBL_CLOSE}" style="cursor:pointer" onClick="document.getElementById('editlistprice').style.display='none';"></td>
            </tr>
        </table>
        <table border=0 cellspacing=0 cellpadding=5 width=95% align=center>
            <tr>
                <td class="small">
                    <table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
                        <tr>
                            <td width="50%" class="cellLabel small"><b>{$MOD.LBL_EDITLISTPRICE}</b></td>
                            <td width="50%" class="cellText small"><input class="dataInput" type="text" id="list_price" name="list_price" value="{$LIST_PRICE}" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
            <tr>
                <td colspan="3" align="center" class="small">
                    <input type="submit" name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmbutton small save">
                    <input title="{$APP.LBL_CANCEL_BUTTON_LABEL}" accessKey="{$APP.LBL_CANCEL_BUTTON_LABEL}" class="crmbutton small cancel" onClick="document.getElementById('editlistprice').style.display='none';" type="button" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}'">
                </td>
            </tr>
        </table>
    </form>
</div>