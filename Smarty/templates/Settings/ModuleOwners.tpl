{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>
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
                    <tr>
                        <td width=50 rowspan=2 valign=top><img src="{'assign.gif'|resourcever}" alt="{$MOD.LBL_USERS}"
                                                               width="48" height="48" border=0 title="{$MOD.LBL_USERS}">
                        </td>
                        <td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_MODULE_OWNERS} </b>
                        </td> <!-- crmv@30683 -->
                    </tr>
                    <tr>
                        <td valign=top class="small">{$MOD.LBL_MODULE_OWNERS_DESCRIPTION}</td>
                    </tr>
                </table>
                <br>
                <table border=0 cellspacing=0 cellpadding=10 width=100%>
                    <tr>
                        <td>

                            <div id="module_list_owner">
                                {include file='Settings/ModuleOwnersContents.tpl'}
                            </div>

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


{literal}
    <script>

        // crmv@192033
        function assignmodulefn(mode) {
            jQuery("#status").show();
            var urlstring = '';

            for (var i = 0; i < document.support_owners.elements.length; i++) {
                if (document.support_owners.elements[i].name != 'button') {
                    urlstring += '&' + document.support_owners.elements[i].name + '=' + document.support_owners.elements[i].value;
                }
            }

            jQuery.ajax({
                url: 'index.php',
                method: 'POST',
                data: urlstring + '&list_module_mode=' + mode + '&file_mode=ajax',
                success: function (result) {
                    jQuery("#status").hide();
                    jQuery("#module_list_owner").html(result);
                }
            });
        }

        // crmv@192033e

    </script>
{/literal}