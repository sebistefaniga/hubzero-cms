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

include_once (dirname(__DIR__) . DS . 'models' . DS . 'import.php');

/**
 * Member importer
 */
class MembersControllerImport extends \Hubzero\Component\AdminController
{
	/**
	 * Determine task and execute it
	 *
	 * @return  void
	 */
	public function execute()
	{
		if (!MembersHelper::getActions('component')->get('core.admin'))
		{
			$this->setRedirect(
				'index.php?option=com_members',
				JText::_('Not authorized'),
				'warning'
			);
		}

		parent::execute();
	}

	/**
	 * Display imports
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		$app = JFactory::getApplication();

		// Get filters
		$this->view->filters = array(
			'limit' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limit',
				'limit',
				JFactory::getConfig()->getValue('config.list_limit'),
				'int'
			),
			'start' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limitstart',
				'limitstart',
				0,
				'int'
			),
			'state'    => array(1),
			'sort'     => 'created_at',
			'sort_Dir' => 'DESC',
			'type'     => 'members'
		);

		// get all imports from archive
		$archive = \Hubzero\Content\Import\Model\Archive::getInstance();

		$this->view->total   = $archive->imports('count', $this->view->filters);
		$this->view->imports = $archive->imports('list', $this->view->filters);

		// Initiate paging
		jimport('joomla.html.pagination');
		$this->view->pageNav = new JPagination(
			$this->view->total,
			$this->view->filters['start'],
			$this->view->filters['limit']
		);

		// Set any errors
		if ($this->getError())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}
		}

		// Output the HTML
		$this->view
			->setLayout('display')
			->display();
	}

	/**
	 * Add an Import
	 *
	 * @return  void
	 */
	public function addTask()
	{
		$this->editTask();
	}

	/**
	 * Edit an Import
	 *
	 * @param   object  $row  \Members\Models\Import
	 * @return  void
	 */
	public function editTask($row=null)
	{
		JRequest::setVar('hidemainmenu', 1);

		// get the import object
		if ($row instanceof Members\Models\Import)
		{
			$this->view->import = $row;
		}
		else
		{
			// get request vars
			$id = JRequest::getVar('id', array(0));
			if (is_array($id))
			{
				$id = (isset($id[0]) ? $id[0] : 0);
			}

			$this->view->import = new \Members\Models\Import($id);
		}

		// import params
		$this->view->params = new JParameter($this->view->import->get('params'));

		// get all files in import filespace
		$this->view->files = JFolder::files($this->view->import->fileSpacePath(), '.');

		// get all imports from archive
		$hooksArchive = \Hubzero\Content\Import\Model\Hook\Archive::getInstance();
		$this->view->hooks = $hooksArchive->hooks('list', array(
			'state' => array(1)
		));

		// Set any errors
		if ($this->getErrors())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}
		}

		// Output the HTML
		$this->view
				->setLayout('edit')
				->display();
	}

	/**
	 * Save an Import
	 *
	 * @param   boolean  $redirect  Redirect after save?
	 * @return  void
	 */
	public function applyTask()
	{
		$this->saveTask(false);
	}

	/**
	 * Save an Import
	 *
	 * @return  void
	 */
	public function saveTask($redirect=true)
	{
		// check token
		JSession::checkToken() or die('Invalid Token');

		// Get request vars
		$import = JRequest::getVar('import', array());
		$hooks  = JRequest::getVar('hooks', array());
		$params = JRequest::getVar('params', array());
		$fields = JRequest::getVar('mapping', array());
		$file   = JRequest::getVar('file', array(), 'FILES');

		// Create import model object
		$this->import = new \Members\Models\Import();

		// Set our hooks
		$this->import->set('hooks', json_encode($hooks));

		// Set our fields
		$this->import->set('fields', json_encode($fields));

		// Load current params
		$iparams = new JRegistry($this->import->get('params'));

		// Bind incoming params
		$iparams->loadArray($params);

		// Set params on import object
		$this->import->set('params', $iparams->toString());

		// Bind input to model
		if (!$this->import->bind($import))
		{
			$this->setError($this->import->getError());
			return $this->editTask($this->import);
		}

		// Is this a new import?
		$isNew = false;
		if (!$this->import->exists())
		{
			$isNew = true;

			// Set the created by/at
			$this->import->set('created_by', JFactory::getUser()->get('id'));
			$this->import->set('created_at', JFactory::getDate()->toSql());
		}

		// Do we have a data file
		/*if ($this->import->get('file'))
		{
			// Get record count
			$importImporter = new \Hubzero\Content\Importer();
			$count = $importImporter->count($this->import);
			$this->import->set('count', $count);
		}*/

		// Attempt to save
		if (!$this->import->store(true))
		{
			$this->setError($this->import->getError());
			return $this->editTask();
		}

		// Is this a new import?
		if ($isNew)
		{
			// create folder for files
			$this->_createImportFilespace($this->import);
		}

		// If we have a file
		if (is_array($file) && $file['size'] > 0 && $file['error'] == 0)
		{
			move_uploaded_file($file['tmp_name'], $this->import->fileSpacePath() . DS . $file['name']);
			$this->import->set('file', $file['name']);
			$this->import->set('fields', '');

			// Force into the field map view
			$isNew = true;
		}

		// Do we have a data file?
		if ($this->import->get('file'))
		{
			// get record count
			$importImporter = new \Hubzero\Content\Importer();
			$count = $importImporter->count($this->import);
			$this->import->set('count', $count);
		}

		// Save again with import count
		if (!$this->import->store(true))
		{
			$this->setError($this->import->getError());
			return $this->editTask($this->import);
		}

		// Inform user & redirect
		if ($redirect)
		{
			if ($isNew)
			{
				$this->view
					->set('import', $this->import)
					->setLayout('fields')
					->display();
				return;
			}

			$this->setRedirect(
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller,
				JText::_('COM_MEMBERS_IMPORT_CREATED'),
				'passed'
			);
			return;
		}

		$this->editTask($this->import);
	}

	/**
	 * Delete Import
	 *
	 * @return  void
	 */
	public function removeTask()
	{
		// check token
		JSession::checkToken() or die('Invalid Token');

		// get request vars
		$ids = JRequest::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// loop through all ids posted
		foreach ($ids as $id)
		{
			// make sure we have an object
			if (!$resourceImport = new \Members\Models\Import($id))
			{
				continue;
			}

			// attempt to delete import
			if (!$resourceImport->delete())
			{
				$this->setRedirect(
					'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=display',
					$resourceImport->getError(),
					'error'
				);
				return;
			}
		}

		//inform user & redirect
		$this->setRedirect(
			'index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&task=display',
			JText::_('COM_MEMBERS_IMPORT_REMOVED'),
			'passed'
		);
	}

	/**
	 * Run Import as Dry Run
	 *
	 * @return  void
	 */
	public function runTestTask()
	{
		$this->runTask(1);
	}

	/**
	 * Run Import
	 *
	 * @param   integer  $dryRun
	 * @return  void
	 */
	public function runTask($dryRun = 0)
	{
		// get request vars
		$id = JRequest::getVar('id', array(0));
		$id = (is_array($id) ? $id[0] : $id);

		// are we test mode
		$this->view->dryRun = 1; //$dryRun;

		// create import model object
		$this->view->import = new \Members\Models\Import($id);

		if (!$this->view->import->exists())
		{
			return $this->cancelTask();
		}

		// Set any errors
		if ($this->getErrors())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}
		}

		// Output the HTML
		$this->view
			->setLayout('run')
			->display();
	}

	/**
	 * Actually Run Import
	 * 
	 * @return  string  JSON encoded records that just got inserted or would be
	 */
	public function doRunTask()
	{
		// check token
		JSession::checkToken() or die('Invalid Token');

		// start of import
		$start = microtime(true);

		// get request vars
		$id = JRequest::getInt('id', 0);

		// test mode
		$dryRun = JRequest::getBool('dryrun', 0);

		// create import model object
		$import = new \Members\Models\Import($id);

		// make import importer
		$importImporter = \Hubzero\Content\Importer::getInstance();

		// run process task on importer
		// passed the import model, array or callbacks, and test mode flag
		$resourceData = $importImporter->process($import, array(
			'postparse'   => $this->_hooks('postparse',   $import),
			'postmap'     => $this->_hooks('postmap',     $import),
			'postconvert' => $this->_hooks('postconvert', $import)
		), $dryRun);

		// calculate execution time
		$end  = microtime(true);
		$time = round($end - $start, 3);

		// outputted with html entities to allow browser json formatter
		if (JRequest::getInt('format', 0) == 1)
		{
			echo htmlentities(json_encode(array(
				'import'  => 'success',
				'time'    => $time,
				'records' => $resourceData
			)));
			exit();
		}

		// return results to user
		echo json_encode(array(
			'import'  => 'success',
			'time'    => $time,
			'records' => $resourceData
		));
		exit();
	}

	/**
	 * Get progress of import task
	 * 
	 * @return  string  JSON encoded total and position
	 */
	public function progressTask()
	{
		// get request vars
		$id = JRequest::getInt('id', 0);

		// create import model object
		$import = new \Members\Models\Import($id);

		// get the lastest run
		$run = $import->runs('current');

		// build array of data to return
		$data = array(
			'processed' => $run->get('processed'),
			'total'     => $run->get('count')
		);

		// return progress update
		echo json_encode($data);
		exit();
	}

	/**
	 * Return Hook for Post Parsing or Post Convert
	 *
	 * @param   string  $event   Hook we want
	 * @param   object  $import  Import Model
	 * @return  object  Closure
	 */
	private function _hooks($event, $import)
	{
		// Array to hold callbacks
		$callbacks = array();

		// Get hooks on import
		$hooks = json_decode($import->get('hooks'));

		// Make sure we have this type of hook
		if (!isset($hooks->$event))
		{
			return $callbacks;
		}

		// Loop through each hook
		foreach ($hooks->$event as $hook)
		{
			// Load hook object
			$importHook = new \Hubzero\Content\Import\Model\Hook($hook);

			// Make sure we have an object
			if (!$importHook)
			{
				continue;
			}

			// Build path to script
			$hookFile = $importHook->fileSpacePath() . DS . $importHook->get('file');

			// Make sure we have a file
			if (!is_file($hookFile))
			{
				continue;
			}

			// Add callback
			$callbacks[] = function($data, $dryRun) use ($hookFile)
			{
				return include $hookFile;
			};
		}

		// Return closures as callbacks
		return $callbacks;
	}

	/**
	 * Method to create import filespace if needed
	 *
	 * @param   object  $import  \Hubzero\Content\Import\Model\Import
	 * @return  boolean
	 */
	private function _createImportFilespace(\Hubzero\Content\Import\Model\Import $import)
	{
		// upload path
		$uploadPath = $import->fileSpacePath();

		// if we dont have a filespace, create it
		if (!is_dir($uploadPath))
		{
			JFolder::create($uploadPath, 0775);
		}

		// all set
		return true;
	}
}