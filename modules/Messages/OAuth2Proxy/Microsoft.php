<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@206145 */
namespace Messages\OAuth2\Proxy;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

class MicrosoftProxy extends Microsoft
{
	protected $urlAuthorize = 'https://oauthms.vtecrm.net/proxy/authorize.php';
	protected $urlAccessToken = 'https://oauthms.vtecrm.net/proxy/token.php';
}