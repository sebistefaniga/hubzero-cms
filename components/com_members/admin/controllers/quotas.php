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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Manage member quotas
 */
class MembersControllerQuotas extends \Hubzero\Component\AdminController
{
	/**
	 * Display member quotas
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Incoming
		$this->view->filters = array(
			'search' => urldecode(Request::getState(
				$this->_option . '.quotas.search',
				'search',
				''
			)),
			'search_field' => urldecode(Request::getState(
				$this->_option . '.quotas.search_field',
				'search_field',
				'name'
			)),
			'sort' => Request::getState(
				$this->_option . '.quotas.sort',
				'filter_order',
				'user_id'
			),
			'sort_Dir' => Request::getState(
				$this->_option . '.quotas.sortdir',
				'filter_order_Dir',
				'ASC'
			),
			'class_alias' => Request::getState(
				$this->_option . '.quotas.class_alias',
				'class_alias',
				''
			),
			'limit' => Request::getState(
				$this->_option . '.quotas.limit',
				'limit',
				Config::get('list_limit'),
				'int'
			),
			'start' => Request::getState(
				$this->_option . '.quotas.limitstart',
				'limitstart',
				0,
				'int'
			)
		);

		$obj = new UsersQuotas($this->database);

		// Get a record count
		$this->view->total = $obj->getCount($this->view->filters, true);
		$this->view->rows  = $obj->getRecords($this->view->filters, true);

		$classes = new MembersQuotasClasses($this->database);
		$this->view->classes = $classes->getRecords();

		$this->view->config = $this->config;

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Create a new quota class
	 *
	 * @return  void
	 */
	public function addTask()
	{
		// Output the HTML
		$this->editTask();
	}

	/**
	 * Edit a member quota
	 *
	 * @param   integer  $id  ID of user to edit
	 * @return  void
	 */
	public function editTask($id=0)
	{
		Request::setVar('hidemainmenu', 1);

		if (!$id)
		{
			// Incoming
			$id = Request::getVar('id', array(0));

			// Get the single ID we're working with
			if (is_array($id))
			{
				$id = (!empty($id)) ? $id[0] : 0;
			}
		}

		$quotas = new UsersQuotas($this->database);
		$this->view->row = $quotas->getRecord($id);

		// Build classes select option
		$quotaClass = new MembersQuotasClasses($this->database);
		$classes    = $quotaClass->getRecords();
		$selected   = '';
		$options[]  = JHTML::_('select.option', '0', Lang::txt('COM_MEMBERS_QUOTA_CUSTOM'), 'value', 'text');

		foreach ($classes as $class)
		{
			$options[] = JHTML::_('select.option', $class->id, $class->alias, 'value', 'text');
			if ($class->id == $this->view->row->class_id)
			{
				$selected = $class->id;
			}
		}
		$this->view->classes = JHTML::_('select.genericlist', $options, 'fields[class_id]', '', 'value', 'text', $selected, 'class_id', false, false);

		$this->view->du = $this->getQuotaUsageTask('array', $this->view->row->id);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view
			->setLayout('edit')
			->display();
	}

	/**
	 * Apply changes to a user quota
	 *
	 * @return  void
	 */
	public function applyTask()
	{
		// Save without redirect
		$this->saveTask();
	}

	/**
	 * Save user quota
	 *
	 * @param   integer  $redirect  Whether or not to redirect after save
	 * @return  void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming fields
		$fields = Request::getVar('fields', array(), 'post');

		// Load the profile
		$row = new UsersQuotas($this->database);

		if ($fields['class_id'])
		{
			$class = new MembersQuotasClasses($this->database);
			$class->load($fields['class_id']);

			if ($class->id)
			{
				$fields['hard_files']  = $class->hard_files;
				$fields['soft_files']  = $class->soft_files;
				$fields['hard_blocks'] = $class->hard_blocks;
				$fields['soft_blocks'] = $class->soft_blocks;
			}
		}

		$user = User::getInstance($fields['user_id']);

		if (!is_object($user) || !$user->get('id'))
		{
			$this->view->task = 'edit';
			$this->setError(Lang::txt('COM_MEMBERS_QUOTA_USER_NOT_FOUND'));
			$this->editTask($row->id);
			return;
		}

		$fields['user_id'] = $user->get('id');

		// Try to save
		if (!$row->save($fields))
		{
			$this->view->task = 'edit';
			$this->setError($row->getError());
			$this->editTask($row->id);
			return;
		}

		// Redirect
		if ($this->_task == 'apply')
		{
			return $this->editTask($row->id);
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_MEMBERS_QUOTA_SAVE_SUCCESSFUL'),
			'message'
		);
	}

	/**
	 * Restore member to default quota class
	 *
	 * @return  void
	 */
	public function restoreDefaultTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Do we have any IDs?
		if (!empty($ids))
		{
			// Loop through each ID and restore
			foreach ($ids as $id)
			{
				$id = intval($id);

				$row = new UsersQuotas($this->database);
				$row->load($id);

				$class = new MembersQuotasClasses($this->database);
				$class->load(array('alias' => 'default'));

				if (!$class->id)
				{
					// Output message and redirect
					App::redirect(
						Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
						Lang::txt('COM_MEMBERS_QUOTA_MISSING_DEFAULT_CLASS'),
						'error'
					);
					return;
				}

				$row->set('class_id'   , $class->id);
				$row->set('hard_files' , $class->hard_files);
				$row->set('soft_files' , $class->soft_files);
				$row->set('hard_blocks', $class->hard_blocks);
				$row->set('soft_blocks', $class->soft_blocks);

				$row->store();
			}
		}
		else // no rows were selected
		{
			// Output message and redirect
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_MEMBERS_QUOTA_DELETE_NO_ROWS'),
				'warning'
			);
		}

		// Output messsage and redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_MEMBERS_QUOTA_SET_TO_DEFAULT')
		);
	}

	/* ------------- */
	/* Classes tasks */
	/* ------------- */

	/**
	 * Display quota classes
	 *
	 * @return  void
	 */
	public function displayClassesTask()
	{
		// Incoming
		$this->view->filters = array(
			'limit' => Request::getState($this->_option . '.classes.limit', 'limit', Config::get('list_limit'), 'int'),
			'start' => Request::getState($this->_option . '.classes.limitstart', 'limitstart', 0, 'int')
		);

		$obj = new MembersQuotasClasses($this->database);

		// Get a record count
		$this->view->total = $obj->getCount($this->view->filters, true);
		$this->view->rows  = $obj->getRecords($this->view->filters, true);

		$this->view->config = $this->config;

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Create a new quota class
	 *
	 * @return  void
	 */
	public function addClassTask()
	{
		// Output the HTML
		$this->editClassTask();
	}

	/**
	 * Edit a quota class
	 *
	 * @param   integer  $id  ID of class to edit
	 * @return  void
	 */
	public function editClassTask($id=0)
	{
		Request::setVar('hidemainmenu', 1);

		if (!$id)
		{
			// Incoming
			$id = Request::getVar('id', array());

			// Get the single ID we're working with
			if (is_array($id))
			{
				$id = (!empty($id)) ? $id[0] : 0;
			}
		}

		// Initiate database class and load info
		$this->view->row = new MembersQuotasClasses($this->database);
		$this->view->row->load($id);

		$quotas = new UsersQuotas($this->database);
		$this->view->user_count = count($quotas->getRecords(array('class_id'=>$id)));

		/*$this->view->groups = array();
		$qcGroups = new MembersQuotasClassesGroups($this->database);
		foreach ($qcGroups->find('list', array('class_id' => $id)) as $group)
		{
			$this->view->groups[] = $group->group_id
		}*/

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			Notify::error($error);
		}

		// Output the HTML
		$this->view
			->setLayout('editClass')
			->display();
	}

	/**
	 * Apply changes to a quota class item
	 *
	 * @return  void
	 */
	public function applyClassTask()
	{
		// Save without redirect
		$this->saveClassTask();
	}

	/**
	 * Save quota class
	 *
	 * @param   integer  $redirect  Whether or not to redirect after save
	 * @return  void
	 */
	public function saveClassTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming fields
		$fields = Request::getVar('fields', array(), 'post');

		// Load the profile
		$row = new MembersQuotasClasses($this->database);

		// Try to save
		if (!$row->save($fields))
		{
			$this->view->task = 'editClass';
			$this->setError($row->getError());
			$this->editClassTask($row->id);
			return;
		}

		// Save class/access-group association
		if (isset($fields['groups']))
		{
			if (!$row->setGroupIds($fields['groups']))
			{
				$this->view->task = 'editClass';
				$this->setError($row->getError());
				$this->editClassTask($row->id);
				return;
			}
		}

		// If changing, update members referencing this class
		$quotas = new UsersQuotas($this->database);
		$quotas->updateUsersByClassId($row->id);

		Notify::success(Lang::txt('COM_MEMBERS_QUOTA_CLASS_SAVE_SUCCESSFUL'));

		// Redirect
		if ($this->_task == 'applyClass')
		{
			return $this->editClassTask($row->id);
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=displayClasses', false)
		);
	}

	/**
	 * Removes class(es)
	 *
	 * @return  void
	 */
	public function deleteClassTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Do we have any IDs?
		if (!empty($ids))
		{
			// Loop through each ID and delete the necessary items
			foreach ($ids as $id)
			{
				$id = intval($id);

				$row = new MembersQuotasClasses($this->database);
				$row->load($id);

				if ($row->alias == 'default')
				{
					// Output message and redirect
					App::redirect(
						Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=displayClasses', false),
						Lang::txt('COM_MEMBERS_QUOTA_CLASS_DONT_DELETE_DEFAULT'),
						'warning'
					);

					return;
				}

				// Remove the record
				$row->delete($id);

				// Restore all members of this class to default
				$quota = new UsersQuotas($this->database);
				$quota->restoreDefaultClass($id);
			}
		}
		else // no rows were selected
		{
			// Output message and redirect
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=displayClasses', false),
				Lang::txt('COM_MEMBERS_QUOTA_DELETE_NO_ROWS'),
				'warning'
			);
		}

		// Output messsage and redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=displayClasses', false),
			Lang::txt('COM_MEMBERS_QUOTA_DELETE_SUCCESSFUL')
		);
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return  void
	 */
	public function cancelClassTask()
	{
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=displayClasses', false)
		);
	}

	/**
	 * Get class values
	 *
	 * @return  void
	 */
	public function getClassValuesTask()
	{
		$class_id = Request::getInt('class_id');

		$class = new MembersQuotasClasses($this->database);
		$class->load($class_id);

		$return = array(
			'soft_files'  => $class->soft_files,
			'hard_files'  => $class->hard_files,
			'soft_blocks' => $class->soft_blocks,
			'hard_blocks' => $class->hard_blocks
		);

		echo json_encode($return);
		exit();
	}

	/**
	 * Get quota usage info for a given quota id
	 *
	 * @return  void
	 */
	public function getQuotaUsageTask($returnType='json', $id=NULL)
	{
		if (is_null($id))
		{
			$id = Request::getInt('id');
		}

		$quota = new UsersQuotas($this->database);
		$quota->load($id);
		$username = User::getInstance($quota->user_id)->get('username');

		$info = array();
		$success = false;

		$config = Component::params('com_tools');
		$host = $config->get('storagehost');

		if ($username && $host)
		{
			$fp = @stream_socket_client($host, $errno, $errstr, 30);
			if (!$fp)
			{
				$info[] = "$errstr ($errno)\n";
			}
			else
			{
				$msg = '';
				fwrite($fp, "getquota user=" . $username . "\n");
				while (!feof($fp))
				{
					$msg .= fgets($fp, 1024);
				}
				fclose($fp);
				$tokens = preg_split('/,/',$msg);
				foreach ($tokens as $token)
				{
					if (!empty($token))
					{
						$t = preg_split('#=#', $token);
						$info[$t[0]] = (isset($t[1])) ? $t[1] : '';
					}
				}

				$used = (isset($info['softspace']) && $info['softspace'] != 0) ? bcdiv($info['space'], $info['softspace'], 6) : 0;
				$percent = round($used * 100);

				$success = true;
			}
		}

		$return = array(
			'success' => $success,
			'info'    => $info,
			'used'    => $used,
			'percent' => $percent
		);

		if ($returnType == 'json')
		{
			echo json_encode($return);
			exit();
		}
		else
		{
			return $return;
		}
	}

	/**
	 * Display quota import page
	 *
	 * @return     void
	 */
	public function importTask()
	{
		$this->view->config = $this->config;

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Process quota import
	 *
	 * @return     void
	 */
	public function processImportTask()
	{
		// Import quotas
		$qfile     = Request::getVar('conf_text');
		$overwrite = Request::getInt('overwrite_existing', 0);

		if (empty($qfile))
		{
			// Output message and redirect
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=import', false),
				Lang::txt('COM_MEMBERS_QUOTA_NO_CONF_TEXT'),
				'warning'
			);

			return;
		}

		$lines   = explode("\n", $qfile);
		$classes = array();
		foreach ($lines as $line)
		{
			$line = trim($line);
			if (empty($line))
			{
				continue;
			}

			if (substr($line, 0, 1) == "#")
			{
				continue;
			}

			$args = preg_split('/\s+/', $line);
			switch ($args[0])
			{
				case 'class':
					$class = new MembersQuotasClasses($this->database);
					$class->load(array('alias' => $args[1]));

					if ($class->id && !$overwrite)
					{
						continue;
					}

					$class->set('alias'      , $args[1]);
					$class->set('soft_blocks', $args[2]);
					$class->set('hard_blocks', $args[3]);
					$class->set('soft_files' , $args[4]);
					$class->set('hard_files' , $args[5]);
					$class->store();
				break;

				case 'user':
					if ($args[2] == 'ignore')
					{
						continue;
					}

					$juser = JFactory::getUser($args[1]);
					if (!is_object($juser) || !is_numeric($juser->get('id')))
					{
						continue;
					}
					else
					{
						$user_id = $juser->get('id');
					}

					$class = new MembersQuotasClasses($this->database);
					$class->load(array('alias' => $args[2]));

					if (!$class->id)
					{
						continue;
					}

					$quota = new UsersQuotas($this->database);
					$quota->load(array('user_id' => $user_id));

					if ($quota->id && !$overwrite)
					{
						continue;
					}

					$quota->set('user_id'    , $user_id);
					$quota->set('class_id'   , $class->id);
					$quota->set('soft_blocks', $class->soft_blocks);
					$quota->set('hard_blocks', $class->hard_blocks);
					$quota->set('soft_files' , $class->soft_files);
					$quota->set('hard_files' , $class->hard_files);
					$quota->store();
				break;
			}
		}

		// Output message and redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_MEMBERS_QUOTA_CONF_IMPORT_SUCCESSFUL'),
			'passed'
		);

		return;
	}

	/**
	 * Check for registered users without quota entries and add them
	 *
	 * @return     void
	 */
	public function importMissingTask()
	{
		// Query for all members in the CMS
		$query = "SELECT `id` FROM `#__users`";
		$this->database->setQuery($query);
		$results = $this->database->loadObjectList();

		if (count($results) > 0)
		{
			$updates = 0;
			$class = new MembersQuotasClasses($this->database);
			$class->load(array('alias' => 'default'));

			if (!$class->id)
			{
				// Output message and redirect
				App::redirect(
					Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=import', false),
					Lang::txt('COM_MEMBERS_QUOTA_MISSING_DEFAULT_CLASS'),
					'error'
				);
				return;
			}

			foreach ($results as $r)
			{
				$quota = new UsersQuotas($this->database);
				$quota->load(array('user_id' => $r->id));

				if ($quota->id)
				{
					continue;
				}

				$quota->set('user_id'    , $r->id);
				$quota->set('class_id'   , $class->id);
				$quota->set('soft_blocks', $class->soft_blocks);
				$quota->set('hard_blocks', $class->hard_blocks);
				$quota->set('soft_files' , $class->soft_files);
				$quota->set('hard_files' , $class->hard_files);
				$quota->store();
				$updates++;
			}
		}

		// Output message and redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_MEMBERS_QUOTA_MISSING_USERS_IMPORT_SUCCESSFUL', $updates),
			'passed'
		);

		return;
	}
}