{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<div class="wrapper">
	<h1 class="title">{'LBL_WELCOME'|getTranslatedString}<br><small>{'LBL_WELCOME_SERVICE'|getTranslatedString}</small></h1>
	<ul>
		<li>
			<h3><a class="vocemenu" href=index.php?module=Contacts&action=index&id={$CUSTERMID}&profile=yes>{'LBL_MODIFY_PROFILE'|getTranslatedString}</a></h3>
		</li>
		<p>
			<img alt="" src="images/black-line.gif">
		</p>
		<li>
			<h3><a class="vocemenu" href="index.php?module=HelpDesk&action=index&fun=newticket">{'LBL_NEW_TICKET'|getTranslatedString}</a></h3>
		</li>	
		{foreach from=$SHOWMODULE item=MODULE}
			{if $MODULE != "Contacts" && $MODULE != "Documents" && $MODULE != "Potentials"} <!-- crmv@5946 -->
				<p>
					<img alt="" src="images/black-line.gif">
				</p>
				<li>
					<h3><a class="vocemenu" href='index.php?module={$MODULE}&action=index&onlymine=true'>{$MODULE|getTranslatedString}</a></h3>
				</li>
			{/if}
		{/foreach}
			
	</ul>
</div>

<script type="text/javascript">
	jQuery("#sidebar-wrapper").toggleClass("active");
	jQuery("#page-wrapper").toggleClass("active");
</script>