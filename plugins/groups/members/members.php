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

// include role lib
require_once JPATH_ROOT.DS.'plugins'.DS.'groups'.DS.'members'.DS.'role.php';

/**
 * Groups Plugin class for group members
 */
class plgGroupsMembers extends \Hubzero\Plugin\Plugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 */
	protected $_autoloadLanguage = true;

	/**
	 * Return the alias and name for this category of content
	 *
	 * @return     array
	 */
	public function &onGroupAreas()
	{
		$area = array(
			'name' => 'members',
			'title' => Lang::txt('PLG_GROUPS_MEMBERS'),
			'default_access' => $this->params->get('plugin_access','members'),
			'display_menu_tab' => $this->params->get('display_tab', 1),
			'icon' => 'f007'
		);
		return $area;
	}

	/**
	 * Return data on a group view (this will be some form of HTML)
	 *
	 * @param      object  $group      Current group
	 * @param      string  $option     Name of the component
	 * @param      string  $authorized User's authorization level
	 * @param      integer $limit      Number of records to pull
	 * @param      integer $limitstart Start of records to pull
	 * @param      string  $action     Action to perform
	 * @param      array   $access     What can be accessed
	 * @param      array   $areas      Active area(s)
	 * @return     array
	 */
	public function onGroup($group, $option, $authorized, $limit=0, $limitstart=0, $action='', $access, $areas=null)
	{
		$returnhtml = true;
		$active = 'members';

		// The output array we're returning
		$arr = array(
			'html'=>'',
			'metadata'=>''
		);

		//get this area details
		$this_area = $this->onGroupAreas();

		// Check if our area is in the array of areas we want to return results for
		if (is_array($areas) && $limit)
		{
			if (!in_array($this_area['name'], $areas))
			{
				$returnhtml = false;
			}
		}

		// Set some variables so other functions have access
		$this->authorized = $authorized;
		$this->action = $action;
		$this->_option = $option;
		$this->group = $group;
		$this->_name = substr($option, 4, strlen($option));

		//Create user object
		$juser = JFactory::getUser();

		// Only perform the following if this is the active tab/plugin
		if ($returnhtml)
		{
			//set group members plugin access level
			$group_plugin_acl = $access[$active];

			//get the group members
			$members = $group->get('members');

			//if set to nobody make sure cant access
			if ($group_plugin_acl == 'nobody')
			{
				$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_OFF', ucfirst($active)) . '</p>';
				return $arr;
			}

			//check if guest and force login if plugin access is registered or members
			if ($juser->get('guest')
			 && ($group_plugin_acl == 'registered' || $group_plugin_acl == 'members'))
			{
				$url = Route::url('index.php?option=com_groups&cn='.$group->get('cn').'&active='.$active, false, true);

				$this->redirect(
					Route::url('index.php?option=com_users&view=login&return=' . base64_encode($url)),
					Lang::txt('GROUPS_PLUGIN_REGISTERED', ucfirst($active)),
					'warning'
				);
				return;
			}

			//check to see if user is member and plugin access requires members
			if (!in_array($juser->get('id'), $members)
			 && $group_plugin_acl == 'members'
			 && $authorized != 'admin')
			{
				$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_REQUIRES_MEMBER', ucfirst($active)) . '</p>';
				return $arr;
			}

			// Set the page title
			$document = JFactory::getDocument();
			$document->setTitle(Lang::txt(strtoupper($this->_name)).': '.$this->group->description.': '.Lang::txt('PLG_GROUPS_MEMBERS'));

			\Hubzero\Document\Assets::addPluginStylesheet('groups', 'members');
			\Hubzero\Document\Assets::addPluginScript('groups', 'members');

			$gparams = new JRegistry($group->get('params'));
			$this->membership_control = $gparams->get('membership_control', 1);

			$oparams = Component::params($this->_option);
			$this->display_system_users = $oparams->get('display_system_users', 'no');

			switch ($gparams->get('display_system_users', "global"))
			{
				case 'yes':
					$this->display_system_users = 'yes';
				break;
				case 'no':
					$this->display_system_users = 'no';
				break;
				case 'global':
					$this->display_system_users = $this->display_system_users;
				break;
			}

			// Do we need to perform any actions?
			if ($action)
			{
				$action = strtolower(trim($action));

				// Perform the action
				$this->$action();

				// Did the action return anything? (HTML)
				if (isset($this->_output) && $this->_output != '')
				{
					$arr['html'] = $this->_output;
				}
			}

			if (!$arr['html'])
			{
				// Get group members based on their status
				// Note: this needs to happen *after* any potential actions ar performed above

				$view = new \Hubzero\Plugin\View(
					array(
						'folder'  => 'groups',
						'element' => 'members',
						'name'    => 'browse'
					)
				);

				$view->membership_control = $this->membership_control;

				$view->option = $option;
				$view->group = $group;
				$view->authorized = $authorized;
				$this->database = JFactory::getDBO();

				$view->q = Request::getVar('q', '');
				$view->filter = Request::getVar('filter', '');
				$view->role_filter = Request::getVar('role_filter','');

				if ($view->authorized != 'manager' && $view->authorized != 'admin')
				{
					$view->filter = ($view->filter == 'managers') ? $view->filter : 'members';
				}

				//get messages plugin access level
				$view->messages_acl = \Hubzero\User\Group\Helper::getPluginAccess($group, 'messages');

				//get all member roles
				$db = JFactory::getDBO();
				$sql = "SELECT * FROM #__xgroups_roles WHERE gidNumber=".$db->quote($group->get('gidNumber'));
				$db->setQuery($sql);
				$view->member_roles = $db->loadAssocList();

				$group_inviteemails = new \Hubzero\User\Group\InviteEmail($db);
				$view->current_inviteemails = $group_inviteemails->getInviteEmails($this->group->get('gidNumber'), true);

				switch ($view->filter)
				{
					case 'invitees':
						$view->groupusers = ($view->q) ? $group->search('invitees', $view->q) : $group->get('invitees');
						foreach ($view->current_inviteemails as $ie)
						{
							$view->groupusers[] = $ie;
						}
						$view->managers = array();
					break;
					case 'pending':
						$view->groupusers = ($view->q) ? $group->search('applicants', $view->q) : $group->get('applicants');
						$view->managers   = array();
					break;
					case 'managers':
						$view->groupusers = ($view->q) ? $group->search('managers', $view->q) : $group->get('managers');
						$view->groupusers = ($view->role_filter) ? \Hubzero\User\Group\Helper::search_roles($group, $view->role_filter) : $view->groupusers;
						$view->managers   = $group->get('managers');
					break;
					case 'members':
					default:
						$view->groupusers = ($view->q) ? $group->search('members', $view->q) : $group->get('members');
						$view->groupusers = ($view->role_filter) ? \Hubzero\User\Group\Helper::search_roles($group, $view->role_filter) : $view->groupusers;
						$view->managers   = $group->get('managers');
					break;
				}

				//if we dont want to display system users
				//filter values through callback above and then reset array keys
				if ($this->display_system_users == 'no' && is_array($view->groupusers))
				{
					$view->groupusers = array_map(array($this, "isSystemUser"), $view->groupusers);
					$view->groupusers = array_values(array_filter($view->groupusers));
				}

				// sort users before display
				$view->groupusers = $this->sortAlphabetically($view->groupusers);

				$view->limit = Request::getInt('limit', $this->params->get('display_limit', 50));
				$view->start = Request::getInt('limitstart', 0);
				$view->start = ($view->limit == 0) ? 0 : $view->start;
				$view->no_html = Request::getInt('no_html', 0);
				$view->params = $this->params;

				// Initiate paging
				jimport('joomla.html.pagination');
				$view->pageNav = new JPagination(count($view->groupusers), $view->start, $view->limit);

				if ($this->getError())
				{
					$view->setError($this->getError());
				}

				$arr['html'] = $view->loadTemplate();
			}
		}

		//return metadata
		$arr['metadata']['count'] = count($group->get('members'));

		//do we have any pending requests
		$pending = $group->get("applicants");
		if (count($pending) > 0 && in_array($juser->get('id'), $group->get("managers")))
		{
			$title = Lang::txt('PLG_GROUPS_MEMBERS_GROUP_HAS_REQUESTS', $group->get('description'), count($pending));
			$link = Route::url('index.php?option=com_groups&cn='.$this->group->get('cn').'&active=members&filter=pending');
			$arr['metadata']['alert'] = '<a class="alrt" href="' . $link . '"><span><h5>' . Lang::txt('PLG_GROUPS_MEMBERS_ALERT') . '</h5>' . $title . '</span></a>';
		}

		// Return the output
		return $arr;
	}

	/**
	 * [sortAlphabetically description]
	 * @param  [type] $userIds [description]
	 * @return [type]          [description]
	 */
	private function sortAlphabetically($userIds)
	{
		// get each users name
		$users = array();
		$emails = array();
		foreach ($userIds as $k => $userid)
		{
			$profile = Hubzero\User\Profile::getInstance($userid);
			if ($profile)
			{
				$users[$profile->get('uidNumber')] = $profile->get('surname');
			}
			elseif (preg_match("/^[_\.\%0-9a-zA-Z-]+@([0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $userid))
			{
				$emails[] = $userid;
			}
		}

		// sort by last name
		asort($users);

		// return sorted member ids
		return array_merge(array_keys($users), $emails);
	}

	/**
	 * Is user sustem user?
	 * 
	 * @param  [type]  $userid [description]
	 * @return boolean         [description]
	 */
	private function isSystemUser( $userid )
	{
		return (is_numeric($userid) && $userid < 1000) ? null : $userid;
	}

	/**
	 * Make a thumbnail name out of a picture name
	 *
	 * @param      string $thumb Picture name
	 * @return     string
	 */
	public function thumbit($thumb)
	{
		$image = explode('.', $thumb);
		$n = count($image);
		$image[$n-2] .= '_thumb';
		$end = array_pop($image);
		$image[] = $end;
		$thumb = implode('.', $image);

		return $thumb;
	}

	/**
	 * Prepend 0's to an ID
	 *
	 * @param      integer $someid ID to prepend 0's to
	 * @return     integer
	 */
	public function niceidformat($someid)
	{
		while (strlen($someid) < 5)
		{
			$someid = 0 . "$someid";
		}
		return $someid;
	}

	/**
	 * Approve membership for one or more users
	 *
	 * @return     void
	 */
	private function approve()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		$database = JFactory::getDBO();

		// Set a flag for emailing any changes made
		$admchange = '';

		// Note: we use two different lists to avoid situations where the user is already a member but somehow an applicant too.
		// Recording the list of applicants for removal separate allows for removing the duplicate entry from the applicants list
		// without trying to add them to the members list (which they already belong to).
		$users = array();
		$applicants = array();

		// Get all normal members (non-managers) of this group
		$members = $this->group->get('members');

		// Incoming array of users to promote
		$mbrs = Request::getVar('users', array(0));

		foreach ($mbrs as $mbr)
		{
			// Retrieve user's account info
			$targetuser = JUser::getInstance($mbr);

			// Ensure we found an account
			if (is_object($targetuser))
			{
				$uid = $targetuser->get('id');

				// The list of applicants to remove from the applicant list
				$applicants[] = $uid;

				// Loop through existing members and make sure the user isn't already a member
				if (in_array($uid, $members))
				{
					$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_ALREADY_A_MEMBER', $mbr));
					continue;
				}

				// Remove record of reason wanting to join group
				$reason = new GroupsReason($database);
				$reason->deleteReason($targetuser->get('id'), $this->group->get('gidNumber'));

				// Are they approved for membership?
				$admchange .= "\t\t".$targetuser->get('name')."\r\n";
				$admchange .= "\t\t".$targetuser->get('username') .' ('. $targetuser->get('email') .')';
				$admchange .= (count($mbrs) > 1) ? "\r\n" : '';

				// They user is not already a member, so we can go ahead and add them
				$users[] = $uid;

				// E-mail the user, letting them know they've been approved
				$this->notifyUser($targetuser);
			}
			else
			{
				$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_USER_NOTFOUND').' '.$mbr);
			}
		}

		// Remove users from applicants list
		$this->group->remove('applicants', $applicants);

		// Add users to members list
		$this->group->add('members', $users);

		// Save changes
		$this->group->update();

		// log invites
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'membership_approved',
			'comments'  => $users
		));
	}

	/**
	 * Promote one or more users
	 *
	 * @return     void
	 */
	private function promote()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		// Set a flag for emailing any changes made
		$admchange = '';
		$users = array();

		// Get all managers of this group
		$managers = $this->group->get('managers');

		// Incoming array of users to promote
		$mbrs = Request::getVar('users', array(0));

		foreach ($mbrs as $mbr)
		{
			// Retrieve user's account info
			$targetuser = JUser::getInstance($mbr);

			// Ensure we found an account
			if (is_object($targetuser))
			{
				$uid = $targetuser->get('id');

				// Loop through existing managers and make sure the user isn't already a manager
				if (in_array($uid, $managers))
				{
					$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_ALREADY_A_MANAGER', $mbr));
					continue;
				}

				$admchange .= "\t\t".$targetuser->get('name')."\r\n";
				$admchange .= "\t\t".$targetuser->get('username') .' ('. $targetuser->get('email') .')';
				$admchange .= (count($mbrs) > 1) ? "\r\n" : '';

				// They user is not already a manager, so we can go ahead and add them
				$users[] = $uid;
			}
			else
			{
				$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERRORS_USER_NOTFOUND').' '.$mbr);
			}
		}

		// Add users to managers list
		$this->group->add('managers', $users);

		// Save changes
		$this->group->update();

		// log invites
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'membership_promoted',
			'comments'  => $users
		));

		$start = Request::getVar("limitstart", 0);
		$limit = Request::getVar("limit", 25);
		$filter = Request::getVar("filter", "members");

		$this->_redirect = Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members&filter='.$filter.'&limit='.$limit.'&limitstart='.$start);
	}

	/**
	 * Demote one or more users
	 *
	 * @return     void
	 */
	private function demote()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		// Get all managers of this group
		$managers = $this->group->get('managers');

		// Get a count of the number of managers
		$nummanagers = count($managers);

		// Only admins can demote the last manager
		if ($this->authorized != 'admin' && $nummanagers <= 1)
		{
			$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_LAST_MANAGER'));
			return;
		}

		// Set a flag for emailing any changes made
		$admchange = '';
		$users = array();

		// Incoming array of users to demote
		$mbrs = Request::getVar('users', array(0));

		foreach ($mbrs as $mbr)
		{
			// Retrieve user's account info
			$targetuser = JUser::getInstance($mbr);

			// Ensure we found an account
			if (is_object($targetuser))
			{
				$admchange .= "\t\t".$targetuser->get('name')."\r\n";
				$admchange .= "\t\t".$targetuser->get('username') .' ('. $targetuser->get('email') .')';
				$admchange .= (count($mbrs) > 1) ? "\r\n" : '';

				$users[] = $targetuser->get('id');
			}
			else
			{
				$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERRORS_USER_NOTFOUND').' '.$mbr);
			}
		}

		// Make sure there's always at least one manager left
		if ($this->authorized != 'admin' && count($users) >= count($managers))
		{
			$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_LAST_MANAGER'));
			return;
		}

		// Remove users from managers list
		$this->group->remove('managers',$users);

		// Save changes
		$this->group->update();

		// log invites
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'membership_demoted',
			'comments'  => $users
		));

		$start = Request::getVar("limitstart", 0);
		$limit = Request::getVar("limit", 25);
		$filter = Request::getVar("filter", "members");

		$this->_redirect = Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members&filter='.$filter.'&limit='.$limit.'&limitstart='.$start);
	}

	/**
	 * Display a form for sending a message to users being removed
	 *
	 * @return    void
	 */
	private function remove()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		// Set the page title
		$document = JFactory::getDocument();
		$document->setTitle(Lang::txt(strtoupper($this->_name)).': '.$this->group->get('description').': '.Lang::txt(strtoupper($this->action)));

		// Cancel membership confirmation screen
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  => 'groups',
				'element' => 'members',
				'name'    => 'remove'
			)
		);
		$view->option = $this->_option;
		$view->group = $this->group;
		$view->authorized = $this->authorized;
		$view->users = Request::getVar('users', array(0));
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$view->setError($error);
			}
		}

		$this->_output = $view->loadTemplate();
	}

	/**
	 * Remove one or more users
	 *
	 * @return     void
	 */
	private function confirmremove()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		// Get all the group's managers
		$managers = $this->group->get('managers');

		// Get all the group's managers
		$members = $this->group->get('members');

		// Set a flag for emailing any changes made
		$admchange = '';
		$users_mem = array();
		$users_man = array();

		// Incoming array of users to remove
		$mbrs = Request::getVar('users', array(0));

		// Figure out how many managers are being deleted
		$intersect = array_intersect($managers, $mbrs);

		// Only admins can demote the last manager
		if ($this->authorized != 'admin' && (count($managers) == 1 && count($intersect) > 0))
		{
			$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_LAST_MANAGER'));
			return;
		}

		foreach ($mbrs as $mbr)
		{
			// Retrieve user's account info
			$targetuser = JUser::getInstance($mbr);

			// Ensure we found an account
			if (is_object($targetuser))
			{
				$admchange .= "\t\t".$targetuser->get('name')."\r\n";
				$admchange .= "\t\t".$targetuser->get('username') .' ('. $targetuser->get('email') .')';
				$admchange .= (count($mbrs) > 1) ? "\r\n" : '';

				$uid = $targetuser->get('id');

				if (in_array($uid, $members))
				{
					$users_mem[] = $uid;
				}

				if (in_array($uid, $managers))
				{
					$users_man[] = $uid;
				}

				require_once JPATH_ROOT . DS . 'plugins' . DS . 'groups' . DS . 'members' . DS . 'role.php';
				GroupsMembersRole::deleteRolesForUserWithId($uid);

				$this->notifyUser($targetuser);
			}
			else
			{
				$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_USER_NOTFOUND').' '.$mbr);
			}
		}

		// Remove users from members list
		$this->group->remove('members',$users_mem);

		// Make sure there's always at least one manager left
		if ($this->authorized != 'admin' && count($users_man) >= count($managers))
		{
			$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_LAST_MANAGER'));
		}
		else
		{
			// Remove users from managers list
			$this->group->remove('managers', $users_man);
		}

		// Save changes
		$this->group->update();

		// log invites
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'membership_removed',
			'comments'  => $users_mem
		));
		$app = JFactory::getApplication();
		$app->redirect(Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members'));
	}

	/**
	 * Add members
	 *
	 * @return     void
	 */
	private function add()
	{
		$app = JFactory::getApplication();

		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		$app->redirect(Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&task=invite&return=members'),'','message',true);
	}

	/**
	 * Display a form for a message to send to users that are denied membership
	 *
	 * @return     void
	 */
	private function deny()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		// Get message about restricted access to group
		$msg = $this->group->get('restrict_msg');

		// Set the page title
		$document = JFactory::getDocument();
		$document->setTitle(Lang::txt(strtoupper($this->_name)).': '.$this->group->get('description').': '.Lang::txt(strtoupper($this->action)));

		// Display form asking for a reason to deny membership
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  => 'groups',
				'element' => 'members',
				'name'    => 'deny'
			)
		);
		$view->option = $this->_option;
		$view->group = $this->group;
		$view->authorized = $this->authorized;
		$view->users = Request::getVar('users', array(0));
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$view->setError($error);
			}
		}

		$this->_output = $view->loadTemplate();
	}

	/**
	 * Deny one or more users membership
	 *
	 * @return     void
	 */
	private function confirmdeny()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		$database = JFactory::getDBO();

		$admchange = '';

		// An array for the users we're going to deny
		$users = array();

		// Incoming array of users to demote
		$mbrs = Request::getVar('users', array(0));

		foreach ($mbrs as $mbr)
		{
			// Retrieve user's account info
			$targetuser = JUser::getInstance($mbr);

			// Ensure we found an account
			if (is_object($targetuser))
			{
				$admchange .= "\t\t".$targetuser->get('name')."\r\n";
				$admchange .= "\t\t".$targetuser->get('username') .' ('. $targetuser->get('email') .')';
				$admchange .= (count($mbrs) > 1) ? "\r\n" : '';

				// Remove record of reason wanting to join group
				$reason = new GroupsReason($database);
				$reason->deleteReason($targetuser->get('id'), $this->group->get('gidNumber'));

				// Add them to the array of users to deny
				$users[] = $targetuser->get('id');

				// E-mail the user, letting them know they've been denied
				$this->notifyUser($targetuser);
			}
			else
			{
				$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_USER_NOTFOUND').' '.$mbr);
			}
		}

		// Remove users from managers list
		$this->group->remove('applicants',$users);

		// Save changes
		$this->group->update();

		// log invites
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'membership_denied',
			'comments'  => $users
		));
	}

	/**
	 * Display a form for confirming canceling membership
	 *
	 * @return     void
	 */
	private function cancel()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		// Set the page title
		$document = JFactory::getDocument();
		$document->setTitle(Lang::txt(strtoupper($this->_name)).': '.$this->group->get('description').': '.Lang::txt(strtoupper($this->action)));

		// Display form asking for a reason to deny membership
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  => 'groups',
				'element' => 'members',
				'name'    => 'cancel'
			)
		);
		$view->option = $this->_option;
		$view->group = $this->group;
		$view->authorized = $this->authorized;
		$view->users = Request::getVar('users', array(0));
		if ($this->getError()) {
			$view->setError($this->getError());
		}

		$this->_output = $view->loadTemplate();
	}

	/**
	 * Cancel membership of one or more users
	 *
	 * @return     void
	 */
	private function confirmcancel()
	{
		if ($this->authorized != 'manager' && $this->authorized != 'admin')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		$database = JFactory::getDBO();

		// An array for the users we're going to deny
		$users = array();
		$user_emails = array();

		// Incoming array of users to demote
		$mbrs = Request::getVar('users', array(0), 'post');

		// Set a flag for emailing any changes made
		$admchange = '';

		foreach ($mbrs as $mbr)
		{
			//if an email address
			if (preg_match("#^[_\.\%0-9a-zA-Z-]+@([0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$#i", $mbr))
			{
				$user_emails[] = $mbr;
				$this->notifyEmailInvitedUser($mbr);
			}
			else
			{
				// Retrieve user's account info
				$targetuser = JUser::getInstance($mbr);

				// Ensure we found an account
				if (is_object($targetuser))
				{
					$admchange .= "\t\t".$targetuser->get('name')."\r\n";
					$admchange .= "\t\t".$targetuser->get('username') .' ('. $targetuser->get('email') .')';
					$admchange .= (count($mbrs) > 1) ? "\r\n" : '';

					// Add them to the array of users to cancel invitations
					$users[] = $targetuser->get('id');

					// E-mail the user, letting them know the invitation has been cancelled
					$this->notifyUser($targetuser);
				}
				else
				{
					$this->setError(Lang::txt('PLG_GROUPS_MESSAGES_ERROR_USER_NOTFOUND').' '.$mbr);
				}
			}
		}

		// Remove users from managers list
		$this->group->remove('invitees', $users);

		// Save changes
		$this->group->update();

		//delete any email invited users
		$db = JFactory::getDBO();
		foreach ($user_emails as $ue)
		{
			$sql = "DELETE FROM #__xgroups_inviteemails WHERE email=".$db->Quote($ue);
			$db->setQuery($sql);
			$db->query();
		}

		// log invites
		GroupsModelLog::log(array(
			'gidNumber' => $this->group->get('gidNumber'),
			'action'    => 'membership_invite_cancelled',
			'comments'  => array_merge($users, $user_emails)
		));

		$app = JFactory::getApplication();
		$app->redirect(Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members&filter=invitees'),'','',true);
	}

	public function addRole()
	{
		$this->editRole();
	}

	public function editRole()
	{
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  => 'groups',
				'element' => 'members',
				'name'    => 'role',
				'layout'  => 'add'
			)
		);

		// database object
		$database = JFactory::getDBO();

		// load role object
		$view->role = new GroupsMembersRole($database);
		$view->role->load(Request::getInt('role', 0));

		// did we pass role back from save?
		if (isset($this->role) && !is_null($this->role))
		{
			$view->role = $this->role;
		}

		// get permissions
		$view->available_permissions = array(
			'group.invite' => Lang::txt('PLG_GROUPS_MEMBERS_ROLE_GROUPINVITE'),
			'group.edit'   => Lang::txt('PLG_GROUPS_MEMBERS_ROLE_GROUPEDIT'),
			'group.pages'   => Lang::txt('PLG_GROUPS_MEMBERS_ROLE_GROUPPAGES'),
		);

		// pass vars to view
		$view->option     = $this->_option;
		$view->group      = $this->group;
		$view->authorized = $this->authorized;
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$view->setError($error);
			}
		}
		$this->_output = $view->loadTemplate();
	}

	public function saveRole()
	{
		// get request vars
		$role = Request::getVar('role', array());
		$role['gidNumber']   = $this->group->get('gidNumber');
		$role['permissions'] = json_encode($role['permissions']);

		// database object
		$database = JFactory::getDBO();

		// load role object
		$this->role = new GroupsMembersRole($database);

		// attempt to save new role
		if (!$this->role->save($role))
		{
			$this->setError($this->role->getError());
			$this->editRole();
			return;
		}

		$this->redirect(
			Route::url('index.php?option=com_groups&cn='. $this->group->get('cn').'&active=members'),
			Lang::txt('PLG_GROUPS_MEMBERS_ROLE_SUCCESS'),
			'passed'
		);
	}

	/**
	 * Add a member role
	 *
	 * @return     void
	 */
	/*
	private function addrole()
	{
		$app = JFactory::getApplication();

		if ($this->membership_control == 0)
		{
			return false;
		}

		$role = Request::getVar('role', '');
		$gid = Request::getVar('gid', '');

		if (!$role || !$gid)
		{
			return false;
		}

		$db = JFactory::getDBO();
		$sql = "INSERT INTO #__xgroups_roles(gidNumber,role) VALUES(".$db->Quote($gid).",".$db->Quote($role).")";
		$db->setQuery($sql);
		if (!$db->query())
		{
			$this->setError('An error occurred while trying to add the member role. Please try again.');
		}

		$app->redirect(Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members'),'','message',true);
	}
	*/
	/**
	 * Remove a member role
	 *
	 * @return     void
	 */
	private function removerole()
	{
		$app = JFactory::getApplication();

		if ($this->membership_control == 0)
		{
			return false;
		}

		$role = Request::getVar('role','');

		if (!$role)
		{
			return false;
		}

		$db = JFactory::getDBO();
		$sql = "DELETE FROM #__xgroups_member_roles WHERE roleid=".$db->Quote($role);
		$db->setQuery($sql);
		$db->query();

		$sql = "DELETE FROM #__xgroups_roles WHERE id=".$db->Quote($role);
		$db->setQuery($sql);
		$db->query();

		if (!$db->query())
		{
			$this->setError('An error occurred while trying to remove the member role. Please try again.');
		}

		$app->redirect(Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members'),'','message',true);
	}

	/**
	 * Show a form for assigning a role to a member
	 *
	 * @return     void
	 */
	private function assignrole()
	{
		if ($this->authorized != 'manager')
		{
			return false;
		}

		if ($this->membership_control == 0)
		{
			return false;
		}

		$uid = Request::getVar('uid','');
		if (!$uid)
		{
			return false;
		}

		// Set the page title
		$document = JFactory::getDocument();
		$document->setTitle(Lang::txt(strtoupper($this->_name)).': '.$this->group->get('description').': '.Lang::txt(strtoupper($this->action)));

		// Cancel membership confirmation screen
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  => 'groups',
				'element' => 'members',
				'name'    => 'role',
				'layout'  => 'assign'
			)
		);

		$db = JFactory::getDBO();
		$sql = "SELECT * FROM #__xgroups_roles WHERE gidNumber=".$db->Quote($this->group->get('gidNumber'));
		$db->setQuery($sql);
		$roles = $db->loadAssocList();

		$view->option = $this->_option;
		$view->group = $this->group;
		$view->authorized = $this->authorized;
		$view->uid = $uid;
		$view->roles = $roles;
		$view->no_html = Request::getInt('no_html', 0);
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$view->setError($error);
			}
		}

		$this->_output = $view->loadTemplate();
	}

	/**
	 * Assign a role to a member
	 *
	 * @return     void
	 */
	private function submitrole()
	{
		$app = JFactory::getApplication();

		if ($this->membership_control == 0)
		{
			return false;
		}

		$uid = Request::getVar('uid', '','post');
		$role = Request::getVar('role','','post');
		$no_html = Request::getInt('no_html', 0,'post');

		if (!$uid || !$role)
		{
			$this->setError('You must select a role.');
			$this->assignrole();
			return;
		}

		$db = JFactory::getDBO();
		$sql = "INSERT INTO #__xgroups_member_roles(roleid,uidNumber) VALUES(" . $db->Quote($role) . "," . $db->Quote($uid) . ")";
		$db->setQuery($sql);
		$db->query();

		if ($no_html == 0)
		{
			$app->redirect(Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members'),'','message',true);
		}
	}

	/**
	 * Delete a role
	 *
	 * @return     void
	 */
	private function deleterole()
	{
		$app = JFactory::getApplication();

		if ($this->membership_control == 0)
		{
			return false;
		}

		$uid = Request::getVar('uid','');
		$role = Request::getVar('role','');

		if (!$uid || !$role)
		{
			return false;
		}

		$db = JFactory::getDBO();

		$sql = "DELETE FROM #__xgroups_member_roles WHERE roleid=" . $db->Quote($role) . " AND uidNumber=" . $db->Quote($uid);
		$db->setQuery($sql);
		$db->query();

		if (!$db->query())
		{
			$this->setError('An error occurred while trying to remove the members role. Please try again.');
		}

		$app->redirect(Route::url('index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=members'),'','message',true);
	}

	/**
	 * Notify user of changes
	 *
	 * @param      object $targetuser User to message
	 * @return     void
	 */
	private function notifyUser($targetuser)
	{
		// Get the group information
		$group = $this->group;

		// Build the SEF referenced in the message
		$juri = JURI::getInstance();
		$sef  = Route::url('index.php?option='.$this->_option.'&cn='. $group->get('cn'));
		$sef  = ltrim($sef, DS);

		// Get the site configuration
		$jconfig = JFactory::getConfig();

		// Start building the subject
		$subject = '';
		$plain   = '';

		// Build the e-mail based upon the action chosen
		switch (strtolower($this->action))
		{
			case 'approve':
				// Subject
				$subject .= Lang::txt('PLG_GROUPS_MESSAGES_SUBJECT_MEMBERSHIP_APPROVED');

				// Message
				$plain  = "Your request for membership in the " . $group->get('description') . " group has been approved.\r\n";
				$plain .= "To view this group go to: \r\n";
				$plain .= $juri->base() . $sef . "\r\n";
			break;

			case 'confirmdeny':
				// Incoming
				$reason = Request::getVar('reason', '', 'post');

				// Subject
				$subject .= Lang::txt('PLG_GROUPS_MESSAGES_SUBJECT_MEMBERSHIP_DENIED');

				// Message
				$plain  = "Your request for membership in the " . $group->get('description') . " group has been denied.\r\n\r\n";
				if ($reason)
				{
					$plain .= stripslashes($reason)."\r\n\r\n";
				}
				$plain .= "If you feel this is in error, you may try to join the group again, \r\n";
				$plain .= "this time better explaining your credentials and reasons why you should be accepted.\r\n\r\n";
				$plain .= "To join the group go to: \r\n";
				$plain .= $juri->base() . $sef . "\r\n";
			break;

			case 'confirmremove':
				// Incoming
				$reason = Request::getVar('reason', '', 'post');

				// Subject
				$subject .= Lang::txt('PLG_GROUPS_MESSAGES_SUBJECT_MEMBERSHIP_CANCELLED');

				// Message
				$plain  = "Your membership in the " . $group->get('description') . " group has been cancelled.\r\n\r\n";
				if ($reason)
				{
					$plain .= stripslashes($reason)."\r\n\r\n";
				}
				$plain .= "If you feel this is in error, you may try to join the group again by going to:\r\n";
				$plain .= $juri->base() . $sef . "\r\n";
			break;

			case 'confirmcancel':
				// Incoming
				$reason = Request::getVar('reason', '', 'post');

				// Subject
				$subject .= Lang::txt('PLG_GROUPS_MESSAGES_SUBJECT_INVITATION_CANCELLED');

				// Message
				$plain  = "Your invitation for membership in the " . $group->get('description') . " group has been cancelled.\r\n\r\n";
				if ($reason)
				{
					$plain .= stripslashes($reason)."\r\n\r\n";
				}
				$plain .= "If you feel this is in error, you may try to join the group by going to:\r\n";
				$plain .= $juri->base() . $sef . "\r\n";
			break;
		}

		// Build the "from" data for the e-mail
		$from = array(
			'name'  => $jconfig->getValue('config.sitename') . ' ' . Lang::txt(strtoupper($this->_name)),
			'email' => $jconfig->getValue('config.mailfrom')
		);

		// create message object
		$message = new \Hubzero\Mail\Message();

		// set message details and send
		$message->setSubject($subject)
				->addFrom($from['email'], $from['name'])
				->setTo($targetuser->get('email'))
				->addPart($plain, 'text/plain')
				->send();
	}

	/**
	 * Send an email to an invited user
	 *
	 * @param      string $email Email address to message
	 * @return     boolean True if message sent
	 */
	private function notifyEmailInvitedUser($email)
	{
		// Get the group information
		$group = $this->group;

		// Build the SEF referenced in the message
		$juri = JURI::getInstance();
		$sef = Route::url('index.php?option='.$this->_option.'&cn='. $group->get('cn'));
		$sef = ltrim($sef, DS);

		// Get the site configuration
		$jconfig = JFactory::getConfig();

		//get the reason
		$reason = Request::getVar('reason', '', 'post');

		// Build the "from" info for e-mails
		$from = array(
			'name'  => $jconfig->getValue('config.sitename') . ' ' . Lang::txt(strtoupper($this->_name)),
			'email' => $jconfig->getValue('config.mailfrom')
		);

		//create the subject
		$subject = Lang::txt('PLG_GROUPS_MESSAGES_SUBJECT_INVITATION_CANCELLED');

		//create the message body
		$plain  = "Your invitation for membership in the " . $group->get('description') . " group has been cancelled.\r\n\r\n";
		if ($reason)
		{
			$plain .= stripslashes($reason)."\r\n\r\n";
		}
		$plain .= "If you feel this is in error, you may try to join the group by going to:\r\n";
		$plain .= $juri->base() . $sef . "\r\n";

		//send the message
		if ($email)
		{
			// create message object
			$message = new \Hubzero\Mail\Message();

			// set message details and send
			$message->setSubject($subject)
					->addFrom($from['email'], $from['name'])
					->setTo($email)
					->addPart($plain, 'text/plain')
					->send();
		}

		// all good
		return true;
	}
}

