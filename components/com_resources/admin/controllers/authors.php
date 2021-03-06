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

namespace Components\Resources\Admin\Controllers;

use Components\Resources\Tables\Contributor\Role;
use Components\Resources\Tables\Contributor;
use Hubzero\Component\AdminController;

/**
 * Manage resource authors
 */
class Authors extends AdminController
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

		parent::execute();
	}

	/**
	 * List resource authors
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Get configuration
		$app = \JFactory::getApplication();

		// Get filters
		$this->view->filters = array(
			'search' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.search',
				'search',
				''
			),
			// Get sorting variables
			'sort' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.sort',
				'filter_order',
				'name'
			),
			'sort_Dir' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.sortdir',
				'filter_order_Dir',
				'ASC'
			),
			// Get paging variables
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

		$obj = new Contributor($this->database);

		// Get record count
		$this->view->total = $obj->getAuthorCount($this->view->filters);

		// Get records
		$this->view->rows = $obj->getAuthorRecords($this->view->filters);

		$this->view->display();
	}

	/**
	 * Edit an entry
	 *
	 * @return  void
	 */
	public function editTask($rows=null)
	{
		Request::setVar('hidemainmenu', 1);

		require_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'contributor' . DS . 'role.php');
		require_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'contributor' . DS . 'roletype.php');

		$authorid = 0;
		if (!is_array($rows))
		{
			// Incoming
			$authorid = Request::getVar('id', array(0));
			if (is_array($authorid))
			{
				$authorid = (!empty($authorid) ? $authorid[0] : 0);
			}

			// Load category
			$obj = new Contributor($this->database);
			$rows = $obj->getRecordsForAuthor($authorid);
		}

		$this->view->rows = $rows;
		$this->view->authorid = $authorid;

		$model = new Role($this->database);
		$this->view->roles = $model->getRecords(array('sort' => 'title'));

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
	 * Save an entry
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$fields   = Request::getVar('fields', array(), 'post');
		$authorid = Request::getVar('authorid', 0);
		$id       = Request::getVar('id', 0);

		if (!$authorid)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
			);
			return;
		}

		$rows = array();
		if (is_array($fields))
		{
			foreach ($fields as $fieldset)
			{
				$rc = new Contributor($this->database);
				$rc->subtable     = 'resources';
				$rc->subid        = trim($fieldset['subid']);
				$rc->authorid     = $authorid;
				$rc->name         = trim($fieldset['name']);
				$rc->organization = trim($fieldset['organization']);
				$rc->role         = $fieldset['role'];
				$rc->ordering     = $fieldset['ordering'];
				if ($authorid != $id)
				{
					if (!$rc->createAssociation())
					{
						$this->addComponentMessage($rc->getError(), 'error');
					}
					if (!$rc->deleteAssociation($id, $rc->subid, $rc->subtable))
					{
						$this->addComponentMessage($rc->getError(), 'error');
					}
				}
				else
				{
					if (!$rc->updateAssociation())
					{
						$this->addComponentMessage($rc->getError(), 'error');
					}
				}

				$rows[] = $rc;
			}
		}

		// Instantiate a resource/contributor association object
		$rc = new Contributor($this->database);

		if ($this->_task == 'apply')
		{
			return $this->editTask($rows);
		}

		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}
}
