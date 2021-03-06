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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

class ToolsControllerApi extends \Hubzero\Component\ApiController
{
	public function execute()
	{
		//JLoader::import('joomla.environment.request');
		//JLoader::import('joomla.application.component.helper');

		//include tool utils
		include_once PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'helpers' . DS . 'utils.php';
		include_once PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'models' . DS . 'tool.php';

		// Get the format type for the request
		$this->format = Request::getVar('format', 'application/json');

		switch ($this->segments[0])
		{
			case 'index':		$this->index();				break;
			case 'info':		$this->info();				break;
			case 'screenshot':	$this->screenshot();		break;
			case 'screenshots':	$this->screenshots();		break;
			case 'invoke':		$this->invoke();			break;
			case 'view':		$this->view();				break;
			case 'stop':		$this->stop();				break;
			case 'unshare':		$this->unshare();			break;

			case 'run':         $this->run();               break;
			case 'status':      $this->status();            break;
			case 'output':      $this->output();            break;

			case 'storage':		$this->storage();			break;
			case 'purge':		$this->purge();				break;

			default:			$this->index();
		}
	}

	/**
	 * Short description for 'not_found'
	 *
	 * Long description (if any) ...
	 *
	 * @return     void
	 */
	private function not_found()
	{
		$response = $this->getResponse();
		$response->setErrorMessage(404,'Not Found');
	}

	/**
	 * Method to report errors. creates error node for response body as well
	 *
	 * @param	$code		Error Code
	 * @param	$message	Error Message
	 * @param	$format		Error Response Format
	 *
	 * @return     void
	 */
	private function errorMessage($code, $message, $format = 'json')
	{
		//build error code and message
		$object = new stdClass();
		$object->error->code = $code;
		$object->error->message = $message;

		//set http status code and reason
		$response = $this->getResponse();
		$response->setErrorMessage($object->error->code, $object->error->message);

		//add error to message body
		$this->setMessageType($format);
		$this->setMessage($object);
	}

	/**
	 * Method to get list of tools
	 *
	 * @return     void
	 */
	private function index()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		//if ($result === false) return $this->not_found();

		//instantiate database object
		$database = JFactory::getDBO();

		//get any request vars
		$format = Request::getVar('format', 'json');

		//get list of tools
		$tools = ToolsModelTool::getMyTools();

		//get the supported tag
		$rconfig = Component::params('com_resources');
		$supportedtag = $rconfig->get('supportedtag', '');

		//get supportedtag usage
		include_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'helpers' . DS . 'tags.php');
		$resource_tags = new \Components\Resources\Helpers\Tags(0);
		$supportedtagusage = $resource_tags->getTagUsage($supportedtag, 'alias');

		//create list of tools
		$t = array();
		foreach ($tools as $k => $tool)
		{
			if (isset($t[$tool->alias]))
			{
				$t[$tool->alias]['versions'][] = $tool->revision;
				continue;
			}

			$t[$tool->alias]['alias']       = $tool->alias;
			$t[$tool->alias]['title']       = $tool->title;
			$t[$tool->alias]['description'] = $tool->description;
			$t[$tool->alias]['versions']    = array($tool->revision);
			$t[$tool->alias]['supported']   = (in_array($tool->alias, $supportedtagusage)) ? 1 : 0;
		}

		//encode and return result
		$object = new stdClass();
		$object->tools = array_values($t);
		$this->setMessageType($format);
		$this->setMessage($object);
	}

	/**
	 * Method to get tool information
	 *
	 * @return     void
	 */
	private function info()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		//instantiate database object
		$database = JFactory::getDBO();

		//get request vars
		$tool    = Request::getVar('tool', '');
		$version = Request::getVar('version', 'current');
		$format  = Request::getVar('format', 'json');

		//we need a tool to continue
		if ($tool == '')
		{
			$this->errorMessage(400, 'Tool Alias Required.');
			return;
		}

		//poll database for tool matching alias
		$sql = "SELECT r.id, r.alias, tv.toolname, tv.title, tv.description, tv.toolaccess as access, tv.mw, tv.instance, tv.revision, r.fulltxt as abstract, r.created
				FROM #__resources as r, #__tool_version as tv
				WHERE r.published=1
				AND r.type=7
				AND r.standalone=1
				AND r.access!=4
				AND r.alias=tv.toolname
				AND tv.state=1
				AND r.alias='{$tool}'
				ORDER BY revision DESC";
		$database->setQuery($sql);
		$tool_info = $database->loadObject();

		//veryify we have result
		if ($tool_info == null)
		{
			$this->errorMessage(404, 'No Tool Found Matching the Alias: "' . $tool . '"');
			return;
		}

		//add tool alias to tool info from db
		$tool_info->alias = $tool;

		//remove tags and slashes from abastract
		$tool_info->abstract = stripslashes(strip_tags($tool_info->abstract));

		//get the supported tag
		$rconfig = Component::params('com_resources');
		$supportedtag = $rconfig->get('supportedtag', '');

		//get supportedtag usage
		include_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'helpers' . DS . 'tags.php');
		$this->rt = new \Components\Resources\Helpers\Tags(0);
		$supportedtagusage = $this->rt->getTagUsage($supportedtag, 'alias');
		$tool_info->supported = (in_array($tool_info->alias, $supportedtagusage)) ? 1 : 0;

		//get screenshots
		include_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'screenshot.php');
		include_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'version.php');
		$ts = new \Components\Resources\Tables\Screenshot($database);
		$tv = new ToolVersion($database);
		$vid = $tv->getVersionIdFromResource($tool_info->id, $version);
		$shots = $ts->getScreenshots($tool_info->id, $vid);

		//get base path
		$path = ToolsHelperUtils::getResourcePath($tool_info->created, $tool_info->id, $vid);

		//add full path to screenshot
		$s = array();
		foreach ($shots as $shot)
		{
			$s[] = $path . DS . $shot->filename;
		}
		$tool_info->screenshots = $s;

		//return result
		$object = new stdClass();
		$object->tool = $tool_info;
		$this->setMessageType($format);
		$this->setMessage($object);
	}

	/**
	 * Method to take session screenshots for user
	 *
	 * @return     void
	 */
	private function screenshots()
	{
		// get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		// make sure we have a user
		if ($result === false)	return $this->not_found();

		// request params
		$format = Request::getVar('format', 'json');

		// take new screenshots for user
		$cmd = "/bin/sh ". PATH_CORE . "/components/com_tools/scripts/mw screenshot " . $result->get('username') . " 2>&1 </dev/null";
		exec($cmd, $results, $status);

		// object to return
		$object = new stdClass();
		$object->screenshots_taken = true;

		// set format & return
		$this->setMessageType($format);
		$this->setMessage($object);
	}

	/**
	 * Method to return session screenshot
	 *
	 * @return     void
	 */
	private function screenshot()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		//mw session lib
		require_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.session.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.viewperm.php');

		//instantiate middleware database object
		$mwdb = ToolsHelperUtils::getMWDBO();

		//get any request vars
		$type      = Request::getVar('type', 'png');
		$sessionid = Request::getVar('sessionid', '');
		$notFound  = Request::getVar('notfound', 0);
		$format    = Request::getVar('format', 'json');

		$image_type = IMAGETYPE_PNG;
		if ($type == 'jpeg' || $type == 'jpg')
		{
			$image_type = IMAGETYPE_JPEG;
		}
		else if ($type == 'gif')
		{
			$image_type = IMAGETYPE_GIF;
		}

		//check to make sure we have a valid sessionid
		if ($sessionid == '' || !is_numeric($sessionid))
		{
			$this->errorMessage(401, 'No session ID Specified.');
			return;
		}

		//load session
		$ms = new MwSession($mwdb);
		$sess = $ms->loadSession($sessionid);

		//check to make sure we have a sessions dir
		$home_directory = DS .'webdav' . DS . 'home' . DS . strtolower($sess->username) . DS . 'data' . DS . 'sessions';
		if (!is_dir($home_directory))
		{
			clearstatcache();
			if (!is_dir($home_directory))
			{
				$this->errorMessage(404, 'Unable to find users sessions directory. - ' . $home_directory);
				return;
			}
		}

		//check to make sure we have an active session with the ID supplied
		$home_directory .= DS . $sessionid . '{,L,D}';
		$directories = glob($home_directory, GLOB_BRACE);
		if (empty($directories))
		{
			$this->errorMessage(404, "No Session directory with the ID: " . $sessionid);
			return;
		}
		else
		{
			$home_directory = $directories[0];
		}

		// check to make sure we have a screenshot
		$screenshot = $home_directory . DS . 'screenshot.png';

		if (!file_exists($screenshot))
		{
			if ($notFound)
			{
				$screenshot = PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'site' . DS . 'assets' . DS . 'img' . DS . 'screenshot-notfound.png';
			}
			else
			{
				$this->errorMessage(404,'No screenshot Found.');
				return;
			}
		}

		//load image and serve up
		$image = new \Hubzero\Image\Processor($screenshot);
		$this->setMessageType('image/' . $type);
		$image->setImageType($image_type);
		$image->display();
	}


	/**
	 * Method to invoke new tools session
	 *
	 * @return     void
	 */
	private function invoke()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		//get request vars
		$tool_name    = Request::getVar('app', '');
		$tool_version = Request::getVar('version', 'default');
		$format       = Request::getVar('format', 'json');

		//build application object
		$app = new stdClass;
		$app->name    = trim(str_replace(':', '-', $tool_name));
		$app->version = $tool_version;
		$app->ip      = $_SERVER["REMOTE_ADDR"];

		//check to make sure we have an app to invoke
		if (!$app->name)
		{
			$this->errorMessage(400, 'You Must Supply a Valid Tool Name to Invoke.');
			return;
		}

		//include needed tool libraries
		include_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'version.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.session.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.viewperm.php');

		//create database object
		JLoader::import("joomla.database.table");
		$database = JFactory::getDBO();

		//load the tool version
		$tv = new ToolVersion($database);
		switch ($app->version)
		{
			case 1:
			case 'default':
				$app->name = $tv->getCurrentVersionProperty($app->name, 'instance');
			break;
			case 'test':
			case 'dev':
				$app->name .= '_dev';
			break;
			default:
				$app->name .= '_r' . $app->version;
			break;
		}

		$app->toolname = $app->name;
		if ($parent = $tv->getToolname($app->name))
		{
			$app->toolname = $parent;
		}

		// Check of the toolname has a revision indicator
		$r = substr(strrchr($app->name, '_'), 1);
		if (substr($r, 0, 1) != 'r' && substr($r, 0, 3) != 'dev')
		{
			$r = '';
		}
		// No version passed and no revision
		if ((!$app->version || $app->version == 'default') && !$r)
		{
			// Get the latest version
			$app->version = $tv->getCurrentVersionProperty($app->toolname, 'revision');
			$app->name    = $app->toolname . '_r' . $app->version;
		}

		// Get the caption/session title
		$tv->loadFromInstance($app->name);
		$app->caption = stripslashes($tv->title);
		$app->title   = stripslashes($tv->title);

		//make sure we have a valid tool
		if ($app->title == '' || $app->toolname == '')
		{
			$this->errorMessage(400, 'The tool "' . $tool_name . '" does not exist on the HUB.');
			return;
		}

		//get tool access
		$toolAccess = ToolsHelperUtils::getToolAccess($app->name, $result->get('username'));

		//do we have access
		if ($toolAccess->valid != 1)
		{
			$this->errorMessage(400, $toolAccess->error->message);
			return;
		}

		// Log the launch attempt
		ToolsHelperUtils::recordToolUsage($app->toolname, $result->get('id'));

		// Get the middleware database
		$mwdb = ToolsHelperUtils::getMWDBO();

		// Find out how many sessions the user is running.
		$ms = new MwSession($mwdb);
		$jobs = $ms->getCount($result->get('username'));

		// Find out how many sessions the user is ALLOWED to run.
		include_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'preferences.php');

		$preferences = new ToolsTablePreferences($database);
		$preferences->loadByUser($result->get('uidNumber'));
		if (!$preferences || !$preferences->id)
		{
			include_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'sessionclass.php');
			$scls = new ToolsTableSessionClass($this->database);
			$default = $scls->find('one', array('alias' => 'default'));
			$preferences->user_id  = $result->get('uidNumber');
			$preferences->class_id = $default->id;
			$preferences->jobs     = ($default->jobs ? $default->jobs : 3);
			$preferences->store();
		}
		$remain = $preferences->jobs - $jobs;

		//can we open another session
		if ($remain <= 0)
		{
			$this->errorMessage(401, 'You are using all (' . $jobs . ') your available job slots.');
			return;
		}

		//import joomla plugin helpers
		jimport('joomla.plugin.helper');

		// Get plugins
		Plugin::import('mw', $app->name);

		// Trigger any events that need to be called before session invoke
		Event::trigger('mw.onBeforeSessionInvoke', array($app->toolname, $app->version));

		// We've passed all checks so let's actually start the session
		$status = ToolsHelperUtils::middleware("start user=" . $result->get('username') . " ip=" . $app->ip . " app=" . $app->name . " version=" . $app->version, $output);

		//make sure we got a valid session back from the middleware
		if (!isset($output->session))
		{
			$this->errorMessage(500, 'There was a issue while trying to start the tool session. Please try again later.');
			return;
		}

		//set session output
		$app->sess = $output->session;

		// Trigger any events that need to be called after session invoke
		Event::trigger('mw.onAfterSessionInvoke', array($app->toolname, $app->version));

		// Get a count of the number of sessions of this specific tool
		$appcount = $ms->getCount($result->get('username'), $app->name);

		// Do we have more than one session of this tool?
		if ($appcount > 1)
		{
			// We do, so let's append a timestamp
			$app->caption .= ' (' . Date::format("g:i a") . ')';
		}

		// Save the changed caption
		$ms->load($app->sess);
		$ms->sessname = $app->caption;
		if (!$ms->store())
		{
			$this->errorMessage(500, 'There was a issue while trying to start the tool session. Please try again later.');
			return;
		}

		//add tool title to output
		//add session title to ouput
		$output->tool = $app->title;
		$output->session_title = $app->caption;
		$output->owner = 1;
		$output->readonly = 0;

		//return result
		if ($status)
		{
			$this->setMessageType($format);
			$this->setMessage($output);
		}
	}

	/**
	 * Runs a rappture job.
	 *
	 * This is more than just invoking a tool. We're expecting a driver file to pass to the
	 * tool to be picked up and automatically run by rappture.
	 *
	 * @return void
	 */
	private function run()
	{
		// Set message format
		$this->setMessageType($this->format);

		// Get the user_id and attempt to load user profile
		$userid  = JFactory::getApplication()->getAuthn('user_id');
		$profile = \Hubzero\User\Profile::getInstance($userid);

		// Make sure we have a user
		if ($profile === false) return $this->not_found();

		// Grab tool name and version
		$tool_name    = Request::getVar('app', '');
		$tool_version = Request::getVar('version', 'default');

		// Build application object
		$app          = new stdClass;
		$app->name    = trim(str_replace(':', '-', $tool_name));
		$app->version = $tool_version;
		$app->ip      = $_SERVER["REMOTE_ADDR"];

		// Check to make sure we have an app to invoke
		if (!$app->name)
		{
			$this->setMessage(array('success' => false, 'message' => 'A valid app name must be provided'), 404, 'Not Found');
			return;
		}

		// Include needed tool libraries
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'version.php';
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.session.php';
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.viewperm.php';

		// Create database object
		JLoader::import("joomla.database.table");
		$database = JFactory::getDBO();

		// Load the tool version
		$tv = new ToolVersion($database);
		switch ($app->version)
		{
			case 1:
			case 'default':
				$app->name = $tv->getCurrentVersionProperty($app->name, 'instance');
			break;
			case 'test':
			case 'dev':
				$app->name .= '_dev';
			break;
			default:
				$app->name .= '_r' . $app->version;
			break;
		}

		$app->toolname = $app->name;
		if ($parent = $tv->getToolname($app->name))
		{
			$app->toolname = $parent;
		}

		// Check of the toolname has a revision indicator
		$r = substr(strrchr($app->name, '_'), 1);
		if (substr($r, 0, 1) != 'r' && substr($r, 0, 3) != 'dev')
		{
			$r = '';
		}
		// No version passed and no revision
		if ((!$app->version || $app->version == 'default') && !$r)
		{
			// Get the latest version
			$app->version = $tv->getCurrentVersionProperty($app->toolname, 'revision');
			$app->name    = $app->toolname . '_r' . $app->version;
		}

		// Get the caption/session title
		$tv->loadFromInstance($app->name);
		$app->caption = stripslashes($tv->title);
		$app->title   = stripslashes($tv->title);

		// Make sure we have a valid tool
		if ($app->title == '' || $app->toolname == '')
		{
			$this->errorMessage(404, 'The tool "' . $tool_name . '" does not exist on the HUB.');
			return;
		}

		// Get tool access
		$toolAccess = ToolsHelperUtils::getToolAccess($app->name, $profile->get('username'));

		// Do we have access
		if ($toolAccess->valid != 1)
		{
			$this->errorMessage(404, $toolAccess->error->message);
			return;
		}

		// Log the launch attempt
		ToolsHelperUtils::recordToolUsage($app->toolname, $profile->get('id'));

		// Get the middleware database
		$mwdb = ToolsHelperUtils::getMWDBO();

		// Find out how many sessions the user is running
		$ms = new MwSession($mwdb);
		$jobs = $ms->getCount($profile->get('username'));

		// Find out how many sessions the user is ALLOWED to run.
		include_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'preferences.php');

		$preferences = new ToolsTablePreferences($database);
		$preferences->loadByUser($profile->get('uidNumber'));
		if (!$preferences || !$preferences->id)
		{
			$default = $preferences->find('one', array('alias' => 'default'));
			$preferences->user_id  = $profile->get('uidNumber');
			$preferences->class_id = $default->id;
			$preferences->jobs     = $default->jobs;
			$preferences->store();
		}
		$remain = $preferences->jobs - $jobs;

		//can we open another session
		if ($remain <= 0)
		{
			$this->errorMessage(401, 'You are using all (' . $jobs . ') your available job slots.');
			return;
		}

		// Check for an incoming driver file
		if ($driver = Request::getVar('xml', false, 'post', 'none', JREQUEST_ALLOWRAW))
		{
			// Build a path to where the driver file will go through webdav
			$base = DS . 'webdav' . DS . 'home';
			$user = DS . $profile->get('username');
			$data = DS . 'data';
			$drvr = DS . '.queued_drivers';
			$inst = DS . md5(time()) . '.xml';

			// Real home directory
			$homeDir = $profile->get('homeDirectory');

			// First, make sure webdav is there and that the necessary folders are there
			if (!\JFolder::exists($base))
			{
				$this->setMessage(array('success' => false, 'message' => 'Home directories are unavailable'), 500, 'Server Error');
				return;
			}

			// Now see if the user has a home directory yet
			if (!\JFolder::exists($homeDir))
			{
				// Try to create their home directory
				require_once(JPATH_ROOT . DS .'components' . DS . 'com_tools' . DS . 'helpers' . DS . 'utils.php');

				if (!ToolsHelperUtils::createHomeDirectory($profile->get('username')))
				{
					$this->setMessage(array('success' => false, 'message' => 'Failed to create user home directory'), 500, 'Server Error');
					return;
				}
			}

			// Check for, and create if needed a session data directory
			if (!\JFolder::exists($base . $user . $data) && !\JFolder::create($base . $user . $data, 0700))
			{
				$this->setMessage(array('success' => false, 'message' => 'Failed to create data directory'), 500, 'Server Error');
				return;
			}

			// Check for, and create if needed a queued drivers directory
			if (!\JFolder::exists($base . $user . $data . $drvr) && !\JFolder::create($base . $user . $data . $drvr, 0700))
			{
				$this->setMessage(array('success' => false, 'message' => 'Failed to create drivers directory'), 500, 'Server Error');
				return;
			}

			// Write the driver file out
			if (!\JFile::write($base . $user . $data . $drvr . $inst, $driver))
			{
				$this->setMessage(array('success' => false, 'message' => 'Failed to create driver file'), 500, 'Server Error');
				return;
			}
		}
		else
		{
			$this->setMessage(array('success' => false, 'message' => 'No driver file provided'), 404, 'Not Found');
			return;
		}

		// Now build params path that will be included with tool execution
		// We know from the checks above that this directory already exists
		$params  = 'file(execute):' . $homeDir . DS . 'data' . DS . '.queued_drivers' . $inst;
		$encoded = ' params=' . rawurlencode($params) . ' ';
		$command = 'start user=' . $profile->get('username') . " ip={$app->ip} app={$app->name} version={$app->version}" . $encoded;
		$status  = ToolsHelperUtils::middleware($command, $output);

		$this->setMessage(array('success' => true, 'session' => $output->session), 200, 'OK');
	}

	/**
	 * Gets the status of the session identified
	 *
	 * @return void
	 **/
	private function status()
	{
		// Set message format
		$this->setMessageType($this->format);

		// Get profile instance and session number
		$profile = \Hubzero\User\Profile::getInstance(JFactory::getApplication()->getAuthn('user_id'));
		$session = Request::getInt('session_num', 0);

		// Require authorization
		if ($profile === false) return $this->not_found();

		// Get the middleware database
		$mwdb = ToolsHelperUtils::getMWDBO();

		// Make sure it's a valid sesssion number and the user is/was the owner of it
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.session.php';
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.viewperm.php';
		$ms = new MwSession($mwdb);
		if (!$ms->checkSession($session))
		{
			$this->setMessage(array('success' => false, 'message' => 'You can only check the status of your sessions.'), 401, 'Not authorized');
			return;
		}

		// Check for specific sesssion entry, either sesssion# or session#-expired
		$dir = DS . 'webdav' . DS . 'home' . DS . $profile->get('username') . DS . 'data' . DS .'sessions' . DS . $session;

		// If the active session dir doesn't exist, look for an expired one
		if (!is_dir($dir))
		{
			$dir .= '-expired';

			if (!is_dir($dir))
			{
				$this->setMessage(array('success' => false, 'message' => 'No session directory found.'), 404, 'Not found');
				return;
			}
		}

		// Look for a rappture.status file in that dir
		$statusFile = $dir . DS . 'rappture.status';
		if (!is_file($statusFile))
		{
			$this->setMessage(array('success' => false, 'message' => 'No status file found.'), 404, 'Not found');
			return;
		}

		// Read the file
		$status   = file_get_contents($statusFile);
		$parsed   = explode("\n", trim($status));
		$finished = (strpos(end($parsed), '[status] exit') !== false) ? true : false;
		$runFile  = '';

		if ($finished)
		{
			$count = count($parsed);
			preg_match('/\[status\] output saved in [a-zA-Z0-9\/]*\/(run[0-9]*\.xml)/', $parsed[($count-2)], $matches);
			$runFile = (isset($matches[1])) ? $matches[1] : '';
		}

		$this->setMessage(array('success' => true, 'status' => $parsed, 'finished' => $finished, 'run_file' => $runFile), 200, 'OK');
	}

	/**
	 * Grabs the output from a tool session
	 *
	 * @return void
	 * @author 
	 **/
	private function output()
	{
		// Set message format
		$this->setMessageType($this->format);

		// Get profile instance and session number
		$profile = \Hubzero\User\Profile::getInstance(JFactory::getApplication()->getAuthn('user_id'));
		$session = Request::getInt('session_num', 0);
		$runFile = Request::getVar('run_file', false);

		// Require authorization
		if ($profile === false) return $this->not_found();

		// Get the middleware database
		$mwdb = ToolsHelperUtils::getMWDBO();

		// Make sure it's a valid sesssion number and the user is/was the owner of it
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.session.php';
		require_once JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.viewperm.php';
		$ms = new MwSession($mwdb);
		if (!$ms->checkSession($session))
		{
			$this->setMessage(array('success' => false, 'message' => 'You can only check the status of your sessions.'), 401, 'Not authorized');
			return;
		}

		// Check for specific sesssion entry
		$dir = DS . 'webdav' . DS . 'home' . DS . $profile->get('username') . DS . 'data' . DS .'results' . DS . $session;

		if (!is_dir($dir))
		{
			$this->setMessage(array('success' => false, 'message' => 'No results directory found.'), 404, 'Not found');
			return;
		}

		$outputFile = $dir . DS . $runFile;

		if (!is_file($outputFile))
		{
			$this->setMessage(array('success' => false, 'message' => 'No run file found.'), 404, 'Not found');
			return;
		}

		$output = file_get_contents($outputFile);

		$this->setMessage(array('success' => true, 'output' => $output), 200, 'OK');
	}

	/**
	 * Method to view tool session
	 *
	 * @return     void
	 */
	private function view()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		//include needed tool libs
		include_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'version.php');
		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.session.php');
		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.viewperm.php');

		//instantiate db objects
		$database = JFactory::getDBO();
		$mwdb = ToolsHelperUtils::getMWDBO();

		//get request vars
		$sessionid = Request::getVar('sessionid', '');
		$format    = Request::getVar('format', 'json');
		$ip        = Request::ip();

		//make sure we have the session
		if (!$sessionid)
		{
			$this->errorMessage(400, 'Session ID Needed');
			return;
		}

		//create app object
		$app = new stdClass;
		$app->sess = $sessionid;
		$app->ip   = $ip;

		//load the session
		$ms = new MwSession($mwdb);
		$row = $ms->loadSession($app->sess);

		//if we didnt find a session
		if (!is_object($row) || !$row->appname)
		{
			$this->errorMessage(400, 'Session Doesn\'t Exist.');
			return;
		}

		//get the version
		if (strstr($row->appname, '_'))
		{
			$v = substr(strrchr($row->appname, '_'), 1);
			$v = str_replace('r', '', $v);
			Request::setVar('version', $v);
		}

		//load tool version
		$tv = new ToolVersion($database);
		$parent_toolname = $tv->getToolname($row->appname);
		$toolname = ($parent_toolname) ? $parent_toolname : $row->appname;
		$tv->loadFromInstance($row->appname);

		//command to run on middleware
		$command = "view user=" . $result->get('username') . " ip=" . $app->ip . " sess=" . $app->sess;

		//app vars
		$app->caption  = $row->sessname;
		$app->name     = $row->appname;
		$app->username = $row->username;

		//import joomla plugin helpers
		jimport('joomla.plugin.helper');

		// Get plugins
		Plugin::import('mw', $app->name);

		// Trigger any events that need to be called before session start
		Event::trigger('mw.onBeforeSessionStart', array($toolname, $tv->revision));

		// Call the view command
		$status = ToolsHelperUtils::middleware($command, $output);

		// Trigger any events that need to be called after session start
		Event::trigger('mw.onAfterSessionStart', array($toolname, $tv->revision));

		//add the session id to the result
		$output->session = $sessionid;

		//add tool title to result
		$output->tool = $tv->title;
		$output->session_title = $app->caption;
		$output->owner = ($row->viewuser == $row->username) ? 1 : 0;
		$output->readonly = ($row->readonly == 'Yes') ? 1 : 0;

		//return result
		if ($status)
		{
			$this->setMessageType($format);
			$this->setMessage($output);
		}
	}


	/**
	 * Method to stop tool session
	 *
	 * @return     void
	 */
	private function stop()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		//include needed libraries
		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.session.php');

		//instantiate middleware database object
		$mwdb = ToolsHelperUtils::getMWDBO();

		//get request vars
		$sessionid = Request::getVar('sessionid', '');
		$format    = Request::getVar('format', 'json');

		//make sure we have the session
		if (!$sessionid)
		{
			$this->errorMessage(400, 'Missing session ID.');
			return;
		}

		//load the session we are trying to stop
		$ms = new MwSession($mwdb);
		$ms->load($sessionid, $result->get("username"));

		//check to make sure session exists and it belongs to the user
		if (!$ms->username || $ms->username != $result->get("username"))
		{
			$this->errorMessage(400, 'Session Doesn\'t Exist or Does Not Belong to User');
			return;
		}

		//import joomla plugin helpers
		jimport('joomla.plugin.helper');

		//get middleware plugins
		Plugin::import('mw', $ms->appname);

		// Trigger any events that need to be called before session stop
		Event::trigger('mw.onBeforeSessionStop', array($ms->appname));

		//run command to stop session
		$status = ToolsHelperUtils::middleware("stop $sessionid", $out);

		// Trigger any events that need to be called after session stop
		Event::trigger('mw.onAfterSessionStop', array($ms->appname));

		// was the session stopped successfully
		if ($status == 1)
		{
			$object = new stdClass();
			$object->session = array("session" => $sessionid, "status" => "stopped", "stopped" => Date::toSql());
			$this->setMessageType($format);
			$this->setMessage($object);
		}
	}


	/**
	 * Method to disconnect from shared tool session
	 *
	 * @return     void
	 */
	private function unshare()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		//include needed libraries
		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'mw.viewperm.php');

		//instantiate middleware database object
		$mwdb = ToolsHelperUtils::getMWDBO();

		//get request vars
		$sessionid = Request::getVar('sessionid', '');
		$format    = Request::getVar('format', 'json');

		//check to make sure we have session id
		if (!$sessionid)
		{
			$this->errorMessage(400, 'Missing session ID.');
			return;
		}

		// Delete the viewperm
		$mv = new MwViewperm($mwdb);
		$mv->deleteViewperm($sessionid, $result->get("username"));

		//make sure we didnt have error disconnecting
		if (!$mv->getError())
		{
			$object = new stdClass();
			$object->session = array("session" => $sessionid, "status" => "disconnected", "disconnected" => Date::toSql());
			$this->setMessageType($format);
			$this->setMessage($object);
		}
	}


	/**
	 * Method to return users storage results
	 *
	 * @return     void
	 */
	private function storage()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false) return $this->not_found();

		//get request vars
		$type   = Request::getVar('type', 'soft');
		$format = Request::getVar('format', 'json');

		//get storage quota
		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'helpers' . DS . 'utils.php');
		$disk_usage = ToolsHelperUtils::getDiskUsage($result->get('username'));

		//get the tools storage path
		$com_tools_params = Component::params('com_tools');
		$path = DS . $com_tools_params->get('storagepath', 'webdav' . DS . 'home') . DS . $result->get('username');

		//get a list of files
		jimport('joomla.filesystem.folder');
		$files = array();
		//$files = \JFolder::files($path, '.', true, true, array('.svn', 'CVS'));

		//return result
		$object = new stdClass();
		$object->storage = array('quota' => $disk_usage, 'files' => $files);
		$this->setMessageType($format);
		$this->setMessage($object);
	}


	/**
	 * Method to purge users storage
	 *
	 * @return     void
	 */
	private function purge()
	{
		//get the userid and attempt to load user profile
		$userid = JFactory::getApplication()->getAuthn('user_id');
		$result = \Hubzero\User\Profile::getInstance($userid);

		//make sure we have a user
		if ($result === false)	return $this->not_found();

		//get request vars
		$degree = Request::getVar('degree', '');
		$format = Request::getVar('format', 'json');

		//get the hubs storage host
		$tool_params = Component::params('com_tools');
		$storage_host = $tool_params->get('storagehost', '');

		//check to make sure we have a storage host
		if ($storage_host == '')
		{
			$this->errorMessage(500, 'Unable to find storage host.');
			return;
		}

		//list of acceptable purge degrees
		$accepted_degrees = array(
			'default' => 'Minimally',
			'olderthan1' => 'Older than 1 Day',
			'olderthan7' => 'Older than 7 Days',
			'olderthan30' => 'Older than 30 Days',
			'all' => 'All'
		);

		//check to make sure we have a degree
		if ($degree == '' || !in_array($degree, array_keys($accepted_degrees)))
		{
			$this->errorMessage(401, 'No purge level supplied.');
			return;
		}

		//var to hold purge info
		$purge_info = array();

		//open stream to purge files
		if (!$fp = stream_socket_client($storage_host, $error_num, $error_str, 30))
		{
			die("$error_str ($error_num)");
		}
		else
		{
			fwrite($fp, 'purge user=' . $result->get('username') . ",degree=$degree \n");
			while (!feof($fp))
			{
				$purge_info[] = fgets($fp, 1024) . "\n";
			}
			fclose($fp);
		}

		//trim array values
		$purge_info = array_map("trim", $purge_info);

		//check to make sure the purge was successful
		if (in_array('Success.', $purge_info))
		{
			//return result
			$object = new stdClass();
			$object->purge = array('degree' => $accepted_degrees[$degree], 'success' => 1);
			$this->setMessageType($format);
			$this->setMessage($object);
		}
	}
}
