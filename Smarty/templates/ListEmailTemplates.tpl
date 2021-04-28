{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script>
    function ifselected() {ldelim}


        var sel = document.massdelete.selected_id.length;
        var returnval = false;

        for (i = 0; i < sel; i++) {ldelim}

            if (document.massdelete.selected_id[i].checked == true) {ldelim}
                returnval = true;
                break;
                {rdelim}

            {rdelim}


        if (returnval == true) {ldelim}
            document.getElementById("myProfile").style.display = "none";
            {rdelim} else {ldelim}
            document.getElementById("myProfile").style.display = "block";
            {rdelim}

        {rdelim}


    function massDelete() {ldelim}

        x = document.massdelete.selected_id.length;
        idstring = "";

        if (x == undefined) {ldelim}

            if (document.massdelete.selected_id.checked) {ldelim}
                document.massdelete.idlist.value = document.massdelete.selected_id.value + ';';


                xx = 1;
                {rdelim} else {ldelim}
                document.massdelete.profile.style.display = "none";
                alert("{$APP.SELECT_ATLEAST_ONE}");
                return false;
                {rdelim}
            {rdelim} else {ldelim}
            xx = 0;
            for (i = 0; i < x; i++) {ldelim}
                if (document.massdelete.selected_id[i].checked) {ldelim}
                    idstring = document.massdelete.selected_id[i].value + ";" + idstring
                    xx++
                    {rdelim}
                {rdelim}
            if (xx != 0) {ldelim}
                document.massdelete.idlist.value = idstring;
                {rdelim} else {ldelim}
                alert("{$APP.SELECT_ATLEAST_ONE}");
                return false;
                {rdelim}
            {rdelim}

        {* crmv@37290 *}
        var res = getFile('index.php?module=Settings&action=deleteemailtemplate&return_module=Settings&return_action=listemailtemplates&mode=check&idlist=' + idstring);
        if (res == 'SUCCESS') {ldelim}
            if (confirm("{$APP.DELETE_CONFIRMATION} " + xx + " {$APP.RECORDS}")) {ldelim}
                document.massdelete.action = "index.php?module=Settings&action=deleteemailtemplate&return_module=Settings&return_action=listemailtemplates";
                {rdelim} else {ldelim}
                return false;
                {rdelim}
            {rdelim} else {ldelim}
            if (confirm("{$MOD.LBL_ERR_DELETE_MAIL_TEMPLATE_NEWSLETTER}")) {ldelim}
                document.massdelete.action = "index.php?module=Settings&action=deleteemailtemplate&return_module=Settings&return_action=listemailtemplates";
                {rdelim} else {ldelim}
                return false;
                {rdelim}
            {rdelim}
        {* crmv@37290 *}
        {rdelim}
</script>
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
    <tbody>
    <tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
            <div align=center>

                {include file='SetMenu.tpl'}
                {include file='Buttons_List.tpl'} {* crmv@30683 *}
                <!-- DISPLAY -->
                <table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
                    <form name="massdelete" method="POST" onsubmit="VteJS_DialogBox.block();">
                        <input name="idlist" type="hidden">
                        <input name="module" type="hidden" value="Settings">
                        <input name="action" type="hidden" value="deleteemailtemplate">
                        <tr>
                            <td width=50 rowspan=2 valign=top><img src="{'ViewTemplate.gif'|resourcever}" border=0></td>
                            <td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$UMOD.LBL_EMAIL_TEMPLATES} </b>
                            </td>
                        </tr>
                        <tr>
                            <td valign=top class="small">{$UMOD.LBL_EMAIL_TEMPLATE_DESC}</td>
                        </tr>
                </table>

                <br>
                <table border=0 cellspacing=0 cellpadding=10 width=100%>
                    <tr>
                        <td>

                            <table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
                                <tr>
                                    <td class="big"><strong>{$UMOD.LBL_EMAIL_TEMPLATES}</strong></td>
                                    <td class="small" align=right>&nbsp;
                                    </td>
                                </tr>
                            </table>

                            <table border=0 cellspacing=0 cellpadding=5 width=100% class="listTableTopButtons">
                                <tr>
                                    <td class=small><input type="submit" value="{$UMOD.LBL_DELETE}"
                                                           onclick="return massDelete();"
                                                           class="crmButton delete small"></td>
                                    <td class=small align=right id="new_template">
                                        <div id="myProfile"><input class="crmButton create small" type="submit"
                                                                   value="{$UMOD.LBL_NEW_TEMPLATE}" name="profile"
                                                                   class="classBtn"
                                                                   onclick="this.form.action.value='createemailtemplate';">
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <table border="0" cellspacing="0" cellpadding="5" width="100%" class="listTable">
                                <tr>
                                    <td width="5%" class="colHeader small">#</td>
                                    <td width="5%" class="colHeader small">{$UMOD.LBL_LIST_SELECT}</td>
                                    <td width="30%" class="colHeader small">{$UMOD.LBL_EMAIL_TEMPLATE}</td>
                                    <td width="60%" class="colHeader small">{$UMOD.LBL_DESCRIPTION}</td>
                                    <!--<td width="20%" class="colHeader small">{$UMOD.LBL_TEMPLATE_TOOLS}</td>-->
                                </tr>
                                {foreach name=emailtemplate item=template from=$TEMPLATES}
                                    <tr>
                                        <td class="listTableRow small"
                                            valign=top>{$smarty.foreach.emailtemplate.iteration}</td>
                                        <td class="listTableRow small" valign=top><input type="checkbox"
                                                                                         name="selected_id"
                                                                                         value="{$template.templateid}"
                                                                                         onClick="ifselected(); "
                                                                                         class=small></td>
                                        <td class="listTableRow small" valign=top>
                                            <a href="index.php?module=Settings&action=detailviewemailtemplate&parenttab=Settings&templateid={$template.templateid}"><b>{$template.templatename}</b></a>
                                        </td>
                                        <td class="listTableRow small" valign=top>{$template.description}&nbsp;</td>
                                        <!--<td class="listTableRow small" valign=top>
							<a href="index.php?module=Settings&action=detailviewemailtemplate&parenttab=Settings&templateid={$template.templateid}">{$UMOD.LNK_SAMPLE_EMAIL}</a>
						</td>-->
                                    </tr>
                                {/foreach}
                            </table>
                            {include file="Settings/ScrollTop.tpl"}
                        </td>
                    </tr>
                </table>


        </td>
    </tr>
</table>
</td>
</tr>
</form>
</table>

</div>
</td>
<td valign="top"></td>
</tr>
</tbody>
</table>