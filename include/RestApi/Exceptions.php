<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@170283 */
class InvalidLoginException extends \Exception {
	var $pHttpCode = '401';
}
class MethodNotFoundException extends \Exception {
	var $pHttpCode = '500';
}