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
 * @author    Nicholas J. Kisseberth <nkissebe@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Tools controller class
 */
class ToolsControllerPipeline extends \Hubzero\Component\AdminController
{
	/**
	 * Execute a task
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->registerTask('add', 'edit');
		$this->registerTask('apply', 'save');

		parent::execute();
	}

	/**
	 * Display entries in the pipeline
	 *
	 * @return     void
	 */
	public function displayTask()
	{
		$this->view->filters = array(
			// Get filters
			'search' => urldecode(Request::getState(
				$this->_option . '.' . $this->_controller . '.search',
				'search',
				''
			)),
			'search_field' => urldecode(Request::getState(
				$this->_option . '.' . $this->_controller . '.search_field',
				'search_field',
				'all'
			)),
			// Sorting
			'sort' => Request::getState(
				$this->_option . '.' . $this->_controller . '.sort',
				'filter_order',
				'toolname'
			),
			'sort_Dir' => strtoupper(Request::getState(
				$this->_option . '.' . $this->_controller . '.sortdir',
				'filter_order_Dir',
				'ASC'
			)),
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

		$this->view->filters['sortby'] = $this->view->filters['sort'] . ' ' . $this->view->filters['sort_Dir'];

		// In case limit has been changed, adjust limitstart accordingly
		$this->view->filters['start'] = ($this->view->filters['limit'] != 0 ? (floor($this->view->filters['start'] / $this->view->filters['limit']) * $this->view->filters['limit']) : 0);

		// Get a record count
		$this->view->total = ToolsModelTool::getToolCount($this->view->filters, true);

		// Get records
		$this->view->rows = ToolsModelTool::getToolSummaries($this->view->filters, true);

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
	 * Edit an entry
	 *
	 * @return  void
	 */
	public function editTask($row=null)
	{
		Request::setVar('hidemainmenu', 1);

		// Incoming instance ID
		$id = Request::getInt('id', 0);

		// Do we have an ID?
		if (!$id)
		{
			return $this->cancelTask();
		}

		if (!is_object($row))
		{
			$row = ToolsModelTool::getInstance($id);
		}

		$this->view->row = $row;

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
	 * Save an entry
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		Request::checkToken() or die('Invalid Token');

		// Incoming instance ID
		$fields = Request::getVar('fields', array(), 'post');

		// Do we have an ID?
		if (!$fields['id'])
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_TOOLS_ERROR_MISSING_ID'),
				'error'
			);
			return;
		}

		$row = ToolsModelTool::getInstance(intval($fields['id']));
		if (!$row)
		{
			Request::setVar('id', $fields['id']);

			Notify::error(Lang::txt('COM_TOOLS_ERROR_TOOL_NOT_FOUND'));
			return $this->editTask();
		}

		$row->title = trim($fields['title']);

		if (!$row->title)
		{
			Notify::error(Lang::txt('COM_TOOLS_ERROR_MISSING_TITLE'), 'error');
			return $this->editTask($row);
		}

		$row->update();

		if ($this->getTask() == 'apply')
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=edit&id=' . $fields['id'], false)
			);
			return;
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_TOOLS_ITEM_SAVED')
		);
	}

	/**
	 * Temp function to issue new service DOIs for tool versions published previously
	 *
	 * @return  void
	 */
	public function batchdoiTask()
	{
		$yearFormat = 'Y';

		//  Limit one-time batch size
		$limit = Request::getInt('limit', 2);

		// Store output
		$created = array();
		$failed = array();

		// Initiate extended database classes
		$resource = new \Components\Resources\Tables\Resource($this->database);
		$objDOI = new \Components\Resources\Tables\Doi($this->database);
		$objV = new ToolVersion($this->database);
		$objA = new ToolAuthor($this->database);

		$live_site = rtrim(Request::base(),'/');
		$sitename = Config::get('sitename');

		// Get config
		$config = Component::params($this->_option);

		// Get all tool publications without new DOI
		$this->database->setQuery("SELECT * FROM `#__doi_mapping` WHERE doi='' OR doi IS NULL ");
		$rows = $this->database->loadObjectList();

		if ($rows)
		{
			$i = 0;
			foreach ($rows as $row)
			{
				if ($limit && $i == $limit)
				{
					// Output status message
					if ($created)
					{
						foreach ($created as $cr)
						{
							echo '<p>'.$cr.'</p>';
						}
					}
					echo '<p>' . Lang::txt('COM_TOOLS_REGISTERED_DOIS', count($created), count($failed)) . '</p>';
					return;
				}

				// Skip entries with no resource information loaded / non-tool resources
				if (!$resource->load($row->rid) || !$row->alias)
				{
					continue;
				}

				// Get version info
				$this->database->setQuery("SELECT * FROM `#__tool_version` WHERE toolname='" . $row->alias . "' AND revision='" . $row->local_revision . "' AND state!=3 LIMIT 1");
				$results = $this->database->loadObjectList();

				if ($results)
				{
					$title = $results[0]->title ? $results[0]->title : $resource->title;
					$pubyear = $results[0]->released ? trim(JHTML::_('date', $results[0]->released, $yearFormat)) : date('Y');
				}
				else
				{
					// Skip if version not found
					continue;
				}

				// Collect metadata
				$metadata = array();
				$metadata['targetURL'] = $live_site . '/resources/' . $row->rid . '/?rev=' . $row->local_revision;
				$metadata['title']     = htmlspecialchars($title);
				$metadata['pubYear']   = $pubyear;

				// Get authors
				$objA = new ToolAuthor($this->database);
				$authors = $objA->getAuthorsDOI($row->rid);

				// Register DOI
				$doiSuccess = $objDOI->registerDOI($authors, $config, $metadata, $doierr);
				if ($doiSuccess)
				{
					$this->database->setQuery("UPDATE `#__doi_mapping` SET doi='$doiSuccess' WHERE rid=$row->rid AND local_revision=$row->local_revision");
					if (!$this->database->query())
					{
						$failed[] = $doiSuccess;
					}
					else
					{
						$created[] = $doiSuccess;
					}
				}
				else
				{
					print_r($doierr);
					echo '<br />';
					print_r($metadata);
					echo '<br />';
				}

				$i++;
			}
		}

		// Output status message
		if ($created)
		{
			foreach ($created as $cr)
			{
				echo '<p>' . $cr . '</p>';
			}
		}
		echo '<p>' . Lang::txt('COM_TOOLS_REGISTERED_DOIS', count($created), count($failed)) . '</p>';
		return;
	}

	/**
	 * Temp function to ensure #__doi_mapping table is updated
	 *
	 * @return  boolean
	 */
	public function setupdoiTask()
	{
		$fields = $this->database->getTableFields(Config::get('dbprefix') . 'doi_mapping');

		if (!array_key_exists('versionid', $fields[Config::get('dbprefix') . 'doi_mapping']))
		{
			$this->database->setQuery("ALTER TABLE `#__doi_mapping` ADD `versionid` int(11) default '0'");
			if (!$this->database->query())
			{
				echo $this->database->getErrorMsg();
				return false;
			}
		}
		if (!array_key_exists('doi', $fields[Config::get('dbprefix') . 'doi_mapping']))
		{
			$this->database->setQuery("ALTER TABLE `#__doi_mapping` ADD `doi` varchar(50) default NULL");
			if (!$this->database->query())
			{
				echo $this->database->getErrorMsg();
				return false;
			}
		}
		return true;
	}
}
