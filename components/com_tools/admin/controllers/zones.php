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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

include_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'middleware.php');

/**
 * Administrative tools controller for zones
 */
class ToolsControllerZones extends \Hubzero\Component\AdminController
{
	/**
	 * Determines task being called and attempts to execute it
	 *
	 * @return	void
	 */
	public function execute()
	{
		if (!$this->config->get('zones'))
		{
			App::redirect(Route::url('index.php?option=' . $this->_option, false));
			return;
		}

		$this->registerTask('add', 'edit');
		$this->registerTask('apply', 'save');

		parent::execute();
	}

	/**
	 * Display a list of hosts
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Get filters
		$this->view->filters = array(
			'zone' => urldecode(Request::getState(
				$this->_option . '.' . $this->_controller . '.zone',
				'zone',
				''
			)),
			'master' => urldecode(Request::getState(
				$this->_option . '.' . $this->_controller . '.master',
				'master',
				''
			)),
			// Sorting
			'sort' => Request::getState(
				$this->_option . '.' . $this->_controller . '.sort',
				'filter_order',
				'zone'
			),
			'sort_Dir' => Request::getState(
				$this->_option . '.' . $this->_controller . '.sortdir',
				'filter_order_Dir',
				'ASC'
			),
			// Get paging variables
			'limit' => Request::getState(
				$this->_option . '.' . $this->_controller . '.limit',
				'limit',
				Config::get('list_limit'),
				'int'
			),
			'start' => Request::getState(
				$this->_option . '.' . $this->_controller . '.limitstart',
				'limitstart',
				0,
				'int'
			)
		);

		// In case limit has been changed, adjust limitstart accordingly
		$this->view->filters['start'] = ($this->view->filters['limit'] != 0 ? (floor($this->view->filters['start'] / $this->view->filters['limit']) * $this->view->filters['limit']) : 0);

		// Get the middleware
		$model = new ToolsModelMiddleware();

		$this->view->total = $model->zones('count', $this->view->filters);

		$this->view->rows  = $model->zones('list', $this->view->filters);

		// Initiate paging
		jimport('joomla.html.pagination');
		$this->view->pageNav = new JPagination(
			$this->view->total,
			$this->view->filters['start'],
			$this->view->filters['limit']
		);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Display results
		$this->view->display();
	}

	/**
	 * Edit a record
	 *
	 * @param   mixed  $row
	 * @return  void
	 */
	public function editTask($row=null)
	{
		Request::setVar('hidemainmenu', 1);

		// Get the middleware database
		$mwdb = ToolsHelperUtils::getMWDBO();

		$mw = new ToolsModelMiddleware($mwdb);

		if (!is_object($row))
		{
			// Incoming
			$id = Request::getInt('id', 0);

			$row = new MiddlewareModelZone($id);
		}

		$this->view->row = $row;

		if (!$this->view->row->exists())
		{
			$this->view->row->set('state', 'down');
		}

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Display results
		$this->view
			->setLayout('edit')
			->display();
	}

	/**
	 * Save changes to a record
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$fields = Request::getVar('fields', array(), 'post');

		$row = new MiddlewareModelZone($fields['id']);
		if (!$row->bind($fields))
		{
			Notify::error($row->getError());
			return $this->editTask($row);
		}

		// Store new content
		if (!$row->store(true))
		{
			Notify::error($row->getError());
			return $this->editTask($row);
		}

		/*$vl = new MwZoneLocations($mwdb);
		$vl->deleteByZone($row->id);

		$locations = Request::getVar('locations', array(), 'post');
		foreach ($locations as $location)
		{
			$vl = new MwZoneLocations($mwdb);
			$vl->zone_id = $row->id;
			$vl->location = $location;
			if (!$vl->check())
			{
				Notify::error($vl->getError());
				return $this->editTask($row);
			}
			if (!$vl->store())
			{
				Notify::error($vl->getError());
				return $this->editTask($row);
			}
		}*/
		Notify::success(Lang::txt('COM_TOOLS_ITEM_SAVED'));

		if ($this->getTask() == 'apply')
		{
			return $this->editTask($row);
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}

	/**
	 * Toggle a zone's state
	 *
	 * @return     void
	 */
	public function stateTask()
	{
		// Check for request forgeries
		Request::checkToken('get') or jexit('Invalid Token');

		// Incoming
		$id = Request::getInt('id', 0);
		$state = strtolower(Request::getWord('state', 'up'));

		if ($state != 'up' && $state != 'down')
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
			);
			return;
		}

		// Get the middleware database
		$mwdb = ToolsHelperUtils::getMWDBO();

		$row = new MwZones($mwdb);
		if ($row->load($id))
		{
			$row->state = $state;
			if (!$row->store())
			{
				Notify::error(Lang::txt('COM_TOOLS_ERROR_STATE_UPDATE_FAILED'));
			}
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}

	/**
	 * Delete one or more records
	 *
	 * @return     void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = Request::getVar('id', array());

		$mwdb = ToolsHelperUtils::getMWDBO();

		if (count($ids) > 0)
		{
			$row = new MwZones($mwdb);

			// Loop through each ID
			foreach ($ids as $id)
			{
				if (!$row->delete(intval($id)))
				{
					throw new Exception($row->getError(), false);
				}
			}
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_TOOLS_ITEM_DELETED'),
			'message'
		);
	}

	/**
	 * Upload a file to the wiki via AJAX
	 *
	 * @return  string
	 */
	public function ajaxUploadTask()
	{
		// Check for request forgeries
		Request::checkToken('get') or Request::checkToken() or jexit('Invalid Token');

		// Ensure we have an ID to work with
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_MISSING_ID')));
			return;
		}

		$zone = MiddlewareModelZone::getInstance($id);

		// Build the path
		$path = $zone->logo('path');

		if (!$path)
		{
			echo json_encode(array('error' => $this->getError()));
			return;
		}

		// allowed extensions for uplaod
		$allowedExtensions = array('png','jpeg','jpg','gif');

		// max upload size
		$sizeLimit = $this->config->get('maxAllowed', 40000000);

		// get the file
		if (isset($_GET['qqfile']))
		{
			$stream = true;
			$file = $_GET['qqfile'];
			$size = (int) $_SERVER["CONTENT_LENGTH"];
		}
		elseif (isset($_FILES['qqfile']))
		{
			$stream = false;
			$file = $_FILES['qqfile']['name'];
			$size = (int) $_FILES['qqfile']['size'];
		}
		else
		{
			echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_NO_FILE')));
			return;
		}

		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_UNABLE_TO_CREATE_UPLOAD_PATH')));
				return;
			}
		}

		if (!is_writable($path))
		{
			echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_DIRECTORY_NOT_WRITABLE')));
			return;
		}

		// check to make sure we have a file and its not too big
		if ($size == 0)
		{
			echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_EMPTY_FILE')));
			return;
		}
		if ($size > $sizeLimit)
		{
			$max = preg_replace('/<abbr \w+=\\"\w+\\">(\w{1,3})<\\/abbr>/', '$1', \Hubzero\Utility\Number::formatBytes($sizeLimit));
			echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_FILE_TOO_LARGE', $max)));
			return;
		}

		// don't overwrite previous files that were uploaded
		$pathinfo = pathinfo($file);
		$filename = $pathinfo['filename'];

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$filename = urldecode($filename);
		$filename = \JFile::makeSafe($filename);
		$filename = str_replace(' ', '_', $filename);

		$ext = $pathinfo['extension'];
		if (!in_array(strtolower($ext), $allowedExtensions))
		{
			echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_INVALID_FILE_TYPE')));
			return;
		}

		$file = $path . DS . $filename . '.' . $ext;

		if ($stream)
		{
			//read the php input stream to upload file
			$input = fopen("php://input", "r");
			$temp = tmpfile();
			$realSize = stream_copy_to_stream($input, $temp);
			fclose($input);

			//move from temp location to target location which is user folder
			$target = fopen($file , "w");
			fseek($temp, 0, SEEK_SET);
			stream_copy_to_stream($temp, $target);
			fclose($target);
		}
		else
		{
			move_uploaded_file($_FILES['qqfile']['tmp_name'], $file);
		}

		// Do we have an old file we're replacing?
		if ($curfile = $zone->get('picture'))
		{
			// Remove old image
			if (file_exists($path . DS . $curfile))
			{
				if (!\JFile::delete($path . DS . $curfile))
				{
					echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_UNABLE_TO_DELETE_FILE')));
					return;
				}
			}
		}

		$zone->set('picture', $filename . '.' . $ext);
		if (!$zone->store())
		{
			echo json_encode(array('error' => $zone->getError()));
			return;
		}

		$this_size = filesize($file);
		list($width, $height, $type, $attr) = getimagesize($file);

		//echo result
		echo json_encode(array(
			'success'   => true,
			'file'      => $filename . '.' . $ext,
			'directory' => str_replace(PATH_APP, '', $path),
			'id'        => $id,
			'size'      => \Hubzero\Utility\Number::formatBytes($this_size),
			'width'     => $width,
			'height'    => $height
		));
	}

	/**
	 * Upload a file
	 *
	 * @return  void
	 */
	public function uploadTask()
	{
		if (Request::getVar('no_html', 0))
		{
			return $this->ajaxUploadTask();
		}

		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('COM_COURSES_NO_ID'));
			$this->pictureTask('', $id);
			return;
		}

		$zone = MiddlewareModelZone::getInstance($id);

		// Build the path
		$path = $zone->logo('path');

		if (!$path)
		{
			$this->pictureTask('', $id);
			return;
		}

		// Incoming file
		$file = Request::getVar('upload', '', 'files', 'array');
		if (!$file['name'])
		{
			$this->setError(Lang::txt('COM_TOOLS_ERROR_NO_FILE'));
			$this->pictureTask('', $id);
			return;
		}
		$curfile = Request::getVar('curfile', '');

		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				$this->setError(Lang::txt('COM_TOOLS_ERROR_UNABLE_TO_CREATE_UPLOAD_PATH'));
				$this->pictureTask('', $id);
				return;
			}
		}

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$file['name'] = \JFile::makeSafe($file['name']);
		$file['name'] = str_replace(' ', '_', $file['name']);

		// Perform the upload
		if (!\JFile::upload($file['tmp_name'], $path . DS . $file['name']))
		{
			$this->setError(Lang::txt('COM_TOOLS_ERROR_UPLOADING'));
			$file = $curfile;
		}
		else
		{
			// Do we have an old file we're replacing?
			if ($curfile = $zone->get('picture'))
			{
				// Remove old image
				if (file_exists($path . DS . $curfile))
				{
					if (!\JFile::delete($path . DS . $curfile))
					{
						$this->setError(Lang::txt('COM_TOOLS_ERROR_UNABLE_TO_DELETE_FILE'));
						$this->pictureTask($file['name'], $id);
						return;
					}
				}
			}

			$zone->set('picture', $file['name']);
			if (!$zone->store())
			{
				$this->setError($zone->getError());
			}

			$file = $file['name'];
		}

		// Push through to the image view
		$this->pictureTask($file, $id);
	}

	/**
	 * Upload a file to the wiki via AJAX
	 *
	 * @return  string
	 */
	public function ajaxRemoveTask()
	{
		// Check for request forgeries
		Request::checkToken('get') or Request::checkToken() or jexit('Invalid Token');

		// Ensure we have an ID to work with
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_MISSING_ID')));
			return;
		}

		$zone = MiddlewareModelZone::getInstance($id);

		// Build the path
		$path = $zone->logo('path');
		if (!$path)
		{
			echo json_encode(array('error' => $this->getError()));
			return;
		}

		$file = $zone->get('picture');

		if (!file_exists($path . DS . $file) or !$file)
		{
			$this->setError(Lang::txt('COM_TOOLS_ERROR_FILE_NOT_FOUND'));
		}
		else
		{
			// Attempt to delete the file
			jimport('joomla.filesystem.file');
			if (!\JFile::delete($path . DS . $file))
			{
				echo json_encode(array('error' => Lang::txt('COM_TOOLS_ERROR_UNABLE_TO_DELETE_FILE')));
				return;
			}
		}

		// Instantiate a model, change some info and save
		$zone->set('picture', '');
		if (!$zone->store())
		{
			echo json_encode(array('error' => $zone->getError()));
			return;
		}

		//echo result
		echo json_encode(array(
			'success'   => true,
			'file'      => '',
			'directory' => str_replace(PATH_APP, '', $path),
			'id'        => $id,
			'size'      => 0,
			'width'     => 0,
			'height'    => 0
		));
	}

	/**
	 * Delete a file
	 *
	 * @return  void
	 */
	public function removefileTask()
	{
		if (Request::getVar('no_html', 0))
		{
			return $this->ajaxRemoveTask();
		}

		// Check for request forgeries
		Request::checkToken('get') or jexit('Invalid Token');

		// Incoming member ID
		$id = Request::getInt('id', 0);
		if (!$id)
		{
			$this->setError(Lang::txt('COM_TOOLS_ERROR_MISSING_ID'));
			$this->pictureTask('', $id);
			return;
		}

		$zone = MiddlewareModelZone::getInstance($id);

		// Build the file path
		$path = $zone->logo('path');
		$file = $zone->get('picture');

		if (!file_exists($path . DS . $file) or !$file)
		{
			$this->setError(Lang::txt('COM_TOOLS_ERROR_FILE_NOT_FOUND'));
		}
		else
		{
			// Attempt to delete the file
			jimport('joomla.filesystem.file');
			if (!\JFile::delete($path . DS . $file))
			{
				$this->setError(Lang::txt('COM_TOOLS_ERROR_UNABLE_TO_DELETE_FILE'));
				$this->pictureTask($file, $id);
				return;
			}

			$zone->set('picture', '');
			if (!$zone->store())
			{
				$this->setError($zone->getError());
			}

			$file = '';
		}

		$this->pictureTask($file, $id);
	}

	/**
	 * Display a file and its info
	 *
	 * @param   string   $file  File name
	 * @param   integer  $id    User ID
	 * @return  void
	 */
	public function pictureTask($file='', $id=0)
	{
		// Incoming
		$id = $id ?: Request::getInt('id', 0);

		$this->view->zone = MiddlewareModelZone::getInstance($id);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view
			->set('config', $this->config)
			->setLayout('picture')
			->display();
	}
}
