{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

<div id="rightPanel">

	<div class="vteRightHeader">
		<ul class="profileWrapper">
			<li class="profileInner">
				{if $HEADER_OVERRIDE.user_icon}
					{$HEADER_OVERRIDE.user_icon}
				{else}
					<a href="#" class="profile">
						<span>{$CURRENT_USER_ID|getUserAvatarImg}</span>
					</a>
				{/if}
				<ul class="profileMenu">
					{if $smarty.session.MorphsuitZombie eq false && $IS_ADMIN eq '1'}
						<li class="shrink">
							{if $HEADER_OVERRIDE.settings_icon}
								{$HEADER_OVERRIDE.settings_icon}
							{else}
								<a href="index.php?module=Settings&amp;action=index&amp;parenttab=Settings&amp;reset_session_menu_tab=true">
									<div class="row">
										<div class="col-xs-2">
											<i class="vteicon vcenter">settings_applications</i>
										</div>
										<div class="col-xs-10">
											<div class="vcenter">{'Settings'|getTranslatedString:'Settings'}</div>
										</div>
									</div>
								</a>
							{/if}
						</li>
					{/if}
					<li>
						<a href="index.php?module=Users&action=DetailView&record={$CURRENT_USER_ID}&modechk=prefview">
							<div class="row">
								<div class="col-xs-2">
									<i class="vteicon vcenter">person</i>
								</div>
								<div class="col-xs-10">
									<div class="vcenter">{$APP.LBL_PREFERENCES}</div>
								</div>
							</div>
						</a>
					</li>
					{if $HEADERLINKS}
						{foreach item=HEADERLINK from=$HEADERLINKS}
							{assign var="headerlink_href" value=$HEADERLINK->linkurl}
							{assign var="headerlink_label" value=$HEADERLINK->linklabel}
							{assign var="headerlink_icon" value=$HEADERLINK->linkicon}
							{if $headerlink_label eq ''}
								{assign var="headerlink_label" value=$headerlink_href}
							{else}
								{assign var="headerlink_label" value=$headerlink_label|@getTranslatedString:$HEADERLINK->module()}
							{/if}
							<li>
								<a href="{$headerlink_href}">
									<div class="row">
										<div class="col-xs-2">
											<i class="vteicon vcenter">{$headerlink_icon}</i>
										</div>
										<div class="col-xs-10">
											<div class="vcenter">{$headerlink_label}</div>
										</div>
									</div>
								</a>
							</li>
						{/foreach}
					{/if}
					<li>
						<a href="index.php?module=Users&action=Logout">
							<div class="row">
								<div class="col-xs-2">
									<i class="vteicon vcenter">power_settings_new</i>
								</div>
								<div class="col-xs-10">
									<div class="vcenter">{$APP.LBL_LOGOUT}</div>
								</div>
							</div>
						</a>
					</li>
				</ul>	
			</li>
		</ul>
	</div>
	
	<ul class="menuList">
		<li><ul id="Buttons_List_Fixed" class="menuListSection"></ul></li>
		{* crmv@75301 *}
		{if $HEADER_OVERRIDE.post_menu_bar}
			<li>
				{$HEADER_OVERRIDE.post_menu_bar}
			</li>
		{/if}
		{if $HEADER_OVERRIDE.post_primary_bar}
			<li>
				{$HEADER_OVERRIDE.post_primary_bar}
			</li>
		{/if}
		{if $HEADER_OVERRIDE.post_secondary_bar}
			<li>
				{$HEADER_OVERRIDE.post_secondary_bar}
			</li>
		{/if}
		{* crmv@75301e *}
	</ul>
	
</div>