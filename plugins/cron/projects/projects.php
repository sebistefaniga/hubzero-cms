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

jimport('joomla.plugin.plugin');

/**
 * Cron plugin for projects
 */
class plgCronProjects extends JPlugin
{
	/**
	 * Return a list of events
	 *
	 * @return  array
	 */
	public function onCronEvents()
	{
		$this->loadLanguage();

		$obj = new stdClass();
		$obj->plugin = 'projects';

		$obj->events = array(
			array(
				'name'   => 'computeStats',
				'label'  => Lang::txt('PLG_CRON_PROJECTS_LOG_STATS'),
				'params' => ''
			),
			array(
				'name'   => 'googleSync',
				'label'  => Lang::txt('PLG_CRON_PROJECTS_SYNC_GDRIVE'),
				'params' => ''
			),
			array(
				'name'   => 'gitGc',
				'label'  => Lang::txt('PLG_CRON_PROJECTS_GITGC'),
				'params' => ''
			)
		);

		return $obj;
	}

	/**
	 * Compute and log overall projects usage stats
	 *
	 * @param   object   $job  \Components\Cron\Models\Job
	 * @return  boolean
	 */
	public function computeStats(\Components\Cron\Models\Job $job)
	{
		$database   = JFactory::getDBO();
		$publishing = Plugin::isEnabled('projects', 'publications') ? 1 : 0;

		require_once(PATH_CORE . DS . 'components'. DS . 'com_projects' . DS . 'models' . DS . 'project.php');
		require_once(PATH_CORE . DS . 'components'. DS . 'com_projects' . DS . 'tables' . DS . 'stats.php');

		if ($publishing)
		{
			require_once(PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'tables' . DS . 'publication.php');
			require_once(PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'tables' . DS . 'version.php');
		}

		$tblStats = new \Components\Projects\Tables\Stats($database);
		$model = new \Components\Projects\Models\Project();

		// Compute and store stats
		$stats  = $tblStats->getStats($model, true, $publishing);

		return true;
	}

	/**
	 * Auto sync project repositories connected with GDrive
	 *
	 * @param   object   $job  \Components\Cron\Models\Job
	 * @return  boolean
	 */
	public function googleSync(\Components\Cron\Models\Job $job)
	{
		$database = JFactory::getDBO();

		$pconfig = Component::params('com_projects');

		require_once(PATH_CORE . DS . 'components'. DS . 'com_projects' . DS . 'tables' . DS . 'project.php');
		require_once(PATH_CORE . DS . 'components'. DS . 'com_projects' . DS . 'tables' . DS . 'owner.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_projects' . DS . 'helpers' . DS . 'connect.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_projects' . DS . 'helpers' . DS . 'html.php');

		require_once(PATH_CORE . DS . 'components'. DS . 'com_projects' . DS . 'tables' . DS . 'remotefile.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_projects' . DS . 'helpers' . DS . 'remote' . DS . 'google.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'tables' . DS . 'attachment.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'tables' . DS . 'publication.php');
		require_once(PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'tables' . DS . 'version.php');

		// Get all projects
		$obj = new \Components\Projects\Tables\Project($database);
		$projects = $obj->getValidProjects(array(), array(), $pconfig, false, 'alias');

		if (!$projects)
		{
			return true;
		}

		$prefix = $pconfig->get('offroot', 0) ? '' : JPATH_ROOT;
		$webdir = DS . trim($pconfig->get('webpath'), DS);

		Request::setVar('auto', 1);

		foreach ($projects as $alias)
		{
			// Load project
			$project = $obj->getProject($alias, 0);

			$pparams   = new JParameter($project->params);
			$connected = $pparams->get('google_dir_id');
			$token     = $pparams->get('google_token');

			if (!$connected || !$token)
			{
				continue;
			}

			// Unlock sync
			$obj->saveParam($project->id, 'google_sync_lock', '');

			// Plugin params
			$plugin_params = array(
				$project,
				'com_projects',
				true,
				$project->created_by_user,
				NULL,
				NULL,
				'sync',
				array('files')
			);

			$sections = Event::trigger('projects.onProject', $plugin_params);
		}

		return true;

	}

	/**
	 * Optimize project repos
	 *
	 * @param   object   $job  \Components\Cron\Models\Job
	 * @return  boolean
	 */
	public function gitGc(\Components\Cron\Models\Job $job)
	{
		$database = JFactory::getDBO();

		$pconfig = Component::params('com_projects');

		require_once(PATH_CORE . DS . 'components' . DS .'com_projects' . DS . 'tables' . DS . 'project.php');
		require_once(PATH_CORE . DS . 'components' . DS .'com_projects' . DS . 'helpers' . DS . 'githelper.php');
		require_once(PATH_CORE . DS . 'components' . DS .'com_projects' . DS . 'helpers' . DS . 'html.php');

		// Get all projects
		$obj = new \Components\Projects\Tables\Project($database);
		$projects = $obj->getValidProjects(array(), array(), $pconfig, false, 'alias' );

		if (!$projects)
		{
			return true;
		}

		foreach ($projects as $project)
		{
			$path = \Components\Projects\Helpers\Html::getProjectRepoPath(strtolower($project), 'files');

			// Make sure there is .git directory
			if (!$path || !is_dir($path . DS . '.git'))
			{
				continue;
			}
			$git = new \Components\Projects\Helpers\Git($path);

			$git->callGit('gc --aggressive');
		}

		return true;
	}
}

