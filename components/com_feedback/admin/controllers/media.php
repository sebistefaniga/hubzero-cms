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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Feedback\Admin\Controllers;

use Hubzero\Component\AdminController;
use Hubzero\Utility\String;
use Request;
use Lang;

/**
 * Feedback controller class for handling media (files)
 */
class Media extends AdminController
{
	/**
	 * Execute a task
	 *
	 * @return     void
	 */
	public function execute()
	{
		$this->type = Request::getVar('type', '', 'post');

		if (!$this->type)
		{
			$this->type = Request::getVar('type', 'regular', 'get');
		}
		$this->type = ($this->type == 'regular') ? $this->type : 'selected';

		parent::execute();
	}

	/**
	 * Upload an image
	 *
	 * @return     void
	 */
	public function uploadTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('FEEDBACK_NO_ID'));
			$this->displayTask('', $id);
			return;
		}

		// Incoming file
		$file = Request::getVar('upload', '', 'files', 'array');
		if (!$file['name'])
		{
			$this->setError(Lang::txt('FEEDBACK_NO_FILE'));
			$this->displayTask('', $id);
			return;
		}

		$row = new Quote($this->database);

		// Build upload path
		$path = $row->filespace() . DS . String::pad($id);

		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				$this->setError(Lang::txt('UNABLE_TO_CREATE_UPLOAD_PATH'));
				$this->displayTask('', $id);
				return;
			}
		}

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$file['name'] = \JFile::makeSafe($file['name']);
		$file['name'] = str_replace(' ', '_', $file['name']);

		$qid = Request::getInt('qid', 0);

		// Perform the upload
		if (!\JFile::upload($file['tmp_name'], $path . DS . $file['name']))
		{
			$this->setError(Lang::txt('ERROR_UPLOADING'));
			$file = $curfile;
		}
		else
		{
			$row = new Quote($this->database);
			$row->load($qid);

			// Do we have an old file we're replacing?
			$curfile = $row->picture;

			if ($curfile != '' && $curfile != $file['name'])
			{
				// Yes - remove it
				if (file_exists($path . DS . $curfile))
				{
					if (!\JFile::delete($path . DS . $curfile))
					{
						$this->setError(Lang::txt('UNABLE_TO_DELETE_FILE'));
						$this->displayTask($file['name'], $id);
						return;
					}
				}
			}

			$file = $file['name'];

			$row->picture = $file;
			if (!$row->store())
			{
				$this->setError($row->getError());
			}
		}

		// Push through to the image view
		$this->displayTask($file, $id, $qid);
	}

	/**
	 * Delete a file
	 *
	 * @return     void
	 */
	public function deleteTask()
	{
		// Check for request forgeries
		Request::checkToken('get') or jexit('Invalid Token');

		// Incoming member ID
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('FEEDBACK_NO_ID'));
			$this->displayTask('', $id);
			return;
		}

		$qid = Request::getInt('qid', 0);

		$row = new Quote($this->database);
		$row->load($qid);

		// Incoming file
		if (!$row->picture)
		{
			$this->setError(Lang::txt('FEEDBACK_NO_FILE'));
			$this->displayTask('', $id);
			return;
		}

		// Build the file path
		$path = $row->filespace() . DS . String::pad($id);

		if (!file_exists($path . DS . $row->picture) or !$row->picture)
		{
			$this->setError(Lang::txt('FILE_NOT_FOUND'));
		}
		else
		{
			// Attempt to delete the file
			jimport('joomla.filesystem.file');
			if (!\JFile::delete($path . DS . $row->picture))
			{
				$this->setError(Lang::txt('UNABLE_TO_DELETE_FILE'));
				$this->displayTask($file, $id);
				return;
			}

			$row->picture = '';
			if (!$row->store())
			{
				$this->setError($row->getError());
			}
		}

		// Push through to the image view
		$this->displayTask($row->picture, $id, $qid);
	}

	/**
	 * Display an image
	 *
	 * @param      string  $file File name
	 * @param      integer $id   User ID
	 * @param      integer $qid  Quote ID
	 * @return     void
	 */
	public function displayTask($file='', $id=0, $qid=0)
	{
		$this->view->type = $this->type;

		// Load the component config
		$this->view->config = $this->config;

		// Do have an ID or do we need to get one?
		$this->view->id = ($id) ? $id : Request::getInt('id', 0);

		$this->view->dir = String::pad($this->view->id);

		// Do we have a file or do we need to get one?
		$this->view->file = ($file) ? $file : Request::getVar('file', '');

		$row = new Quote($this->database);

		// Build the directory path
		$this->view->path = $row->filespace(false) . DS . $this->view->dir;

		$this->view->qid = ($qid) ? $qid : Request::getInt('qid', 0);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->setLayout('display')->display();
	}
}

