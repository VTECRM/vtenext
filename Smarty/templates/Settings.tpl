{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{include file="Buttons_List1.tpl"}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">
    <tbody>
    <tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 10px;" valign="top" width="100%">
            <br>
            <div align=center>
                <table border=0 cellspacing=0 cellpadding=20 width=90% class="settingsUI">
                    <tr>
                        <td>
                            <table border=0 cellspacing=0 cellpadding=0 width=100%>
                                {foreach key=BLOCKID item=BLOCKLABEL from=$BLOCKS}
                                    {if $BLOCKLABEL neq 'LBL_MODULE_MANAGER'}
                                        <tr>
                                            <td class="settingsTabHeader">
                                                {$MOD.$BLOCKLABEL}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="settingsIconDisplay small">
                                                <table border=0 cellspacing=0 cellpadding=10 width=100%>
                                                    <tr>
                                                        {foreach item=data from=$FIELDS.$BLOCKID name=itr}
                                                        <td width=25% valign=top>
                                                            {if $data.name eq ''}
                                                                &nbsp;
                                                            {else}
                                                                <table border=0 cellspacing=0 cellpadding=5 width=100%>
                                                                    <tr>
                                                                        {assign var=label value=$data.name|@getTranslatedString:'Settings'}
                                                                        {assign var=count value=$smarty.foreach.itr.iteration}
                                                                        <td rowspan=2 valign=top>
                                                                            <a href="{$data.link}">
                                                                                <img src="{$data.icon|@vtecrm_imageurl:$THEME}"
                                                                                     alt="{$label}" width="48"
                                                                                     height="48" border=0
                                                                                     title="{$label}">
                                                                            </a>
                                                                        </td>
                                                                        <td class=big valign=top>
                                                                            <a href="{$data.link}">
                                                                                {$label}
                                                                            </a>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        {assign var=description value=$data.description|@getTranslatedString:'Settings'}
                                                                        <td class="small" valign=top>
                                                                            {$description}
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            {/if}
                                                        </td>
                                                        {if $count mod $NUMBER_OF_COLUMNS eq 0}
                                                    </tr>
                                                    <tr>
                                                        {/if}
                                                        {/foreach}
                                                </table>
                                            </td>
                                        </tr>
                                    {/if}
                                {/foreach}
                            </table>
                        </td>
                    </tr>
                </table>
        </td>
    </tr>
</table>