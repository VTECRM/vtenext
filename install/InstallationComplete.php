<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo $enterprise_mode. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . $installationStrings['LBL_FINISH']?></title>
	<link REL="SHORTCUT ICON" HREF="<?php echo get_logo_install('favicon'); ?>">	<!-- crmv@18123 -->
	<link href="themes/softed/vte_bootstrap.css" rel="stylesheet" type="text/css">
	<link href="themes/softed/style.css" rel="stylesheet" type="text/css">
	<link href="themes/softed/install.css" rel="stylesheet" type="text/css">
	<script type="text/javascript">
	function showhidediv() {
		var div_style = document.getElementById("htaccess_div").style.display;
		if(div_style == "inline")
			document.getElementById("htaccess_div").style.display = "none";
		else
			document.getElementById("htaccess_div").style.display = "inline";		
	}
	</script>
</head>

<body>
	<div id="main-container" class="container">
		<div class="row">
			<div class="col-xs-offset-1 col-xs-10">
				
				<div id="content" class="col-xs-12">
					<div id="content-cont" class="col-xs-12">
						<div id="content-inner-cont" class="col-xs-12">
						
							<div class="col-xs-12 content-padding">	
								<div class="col-xs-12 col-md-6 vcenter text-left">
									<h2 class=""><?php echo $installationStrings['LBL_CONFIG_COMPLETED']; ?></h2>
								</div><!--
								--><div class="col-xs-12 col-md-6 vcenter text-right">
									<a href="<?php echo $enterprise_website[0]; ?>" target="_blank">
										<img src="include/install/images/vtenext.png" />
									</a>
								</div>
							</div>
							
							<div id="config" class="col-xs-12">
								<div id="config-inner" class="col-xs-12 content-padding">
									<div class="col-xs-12 nopadding">
										<?php
											@include_once('config.inc.php');
											
											require_once('include/utils/utils.php');
											$log =& LoggerManager::getLogger('INSTALL');
											Common_Install_Wizard_Utils::disableMorph();
											
											// crmv@49398
											global $metaLogs;
											if ($metaLogs) {
												$metaLogs->enable();
												$metaLogs->clearAll();
												$metaLogs->log($metaLogs::OPERATION_INSTALLED, 0, array('revision'=>$enterprise_current_build));
											}
											// crmv@49398e
											
											$renameResult = Common_Install_Wizard_Utils::renameInstallationFiles();
											$renamefile = $renameResult['renamefile'];
											$ins_file_renamed = $renameResult['install_file_renamed'];
											$ins_dir_renamed = $renameResult['install_directory_renamed'];
											
											$csrfToken = RequestHandler::getCSRFToken(); // crmv@171581
										?>
										
										<table class="table borderless">
											<tr>
												<td align="center" class="small">
													<b><?php echo $enterprise_mode.' '.$enterprise_current_version. ' (build ' .$enterprise_current_build. ') ' .$installationStrings['LBL_ALL_SET_TO_GO']; ?></b>
													<div style="width:100%;padding:10px;" align="left">
														<strong><?php echo $installationStrings['LBL_RECOMMENDED_STEPS']; ?></strong>
														<div><em><?php echo $installationStrings['LBL_RECOMMENDED_STEPS_TEXT']; ?></em></div>
														<ul>
															<li><?php echo $installationStrings['LBL_SET_OUTGOING_EMAIL_SERVER']; ?></li>
															<li><?php echo $installationStrings['LBL_SETUP_BACKUP']; ?></li>
															<li><?php echo $installationStrings['LBL_RENAME_HTACCESS_FILE']; ?>. <a href="javascript:void(0);" onclick="showhidediv();"><?php echo $installationStrings['LBL_MORE_INFORMATION']; ?></a>
											   				<div id='htaccess_div' style="display:none"><br><br>
												   				<?php echo sprintf($installationStrings['MSG_HTACCESS_DETAILS'],$root_directory); ?>
											  			 	</div>
											  			</li>
														</ul>
														<br>
														<strong><?php echo $installationStrings['LBL_DOCUMENTATION_TUTORIAL']; ?></strong>
														<ul>
															<li><?php echo $installationStrings['LBL_DOCUMENTATION_TEXT']; ?>
																&nbsp;<a href="http://help.vtecrm.com" target="_blank">here</a>
															</li>
														</ul>
														<br>
														<strong><?php echo $installationStrings['LBL_WELCOME_FEEDBACK'].':'; ?></strong>
														<ul><b>
															<li><?php echo $installationStrings['LBL_TALK_TO_US_AT_FORUMS']; ?></li>
															<!--
															<li><?php echo $installationStrings['LBL_DISCUSS_WITH_US_AT_BLOGS']; ?></li>
															<li><?php echo $installationStrings['LBL_DROP_A_MAIL']; ?>
																<a href="mailto:info@vtecrm.com" target="_blank">info@vtecrm.com</a>
															</li>
															-->
														</b></ul>
														<?php if (!empty($renamefile)) { ?>
														<ul>
															<?php if($ins_file_renamed==true){ ?>
															<li><?php echo $installationStrings['LBL_INSTALL_PHP_FILE_RENAMED']. ' ' .$renamefile;?>install.php.txt.</li>
															<?php } else { ?>
															<li><font color='red'><?php echo $installationStrings['WARNING_RENAME_INSTALL_PHP_FILE']; ?>.</font></li>
															<?php } ?>
															<?php if($ins_dir_renamed==true){ ?>
															<li><?php echo $installationStrings['LBL_INSTALL_DIRECTORY_RENAMED']. ' ' .$renamefile;?>install.</li>
															<?php } else { ?>
															<li><font color='red'><?php echo $installationStrings['WARNING_RENAME_INSTALL_DIRECTORY']; ?>.</font></li>
															<?php } ?>
														</ul>
														<?php } ?>
													</div>
												</td>
											</tr>
										</table>
									
									</div>
								</div>
							</div>
	
							<div id="nav-bar" class="col-xs-12 nopadding">
								<div id="nav-bar-inner" class="col-xs-12">	
									<div class="col-xs-12 text-center">
										<form action="index.php" method="post" name="form" id="form">
											<input type="hidden" name="__csrf_token" value="<?php echo $csrfToken; ?>"> <!-- crmv@171581 -->
											<button class="crmbutton small edit"><?php echo $installationStrings['LBL_FINISH']; ?></button>
										</form>
									</div>
								</div>
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
