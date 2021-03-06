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

namespace Components\Forum\Site\Controllers;

use Hubzero\Component\SiteController;
use Components\Forum\Models\Manager;
use Components\Forum\Models\Section;
use Pathway;
use Request;
use Route;
use User;
use Lang;

/**
 * Controller class for forum sections
 */
class Sections extends SiteController
{
	/**
	 * Determine task and execute
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->model = new Manager('site', 0);

		parent::execute();
	}

	/**
	 * Display a list of sections and their categories
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Get authorization
		$this->_authorize('section');
		$this->_authorize('category');

		$this->view->config = $this->config;

		$this->view->title = Lang::txt('COM_FORUM');

		// Incoming
		$this->view->filters = array(
			//'authorized' => 1,
			'scope'      => $this->model->get('scope'),
			'scope_id'   => $this->model->get('scope_id'),
			'search'     => Request::getVar('q', ''),
			//'section_id' => 0,
			'state'      => 1,
			// Show based on if logged in or not
			'access'     => (User::isGuest() ? 0 : array(0, 1))
		);

		// Flag to indicate if a section is being put into edit mode
		$this->view->edit = null;
		if (($section = Request::getVar('section', '')) && $this->_task == 'edit')
		{
			$this->view->edit = $section;
		}
		$this->view->sections = $this->model->sections('list', array('state' => 1));

		$this->view->model = $this->model;

		if (!$this->view->sections->total()
		 && $this->config->get('access-create-section')
		 && Request::getWord('action') == 'populate')
		{
			if (!$this->model->setup())
			{
				$this->setError($this->model->getError());
			}
			$this->view->sections = $this->model->sections('list', array('state' => 1));
		}

		// Set the page title
		$this->_buildTitle();

		// Set the pathway
		$this->_buildPathway();

		$this->view->notifications = $this->getComponentMessage();

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Saves a section and redirects to main page afterward
	 *
	 * @return     void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming posted data
		$fields = Request::getVar('fields', array(), 'post');
		$fields = array_map('trim', $fields);

		// Instantiate a new table row and bind the incoming data
		$section = new Section($fields['id']);
		if (!$section->bind($fields))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option),
				$section->getError(),
				'error'
			);
			return;
		}

		// Store new content
		if (!$section->store(true))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option),
				$section->getError(),
				'error'
			);
			return;
		}

		// Set the redirect
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option)
		);
	}

	/**
	 * Deletes a section and redirects to main page afterwards
	 *
	 * @return     void
	 */
	public function deleteTask()
	{
		// Is the user logged in?
		if (User::isGuest())
		{
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode(Route::url('index.php?option=' . $this->_option, false, true))),
				Lang::txt('COM_FORUM_LOGIN_NOTICE'),
				'warning'
			);
			return;
		}

		// Load the section
		$section = $this->model->section(Request::getVar('section', ''));

		// Make the sure the section exist
		if (!$section->exists())
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option),
				Lang::txt('COM_FORUM_MISSING_ID'),
				'error'
			);
			return;
		}

		// Check if user is authorized to delete entries
		$this->_authorize('section', $section->get('id'));

		if (!$this->config->get('access-delete-section'))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option),
				Lang::txt('COM_FORUM_NOT_AUTHORIZED'),
				'warning'
			);
			return;
		}

		// Set the section to "deleted"
		$section->set('state', 2);  /* 0 = unpublished, 1 = published, 2 = deleted */

		if (!$section->store())
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option),
				$model->getError(),
				'error'
			);
			return;
		}

		// Redirect to main listing
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option),
			Lang::txt('COM_FORUM_SECTION_DELETED'),
			'message'
		);
	}

	/**
	 * Set the authorization level for the user
	 *
	 * @return     void
	 */
	protected function _authorize($assetType='component', $assetId=null)
	{
		$this->config->set('access-view-' . $assetType, true);
		if (!User::isGuest())
		{
			$asset  = $this->_option;
			if ($assetId)
			{
				$asset .= ($assetType != 'component') ? '.' . $assetType : '';
				$asset .= ($assetId) ? '.' . $assetId : '';
			}

			$at = '';
			if ($assetType != 'component')
			{
				$at .= '.' . $assetType;
			}

			// Admin
			$this->config->set('access-admin-' . $assetType, User::authorise('core.admin', $asset));
			$this->config->set('access-manage-' . $assetType, User::authorise('core.manage', $asset));
			// Permissions
			if ($assetType == 'post' || $assetType == 'thread')
			{
				$this->config->set('access-create-' . $assetType, true);
				$val = User::authorise('core.create' . $at, $asset);
				if ($val !== null)
				{
					$this->config->set('access-create-' . $assetType, $val);
				}

				$this->config->set('access-edit-' . $assetType, true);
				$val = User::authorise('core.edit' . $at, $asset);
				if ($val !== null)
				{
					$this->config->set('access-edit-' . $assetType, $val);
				}

				$this->config->set('access-edit-own-' . $assetType, true);
				$val = User::authorise('core.edit.own' . $at, $asset);
				if ($val !== null)
				{
					$this->config->set('access-edit-own-' . $assetType, $val);
				}
			}
			else
			{
				$this->config->set('access-create-' . $assetType, User::authorise('core.create' . $at, $asset));
				$this->config->set('access-edit-' . $assetType, User::authorise('core.edit' . $at, $asset));
				$this->config->set('access-edit-own-' . $assetType, User::authorise('core.edit.own' . $at, $asset));
			}

			$this->config->set('access-delete-' . $assetType, User::authorise('core.delete' . $at, $asset));
			$this->config->set('access-edit-state-' . $assetType, User::authorise('core.edit.state' . $at, $asset));
		}
	}

	/**
	 * Method to set the document path
	 *
	 * @return	void
	 */
	protected function _buildPathway()
	{
		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		if ($this->_task)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option) . '_' . strtoupper($this->_task)),
				'index.php?option=' . $this->_option . '&task=' . $this->_task
			);
		}
	}

	/**
	 * Method to build and set the document title
	 *
	 * @return	void
	 */
	protected function _buildTitle()
	{
		$this->_title = Lang::txt(strtoupper($this->_option));
		if ($this->_task)
		{
			$this->_title .= ': ' . Lang::txt(strtoupper($this->_option) . '_' . strtoupper($this->_task));
		}
		$document = \JFactory::getDocument();
		$document->setTitle($this->_title);
	}
}