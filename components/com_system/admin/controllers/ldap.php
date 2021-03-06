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

namespace Components\System\Admin\Controllers;

use Hubzero\Component\AdminController;
use Route;
use Lang;
use App;

/**
 * Controller class for system config
 */
class Ldap extends AdminController
{
	/**
	 * Default view
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Output the HTML
		$this->view->display();
	}

	/**
	 * Import the hub configuration
	 *
	 * @return  void
	 */
	public function importHubconfigTask()
	{
		if (file_exists(PATH_APP . DS . 'hubconfiguration.php'))
		{
			include_once(PATH_APP . DS . 'hubconfiguration.php');
		}

		$table = new \JTableExtension($this->database);
		$table->load($table->find(array(
			'element' => $this->_option,
			'type'    => 'component'
		)));

		if (class_exists('HubConfig'))
		{
			$hub_config = new \HubConfig();

			$this->config->set('ldap_basedn', $hub_config->hubLDAPBaseDN);
			$this->config->set('ldap_primary', $hub_config->hubLDAPMasterHost);
			$this->config->set('ldap_secondary', $hub_config->hubLDAPSlaveHosts);
			$this->config->set('ldap_tls', $hub_config->hubLDAPNegotiateTLS);
			$this->config->set('ldap_searchdn', $hub_config->hubLDAPSearchUserDN);
			$this->config->set('ldap_searchpw', $hub_config->hubLDAPSearchUserPW);
			$this->config->set('ldap_managerdn', $hub_config->hubLDAPAcctMgrDN);
			$this->config->set('ldap_managerpw', $hub_config->hubLDAPAcctMgrPW);
		}

		$table->params = $this->config->toString();

		$table->store();

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_SYSTEM_LDAP_IMPORT_COMPLETE')
		);
	}

	/**
	 * Delete LDAP group entries
	 *
	 * @return     void
	 */
	public function deleteGroupsTask()
	{
		$result = \Hubzero\Utility\Ldap::deleteAllGroups();

		$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_ERROR_RESULT_UNKNOWN'), 'info');

		if (isset($result['errors']) && isset($result['fatal']) && !empty($result['fatal'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_ERROR_EXPORT_FAILED', $result['fatal'][0]), 'error');
		}
		elseif (isset($result['errors']) && isset($result['warning']) && !empty($result['warning'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_WARNING_COMPLETED_WITH_ERRORS', count($result['warning'])), 'warning');
		}
		elseif (isset($result['success']))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_GROUP_ENTRIES_DELETED', $result['deleted']), 'passed');
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}

	/**
	 * Delete LDAP user entries
	 *
	 * @return  void
	 */
	public function deleteUsersTask()
	{
		$result = \Hubzero\Utility\Ldap::deleteAllUsers();

		$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_ERROR_RESULT_UNKNOWN'), 'info');

		if (isset($result['errors']) && isset($result['fatal']) && !empty($result['fatal'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_ERROR_EXPORT_FAILED', $result['fatal'][0]), 'error');
		}
		elseif (isset($result['errors']) && isset($result['warning']) && !empty($result['warning'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_WARNING_COMPLETED_WITH_ERRORS', count($result['warning'])), 'warning');
		}
		elseif (isset($result['success']))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_USER_ENTRIES_DELETED', $result['deleted']), 'passed');
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}

	/**
	 * Export all groups to LDAP
	 *
	 * @return  void
	 */
	public function exportGroupsTask()
	{
		$result = \Hubzero\Utility\Ldap::syncAllGroups();

		$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_ERROR_RESULT_UNKNOWN'), 'info');

		if (isset($result['errors']) && isset($result['fatal']) && !empty($result['fatal'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_ERROR_EXPORT_FAILED', $result['fatal'][0]), 'error');
		}
		elseif (isset($result['errors']) && isset($result['warning']) && !empty($result['warning'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_WARNING_COMPLETED_WITH_ERRORS', count($result['warning'])), 'warning');
		}
		elseif (isset($result['success']))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_GROUPS_EXPORTED', $result['added'], $result['modified'], $result['deleted'], $result['unchanged']), 'passed');
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}

	/**
	 * Delete LDAP user entries
	 *
	 * @return  void
	 */
	public function exportUsersTask()
	{
		$result = \Hubzero\Utility\Ldap::syncAllUsers();

		$this->setMessage(
			Lang::txt('COM_SYSTEM_LDAP_ERROR_RESULT_UNKNOWN'),
			'info'
		);

		if (isset($result['errors']) && isset($result['fatal']) && !empty($result['fatal'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_ERROR_EXPORT_FAILED', $result['fatal'][0]), 'error');
		}
		elseif (isset($result['errors']) && isset($result['warning']) && !empty($result['warning'][0]))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_WARNING_COMPLETED_WITH_ERRORS', count($result['warning'])), 'warning');
		}
		elseif (isset($result['success']))
		{
			$this->setMessage(Lang::txt('COM_SYSTEM_LDAP_USERS_EXPORTED', $result['added'], $result['modified'], $result['deleted'], $result['unchanged']), 'passed');
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false)
		);
	}
}
