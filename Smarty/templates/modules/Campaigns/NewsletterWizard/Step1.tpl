{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div id="nlWizStep1" style="">
	<div style="display:flex;flex-direction:row;">
		<div style="max-width:150px;padding-left:10px;padding-right:25px;">
			<ul class="nav nav-pills nav-pills-newsletter">
				{foreach key=TMOD item=TMODINFO from=$TARGET_MODS}
					<li id="li_mod_{$TMOD}" data-mod="{$TMOD}" class="nav-pills-item {if $TMOD eq 'Contacts'}active{/if}">
						<a href="javascript:void(0);" onclick="setPill('{$TMOD}');">
							<i class="icon-module icon-{$TMOD|strtolower}" data-first-letter="{$TMOD|strtolower|substr:0:1}" style="display: block; font-size: 30px; padding: 15px 0;"></i>
							{$TMOD|getTranslatedString:$TMOD}
						</a>
					</li>
				{/foreach}
			</ul>
		</div>
		<div style="flex:1">
			{$MOD.WhichRecipientsToAdd}
			
			<div class="dvtCellInfo" style="display:none;width:200px">
				<select class="detailedViewTextBox" id="nlw_targetTypeSel" onchange="nlwChangeTargetSel()">
					<option value="">{$APP.LBL_SELECT}</option>
					{foreach key=TMOD item=TMODINFO from=$TARGET_MODS}
						<option value="{$TMOD}">{$TMOD|getTranslatedString:$TMOD}</option>
					{/foreach}
				</select>
			</div>

			{literal}
			<script type="text/javascript">

				function setPill(mod){
					
					jQuery('[id^="li_mod"]').each(function(){
						jQuery(this).removeClass('active');
						
						if(jQuery(this).attr('data-mod') == mod){
							jQuery(this).addClass('active');
						}
					});
					
					jQuery('#nlw_targetTypeSel').val(mod);
					nlwChangeTargetSel();
				}

				jQuery(document).ready(function() {
					jQuery('#nlw_targetTypeSel').val('Contacts');
					nlwChangeTargetSel();
				});
			</script>
			{/literal}

			<div class="divider"></div>

			{foreach key=TMOD item=TMODINFO from=$TARGET_MODS}
				{if $TMOD eq 'Targets'}
					{assign var=LISTIDTARGETS value=$TMODINFO.listid}
				{/if}
				<div class="nlWizTargetList" id="nlw_targetList_{$TMOD}" style="display:none">
				{$TMODINFO.list}
				</div>
			{/foreach}

			<div id="nlw_targetsBoxCont">
				<p><b>{$MOD.SelectedRecipients}</b></p>
				<div id="nlWizTargetsBox"></div>
				{if $SEL_TARGETS neq '' && count($SEL_TARGETS) > 0}
				<script type="text/javascript">
					{foreach item=TGT from=$SEL_TARGETS}
						nlwRecordSelect('{$LISTIDTARGETS}', 'Targets', '{$TGT.crmid}', '{$TGT.entityname}');
					{/foreach}
				</script>
				{/if}
			</div>
		</div>
	</div>
</div>