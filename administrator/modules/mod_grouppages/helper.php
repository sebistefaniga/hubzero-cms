<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2014 Purdue University. All rights reserved.
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
 * @author    Christopher Smoak <csmoak@purdue.edu>
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Modules\GroupPages;

use Hubzero\Module\Module;
use GroupsModelPageArchive;
use GroupsModelModuleArchive;

/**
 * Module class for showing group pages
 */
class Helper extends Module
{
	/**
	 * Display module contents
	 *
	 * @return     void
	 */
	public function display()
	{
		// include group page archive model
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_groups' . DS . 'models' . DS . 'page' . DS . 'archive.php';

		// include group module archive model
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_groups' . DS . 'models' . DS . 'module' . DS . 'archive.php';

		// get unapproved pages
		$groupModelPageArchive = new GroupsModelPageArchive();
		$this->unapprovedPages = $groupModelPageArchive->pages('unapproved', array(
			'state' => array(0, 1)
		), true);

		// get unapproved modules
		$groupModelModuleArchive = new GroupsModelModuleArchive();
		$this->unapprovedModules = $groupModelModuleArchive->modules('unapproved', array(
			'state' => array(0, 1)
		), true);

		// Get the view
		parent::display();
	}
}
