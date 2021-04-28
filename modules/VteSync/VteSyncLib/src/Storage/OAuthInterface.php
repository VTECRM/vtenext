<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Storage;

interface OAuthInterface {

	public function getOAuthInfo($syncid);
	public function getTokenInfo($syncid);
	public function setTokenInfo($syncid, $tokenInfo);
	//public function setRefreshToken($syncid, $token);
	
}