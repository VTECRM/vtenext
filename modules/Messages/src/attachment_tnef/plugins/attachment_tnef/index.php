<?php
/**
 * Copyright (c) 2007 The NaSMail Project
 * This file is part of NaSMail webmail interface. It is used to block listing
 * of available interface scripts.
 *
 * NaSMail is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software 
 * Foundation; either version 2 of the License, or (at your option) any later 
 * version.
 *
 * NaSMail is distributed in the hope that it will be useful, but WITHOUT ANY 
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * NaSMail; if not, write to the Free Software Foundation, Inc., 51 Franklin 
 * Street, Fifth Floor, Boston, MA 02110-1301, USA
 * @version $Id: index.php 1161 2008-02-22 08:43:07Z tokul $
 * @package nasmail
 */

/** sends http 1.0 header and text message */
header('HTTP/1.0 403 Forbidden');
header('Content-Type: text/plain; charset=utf-8');
exit('Listing denied');
