<?php
/**
 * Init script
 *
 * Copyright (c) 2002 Bernd Wiegmann <bernd@wib-software.de>
 *                    Graham Norbury <gnorbury@bondcar.com>
 * Copyright (c) 2008 The NaSMail Project
 * This file is part of NaSMail attachment_tnef plugin.
 *
 * This plugin is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * plugin; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA  02111-1307  USA
 * @package plugins
 * @subpackage attachment_tnef
 * @version $Id: setup.php 1171 2008-02-23 10:56:54Z tokul $
 */

/**
 * Shows plugin version
 * @return string version number
 */
function attachment_tnef_version() {
    return '0.7.nsm';
}

/** Init function */
function squirrelmail_plugin_init_attachment_tnef() {
    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['attachment application/ms-tnef']['attachment_tnef'] = 'attachment_tnef_link';
}

/**
 * Function attached to attachment application/ms-tnef hook.
 * @param array $Args
 */
function attachment_tnef_link(&$Args) {
    include_once(SM_PATH . 'plugins/attachment_tnef/functions.php');
    attachment_tnef_link_do($Args);
}
