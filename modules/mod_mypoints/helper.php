<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 * All rights reserved.
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

namespace Modules\MyPoints;

use Hubzero\Module\Module;
use Hubzero\Bank\Teller;
use Config;
use User;
use JFactory;

/**
 * Module class for displaying point total and recent transactions
 */
class Helper extends Module
{
	/**
	 * Display module content
	 *
	 * @return  void
	 */
	public function display()
	{
		$database = JFactory::getDBO();

		$this->moduleclass = $this->params->get('moduleclass');
		$this->limit = intval($this->params->get('limit', 10));
		$this->error = false;

		// Check for the existence of required tables that should be
		// installed with the com_support component
		$tables = $database->getTableList();

		if ($tables && array_search(Config::get('dbprefix') . 'users_points', $tables) === false)
		{
			// Points table not found
			$this->error = true;
		}
		else
		{
			// Get the user's point summary and history
			$BTL = new Teller($database, User::get('id'));
			$this->summary = $BTL->summary();
			$this->history = $BTL->history($this->limit);

			// Push the module CSS to the template
			$this->css();
		}

		require $this->getLayoutPath();
	}
}

