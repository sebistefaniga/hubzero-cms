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

namespace Components\Cron\Admin\Controllers;

use Components\Cron\Models\Manager;
use Components\Cron\Models\Job;
use Components\Cron\Tables\Job as Table;
use Hubzero\Component\AdminController;
use stdClass;
use Request;
use Config;
use Event;
use Route;
use Lang;
use User;
use Date;
use App;

/**
 * Cron controller class for jobs
 */
class Jobs extends AdminController
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
		$this->registerTask('publish', 'state');
		$this->registerTask('unpublish', 'state');

		parent::execute();
	}

	/**
	 * Displays a form for editing an entry
	 *
	 * @return	void
	 */
	public function displayTask()
	{
		// Filters
		$this->view->filters = array(
			'limit' => Request::getState(
				$this->_option . '.jobs.limit',
				'limit',
				Config::get('list_limit'),
				'int'
			),
			'start' => Request::getState(
				$this->_option . '.jobs.limitstart',
				'limitstart',
				0,
				'int'
			),
			'sort' => trim(Request::getState(
				$this->_option . '.jobs.sort',
				'filter_order',
				'id'
			)),
			'sort_Dir' => trim(Request::getState(
				$this->_option . '.jobs.sortdir',
				'filter_order_Dir',
				'ASC'
			))
		);

		$model = new Manager();

		// Get a record count
		$this->view->total   = $model->jobs('count', $this->view->filters);

		// Get records
		$this->view->results = $model->jobs('list', $this->view->filters);

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Displays a form for editing an entry
	 *
	 * @param   mixed  $row
	 * @return  void
	 */
	public function editTask($row=null)
	{
		Request::setVar('hidemainmenu', 1);

		// Load info from database
		if (!is_object($row))
		{
			// Incoming
			$id = Request::getVar('id', array(0));
			if (is_array($id))
			{
				$id = intval($id[0]);
			}

			$row = new Job($id);
		}

		$this->view->row = $row;

		if (!$this->view->row->get('id'))
		{
			$this->view->row->set('created', Date::toSql());
			$this->view->row->set('created_by', User::get('id'));

			$this->view->row->set('recurrence', '');
		}
		$this->view->row->set('minute', '*');
		$this->view->row->set('hour', '*');
		$this->view->row->set('day', '*');
		$this->view->row->set('month', '*');
		$this->view->row->set('dayofweek', '*');
		if ($this->view->row->get('recurrence'))
		{
			$bits = explode(' ', $this->view->row->get('recurrence'));
			$this->view->row->set('minute', $bits[0]);
			$this->view->row->set('hour', $bits[1]);
			$this->view->row->set('day', $bits[2]);
			$this->view->row->set('month', $bits[3]);
			$this->view->row->set('dayofweek', $bits[4]);
		}

		$defaults = array(
			'',
			'0 0 1 1 *',
			'0 0 1 * *',
			'0 0 * * 0',
			'0 0 * * *',
			'0 * * * *'
		);
		if (!in_array($this->view->row->get('recurrence'), $defaults))
		{
			$this->view->row->set('recurrence', 'custom');
		}

		$e = array();

		$events = Event::trigger('cron.onCronEvents');
		if ($events)
		{
			foreach ($events as $event)
			{
				$e[$event->plugin] = $event->events;
			}
		}

		$this->database->setQuery("SELECT p.* FROM `#__extensions` AS p WHERE p.type='plugin' AND p.folder='cron' AND enabled=1 ORDER BY p.ordering");
		$this->view->plugins = $this->database->loadObjectList();
		if ($this->view->plugins)
		{
			foreach ($this->view->plugins as $key => $plugin)
			{
				$this->view->plugins[$key]->events = (isset($e[$plugin->element])) ? $e[$plugin->element] : array();
			}
		}

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
	 * Save changes to an entry
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$fields = Request::getVar('fields', array(), 'post');

		$recurrence = array();
		if (isset($fields['minute']))
		{
			$recurrence[] = ($fields['minute']['c']) ? $fields['minute']['c'] : $fields['minute']['s'];
		}
		if (isset($fields['hour']))
		{
			$recurrence[] = ($fields['hour']['c']) ? $fields['hour']['c'] : $fields['hour']['s'];
		}
		if (isset($fields['day']))
		{
			$recurrence[] = ($fields['day']['c']) ? $fields['day']['c'] : $fields['day']['s'];
		}
		if (isset($fields['month']))
		{
			$recurrence[] = ($fields['month']['c']) ? $fields['month']['c'] : $fields['month']['s'];
		}
		if (isset($fields['dayofweek']))
		{
			$recurrence[] = ($fields['dayofweek']['c']) ? $fields['dayofweek']['c'] : $fields['dayofweek']['s'];
		}
		if (!empty($recurrence))
		{
			$fields['recurrence'] = implode(' ', $recurrence);
		}

		// Initiate extended database class
		$row = new Job();
		if (!$row->bind($fields))
		{
			$this->setError($row->getError(), 'error');
			$this->editTask($row);
			return;
		}

		if ($row->get('recurrence'))
		{
			$row->set('next_run', $row->nextRun());
		}

		$p = new \JRegistry('');
		$p->loadArray(Request::getVar('params', '', 'post'));

		$row->set('params', $p->toString());

		// Store content
		if (!$row->store(true))
		{
			$this->setError($row->getError(), 'error');
			$this->editTask($row);
			return;
		}

		if ($this->getTask() == 'apply')
		{
			return $this->editTask($row);
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
			Lang::txt('COM_CRON_ITEM_SAVED')
		);
	}

	/**
	 * Deletes one or more records and redirects to listing
	 *
	 * @return  void
	 */
	public function runTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = Request::getVar('id', array());

		// Ensure we have an ID to work with
		if (empty($ids))
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_CRON_ERROR_NO_ITEMS_SELECTED'),
				'error'
			);
			return;
		}

		$output = new stdClass;
		$output->jobs = array();

		// Loop through each ID
		foreach ($ids as $id)
		{
			$job = new Job(intval($id));
			if (!$job->exists())
			{
				continue;
			}

			if ($job->get('active'))
			{
				continue;
			}

			// Show related content
			$results = Event::trigger('cron.' . $job->get('event'), array($job));
			if ($results)
			{
				if (is_array($results))
				{
					// Set it as active in case there were multiple plugins called on
					// the event. This is to ensure ALL processes finished.
					$job->set('active', 1);

					foreach ($results as $result)
					{
						if ($result)
						{
							$job->set('active', 0);
						}
					}
				}
			}

			$job->set('last_run', Date::toLocal('Y-m-d H:i:s'));
			$job->set('next_run', $job->nextRun());
			$job->store();

			$output->jobs[] = $job->toArray();
		}

		$this->view->output = $output;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Deletes one or more records and redirects to listing
	 *
	 * @return  void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Ensure we have an ID to work with
		if (empty($ids))
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option, false),
				Lang::txt('COM_CRON_ERROR_NO_ITEMS_SELECTED'),
				'error'
			);
			return;
		}

		$obj = new Table($this->database);

		// Loop through each ID
		foreach ($ids as $id)
		{
			if (!$obj->delete(intval($id)))
			{
				$this->addComponentMessage($obj->getError(), 'error');
			}
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option, false),
			Lang::txt('COM_CRON_ITEMS_DELETED')
		);
	}

	/**
	 * Sets the state of one or more entries
	 *
	 * @param   integer  $state  The state to set entries to
	 * @return  void
	 */
	public function stateTask($state=0)
	{
		// Check for request forgeries
		Request::checkToken('get') or Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$state = $this->_task == 'publish' ? 1 : 0;

		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Check for an ID
		if (count($ids) < 1)
		{
			$action = ($state == 1) ? Lang::txt('COM_CRON_STATE_UNPUBLISH') : Lang::txt('COM_CRON_STATE_PUBLISH');

			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_CRON_ERROR_SELECT_ITEMS', $action),
				'error'
			);
			return;
		}

		$total = 0;
		foreach ($ids as $id)
		{
			// Update record(s)
			$row = new Job($id);
			$row->set('state', $state);
			if (!$row->store())
			{
				$this->addComponentMessage($row->getError(), 'error');
				continue;
			}

			$total++;
		}

		// Set message
		if ($state == 1)
		{
			$this->setMessage(Lang::txt('COM_CRON_ITEMS_PUBLISHED', $total));
		}
		else
		{
			$this->setMessage(Lang::txt('COM_CRON_ITEMS_UNPUBLISHED', $total));
		}

		App::redirect(
			Route::url('index.php?option=' . $this->_option, false)
		);
	}
}

