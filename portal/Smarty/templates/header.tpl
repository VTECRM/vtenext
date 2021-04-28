{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{include file="htmlheader.tpl"} {* crmv@168297 *}
<body>	
	<script type="text/javascript">
	{literal}
		function fnMySettings(){
			params = "last_login={$last_login}&support_start_date={$support_start_date}&support_end_date={$support_end_date}";
			window.open("MySettings.php?"+params,"MySettings","menubar=no,location=no,resizable=no,scrollbars=no,status=no,width=400,height=350,left=550,top=200");
		}
	
		// crmv@195833
		function showSearchFormNow(elementid) {
			fnDown(elementid);

			// crmv@173153
			if(jQuery('#'+elementid).is(':visible')) {
				return;
			} else {
				// Squeeze the search div wrapper
				jQuery('#'+elementid).width('100%');
			}
			
			return;
			// crmv@173153e
	
			var url = 'module=HelpDesk&action=SearchForm&ajax=true';
	
			jQuery.ajax({
				url: 'index.php',
				method: 'POST',
				data: url,
				success: function(result) {
					// Set the width of search div wrapper
					jQuery('#'+elementid).width('100%');
					jQuery('#_search_formelements_').html(result);
				}
			});
		}
		// crmv@195833e
		
		{/literal}
	</script>

<!-- crmv@57342e-->
    <!-- Navigation -->
   <!-- crmv@57342 <a id="menu-toggle" href="#" class="btn btn-dark btn-lg toggle"><img src="images/open-menu.png"></a>-->
    <nav id="sidebar-wrapper">
        <ul class="sidebar-nav">
           <!-- <a id="menu-close" href="#" class="btn-light btn-lg pull-right toggle"><img src="images/open-menu.png"></a> -->
            <a id="menu-close" href="#" class="pull-right toggle">
			     <i class="material-icons material-icons-menu icon_highlight_off" style="font-size:40px;"></i>
            </a>

            <!-- crmv@57342 -->
			<li id="logo-small"><a href="{$ENTERPRISE_WEBSITE.0}" target="_blank"><img src="{'login'|get_logo}" class="logo" style="max-height: 130px;max-width: 100px;"/></a></li> {* crmv@126040 *}
            <li class="sidebar-brand">
            {* crmv@167855 *}
            {if $HELPDESK_ENABLED}
            <a class="menu slidemenu-vte-more" href="index.php?module=HelpDesk&action=index&fun=newticket">
				<i class="material-icons icon_default icon_menuvte icon_new_ticket"></i>
				<span class="slidemenu-vte-label-more">{'LBL_NEW_TICKET'|getTranslatedString}</span>
			</a><hr class="hr-vte">
			{/if}
			{* crmv@167855e *}
            	{foreach from=$showmodulemenu item=showmodule}
					<a class='menu {$showmodule.class_css}' href='index.php?module={$showmodule.module}&action=index&onlymine=true'>
						<i class='material-icons icon_default icon_{$showmodule.icon} icon_menuvte' data-first-letter="{$showmodule.first_letter}"></i>
						<span class="{$showmodule.class_css_label}">{$showmodule.module|getTranslatedString}</span>
					</a><hr class='hr-vte'>
				{/foreach}
				<a href="index.php?module=Contacts&action=index&id={$customerid}&profile=yes" class="menu slidemenu-vte">
					<i class='material-icons icon_default icon_menuvte icon_info'></i>
					<span class="slidemenu-vte-label">{'LBL_MODIFY_PROFILE'|getTranslatedString}</span>
				</a><hr class="hr-vte">
       			<a href="index.php?logout=true" class="menu slidemenu-vte">
					<i class='material-icons icon_default icon_menuvte icon_exit_to_app'></i>
					<span class="slidemenu-vte-label">{'LBL_LOG_OUT'|getTranslatedString}</span>
				</a><hr class="hr-vte">
				<div style="height:50px"></div>
            </li>
        </ul>
    </nav>
   
     <div id="page-wrapper">
     	<div class="container-fluid">		

		    <!--  overflow -->
		    <link href="js/mCustomScrollbar/jquery.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
			<script src="js/mCustomScrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
			<script language="javascript" type="text/javascript" src="js/slimscroll/jquery.slimscroll.min.js"></script>
			<link href="js/mCustomScrollbar/VTE.mCustomScrollbar.css" rel="stylesheet" type="text/css" />
		
		    <!-- Custom Menu JavaScript -->
		    <script>
			{literal}
		    //crmv@57342
		    jQuery("#menu-close").click(function(e) {
		        e.preventDefault();
		        jQuery("#sidebar-wrapper").toggleClass("active");
		        jQuery("#page-wrapper").toggleClass("active");
		        
		        
		        if(jQuery( "#sidebar-wrapper" ).hasClass( "active" )){
					jQuery(".material-icons-menu").removeClass("icon_highlight_off");
		        	jQuery(".material-icons-menu").addClass('icon_menu');
		        }else{
					jQuery(".material-icons-menu").removeClass("icon_menu");
		        	jQuery(".material-icons-menu").addClass('icon_highlight_off');
		        }
		    });
			
			  (function() {
			
			    "use strict";
			
			    var toggles = document.querySelectorAll(".c-hamburger");
			
			    for (var i = toggles.length - 1; i >= 0; i--) {
			      var toggle = toggles[i];
			      toggleHandler(toggle);
			    };
			
			    function toggleHandler(toggle) {
			      toggle.addEventListener( "click", function(e) {
			        e.preventDefault();
			        (this.classList.contains("is-active") === true) ? this.classList.remove("is-active") : this.classList.add("is-active");
			      });
			    }
			
			  })();
			
		 	// overflow
				jQuery(document).ready(function (){
					jQuery('.sidebar-nav').slimScroll({
						width: '250px',
						height: '100%',
					})
				});
			{/literal}
			</script>