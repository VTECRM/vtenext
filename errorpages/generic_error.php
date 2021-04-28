<html>
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
<head>
	<meta charset="utf-8">
	
	<title><?php echo $pageTitle; ?></title>
			
	<link rel="shortcut icon" href="themes/logos/VTE_favicon.ico"> <!-- don't change this, we don't have VTE functions here -->
	
	<link rel="stylesheet" type="text/css" href="themes/softed/vte_bootstrap.css">
	<link rel="stylesheet" type="text/css" href="themes/softed/style.css">
	<link rel="stylesheet" type="text/css" href="themes/softed/recover.css">
	
</head>
<body>

<div id="main-container" class="container">
	<div class="row">
		<div class="col-xs-offset-1 col-xs-10">
				
			<div id="content" class="col-xs-12">
				<div id="content-cont" class="col-xs-12">
					<div id="content-inner-cont" class="col-xs-12">
							
						<div class="col-xs-12 content-padding">	
							<div class="col-xs-6 nopadding vcenter text-left">
								<h2><?php echo $errorTitle; ?></h2>
							</div><!--
							--><div class="col-xs-6 nopadding vcenter text-right">
								<a href="<?php echo $enterprise_website[0]; ?>" target="_blank">
									<img src="include/install/images/vtenext.png" />
								</a>
							</div>
						</div>

						<div class="col-xs-12 content-padding">	

							<table class="table borderless">
								<tr>
									<td align="left">
										<div style="width:90%" class="text-left">
											<p><?php echo $errorDescription; ?></p>
										</div>
									</td>
								</tr>
								<tr style="height:45px"><td colspan="2"></td></tr>
							</table>
						</div>

					</div>
				</div>
			</div>
	
			<div id="footer" class="col-xs-12 content-padding">
				<div id="footer-inner" class="col-xs-12 content-padding text-center">
					<div class="spacer-50"></div>
				</div>
			</div>
				
		</div>
	</div>
</div>

</body>
</html>