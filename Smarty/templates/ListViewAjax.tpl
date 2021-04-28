{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 ********************************************************************************/

*}		
<!-- Table to display the mails list -  Starts -->
				<div id="navTemp" style="display:none">
					<span style="float:left">{$ACCOUNT} &gt; {$MAILBOX}
					{if $NUM_EMAILS neq 0}
                                                 {if $NUM_EMAILS neq 1}
                                                        ({$NUM_EMAILS} {$MOD.LBL_MESSAGES})
                                                 {else}
                                                        ({$NUM_EMAILS} {$MOD.LBL_MESSAGE})
                                                 {/if}
                                         {/if}
					</span> <span style="float:right">{$NAVIGATION}</span>	
				</div>
				<span id="{$MAILBOX}_tempcount" style="display:none" >{$UNREAD_COUNT}</span>
				<div id="temp_boxlist" style="display:none">
					<ul style="list-style-type:none;">
					{foreach item=row from=$BOXLIST}
						{foreach item=row_values from=$row}                                                                                                 {$row_values}                                                                                                       {/foreach}                                                                                                          {/foreach}
					</ul>
				</div>
				<div id="temp_movepane" style="display:none">
					<input type="button" name="mass_del" value=" {$MOD.LBL_DELETE} "  class="crmbutton small delete" onclick="mass_delete();"/>
                                        {$FOLDER_SELECT}
				</div>
			<div id="show_msg" class="layerPopup" align="center" style="padding: 5px;font-weight:bold;width: 400px;display:none;z-index:10000"></div>	
                                <form name="massdelete" method="post" onsubmit="VteJS_DialogBox.block();">
                                <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
                                <table cellspacing="1" cellpadding="3" border="0" width="100%" id="message_table">
                                   <tr>
                                <th class="tableHeadBg"><input type="checkbox" name="select_all" value="checkbox"  onclick="toggleSelect(this.checked,'selected_id');"/></th>
                                        {foreach item=element from=$LISTHEADER}
                                                {$element}
                                        {/foreach}
                                   </tr>
                                        {foreach item=row from=$LISTENTITY}
                                                {foreach item=row_values from=$row}
                                                        {$row_values}
                                                {/foreach}
                                        {/foreach}
				</table>
                                </form>
                                <!-- Table to display the mails list - Ends -->