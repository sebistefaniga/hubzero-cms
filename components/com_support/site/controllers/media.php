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

namespace Components\Support\Site\Controllers;

use Components\Support\Models\Attachment;
use Hubzero\Component\SiteController;
use Hubzero\Utility\Number;
use Hubzero\Component\View;
use Request;
use Lang;
use User;

require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'ticket.php');

/**
 * Collections controller class for media
 */
class Media extends SiteController
{
	/**
	 * Upload a file to the wiki via AJAX
	 *
	 * @return  string
	 */
	public function ajaxUploadTask()
	{
		// Check if they're logged in
		/*if (User::isGuest())
		{
			echo json_encode(array('error' => Lang::txt('Must be logged in.')));
			return;
		}*/

		// Ensure we have an ID to work with
		$ticket  = Request::getInt('ticket', 0);
		$comment = Request::getInt('comment', 0);
		if (!$ticket)
		{
			echo json_encode(array('error' => Lang::txt('COM_SUPPORT_NO_ID'), 'ticket' => $ticket));
			return;
		}

		//max upload size
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
			echo json_encode(array('error' => Lang::txt('File not found')));
			return;
		}

		//define upload directory and make sure its writable
		$path = PATH_APP . DS . trim($this->config->get('webpath', '/site/tickets'), DS) . DS . $ticket;
		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				echo json_encode(array('error' => Lang::txt('Error uploading. Unable to create path.')));
				return;
			}
		}

		if (!is_writable($path))
		{
			echo json_encode(array('error' => Lang::txt('Server error. Upload directory isn\'t writable.')));
			return;
		}

		//check to make sure we have a file and its not too big
		if ($size == 0)
		{
			echo json_encode(array('error' => Lang::txt('File is empty')));
			return;
		}
		if ($size > $sizeLimit)
		{
			$max = preg_replace('/<abbr \w+=\\"\w+\\">(\w{1,3})<\\/abbr>/', '$1', Number::formatBytes($sizeLimit));
			echo json_encode(array('error' => Lang::txt('File is too large. Max file upload size is %s', $max)));
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
		while (file_exists($path . DS . $filename . '.' . $ext))
		{
			$filename .= rand(10, 99);
		}

		//make sure that file is acceptable type
		if (!in_array(strtolower($ext), explode(',', $this->config->get('file_ext'))))
		{
			echo json_encode(array('error' => Lang::txt('COM_SUPPORT_ERROR_INCORRECT_FILE_TYPE')));
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

		if (!\JFile::isSafe($file))
		{
			if (\JFile::delete($file))
			{
				echo json_encode(array(
					'success' => false,
					'error'   => Lang::txt('ATTACHMENT: File rejected because the anti-virus scan failed.')
				));
				return;
			}
		}

		// Create database entry
		$asset = new Attachment();
		$asset->bind(array(
			'id'          => 0,
			'ticket'      => $ticket,
			'comment_id'  => $comment,
			'filename'    => $filename . '.' . $ext,
			'description' => Request::getVar('description', '')
		));
		if (!$asset->store(true))
		{
			echo json_encode(array(
				'success' => false,
				'error'   => $asset->getError()
			));
			return;
		}

		$view = new View(array(
			'name'   => 'media',
			'layout' => '_asset'
		));
		$view->option     = $this->_option;
		$view->controller = $this->_controller;
		$view->asset      = $asset;
		$view->no_html    = 1;

		//echo result
		echo json_encode(array(
			'success'    => true,
			'file'       => $filename . '.' . $ext,
			'directory'  => str_replace(PATH_APP, '', $path),
			'ticket'     => $ticket,
			'comment_id' => $comment,
			'html'       => str_replace('>', '&gt;',  $view->loadTemplate()) // Entities have to be encoded or IE 8 goes nuts
		));
	}

	/**
	 * Upload a file
	 *
	 * @return     void
	 */
	public function uploadTask()
	{
		// Check if they're logged in
		/*if (User::isGuest())
		{
			$this->displayTask();
			return;
		}*/

		if (Request::getVar('no_html', 0))
		{
			return $this->ajaxUploadTask();
		}

		// Ensure we have an ID to work with
		$ticket  = Request::getInt('ticket', 0, 'post');
		$comment = Request::getInt('comment', 0, 'post');
		if (!$ticket)
		{
			$this->setError(Lang::txt('COM_SUPPORT_NO_ID'));
			$this->displayTask();
			return;
		}

		// Incoming file
		$file = Request::getVar('upload', '', 'files', 'array');
		if (!$file['name'])
		{
			$this->setError(Lang::txt('COM_SUPPORT_NO_FILE'));
			$this->displayTask();
			return;
		}

		// Build the upload path if it doesn't exist
		$path = PATH_APP . DS . trim($this->config->get('filepath', '/site/tickets'), DS) . DS . $ticket;

		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				$this->setError(Lang::txt('Error uploading. Unable to create path.'));
				$this->displayTask();
				return;
			}
		}

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$file['name'] = urldecode($file['name']);
		$file['name'] = \JFile::makeSafe($file['name']);
		$file['name'] = str_replace(' ', '_', $file['name']);

		$ext = \JFile::getExt($file['name']);
		$filename = \JFile::stripExt($file['name']);
		while (file_exists($path . DS . $filename . '.' . $ext))
		{
			$filename .= rand(10, 99);
		}

		//make sure that file is acceptable type
		if (!in_array($ext, explode(',', $this->config->get('file_ext'))))
		{
			$this->setError(Lang::txt('COM_SUPPORT_ERROR_INCORRECT_FILE_TYPE'));
			echo $this->getError();
			return;
		}

		$filename .= '.' . $ext;

		// Upload new files
		if (!\JFile::upload($file['tmp_name'], $path . DS . $filename))
		{
			$this->setError(Lang::txt('ERROR_UPLOADING'));
		}
		// File was uploaded
		else
		{
			$fle = $path . DS . $filename;

			if (!\JFile::isSafe($file))
			{
				if (\JFile::delete($file))
				{
					$this->setError(Lang::txt('ATTACHMENT: File rejected because the anti-virus scan failed.'));
					echo $this->getError();
					return;
				}
			}

			// Create database entry
			$asset = new Attachment();
			$asset->bind(array(
				'id'          => 0,
				'ticket'      => $ticket,
				'comment_id'  => $comment,
				'filename'    => $filename,
				'description' => Request::getVar('description', '')
			));

			if (!$asset->store(true))
			{
				$this->setError($asset->getError());
			}
		}

		// Push through to the media view
		$this->displayTask();
	}

	/**
	 * Delete a file
	 *
	 * @return     void
	 */
	public function deleteTask()
	{
		if (Request::getVar('no_html', 0))
		{
			return $this->ajaxDeleteTask();
		}

		// Incoming asset
		$id = Request::getInt('asset', 0, 'get');

		$model = new Attachment($id);

		if ($model->exists())
		{
			// Check if they're logged in when the ticket ID
			// is > 0. This means it's an attachment on a real
			// ticket, not a temp.
			if ($model->get('ticket') > 0 && User::isGuest())
			{
				$this->displayTask();
				return;
			}
			$model->delete();
		}

		// Push through to the media view
		$this->displayTask();
	}

	/**
	 * Display a form for uploading files
	 *
	 * @return     void
	 */
	public function ajaxDeleteTask()
	{
		// Incoming
		$id = Request::getInt('asset', 0);

		if ($id)
		{
			$model = new Attachment($id);

			if ($model->exists())
			{
				if (!$model->delete())
				{
					echo json_encode(array(
						'success' => false,
						'error'   => $model->getError()
					));
					return;
				}
			}
		}

		//echo result
		echo json_encode(array(
			'success' => true,
			'asset'   => $id
		));
	}

	/**
	 * Display a list of files
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Incoming
		$ticket  = Request::getInt('ticket', 0);
		$comment = Request::getInt('comment', 0);

		if (!$ticket)
		{
			$this->setError(Lang::txt('COM_COLLECTIONS_NO_ID'));
		}

		if ($comment)
		{
			$model = new Comment($comment);
		}
		else
		{
			$model = new Ticket($ticket);
		}

		$this->view->model   = $model;
		$this->view->config  = $this->config;
		$this->view->ticket  = $ticket;
		$this->view->comment = $comment;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view
			->setLayout('list')
			->display();
	}
}

