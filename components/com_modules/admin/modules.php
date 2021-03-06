<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_modules
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Access check.
if (!User::authorise('core.manage', 'com_modules')) {
	return JError::raiseWarning(404, Lang::txt('JERROR_ALERTNOAUTHOR'));
}

$controller	= JControllerLegacy::getInstance('Modules');
$controller->execute(Request::getCmd('task'));
$controller->redirect();
