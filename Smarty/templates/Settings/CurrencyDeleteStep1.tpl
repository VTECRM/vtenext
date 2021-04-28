<div id="CurrencyDeleteLay"  class="layerPopup">
    <form name="newCurrencyForm" action="index.php" style="margin=0;" onsubmit="VteJS_DialogBox.block();">
    <input type="hidden" name="module" value="Settings">
    <input type="hidden" name="action" value="CurrencyDelete">
    <input type="hidden" name="delete_currency_id" value="'.$delete_currency_id.'">
    <table width="100%" border="0" cellpadding="3" cellspacing="0" class="layerHeadingULine">
        <tr>
            <td class="layerPopupHeading"  align="left" width="60%">{$MOD.LBL_DELETE_CURRENCY}</td>
            <td align="right" width="40%"><img src="{'close.gif'|resourcever}" border=0 alt="{$APP.LBL_CLOSE}" title="{$APP.LBL_CLOSE}" style="cursor:pointer;" onClick="document.getElementById('CurrencyDeleteLay').style.display='none';"></td>
        </tr>
        <table>
            <table border=0 cellspacing=0 cellpadding=5 width=95% align=center>
                <tr>
                    <td class=small >
                        <table border=0 celspacing=0 cellpadding=5 width=100% align=center bgcolor=white>
                            <tr>
                                <td width="50%" class="cellLabel small"><b>{$MOD.LBL_CURRDEL}</b></td>
                                <td width="50%" class="cellText small"><b>{$TRANSLATED_CURRENCY}</b></td>
                            </tr>
                            <tr>
                                <td class="cellLabel small"><b>{$MOD.LBL_TRANSCURR}</b></td>
                                <td class="cellText small">
                                    <select class="select small" name="transfer_currency_id" id="transfer_currency_id">
                                        {foreach item=arr from=$ALL_CURRENCIES}
                                            <option value="{$arr.currencyid}">{$arr.currencyname}</option>
                                        {/foreach}
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table border=0 cellspacing=0 cellpadding=5 width=100% class="layerPopupTransport">
                <tr>
                    <td align="center"><input type="button" onclick="VTE.Settings.Currency.transferCurrency({$DELETE_CURRENCY_ID})" name="Delete" value="{$APP.LBL_SAVE_BUTTON_LABEL}" class="crmbutton small save">
                    </td>
                </tr>
            </table>
    </form>
</div>