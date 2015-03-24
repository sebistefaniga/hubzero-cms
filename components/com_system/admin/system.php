<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\System\Admin;

if (!\JFactory::getUser()->authorise('core.manage', 'com_system'))
{
	return App::abort(404, Lang::txt('JERROR_ALERTNOAUTHOR'));
}

// Include scripts
require_once(dirname(__DIR__) . DS . 'helpers' . DS . 'html.php');
require_once(dirname(__DIR__) . DS . 'helpers' . DS . 'permissions.php');

$controllerName = \JRequest::getCmd('controller', \JRequest::getCmd('view', 'info'));
if (!file_exists(__DIR__ . DS . 'controllers' . DS . $controllerName . '.php'))
{
	$controllerName = 'info';
}

\JSubMenuHelper::addEntry(
	Lang::txt('COM_SYSTEM_LDAP'),
	Route::url('index.php?option=com_system&controller=ldap'),
	$controllerName == 'ldap'
);
\JSubMenuHelper::addEntry(
	Lang::txt('COM_SYSTEM_GEO'),
	Route::url('index.php?option=com_system&controller=geodb'),
	$controllerName == 'geodb'
);
\JSubMenuHelper::addEntry(
	Lang::txt('COM_SYSTEM_APC'),
	Route::url('index.php?option=com_system&controller=apc'),
	$controllerName == 'apc'
);

require_once(__DIR__ . DS . 'controllers' . DS . $controllerName . '.php');
$controllerName = __NAMESPACE__ . '\\Controllers\\' . ucfirst($controllerName);

// Instantiate controller
$controller = new $controllerName();
$controller->execute();
$controller->redirect();
