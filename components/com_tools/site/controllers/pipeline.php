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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

include_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'tool.php');
include_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'version.php');
include_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'group.php');
include_once(dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'author.php');
include_once(dirname(dirname(__DIR__)) . DS . 'helpers' . DS . 'helper.php');
include_once(dirname(dirname(__DIR__)) . DS . 'helpers' . DS . 'html.php');

include_once(PATH_CORE . DS . 'components' . DS . 'com_support' . DS . 'helpers' . DS . 'utilities.php');
include_once(PATH_CORE . DS . 'components' . DS . 'com_support' . DS . 'models' . DS . 'ticket.php');

require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'resource.php');
include_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'doi.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'type.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'assoc.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'contributor.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'helpers' . DS . 'helper.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'helpers' . DS . 'tags.php');

include_once(dirname(dirname(__DIR__)) . DS . 'helpers' . DS . 'html.php');

/**
 * Controller class for contributing a tool
 */
class ToolsControllerPipeline extends \Hubzero\Component\SiteController
{
	/**
	 * Determines task being called and attempts to execute it
	 *
	 * @return     void
	 */
	public function execute()
	{
		// Check logged in status
		if (User::isGuest())
		{
			$return = base64_encode(Request::getVar('REQUEST_URI', Route::url('index.php?option='
				. $this->_option . '&controller=' . $this->_controller . '&task='
				. Request::getWord('task', ''), false, true), 'server'));

			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . $return)
			);
			return;
		}

		$this->_authorize();

		// Load the com_resources component config
		$rconfig = Component::params('com_resources');
		$this->rconfig = $rconfig;

		// Set the default task
		$this->registerTask('__default', 'pipeline');
		$this->registerTask('register', 'save');

		parent::execute();
	}

	/**
	 * Tool Development Pipeline
	 *
	 * @return     void
	 */
	public function pipelineTask()
	{
		// Set the page title
		$this->view->title = Lang::txt(strtoupper($this->_option)) . ': ' . Lang::txt(strtoupper($this->_option . '_' . $this->_task));
		$document = JFactory::getDocument();
		$document->setTitle($this->view->title);

		// Filters
		$this->view->filters = array();
		$this->view->filters['filterby'] = trim(Request::getVar('filterby', 'all'));
		$this->view->filters['search'] = trim(urldecode(Request::getVar('search', '')));
		if (!$this->config->get('access-admin-component'))
		{
			$this->view->filters['sortby'] = trim(trim(Request::getVar('sortby', 'f.state, f.registered')));
		}
		else
		{
			$this->view->filters['sortby'] = trim(trim(Request::getVar('sortby', 'f.state_changed DESC')));
		}

		// Paging vars
		$this->view->filters['limit'] = Request::getInt('limit', Config::get('list_limit'));
		$this->view->filters['start'] = Request::getInt('limitstart', 0);

		// Create a Tool object
		$obj = new Tool($this->database);

		// Record count
		$this->view->total = $obj->getToolCount($this->view->filters, $this->config->get('access-admin-component'));

		// Fetch results
		$this->view->rows = $obj->getTools($this->view->filters, $this->config->get('access-admin-component'));

		// Initiate paging class
		jimport('joomla.html.pagination');
		$this->view->pageNav = new JPagination(
			$this->view->total,
			$this->view->filters['start'],
			$this->view->filters['limit']
		);

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		Pathway::append(
			Lang::txt('COM_TOOLS_PIPELINE'),
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller .  '&task=pipeline'
		);

		$this->view->config = $this->config;
		$this->view->admin = $this->config->get('access-admin-component');

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Display the status of the current app
	 *
	 * @return     void
	 */
	public function statusTask()
	{
		$this->view->setLayout('status');

		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// Couldn't get ID, exit
		if (!$this->_toolid)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller)
			);
			return;
		}

		if (!$this->_error)
		{
			$this->_error = '';
		}
		if (!$this->_msg)
		{
			$this->_msg = Request::getVar('msg', '', 'post');
		}

		// check access rights
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		// get tool status
		$obj->getToolStatus($this->_toolid, $this->_option, $status, 'dev');

		if (!$status)
		{
			App::abort(404, Lang::txt('COM_TOOLS_ERR_STATUS_CANNOT_FIND'));
			return;
		}

		// get tickets/wishes/questions
		if ($status['published'])
		{
			$status['questions'] = 'N/A';
			if (Component::isEnabled('com_answers'))
			{
				require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'tables' . DS . 'question.php');
				require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'tables' . DS . 'response.php');
				require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'tables' . DS . 'log.php');
				require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'tables' . DS . 'questionslog.php');

				$aq = new \Components\Answers\Tables\Question($this->database);
				$status['questions'] = $aq->getCount(array(
					'filterby' => 'all',
					'sortby'   => 'date',
					'tag'      => 'tool' . $status['toolname']
				));
			}

			$status['wishes'] = 'N/A';
			if (Component::isEnabled('com_wishlist'))
			{
				require_once(PATH_CORE . DS . 'components' . DS . 'com_wishlist' . DS . 'models' . DS . 'wishlist.php');
				require_once(PATH_CORE . DS . 'components' . DS . 'com_wishlist' . DS . 'controllers' . DS . 'wishlist.php');

				$objWishlist = new \Components\Wishlist\Tables\Wishlist($this->database);
				$objWish = new \Components\Wishlist\Tables\Wish($this->database);
				$listid = $objWishlist->get_wishlistID($status['resourceid'], 'resource');
				if ($listid)
				{
					$controller = new \Components\Wishlist\Controllers\Wishlist();
					$filters = $controller->getFilters(1);
					$wishes = $objWish->get_wishes($listid, $filters, 1, User::getRoot());
					$status['wishes'] = count($wishes);
				}
				else
				{
					$status['wishes']= 0;
				}
			}
		}

		$this->view->status = $status;
		$this->view->msg    = (isset($this->_msg)) ? $this->_msg : '';
		$this->view->config = $this->config;
		$this->view->admin  = $this->config->get('access-admin-component');

		// Set the page title
		$this->view->title  = Lang::txt(strtoupper($this->_option)) . ': ' . Lang::txt(strtoupper($this->_option . '_' . $this->_task));
		$this->view->title .= $status['toolname'] ? ' ' . Lang::txt('COM_TOOLS_FOR') . ' ' . $status['toolname'] : '';
		$document = JFactory::getDocument();
		$document->setTitle($this->view->title);

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		Pathway::append(
			Lang::txt('COM_TOOLS_PIPELINE'),
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller .  '&task=pipeline'
		);
		Pathway::append(
			Lang::txt('COM_TOOLS_STATUS_FOR', $status['toolname']),
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller .  '&task=status&app=' . $status['toolname']
		);

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Display a list of versions for a tool
	 *
	 * @return     void
	 */
	public function versionsTask()
	{
		$this->view->setLayout('versions');

		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// Couldn't get ID, exit
		if (!$this->_toolid)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller)
			);
			return;
		}

		// get vars
		if (!$this->_action)
		{
			$this->_action = Request::getVar('action', 'dev');
		}
		if (!$this->_error)
		{
			$this->_error = Request::getVar('error', '');
		}

		// check access rights
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		// Create a Tool Version object
		$objV = new ToolVersion($this->database);
		$objV->getToolVersions($this->_toolid, $versions, '');

		// Set the page title
		$this->view->title  = Lang::txt(strtoupper($this->_option)) . ': ';
		$this->view->title .= ($this->_action=='confirm') ? Lang::txt('COM_TOOLS_CONTRIBTOOL_APPROVE_TOOL') : Lang::txt('COM_TOOLS_TASK_VERSIONS');

		$document = JFactory::getDocument();
		$document->setTitle($this->view->title);

		$hzt = ToolsModelTool::getInstance($this->_toolid);
		$hztv_dev = $hzt->getRevision('development');
		$hztv_current = $hzt->getRevision('current');

		$this->view->status = array(
			'toolid'          => $hzt->id,
			'published'       => $hzt->published,
			'version'         => $hztv_dev->version,
			'state'           => $hzt->state,
			'toolname'        => $hzt->toolname,
			'membergroups'    => ToolsModelTool::getToolGroups($this->_toolid),
			'resourceid'      => ToolsModelTool::getResourceId($this->_toolid),
			'currentrevision' => (is_object($hztv_current) ? $hztv_current->revision : ''),
			'currentversion'  => (is_object($hztv_current) ? $hztv_current->version : '')
		);

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		Pathway::append(
			Lang::txt('COM_TOOLS_STATUS') . ' ' . Lang::txt('COM_TOOLS_FOR') . ' ' . $this->view->status['toolname'],
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller . 'task=status&app=' . $this->view->status['toolname']
		);
		if ($this->_action != 'confirm')
		{
			Pathway::append(
				Lang::txt('COM_TOOLS_TASK_VERSIONS'),
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=versions&app=' . $this->view->status['toolname']
			);
		}

		$this->view->admin = $this->config->get('access-admin-component');
		$this->view->error = $this->_error;
		$this->view->action = $this->_action;
		$this->view->versions = $versions;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Finalize the version
	 *
	 * @return     void
	 */
	public function finalizeTask()
	{
		$this->view->setLayout('finalize');

		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// Couldn't get ID, exit
		if (!$this->_toolid)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller)
			);
			return;
		}

		if (!$this->_error)
		{
			$this->_error = Request::getVar('error', '');
		}

		// check access rights
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		// get tool status
		$obj->getToolStatus($this->_toolid, $this->_option, $status, 'dev');

		if (!$status)
		{
			App::abort(404, Lang::txt('COM_TOOLS_ERR_STATUS_CANNOT_FIND'));
			return;
		}

		// Set the page title
		$this->view->title = Lang::txt(strtoupper($this->_option)) . ': ' . Lang::txt('COM_TOOLS_CONTRIBTOOL_APPROVE_TOOL');
		$document = JFactory::getDocument();
		$document->setTitle($this->view->title);

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		Pathway::append(
			Lang::txt('COM_TOOLS_STATUS_FOR', $status['toolname']),
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=status&app=' . $this->_toolid
		);

		$this->view->config = $this->config;
		$this->view->status = $status;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Show a form to apply a license
	 *
	 * @return     void
	 */
	public function licenseTask()
	{
		$this->view->setLayout('license');

		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		if (!$this->_action)
		{
			$this->_action = Request::getVar('action', 'dev');
		}
		if (!$this->_error)
		{
			$this->_error = Request::getVar('error', '');
		}

		// check access rights
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		// Create a Tool object
		$obj->getToolStatus($this->_toolid, $this->_option, $status, 'dev');

		if (!$status)
		{
			App::abort(404, Lang::txt('COM_TOOLS_ERR_STATUS_CANNOT_FIND'));
			return;
		}

		// get license
		if (!isset($this->view->license_choice))
		{
			$this->view->license_choice = array(
				'text'     => $status['license'],
				'template' => 'c1'
			);
		}

		if (!isset($this->view->code))
		{
			$this->view->code = $status['code'];
		}

		// get default license text
		$toolhelper = new ContribtoolHelper();
		$this->view->licenses = $toolhelper->getLicenses($this->database);

		// Set the page title
		$this->view->title  = Lang::txt(strtoupper($this->_option)) . ': ';
		$this->view->title .= ($this->_action == 'confirm') ? Lang::txt('COM_TOOLS_CONTRIBTOOL_APPROVE_TOOL') : Lang::txt('COM_TOOLS_TASK_LICENSE');

		$document = JFactory::getDocument();
		$document->setTitle($this->view->title);

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		if (Pathway::count() <= 1)
		{
			Pathway::append(
				Lang::txt('COM_TOOLS_STATUS_FOR', $status['toolname']),
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=status&app=' . $this->_toolid
			);
			if ($this->_action != 'confirm')
			{
				Pathway::append(
					Lang::txt('COM_TOOLS_TASK_LICENSE'),
					'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=license&app=' . $this->_toolid
				);
			}
		}

		$this->view->config = $this->config;
		$this->view->error  = $this->_error;
		$this->view->action = $this->_action;
		$this->view->status = $status;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Apply a license
	 *
	 * @return     void
	 */
	public function savelicenseTask()
	{
		$id    = Request::getInt('toolid', 0, 'post');
		$error = '';

		if (!$id)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller)
			);
		}

		$hztv = ToolsHelperVersion::getDevelopmentToolVersion($id);

		$this->license_choice = array(
			'text'      => Request::getVar('license', ''),
			'template'  => Request::getVar('templates', 'c1'),
			'authorize' => Request::getInt('authorize', 0)
		);

		$hztv->codeaccess = Request::getVar('t_code', '@OPEN');
		$action = Request::getWord('action', 'dev');

		// Closed source
		if ($hztv->codeaccess == '@DEV')
		{
			$reason = Request::getVar('reason', '');

			if (!$reason)
			{
				$this->view->license_choice = $this->license_choice;
				$this->view->code			= $hztv->codeaccess;

				// display license page with error
				$this->setError(Lang::txt('COM_TOOLS_LICENSE_CLOSED_SOURCE_NEED_REASON'));
				$this->licenseTask();
				return;
			}
			else
			{
				jimport('joomla.filesystem.folder');
				$path = DS . 'site/protected';
				if (is_dir(PATH_APP . $path))
				{
					$log  = 'By: ' . User::get('name') . ' (' . User::get('username') . ') ' . "\n";
					$log .= 'Tool: ' . $hztv->toolname . ' (id #' . $id . ')' . ' - ' . $hztv->instance . "\n";
					$log .= 'Time : ' . date('c') . "\n";
					$log .= 'Reason for closed source: ' . "\n";
					$log .= $reason . "\n";
					$log .= '-----------------------------------------' . "\n";

					// Log reason for closed source
					$this->_writeToFile($log, PATH_APP . $path . DS . 'closed_source_reasons.txt', true);
				}

				// code for saving license
				$hztv->license = NULL;

				// save version info
				$hztv->update(); //@FIXME: look

				$this->_setTracAccess($hztv->toolname, $hztv->codeaccess, null);

				if ($action != 'confirm')
				{
					$this->_msg = Lang::txt('COM_TOOLS_NOTICE_CHANGE_LICENSE_SAVED');
					$this->statusTask();
				}
				else
				{
					$this->finalizeTask();
				}
				return;
			}
		}

		// Open source
		if (ToolsModelTool::validateLicense($this->license_choice, $hztv->codeaccess, $error))
		{
			// code for saving license
			$hztv->license = strip_tags($this->license_choice['text']);

			// save version info
			$hztv->update(); //@FIXME: look

			$this->_setTracAccess($hztv->toolname, $hztv->codeaccess, null);

			if ($action != 'confirm')
			{
				$this->_msg = Lang::txt('COM_TOOLS_NOTICE_CHANGE_LICENSE_SAVED');
				$this->statusTask();
			}
			else
			{
				$this->finalizeTask();
			}
		}
		else
		{
			$this->view->code			= $hztv->codeaccess;
			$this->view->license_choice = $this->license_choice;

			// display license page with error
			$this->setError($error);
			$this->licenseTask();
		}
	}

	/**
	 * Write sync status to file
	 *
	 * @return   void
	 */
	protected function _writeToFile($content = '', $file = '', $append = false )
	{
		$place   = $append == true ? 'a' : 'w';
		$content = $append ? $content . "\n" : $content;

		$handle  = fopen($file, $place);
		fwrite($handle, $content);
		fclose($handle);
	}

	/**
	 * Show a form for a new entry
	 *
	 * @return     void
	 */
	public function createTask()
	{
		// set defaults
		list($vncGeometryX, $vncGeometryY) = preg_split('/[x]/', $this->config->get('default_vnc'));

		$this->view->defaults = array(
			'toolname'     => 'shortname',
			'title'        => '',
			'version'      => '1.0',
			'description'  => '',
			'exec'         => '',
			'membergroups' => array(),
			'published'    => '',
			'code'         => '',
			'wiki'         => '',
			'developers'   => array(User::get('id')),
			'vncGeometryX' => $vncGeometryX,
			'vncGeometryY' => $vncGeometryY,
			'team'         => User::get('username')
		);

		// Set the page title
		$this->view->title = Lang::txt(strtoupper($this->_option)) . ': ' .  Lang::txt('COM_TOOLS_TASK_CREATE_NEW_TOOL');
		$document = JFactory::getDocument();
		$document->setTitle($this->view->title);

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		if (Pathway::count() <= 1)
		{
			Pathway::append(
				Lang::txt('COM_TOOLS_TASK_CREATE_NEW_TOOL'),
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=create'
			);
		}

		$this->view->admin  = $this->config->get('access-admin-component');
		$this->view->config = $this->config;
		$this->view->id     = '';
		$this->view->editversion = 'dev';
		//$this->view->error = $this->_error;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Show an edit form
	 *
	 * @return     void
	 */
	public function editTask($tool = null)
	{
		$this->view->setLayout('edit');

		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		$this->view->editversion = Request::getVar('editversion', '');
		$this->view->editversion = ($this->view->editversion == 'current') ? 'current' : 'dev'; // do not allow to edit all versions just yet, will default to dev

		// check access rights
		if ($this->_toolid && !$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		// get tool status
		$obj->getToolStatus($this->_toolid, $this->_option, $status, $this->view->editversion);

		if ($this->_toolid && !$status)
		{
			App::abort(404, Lang::txt('COM_TOOLS_ERR_EDIT_CANNOT_FIND'));
			return;
		}

		// Set the page title
		$this->view->title = Lang::txt(strtoupper($this->_option)) . ': ' . Lang::txt('COM_TOOLS_TASK_EDIT_TOOL');
		$document = JFactory::getDocument();
		$document->setTitle($this->view->title);

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		if (Pathway::count() <= 1)
		{
			Pathway::append(
				Lang::txt('COM_TOOLS_STATUS') . ' ' . Lang::txt('COM_TOOLS_FOR') . ' ' . $status['toolname'],
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=status&app=' . $status['toolname']
			);
			Pathway::append(
				Lang::txt('COM_TOOLS_TASK_EDIT_TOOL'),
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=edit&app=' . $status['toolname']
			);
		}

		$this->view->admin    = $this->config->get('access-admin-component');
		$this->view->config   = $this->config;
		$this->view->error    = $this->_error;
		$this->view->defaults = (is_array($tool)) ? $tool : $status;
		$this->view->id       = $this->_toolid;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Set the access for TRAC
	 *
	 * @param      string $toolname Parameter description (if any) ...
	 * @param      string $codeaccess Parameter description (if any) ...
	 * @param      string $wikiaccess Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	protected function _setTracAccess($toolname, $codeaccess, $wikiaccess)
	{
		if (!($hztrac = \Hubzero\Trac\Project::find_or_create('app:' . $toolname)))
		{
			return false;
		}

		if ($codeaccess == '@OPEN')
		{
			$hztrac->add_user_permission(0, array(
				'BROWSER_VIEW',
				'LOG_VIEW',
				'FILE_VIEW'
			));
		}
		elseif ($codeaccess == '@DEV')
		{
			$hztrac->remove_user_permission(0, array(
				'BROWSER_VIEW',
				'LOG_VIEW',
				'FILE_VIEW'
			));
		}

		if ($wikiaccess == '@OPEN')
		{
			$hztrac->add_user_permission(0, array(
				'WIKI_VIEW',
				'MILESTONE_VIEW',
				'ROADMAP_VIEW',
				'SEARCH_VIEW'
			));
		}
		elseif ($wikiaccess == '@DEV')
		{
			$hztrac->remove_user_permission(0, array(
				'WIKI_VIEW',
				'MILESTONE_VIEW',
				'ROADMAP_VIEW',
				'SEARCH_VIEW'
			));
		}

		return true;
	}

	/**
	 * Save an entry
	 *
	 * @return     void
	 */
	public function saveTask()
	{
		$xlog = JFactory::getLogger();

		$exportmap  = array(
			'@OPEN'   => null,
			'@GROUP'  => null,
			'@US'     => 'us',
			'@us'     => 'us',
			'@PU'     => 'pu',
			'@pu'     => 'pu',
			'@D1'     => 'd1',
			'@d1'     => 'd1'
		);

		// set vars
		$tool = Request::getVar('tool', array(), 'post');
		$tool = array_map('trim', $tool);
		$tool = array_map(array('Request', '_cleanVar'), $tool); // Sanitize the input a bit

		$today = Date::toSql();

		$group_prefix = $this->config->get('group_prefix', 'app-');
		$dev_suffix   = $this->config->get('dev_suffix', '_dev');

		// pass data from forms
		$id = Request::getInt('toolid', 0);
		$this->_action  = Request::getVar('action', '');
		$comment     = Request::getVar('comment', '');
		$editversion = Request::getVar('editversion', 'dev', 'post');
		//$toolname    = strtolower($tool['toolname']);
		$oldstatus   = array();

		// Create a Tool Version object
		$objV = new ToolVersion($this->database);

		// Create a Tool object
		$obj = new Tool($this->database);

		if ($id)
		{
			// make sure user is authorized to go further
			if (!$this->_checkAccess($id))
			{
				App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
				return;
			}
		}

		if (!ToolsModelTool::validate($tool, $err, $id))
		{
			// display form with errors
			//$title = Lang::txt(strtoupper($this->_option)).': '.Lang::txt('COM_TOOLS_EDIT_TOOL');
			//$document = JFactory::getDocument();
			//$document->setTitle($title);
			if (is_array($err))
			{
				foreach ($err as $error)
				{
					$this->setError($error);
				}
			}
			else
			{
				$this->setError($err);
			}

			if ($id)
			{
				// get tool status
				$obj->getToolStatus($id, $this->_option, $fstatus, $editversion);

				$tool['developers']   = $fstatus['developers'];
				$tool['membergroups'] = $fstatus['membergroups'];
				$tool['published']    = $fstatus['published'];
			}

			$this->editTask($tool);
			return;
		}

		$tool['vncGeometry']  = $tool['vncGeometryX'] . 'x' . $tool['vncGeometryY'];
		$tool['toolname']     = strtolower($tool['toolname']);
		$tool['developers']   = array_map('trim', explode(',', $tool['developers']));
		$tool['membergroups'] = array_map('trim', explode(',', $tool['membergroups']));

		// save tool info
		if (!$id)  // new tool
		{
			$hzt = ToolsModelTool::createInstance($tool['toolname']);
			$hzt->toolname      = $tool['toolname'];
			$hzt->title         = $tool['title'];
			$hzt->published     = 0;
			$hzt->state         = 1;
			$hzt->priority      = 3;
			$hzt->registered    = $today;
			$hzt->state_changed = $today;
			$hzt->registered_by = User::get('username');
		}
		else
		{
			$hzt = ToolsModelTool::getInstance($id);
		}

		// get tool id for newly registered tool
		$this->_toolid = $hzt->id;

		// save version info
		$hztv = $hzt->getRevision($editversion);
		if ($hztv)
		{
			$oldstatus = $hztv->toArray();
			$oldstatus['toolstate'] = $hzt->state;
			$oldstatus['membergroups'] = $tool['membergroups'];

			if ($id)
			{
				$oldstatus['developers'] = $obj->getToolDevelopers($id);
			}
		}

		if ($editversion == 'dev')
		{
			if ($hztv === false)
			{
				$xlog->debug(__FUNCTION__ . "() HZTV createInstance dev_suffix=$dev_suffix");
				$hztv = ToolsModelVersion::createInstance($tool['toolname'], $tool['toolname'] . $dev_suffix);

				$oldstatus = $hztv->toArray();
				$oldstatus['toolstate']    = $hzt->state;
				$oldstatus['membergroups'] = $tool['membergroups'];
			}

			if ($id)
			{
				$oldstatus['developers'] = $obj->getToolDevelopers($id);
			}

			$invokedir = $this->config->get('invokescript_dir', DS . 'apps');
			$invokedir = rtrim($invokedir, DS);

			$hztv->toolid        = $this->_toolid;
			$hztv->toolname      = $tool['toolname'];
			$hztv->title         = $tool['title'];
			$hztv->version       = $tool['version'];
			$hztv->description   = $tool['description'];
			$hztv->toolaccess    = $tool['exec'];
			$hztv->codeaccess    = $tool['code'];
			$hztv->wikiaccess    = $tool['wiki'];
			$hztv->vnc_command   = $invokedir . DS . $tool['toolname'] . DS . 'dev' . DS . 'middleware' . DS . 'invoke -T dev';
			$hztv->vnc_geometry  = $tool['vncGeometry'];
			$hztv->exportControl = $exportmap[$tool['exec']];
			$hztv->state         = 3;
			$hztv->instance      = $tool['toolname'] . $dev_suffix;
			$hztv->mw            = $this->config->get('default_mw', 'narwhal');

			$hzt->add('version', $hztv->instance);
		}
		else
		{
			if ($hztv)
			{
				$hztv->toolid        = $this->_toolid;
				$hztv->toolname      = $tool['toolname'];
				$hztv->title         = $tool['title'];
				$hztv->version       = $tool['version'];
				$hztv->description   = $tool['description'];
				$hztv->toolaccess    = $tool['exec'];
				$hztv->codeaccess    = $tool['code'];
				$hztv->wikiaccess    = $tool['wiki'];
				$hztv->vnc_geometry  = $tool['vncGeometry'];
				$hztv->exportControl = $exportmap[$tool['exec']];

				$hzt->add('version', $hztv->instance);
			}
		}

		$this->_setTracAccess($tool['toolname'], $hztv->codeaccess, $hztv->wikiaccess);

		if ($this->_error)
		{
			App::abort(500, $this->_error);
			return;
		}

		// create/update developers group
		$gid = $hztv->getDevelopmentGroup();

		if (empty($gid))
		{
			$hzg = new \Hubzero\User\Group();
			$hzg->cn =  $group_prefix . strtolower($tool['toolname']);
			$hzg->create();
			$hzg->set('type', 2);
			$hzg->set('description', Lang::txt('COM_TOOLS_DELEVOPMENT_GROUP', $tool['title']));
			$hzg->set('created', Date::toSql());
			$hzg->set('created_by', User::get('id'));
		}
		else
		{
			$hzg = \Hubzero\User\Group::getInstance($gid);
		}
		$hzg->set('members', $tool['developers']);

		$hztrac = \Hubzero\Trac\Project::find_or_create('app:' . $tool['toolname']);
		$hztrac->add_group_permission('apps', array(
			'WIKI_ADMIN',
			'MILESTONE_ADMIN',
			'BROWSER_VIEW',
			'LOG_VIEW',
			'FILE_VIEW',
			'CHANGESET_VIEW',
			'ROADMAP_VIEW',
			'TIMELINE_VIEW',
			'SEARCH_VIEW'
		));
		$hztrac->add_group_permission($hzg->cn, array(
			'WIKI_ADMIN',
			'MILESTONE_ADMIN',
			'BROWSER_VIEW',
			'LOG_VIEW',
			'FILE_VIEW',
			'CHANGESET_VIEW',
			'ROADMAP_VIEW',
			'TIMELINE_VIEW',
			'SEARCH_VIEW'
		));

		$hztv->set('owner', $hzg->cn);
		$hztv->add('owner', 'apps');
		$hztv->set('member', $tool['membergroups']);

		// Add repo for new tools
		$auto_addrepo = $this->config->get('auto_addrepo', 1);
		if (!$id && $auto_addrepo)
		{
			$hzt->update();  // Make sure tool exists in database or gensvn won't configure apachce access to it
			$hztv->update(); // Make sure tool exists in database or gensvn won't configure apachce access to it

			// Run add repo
			$this->_addRepo($output, array(
				'toolname'    => $tool['toolname'],
				'title'       => $tool['title'],
				'description' => $tool['description']
			));
			if ($output['class'] != 'error')
			{
				$hzt->state = 2;
				$hzt->update();
			}
		}

		// get ticket information
		if (empty($hzt->ticketid))
		{
			$hzt->ticketid = $this->_createTicket($this->_toolid, $tool);
		}

		// create resource page
		$rid = ToolsModelTool::getResourceId($hzt->toolname,$hzt->id);

		if (empty($rid))
		{
			include_once(__DIR__ . DS . 'resource.php');

			$resource = new ToolsControllerResource();

			$rid = $resource->createPage($this->_toolid, $tool);
			// save authors by default
			//$objA = new ToolAuthor($this->database);
			//if(!$id) { $objA->saveAuthors($tool['developers'], 'dev', $rid, '', $tool['toolname']); }
			if (!$id)
			{
				require_once(__DIR__ . DS . 'authors.php');

				$controller = new ToolsControllerAuthors();
				$controller->saveTask(0, $rid, $tool['developers']);

				//$this->author_save(0, $rid, $tool['developers']);
			}
		}

		// display status page
		//$this->_task = 'status';
		//$this->_msg = $id ? Lang::txt('COM_TOOLS_NOTICE_TOOL_INFO_CHANGED'): Lang::txt('COM_TOOLS_NOTICE_TOOL_INFO_REGISTERED');
		$hzg->update();
		$hzt->update();
		$hztv->update(); // @FIXME: look

		$status = $hztv->toArray();
		$status['toolstate']    = $hzt->state;
		$status['membergroups'] = $tool['membergroups'];
		$status['toolname']     = $tool['toolname'];
		if ($id)
		{
			$status['developers'] = $obj->getToolDevelopers($id);
		}

		// update history ticket
		if ($id && $oldstatus != $status && $editversion !='current')
		{
			$this->_newUpdateTicket($hzt->id, $hzt->ticketid, $oldstatus, $status, $comment, 0 , 1);
		}

		$this->setRedirect(
			Route::url('index.php?option='.$this->_option . '&controller=' . $this->_controller . '&task=status&app=' . $hzt->toolname),
			($id ? Lang::txt('COM_TOOLS_NOTICE_TOOL_INFO_CHANGED') : Lang::txt('COM_TOOLS_NOTICE_TOOL_INFO_REGISTERED'))
		);
	}

	/**
	 * Add repo
	 *
	 * @param      array &$output  Messages to be returned
	 * @param      array $toolinfo Tool information
	 * @return     boolean False if errors, True on success
	 */
	protected function _addRepo(&$output, $toolinfo = array())
	{
		if (!$this->_toolid)
		{
			return false;
		}

		// Create a Tool object
		if (empty($toolinfo))
		{
			$obj = new Tool($this->database);
			$obj->getToolStatus($this->_toolid, $this->_option, $toolinfo, 'dev');
		}

		if (!empty($toolinfo))
		{
			$ldap_params = Component::params('com_system');
			$pw = $ldap_params->get('ldap_searchpw','');

			$command = '/usr/bin/addrepo ' . $toolinfo['toolname'] . ' -title "' . $toolinfo['title'] . '" -description "' . $toolinfo['description'] . '" -password "' . $pw . '"' . " -hubdir " . JPATH_ROOT;

			if (!$this->_invokescript($command, Lang::txt('COM_TOOLS_NOTICE_PROJECT_AREA_CREATED'), $output))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			$output['class'] = 'error';
			$output['msg'] = Lang::txt('COM_TOOLS_ERR_CANNOT_RETRIEVE');
			return false;
		}
	}

	/**
	 * Save a version
	 *
	 * @return     void
	 */
	public function saveversionTask()
	{
		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// make sure user is authorized to go further
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		$newstate    = Request::getVar('newstate', '');
		$priority    = Request::getVar('priority', 3);
		$access      = Request::getInt('access', 0);
		$newversion  = Request::getVar('newversion', '');
		$editversion = Request::getVar('editversion', 'dev');
		if (!$this->_action)
		{
			$this->_action = Request::getVar('action', 'dev');
		}
		if (!$this->_error)
		{
			$this->_error = Request::getVar('error', '');
		}
		$error = '';

		$hzt = ToolsModelTool::getInstance($this->_toolid);
		$hztv = $hzt->getRevision($editversion);

		if ($newstate && !intval($newstate))
		{
			$newstate = ToolsHelperHtml::getStatusNum($newstate);
		}

		$oldstatus = ($hztv) ? $hztv->toArray() : array();
		$oldstatus['toolstate'] = $hzt->state;

		if (ToolsModelTool::validateVersion($newversion, $error, $hzt->id))
		{
			$this->_error = $error;
			$hztv->version = $newversion;
			$hztv->update(); // @FIXME: look

			if ($this->_action == 'confirm')
			{
				// Go to license page
				$this->setRedirect(
					Route::url('index.php?option=' . $this->_option . '&controller=pipeline&task=license&action=confirm&app=' . $hztv->toolname)
				);
				return;
			}
			else
			{
				$status = $hztv->toArray();
				$status['toolstate'] = $hzt->state;
				// update history ticket
				if ($oldstatus != $status)
				{
					$this->_newUpdateTicket($hzt->id, $hzt->ticketid, $oldstatus, $status, '');
				}
				$this->_msg = Lang::txt('COM_TOOLS_NOTICE_CHANGE_VERSION_SAVED');
				$this->statusTask();
				return;
			}

		}
		else
		{
			$this->_error = $error;
			$this->versionsTask(); // display version page with error
			return;
		}
	}

	/**
	 * Save notes
	 *
	 * @return     void
	 */
	public function savenotesTask()
	{
		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// make sure user is authorized to go further
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		$action = Request::getVar('action', '');

		if ($action != 'confirm')
		{
			$this->_msg = Lang::txt('COM_TOOLS_RELEASE_NOTES_SAVED');
			//$this->_task = 'status';
			$this->statusTask();
			return;
		}
		else
		{
			$this->finalizeTask();
			return;
		}
	}

	/**
	 * Finalize a tool version
	 *
	 * @return     void
	 */
	public function finalizeversionTask()
	{
		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// make sure user is authorized to go further
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		$newstate    = Request::getVar('newstate', '');
		//$priority    = Request::getVar('priority', 3);
		//$access      = Request::getInt('access', 0);
		//$newversion  = Request::getVar('newversion', '');
		$editversion = Request::getVar('editversion', 'dev');

		$hzt = ToolsModelTool::getInstance($this->_toolid);
		$hztv = $hzt->getRevision($editversion);

		$oldstatus = ($hztv) ? $hztv->toArray() : array();
		$oldstatus['toolstate'] = $hzt->state;

		if ($newstate && !intval($newstate))
		{
			$newstate = ToolsHelperHtml::getStatusNum($newstate);
		}

		$hzt->state = $newstate;
		$hzt->state_changed = Date::toSql();
		$hzt->update();

		$status = $hztv->toArray();
		$status['toolstate'] = $hzt->state;
		// update history ticket
		if ($oldstatus != $status)
		{
			$this->_newUpdateTicket($hzt->id, $hzt->ticketid, $oldstatus, $status, '');
		}
		//$this->addComponentMessage(Lang::txt('COM_TOOLS_NOTICE_STATUS_CHANGED'));
		$this->_msg = Lang::txt('COM_TOOLS_NOTICE_STATUS_CHANGED');
		//$this->_task = 'status';
		$this->statusTask();
	}

	/**
	 * Update a tool version
	 *
	 * @return     void
	 */
	public function updateTask()
	{
		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}
		if (!$this->_error)
		{
			$this->_error = Request::getVar('error', '');
		}
		$error = '';
		//$id = $this->_toolid;

		// make sure user is authorized to go further
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		$xlog = JFactory::getLogger();

		$newstate    = Request::getVar('newstate', '');
		$priority    = Request::getVar('priority', 3);
		$comment     = Request::getVar('comment', '');
		$access      = Request::getInt('access', 0);
		$newversion  = Request::getVar('newversion', '');
		$editversion = Request::getVar('editversion', 'dev');

		$hzt = ToolsModelTool::getInstance($this->_toolid);
		$hztv = $hzt->getRevision($editversion);

		$oldstatus = ($hztv) ? $hztv->toArray() : array();
		$oldstatus['toolstate'] = $hzt->state;

		if ($newstate && !intval($newstate))
		{
			$newstate = ToolsHelperHtml::getStatusNum($newstate);
		}

		if (intval($newstate) && $newstate != $oldstatus['toolstate'])
		{
			$xlog->debug(__FUNCTION__ . "() state changing");

			if ($newstate == ToolsHelperHtml::getStatusNum('Approved') && ToolsModelTool::validateVersion($oldstatus['version'], $error, $hzt->id))
			{
				$this->_error = $error;
				$xlog->debug(__FUNCTION__ . "() state changing to approved, action confirm");
				$this->_action = 'confirm';
				$this->_task = Lang::txt('COM_TOOLS_CONTRIBTOOL_APPROVE_TOOL');
				$this->versionsTask();
				return;
			}
			else if ($newstate == ToolsHelperHtml::getStatusNum('Approved'))
			{
				$this->_error = $error;
				$xlog->debug(__FUNCTION__ . "() state changing to approved, action new");
				$this->_action = 'new';
				$this->_task = Lang::txt('COM_TOOLS_CONTRIBTOOL_APPROVE_TOOL');
				$this->versionsTask();
				return;
			}
			else if ($newstate == ToolsHelperHtml::getStatusNum('Published'))
			{
				$xlog->debug(__FUNCTION__ . "() state changing to published");
				$hzt->published = '1';
			}
			$this->_error = $error;

			// update dev screenshots of a published tool changes status
			if ($oldstatus['state'] == ToolsHelperHtml::getStatusNum('Published'))
			{
				// Create a Tool Version object
				$objV = new ToolVersion($this->database);

				$xlog->debug(__FUNCTION__ . "() state changing away from  published");
				// Get version ids
				$rid = ToolsModelTool::getResourceId($hzt->toolname,$hzt->id);

				$to   = $objV->getVersionIdFromResource($rid, 'dev');
				$from = $objV->getVersionIdFromResource($rid, 'current');

				$dev_hztv = $hzt->getRevision('dev');
				$current_hztv = $hzt->getRevision('current');

				$xlog->debug("update: to=$to from=$from   dev=" . $dev_hztv->id . " current=" . $current_hztv->id);
				if ($to && $from)
				{
					require_once(JPATH_COMPONENT . DS . 'controllers' . DS . 'screenshots.php');

					$ss = new ToolsControllerScreenshots();
					$ss->transfer($from, $to, $rid);
				}
			}

			$xlog->debug(__FUNCTION__ . "() state changing to $newstate");
			$hzt->state = $newstate;
			$hzt->state_changed = Date::toSql();
		}

		// if priority changes
		if (intval($priority) && $priority != $oldstatus['priority'])
		{
			$hzt->priority = $priority;
		}

		// save tool info
		$hzt->update();
		$hztv->update(); //@FIXME: look
		// get tool status after updates
		$status = $hztv->toArray();
		$status['toolstate'] = $hzt->state;
		// update history ticket
		$xlog->debug(__FUNCTION__ . "() before newUpdateTicket test");
		if ($oldstatus != $status || !empty($comment))
		{
			$xlog->debug(__FUNCTION__ . "() before newUpdateTicket");
			$this->_newUpdateTicket($hzt->id, $hzt->ticketid, $oldstatus, $status, $comment, $access, 1);
			$xlog->debug(__FUNCTION__ . "() after newUpdateTicket");
		}

		//$this->addComponentMessage(Lang::txt('COM_TOOLS_NOTICE_STATUS_CHANGED'));
		$this->_msg = Lang::txt('COM_TOOLS_NOTICE_STATUS_CHANGED');
		//$this->_task = 'status';
		$this->statusTask();
	}

	/**
	 * Set ticket update
	 *
	 * @return     void
	 */
	public function messageTask()
	{
		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				// Create a Tool object
				$obj = new Tool($this->database);
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// make sure user is authorized to go further
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		$newstate = Request::getVar('newstate', '');
		$access   = Request::getInt('access', 0);
		$comment  = Request::getVar('comment', '', 'post', 'none', 2);

		if ($newstate && !intval($newstate))
		{
			$newstate = ToolsHelperHtml::getStatusNum($newstate);
		}

		$hzt = ToolsModelTool::getInstance($this->_toolid);

		if ($comment)
		{
			$this->_newUpdateTicket($hzt->id, $hzt->ticketid, '', '', $comment, $access, 1);
			$this->_msg = Lang::txt('COM_TOOLS_NOTICE_MSG_SENT');
			//$this->addComponentMessage(Lang::txt('COM_TOOLS_NOTICE_MSG_SENT'));
		}

		//$this->_task = 'status';
		$this->statusTask();
	}

	/**
	 * Send an email to one or more users
	 *
	 * @param      string $toolid   Tool ID
	 * @param      string $summary  Message subject
	 * @param      string $comment  Message
	 * @param      unknown $access  Parameter description (if any) ...
	 * @param      string $action   Parameter description (if any) ...
	 * @param      array  $toolinfo Array of tool information
	 * @return     void
	 */
	protected function _email($toolid, $summary, $comment, $access, $action, $toolinfo = array())
	{
		$headline = '';

		// Get tool information
		$obj = new Tool($this->database);
		$obj->getToolStatus($toolid, $this->_option, $status, 'dev');

		if (empty($status) && !empty($toolinfo))
		{
			$status = $toolinfo;
		}

		// Get team
		$team = ContribtoolHelper::transform($status['developers'], 'uidNumber');

		// Get admins
		$admins = array();
		if ($this->config->get('access-admin-component'))
		{
			$admins[] = User::get('username');
		}
		$admingroup = $this->config->get('admingroup', '');

		$group = \Hubzero\User\Group::getInstance($admingroup);
		if (is_object($group))
		{
			$members  = $group->get('members');
			$managers = $group->get('managers');
			$members  = array_merge($members, $managers);
			if ($members)
			{
				foreach ($members as $member)
				{
					$muser = \Hubzero\User\Profile::getInstance($member);
					if (is_object($muser))
					{
						$admins[] = $member;
					}
				}
			}
		}

		$inteam = (in_array(User::get('id'), $team)) ? 1 : 0;

		// collector for those who need to get notified
		$users = array();

		switch ($action)
		{
			case 1:
				$action = 'contribtool_info_changed';
				$headline = Lang::txt('COM_TOOLS_INFORMATION_CHANGED');
				//$users = $team;
			break;

			case 2:
				$action = 'contribtool_status_changed';
				$headline = $summary;
				//$users = $this->config->get('access-admin-component') ? $team : $admins;
				//if (!$inteam)
				//{
					//$users[] = User::get('id'); // cc person who made the change if not in team
				//}
			break;

			case 3:
				$action = 'contribtool_new_message';
				$headline = Lang::txt('COM_TOOLS_new message');
				//$users = $this->config->get('access-admin-component') && $access != 1 ? $team : $admins;
			break;

			case 4:
				$action = 'contribtool_status_changed';
				$headline = Lang::txt('COM_TOOLS_NEW_REGISTRATION');
				//$users = array_merge($team, $admins);
			break;

			case 5:
				$action = 'contribtool_status_changed';
				$headline = Lang::txt('COM_TOOLS_REGISTRATION_CANCELLED');
				//$users = array_merge($team, $admins);
			break;
		}

		// send messages to everyone
		$users = array_merge($team, $admins);

		// make sure we are not mailing twice
		$users = array_unique($users);

		// Build e-mail components
		$subject = Lang::txt(strtoupper($this->_option)) . ', ' . Lang::txt('COM_TOOLS_TOOL') . ' ' . $status['toolname'] . '(#' . $toolid . '): ' . $headline;
		$from    = Config::get('sitename') . ' ' . Lang::txt('COM_TOOLS_CONTRIBTOOL');
		$hub     = array(
			'email' => Config::get('mailfrom'),
			'name'  => $from
		);

		$live_site = rtrim(Request::base(),'/');

		// Compose Message
		$message  = strtoupper(Lang::txt('COM_TOOLS_TOOL')) . ': ' . $status['title'] . ' (' . $status['toolname'] . ')' . "\r\n";
		$message .= strtoupper(Lang::txt('COM_TOOLS_SUMMARY')) . ': ' . $summary . "\r\n";
		$message .= strtoupper(Lang::txt('COM_TOOLS_WHEN')) . ' ' . JHTML::_('date', Date::toSql(), Lang::txt('DATE_FORMAT_HZ1')) . "\r\n";
		$message .= strtoupper(Lang::txt('COM_TOOLS_BY')) . ': ' . User::get('username') . "\r\n";
		$message .= '----------------------------' . "\r\n\r\n";
		if ($comment)
		{
			$message .= strtoupper(Lang::txt('COM_TOOLS_MESSAGE')) . ': ' . "\r\n";
			$message .= $comment . "\r\n";
			$message .= '----------------------------' . "\r\n\r\n";
		}
		$message .= Lang::txt('COM_TOOLS_TIP_URL_TO_STATUS') . "\r\n";
		$message .= $live_site.Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=status&app=' . $status['toolname']) . "\r\n";

		// fire off message
		if ($summary or $comment)
		{
			if (!Event::trigger('xmessage.onSendMessage', array($action, $subject, $message, $hub, $users, $this->_option)))
			{
				$this->addComponentMessage(Lang::txt('COM_TOOLS_FAILED_TO_MESSAGE'), 'error');
			}
		}
	}

	/**
	 * Add an update of changes to a support ticket
	 *
	 * @param      integer $toolid    Tool ID
	 * @param      integer $ticketid  Ticket ID
	 * @param      array   $oldstuff  Information before any changes
	 * @param      array   $newstuff  Information after changes
	 * @param      string  $comment   Comments to add
	 * @param      integer $access    Parameter description (if any) ...
	 * @param      integer $email     Parameter description (if any) ...
	 * @param      integer $action    Parameter description (if any) ...
	 * @return     boolean False if errors, True on success
	 */
	protected function _newUpdateTicket($toolid, $ticketid, $oldstuff, $newstuff, $comment, $access=0, $email=0, $action=1)
	{
		$xlog = JFactory::getLogger();
		$xlog->debug(__FUNCTION__ . '() started');

		$summary = '';

		// Make sure ticket is tied to the tool group
		$row = new \Components\Support\Models\Ticket($ticketid);
		if ($row->exists() && isset($newstuff['toolname']))
		{
			$row->set('group', $this->config->get('group_prefix', 'app-') . $newstuff['toolname']);
			$row->store();
		}

		$rowc = new \Components\Support\Models\Comment();
		$rowc->set('ticket', $ticketid);

		// see what changed
		if ($oldstuff != $newstuff)
		{
			if (isset($oldstuff['toolname']) && isset($newstuff['toolname']) && $oldstuff['toolname'] != $newstuff['toolname'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOLNAME'),
					$oldstuff['toolname'],
					$newstuff['toolname']
				);
			}
			if ($oldstuff['title'] != $newstuff['title'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOL') . ' ' . strtolower(Lang::txt('COM_TOOLS_TITLE')),
					$oldstuff['title'],
					$newstuff['title']
				);
				$summary .= strtolower(Lang::txt('COM_TOOLS_TITLE'));
			}
			if ($oldstuff['version'] != '' && $oldstuff['version'] != $newstuff['version'])
			{
				$rowc->changelog()->changed(
					strtolower(Lang::txt('COM_TOOLS_DEV_VERSION_LABEL')),
					$oldstuff['version'],
					$newstuff['version']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_VERSION'));
			}
			else if ($oldstuff['version'] == '' && $newstuff['version'] != '')
			{
				$rowc->changelog()->changed(
					strtolower(Lang::txt('COM_TOOLS_DEV_VERSION_LABEL')),
					'',
					$newstuff['version']
				);
			}
			if ($oldstuff['description'] != $newstuff['description'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOL') . ' ' . strtolower(Lang::txt('COM_TOOLS_DESCRIPTION')),
					$oldstuff['description'],
					$newstuff['description']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_DESCRIPTION'));
			}
			if ($oldstuff['toolaccess'] != $newstuff['toolaccess'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOL_ACCESS'),
					$oldstuff['toolaccess'],
					$newstuff['toolaccess']
				);
				if ($newstuff['toolaccess'] == '@GROUP')
				{
					$rowc->changelog()->changed(
						Lang::txt('COM_TOOLS_ALLOWED_GROUPS'),
						implode(',', $oldstuff['membergroups']),
						implode(',', $newstuff['membergroups'])
					);
				}
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_TOOL_ACCESS'));
			}
			if ($oldstuff['codeaccess'] != $newstuff['codeaccess'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_CODE_ACCESS'),
					$oldstuff['codeaccess'],
					$newstuff['codeaccess']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_CODE_ACCESS'));
			}
			if ($oldstuff['wikiaccess'] != $newstuff['wikiaccess'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_WIKI_ACCESS'),
					$oldstuff['wikiaccess'],
					$newstuff['wikiaccess']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_WIKI_ACCESS'));
			}
			if (isset($oldstuff['vncGeometry']) && isset($newstuff['vncGeometry']) && $oldstuff['vncGeometry'] != $newstuff['vncGeometry'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_VNC_GEOMETRY'),
					$oldstuff['vncGeometry'],
					$newstuff['vncGeometry']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_VNC_GEOMETRY'));
			}
			if (isset($oldstuff['developers']) && isset($newstuff['developers']) && $oldstuff['developers'] != $newstuff['developers'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_DEVELOPMENT_TEAM'),
					ToolsHelperHtml::getDevTeam($oldstuff['developers']),
					ToolsHelperHtml::getDevTeam($newstuff['developers'])
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_DEVELOPMENT_TEAM'));
			}

			// end of tool information changes
			if ($summary)
			{
				$summary .= ' ' . Lang::txt('COM_TOOLS_INFO_CHANGED');
				$action = 1;
			}

			// tool status/priority changes
			if ($oldstuff['priority'] != $newstuff['priority'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_PRIORITY'),
					ToolsHelperHtml::getPriority($oldstuff['priority']),
					ToolsHelperHtml::getPriority($newstuff['priority'])
				);
				$email = 0; // do not send email about priority changes
			}
			if ($oldstuff['toolstate'] != $newstuff['toolstate'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_STATUS'),
					ToolsHelperHtml::getStatusName($oldstuff['toolstate'], $oldstate),
					ToolsHelperHtml::getStatusName($newstuff['toolstate'], $newstate)
				);
				$summary = Lang::txt('COM_TOOLS_STATUS') . ' ' . Lang::txt('COM_TOOLS_TICKET_CHANGED_FROM') . ' ' . $oldstate . ' ' . Lang::txt('COM_TOOLS_TO') . ' ' . $newstate;
				$email   = 1; // send email about status changes
				$action  = 2;
			}
		}

		if ($comment)
		{
			//$action = $action==2 ? $action : 3;
			$email = 1;
			$rowc->set('comment', nl2br($comment));
		}

		$log = $rowc->changelog()->lists();
		if (!empty($log['changes']) || $comment)
		{
			$rowc->set('created', Date::toSql());
			$rowc->set('created_by', User::get('id'));
			$rowc->set('access', $access);

			$xlog->debug(__FUNCTION__ . '() storing ticket');
			if (!$rowc->store())
			{
				$this->_error = $rowc->getError();
				return false;
			}

			if ($email)
			{
				$xlog->debug(__FUNCTION__ . '() emailing notifications');
				// send notification emails
				$this->_email($toolid, $summary, $comment, $access, $action);
			}
		}

		return true;
	}

	/**
	 * Update a support ticket
	 *
	 * @param      integer $toolid    Tool ID
	 * @param      array   $oldstuff  Information before any changes
	 * @param      array   $newstuff  Information after changes
	 * @param      string  $comment   Comments to add
	 * @param      integer $access    Parameter description (if any) ...
	 * @param      integer $email     Parameter description (if any) ...
	 * @param      integer $action    Parameter description (if any) ...
	 * @return     boolean False if errors, True on success
	 */
	protected function _updateTicket($toolid, $oldstuff, $newstuff, $comment, $access=0, $email=0, $action=1, $toolinfo=array())
	{
		$obj = new Tool($this->database);

		$summary = '';

		$rowc = new \Components\Support\Models\Comment();
		$rowc->set('ticket', $obj->getTicketId($toolid));

		// see what changed
		if ($oldstuff != $newstuff)
		{
			if ($oldstuff['toolname'] != $newstuff['toolname'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOLNAME'),
					$oldstuff['toolname'],
					$newstuff['toolname']
				);
			}
			if ($oldstuff['title'] != $newstuff['title'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOL') . ' ' . strtolower(Lang::txt('COM_TOOLS_TITLE')),
					$oldstuff['title'],
					$newstuff['title']
				);
				$summary .= strtolower(Lang::txt('COM_TOOLS_TITLE'));
			}
			if ($oldstuff['version'] !='' && $oldstuff['version'] != $newstuff['version'])
			{
				$rowc->changelog()->changed(
					strtolower(Lang::txt('COM_TOOLS_DEV_VERSION_LABEL')),
					$oldstuff['version'],
					$newstuff['version']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_VERSION'));
			}
			else if ($oldstuff['version'] == '' && $newstuff['version']!='')
			{
				$rowc->changelog()->changed(
					strtolower(Lang::txt('COM_TOOLS_DEV_VERSION_LABEL')),
					'',
					$newstuff['version']
				);
			}
			if ($oldstuff['description'] != $newstuff['description'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOL') . ' ' . strtolower(Lang::txt('COM_TOOLS_DESCRIPTION')),
					$oldstuff['description'],
					$newstuff['description']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_DESCRIPTION'));
			}
			if ($oldstuff['exec'] != $newstuff['exec'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TOOL_ACCESS'),
					$oldstuff['exec'],
					$newstuff['exec']
				);
				if ($newstuff['exec'] == '@GROUP')
				{
					$rowc->changelog()->changed(
						Lang::txt('COM_TOOLS_ALLOWED_GROUPS'),
						'',
						ToolsHelperHtml::getGroups($newstuff['membergroups'])
					);
				}
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_TOOL_ACCESS'));
			}
			if ($oldstuff['code'] != $newstuff['code'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_CODE_ACCESS'),
					$oldstuff['code'],
					$newstuff['code']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_CODE_ACCESS'));
			}
			if ($oldstuff['wiki'] != $newstuff['wiki'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_WIKI_ACCESS'),
					$oldstuff['wiki'],
					$newstuff['wiki']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_WIKI_ACCESS'));
			}
			if ($oldstuff['vncGeometry'] != $newstuff['vncGeometry'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_VNC_GEOMETRY'),
					$oldstuff['vncGeometry'],
					$newstuff['vncGeometry']
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_VNC_GEOMETRY'));
			}
			if ($oldstuff['developers'] != $newstuff['developers'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_DEVELOPMENT_TEAM'),
					ToolsHelperHtml::getDevTeam($oldstuff['developers']),
					ToolsHelperHtml::getDevTeam($newstuff['developers'])
				);
				$summary .= $summary == '' ? '' : ', ';
				$summary .= strtolower(Lang::txt('COM_TOOLS_DEVELOPMENT_TEAM'));
			}

			// end of tool information changes
			if ($summary)
			{
				$summary .= ' ' . Lang::txt('COM_TOOLS_INFO_CHANGED');
				$action = 1;
			}

			// tool status/priority changes
			if ($oldstuff['priority'] != $newstuff['priority'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_PRIORITY'),
					ToolsHelperHtml::getPriority($oldstuff['priority']),
					ToolsHelperHtml::getPriority($newstuff['priority'])
				);
				$email = 0; // do not send email about priority changes
			}
			if ($oldstuff['state'] != $newstuff['state'])
			{
				$rowc->changelog()->changed(
					Lang::txt('COM_TOOLS_TICKET_CHANGED_FROM'),
					ToolsHelperHtml::getStatusName($oldstuff['state'], $oldstate),
					ToolsHelperHtml::getStatusName($newstuff['state'], $newstate)
				);
				$summary = Lang::txt('COM_TOOLS_STATUS') . ' ' . Lang::txt('COM_TOOLS_TICKET_CHANGED_FROM') . ' ' . $oldstate . ' ' . Lang::txt('COM_TOOLS_TO') . ' ' . $newstate;
				$email = 1; // send email about status changes
				$action = 2;
			}
		}

		if ($comment)
		{
			//$action = $action==2 ? $action : 3;
			$email = 1;
			$rowc->set('comment', nl2br($comment));
		}
		$rowc->set('created', Date::toSql());
		$rowc->set('created_by', User::get('id'));
		$rowc->set('access', $access);

		if (!$rowc->store())
		{
			$this->setError($rowc->getError());
			return false;
		}

		if ($email)
		{
			// send notification emails
			$summary = $summary ? $summary : $comment;
			$this->_email($toolid, $summary, $comment, $access, $action, $toolinfo);
		}

		return true;
	}

	/**
	 * Creates a support ticket for a tool
	 *
	 * @param      integer $toolid Tool ID
	 * @param      array   $tool   Array of tool info
	 * @return     mixed False if errors, integer on success
	 */
	private function _createTicket($toolid, $tool)
	{
		$row = new \Components\Support\Models\Ticket();
		$row->set('open', 1);
		$row->set('status', 0);
		$row->set('created', Date::toSql());
		$row->set('login', User::get('username'));
		$row->set('severity', 'normal');
		$row->set('summary', Lang::txt('COM_TOOLS_NEW_TOOL_SUBMISSION') . ': ' . $tool['toolname']);
		$row->set('report', $tool['toolname']);
		$row->set('section', 2);
		$row->set('type', 3);

		// Attach tool group to a ticket for access
		$row->set('group', $this->config->get('group_prefix', 'app-') . $tool['toolname']);
		$row->set('email', User::get('email'));
		$row->set('name', User::get('name'));

		if (!$row->store())
		{
			$this->setError($row->getError());
			return false;
		}
		else
		{
			if ($row->exists())
			{
				// save tag
				$row->tag('tool:' . $tool['toolname'], User::get('id'));

				// store ticket id
				$obj = new Tool($this->database);
				$obj->saveTicketId($toolid, $row->get('id'));

				// make a record
				$this->_updateTicket($toolid, '', '', Lang::txt('COM_TOOLS_NOTICE_TOOL_REGISTERED'), 0, 1, 4, $tool);
			}
		}

		return $row->get('id');
	}

	/**
	 * Cancel a tool contribution
	 *
	 * @return     void
	 */
	public function cancelTask()
	{
		// get vars
		if (!$this->_toolid)
		{
			$this->_toolid = Request::getInt('toolid', 0);
		}

		// Create a Tool object
		$obj = new Tool($this->database);

		// do we have an alias?
		if ($this->_toolid == 0)
		{
			if (($alias = Request::getVar('app', '')))
			{
				$this->_toolid = $obj->getToolId($alias);
			}
		}

		// check access rights
		if (!$this->_checkAccess($this->_toolid))
		{
			App::abort(403, Lang::txt('COM_TOOLS_ALERTNOTAUTH'));
			return;
		}

		// get tool status
		$obj->getToolStatus($this->_toolid, $this->_option, $status, 'dev');

		if (!$status)
		{
			App::abort(404, Lang::txt('COM_TOOLS_ERR_EDIT_CANNOT_FIND'));
			return;
		}
		if ($status['state'] == ToolsHelperHtml::getStatusNum('Abandoned'))
		{
			App::abort(404, Lang::txt('COM_TOOLS_ERR_ALREADY_CANCELLED'));
			return;
		}
		if ($status['published'] == 1)
		{
			App::abort(404, Lang::txt('COM_TOOLS_ERR_CANNOT_CANCEL_PUBLISHED_TOOL'));
			return;
		}

		// unpublish resource page
		include_once(JPATH_COMPONENT . DS . 'controllers' . DS . 'resource.php');

		$resource = new ToolsControllerResource();
		$resource->updatePage($status['resourceid'], $status, '4');

		// change tool status to 'abandoned' and priority to 'lowest'
		$obj->updateTool($this->_toolid, ToolsHelperHtml::getStatusNum('Abandoned') , 5);

		// add comment to ticket
		$this->_updateTicket($this->_toolid, '', '', Lang::txt('COM_TOOLS_NOTICE_TOOL_CANCELLED'), 0, 1, 5);

		// continue output
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=pipeline'),
			Lang::txt('COM_TOOLS_NOTICE_TOOL_CANCELLED')
		);
	}

	/**
	 * Set the license for a tool
	 *
	 * @param      string $toolname Tool name
	 * @return     void
	 */
	public function licenseTool($toolname)
	{
		$token = md5(uniqid());

		$fname = '/tmp/license' . $toolname . $token . 'txt';
		$handle = fopen($fname, "w");

		fwrite($handle, $this->_output);
		fclose($handle);

		$command = '/usr/bin/sudo -u apps /usr/bin/licensetool -hubdir ' . JPATH_ROOT . ' -type raw -license ' . $fname . ' ' . $toolname;

		if (!$this->_invokescript($command, Lang::txt('COM_TOOLS_NOTICE_LICENSE_CHECKED_IN'), $output))
		{
			return false;
		}
		else
		{
			unlink($fname);
			return true;
		}
	}

	/**
	 * Execute a script
	 *
	 * @param      string  $command    Command to execute
	 * @param      string  $successmsg Message to set upon success
	 * @param      array   &$output    Output data
	 * @param      integer $success    Was the exec successful?
	 * @return     boolean True if no errors
	 */
	protected function _invokescript($command, $successmsg, &$output, $success = 1)
	{
		$output['class'] = 'passed';
		$output['msg']   = '';

		exec($command . ' 2>&1 </dev/null', $rawoutput, $status);

		if ($status != 0)
		{
			$output['class'] = 'error';
			$output['msg'] = Lang::txt('COM_TOOLS_ERR_OPERATION_FAILED');
			$success = 0;
		}

		if ($success)
		{
			$output['msg'] = Lang::txt('COM_TOOLS_SUCCESS') . ': ' . $successmsg;
		}

		$msg = '';
		// Print out results or errors
		foreach ($rawoutput as $line)
		{
			$msg = '<br /> * ' . $line;
			$output['msg'] .= $msg;
		}

		return true;
	}

	/**
	 * Check if the current user has access to this tool
	 *
	 * @param      integer $toolid       Tool ID
	 * @param      integer $allowAdmins  Allow admins access?
	 * @param      boolean $allowAuthors Allow authors access?
	 * @return     boolean True if they have access
	 */
	private function _checkAccess($toolid, $allowAdmins=1, $allowAuthors=false)
	{
		// Create a Tool object
		$obj = new Tool($this->database);

		// allow to view if admin
		if ($this->config->get('access-manage-component') && $allowAdmins)
		{
			return true;
		}

		// check if user in tool dev team
		if ($developers = $obj->getToolDevelopers($toolid))
		{
			foreach ($developers as $dv)
			{
				if ($dv->uidNumber == User::get('id'))
				{
					return true;
				}
			}
		}

		// allow access to tool authors
		if ($allowAuthors)
		{
			// Nothing here?
		}

		return false;
	}

	/**
	 * Authorization checks
	 *
	 * @param      string $assetType Asset type
	 * @param      string $assetId   Asset id to check against
	 * @return     void
	 */
	protected function _authorize($assetType='component', $assetId=null)
	{
		$this->config->set('access-view-' . $assetType, true);
		if (User::isGuest())
		{
			return;
		}

		// if no admin group is defined, allow superadmin to act as admin
		// otherwise superadmins can only act if they are also a member of the component admin group
		if (($admingroup = trim($this->config->get('admingroup', ''))))
		{
			// Check if they're a member of admin group
			$ugs = \Hubzero\User\Helper::getGroups(User::get('id'));
			if ($ugs && count($ugs) > 0)
			{
				$admingroup = strtolower($admingroup);
				foreach ($ugs as $ug)
				{
					if (strtolower($ug->cn) == $admingroup)
					{
						$this->config->set('access-manage-' . $assetType, true);
						$this->config->set('access-admin-' . $assetType, true);
						$this->config->set('access-create-' . $assetType, true);
						$this->config->set('access-delete-' . $assetType, true);
						$this->config->set('access-edit-' . $assetType, true);
					}
				}
			}
		}
		else
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
			$this->config->set('access-create-' . $assetType, User::authorise('core.create' . $at, $asset));
			$this->config->set('access-delete-' . $assetType, User::authorise('core.delete' . $at, $asset));
			$this->config->set('access-edit-' . $assetType, User::authorise('core.edit' . $at, $asset));
			$this->config->set('access-edit-state-' . $assetType, User::authorise('core.edit.state' . $at, $asset));
			$this->config->set('access-edit-own-' . $assetType, User::authorise('core.edit.own' . $at, $asset));
		}
	}
}
