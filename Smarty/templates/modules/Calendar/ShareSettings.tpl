{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@187823 *}

{if $MODE eq 'detail'}
	<tr valign="top">
		<td width="50%">
			{include file='FieldHeader.tpl' label='LBL_CALENDAR_SHARING'|getTranslatedString:'Calendar'}
			<div class="dvtCellInfo">
				{foreach key=USERID item=USER from=$SHAREDUSERS}
					{if $USERID != ''}
						{$USER}<br />
					{/if}
				{/foreach}
			</div>
		</td>
		<td width="50%">
			{include file='FieldHeader.tpl' label='LBL_CALENDAR_SHARING_OCC'|getTranslatedString:'Users'}
			<div class="dvtCellInfo">
				{foreach key=USERID item=USER from=$SHAREDUSERSOCC}
					{if $USERID != ''}
						{$USER}<br />
					{/if}
				{/foreach}
			</div>
		</td>
	</tr>
	<tr valign="top">
		<td>
			{include file='FieldHeader.tpl' label='LBL_CALENDAR_USERS_SHOWN'|getTranslatedString:'Users'}
			<div class="dvtCellInfo">
				{foreach key=USERID item=USER from=$SHOWNUSERS}
					{if $USERID != ''}
						{$USER.name}<br />
					{/if}
				{/foreach}
			</div>
		</td>
		<td></td>
	</tr>
{elseif $MODE != 'create'}
	<input type="hidden" name="shar_userid" id="shar_userid" >
	<input type="hidden" name="sharocc_userid" id="sharocc_userid" >
	<input type="hidden" name="shown_userid" id="shown_userid" >
	<table border=0 cellspacing=0 cellpadding=5 width=100% align=center>
		<tr>
			<td><b>{'LBL_CALENDAR_SHARING'|getTranslatedString:'Calendar'}</b></td>
		</tr>
		<tr>
			<td align="center"><!-- Calendar sharing UI-->
			<DIV id="cal_shar" style="display: block; width: 100%;">
			<table border=0 cellspacing=0 cellpadding=2 width=100%>
				<tr>
					<td valign=top>
					<table border=0 cellspacing=0 cellpadding=2 width=100%>
						<tr>
							<td><b>{'LBL_AVL_USERS'|getTranslatedString:'Calendar'}</b></td>
							<td>&nbsp;</td>
							<td><b>{'LBL_SEL_USERS'|getTranslatedString:'Calendar'}</b></td>
						</tr>
						<tr>
							<td width=40% align=center valign=top>
								<div class="dvtCellInfo">
									<select name="available_users_sharing" id="available_users_sharing" class="detailedViewTextBox" size=5 multiple style="height: 70px;">
									{foreach key=USERID item=USER from=$SHAREDUSERS_LIST}
										{if $USERID != ''}
											<option value="{$USERID}">{$USER}</option>
										{/if}
									{/foreach}
									</select>
								</div>
							</td>
							<td width=20% align=center valign=middle>
								<input type=button value="{'LBL_ADD_BUTTON'|getTranslatedString:'Calendar'} &gt;&gt;" class="crmbutton small edit" style="width: 100%" onClick="incUserAndAlign('available_users_sharing','selected_users_sharing')"><br/><br />
								<input type=button value="&lt;&lt; {'LBL_RMV_BUTTON'|getTranslatedString:'Calendar'}" class="crmbutton small edit" style="width: 100%" onClick="rmvUserAndAlign('selected_users_sharing')"></td>
							<td>
								<div class="dvtCellInfo">
									<select name="selected_users_sharing" id="selected_users_sharing" class="detailedViewTextBox" size=5 multiple style="height: 70px;">
									{foreach key=USERID item=USER from=$SHAREDUSERS}
										{if $USERID != ''}
											<option value="{$USERID}">{$USER}</option>
										{/if}
									{/foreach}
									</select>
								</div>
							<td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			</div>
			</td>
		</tr>
		
		<tr>
			<td><b>{'LBL_CALENDAR_SHARING_OCC'|getTranslatedString:'Users'}</b></td>
		</tr>
		<tr>
			<td align="center">
			<DIV id="cal_shar2" style="display: block; width: 100%;">
			<table border=0 cellspacing=0 cellpadding=2 width=100%>
				<tr>
					<td valign=top>
					<table border=0 cellspacing=0 cellpadding=2 width=100%>
						<tr>
							<td><b>{'LBL_AVL_USERS'|getTranslatedString:'Calendar'}</b></td>
							<td>&nbsp;</td>
							<td><b>{'LBL_SEL_USERS'|getTranslatedString:'Calendar'}</b></td>
						</tr>
						<tr>
							<td width=40% align=center valign=top>
								<div class="dvtCellInfo">
									<select name="available_users_sharing_occ" id="available_users_sharing_occ" class="detailedViewTextBox" size=5 multiple style="height: 70px; width: 100%">
									{foreach key=USERID item=USER from=$SHAREDUSERS_LIST}
										{if $USERID != ''}
											<option value="{$USERID}">{$USER}</option>
										{/if}
									{/foreach}
									</select>
								</div>
							</td>
							<td width=20% align=center valign=middle>
								<input type=button value="{'LBL_ADD_BUTTON'|getTranslatedString:'Calendar'} &gt;&gt;" class="crmbutton small edit" style="width: 100%" onClick="incUserAndAlign('available_users_sharing_occ','selected_users_sharing_occ')"><br/><br />
								<input type=button value="&lt;&lt; {'LBL_RMV_BUTTON'|getTranslatedString:'Calendar'}" class="crmbutton small edit" style="width: 100%" onClick="rmvUserAndAlign('selected_users_sharing_occ')"></td>
							<td>
								<div class="dvtCellInfo">
									<select name="selected_users_sharing_occ" id="selected_users_sharing_occ" class="detailedViewTextBox" size=5 multiple style="height: 70px; width: 100%">
									{foreach key=USERID item=USER from=$SHAREDUSERSOCC}
										{if $USERID != ''}
											<option value="{$USERID}">{$USER}</option>
										{/if}
									{/foreach}
									</select>
								</div>
							<td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			</div>
			</td>
		</tr>
		
		<tr>
			<td><b>{'LBL_CALENDAR_USERS_SHOWN'|getTranslatedString:'Users'}</b></td>
		</tr>
		<tr>
			<td align="center"><!-- Calendar sharing UI-->
			<DIV id="cal_shar3" style="display: block; width: 100%;">
			<table border=0 cellspacing=0 cellpadding=2 width=100%>
				<tr>
					<td valign=top>
					<table border=0 cellspacing=0 cellpadding=2 width=100%>
						<tr>
							<td><b>{'LBL_AVL_USERS'|getTranslatedString:'Calendar'}</b></td>
							<td>&nbsp;</td>
							<td><b>{'LBL_SEL_USERS'|getTranslatedString:'Calendar'}</b></td>
						</tr>
						<tr>
							<td width=40% align=center valign=top>
								<div class="dvtCellInfo">
									<select name="available_users_shown" id="available_users_shown" class="detailedViewTextBox" size=5 multiple style="height: 70px; width: 100%">
									{foreach key=USERID item=USER from=$SHOWNUSERS_LIST}
										{if $USERID != ''}
											<option value="{$USERID}">{$USER}</option>
										{/if}
									{/foreach}
									</select>
								</div>
							</td>
							<td width=20% align=center valign=middle>
								<input type=button value="{'LBL_ADD_BUTTON'|getTranslatedString:'Calendar'} &gt;&gt;" class="crmbutton small edit" style="width:100%;" onClick="incUser('available_users_shown','selected_users_shown')"><br/><br />
								<input type=button value="&lt;&lt; {'LBL_RMV_BUTTON'|getTranslatedString:'Calendar'}" class="crmbutton small edit" style="width:100%;" onClick="rmvUser('selected_users_shown')"></td>
							<td>
								<div class="dvtCellInfo">
									<select name="selected_users_shown" id="selected_users_shown" class="detailedViewTextBox" size=5 multiple style="height: 70px; width: 100%">
									{foreach key=USERID item=USER from=$SHOWNUSERS}
										{if $USERID != ''}
											<option value="{$USERID}">{$USER.name}</option>
										{/if}
									{/foreach}
									</select>
								</div>
							<td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			</div>
			</td>
		</tr>
	</table>
	
	<script type="text/javascript">
		alignCalSharing();
	</script>
{/if}