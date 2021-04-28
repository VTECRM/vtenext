{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@197575 crmv@205899 *}
<!DOCTYPE html>
<html>
	<head>
		<link href="include/js/grapesjs/css/roboto-slab.css" rel="stylesheet" />
		<link href="include/js/grapesjs/css/grapes.min.css" rel="stylesheet" />
		<link href="include/js/grapesjs/grapesjs-preset-newsletter.css" rel="stylesheet" />
		{literal}
		<style>
			body {
				margin: 0;
				padding: 0;
			}

			/* We can remove the border we've set at the beginnig */
			#gjs {
				border: none;
			}
			/* Theming */

			/* Primary color for the background */
			.gjs-one-bg {
				background-color: #16556F;	
			}

			/* Secondary color for the text color */
			.gjs-two-color {
				color: rgba(255, 255, 255, 0.7);
			}

			/* Tertiary color for the background */
			.gjs-three-bg {
				background-color: #ec5896;
				color: white;
			}

			.gjs-btnt.gjs-pn-active,
			.gjs-color-active,
			.gjs-pn-btn.gjs-pn-active,
			.gjs-pn-btn:active,
			.gjs-block:hover {
				color: #d38600; 
			}
			#gjs-pn-views .gjs-pn-active {
				color: rgba(255, 255, 255, 0.9);
				border-bottom: 2px solid #d38600;
				border-radius: 0; 
			}

			.template-vars-input {
				background-color: white;
				max-width: 120px;
			}
		</style>
		{/literal}
	</head>
	<body>
		{if $load_header}
			{include file="modules/SDK/src/Grapes/GrapesHeader.tpl"}
		{/if}

		{include file="modules/SDK/src/Grapes/GrapesBody.tpl"}
	</body>
</html>