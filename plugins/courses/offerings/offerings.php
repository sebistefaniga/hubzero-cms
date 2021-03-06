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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Courses Plugin class for course offerings
 */
class plgCoursesOfferings extends \Hubzero\Plugin\Plugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var  boolean
	 */
	protected $_autoloadLanguage = true;

	/**
	 * Return data on a course view (this will be some form of HTML)
	 *
	 * @param   object  $course  Current course
	 * @param   string  $active  Current active area
	 * @return  array
	 */
	public function onCourseView($course, $active=null)
	{
		// Check that there are any offerings to show
		if ($course->offerings(array('state' => 1, 'sort_Dir' => 'ASC'), true)->total() <= 0)
		{
			return;
		}

		// Can this plugin respond, based on the current access settings?
		$respond = false;
		switch ($this->params->get('plugin_access', 'anyone'))
		{
			case 'managers':
				$memberships = $course->offering()->membership();

				if (count($memberships) > 0)
				{
					foreach ($memberships as $membership)
					{
						if (!$membership->get('student'))
						{
							$respond = true;
							break;
						}
					}
				}
			break;

			case 'members':
				if (count($course->offering()->membership()) > 0)
				{
					$respond = true;
				}
			break;

			case 'registered':
				if (!User::isGuest())
				{
					$respond = true;
				}
			break;

			case 'anyone':
			default:
				$respond = true;
			break;
		}

		if (!$respond)
		{
			return;
		}

		// Prepare response
		$response = with(new \Hubzero\Base\Object)
			->set('name', $this->_name)
			->set('title', Lang::txt('PLG_COURSES_' . strtoupper($this->_name)));

		// Check if our area is in the array of areas we want to return results for
		if ($response->get('name') == $active)
		{
			$view = $this->view('default', 'overview');
			$view->set('option', Request::getCmd('option', 'com_courses'))
			     ->set('controller', Request::getWord('controller', 'course'))
			     ->set('course', $course)
			     ->set('name', $this->_name);

			$response->set('html', $view->loadTemplate());
		}

		// Return the output
		return $response;
	}
}

