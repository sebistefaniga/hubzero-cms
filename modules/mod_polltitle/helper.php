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

namespace Modules\PollTitle;

use Hubzero\Module\Module;
use Components\Poll\Tables\Poll;
use JFactory;

/**
 * Module class for displaying the latest poll's title
 */
class Helper extends Module
{
	/**
	 * Get module contents
	 *
	 * @return  void
	 */
	public function run()
	{
		require_once(PATH_CORE . DS . 'components' . DS . 'com_poll' . DS . 'models' . DS . 'poll.php');

		$model = new Poll();

		// Load the latest poll
		$this->poll = $model->getLatest();

		require $this->getLayoutPath();
	}

	/**
	 * Display module content
	 *
	 * @return  void
	 */
	public function display()
	{
		$debug = (defined('JDEBUG') && JDEBUG ? true : false);

		if (!$debug && intval($this->params->get('cache', 0)))
		{
			$cache = JFactory::getCache('callback');
			$cache->setCaching(1);

			// Module time is in seconds, setLifeTime() is in minutes
			// Some module times may have been set in minutes so we
			// need to account for that.
			$ct = intval($this->params->get('cache_time', 900));
			$ct = (!$ct || $ct == 15 ?: $ct / 60);
			$cache->setLifeTime($ct);

			$cache->call(array($this, 'run'));
			echo '<!-- cached ' . \Date::toSql() . ' -->';
			return;
		}

		$this->run();
	}
}
