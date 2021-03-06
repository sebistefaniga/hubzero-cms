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

namespace Modules\LatestUsage;

use Hubzero\Module\Module;
use JFactory;
use UsageHelper;

/**
 * Module class for displaying latest usage
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

		include_once(JPATH_ROOT . DS . 'components' . DS . 'com_usage' . DS . 'helpers' . DS . 'helper.php');
		$udb = UsageHelper::getUDBO();

		$this->cls = trim($this->params->get('moduleclass_sfx'));

		if ($udb)
		{
			$udb->setQuery('SELECT value FROM summary_user_vals WHERE datetime = (SELECT MAX(datetime) FROM summary_user_vals) AND period = "12" AND colid = "1" AND rowid = "1"');
			$this->users = $udb->loadResult();

			$udb->setQuery('SELECT value FROM summary_simusage_vals WHERE datetime  = (SELECT MAX(datetime) FROM summary_simusage_vals) AND period = "12" AND colid = "1" AND rowid = "2"');
			$this->sims = $udb->loadResult();
		}
		else
		{
			$database->setQuery("SELECT COUNT(*) FROM `#__users`");
			$this->users = $database->loadResult();

			$this->sims = 0;
		}

		$database->setQuery("SELECT COUNT(*) FROM `#__resources` WHERE standalone=1 AND published=1 AND access!=1 AND access!=4");
		$this->resources = $database->loadResult();

		$database->setQuery("SELECT COUNT(*) FROM `#__resources` WHERE standalone=1 AND published=1 AND access!=1 AND access!=4 AND type=7");
		$this->tools = $database->loadResult();

		require $this->getLayoutPath();
	}
}

