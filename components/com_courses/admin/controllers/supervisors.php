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

namespace Components\Courses\Admin\Controllers;

use Hubzero\Component\AdminController;
use Exception;

require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'course.php');

/**
 * Manage a course section's manager entries
 */
class Supervisors extends AdminController
{
	/**
	 * Add a user to the manager list
	 *
	 * @return  void
	 */
	public function addTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming member ID
		$id = Request::getInt('offering', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('COURSES_NO_ID'));
			$this->displayTask();
			return;
		}
		$section = Request::getInt('section', 0);

		$role_id = Request::getInt('role', 0);

		// Load the profile
		$model = \CoursesModelOffering::getInstance($id);
		if ($section)
		{
			$model->section($section);
		}

		$managers = $model->managers(array(
			'student'     => 0,
			'section_id'  => array(0, $section),
			'offering_id' => array(0, $id)
		));

		// Incoming host
		$m = Request::getVar('usernames', '', 'post');
		$mbrs = explode(',', $m);

		jimport('joomla.user.helper');

		$users = array();
		foreach ($mbrs as $mbr)
		{
			// Retrieve user's account info
			$mbr = trim($mbr);
			$uid = \JUserHelper::getUserId($mbr);

			// Ensure we found an account
			if ($uid)
			{
				// Loop through existing members and make sure the user isn't already a member
				if (isset($managers[$uid]))
				{
					$this->setError(Lang::txt('COM_COURSES_ERROR_ALREADY_MANAGER', $mbr));
					continue;
				}

				// They user is not already a member, so we can go ahead and add them
				$users[] = $uid;
			}
			else
			{
				$this->setError(Lang::txt('COM_COURSES_ERROR_USER_NOTFOUND') . ' ' . $mbr);
			}
		}

		if (count($users) > 0)
		{
			$model->add($users, $role_id);
		}

		// Push through to the hosts view
		$this->displayTask($model);
	}

	/**
	 * Remove one or more users from the course manager list
	 *
	 * @return  void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming member ID
		$id = Request::getInt('offering', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('COM_COURSES_ERROR_NO_ID'));
			$this->displayTask();
			return;
		}
		$section = Request::getInt('section', 0);

		$model = \CoursesModelOffering::getInstance($id);
		if ($section)
		{
			$model->section($section);
		}

		$mbrs = Request::getVar('entries', array(0), 'post');

		foreach ($mbrs as $mbr)
		{
			if (!isset($mbr['select']))
			{
				continue;
			}

			$member = \CoursesModelMember::getInstance($mbr['select'], null, null, null);
			if (!$member->delete())
			{
				$this->setError($member->getError());
			}
		}

		// Push through to the hosts view
		$this->displayTask($model);
	}

	/**
	 * Remove one or more users from the course manager list
	 *
	 * @return  void
	 */
	public function updateTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming member ID
		$id = Request::getInt('offering', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('COM_COURSES_ERROR_NO_ID'));
			$this->displayTask();
			return;
		}
		$section = Request::getInt('section', 0);

		$model = \CoursesModelOffering::getInstance($id);
		if ($section)
		{
			$model->section($section);
		}

		$entries = Request::getVar('entries', array(0), 'post');

		foreach ($entries as $key => $data)
		{
			// Retrieve user's account info
			$member = \CoursesModelMember::getInstance($data['id'], null, null, null);
			if ($member->get('role_id') == $data['role_id'])
			{
				continue;
			}
			$member->set('role_id', $data['role_id']);
			if (!$member->store())
			{
				$this->setError($member->getError());
			}
		}

		// Push through to the hosts view
		$this->displayTask($model);
	}

	/**
	 * Display a list of 'manager' for a specific section
	 *
	 * @param   object  $model  CoursesModelOffering
	 * @return  void
	 */
	public function displayTask($model=null)
	{
		// Incoming
		if (!($model instanceof \CoursesModelOffering))
		{
			$model = \CoursesModelOffering::getInstance(Request::getInt('offering', 0, 'get'));
			if (($section = Request::getInt('section', 0)))
			{
				$model->section($section);
			}
		}

		$this->view->model = $model;

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view
			->setLayout('display')
			->display();
	}
}

