<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

ob_start();
eval ("phpinfo();");
$info = ob_get_contents();
ob_end_clean();

foreach (explode("\n", $info) as $line) {
	if (strpos($line, "Client API version") !== false)
		$mysql_version = trim(str_replace("Client API version", "", strip_tags($line)));
}

ob_start();
phpinfo(INFO_GENERAL);
$string = ob_get_contents();
ob_end_clean();

$pieces = explode("<h2", $string);
$settings = array ();
foreach ($pieces as $val) {
	preg_match("/<a name=\"module_([^<>]*)\">/", $val, $sub_key);
	preg_match_all("/<tr[^>]*>
										   <td[^>]*>(.*)<\/td>
										   <td[^>]*>(.*)<\/td>/Ux", $val, $sub);
	preg_match_all("/<tr[^>]*>
										   <td[^>]*>(.*)<\/td>
										   <td[^>]*>(.*)<\/td>
										   <td[^>]*>(.*)<\/td>/Ux", $val, $sub_ext);
	foreach ($sub[0] as $key => $val) {
		if (preg_match("/Configuration File \(php.ini\) Path /", $val)) {
			$val = preg_replace("/Configuration File \(php.ini\) Path /", '', $val);
			$phpini = strip_tags($val);
		}
	}
}

if (isset ($_REQUEST['filename'])) {
	$file_name = htmlspecialchars($_REQUEST['filename']);
}

$failed_permissions = Common_Install_Wizard_Utils::getFailedPermissionsFiles();
$gd_info_alternate = Common_Install_Wizard_Utils::$gdInfoAlternate;
$directive_recommended = Common_Install_Wizard_Utils::getRecommendedDirectives();
$directive_array = Common_Install_Wizard_Utils::getCurrentDirectiveValue();
$check_mysql_extension = Common_Install_Wizard_Utils::check_mysql_extension();
$curl_check = function_exists('curl_init');

$title = $enterprise_mode.' '.$enterprise_current_version. ' (build ' .$enterprise_current_build. ') '. ' - ' . $installationStrings['LBL_CONFIG_WIZARD']. ' - ' . $installationStrings['LBL_INSTALLATION_CHECK'];
$sectionTitle = $installationStrings['LBL_PRE_INSTALLATION_CHECK'];

include_once "install/templates/overall/header.php";

?>

<div id="config" class="col-xs-12">
	<div id="config-inner" class="col-xs-12 content-padding">
		<div class="col-xs-12 nopadding">
		
			<div class="col-xs-12 nopadding">
			
				<div class="col-xs-12 col-md-6" style="padding-left:0px">
					<div class="col-xs-12 nopadding">
						<table class="table table-striped">
							<tr>
								<td><?php echo $installationStrings['LBL_PHP_VERSION_GT_5']; ?></td>
								<td>
								<?php 
									// crmv@111927
									$php_version = phpversion(); 
									if (!defined('PHP_VERSION_ID')) {
										$version = explode('.', $php_version);
										define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
									}
									echo (PHP_VERSION_ID < 70000) ? // crmv@146653
										"<strong><font color=\"Red\">{$installationStrings['LBL_NO']}</strong></font>" : 
										"<strong><font color=\"#46882B\">$php_version</strong></font>";
									// crmv@111927e
								?>
								</td>
							</tr>
							<tr>
								<td><?php echo $installationStrings['LBL_IMAP_SUPPORT']; ?></td>
								<td><?php 
									echo function_exists('imap_open') ? 
										"<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>" : 
										"<strong><font color=\"#FF0000\">{$installationStrings['LBL_NO']}</strong></font>";
									?>
								</td>
							</tr>
							<tr>
								<td><?php echo $installationStrings['LBL_ZLIB_SUPPORT']; ?></td>
								<td><?php 
									echo function_exists('gzinflate') ? 
										"<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>" : 
										"<strong><font color=\"#FF0000\">{$installationStrings['LBL_NO']}</strong></font>";
									?>
								</td>
							</tr>
							<tr>
								<td><?php echo $installationStrings['LBL_GD_LIBRARY']; ?></td>
								<td><?php				
									if (!extension_loaded('gd')) {
										echo "<strong><font size=-1 color=\"#FF0000\">{$installationStrings['LBL_NOT_CONFIGURED']}.</strong></font>";
									} else {
										if (!function_exists('gd_info')) {
											eval($gd_info_alternate);
										}
										$gd_info = gd_info();
									
										if (isset($gd_info['GD Version'])) {
											$gd_version = $gd_info['GD Version'];
											$gd_version = preg_replace('%[^0-9.]%', '', $gd_version);
												echo "<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>";
										} else {
											echo "<strong><font size=-1 color=\"#FF0000\">{$installationStrings['LBL_NO']}</font>";
										}
									}
								?>
								</td>
							</tr>
							<tr>
								<td><?php echo $installationStrings['LBL_DATABASE_EXTENSION'];?></td>
								<td><?php
									if ($check_mysql_extension == false) {
										echo "<strong><font size=-1 color=\"#FF0000\">{$installationStrings['LBL_NO']}</strong></font>";
									} else {
										echo "<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>";
									}
								?>
								</td>
							</tr>
							<tr>
								<td><?php echo $installationStrings['LBL_CURL_LIBRARY'];?></td>
								<td><?php
									if (!$curl_check) {
										echo "<strong><font size=-1 color=\"#FF0000\">{$installationStrings['LBL_NO']}</strong></font>";
									} else {
										echo "<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>";
									}
								?>
								</td>
							</tr>
							<!-- crmv@146653 -->
							<tr>
								<td><?php echo $installationStrings['LBL_SIMPLEXML_LIBRARY'];?></td>
								<td><?php
									if (!extension_loaded('simplexml')) {
										echo "<strong><font size=-1 color=\"#FF0000\">{$installationStrings['LBL_NO']}</strong></font>";
									} else {
										echo "<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>";
									}
								?>
								</td>
							</tr>
							<tr>
								<td><?php echo $installationStrings['LBL_MBSTRING_LIBRARY'];?></td>
								<td><?php
									if (!extension_loaded('mbstring')) {
										echo "<strong><font size=-1 color=\"#FF0000\">{$installationStrings['LBL_NO']}</strong></font>";
									} else {
										echo "<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>";
									}
								?>
								</td>
							</tr>
							<!-- crmv@146653e -->
							<!-- crmv@91321 -->
							<tr>
								<td><?php echo $installationStrings['LBL_IMAGICK_LIBRARY']; ?></td>
								<td><?php				
									if (!extension_loaded('imagick')) {
										echo "<strong><font size=-1 color=\"#FF0000\">{$installationStrings['LBL_NO']}</strong></font>";
									} else {
										echo "<strong><font color=\"#46882B\">{$installationStrings['LBL_YES']}</strong></font>";
									}
								?>
								</td>
							</tr>
							<!-- crmv@91321e -->
						</table>
					</div>
					<div class="col-xs-12 nopadding">
						<p><b><?php echo $installationStrings['LBL_RECOMMENDED_PHP_SETTINGS']; ?>:</b></p>
						<?php
							$all_directive_recommended_value = true;
							if (!empty($directive_array)) {
								$all_directive_recommended_value = false;
						?>
							
						<!-- Recommended Settings -->
						<div class="col-xs-12 nopadding">
							<table class="table table-striped">
								<tr>
									<td><strong><?php echo $installationStrings['LBL_DIRECTIVE']; ?></strong></td>
									<td><strong><?php echo $installationStrings['LBL_RECOMMENDED']; ?></strong></td>
									<td nowrap><strong><?php echo $installationStrings['LBL_PHP_INI_VALUE']; ?></strong></td>
								</tr>
								<?php
									foreach ($directive_array as $index => $value) {
								?>
									<tr> 
										<td><?php echo $index; ?></td>
										<td><?php echo $directive_recommended[$index]; ?></td>
										<td><strong><font color="red"><?php echo $value; ?></font></strong></td>
									</tr>
								<?php
									}
								?>
							</table>
						</div>
							
						<!-- crmv@24713m -->
						<div class="col-xs-12 nopadding">
							<table class="table table-striped">
								<tr>
									<td><i><?php echo $installationStrings['LBL_MOD_REWRITE_INSTRUCTIONS']; ?></i></td>
								</tr>
							</table>
						</div>
						<!-- crmv@24713me -->
							
						<?php
							} else {
								echo "<p>".$installationStrings['LBL_PHP_DIRECTIVES_HAVE_RECOMMENDED_VALUES']."</p>";
							}
						?>
					</div>
				</div>
				
				<div class="col-xs-12 col-md-6" style="padding-right:0px">
					<div class="col-xs-12 nopadding text-right">
						<form action="install.php" method="post" name="form" id="form" style="padding-bottom:10px">
							<input type="hidden" name="filename" value="<?php echo $file_name?>" />
							<input type="hidden" name="file" value="CheckSystem.php" />	
							<button type="button" class="crmbutton small edit" onClick="submit();"><?php echo $installationStrings['LBL_CHECK_AGAIN']; ?></button>
						</form>
					</div>
					
					<div class="col-xs-12 nopadding">
						<table class="table table-striped">
							<?php
								if (!empty($failed_permissions)) {
							?>
								<tr>
									<td colspan=2>
										<strong>
											<span style="color:#000000">
												<?php echo $installationStrings['LBL_READ_WRITE_ACCESS']; ?>
											</span>
										</strong>
									</td>
								</tr>
							<?php
								foreach ($failed_permissions as $index => $value) {
							?>
								<tr>
									<td><?php echo $index; ?> (<?php echo str_replace("./","",$value); ?>)</td>
						        	<td><font color="red"><strong><?php echo $installationStrings['LBL_NO']; ?></strong></font></td>
								</tr>
							<?php					
								}
							}
							?>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="nav-bar" class="col-xs-12 nopadding">
	<div id="nav-bar-inner" class="col-xs-12">	
		<div class="col-xs-6 text-left">
			<button type="button" class="crmbutton small edit btn-arrow-left" onClick="window.history.back();"><?php echo $installationStrings['LBL_BACK']; ?></button>
		</div>
		
		<div class="col-xs-6 text-right">
			<form action="install.php" method="post" name="form" id="form">
				<input type="hidden" name="file" value="<?php echo $file_name?>" />
				<button type="button" class="crmbutton small edit btn-arrow-right" onClick="if(isPermitted()) submit();"><?php echo $installationStrings['LBL_NEXT']; ?></button>
			</form>
		</div>
	</div>
</div>
							
<script type="text/javascript">
	function isPermitted() {
		<?php
		if (!empty ($failed_permissions)) {
			echo "alert('{$installationStrings['MSG_PROVIDE_READ_WRITE_ACCESS_TO_PROCEED']}');";
			echo "return false;";
		} elseif (!$curl_check) {?>
			if (confirm('<?php echo $installationStrings['LBL_CURL_LIBRARY_ERROR']; ?>')) {
				return true;
			} else {
				return false;
			}
		<?php
		} else {
			if (!$all_directive_recommended_value) { ?>
				if (confirm('<?php echo $installationStrings['WARNING_PHP_DIRECTIVES_NOT_RECOMMENDED_STILL_WANT_TO_PROCEED']; ?>')) {
					return true;
				} else {
					return false;
				}
			<?php
			}
			echo "return true;";
		}
		?>
	}
</script>

<?php include_once "install/templates/overall/footer.php"; ?>
