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

namespace Components\Courses\Site\Controllers;

use Hubzero\Component\SiteController;
use Components\Resources\Tables\MediaTrackingDetailed;
use Components\Resources\Tables\MediaTracking;
use stdClass;
use Request;
use Date;
use User;
use Lang;

/**
 * Courses controller class for media
 */
class Media extends SiteController
{
	/**
	 * Track video viewing progress
	 *
	 * @return     void
	 */
	public function trackingTask()
	{
		// Include need media tracking library
		require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'media.tracking.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'media.tracking.detailed.php');

		// Instantiate objects
		$database = \JFactory::getDBO();
		$session  = \JFactory::getSession();

		// Get request vars
		$time       = Request::getVar('time', 0);
		$duration   = Request::getVar('duration', 0);
		$event      = Request::getVar('event', 'update');
		$resourceid = Request::getVar('resourceid', 0);
		$detailedId = Request::getVar('detailedTrackingId', 0);
		$ipAddress  = $_SERVER['REMOTE_ADDR'];

		// Check for asset id
		if (!$resourceid)
		{
			echo 'Unable to find resource identifier.';
			return;
		}

		// Instantiate new media tracking object
		$mediaTracking         = new MediaTracking($database);
		$mediaTrackingDetailed = new MediaTrackingDetailed($database);

		// Load tracking information for user for this resource
		$trackingInformation         = $mediaTracking->getTrackingInformationForUserAndResource(User::get('id'), $resourceid, 'course');
		$trackingInformationDetailed = $mediaTrackingDetailed->loadByDetailId($detailedId);

		// Are we creating a new tracking record
		if (!is_object($trackingInformation))
		{
			$trackingInformation                              = new stdClass;
			$trackingInformation->user_id                     = User::get('id');
			$trackingInformation->session_id                  = $session->getId();
			$trackingInformation->ip_address                  = $ipAddress;
			$trackingInformation->object_id                   = $resourceid;
			$trackingInformation->object_type                 = 'course';
			$trackingInformation->object_duration             = $duration;
			$trackingInformation->current_position            = $time;
			$trackingInformation->farthest_position           = $time;
			$trackingInformation->current_position_timestamp  = Date::toSql();
			$trackingInformation->farthest_position_timestamp = Date::toSql();
			$trackingInformation->completed                   = 0;
			$trackingInformation->total_views                 = 1;
			$trackingInformation->total_viewing_time          = 0;
		}
		else
		{
			// Get the amount of video watched from last tracking event
			$time_viewed = (int)$time - (int)$trackingInformation->current_position;

			// If we have a positive value and its less then our ten second threshold,
			// add viewing time to total watched time
			if ($time_viewed < 10 && $time_viewed > 0)
			{
				$trackingInformation->total_viewing_time += $time_viewed;
			}

			// Set the new current position
			$trackingInformation->current_position           = $time;
			$trackingInformation->current_position_timestamp = Date::toSql();

			// Set the object duration
			if ($duration > 0)
			{
				$trackingInformation->object_duration = $duration;
			}

			// Check to see if we need to set a new farthest position
			if ($trackingInformation->current_position > $trackingInformation->farthest_position)
			{
				$trackingInformation->farthest_position           = $time;
				$trackingInformation->farthest_position_timestamp = Date::toSql();
			}

			// If event type is start, means we need to increment view count
			if ($event == 'start' || $event == 'replay')
			{
				$trackingInformation->total_views++;
			}

			// If event type is end, we need to increment completed count
			if ($event == 'ended')
			{
				$trackingInformation->completed++;
			}
		}

		// Save detailed tracking info
		if ($event == 'start' || !$trackingInformationDetailed)
		{
			$trackingInformationDetailed                              = new stdClass;
			$trackingInformationDetailed->user_id                     = User::get('id');
			$trackingInformationDetailed->session_id                  = $session->getId();
			$trackingInformationDetailed->ip_address                  = $ipAddress;
			$trackingInformationDetailed->object_id                   = $resourceid;
			$trackingInformationDetailed->object_type                 = 'course';
			$trackingInformationDetailed->object_duration             = $duration;
			$trackingInformationDetailed->current_position            = $time;
			$trackingInformationDetailed->farthest_position           = $time;
			$trackingInformationDetailed->current_position_timestamp  = Date::toSql();
			$trackingInformationDetailed->farthest_position_timestamp = Date::toSql();
			$trackingInformationDetailed->completed                   = 0;
		}
		else
		{
			// Set the new current position
			$trackingInformationDetailed->current_position           = $time;
			$trackingInformationDetailed->current_position_timestamp = Date::toSql();

			// Set the object duration
			if ($duration > 0)
			{
				$trackingInformationDetailed->object_duration = $duration;
			}

			// Check to see if we need to set a new farthest position
			if (isset($trackingInformationDetailed->farthest_position) && $trackingInformationDetailed->current_position > $trackingInformationDetailed->farthest_position)
			{
				$trackingInformationDetailed->farthest_position           = $time;
				$trackingInformationDetailed->farthest_position_timestamp = Date::toSql();
			}

			// If event type is end, we need to increment completed count
			if ($event == 'ended')
			{
				$trackingInformationDetailed->completed++;
			}
		}

		// Save detailed
		$mediaTrackingDetailed->save($trackingInformationDetailed);

		// Save tracking information
		if ($mediaTracking->save($trackingInformation))
		{
			if (!isset($trackingInformation->id))
			{
				$trackingInformation->id = $mediaTracking->id;
			}
			$trackingInformation->detailedId = $mediaTrackingDetailed->id;
			echo json_encode($trackingInformation);
		}
	}

	/**
	 * Upload a file
	 *
	 * @return     void
	 */
	public function uploadTask()
	{
		// Check if they're logged in
		if (User::isGuest())
		{
			$this->mediaTask();
			return;
		}

		// Incoming
		$listdir = Request::getInt('listdir', 0, 'post');

		// Ensure we have an ID to work with
		if (!$listdir)
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_ID'), 'error');
			$this->mediaTask();
			return;
		}

		// Incoming file
		$file = Request::getVar('upload', '', 'files', 'array');
		if (!$file['name'])
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_FILE'), 'error');
			$this->mediaTask();
			return;
		}

		// Build the upload path if it doesn't exist
		$path = PATH_APP . DS . trim($this->config->get('uploadpath', '/site/courses'), DS) . DS . trim($listdir, DS);

		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				$this->addComponentMessage(Lang::txt('UNABLE_TO_CREATE_UPLOAD_PATH'), 'error');
				$this->mediaTask();
				return;
			}
		}

		// Make the filename safe
		jimport('joomla.filesystem.file');
		$file['name'] = urldecode($file['name']);
		$file['name'] = \JFile::makeSafe($file['name']);
		$file['name'] = str_replace(' ', '_', $file['name']);

		// Perform the upload
		if (!\JFile::upload($file['tmp_name'], $path . DS . $file['name']))
		{
			$this->addComponentMessage(Lang::txt('ERROR_UPLOADING'), 'error');
		}

		//push a success message
		$this->addComponentMessage('You successfully uploaded the file.', 'passed');

		// Push through to the media view
		$this->mediaTask();
	}

	/**
	 * Streaking file upload
	 * This is used by AJAX
	 *
	 * @return     void
	 */
	private function ajaxuploadTask()
	{
		// get config
		$config = Component::params('com_media');

		// Incoming
		$listdir = Request::getInt('listdir', 0);

		// allowed extensions for uplaod
		$allowedExtensions = array_values(array_filter(explode(',', $config->get('upload_extensions'))));

		// max upload size
		$sizeLimit = $config->get('upload_maxsize');
		$sizeLimit = $sizeLimit * 1024 * 1024;

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
			return;
		}

		// Build the upload path if it doesn't exist
		$uploadDirectory = PATH_APP . DS . trim($this->config->get('uploadpath', '/site/courses'), DS) . DS . $listdir . DS;

		//make sure upload directory is writable
		if (!is_dir($uploadDirectory))
		{
			if (!\JFolder::create($uploadDirectory))
			{
				echo json_encode(array('error' => "Server error. Unable to create upload directory."));
				return;
			}
		}
		if (!is_writable($uploadDirectory))
		{
			echo json_encode(array('error' => "Server error. Upload directory isn't writable."));
			return;
		}

		//check to make sure we have a file and its not too big
		if ($size == 0)
		{
			echo json_encode(array('error' => 'File is empty'));
			return;
		}
		if ($size > $sizeLimit)
		{
			$max = preg_replace('/<abbr \w+=\\"\w+\\">(\w{1,3})<\\/abbr>/', '$1', \Hubzero\Utility\Number::formatBytes($sizeLimit));
			echo json_encode(array('error' => 'File is too large. Max file upload size is ' . $max));
			return;
		}

		//check to make sure we have an allowable extension
		$pathinfo = pathinfo($file);
		$filename = $pathinfo['filename'];
		$ext = $pathinfo['extension'];
		if ($allowedExtensions && !in_array(strtolower($ext), $allowedExtensions))
		{
			$these = implode(', ', $allowedExtensions);
			echo json_encode(array('error' => 'File has an invalid extension, it should be one of ' . $these . '.'));
			return;
		}

		//final file
		$file = $uploadDirectory . $filename . '.' . $ext;

		//save file
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

		//return success
		echo json_encode(array('success'=>true));
		return;
	}

	/**
	 * Delete a folder
	 *
	 * @return     void
	 */
	public function deletefolderTask()
	{
		// Check if they're logged in
		if (User::isGuest())
		{
			$this->mediaTask();
			return;
		}

		// Incoming course ID
		$listdir = Request::getInt('listdir', 0, 'get');
		if (!$listdir)
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_ID'), 'error');
			$this->mediaTask();
			return;
		}

		// Incoming file
		$folder = trim(Request::getVar('folder', '', 'get'));
		if (!$folder)
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_DIRECTORY'), 'error');
			$this->mediaTask();
			return;
		}

		$del_folder = DS . trim($this->config->get('uploadpath', '/site/courses'), DS) . DS . trim($listdir, DS) . DS . ltrim($folder, DS);

		// Delete the folder
		if (is_dir(PATH_APP . $del_folder))
		{
			// Attempt to delete the file
			jimport('joomla.filesystem.file');
			if (!\JFolder::delete(PATH_APP . $del_folder))
			{
				$this->addComponentMessage(Lang::txt('UNABLE_TO_DELETE_DIRECTORY'), 'error');
			}
			else
			{
				//push a success message
				$this->addComponentMessage('You successfully deleted the folder.', 'passed');
			}
		}

		// Push through to the media view
		$this->mediaTask();
	}

	/**
	 * Delete a file
	 *
	 * @return     void
	 */
	public function deletefileTask()
	{
		// Check if they're logged in
		if (User::isGuest())
		{
			$this->mediaTask();
			return;
		}

		// Incoming course ID
		$listdir = Request::getInt('listdir', 0, 'get');
		if (!$listdir)
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_ID'), 'error');
			$this->mediaTask();
			return;
		}

		// Incoming file
		$file = trim(Request::getVar('file', '', 'get'));
		if (!$file)
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_FILE'), 'error');
			$this->mediaTask();
			return;
		}

		// Build the file path
		$path = PATH_APP . DS . trim($this->config->get('uploadpath', '/site/courses'), DS) . DS . $listdir;

		if (!file_exists($path . DS . $file) or !$file)
		{
			$this->addComponentMessage(Lang::txt('FILE_NOT_FOUND'), 'error');
			$this->mediaTask();
			return;
		}
		else
		{
			// Attempt to delete the file
			jimport('joomla.filesystem.file');
			if (!\JFile::delete($path . DS . $file))
			{
				$this->addComponentMessage(Lang::txt('UNABLE_TO_DELETE_FILE'), 'error');
			}
		}

		//push a success message
		$this->addComponentMessage('The file was successfully deleted.', 'passed');

		// Push through to the media view
		$this->mediaTask();
	}

	/**
	 * Show a form for uploading and managing files
	 *
	 * @return     void
	 */
	public function mediaTask()
	{
		// Incoming
		$listdir = Request::getInt('listdir', 0);
		if (!$listdir)
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_ID'), 'error');
		}

		$course = \CoursesModelCourse::getInstance($listdir);

		// Output HTML
		$this->view->config = $this->config;
		if (is_object($course))
		{
			$this->view->course = $course;
		}
		$this->view->listdir = $listdir;
		$this->view->notifications = ($this->getComponentMessage()) ? $this->getComponentMessage() : array();
		$this->view->display();
	}

	/**
	 * List files for a course
	 *
	 * @return     void
	 */
	public function listfilesTask()
	{
		// Incoming
		$listdir = Request::getInt('listdir', 0, 'get');

		// Check if coming from another function
		if ($listdir == '')
		{
			$listdir = $this->listdir;
		}

		if (!$listdir)
		{
			$this->addComponentMessage(Lang::txt('COURSES_NO_ID'), 'error');
		}

		$path = PATH_APP . DS . trim($this->config->get('uploadpath', '/site/courses'), DS) . DS . $listdir;

		// Get the directory we'll be reading out of
		$d = @dir($path);

		$images  = array();
		$folders = array();
		$docs    = array();

		if ($d)
		{
			// Loop through all files and separate them into arrays of images, folders, and other
			while (false !== ($entry = $d->read()))
			{
				$img_file = $entry;
				if (is_file($path . DS . $img_file)
				 && substr($entry, 0, 1) != '.'
				 && strtolower($entry) !== 'index.html')
				{
					if (preg_match("#bmp|gif|jpg|jpeg|jpe|tif|tiff|png#i", $img_file))
					{
						$images[$entry] = $img_file;
					}
					else
					{
						$docs[$entry] = $img_file;
					}
				}
				else if (is_dir($path . DS . $img_file)
				 && substr($entry, 0, 1) != '.'
				 && strtolower($entry) !== 'cvs'
				 && strtolower($entry) !== 'template'
				 && strtolower($entry) !== 'blog')
				{
					$folders[$entry] = $img_file;
				}
			}

			$d->close();

			ksort($images);
			ksort($folders);
			ksort($docs);
		}

		$this->view->docs = $docs;
		$this->view->folders = $folders;
		$this->view->images = $images;
		$this->view->config = $this->config;
		$this->view->listdir = $listdir;
		$this->view->notifications = ($this->getNotifications()) ? $this->getNotifications() : array();
		$this->view->display();
	}

	/**
	 * Download a file
	 *
	 * @param      string $filename File name
	 * @return     void
	 */
	public function downloadTask($filename)
	{
		//get the course
		$course = \CoursesModelCourse::getInstance($this->gid);

		//authorize
		$authorized = $this->_authorize();

		//get the file name
		if (substr(strtolower($filename), 0, 5) == 'image')
		{
			$file = urldecode(substr($filename, 6));
		}
		elseif (substr(strtolower($filename), 0, 4) == 'file')
		{
			$file = urldecode(substr($filename, 5));
		}

		//if were on the wiki we need to output files a specific way
		if ($this->active == 'wiki')
		{
			//check to make sure user has access to wiki section
			if (!in_array(User::get('id'), $course->get('members')) || User::isGuest())
			{
				return App::abort(403, Lang::txt('COM_COURSES_NOT_AUTH') . ' ' . $file);
			}

			//load wiki page from db
			require_once(JPATH_ROOT . DS . 'components' . DS . 'com_wiki' . DS . 'tables' . DS . 'page.php');
			$page = new \Components\Wiki\Tables\Page($this->database);
			$page->load(Request::getVar('pagename'), $course->get('cn') . DS . 'wiki');

			//check specific wiki page access
			if ($page->get('access') == 1 && !in_array(User::get('id'), $course->get('members')) && $authorized != 'admin')
			{
				return App::abort(403, Lang::txt('COM_COURSES_NOT_AUTH') . ' ' . $file);
			}

			//get the config and build base path
			$wiki_config = Component::params('com_wiki');
			$base_path = $wiki_config->get('filepath') . DS . $page->get('id');
		}
		else
		{
			//check to make sure we can access it
			if (!in_array(User::get('id'), $course->get('members')) || User::isGuest())
			{
				return App::abort(403, Lang::txt('COM_COURSES_NOT_AUTH') . ' ' . $file);
			}

			// Build the path
			$base_path = $this->config->get('uploadpath');
			$base_path .= DS . $course->get('gidNumber');
		}

		// Final path of file
		$file_path = $base_path . DS . $file;

		// Ensure the file exist
		if (!file_exists(PATH_APP . DS . $file_path))
		{
			return App::abort(404, Lang::txt('COM_COURSES_FILE_NOT_FOUND') . ' ' . $file);
		}

		// Serve up the file
		$xserver = new \Hubzero\Content\Server();
		$xserver->filename(PATH_APP . DS . $file_path);
		$xserver->disposition('attachment');
		$xserver->acceptranges(false); // @TODO fix byte range support
		if (!$xserver->serve())
		{
			return App::abort(404, Lang::txt('COM_COURSES_SERVER_ERROR'));
		}
		else
		{
			exit;
		}
		return;
	}
}

