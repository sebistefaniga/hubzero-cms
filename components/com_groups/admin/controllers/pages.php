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

/**
 * Groups controller class for managing group pages
 */
class GroupsControllerPages extends \Hubzero\Component\AdminController
{
	/**
	 * Overload exec method to load group object
	 *
	 * @return void
	 */
	public function execute()
	{
		// Incoming
		$this->gid = Request::getVar('gid', '');

		// Ensure we have a group ID
		if (!$this->gid)
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=manage', false),
				Lang::txt('COM_GROUPS_MISSING_ID'),
				'error'
			);
			return;
		}

		// load group object
		$this->group = \Hubzero\User\Group::getInstance($this->gid);

		// run parent execute
		parent::execute();
	}

	/**
	 * Manage group pages
	 *
	 * @return void
	 */
	public function displayTask()
	{
		// get group pages
		$pageArchive = GroupsModelPageArchive::getInstance();
		$this->view->pages = $pageArchive->pages('list', array(
			'gidNumber' => $this->group->get('gidNumber'),
			'state'     => array(0,1,2),
			'orderby'   => 'lft ASC'
		));

		// get page approvers
		$approvers = $this->config->get('approvers', '');
		$approvers = array_map("trim", explode(',', $approvers));

		// are we in the approvers
		$this->view->needsAttention = new \Hubzero\Base\ItemList();
		if (in_array(User::get('username'), $approvers))
		{
			// get group pages
			$pageArchive = GroupsModelPageArchive::getInstance();
			$this->view->needsAttention = $pageArchive->pages('unapproved', array(
				'gidNumber' => $this->group->get('gidNumber'),
				'state'     => array(0,1),
				'orderby'   => 'lft ASC'
			));
		}

		// pass vars to view
		$this->view->group = $this->group;

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Create a group page
	 *
	 * @return void
	 */
	public function addTask()
	{
		$this->editTask();
	}

	/**
	 * Edit a group page
	 *
	 * @return void
	 */
	public function editTask()
	{
		// force layout
		$this->view->setLayout('edit');

		// get request vars
		$ids = Request::getVar('id', array());
		$id  = (isset($ids[0])) ? $ids[0] : null;

		// get the page & version objects
		$this->view->page = new GroupsModelPage($id);
		$this->view->version = $this->view->page->version();
		$this->view->firstversion = $this->view->page->version(1);

		// get a list of all pages for creating module menu
		$pageArchive = GroupsModelPageArchive::getInstance();
		$this->view->pages = $pageArchive->pages('list', array(
			'gidNumber' => $this->group->get('gidNumber'),
			'state'     => array(0,1),
			'orderby'   => 'lft ASC'
		));

		// get page categories
		$categoryArchive = new GroupsModelPageCategoryArchive();
		$this->view->categories = $categoryArchive->categories('list', array(
			'gidNumber' => $this->group->get('gidNumber'),
			'orderby'   => 'title'
		));

		// pass vars to view
		$this->view->group = $this->group;

		// get page templates
		$this->view->pageTemplates = GroupsHelperView::getPageTemplates($this->group);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Save a group page
	 *
	 * @return void
	 */
	public function saveTask()
	{
		// Get the page vars being posted
		$page    = Request::getVar('page', array(), 'post');
		$version = Request::getVar('pageversion', array(), 'post', 'none', JREQUEST_ALLOWRAW);

		// are we updating or creating a new page
		$task = ($page['id']) ? 'update' : 'create';

		// load page and version objects
		$this->page    = new GroupsModelPage( $page['id'] );
		$this->version = new GroupsModelPageVersion();

		// bind new page properties
		if (!$this->page->bind($page))
		{
			Notify::error($this->page->getError());
			return $this->editTask();
		}

		// bind new page version properties
		if (!$this->version->bind($version))
		{
			Notify::error($this->version->getError());
			return $this->editTask();
		}

		// make sure page belongs to group
		if ($task == 'update' && !$this->page->belongsToGroup($this->group))
		{
			App::abort(403, 'You are not authorized to modify this page.');
		}

		// set page vars
		$this->page->set('gidNumber', $this->group->get('gidNumber'));
		$this->page->set('alias', $this->page->uniqueAlias());

		// save page settings
		if (!$this->page->store(true))
		{
			Notify::error($this->page->getError());
			return $this->editTask();
		}

		// set page version vars
		$this->version->set('pageid', $this->page->get('id'));
		$this->version->set('version', $this->version->get('version') + 1);
		$this->version->set('created', Date::toSql());
		$this->version->set('created_by', User::get('id'));
		$this->version->set('approved', 1);
		$this->version->set('approved_on', Date::toSql());
		$this->version->set('approved_by', User::get('id'));

		// if we have php or script tags we must get page approved by admin
		if (strpos($this->version->get('content'), '<?') !== false ||
			strpos($this->version->get('content'), '<?php') !== false ||
			strpos($this->version->get('content'), '<script') !== false)
		{
			$this->version->set('approved', 0);
			$this->version->set('approved_on', NULL);
			$this->version->set('approved_by', NULL);
		}

		// save version settings
		// DONT RUN CHECK ON STORE METHOD (pass false as first arg to store() method)
		if (!$this->version->store(false, $this->group->isSuperGroup()))
		{
			Notify::error($this->version->getError());
			return $this->editTask();
		}

		// get page children
		$children = $this->page->getChildren();

		// if we are publishing/unpublishing
		if ($page['state'] == 0 || $page['state'] == 1)
		{
			// lets mark each child the same as parent
			foreach ($children as $child)
			{
				$child->set('state', $page['state']);
				$child->store(false);
			}
		}

		// if deleting lets set the first childs parent 
		// to be the deleted pages parents
		else if ($page['state'] == 2)
		{
			// update the first childs parent
			if ($firstChild = $children->first())
			{
				$firstChild->set('parent', $this->page->get('parent'));
				$firstChild->store(false);
			}

			// adjust depth foreach child
			// the proper depth is needed when viewing pages
			foreach ($children as $child)
			{
				$child->set('depth', $child->get('depth') - 1);
				$child->store(false);
			}
		}

		// log edit
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'group_page_saved',
			'comments'  => array('page' => $page, 'version' => $version)
		));

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
			Lang::txt('COM_GROUPS_PAGES_SAVED'),
			'passed'
		);
	}

	/**
	 * Delete Page
	 *
	 * @return void
	 */
	public function deleteTask()
	{
		// get request vars
		$ids = Request::getVar('id', array());

		// delete each module
		foreach ($ids as $pageid)
		{
			// load modules
			$page = new GroupsModelPage($pageid);

			// cant delete home
			if ($page->get('home') == 1)
			{
				App::redirect(
					Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
					Lang::txt('COM_GROUPS_PAGES_CANT_DELETE_HOME'),
					'error'
				);
				return;
			}

			//set to deleted state
			$page->set('state', $page::APP_STATE_DELETED);

			// set ordering to 999 when deleting
			$page->set('ordering', 999);

			// save module
			if (!$page->store(true))
			{
				App::redirect(
					Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
					$page->getError(),
					'error'
				);
				return;
			}
		}

		// log change
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'group_page_deleted',
			'comments'  => $ids
		));

		//inform user & redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
			Lang::txt('COM_GROUPS_PAGES_DELETED'),
			'passed'
		);
	}

	/**
	 * Scan group page for possible issues
	 *
	 * @return void
	 */
	public function scanTask()
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		// get request vars
		$id = Request::getInt('id', 0);

		// load page
		$page = new GroupsModelPage($id);

		// load current version
		$currentVersion = $page->version();

		// make sure version is unapproved
		if ($currentVersion->get('approved') == 1)
		{
			//inform user & redirect
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_ALREADY_APPROVED'),
				'warning'
			);
			return;
		}

		// get flags
		$flags = GroupsHelperPages::getCodeFlags();

		// get current versions content by lines
		$content = explode("\n", $currentVersion->get('content'));

		// get any issues
		$issues = new stdClass;
		$issues->count = 0;
		foreach ($flags as $lang => $flag)
		{
			// define level patterns
			$severe   = implode('|', $flag['severe']);
			$elevated = implode('|', $flag['elevated']);
			$minor    = implode('|', $flag['minor']);

			// do case insensitive search for any flags
			if (!isset($issues->$lang))
			{
				$issues->$lang = new stdClass;
			}
			$issues->$lang->severe   = ($severe != '') ? preg_grep("/$severe/i", $content) : array();
			$issues->$lang->elevated = ($elevated != '') ? preg_grep("/$elevated/i", $content) : array();
			$issues->$lang->minor    = ($minor != '') ? preg_grep("/$minor/i", $content) : array();

			// add to issues count
			$issues->count += count($issues->$lang->severe) + count($issues->$lang->elevated) + count($issues->$lang->minor);
		}

		// handle issues
		if ($issues->count != 0)
		{
			$this->view->setLayout('scan');
			$this->view->issues = $issues;
			$this->view->page = $page;
			$this->view->option = $this->_option;
			$this->view->controller = $this->_controller;
			$this->view->group = $this->group;
			$this->view->display();
			return;
		}

		// marked as scanned for potential issues!
		$currentVersion->set('scanned', 1);

		// DONT RUN CHECK ON STORE METHOD (pass false as first arg to store() method)
		$currentVersion->store(false, $this->group->isSuperGroup());

		// were all set
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
			Lang::txt('COM_GROUPS_PAGES_NO_XSS'),
			'passed'
		);
	}

	/**
	 * Check for PHP Errors
	 *
	 * @return void
	 */
	public function errorsTask()
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		// get request vars
		$id = Request::getInt('id', 0);

		// load page
		$page = new GroupsModelPage($id);

		// load current version
		$currentVersion = $page->version();

		// make sure version is unapproved
		if ($currentVersion->get('approved') == 1)
		{
			//inform user & redirect
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_ALREADY_APPROVED'),
				'warning'
			);
			return;
		}

		// create file for page
		$file    = PATH_APP . DS . 'tmp' . DS . 'group_page_' . $page->get('id') . '.php';
		$content = $currentVersion->get('content');
		file_put_contents($file, $content);

		// basic php lint command
		$cmd = 'php -l ' . escapeshellarg($file) . ' 2>&1';

		// run lint
		exec($cmd, $output, $return);

		// do we get errors?
		if ($return != 0)
		{
			$this->view->setLayout('errors');
			$this->view->error = (isset($output[0])) ? $output[0] : '';
			$this->view->error = str_replace($file, '"' . $page->get('title') . '"', $this->view->error);
			$this->view->page = $page;
			$this->view->option = $this->_option;
			$this->view->controller = $this->_controller;
			$this->view->group = $this->group;
			$this->view->display();
			return;
		}

		// marked as checked for errors!
		$currentVersion->set('checked_errors', 1);

		// DONT RUN CHECK ON STORE METHOD (pass false as first arg to store() method)
		$currentVersion->store(false, $this->group->isSuperGroup());

		// delete temp file
		register_shutdown_function(function($file){
			unlink($file);
		}, $file);

		// were all set
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
			Lang::txt('COM_GROUPS_PAGES_NO_ERRORS'),
			'passed'
		);
	}

	/**
	 * Mark Page Scanned
	 *
	 * @return void
	 */
	public function markScannedTask()
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		//get request vars
		$page = Request::getVar('page', array(), 'post', 'none', JREQUEST_ALLOWRAW);

		// load page
		$groupPage = new GroupsModelPage($page['id']);

		// load current version
		$currentVersion = $groupPage->version();

		// set the new content
		$currentVersion->set('content', $page['content']);
		$currentVersion->set('scanned', 1);

		// DONT RUN CHECK ON STORE METHOD (pass false as first arg to store() method)
		$currentVersion->store(false, $this->group->isSuperGroup());

		// inform user and redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
			Lang::txt('COM_GROUPS_PAGES_SCANNED'),
			'passed'
		);
	}

	/**
	 * Save content added in textarea & send off to scanner
	 *
	 * @return void
	 */
	public function scanAgainTask()
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		//get request vars
		$page = Request::getVar('page', array(), 'post', 'none', JREQUEST_ALLOWRAW);

		// load page
		$groupPage = new GroupsModelPage($page['id']);

		// load current version
		$currentVersion = $groupPage->version();

		// set the new content
		$currentVersion->set('content', $page['content']);

		// DONT RUN CHECK ON STORE METHOD (pass false as first arg to store() method)
		$currentVersion->store(false, $this->group->isSuperGroup());

		// redirect to scan url
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid . '&task=scan&id=' . $groupPage->get('id'), false)
		);
	}


	/**
	 * Check for Errors again
	 *
	 * @return void
	 */
	public function errorsCheckAgainTask()
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		//get request vars
		$page = Request::getVar('page', array(), 'post', 'none', JREQUEST_ALLOWRAW);

		// load page
		$groupPage = new GroupsModelPage( $page['id'] );

		// load current version
		$currentVersion = $groupPage->version();

		// set the new content
		$currentVersion->set('content', $page['content']);

		// DONT RUN CHECK ON STORE METHOD (pass false as first arg to store() method)
		$currentVersion->store(false, $this->group->isSuperGroup());

		//go back to error checker
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid . '&task=errors&id=' . $groupPage->get('id'), false)
		);
	}

	/**
	 * Approve a group page
	 *
	 * @return void
	 */
	public function approveTask()
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		// get request vars
		$id = Request::getInt('id', 0);

		// load page
		$page = new GroupsModelPage( $id );

		// load current version
		$currentVersion = $page->version();

		// make sure version is unapproved
		if ($currentVersion->get('approved') == 1)
		{
			//inform user & redirect
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_ALREADY_APPROVED'),
				'warning'
			);
			return;
		}

		// set approved and approved date and approver
		$currentVersion->set('approved', 1);
		$currentVersion->set('approved_on', Date::toSql());
		$currentVersion->set('approved_by', $this->juser->get('id'));

		// save version with approved status
		if (!$currentVersion->store(false, $this->group->isSuperGroup()))
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				$currentVersion->getError(),
				'error'
			);
			return;
		}

		// send approved notifcation
		GroupsHelperPages::sendApprovedNotification('page', $page);

		// log approval
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'group_page_approved',
			'comments'  => array($page->get('id'))
		));

		// inform user and redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
			Lang::txt('COM_GROUPS_PAGES_APPROVED'),
			'passed'
		);
	}

	/**
	 * Output raw content
	 *
	 * @param     $escape    Escape outputted content
	 * @return    string     HTML content
	 */
	public function rawTask( $escape = true )
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		// get reqest vars
		$pageid  = Request::getInt('pageid', 0, 'get');
		$version = Request::getInt('version', null, 'get');

		// page object
		$page = new GroupsModelPage( $pageid );

		// make sure page belongs to this group
		if (!$page->belongsToGroup($this->group))
		{
			App::abort(403, Lang::txt('COM_GROUPS_PAGES_NOT_AUTH'));
		}

		// load page version
		$pageVersion = $page->version($version);

		// do we have a page version
		if ($pageVersion === null)
		{
			App::abort(404, Lang::txt('COM_GROUPS_PAGES_VERSION_NOT_FOUND'));
		}

		// output page version
		if ($escape)
		{
			echo highlight_string($pageVersion->content('raw'), true);
		}
		else
		{
			echo $pageVersion->content('raw');
		}
		exit();
	}


	/**
	 * Preview Group Page
	 *
	 * @return void
	 */
	public function previewTask()
	{
		// make sure we are approvers
		if (!GroupsHelperPages::isPageApprover())
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . $this->gid, false),
				Lang::txt('COM_GROUPS_PAGES_MUST_BE_AUTHORIZED'),
				'error'
			);
			return;
		}

		// get reqest vars
		$pageid  = Request::getInt('pageid', 0, 'get');
		$version = Request::getInt('version', 0, 'get');

		// page object
		$page = new GroupsModelPage( $pageid );

		// make sure page belongs to this group
		if (!$page->belongsToGroup($this->group))
		{
			App::abort(403, Lang::txt('COM_GROUPS_PAGES_NOT_AUTH'));
		}

		// get preview
		echo GroupsHelperPages::generatePreview($page, $version);
		exit();
	}

	/**
	 * Cancel a group page task
	 *
	 * @return void
	 */
	public function cancelTask()
	{
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&gid=' . Request::getVar('gid', ''), false)
		);
	}

	/**
	 * Manage group
	 *
	 * @return void
	 */
	public function manageTask()
	{
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=manage&task=edit&id=' . Request::getVar('gid', ''), false)
		);
	}
}
