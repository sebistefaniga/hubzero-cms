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

namespace Modules\LatestBlog;

use Hubzero\Module\Module;
use Hubzero\User\Group\Helper as GroupHelper;
use Components\Blog\Models\Archive;
use User;
use JFactory;

/**
 * Module class for displaying the latest blog posts
 */
class Helper extends Module
{
	/**
	 * Display module contents
	 * 
	 * @return  void
	 */
	public function run()
	{
		include_once(PATH_CORE . DS . 'components' . DS . 'com_blog' . DS . 'models' . DS . 'archive.php');

		$this->pullout   = $this->params->get('pullout', 'yes');
		$this->feedlink  = $this->params->get('feedlink', 'yes');
		$this->limit     = $this->params->get('limit', 5);

		$filters = array(
			'limit'    => $this->params->get('limit', 5),
			'start'    => 0,
			'scope'    => $this->params->get('blog', 'site'),
			'scope_id' => 0,
			'state'    => (!User::isGuest() ? 'registered' : 'public')
		);
		if ($filters['scope'] == 'both' || $filters['scope'] == 'group')
		{
			$filters['limit'] = ($filters['limit'] * 5);  // Since some groups May have private entries, we need to up the limit to try and catch more
		}
		if ($filters['scope'] == 'both')
		{
			$filters['scope'] = '';
		}

		$archive = new Archive('site', 0);
		$rows = $archive->entries('list', $filters);

		if ($this->params->get('blog', 'site') == 'group' || $this->params->get('blog', 'site') == 'both')
		{
			//make sure that the group for each blog post has the right privacy setting
			foreach ($rows as $k => $gf)
			{
				if (!$gf->get('scope_id'))
				{
					continue;
				}

				$group = $gf->item();
				if (is_object($group))
				{
					$blog_access = GroupHelper::getPluginAccess($group, 'blog');

					if ($blog_access == 'nobody'
					 || ($blog_access == 'registered' && User::isGuest())
					 || ($blog_access == 'members' && !in_array(User::get('id'), $group->get('members'))))
					{
						$rows->offsetUnset($k);
					}
				}
				else
				{
					$rows->offsetUnset($k);
				}
			}
		}
		$rows->reset();

		$this->posts = $rows;

		require $this->getLayoutPath();
	}

	/**
	 * Display module content
	 * 
	 * @return     void
	 */
	public function display()
	{
		// Push the module CSS to the template
		$this->css();

		$debug = (defined('JDEBUG') && JDEBUG ? true : false);

		if (!$debug && intval($this->params->get('cache', 0)))
		{
			$cache = JFactory::getCache('callback');
			$cache->setCaching(1);
			$cache->setLifeTime(intval($this->params->get('cache_time', 15)));
			$cache->call(array($this, 'run'));
			echo '<!-- cached ' . \Date::toSql() . ' -->';
			return;
		}

		$this->run();
	}
}
