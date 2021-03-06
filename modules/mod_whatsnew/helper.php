<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Modules\WhatsNew;

use Hubzero\Module\Module;
use Components\Whatsnew\Helpers\Period;
use MembersModelTags;
use Event;
use Route;
use Lang;
use User;
use JFactory;

/**
 * Module class for displaying what's new in a category of content
 */
class Helper extends Module
{
	/**
	 * Get the categories for What's New
	 *
	 * @return  array
	 */
	private function _getAreas()
	{
		// Do we already have an array of areas?
		if (!isset($this->searchareas) || empty($this->searchareas))
		{
			// No - so we'll need to get it
			$areas = array();

			// Trigger the functions that return the areas we'll be searching
			$searchareas = Event::trigger('whatsnew.onWhatsNewAreas');

			// Build an array of the areas
			foreach ($searchareas as $area)
			{
				$areas = array_merge($areas, $area);
			}

			// Save the array for use elsewhere
			$this->searchareas = $areas;
		}

		// Return the array
		return $this->searchareas;
	}

	/**
	 * Fromat tags for display
	 *
	 * @param   array    $tags  Tags to format
	 * @param   integer  $num   Number of tags to display
	 * @param   integer  $max   Max characters for a tag
	 * @return  string   HTML
	 */
	public function formatTags($tags=array(), $num=3, $max=25)
	{
		$out = '';

		if (count($tags) > 0)
		{
			$out .= '<span class="taggi">' . "\n";
			$counter = 0;

			foreach ($tags as $i => $tag)
			{
				$counter = $counter + strlen(stripslashes($tag->get('raw_tag')));
				if ($counter > $max)
				{
					$num = $num - 1;
				}
				if ($i < $num)
				{
					// display tag
					$out .= "\t" . '<a href="' . Route::url($tag->link()) . '">' . $this->escape(stripslashes($tag->get('raw_tag'))) . '</a> ' . "\n";
				}
			}
			if ($i > $num)
			{
				$out .= ' (&#8230;)';
			}
			$out .= '</span>' . "\n";
		}

		return $out;
	}

	/**
	 * Get module contents
	 *
	 * @return  void
	 */
	public function run()
	{
		include_once(PATH_CORE . DS . 'components' . DS . 'com_whatsnew' . DS . 'helpers' . DS . 'period.php');
		$live_site = rtrim(Request::base(), '/');

		// Get some initial parameters
		$count        = intval($this->params->get('limit', 5));
		$this->feed   = $this->params->get('feed');
		$this->cssId  = $this->params->get('cssId');
		$this->period = $this->params->get('period', 'resources:month');
		$this->tagged = intval($this->params->get('tagged', 0));

		$database = JFactory::getDBO();

		// Build the feed link if necessary
		if ($this->feed)
		{
			$this->feedlink = Route::url('index.php?option=com_whatsnew&task=feed.rss&period=' . $this->period);
			$this->feedlink = DS . trim($this->feedlink, DS);
			$this->feedlink = $live_site . $this->feedlink;
			if (substr($this->feedlink, 0, 5) == 'https')
			{
				$this->feedlink = ltrim($this->feedlink, 'https');
				$this->feedlink = 'http' . $this->feedlink;
			}
		}

		// Get categories
		$areas = $this->_getAreas();

		$area = '';

		// Check the search string for a category prefix
		if ($this->period != NULL)
		{
			$searchstring = strtolower($this->period);
			foreach ($areas as $c=>$t)
			{
				$regexp = "/" . $c . ":/";
				if (strpos($searchstring, $c . ":") !== false)
				{
					// We found an active category
					// NOTE: this will override any category sent in the querystring
					$area = $c;
					// Strip it off the search string
					$searchstring = preg_replace($regexp, '', $searchstring);
					break;
				}
				// Does the category contain sub-categories?
				if (is_array($t) && !empty($t))
				{
					// It does - loop through them and perform the same check
					foreach ($t as $sc => $st)
					{
						$regexp = "/" . $sc . ":/";
						if (strpos($searchstring, $sc . ':') !== false)
						{
							// We found an active category
							// NOTE: this will override any category sent in the querystring
							$area = $sc;
							// Strip it off the search string
							$searchstring = preg_replace($regexp, '', $searchstring);
							break;
						}
					}
				}
			}
			$this->period = trim($searchstring);
		}
		$this->area = $area;

		// Get the active category
		$activeareas = array();
		if ($area)
		{
			$activeareas[] = $area;
		}

		// Process the keyword for exact time period
		$p = new Period($this->period);

		// Get the search results
		$results = event::trigger('whatsnew.onWhatsnew', array(
				$p,
				$count,
				0,
				$activeareas,
				array()
			)
		);

		$rows = array();

		if ($results)
		{
			foreach ($results as $result)
			{
				if (is_array($result) && !empty($result))
				{
					$rows = $result;
					break;
				}
			}
		}

		$this->rows = $rows;
		$this->rows2 = null;

		if ($this->tagged)
		{
			include_once(PATH_CORE . DS . 'components' . DS . 'com_members' . DS . 'models' . DS . 'tags.php');
			$mt = new MembersModelTags(User::get('id'));
			$tags = $mt->tags();

			$this->tags = $tags;

			if (count($tags) > 0)
			{
				$tagids = array();
				foreach ($tags as $tag)
				{
					$tagids[] = $tag->get('id');
				}

				// Get the search results
				$results2 = \Event::trigger('onWhatsnew', array(
						$p,
						$count,
						0,
						$activeareas,
						$tagids
					)
				);

				$rows2 = array();

				if ($results2)
				{
					foreach ($results2 as $result2)
					{
						if (is_array($result2) && !empty($result2))
						{
							$rows2 = $result2;
							break;
						}
					}
				}

				$this->rows2 = $rows2;
			}
		}

		require $this->getLayoutPath($this->params->get('layout', 'default'));
	}

	/**
	 * Display module contents
	 *
	 * @return  void
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
			$cache->setLifeTime(intval($this->params->get('cache_time', 900)));
			$cache->call(array($this, 'run'));
			echo '<!-- cached ' . \Date::toSql() . ' -->';
			return;
		}

		$this->run();
	}
}
