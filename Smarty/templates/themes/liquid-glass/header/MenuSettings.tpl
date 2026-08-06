{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{* crmv@181170 *}
{assign var=BLOCKS value=SettingsUtils::getBlocks()}
{assign var=FIELDS value=SettingsUtils::getFields()}
{assign var=THEME value=CRMVUtils::getApplicationTheme()}

{SettingsUtils::resetMenuState()}
{* crmv@181170e *}

<ul class="settingsList">

	<li class="subMenu backButton">
		<a href="index.php">
			<div class="row">
				<div class="col-xs-2 vcenter subIcon">
					<i class="vteicon md-link nohover">arrow_back</i>
				</div><!-- 
				 --><div class="col-xs-10 vcenter subLabel">
					<span class="">{'LBL_GO_BACK'|getTranslatedString}</span>
				</div>
			</div>
		</a>
	</li>

	{foreach key=BLOCKID item=BLOCK from=$BLOCKS}
		{if $BLOCK.label neq 'LBL_MODULE_MANAGER'}
			{assign var=blocklabel value=$BLOCK.label|@getTranslatedString:'Settings'}
			{assign var=image value=$BLOCK.image}
			{assign var=imagetype value=$BLOCK.image_type}
							
			<li class="subMenu">
				<a href="#" data-action="submenu-toggle">
					<div class="row">
						<div class="col-xs-2 vcenter subIcon">
							{if !empty($image) && $imagetype eq 'icon'}
								<i class="vteicon nohover">{$image}</i>
							{elseif !empty($image) && $imagetype eq 'image'}
								<img src="{$image}" />
							{/if}
						</div><!-- 
						 --><div class="col-xs-9 vcenter subLabel">
							<span class="">{$blocklabel}</span>
						</div><!--
						--><div class="expandButton"></div>
					</div>
				</a>
				
				<ul style="display:none">
					{foreach item=data from=$FIELDS.$BLOCKID}
						{if $data.link neq ''}
							{assign var=label_original value=$data.name}
							{assign var=label value=$data.name|@getTranslatedString:'Settings'}
							{assign var='settingsTabClass' value=''}
							{assign var="labelFirstLetter" value=$label|substr:0:1|strtoupper}
							
							{if $smarty.request.module_settings eq 'true' && $smarty.request.formodule eq $data.formodule
								&& $smarty.request.action eq $data.action && $smarty.request.module eq $data.module}
								{assign var='settingsTabClass' value='active'}
								{VteSession::set('settings_last_menu', $label_original)} {* crmv@181170 *}
							{elseif $smarty.request.module_settings eq '' && $data.formodule eq ''
								&& $smarty.request.action eq $data.action && $smarty.request.module eq $data.module}
								{assign var='settingsTabClass' value='active'}
								{VteSession::set('settings_last_menu', $label_original)} {* crmv@181170 *}
							{elseif $smarty.request.module_settings eq '' && $data.formodule eq ''
								&& $smarty.request.module eq $data.module && $smarty.request.module neq 'Settings'}
								{assign var='settingsTabClass' value='active'}
								{VteSession::set('settings_last_menu', $label_original)} {* crmv@181170 *}
							{elseif $smarty.session.settings_last_menu eq $data.name}
								{assign var='settingsTabClass' value='active'}
							{/if}
							
							<li class="{$settingsTabClass}">
								<a href="{$data.link}&reset_session_menu=true">
									<div class="row">
										<div class="col-xs-2 vcenter subIcon">
											{$labelFirstLetter}
										</div><!--
										--><div class="col-xs-12 vcenter subLabel">
											{$label}
										</div>
									</div>
								</a>
							</li>
						{/if}
					{/foreach}
				</ul>
				
			</li>
							
		{/if}
	{/foreach}
	
</ul>

<a id="btnScrollTop" class="btn btn-info btn-fab" href="#" style="display:none">
	<i class="vteicon nohover">arrow_upward</i>
</a>

{literal}
<script type="text/javascript">
	var activeItem = jQuery('.settingsList > li.subMenu > ul > li.active');
	if (activeItem.length > 0) {
		activeItem.parent().show();
        activeItem.parent().parent().toggleClass('toggled');
		activeItem.parent().parent().addClass('active');
	}
</script>
{/literal}