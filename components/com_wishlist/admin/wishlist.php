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

namespace Components\Wishlist\Admin;

// Authorization check
if (!\User::authorise('core.manage', 'com_wishlist'))
{
	return \App::abort(404, \Lang::txt('JERROR_ALERTNOAUTHOR'));
}

// Include scripts
include_once(dirname(__DIR__) . DS . 'tables' . DS . 'wishlist.php');
include_once(dirname(__DIR__) . DS . 'tables' . DS . 'owner.php');
include_once(dirname(__DIR__) . DS . 'tables' . DS . 'ownergroup.php');
include_once(dirname(__DIR__) . DS . 'tables' . DS . 'wish.php');
include_once(dirname(__DIR__) . DS . 'tables' . DS . 'wish' . DS . 'plan.php');
include_once(dirname(__DIR__) . DS . 'tables' . DS . 'wish' . DS . 'rank.php');
include_once(dirname(__DIR__) . DS . 'tables' . DS . 'wish' . DS . 'attachment.php');
include_once(dirname(__DIR__) . DS . 'helpers' . DS . 'permissions.php');

$controllerName = \Request::getCmd('controller', 'lists');
if (!file_exists(__DIR__ . DS . 'controllers' . DS . $controllerName . '.php'))
{
	$controllerName = 'lists';
}

\Submenu::addEntry(
	\Lang::txt('COM_WISHLIST_LISTS'),
	\Route::url('index.php?option=com_wishlist&controller=lists'),
	($controllerName == 'lists')
);
\Submenu::addEntry(
	\Lang::txt('COM_WISHLIST_WISHES'),
	\Route::url('index.php?option=com_wishlist&controller=wishes&wishlist=-1'),
	($controllerName == 'wishes')
);
\Submenu::addEntry(
	\Lang::txt('COM_WISHLIST_COMMENTS'),
	\Route::url('index.php?option=com_wishlist&controller=comments&wish=-1'),
	($controllerName == 'comments')
);

require_once(__DIR__ . DS . 'controllers' . DS . $controllerName . '.php');
$controllerName = __NAMESPACE__ . '\\Controllers\\' . ucfirst($controllerName);

// Initiate controller
$controller = new $controllerName();
$controller->execute();
$controller->redirect();

