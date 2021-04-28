{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 ********************************************************************************/

	*}

<script language="JavaScript" type="text/javascript" src="modules/Portal/Portal.js"></script>

{include file="Buttons_List1.tpl"}

<table border=0 cellspacing=0 cellpadding=0 width=98% align=center>
    <tr>
        <td valign=top align=right width=8></td>
        <td class="showPanelBg" valign="top" width="100%" align=center>

            <!-- MySites UI Starts -->
            <br>
            <table border="0" cellpadding="0" cellspacing="0" width="98%" align="center"
                   class="mailClient mailClientBg">
                <tbody>

                <tr>
                    <td colspan="4">


                        <!-- BOOKMARK PAGE -->
                        <div id="portalcont" style="padding:0px 10px 10px 10px; overflow: hidden; width: 98%;">
                            {include file="MySitesContents.tpl"}
                        </div>


                    </td>
                </tr>
                </tbody>
            </table>
            <br><br>
            <div id="editportal_cont" style="position:absolute;width:510px;"></div> {* crmv@170482 *}

        </td>
        <td valign=top align=right width=8></td>
    </tr>
</table>