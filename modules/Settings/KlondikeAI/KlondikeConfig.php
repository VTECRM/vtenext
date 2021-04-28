<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@215354 crmv@215597 */

class KlondikeConfig {
	
	public function __construct() {
		global $table_prefix;
		$this->table = $table_prefix.'_klondike_config';
	}
	
	public function getKlondikeUrl() {
		return 'https://cloud.klondike.ai';
	}
	
	public function getProvider($klondikeUrl) {
		global $site_URL;
		
		$provider = new \League\OAuth2\Client\Provider\GenericProvider([
			'clientId'                => 'vtenext_cb6f0a675c455c0d5021',
			'clientSecret'            => 'xxxxxxx',
			'redirectUri'             => $site_URL.'/index.php?module=Settings&action=SettingsAjax&ajax=true&file=KlondikeAI/oauthPanel',
			'urlAuthorize'            => $klondikeUrl.'/oauth2/v1/auth.php',
			'urlAccessToken'          => $klondikeUrl.'/oauth2/v1/token.php',
			'urlResourceOwnerDetails' => $klondikeUrl.'/oauth2/v1/resource.php'
		]);
		
		return $provider;
	}
	
	public function getConfig() {
		global $adb;
		
		$res = $adb->pquery("SELECT * FROM {$this->table} WHERE id = ?", [1]);
		$row = $adb->FetchByAssoc($res, -1, false);
		
		return $row;
	}
	
	// get the access token (or renew it if expired)
	public function getValidAccessToken($cfg) {
		$data = [
			'access_token' => $cfg['access_token'],
			'expires' => $cfg['token_expire'],
			'refresh_token' => $cfg['refresh_token'],
		];
		$atoken = new League\OAuth2\Client\Token\AccessToken($data);
		
		if ($atoken->hasExpired()) {
			// try to refresh it
			try {
				$provider = $this->getProvider($cfg['klondike_url']);
				$newAccessToken = $provider->getAccessToken('refresh_token', [
					'refresh_token' => $atoken->getRefreshToken()
				]);
				// save it again
				$this->saveTokens($newAccessToken->getToken(), $newAccessToken->getExpires(), $newAccessToken->getRefreshToken());
				$atoken = $newAccessToken;
			} catch (\Exception $e) {
				return false;
			}
		}
		
		return $atoken->getToken();
	}
	
	public function saveUrl($url) {
		global $adb;
		
		$res = $adb->pquery("SELECT id FROM {$this->table} WHERE id = ?", [1]);
		if ($res && $adb->num_rows($res) > 0) {
			$adb->pquery("UPDATE {$this->table} SET klondike_url = ? WHERE id = ?", [$url, 1]);
		} else {
			$adb->pquery("INSERT INTO {$this->table} (id, klondike_url) VALUES (?,?)", [1, $url]);
		}
	}
	
	public function saveTokens($accessToken, $expire, $refreshToken) {
		global $adb;
		
		$adb->pquery("UPDATE {$this->table} SET access_token = ?, token_expire = ?, refresh_token = ? WHERE id = ?", [$accessToken, $expire, $refreshToken, 1]);
	}
	
	public function removeConfig() {
		global $adb;
		$adb->pquery("DELETE FROM {$this->table} WHERE id = ?", [1]);
	}
	
}
