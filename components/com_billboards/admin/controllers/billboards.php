<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
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
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\BillBoards\Admin\Controllers;

use Hubzero\Component\AdminController;
use Components\Billboards\Models\Billboard;
use Request;
use Route;
use Lang;
use App;

/**
 * Primary controller for the Billboards component
 */
class BillBoards extends AdminController
{
	/**
	 * Browse the list of billboards
	 *
	 * @return void
	 */
	public function displayTask()
	{
		$this->view->rows = Billboard::all()->paginated()->ordered()->rows();
		$this->view->display();
	}

	/**
	 * Create a billboard
	 *
	 * @return void
	 */
	public function addTask()
	{
		$this->view->setLayout('edit');
		$this->view->task = 'edit';
		$this->editTask();
	}

	/**
	 * Edit a billboard
	 *
	 * @param  object $billboard
	 * @return void
	 */
	public function editTask($billboard=null)
	{
		// Hide the menu, force users to save or cancel
		Request::setVar('hidemainmenu', 1);

		if (!isset($billboard) || !is_object($billboard))
		{
			// Incoming - expecting an array
			$cid = Request::getVar('cid', array(0));
			if (!is_array($cid))
			{
				$cid = array($cid);
			}
			$uid = $cid[0];

			$billboard = Billboard::oneOrNew($uid);
		}

		// Fail if not checked out by current user
		if ($billboard->isCheckedOut())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_BILLBOARDS_ERROR_CHECKED_OUT'),
				'warning'
			);
			return;
		}

		// Are we editing an existing entry?
		if ($billboard->id)
		{
			// Yes, we should check it out first
			$billboard->checkout($this->juser->get('id'));
		}

		// Output the HTML
		$this->view->row = $billboard;
		$this->view->display();
	}

	/**
	 * Save a billboard
	 *
	 * @return void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming, make sure to allow HTML to pass through
		$data = Request::getVar('billboard', array(), 'post', 'array', JREQUEST_ALLOWHTML);

		// Create object
		$billboard = Billboard::oneOrNew($data['id'])->set($data);

		if (!$billboard->save())
		{
			// Something went wrong...return errors
			foreach ($billboard->getErrors() as $error)
			{
				$this->view->setError($error);
			}

			$this->view->setLayout('edit');
			$this->view->task = 'edit';
			$this->editTask($billboard);
			return;
		}

		// See if we have an image coming in as well
		$billboard_image = Request::getVar('billboard-image', false, 'files', 'array');

		// If so, proceed with saving the image
		if (isset($billboard_image['name']) && $billboard_image['name'])
		{
			// Build the upload path if it doesn't exist
			$image_location  = $this->config->get('image_location', 'site' . DS . 'media' . DS . 'images' . DS . 'billboards');
			$uploadDirectory = PATH_APP . DS . trim($image_location, DS) . DS;

			// Make sure upload directory exists and is writable
			if (!is_dir($uploadDirectory))
			{
				if (!\JFolder::create($uploadDirectory))
				{
					$this->view->setError(Lang::txt('COM_BILLBOARDS_ERROR_UNABLE_TO_CREATE_UPLOAD_PATH'));
					$this->view->setLayout('edit');
					$this->view->task = 'edit';
					$this->editTask($billboard);
					return;
				}
			}

			// Scan for viruses
			if (!\JFile::isSafe($billboard_image['tmp_name']))
			{
				$this->view->setError(Lang::txt('COM_BILLBOARDS_ERROR_FAILED_VIRUS_SCAN'));
				$this->view->setLayout('edit');
				$this->view->task = 'edit';
				$this->editTask($billboard);
				return;
			}

			if (!move_uploaded_file($billboard_image['tmp_name'], $uploadDirectory . $billboard_image['name']))
			{
				$this->view->setError(Lang::txt('COM_BILLBOARDS_ERROR_FILE_MOVE_FAILED'));
				$this->view->setLayout('edit');
				$this->view->task = 'edit';
				$this->editTask($billboard);
				return;
			}
			else
			{
				// Move successful, save the image url to the billboard entry
				$billboard->set('background_img', $billboard_image['name'])->save();
			}
		}

		// Check in the billboard now that we've saved it
		$billboard->checkin();

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_BILLBOARDS_BILLBOARD_SUCCESSFULLY_SAVED')
		);
	}

	/**
	 * Save the new order
	 *
	 * @return void
	 */
	public function saveorderTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Initialize variables
		$cid   = Request::getVar('cid', array(), 'post', 'array');
		$order = Request::getVar('order', array(), 'post', 'array');

		// Make sure we have something to work with
		if (empty($cid))
		{
			App::abort(500, Lang::txt('BILLBOARDS_ORDER_PLEASE_SELECT_ITEMS'));
			return;
		}

		// Update ordering values
		for ($i = 0; $i < count($cid); $i++)
		{
			$billboard = Billboard::oneOrFail($cid[$i]);
			if ($billboard->ordering != $order[$i])
			{
				$billboard->set('ordering', $order[$i]);
				if (!$billboard->save())
				{
					App::abort(500, $billboard->getError());
					return;
				}
			}
		}

		// Clear the component's cache
		$cache = \JFactory::getCache('com_billboards');
		$cache->clean();

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_BILLBOARDS_ORDER_SUCCESSFULLY_UPDATED')
		);
	}

	/**
	 * Delete a billboard
	 *
	 * @return void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming (expecting an array)
		$ids = Request::getVar('cid', array());
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		// Make sure we have IDs to work with
		if (count($ids) > 0)
		{
			// Loop through the array of ID's and delete
			foreach ($ids as $id)
			{
				$billboard = Billboard::oneOrFail($id);

				// Delete record
				if (!$billboard->destroy())
				{
					App::redirect(
						Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
						Lang::txt('COM_BILLBOARDS_ERROR_CANT_DELETE')
					);
					return;
				}
			}
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_BILLBOARDS_BILLBOARD_SUCCESSFULLY_DELETED', count($ids))
		);
	}

	/**
	 * Publish billboards
	 *
	 * @return void
	 */
	public function publishTask()
	{
		$this->toggle(1);
	}

	/**
	 * Unpublish billboards
	 *
	 * @return void
	 */
	public function unpublishTask()
	{
		$this->toggle(0);
	}

	/**
	 * Cancels out of the billboard edit view, makes sure to check the billboard back in for other people to edit
	 *
	 * @return void
	 */
	public function cancelTask()
	{
		// Incoming - we need an id so that we can check it back in
		$fields = Request::getVar('billboard', array(), 'post');

		// Check the billboard back in
		$billboard = Billboard::oneOrNew($fields['id']);
		$billboard->checkin();

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}

	/**
	 * Toggle a billboard between published and unpublished.  We're looking for an array of ID's to publish/unpublish
	 *
	 * @param  $publish: 1 to publish and 0 for unpublish
	 * @return void
	 */
	protected function toggle($publish=1)
	{
		// Check for request forgeries
		Request::checkToken('get') or Request::checkToken() or jexit('Invalid Token');

		// Incoming (we're expecting an array)
		$ids = Request::getVar('cid', array());
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		// Loop through the IDs
		foreach ($ids as $id)
		{
			// Load the billboard
			$row = Billboard::oneOrFail($id);

			// Only alter items not checked out or checked out by 'me'
			if (!$row->isCheckedOut())
			{
				$row->set('published', $publish);
				if (!$row->save())
				{
					App::abort(500, $row->getError());
					return;
				}
				// Check it back in
				$row->checkin();
			}
			else
			{
				App::redirect(
					Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
					Lang::txt('COM_BILLBOARDS_ERROR_CHECKED_OUT'),
					'warning'
				);
				return;
			}
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}
}