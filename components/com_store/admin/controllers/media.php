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

namespace Components\Store\Admin\Controllers;

use Hubzero\Component\AdminController;
use Components\Store\Helpers\ImgHandler;
use Exception;
use DirectoryIterator;

/**
 * Store controller class for handling media (files)
 */
class Media extends AdminController
{
	/**
	 * Upload an image
	 *
	 * @return  void
	 */
	public function uploadTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('COM_STORE_FEEDBACK_NO_ID'));
			$this->displayTask($id);
			return;
		}

		// Incoming file
		$file = Request::getVar('upload', '', 'files', 'array');
		if (!$file['name'])
		{
			$this->setError(Lang::txt('COM_STORE_FEEDBACK_NO_FILE'));
			$this->displayTask($id);
			return;
		}

		// Build upload path
		$path = PATH_APP . DS . trim($this->config->get('webpath', '/site/store'), DS) . DS . $id;

		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				$this->setError(Lang::txt('COM_STORE_UNABLE_TO_CREATE_UPLOAD_PATH'));
				$this->displayTask($id);
				return;
			}
		}

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$file['name'] = \JFile::makeSafe($file['name']);
		$file['name'] = str_replace(' ', '_', $file['name']);

		require_once(dirname(dirname(__DIR__)) . DS . 'helpers' . DS . 'imghandler.php');

		// Perform the upload
		if (!\JFile::upload($file['tmp_name'], $path . DS . $file['name']))
		{
			$this->setError(Lang::txt('COM_STORE_ERROR_UPLOADING'));
		}
		else
		{
			$ih = new ImgHandler();

			// Do we have an old file we're replacing?
			if (($curfile = Request::getVar('currentfile', '')))
			{
				// Remove old image
				if (file_exists($path . DS . $curfile))
				{
					if (!\JFile::delete($path . DS . $curfile))
					{
						$this->setError(Lang::txt('COM_STORE_UNABLE_TO_DELETE_FILE'));
						$this->displayTask($id);
						return;
					}
				}

				// Get the old thumbnail name
				$curthumb = $ih->createThumbName($curfile);

				// Remove old thumbnail
				if (file_exists($path . DS . $curthumb))
				{
					if (!\JFile::delete($path . DS . $curthumb))
					{
						$this->setError(Lang::txt('COM_STORE_UNABLE_TO_DELETE_FILE'));
						$this->displayTask($id);
						return;
					}
				}
			}

			// Create a thumbnail image
			$ih->set('image', $file['name']);
			$ih->set('path', $path . DS);
			$ih->set('maxWidth', 80);
			$ih->set('maxHeight', 80);
			$ih->set('cropratio', '1:1');
			$ih->set('outputName', $ih->createThumbName());
			if (!$ih->process())
			{
				$this->setError($ih->getError());
			}
		}

		// Push through to the image view
		$this->displayTask($id);
	}

	/**
	 * Delete a file
	 *
	 * @return  void
	 */
	public function deleteTask()
	{
		// Check for request forgeries
		Request::checkToken('get') or jexit('Invalid Token');

		// Incoming member ID
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('COM_STORE_FEEDBACK_NO_ID'));
			$this->displayTask($id);
			return;
		}

		// Incoming picture
		$picture = Request::getVar('current', '');

		// Build the file path
		$path = PATH_APP . DS . trim($this->config->get('webpath', '/site/store'), DS) . DS . $id;

		// Attempt to delete the file
		jimport('joomla.filesystem.folder');
		if (!\JFolder::delete($path))
		{
			$this->setError(Lang::txt('COM_STORE_UNABLE_TO_DELETE_FILE'));
			$this->displayTask($id);
			return;
		}

		// Push through to the image view
		$this->displayTask($id);
	}

	/**
	 * Display an image
	 *
	 * @param   integer  $id  Item ID
	 * @return  void
	 */
	public function displayTask($id=0)
	{
		$this->view->type = $this->type;

		// Load the component config
		$this->view->config = $this->config;

		// Do have an ID or do we need to get one?
		$this->view->id = ($id) ? $id : Request::getInt('id', 0);

		// Do we have a file or do we need to get one?
		//$this->view->file = ($file) ? $file : Request::getVar('file', '');
		// Build the directory path
		$this->view->path = DS . trim($this->config->get('webpath', '/site/store'), DS) . DS . $this->view->id;

		$folders = array();
		$docs    = array();
		$imgs    = array();

		$path = PATH_APP . $this->view->path;

		if (is_dir($path))
		{
			jimport('joomla.filesystem.file');

			// Loop through all files and separate them into arrays of images, folders, and other
			$dirIterator = new DirectoryIterator($path);
			foreach ($dirIterator as $file)
			{
				if ($file->isDot())
				{
					continue;
				}

				if ($file->isDir())
				{
					$name = $file->getFilename();
					$folders[$path . DS . $name] = $name;
					continue;
				}

				if ($file->isFile())
				{
					$name = $file->getFilename();
					if (('cvs' == strtolower($name))
					 || ('.svn' == strtolower($name)))
					{
						continue;
					}

					if (preg_match("#bmp|gif|jpg|png|swf#i", $name))
					{
						$base = \JFile::stripExt($name);
						if (substr($base, -3) == '-tn')
						{
							continue;
						}

						$imgs[$path . DS . $name] = $name;
					}
					else
					{
						$docs[$path . DS . $name] = $name;
					}
				}
			}

			ksort($folders);
			ksort($docs);
		}

		$this->view->file = array_shift($imgs);

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

