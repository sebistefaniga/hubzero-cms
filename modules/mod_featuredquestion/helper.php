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

namespace Modules\Featuredresource;

use Hubzero\Module\Module;
use Components\Answers\Tables\Question;
use Component;
use JFactory;

/**
 * Module class for displaying a random featured question
 */
class Helper extends Module
{
	/**
	 * Generate module contents
	 *
	 * @return  void
	 */
	public function run()
	{
		require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'models' . DS . 'question.php');

		$database = JFactory::getDBO();
		$row = null;

		// randomly choose one
		$filters = array(
			'limit'    => 1,
			'start'    => 0,
			'sortby'   => 'random',
			'tag'      => '',
			'filterby' => 'open',
			'created_before' => gmdate('Y-m-d', mktime(0, 0, 0, gmdate('m'), (gmdate('d')+7), gmdate('Y'))) . ' 00:00:00'
		);

		$mp = new Question($database);

		$rows = $mp->getResults($filters);
		if (count($rows) > 0)
		{
			$row = $rows[0];
		}

		// Did we have a result to display?
		if ($row)
		{
			$this->cls = trim($this->params->get('moduleclass_sfx'));
			$this->txt_length = trim($this->params->get('txt_length'));

			$this->row = $row;

			$config = Component::params('com_answers');

			$this->thumb = DS . trim($this->params->get('defaultpic', '/modules/mod_featuredquestion/assets/img/question_thumb.gif'), DS);
			if ($this->thumb == '/modules/mod_featuredquestion/question_thumb.gif')
			{
				$this->thumb = '/modules/mod_featuredquestion/assets/img/question_thumb.gif';
			}

			require $this->getLayoutPath();
		}
	}

	/**
	 * Display module contents
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

