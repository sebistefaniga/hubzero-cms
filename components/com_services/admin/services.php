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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Services\Admin;

if (!\User::authorise('core.manage', 'com_services'))
{
	return \App::abort(404, \Lang::txt('JERROR_ALERTNOAUTHOR'));
}

// Include scripts
require_once(dirname(__DIR__) . DS . 'helpers' . DS . 'permissions.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'service.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'subscription.php');

$controllerName = \Request::getCmd('controller', 'services');
if (!file_exists(__DIR__ . DS . 'controllers' . DS . $controllerName . '.php'))
{
	$controllerName = 'services';
}

\Submenu::addEntry(
	\Lang::txt('COM_SERVICES_SERVICES'),
	\Route::url('index.php?option=com_services&controller=services'),
	$controllerName == 'services'
);
\Submenu::addEntry(
	\Lang::txt('COM_SERVICES_SUBSCRIPTIONS'),
	\Route::url('index.php?option=com_services&controller=subscriptions'),
	$controllerName == 'subscriptions'
);

require_once(__DIR__ . DS . 'controllers' . DS . $controllerName . '.php');
$controllerName = __NAMESPACE__ . '\\Controllers\\' . ucfirst($controllerName);

// Initiate controller
$controller = new $controllerName();
$controller->execute();
$controller->redirect();

