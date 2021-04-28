{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="include/js/search.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/Merge.js"></script>
<script language="JavaScript" type="text/javascript" src="modules/{$MODULE}/{$MODULE}.js"></script>
{* crmv@31415 - removed func *}
<table align="center" border="0" cellpadding="0" cellspacing="0"
	width="100%">
	<!-- crmv@30683 -->
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top"
				width="100%">
				<!-- crmv@30683 -->
				<div align=center>
					{include file='SetMenu.tpl'}
					{include file='Buttons_List.tpl'}
					{* crmv@30683 crmv@31415 *}
					<table border=0 cellspacing=0 cellpadding=5 width="100%" class="settingsSelUITopLine">
						<tr>
							<td width="50" rowspan="2" valign="top">
								<i class="vteicon md-text md-xlg">person</i>
							</td>
							<td class="heading2" valign="bottom">
								<b> {$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_USERS} </b>
							</td>
							<td rowspan="2" align="right">

								<form id="basicSearch" name="basicSearch" method="post" action="index.php" onSubmit="gVTModule='{$MODULE}'; return callSearch('Basic', '{$FOLDERID}');">
									<input type="hidden" name="searchtype" value="BasicSearch" />
            			            <input type="hidden" name="module" value="{$MODULE}" />
                        			<input type="hidden" name="parenttab" value="{$CATEGORY}" />
            						<input type="hidden" name="action" value="index" />
                        			<input type="hidden" name="query" value="true" />
            						<input type="hidden" id="basic_search_cnt" name="search_cnt" />

            						<div class="form-group basicSearch">
            								<input type="text" class="form-control searchBox" id="basic_search_text" name="search_text" value="{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}" onclick="clearText(this)" onblur="restoreDefaultText(this, '{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}')" /> {* crmv@140887 *}
            								<span class="cancelIcon">
												<i id="basic_search_icn_canc" class="vteicon md-link md-sm" style="display:none" title="Reset" onclick="cancelSearchText('{$APP.LBL_SEARCH_TITLE}{$MODULE|getTranslatedString:$MODULE}')" >cancel</i>&nbsp;
											</span>
											<span class="searchIcon">
            									<i id="basic_search_icn_go" class="vteicon md-link" title="{$APP.LBL_FIND}" onclick="jQuery('#basicSearch').submit();" >search</i>
											</span>
            							</tr>
            						</div>
								</form>
								<div id="basicsearchcolumns" style="display:none;"></div>

							</tr>
							<!-- crmv@30683 -->
						</tr>
						<tr>
							<td valign=top class="small">{$MOD.LBL_USER_DESCRIPTION}</td>
						</tr>
					</table>
					{* crmv@30683e crmv@31415e *}
					<br>

					<!-- DISPLAY -->
					<form action="index.php" method="post" name="EditView" id="form" onsubmit="VteJS_DialogBox.block();">
						<input type='hidden' name='module' value='Users'> <input
							type='hidden' name='action' value='EditView'> <input
							type='hidden' name='return_action' value='ListView'> <input
							type='hidden' name='return_module' value='Users'> <input
							type='hidden' name='parenttab' value='Settings'>
					</form>
					{* crmv@31415 - removed basic search *}

					<!-- Searching UI -->
					<div id="ListViewContents">{include	file="UserListViewContents.tpl"}</div>

			</td>
		</tr>

</table>



</td>
</tr>
</table>
</td>
</tr>
</table>

</div>

</td>
<td valign="top"></td>
</tr>
</tbody>
</form>
</table>

<div id="tempdiv" style="display:block;position:absolute;left:350px;top:200px;"></div>
{literal}
<script>

// crmv@192033
function deleteUser(obj, userid) {
	jQuery('#status').show();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'action=UsersAjax&file=UserDeleteStep1&return_action=ListView&return_module=Users&module=Users&parenttab=Settings&record='+userid,
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#tempdiv").html(result);
			fnvshobj(obj,"tempdiv");
		}
	});
}

function transferUser(del_userid) {
	jQuery('#status').show();
	jQuery("#DeleteLay").hide();
	
	var trans_userid = jQuery('#transfer_user_id').val();
	jQuery.ajax({
		url: 'index.php',
		method: 'POST',
		data: 'module=Users&action=UsersAjax&file=DeleteUser&ajax=true&delete_user_id='+del_userid+'&transfer_user_id='+trans_userid,
		success: function(result) {
			jQuery('#status').hide();
			jQuery("#ListViewContents").html(result);
		}
	});
}
// crmv@192033e

</script>
{/literal}