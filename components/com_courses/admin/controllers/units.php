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

use Components\Courses\Tables;
use Hubzero\Component\AdminController;
use Exception;

require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'unit.php');
require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'offering.php');
require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'course.php');

/**
 * Courses controller class for managing membership and course info
 */
class Units extends AdminController
{
	/**
	 * Execute a task
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->registerTask('add', 'edit');
		$this->registerTask('apply', 'save');
		$this->registerTask('publish', 'state');
		$this->registerTask('unpublish', 'state');
		$this->registerTask('orderup', 'order');
		$this->registerTask('orderdown', 'order');

		parent::execute();
	}

	/**
	 * Displays a list of courses
	 *
	 * @return	void
	 */
	public function displayTask()
	{
		// Get configuration
		$app = \JFactory::getApplication();

		// Incoming
		$this->view->filters = array(
			'offering' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.offering',
				'offering',
				0
			),
			'search' => urldecode($app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.search',
				'search',
				''
			)),
			'state' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.state',
				'state',
				'-1'
			),
			// Filters for returning results
			'limit' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limit',
				'limit',
				Config::get('list_limit'),
				'int'
			),
			'start' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limitstart',
				'limitstart',
				0,
				'int'
			)
		);

		$this->view->offering = \CoursesModelOffering::getInstance($this->view->filters['offering']);
		if (!$this->view->offering->exists())
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=courses', false)
			);
			return;
		}
		$this->view->course = \CoursesModelCourse::getInstance($this->view->offering->get('course_id'));

		// In case limit has been changed, adjust limitstart accordingly
		$this->view->filters['start'] = ($this->view->filters['limit'] != 0 ? (floor($this->view->filters['start'] / $this->view->filters['limit']) * $this->view->filters['limit']) : 0);

		$this->view->filters['count'] = true;

		$this->view->total = $this->view->offering->units($this->view->filters);

		$this->view->filters['count'] = false;

		$this->view->rows = $this->view->offering->units($this->view->filters);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Displays an edit form
	 *
	 * @return	void
	 */
	public function editTask($model=null)
	{
		Request::setVar('hidemainmenu', 1);

		if (!is_object($model))
		{
			// Incoming
			$id = Request::getVar('id', array(0));

			// Get the single ID we're working with
			if (is_array($id))
			{
				$id = (!empty($id)) ? $id[0] : 0;
			}

			$model = \CoursesModelUnit::getInstance($id);
		}

		$this->view->row = $model;

		if (!$this->view->row->get('offering_id'))
		{
			$this->view->row->set('offering_id', Request::getInt('offering', 0));
		}

		$this->view->offering = \CoursesModelOffering::getInstance($this->view->row->get('offering_id'));

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view
			->setLayout('edit')
			->display();
	}

	/**
	 * Saves data to the database
	 *
	 * @param     $redirect boolean Redirect after saving?
	 * @return    void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$fields = Request::getVar('fields', array(), 'post');

		// Instantiate a Course object
		$model = \CoursesModelUnit::getInstance($fields['id']);

		if (!$model->bind($fields))
		{
			$this->addComponentMessage($model->getError(), 'error');
			$this->editTask($model);
			return;
		}

		if (!$model->store(true))
		{
			$this->addComponentMessage($model->getError(), 'error');
			$this->editTask($model);
			return;
		}

		if ($model->get('id') && $model->assetgroups()->total() <= 0)
		{
			$asset_groups = explode(',', $this->config->getValue('default_asset_groups', 'Lectures, Homework, Exam'));
			array_map('trim', $asset_groups);

			foreach ($asset_groups as $key)
			{
				// Get our asset group object
				$assetGroup = new \CoursesModelAssetgroup(null);
				$assetGroup->set('title', $key);
				$assetGroup->set('unit_id', $model->get('id'));
				$assetGroup->set('parent', 0);

				// Save the asset group
				if (!$assetGroup->store(true))
				{
					$this->addComponentMessage($model->getError(), 'error');
				}
			}
		}

		if ($this->_task == 'apply')
		{
			return $this->editTask($model);
		}

		// Output messsage and redirect
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false),
			Lang::txt('COM_COURSES_ITEM_SAVED')
		);
	}

	/**
	 * Copy an entry and all associated data
	 *
	 * @return	void
	 */
	public function copyTask()
	{
		// Incoming
		$ids = Request::getVar('id', 0);

		// Get the single ID we're working with
		if (is_array($id))
		{
			$id = (!empty($id)) ? $id[0] : 0;
		}

		if (!$id)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false),
				Lang::txt('COM_COURSES_ERROR_NO_ID'),
				'error'
			);
			return;
		}

		$unit = \CoursesModelUnit::getInstance($id);
		if (!$unit->copy())
		{
			// Redirect back to the courses page
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . $unit->get('offering_id'), false),
				Lang::txt('COM_COURSES_ERROR_COPY_FAILED') . ': ' . $unit->getError(),
				'error'
			);
			return;
		}

		// Redirect back to the courses page
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . $unit->get('offering_id'), false),
			Lang::txt('COM_COURSES_ITEM_COPIED')
		);
	}

	/**
	 * Removes a course and all associated information
	 *
	 * @return	void
	 */
	public function deleteTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		$num = 0;

		// Do we have any IDs?
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				// Load the course page
				$model = \CoursesModelUnit::getInstance($id);

				// Ensure we found the course info
				if (!$model->exists())
				{
					continue;
				}

				// Delete course
				if (!$model->delete())
				{
					throw new Exception(Lang::txt('COM_COURSES_ERROR_UNABLE_TO_REMOVE_ENTRY'), 500);
				}

				// Log the course approval
				$log = new Tables\Log($this->database);
				$log->scope_id  = $id;
				$log->scope     = 'course_unit';
				$log->user_id   = $this->juser->get('id');
				$log->timestamp = \Date::toSql();
				$log->action    = 'unit_deleted';
				$log->actor_id  = $this->juser->get('id');
				if (!$log->store())
				{
					$this->setError($log->getError());
				}

				$num++;
			}
		}

		// Redirect back to the courses page
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false),
			Lang::txt('COM_COURSES_ITEMS_REMOVED', $num)
		);
	}

	/**
	 * Set the state of an entry
	 *
	 * @return  void
	 */
	public function stateTask()
	{
		$state = $this->_task == 'publish' ? 1 : 0;

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Check for an ID
		if (count($ids) < 1)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false),
				($state == 1 ? Lang::txt('COM_COURSES_SELECT_PUBLISH') : Lang::txt('COM_COURSES_SELECT_UNPUBLISH')),
				'error'
			);
			return;
		}

		// Update record(s)
		foreach ($ids as $id)
		{
			// Updating a category
			$row = new \CoursesModelUnit($id);
			$row->set('state', $state);
			$row->store();
		}

		// Set message
		switch ($state)
		{
			case '-1':
				$message = Lang::txt('COM_COURSES_ARCHIVED', count($ids));
			break;
			case '1':
				$message = Lang::txt('COM_COURSES_PUBLISHED', count($ids));
			break;
			case '0':
				$message = Lang::txt('COM_COURSES_UNPUBLISHED', count($ids));
			break;
		}

		// Set the redirect
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false),
			$message
		);
	}

	/**
	 * Reorder entries
	 *
	 * @return  void
	 */
	public function orderTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		$id = Request::getVar('id', array(0), 'post', 'array');
		\JArrayHelper::toInteger($id, array(0));

		$uid = $id[0];
		$inc = ($this->_task == 'orderup' ? -1 : 1);

		$row = new Tables\Unit($this->database);
		$row->load($uid);
		$row->move($inc, 'offering_id=' . $this->database->Quote($row->offering_id));

		$offering = \CoursesModelOffering::getInstance(Request::getInt('offering', 0));
		foreach ($offering->units() as $unit)
		{
			$unit->store();
		}

		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false)
		);
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return  void
	 */
	public function cancelTask()
	{
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false)
		);
	}
}
