<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@215354 crmv@215597 */

require_once('modules/Settings/KlondikeAI/KlondikeConfig.php');

global $site_URL;


$KC = new KlondikeConfig();

$klondikeUrl = $_REQUEST['klondike_url'] ?: $KC->getKlondikeUrl();
if ($klondikeUrl) {
	// save it
	$KC->saveUrl($klondikeUrl);
} else {
	// retrieve it
	$cfg = $KC->getConfig();
	if ($cfg) $klondikeUrl = $cfg['klondike_url'];
}

$provider = $KC->getProvider($klondikeUrl);

if (!isset($_GET['code'])) {

    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    VteSession::set('oauth2state', $provider->getState());

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit;
    
} elseif (empty($_GET['state']) || (VteSession::hasKey('oauth2state') && $_GET['state'] !== VteSession::get('oauth2state'))) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }

    displayError('Invalid state');

} else {

    try {

        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
        
        // save the token
        $KC->saveTokens($accessToken->getToken(), $accessToken->getExpires(), $accessToken->getRefreshToken());
        
        displaySuccess();

    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Failed to get the access token or user details.
        displayError($e->getMessage());

    } catch (\Exception $e) {

		displayError($e->getMessage());
		
    }
}



function displaySuccess() {
	global $app_strings, $mod_strings;
	global $theme;

	$smarty = new VteSmarty();
	$smarty->assign("MOD", $mod_strings);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("THEME",$theme);
	
	$body = '<div class="container">
		<div class="row">
			<div class="col-sm-12 text-center"><br>'.getTranslatedString('LBL_KLONDIKE_TOKEN_OK', 'Settings').'<br><br></div>
		</div>
		<div class="row">
			<div class="col-sm-12 text-center"><br><button class="crmbutton save" type="button" onclick="window.opener.location.reload();window.close()">'.getTranslatedString('LBL_CLOSE_WINDOW', 'APP_STRING').'</button><br><br></div>
		</div>
	</div>';
	
	$smarty->assign("BODY",$body);
	
	$smarty->display('NoLoginMsg.tpl');
	exit(0);
}


function displayError($string) {
	global $app_strings, $mod_strings;
	global $theme;

	$smarty = new VteSmarty();
	$smarty->assign("MOD", $mod_strings);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("THEME",$theme);
	
	$body = '<div class="container"><div class="row"><div class="col-sm-12 text-center"><br>'.getTranslatedString('LBL_ERROR_HAPPENED', 'APP_STRING').": $string<br><br></div></div></div>";
	
	$smarty->assign("BODY",$body);
	
	$smarty->display('NoLoginMsg.tpl');
	exit(0);
}
