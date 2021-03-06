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
defined('_JEXEC') or die( 'Restricted access' );

include_once(PATH_CORE . DS . 'components' . DS . 'com_publications'
	. DS . 'models' . DS . 'publication.php');

/**
 * Project publications
 */
class plgProjectsPublications extends \Hubzero\Plugin\Plugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 */
	protected $_autoloadLanguage = true;

	/**
	 * Store redirect URL
	 *
	 * @var	   string
	 */
	protected $_referer = NULL;

	/**
	 * Store output message
	 *
	 * @var	   array
	 */
	protected $_message = NULL;

	/**
	 * Component name
	 *
	 * @var  string
	 */
	protected $_option = 'com_projects';

	/**
	 * Store internal message
	 *
	 * @var	   array
	 */
	protected $_msg = NULL;

	/**
	 * Event call to determine if this plugin should return data
	 *
	 * @return     array   Plugin name and title
	 */
	public function &onProjectAreas($alias = NULL)
	{
		$area = array();

		// Check if plugin is restricted to certain projects
		$projects = $this->params->get('restricted') ? \Components\Projects\Helpers\Html::getParamArray($this->params->get('restricted')) : array();

		if (!empty($projects) && $alias)
		{
			if (!in_array($alias, $projects))
			{
				return $area;
			}
		}

		$area = array(
			'name'    => 'publications',
			'title'   => Lang::txt('COM_PROJECTS_TAB_PUBLICATIONS'),
			'submenu' => NULL,
			'show'    => true
		);

		return $area;
	}

	/**
	 * Event call to return count of items
	 *
	 * @param      object  $model 		Project
	 * @return     array   integer
	 */
	public function &onProjectCount( $model )
	{
		// Get this area details
		$this->_area = $this->onProjectAreas();

		$counts['publications'] = 0;

		if (empty($this->_area) || !$model->exists())
		{
			return $counts;
		}
		else
		{
			$database = JFactory::getDBO();

			// Instantiate project publication
			$objP = new \Components\Publications\Tables\Publication( $database );

			$filters = array();
			$filters['project']  		= $model->get('id');
			$filters['ignore_access']   = 1;
			$filters['dev']   	 		= 1;

			$counts['publications'] = count($objP->getCount($filters));
		}

		return $counts;
	}

	/**
	 * Event call to return data for a specific project
	 *
	 * @param      object  $model           Project model
	 * @param      string  $action			Plugin task
	 * @param      string  $areas  			Plugins to return data
	 * @return     array   Return array of html
	 */
	public function onProject ( $model, $action = '', $areas = null )
	{
		$returnhtml = true;

		$arr = array(
			'html'      =>'',
			'metadata'  =>'',
			'message'   =>'',
			'error'     =>''
		);

		// Get this area details
		$this->_area = $this->onProjectAreas();

		// Check if our area is in the array of areas we want to return results for
		if (is_array( $areas ))
		{
			if (empty($this->_area) || !in_array($this->_area['name'], $areas))
			{
				return;
			}
		}

		// Check authorization
		if ($model->exists() && !$model->access('member'))
		{
			return $arr;
		}

		// Model
		$this->model = $model;

		// Get task
		$this->_task = Request::getVar('action','');
		$this->_pid  = Request::getInt('pid', 0);
		if (!$this->_task)
		{
			$this->_task = $this->_pid ? 'publication' : $action;
		}

		$this->_uid       = User::get('id');
		$this->_database  = JFactory::getDBO();
		$this->_config    = $model->config();
		$this->_pubconfig = Component::params( 'com_publications' );

		// Areas that can be updated after publication
		$this->_updateAllowed = \Components\Projects\Helpers\Html::getParamArray(
			$this->params->get('updatable_areas', '' ));

		// Common extensions (for gallery)
		$this->_image_ext = \Components\Projects\Helpers\Html::getParamArray(
			$this->params->get('image_types', 'bmp, jpeg, jpg, png' ));
		$this->_video_ext = \Components\Projects\Helpers\Html::getParamArray(
			$this->params->get('video_types', 'avi, mpeg, mov, wmv' ));

		// Use new curation flow?
		$this->useBlocks  = $this->_pubconfig->get('curation', 0);

		$this->_project 	= $model->project();
		$this->_action 		= $action;

		// Contribute process outside of projects
		if (!is_object($this->_project) or !$this->_project->id)
		{
			$this->_project = new \Components\Projects\Tables\Project( $this->_database );
			$this->_project->provisioned = 1;

			$ajax_tasks  = array('showoptions', 'save', 'showitem');
			$this->_task = $action == 'start' ? 'start' : 'contribute';
			if ($action == 'publication')
			{
				$this->_task = 'publication';
			}
			elseif (in_array($action, $ajax_tasks))
			{
				$this->_task = $action;
			}
		}
		elseif ($this->_project->provisioned == 1 && !$this->_pid)
		{
			// No browsing within provisioned project
			$this->_task = $action == 'browse' ? 'contribute' : $action;
		}

		\Hubzero\Document\Assets::addPluginStylesheet('projects', 'publications');
		\Hubzero\Document\Assets::addPluginStylesheet('projects', 'publications','css/curation');

		// Get JS & CSS
		if ($this->useBlocks)
		{
			\Hubzero\Document\Assets::addPluginScript('projects', 'publications', 'js/curation');
		}
		else
		{
			\Hubzero\Document\Assets::addPluginScript('projects', 'publications');
		}

		// Get types helper
		$this->_pubTypeHelper = new \Components\Publications\Models\Types($this->_database, $this->_project);

		// Actions
		switch ($this->_task)
		{
			case 'browse':
			default:
				$arr['html'] = $this->browse();
				break;

			/* NEW draft flow */
			case 'start':
				$arr['html'] = $this->useBlocks ? $this->startDraft() : $this->start();
				break;

			case 'edit':
			case 'publication':
				$arr['html'] = $this->useBlocks ? $this->editDraft() : $this->edit();
				break;

			case 'newversion':
			case 'savenew':
				$arr['html'] = $this->_newVersion();
				break;

			// Review
			case 'review':
				$arr['html'] = $this->useBlocks ? $this->editDraft() : $this->review();
				break;

			case 'checkstatus':
				$arr['html'] = $this->checkStatus();
				break;

			case 'select':
				$arr['html'] = $this->select();
				break;

			case 'continue':
				$arr['html'] = $this->editDraft();
				break;

			case 'saveparam':
				$arr['html'] = $this->saveParam();
				break;

			// Change publication state
			case 'publish':
			case 'republish':
			case 'archive':
			case 'revert':
			case 'post':
				$arr['html'] = $this->publishDraft();
				break;

			case 'apply':
			case 'save':
			case 'rewind':
			case 'reorder':
			case 'deleteitem':
			case 'additem':

				$arr['html'] = $this->useBlocks ? $this->saveDraft() : $this->save();
				break;

			// Individual items editing
			case 'edititem':
				$arr['html'] = $this->useBlocks ? $this->editItem() : $this->_editContent();
				break;
			case 'saveitem':
				$arr['html'] = $this->useBlocks ? $this->saveDraft() : $this->_saveContent();
				break;
			case 'editauthor':
				$arr['html'] = $this->useBlocks ? $this->editItem() : $this->_editAuthor();
				break;

			case 'dispute':
			case 'skip':
			case 'undispute':
				$arr['html'] = $this->saveDraft();
				break;

			/*------------------*/
			case 'new':
				$arr['html'] = $this->add();
				break;

			case 'suggest_license':
			case 'save_license':
				$arr['html'] = $this->_suggestLicense();
				break;

			// Show all publication versions
			case 'versions':
				$arr['html'] = $this->versions();
				break;

			// Tags
			case 'loadtags':
				$arr['html'] = $this->suggestTags();
				break;

			// Unpublish/delete
			case 'cancel':
				$arr['html'] = $this->cancelDraft();
				break;

			// Contribute process outside of projects
			case 'contribute':
				$arr['html'] = $this->contribute();
				break;

			// Show stats
			case 'stats':
				$arr['html'] = $this->_stats();
				break;

			case 'diskspace':
				$arr['html'] = $this->pubDiskSpace($this->_option, $this->_project, $this->_task, $this->_config);
				break;

			// Handlers
			case 'handler':
				$arr['html'] = $this->handler();
				break;

			/* OLD draft flow */
			case 'showoptions':
				$arr['html'] = $this->_showOptions();
				break;
			case 'showitem':
				$arr['html'] = $this->_loadContentItem();
				break;
			case 'showauthor':
				$arr['html'] = $this->_showAuthor();
				break;
			case 'saveauthor':
				$arr['html'] = $this->_saveAuthor();
				break;
			case 'showaudience':
				$arr['html'] = $this->_showAudience();
				break;
			case 'showimage':
				$arr['html'] = $this->_loadScreenshot();
				break;
			case 'editimage':
				$arr['html'] = $this->_editScreenshot();
				break;
			case 'saveimage':
				$arr['html'] = $this->_saveScreenshot();
				break;
		}

		$arr['referer'] = $this->_referer;
		$arr['msg']     = $this->_message;

		// Return data
		return $arr;
	}

	/**
	 * Handler manager
	 *
	 * @return     string
	 */
	public function handler()
	{
		// Incoming
		$props  = Request::getVar( 'p', '' );
		$ajax   = Request::getInt( 'ajax', 0 );
		$pid    = Request::getInt( 'pid', 0 );
		$vid    = Request::getInt( 'vid', 0 );
		$handler= trim(Request::getVar( 'h', '' ));
		$action = trim(Request::getVar( 'do', '' ));

		// Parse props for curation
		$parts   = explode('-', $props);
		$block   = (isset($parts[0])) ? $parts[0] : 'content';
		$step    = (isset($parts[1]) && is_numeric($parts[1]) && $parts[1] > 0) ? $parts[1] : 1;
		$element = (isset($parts[2]) && is_numeric($parts[2]) && $parts[2] > 0) ? $parts[2] : 0;

		// Output HTML
		$view = new \Hubzero\Component\View(array(
			'base_path' => PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'site',
			'name'   => 'handlers',
			'layout' => 'editor',
		));

		// Load classes
		$objP  			= new \Components\Publications\Tables\Publication( $this->_database );
		$view->version 	= new \Components\Publications\Tables\Version( $this->_database );

		// Load publication version
		$view->version->load($vid);
		if (!$view->version->id)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_SELECTOR_ERROR_NO_PUBID'));
		}

		// Get publication
		$view->publication = $objP->getPublication($view->version->publication_id,
			$view->version->version_number, $this->_project->id);

		if (!$view->publication)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_SELECTOR_ERROR_NO_PUBID'));
		}

		// On error
		if ($this->getError())
		{
			// Output error
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'	=>'projects',
					'element'	=>'files',
					'name'		=>'error'
				)
			);

			$view->title  = '';
			$view->option = $this->_option;
			$view->setError( $this->getError() );
			return $view->loadTemplate();
		}

		// Load master type
		$mt   							= new \Components\Publications\Tables\MasterType( $this->_database );
		$view->publication->_type   	= $mt->getType($view->publication->base);
		$view->publication->_project	= $this->_project;

		// Get curation model
		$view->publication->_curationModel = new \Components\Publications\Models\Curation($view->publication->_type->curation);

		// Set block
		if (!$view->publication->_curationModel->setBlock( $block, $step ))
		{
			$view->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_SELECTOR_ERROR_LOADING_CONTENT') );
		}

		// Set pub assoc and load curation
		$view->publication->_curationModel->setPubAssoc($view->publication);

		// Load handler
		$modelHandler = new \Components\Publications\Models\Handlers($this->_database);
		$view->handler = $modelHandler->ini($handler);
		if (!$view->handler)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_ERROR_LOADING_HANDLER') );
		}
		else
		{
			// Perform request
			if ($action)
			{
				$modelHandler->update($view->handler, $view->publication, $element, $action);
			}

			// Load editor
			$view->editor = $modelHandler->loadEditor($view->handler, $view->publication, $element);
		}

		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->uid 			= $this->_uid;
		$view->ajax			= $ajax;
		$view->task			= $this->_task;
		$view->element		= $element;
		$view->block		= $block;
		$view->step 		= $step;
		$view->props		= $props;
		$view->config		= $this->_pubconfig;

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * View for selecting items (currently used for license selection)
	 *
	 * @return     string
	 */
	public function select()
	{
		// Incoming
		$props  = Request::getVar( 'p', '' );
		$ajax   = Request::getInt( 'ajax', 0 );
		$pid    = Request::getInt( 'pid', 0 );
		$vid    = Request::getInt( 'vid', 0 );
		$filter = urldecode(Request::getVar( 'filter', '' ));

		// Parse props for curation
		$parts   = explode('-', $props);
		$block   = (isset($parts[0])) ? $parts[0] : 'content';
		$step    = (isset($parts[1]) && is_numeric($parts[1]) && $parts[1] > 0) ? $parts[1] : 1;
		$element = (isset($parts[2]) && is_numeric($parts[2]) && $parts[2] > 0) ? $parts[2] : 0;

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'	=>'projects',
				'element'	=>'publications',
				'name'		=>'selector'
			)
		);

		// Load classes
		$objP  			= new \Components\Publications\Tables\Publication( $this->_database );
		$view->version 	= new \Components\Publications\Tables\Version( $this->_database );

		// Load publication version
		$view->version->load($vid);
		if (!$view->version->id)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_SELECTOR_ERROR_NO_PUBID'));
		}

		// Get publication
		$view->publication = $objP->getPublication($view->version->publication_id,
			$view->version->version_number, $this->_project->id);

		if (!$view->publication)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_SELECTOR_ERROR_NO_PUBID'));
		}

		// On error
		if ($this->getError())
		{
			// Output error
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'	=>'projects',
					'element'	=>'files',
					'name'		=>'error'
				)
			);

			$view->title  = '';
			$view->option = $this->_option;
			$view->setError( $this->getError() );
			return $view->loadTemplate();
		}

		\Hubzero\Document\Assets::addPluginStylesheet('projects', 'publications','/css/selector');

		// Load master type
		$mt   							= new \Components\Publications\Tables\MasterType( $this->_database );
		$view->publication->_type   	= $mt->getType($view->publication->base);
		$view->publication->_project	= $this->_project;

		// Get curation model
		$view->publication->_curationModel = new \Components\Publications\Models\Curation($view->publication->_type->curation);

		// Set block
		if (!$view->publication->_curationModel->setBlock( $block, $step ))
		{
			// Output error
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'	=>'projects',
					'element'	=>'files',
					'name'		=>'error'
				)
			);

			$view->title  = '';
			$view->option = $this->_option;
			$view->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_SELECTOR_ERROR_LOADING_CONTENT') );
			return $view->loadTemplate();
		}

		// Set pub assoc and load curation
		$view->publication->_curationModel->setPubAssoc($view->publication);

		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->ajax			= $ajax;
		$view->task			= $this->_task;
		$view->element		= $element;
		$view->block		= $block;
		$view->step 		= $step;
		$view->props		= $props;
		$view->filter		= $filter;
		$view->pubconfig	= $this->_pubconfig;

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();

	}

	/**
	 * Save param in version table (AJAX)
	 *
	 * @return     string
	 */
	public function saveParam()
	{
		// Incoming
		$pid  	= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$vid  	= Request::getInt('vid', 0);
		$param  = Request::getVar('param', '');
		$value  = urldecode(Request::getVar('value', ''));
		$success= 0;

		// Clean up incoming
		$param  = \Hubzero\Utility\Sanitize::paranoid($param, array('-', '_'));
		$value  = \Hubzero\Utility\Sanitize::clean($value);
		$result = $value;

		if (!$vid || !$param)
		{
			$this->setError(Lang::txt('Missing required input'));
		}

		$row = new \Components\Publications\Tables\Version( $this->_database );
		if (!$row->load($vid))
		{
			$this->setError(Lang::txt('Failed to load version'));
		}
		else
		{
			if ($row->saveParam($vid, $param, $value))
			{
				$success = 1;
			}
		}

		return json_encode(array('success' => $success, 'error' => $this->getError(), 'result' => $result));
	}

	/**
	 * Check completion status for a section via AJAX call
	 *
	 * @return     string
	 */
	public function checkStatus()
	{
		// Incoming
		$pid  		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$version 	= Request::getVar( 'version', 'default' );
		$ajax 		= Request::getInt('ajax', 0);
		$block  	= Request::getVar( 'section', '' );
		$sequence  	= Request::getInt( 'step', 0 );
		$element  	= Request::getInt( 'element', 0 );
		$props  	= Request::getVar( 'p', '' );
		$parts   	= explode('-', $props);

		// Parse props for curation
		if (!$block || !$sequence)
		{
			$block   	 = (isset($parts[0])) ? $parts[0] : 'content';
			$sequence    = (isset($parts[1]) && is_numeric($parts[1]) && $parts[1] > 0) ? $parts[1] : 1;
			$element 	 = (isset($parts[2]) && is_numeric($parts[2]) && $parts[2] > 0) ? $parts[2] : 1;
		}

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$row = new \Components\Publications\Tables\Version( $this->_database );

		// Include models
		include_once(PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'models' . DS . 'status.php');

		$status = new \Components\Publications\Models\Status();

		// If publication not found, raise error
		$pub = $objP->getPublication($pid, $version, $this->_project->id);
		if (!$pub)
		{
			return $status->status;
		}

		// Get manifest
		$mt   = new \Components\Publications\Tables\MasterType( $this->_database );
		$pub->_type = $mt->getType($pub->base);

		// Get curation model
		$pub->_curationModel = new \Components\Publications\Models\Curation($pub->_type->curation);

		if ($element && $block)
		{
			// Get block element status
			$status = $pub->_curationModel->getElementStatus($block, $element, $pub, $sequence);
		}
		elseif ($block)
		{
			// Getting block status
			$status = $pub->_curationModel->getStatus($block, $pub, $sequence);
		}

		return json_encode($status);
	}

	/**
	 * Save publication draft
	 *
	 * @return     string
	 */
	public function saveDraft()
	{
		// Incoming
		$pid 		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$version 	= Request::getVar( 'version', '' );
		$block  	= Request::getVar( 'section', '' );
		$sequence  	= Request::getInt( 'step', 0 );
		$element  	= Request::getInt( 'element', 0 );
		$next  		= Request::getInt( 'next', 0 );
		$json  		= Request::getInt( 'json', 0 );
		$new		= false;

		$props  	= Request::getVar( 'p', '' );
		$parts   	= explode('-', $props);

		// When saving individual attachment
		$back 	= Request::getVar( 'backUrl', Request::getVar('HTTP_REFERER', NULL, 'server') );

		// Parse props for curation
		if ($this->_task == 'saveitem'
			|| $this->_task == 'deleteitem'
			|| (!$block || !$sequence))
		{
			$block   	 = (isset($parts[0])) ? $parts[0] : 'content';
			$sequence    = (isset($parts[1]) && is_numeric($parts[1]) && $parts[1] > 0) ? $parts[1] : 1;
			$element 	 = (isset($parts[2]) && is_numeric($parts[2]) && $parts[2] > 0) ? $parts[2] : 0;
		}

		// Are we in draft flow?
		$move = Request::getVar( 'move', '' );

		// Load publication & version classes
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$objV = new \Components\Publications\Tables\Version( $this->_database );
		$mt   = new \Components\Publications\Tables\MasterType( $this->_database );

		// Check that version exists
		$version = $objV->checkVersion($pid, $version) ? $version : 'dev';

		// Instantiate project publication
		$pub 	 		= $objP->getPublication($pid, $version, $this->_project->id);

		// Start url
		$route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias='
						. $this->_project->alias . '&active=publications';

		// Error loading publication record
		if ((!$pub || !$pub->id) && $new == false)
		{
			$this->_referer = Route::url($route);
			$this->_message = array(
				'message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'),
				'type' => 'error');
			return;
		}

		$pub->version 	= $version;
		$pub->_project 	= $this->_project;
		$pub->_type    	= $mt->getType($pub->base);

		// Get type info
		$pub->_category = new \Components\Publications\Tables\Category( $this->_database );
		$pub->_category->load($pub->category);
		$pub->_category->_params = new JParameter( $pub->_category->params );

		// Get manifest from either version record (published) or master type
		$manifest   = $pub->curation
					? $pub->curation
					: $pub->_type->curation;

		// Get curation model
		$pub->_curationModel = new \Components\Publications\Models\Curation($manifest);

		// Make sure block exists, else redirect to status
		if (!$pub->_curationModel->setBlock( $block, $sequence ))
		{
			$block = 'status';
		}

		// Set pub assoc and load curation
		$pub->_curationModel->setPubAssoc($pub);

		// Save incoming
		switch ($this->_task)
		{
			case 'additem':
				$pub->_curationModel->addItem($this->_uid, $element);
				break;

			case 'saveitem':
				$pub->_curationModel->saveItem($this->_uid, $element);
				break;

			case 'deleteitem':
				$pub->_curationModel->deleteItem($this->_uid, $element);
				break;

			case 'reorder':
				$pub->_curationModel->reorder($this->_uid, $element);
				$json = 1; // return result as json
				break;

			case 'dispute':
				$pub->_curationModel->dispute($this->_uid, $element);
				break;

			case 'skip':
				$pub->_curationModel->skip($this->_uid, $element);
				break;

			case 'undispute':
				$pub->_curationModel->undispute($this->_uid, $element);
				break;

			default:
				if ($this->_task != 'rewind')
				{
					$pub->_curationModel->saveBlock($this->_uid, $element);
				}
				break;
		}

		// Save new version label
		if ($block == 'status')
		{
			$pub->_curationModel->saveVersionLabel($this->_uid);
		}

		// Pick up error messages
		if ($pub->_curationModel->getError())
		{
			$this->setError($pub->_curationModel->getError());
		}

		// Pick up success message
		$this->_msg = $pub->_curationModel->get('_message')
			? $pub->_curationModel->get('_message')
			: Lang::txt(ucfirst($block) . ' information successfully saved');

		// Record action, notify team
		$this->onAfterSave( $pub );

		// Report only status action
		if ($json)
		{
			return json_encode(array('success' => 1, 'error' => $this->getError(), 'message' => $this->_msg));
		}

		// Go back to panel after changes to individual attachment
		if ($this->_task == 'saveitem' || $this->_task == 'deleteitem')
		{
			$this->_referer = $back;
			return;
		}

		// Get sequence
		$sequence = $pub->_curationModel->_blockorder;
		$total	  = $pub->_curationModel->_blockcount;

		// Get next element
		if ($next)
		{
			$next = $pub->_curationModel->getNextElement($block, $sequence, $element);
		}

		// What's next?
		$nextnum 	 = $pub->_curationModel->getNextBlock($block, $sequence);
		$nextsection = isset($pub->_curationModel->_blocks->$nextnum)
					 ? $pub->_curationModel->_blocks->$nextnum->name : 'status';

		// Get previous section
		$prevnum 	 = $pub->_curationModel->getPreviousBlock($block, $sequence);
		$prevsection = isset($pub->_curationModel->_blocks->$prevnum)
					 ? $pub->_curationModel->_blocks->$prevnum->name : 'status';

		// Build route
		$route .= a . 'pid=' . $pub->id;
		$route .= $move ? a . 'move=continue' : '';

		// Append version label
		$route .= $version != 'default' ? a . 'version=' . $version : '';

		// Determine which panel to go to
		if ($this->_task == 'apply' || !$move)
		{
			// Stay where you were
			$route .= a . 'section=' . $block . '&step=' . $sequence;

			if ($next)
			{
				if ($next == $element)
				{
					// Move to next block
					$route .= a . 'section=' . $nextsection;
					$route .= $nextnum ? a . 'step=' . $nextnum : '';
				}
				else
				{
					$route .= a . 'el=' . $next . '#element' . $next;
				}
			}
			elseif ($element)
			{
				$route .= a . 'el=' . $element . '#element' . $element;
			}
		}
		elseif ($this->_task == 'rewind')
		{
			// Go back one step
			$route .= a . 'section=' . $prevsection;
			$route .= $prevnum ? a . 'step=' . $prevnum : '';
		}
		else
		{
			// Move next
			$route .= a . 'section=' . $nextsection;
			$route .= $nextnum ? a . 'step=' . $nextnum : '';

			if ($next)
			{
				$route .= a . 'el=' . $next . '#element' . $next;
			}
		}

		// Redirect
		$this->_referer = htmlspecialchars_decode(Route::url($route));
		return;
	}

	/**
	 * Actions after publication draft is saved
	 *
	 * @return     string
	 */
	public function onAfterSave( $pub, $versionNumber = 1 )
	{
		// No afterSave actions when backing one step
		if ($this->_task == 'rewind')
		{
			return false;
		}

		// Pass success or error message
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
		}
		elseif (isset($this->_msg) && $this->_msg)
		{
			$this->_message = array('message' => $this->_msg, 'type' => 'success');
		}

		// Record activity
		if ($this->get('_activity'))
		{
			$pubTitle = \Hubzero\Utility\String::truncate($pub->title, 100);
			$objAA = new \Components\Projects\Tables\Activity ( $this->_database );
			$aid = $objAA->recordActivity( $this->_project->id, $this->_uid,
				   $this->get('_activity'), $pub->id, $pubTitle,
				   Route::url('index.php?option=' . $this->_option . a .
				   'alias=' . $this->_project->alias . '&active=publications' . a .
				   'pid=' . $pub->id) . '/?version=' . $versionNumber, 'publication', 1 );
		}

	}

	/**
	 * Actions after publication draft is started
	 *
	 * @return     string
	 */
	public function onAfterCreate($row)
	{
		// Record activity
		if (!$this->_project->provisioned && !$this->getError())
		{
			$objAA = new \Components\Projects\Tables\Activity ( $this->_database );
			$aid   = $objAA->recordActivity( $this->_project->id, $this->_uid,
				   Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_STARTED_NEW_PUB')
					.' (id ' . $row->publication_id . ')', $row->publication_id, 'publication',
				   Route::url('index.php?option=' . $this->_option . a .
				   'alias=' . $this->_project->alias . '&active=publications' . a .
				   'pid=' . $row->publication_id), 'publication', 1 );
		}

		// Notify project managers
		$objO = new \Components\Projects\Tables\Owner($this->_database);
		$managers = $objO->getIds($this->_project->id, 1, 1);
		if (!empty($managers) && !$this->_project->provisioned)
		{
			$profile = \Hubzero\User\Profile::getInstance($this->_uid);
			$juri = JURI::getInstance();

			$sef = Route::url('index.php?option=' . $this->_option . a
				. 'alias=' . $this->_project->alias . '&active=publications'
				. '&pid=' . $row->publication_id);
			$sef = trim($sef, DS);

			\Components\Projects\Helpers\Html::sendHUBMessage(
				'com_projects',
				$this->_config,
				$this->_project,
				$managers,
				Lang::txt('COM_PROJECTS_EMAIL_MANAGERS_NEW_PUB_STARTED'),
				'projects_admin_notice',
				'publication',
				$profile->get('name') . ' '
					. Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_STARTED_NEW_PUB')
					.' (id ' . $row->publication_id . ')' . ' - ' . $juri->base()
					. $sef . '/?version=' . $row->version_number
			);
		}
	}

	/**
	 * Start a new publication draft
	 *
	 * @return     string
	 */
	public function startDraft()
	{
		$mt = new \Components\Publications\Tables\MasterType( $this->_database );
		$choices = $mt->getTypes('*', 1, 0, 'ordering', $this->_config);

		// Contribute process outside of projects
		if (!is_object($this->_project) or !$this->_project->id)
		{
			$this->_project = new \Components\Projects\Tables\Project( $this->_database );
			$this->_project->provisioned = 1;
		}

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'	=>'projects',
				'element'	=>'publications',
				'name'		=>'draft',
				'layout'	=>'start'
			)
		);

		// Build pub url
		$view->route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route);

		// Do we have a choice?
		if (count($choices) <= 1 )
		{
			$this->_referer = Route::url($view->route . '&action=edit');
			return;
		}

		// Append breadcrumbs
		Pathway::append(
			stripslashes(Lang::txt('PLG_PROJECTS_PUBLICATIONS_START_PUBLICATION')),
			$view->url . '?action=start'
		);

		// Output HTML
		$view->params 		= new JParameter( $this->_project->params );
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->config 		= $this->_config;
		$view->choices 		= $choices;
		$view->title		= $this->_area['title'];
		$view->useBlocks    = $this->useBlocks;

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Provision a new publication draft
	 *
	 * @return     object
	 */
	public function createDraft()
	{
		// Incoming
		$base = Request::getVar( 'base', 'files' );

		// Load publication & version classes
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$mt   = new \Components\Publications\Tables\MasterType( $this->_database );

		// Determine publication master type
		$choices  	= $mt->getTypes('alias', 1);
		if (count($choices) == 1)
		{
			$base = $choices[0];
		}

		$mastertype = in_array($base, $choices) ? $base : 'files';

		$now = JFactory::getDate()->toSql();

		// Need to provision a project
		if (!is_object($this->_project) or !$this->_project->id)
		{
			$this->_project 					= new \Components\Projects\Tables\Project( $this->_database );
			$this->_project->provisioned 		= 1;
			$this->_project->alias 	 			= 'pub-' . strtolower(\Components\Projects\Helpers\Html::generateCode(10, 10, 0, 1, 1));
			$this->_project->title 	 			= $this->_project->alias;
			$this->_project->type 	 			= $base == 'tools' ? 2 : 3; // content publication
			$this->_project->state   			= 1;
			$this->_project->created 			= JFactory::getDate()->toSql();
			$this->_project->created_by_user 	= $this->_uid;
			$this->_project->owned_by_user 		= $this->_uid;
			$this->_project->setup_stage 		= 3;

			// Get project type params
			require_once( PATH_CORE. DS . 'components' . DS
				. 'com_projects' . DS . 'tables' . DS . 'type.php');
			$objT = new \Components\Projects\Tables\Type( $this->_database );
			$this->_project->params = $objT->getParams ($this->_project->type);

			// Save changes
			if (!$this->_project->store())
			{
				$this->setError( $this->_project->getError() );
				return false;
			}

			if (!$this->_project->id)
			{
				$this->_project->checkin();
			}
		}

		// Determine publication type
		$objT = new \Components\Publications\Tables\Category( $this->_database );

		// Get type params
		$mType = $mt->getType($mastertype);

		// Make sure we got type info
		if (!$mType)
		{
			throw new Exception( 'Error loading publication type' );
			return false;
		}

		// Get curation model for the type
		$curationModel = new \Components\Publications\Models\Curation($mType->curation);

		// Get default category from manifest
		$cat = isset($curationModel->_manifest->params->default_category)
				? $curationModel->_manifest->params->default_category : 1;
		if (!$objT->load($cat))
		{
			$cat = 1;
		}

		// Get default title from manifest
		$title = isset($curationModel->_manifest->params->default_title)
					? $curationModel->_manifest->params->default_title : 'Untitled Draft';

		// Make a new publication entry
		$objP->master_type 		= $mType->id;
		$objP->category 		= $cat;
		$objP->project_id 		= $this->_project->id;
		$objP->created_by 		= $this->_uid;
		$objP->created 			= $now;
		$objP->access 			= 0;
		if (!$objP->store())
		{
			throw new Exception( $objP->getError() );
			return false;
		}
		if (!$objP->id)
		{
			$objP->checkin();
		}
		$pid 		= $objP->id;
		$this->_pid = $pid;

		// Initizalize Git repo and transfer files from member dir
		if ($this->_project->provisioned == 1)
		{
			if (!$this->_prepDir())
			{
				// Roll back
				$this->_project->delete();
				$objP->delete();

				throw new Exception( Lang::txt('PLG_PROJECTS_PUBLICATIONS_ERROR_FAILED_INI_GIT_REPO') );
				return false;
			}
			else
			{
				// Add creator as project owner
				$objO = new \Components\Projects\Tables\Owner( $this->_database );
				if (!$objO->saveOwners ( $this->_project->id,
					$this->_uid, $this->_uid,
					0, 1, 1, 1 ))
				{
					// File auto ticket to report this - TBD
					//*******
					$this->setError( Lang::txt('COM_PROJECTS_ERROR_SAVING_AUTHORS').': '.$objO->getError() );
					return false;
				}
			}
		}

		// Make a new dev version entry
		$row 					= new \Components\Publications\Tables\Version( $this->_database );
		$row->publication_id 	= $pid;
		$row->title 			= $row->getDefaultTitle($this->_project->id, $title);
		$row->state 			= 3; // dev
		$row->main 				= 1;
		$row->created_by 		= $this->_uid;
		$row->created 			= $now;
		$row->version_number 	= 1;
		$row->license_type 		= 0;
		$row->access 			= 0;
		$row->secret 			= strtolower(\Components\Projects\Helpers\Html::generateCode(10, 10, 0, 1, 1));

		if (!$row->store())
		{
			// Roll back
			$objP->delete();

			throw new Exception( $row->getError(), 500 );
			return false;
		}
		if (!$row->id)
		{
			$row->checkin();
		}

		// Record action, notify team
		$this->onAfterCreate($row);

		// Return publication object
		return $objP->getPublication($pid, 'dev', $this->_project->id);
	}

	/**
	 * View/Edit publication draft
	 *
	 * @return     string
	 */
	public function editDraft()
	{
		// Incoming
		$pid 		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$version 	= Request::getVar( 'version', '' );
		$block  	= Request::getVar( 'section', 'status' );
		$sequence  	= Request::getInt( 'step', 0 );

		// Load publication & version classes
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$objV = new \Components\Publications\Tables\Version( $this->_database );
		$mt   = new \Components\Publications\Tables\MasterType( $this->_database );

		// Check that version exists
		$version = $objV->checkVersion($pid, $version) ? $version : 'default';

		// Provision draft
		if (!$pid)
		{
			$pub = $this->createDraft();

			// Start url
			$route = $this->_project->provisioned
						? 'index.php?option=com_publications&task=submit'
						: 'index.php?option=com_projects&alias='
							. $this->_project->alias . '&active=publications';

			$mType 	= $mt->getType($pub->base);

			// Get curation model
			$curationModel = new \Components\Publications\Models\Curation($mType->curation);
			$sequence 	   = $curationModel->getFirstBlock();
			$firstBlock    = $curationModel->_blocks->$sequence->name;

			// Redirect to first block
			$this->_referer = Route::url($route . '&pid=' . $pub->id )
				. '?move=continue&step=' . $sequence . '&section=' . $firstBlock;
			return;
		}
		else
		{
			// Instantiate project publication
			$pub = $objP->getPublication($pid, $version, $this->_project->id);
		}

		// Start url
		$route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias='
						. $this->_project->alias . '&active=publications';

		// If publication not found, raise error
		if (($pid && !$pub) || $pub->state == 2)
		{
			$this->_referer = Route::url($route);
			$this->_message = array(
				'message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'),
				'type' => 'error');
			return;
		}

		$pub->_project 	= $this->_project;
		$pub->_type    	= $mt->getType($pub->base);

		// Main version
		if ($pub->main == 1)
		{
			$version = 'default';
		}
		// We have a draft
		if ($pub->state == 3)
		{
			$version = 'dev';
		}

		$pub->version 	= $version;

		// Get type info
		$pub->_category = new \Components\Publications\Tables\Category( $this->_database );
		$pub->_category->load($pub->category);
		$pub->_category->_params = new JParameter( $pub->_category->params );

		// Get authors
		$pAuthors 			= new \Components\Publications\Tables\Author( $this->_database );
		$pub->_authors 		= $pAuthors->getAuthors($pub->version_id);
		$pub->_submitter 	= $pAuthors->getSubmitter($pub->version_id, $pub->created_by);

		// Get attachments
		$pContent = new \Components\Publications\Tables\Attachment( $this->_database );
		$pub->_attachments = $pContent->sortAttachments ( $pub->version_id );

		// Get manifest from either version record (published) or master type
		$manifest   = $pub->curation
					? $pub->curation
					: $pub->_type->curation;

		// Get curation model
		$pub->_curationModel = new \Components\Publications\Models\Curation($manifest);

		// Set pub assoc and load curation
		$pub->_curationModel->setPubAssoc($pub);

		// For publications created in a non-curated flow - convert
		$pub->_curationModel->convertToCuration($pub, $this->_uid);

		// Go to last incomplete section
		if ($this->_task == 'continue')
		{
			$blocks 	= $pub->_curationModel->_progress->blocks;
			$sequence	= $pub->_curationModel->_progress->firstBlock;
			$block		= $sequence ? $blocks->$sequence->name : 'status';
		}

		// Go to review screen
		if ($this->_task == 'review'
			|| ($this->_task == 'continue' && $pub->_curationModel->_progress->complete == 1)
		)
		{
			$sequence	= $pub->_curationModel->_progress->lastBlock;
			$block		= 'review';
		}

		// Certain publications go to status page
		if ($pub->state == 5 || $pub->state == 0 || ($block == 'review' && $pub->state == 1))
		{
			$block = 'status';
			$sequence = 0;
		}

		// Make sure block exists, else redirect to status
		if (!$pub->_curationModel->setBlock( $block, $sequence ))
		{
			$block = 'status';
		}

		// Get requested block
		$name = $block == 'status' ? 'status' : 'draft';

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'	=>'projects',
				'element'	=>'publications',
				'name'		=> $name,
			)
		);

		// Output HTML
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->config 		= $this->_config;
		$view->title		= $this->_area['title'];
		$view->active		= $block;
		$view->pub 			= $pub;
		$view->route 		= $route;
		$view->pubconfig 	= $this->_pubconfig;
		$view->task			= $this->_task;

		// Build pub url
		$view->url = Route::url($view->route . '&pid=' . $pid);

		// Append breadcrumbs
		$this->_appendBreadcrumbs( $pub->title, $view->url, $version);

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Edit content item
	 *
	 * @return     string
	 */
	public function editItem()
	{
		// Incoming
		$id 	= Request::getInt( 'aid', 0 );
		$props  = Request::getVar( 'p', '' );

		// Parse props for curation
		$parts   = explode('-', $props);
		$block   = (isset($parts[0])) ? $parts[0] : 'content';
		$step    = (isset($parts[1]) && is_numeric($parts[1]) && $parts[1] > 0) ? $parts[1] : 1;
		$element = (isset($parts[2]) && is_numeric($parts[2]) && $parts[2] > 0) ? $parts[2] : 0;

		// Load classes
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$objV = new \Components\Publications\Tables\Version( $this->_database );

		if ($this->_task == 'editauthor')
		{
			// Get author information
			$row 	= new \Components\Publications\Tables\Author( $this->_database );
			$error 	= Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_ERROR_LOAD_AUTHOR');
			$layout = 'author';
		}
		else
		{
			// Load attachment
			$row 	= new \Components\Publications\Tables\Attachment( $this->_database );
			$error 	= Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_ERROR_EDIT_CONTENT');
			$layout = 'attachment';
		}

		// We need attachment record
		if (!$id || !$row->load($id))
		{
			$this->setError($error);
		}

		// Load version
		if (!$objV->load($row->publication_version_id))
		{
			$this->setError($error);
		}
		else
		{
			// Get publication
			$pub = $objP->getPublication($objV->publication_id, $objV->version_number, $this->_project->id);
			if (!$pub)
			{
				$this->setError($error);
			}
		}

		// On error
		if ($this->getError())
		{
			// Output error
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'	=>'projects',
					'element'	=>'publications',
					'name'		=>'error'
				)
			);

			$view->title  = '';
			$view->option = $this->_option;
			$view->setError( $this->getError() );
			return $view->loadTemplate();
		}

		// Load master type
		$mt   			= new \Components\Publications\Tables\MasterType( $this->_database );
		$pub->_type   	= $mt->getType($pub->base);
		$pub->_project 	= $this->_project;

		// Get curation model
		$pub->_curationModel = new \Components\Publications\Models\Curation($pub->_type->curation);

		// Set pub assoc and load curation
		$pub->_curationModel->setPubAssoc($pub);

		// On success
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'	=> 'projects',
				'element'	=> 'publications',
				'name'		=> 'edititem',
				'layout'	=> $layout
			)
		);

		// Get project path
		if ($this->_task != 'editauthor')
		{
			$config 		= Component::params( 'com_projects' );
			$view->path 	= \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);
		}

		$view->step 	= $step;
		$view->block	= $block;
		$view->element  = $element;
		$view->database = $this->_database;
		$view->option 	= $this->_option;
		$view->project 	= $this->_project;
		$view->pub		= $pub;
		$view->row		= $row;
		$view->backUrl	= Request::getVar('HTTP_REFERER', NULL, 'server');
		$view->ajax		= Request::getInt( 'ajax', 0 );
		$view->props	= $props;

		return $view->loadTemplate();
	}

	/**
	 *  Append breadcrumbs
	 *
	 * @return   void
	 */
	protected function _appendBreadcrumbs( $title, $url, $version = 'default')
	{
		// Append breadcrumbs
		$url 		= $version != 'default' ? $url . '&version=' . $version : $url;
		Pathway::append(
			stripslashes($title),
			$url
		);
	}

	/**
	 * Browse publications
	 *
	 * @return     string
	 */
	public function browse()
	{
		// Build query
		$filters = array();
		$filters['limit'] 	 		= Request::getInt('limit', 25);
		$filters['start'] 	 		= Request::getInt('limitstart', 0);
		$filters['sortby']   		= Request::getVar( 't_sortby', 'title');
		$filters['sortdir']  		= Request::getVar( 't_sortdir', 'ASC');
		$filters['project']  		= $this->_project->id;
		$filters['ignore_access']   = 1;
		$filters['dev']   	 		= 1; // get dev versions

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'browse'
			)
		);

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );

		// Get all publications
		$view->rows = $objP->getRecords($filters);

		// Get total count
		$results = $objP->getCount($filters);
		$view->total = ($results && is_array($results)) ? count($results) : 0;

		// Areas required for publication
		$view->required = array('content', 'description', 'license', 'authors');

		// Get master publication types
		$mt = new \Components\Publications\Tables\MasterType( $this->_database );
		$choices = $mt->getTypes('alias', 1);

		\Hubzero\Document\Assets::addPluginStylesheet('projects', 'files','css/diskspace');
		\Hubzero\Document\Assets::addPluginScript('projects', 'files','js/diskspace');

		// Get used space
		$view->dirsize = \Components\Publications\Helpers\Html::getDiskUsage($view->rows);
		$view->params  = new JParameter( $this->_project->params );
		$view->quota   = $view->params->get('pubQuota')
						? $view->params->get('pubQuota')
						: \Components\Projects\Helpers\Html::convertSize(floatval($this->_config->get('pubQuota', '1')), 'GB', 'b');

		// Output HTML
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->filters 		= $filters;
		$view->config 		= $this->_config;
		$view->pubconfig 	= $this->_pubconfig;
		$view->choices 		= $choices;
		$view->title		= $this->_area['title'];

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Start a publication
	 *
	 * @return     string
	 */
	public function start()
	{
		// Get master publication types
		$mt = new \Components\Publications\Tables\MasterType( $this->_database );
		$choices = $mt->getTypes('*', 1, 0, 'ordering', $this->_config);

		// Contribute process outside of projects
		if (!is_object($this->_project) or !$this->_project->id)
		{
			$this->_project = new \Components\Projects\Tables\Project( $this->_database );
			$this->_project->provisioned = 1;

			// Send to file picker
			return $this->add();
		}

		// Check that choices apply to a particular project
		$choices = $this->_getAllowedTypes($choices);

		// Do we have a choice?
		if (count($choices) <= 1 )
		{
			// Send to file picker
			return $this->add();
		}

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'draft',
				'layout'=>'start'
			)
		);

		// Build pub url
		$view->route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route);

		// Append breadcrumbs
		Pathway::append(
			stripslashes(Lang::txt('PLG_PROJECTS_PUBLICATIONS_START_PUBLICATION')),
			$view->url . '?action=start'
		);

		// Output HTML
		$view->params 		= new JParameter( $this->_project->params );
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->config 		= $this->_config;
		$view->choices 		= $choices;
		$view->title		= $this->_area['title'];
		$view->useBlocks    = $this->useBlocks;

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * First screen in publication process, adding content
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	public function add()
	{
		// Incoming
		$base = Request::getVar('base', 'files');

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'edit',
				'layout'=>'primarycontent'
			)
		);

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$view->pub = $objP;

		// Instantiate publication version
		$objPV = new \Components\Publications\Tables\Version( $this->_database );
		$view->row = $objPV;
		$view->version = 'dev';
		$view->move = 1;

		// Get master publication types
		$mt = new \Components\Publications\Tables\MasterType( $this->_database );
		$view->choices = $mt->getTypes('alias', 1);

		// Check that choices apply to a particular project
		$view->choices = $this->_getAllowedTypes($view->choices);

		if (!in_array($base, $view->choices))
		{
			$base = 'files'; // default to files
		}

		// Get content plugin JS/CSS
		\Hubzero\Document\Assets::addPluginScript('projects', $base);
		\Hubzero\Document\Assets::addPluginStylesheet('projects', $base);

		// Contribute process outside of projects
		if (!is_object($this->_project) or !$this->_project->id)
		{
			$this->_project = new \Components\Projects\Tables\Project( $this->_database );
			$this->_project->provisioned = 1;
		}

		if ($this->_project->provisioned)
		{
			$base = 'files'; // default to files
		}

		// Build pub url
		$view->route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route . '?action=start');

		// Append breadcrumbs
		Pathway::append(
			stripslashes(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUB_NEWPUB')),
			$view->url
		);

		$this->_base = $base;

		// Get attached files
		$view->attachments = array();

		// Get active panels
		$this->_getPanels();

		// Available sections in order
		$view->panels 		= $this->_panels;
		$view->lastpane 	= 'content';
		$view->last_idx 	= 0;
		$view->current_idx 	= 0;

		// Checked areas
		$view->checked = array();
		foreach ($view->panels as $key => $value)
		{
			$view->checked[$value] = 0;
		}

		// Output HTML
		$view->params 		= new JParameter( $this->_project->params );
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->base 		= $base;
		$view->active 		= 'content';
		$view->config 		= $this->_config;
		$view->pubparams 	= $this->params;
		$view->inreview 	= 0;
		$view->title		= $this->_area['title'];

		// Get type helper
		$view->_pubTypeHelper = $this->_pubTypeHelper->dispatch($base, 'getHelper');

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 *  Publication stats
	 *
	 * @return     string
	 */
	protected function _stats()
	{
		// Incoming
		$pid 		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$version 	= Request::getVar( 'version', '' );

		// Load publication & version classes
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$row  = new \Components\Publications\Tables\Version( $this->_database );

		// Check that version exists
		$version = $row->checkVersion($pid, $version) ? $version : 'default';

		// Add stylesheet
		\Hubzero\Document\Assets::addPluginStylesheet('projects', 'publications','css/impact');

		// Is logging enabled?
		if ( is_file(PATH_CORE . DS . 'administrator' . DS . 'components'. DS
				.'com_publications' . DS . 'tables' . DS . 'logs.php'))
		{
			require_once( PATH_CORE . DS . 'administrator' . DS . 'components'. DS
					.'com_publications' . DS . 'tables' . DS . 'logs.php');
		}
		else
		{
			$this->setError('Publication logs not present on this hub, cannot generate stats');
			return false;
		}

		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'stats'
			)
		);

		// Get pub stats for each publication
		$pubLog = new \Components\Publications\Tables\Log($this->_database);
		$view->pubstats = $pubLog->getPubStats($this->_project->id, $pid);

		// Get date of first log
		$view->firstlog = $pubLog->getFirstLogDate();

		// Test
		$view->totals = $pubLog->getTotals($this->_project->id, 'project');

		// Output HTML
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->pid 			= $pid;
		$view->pub			= $objP->getPublication($pid, $version, $this->_project->id);
		$view->task 		= $this->_task;
		$view->config 		= $this->_config;
		$view->pubconfig 	= $this->_pubconfig;
		$view->version 		= $version;
		$view->route 		= $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias
					. '&active=publications';
		$view->url 			= $pid ? Route::url($view->route . '&pid=' . $pid) : Route::url($view->route);
		$view->title		= $this->_area['title'];

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Edit a publication
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	public function edit()
	{
		// Incoming
		$move 		= Request::getInt( 'move', 0 );
		$section  	= Request::getVar( 'section', 'version' );
		$toolid 	= Request::getVar( 'toolid', 0 );
		$pid 		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$version 	= Request::getVar( 'version', '' );
		$step 		= Request::getVar( 'step', '' );
		$base 		= Request::getVar( 'base', 'files' );
		$primary 	= Request::getVar( 'primary', 1 );
		$layout 	= $section;
		$inreview 	= Request::getInt( 'review', 0 );

		// Load publication & version classes
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$row  = new \Components\Publications\Tables\Version( $this->_database );

		// Check that version exists
		$version = $row->checkVersion($pid, $version) ? $version : 'default';

		// Instantiate project publication
		$pub = $objP->getPublication($pid, $version, $this->_project->id);

		// Start url
		$route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias='
						. $this->_project->alias . '&active=publications';

		// If publication not found, raise error
		if (!$pub)
		{
			$this->_referer = Route::url($route);
			$this->_message = array(
				'message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'),
				'type' => 'error');
			return;
		}

		// Master type
		$this->_base = $pub->base;

		// Get active panels
		$this->_getPanels();

		// Available sections in order
		if (!in_array($section, $this->_panels) && $section != 'version')
		{
			$layout = 'version';
			$section = 'version';
		}

		// Get master publication types
		$mt = new \Components\Publications\Tables\MasterType( $this->_database );
		$choices = $mt->getTypes('alias', 1);

		// Check that choices apply to a particular project
		$choices = $this->_getAllowedTypes($choices);

		// Default primary content
		if (!in_array($base, $choices))
		{
			$base = 'files';
		}

		// Master type params (determines management options)
		$mType = $mt->getType($pub->base);
		$typeParams = new JParameter( $mType->params );

		// New version?
		if ($this->_task == 'newversion')
		{
			$section = 'content';
		}

		// Which content panel?
		if ($section == 'content')
		{
			if ($step)
			{
				$layout = $step == 'supportingdocs' ? 'supportingdocs' : 'primarycontent';
			}
			else
			{
				$layout = $primary ? 'primarycontent' : 'supportingdocs';
			}

			// Get choice of content type for supporting items
			if ($layout == 'supportingdocs')
			{
				$sChoices = $mt->getTypes('alias', 0, 1);

				// Check that choices apply to a particular project
				$choices = $this->_getAllowedTypes($sChoices);
			}
		}
		// Which description panel?
		if ($section == 'description' && $typeParams->get('show_metadata', 0))
		{
			$layout = $step == 'metadata' ? 'metadata' : 'description';
		}

		if ($section == 'content' || $section == 'gallery')
		{
			\Hubzero\Document\Assets::addPluginScript('projects', 'files');
		}

		// Base of primary content corresponds to master type!
		if ($section == 'content' && $primary && $step != 'supportingdocs')
		{
			$base = $pub->base;
		}

		// Main version
		if ($pub->main == 1)
		{
			$version = 'default';
		}
		// We have a draft
		if ($pub->state == 3)
		{
			$version = 'dev';
		}
		// Unpublished version, can't view sections
		if ($pub->state == 0)
		{
			$section = 'version';
			$layout  = 'version';
		}

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'edit',
				'layout'=>$layout
			)
		);
		$view->panels = $this->_panels;
		$view->pub = $pub;
		$view->route = $route;

		// Build pub url
		$view->url = Route::url($view->route . '&pid=' . $pid);

		// Instantiate publication version
		$row->loadVersion($pid, $version);

		// Append breadcrumbs
		$url = $version != 'default' ? $view->url . '&amp;version=' . $version : $view->url;
		Pathway::append(
			stripslashes($row->title),
			$view->url
		);

		// Get extra info specific to each panel
		switch ($section)
		{
			case 'version':
				// Get authors
				$pa = new \Components\Publications\Tables\Author( $this->_database );
				$view->authors = $pa->getAuthors($row->id);
				break;

			case 'content':
			    $pContent = new \Components\Publications\Tables\Attachment( $this->_database );
				$role = $layout == 'primarycontent'  ? '1' : '0';
				$view->attachments = $pContent->getAttachments ( $row->id, $filters = array('role' => $role) );
				$view->base = $pub->base ? $pub->base : 'files';

				// Get project file path
				$view->fpath = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);
				$view->prefix = $this->_config->get('offroot', 0) ? '' : PATH_CORE;

				// Get Files JS
				\Hubzero\Document\Assets::addPluginScript('projects', 'files');
				\Hubzero\Document\Assets::addPluginStylesheet('projects', 'files');
				break;

			case 'description':
				// Get custom metadata fields (depending on type)
				$rt = new \Components\Publications\Tables\Category( $this->_database );
				$rt->load( $pub->category );
				$view->customFields = $rt->customFields;
				break;

			case 'authors':
				// Get authors
				$pa = new \Components\Publications\Tables\Author( $this->_database );
				$view->authors = $pa->getAuthors($row->id);

				// Showing submitter?
				if ($typeParams->get('show_submitter'))
				{
					$view->submitter = $pa->getSubmitter($row->id, $row->created_by);
				}

				// Get team members
				$objO = new \Components\Projects\Tables\Owner( $this->_database );
				$view->teamids = $objO->getIds( $this->_project->id, 'all', 0, 0 );
				break;

			case 'access':
				// Sys group
				$cn = $this->_config->get('group_prefix', 'pr-').$this->_project->alias;
				$view->sysgroup = new \Hubzero\User\Group();
				if (\Hubzero\User\Group::exists($cn))
				{
					$view->sysgroup = \Hubzero\User\Group::getInstance( $cn );
				}

				// Is access restricted?
				$paccess = new \Components\Publications\Tables\Access( $this->_database );
				$view->access_groups = $paccess->getGroups($row->id, $row->publication_id, $version, $cn);
				break;

			case 'license':
				// Get available licenses
				$objL = new \Components\Publications\Tables\License( $this->_database);
				$apps_only = $pub->master_type == 'tools' ? 1 : 0;
				$view->licenses = $objL->getLicenses( $filters=array('apps_only' => $apps_only));

				// If no active licenses are found, give default choice
				if (!$view->licenses)
				{
					$view->licenses = $objL->getDefaultLicense();
				}

				// Get selected license
				$view->license = '';
				if ($row->license_type)
				{
					$view->license = $objL->getPubLicense( $row->id );
				}
				break;

			case 'audience':
				// Get audience info
				$ra = new \Components\Publications\Tables\Audience( $this->_database );
				$audience = $ra->getAudience($row->publication_id, $row->id, $getlabels = 1, $numlevels = 4);
				$view->audience = $audience ? $audience[0] : NULL;

				// Get audience levels
				$ral = new \Components\Publications\Tables\AudienceLevel( $this->_database );
				$view->levels = $ral->getLevels( 4, array(), 0 );
				if (!($view->audience))
				{
					$view->audience = new \Components\Publications\Tables\Audience( $this->_database );
				}
				break;

			case 'citations':
				include_once( PATH_CORE . DS . 'components'
					. DS . 'com_citations' . DS . 'tables' . DS . 'citation.php' );
				include_once( PATH_CORE . DS . 'components'
					. DS . 'com_citations' . DS . 'tables' . DS . 'association.php' );
				include_once( PATH_CORE . DS . 'components' . DS . 'com_citations'
					. DS . 'helpers' . DS . 'format.php' );
				$view->format = $this->_pubconfig->get('citation_format', 'apa');

				// Get citations for this publication
				$c = new \Components\Citations\Tables\Citation( $this->_database );
				$view->citations = $c->getCitations( 'publication', $row->publication_id );

				\Hubzero\Document\Assets::addPluginStylesheet('projects', 'links');
				\Hubzero\Document\Assets::addPluginScript('projects', 'links');
				\Hubzero\Document\Assets::addPluginStylesheet('projects', 'publications','/css/selector');

				break;

			case 'gallery':
				// Get screenshots
				$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
				$view->shots = $pScreenshot->getScreenshots( $row->id );

				// Get gallery path
				$webpath = $this->_pubconfig->get('webpath');
				$view->gallery_path = \Components\Publications\Helpers\Html::buildPubPath($row->publication_id,
					$row->id, $webpath, 'gallery');

				// Get project file path
				$view->fpath = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);
				$view->prefix = $this->_config->get('offroot', 0) ? '' : PATH_CORE;
				break;

			case 'tags':
				// Get tags
				$tagsHelper = new \Components\Publications\Helpers\Tags( $this->_database);

				$tags_men = $tagsHelper->get_tags_on_object($row->publication_id, 0, 0, 0, 0);

				$mytagarray = array();
				foreach ($tags_men as $tag_men)
				{
					$mytagarray[] = $tag_men['raw_tag'];
				}
				$view->tags = implode(', ', $mytagarray);

				// Get types
				$rt = new \Components\Publications\Tables\Category( $this->_database );
				$view->categories = $rt->getContribCategories();
				break;
		}

		// Get type info
		$view->_category = new \Components\Publications\Tables\Category( $this->_database );
		$view->_category->load($pub->category);
		$view->_category->_params = new JParameter( $view->_category->params );

		// What's the last visited panel
		$view->params 		= new JParameter( $row->params );
		$view->lastpane 	= $view->params->get('stage', 'content');
		$indexes 			= $this->_getIndex($row, $view->lastpane, $section);
		$view->last_idx 	= $indexes['last_idx'];
		$view->current_idx 	= $indexes['current_idx'];

		// Checked areas
		$view->checked = $this->_checkDraft( $pub->base, $row, $version );

		// Areas required for publication
		$view->required = $this->_getPanels( true );

		// Areas that can be updated after publication
		$view->mayupdate = $this->_updateAllowed;

		// Check if all required area are filled in
		$view->publication_allowed = $this->_checkPublicationPermit($view->checked, $pub->base);

		$view->_pubTypeHelper = $this->_pubTypeHelper->dispatch($pub->base, 'getHelper');
		$view->_typeHelper 	  = $this->_pubTypeHelper;
		$view->typeParams	  = $typeParams;

		// Output HTML
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->pid 			= $pid;
		$view->version 		= $version;
		$view->active 		= $section;
		$view->layout 		= $layout;
		$view->tool 		= isset($tool) ? $tool : array();
		$view->row 			= $row;
		$view->move 		= $move;
		$view->task 		= $this->_task;
		$view->config 		= $this->_config;
		$view->pubconfig 	= $this->_pubconfig;
		$view->inreview 	= $inreview;
		$view->choices 		= $choices;
		$view->base 		= $base;
		$view->title		= $this->_area['title'];

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Suggest licence
	 *
	 * @return     string
	 */
	protected function _suggestLicense()
	{
		// Incoming
		$pid  		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$version 	= Request::getVar( 'version', 'default' );
		$ajax 		= Request::getInt('ajax', 0);

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$row = new \Components\Publications\Tables\Version( $this->_database );

		// If publication not found, raise error
		$pub = $objP->getPublication($pid, $version, $this->_project->id);
		if (!$pub)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'));
			$this->_task = '';
			return $this->browse();
		}

		// Build pub url
		$route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$url = Route::url($route . '&pid=' . $pid);

		if ($this->_task == 'save_license')
		{
			$l_title 	= htmlentities(Request::getVar('license_title', '', 'post'));
			$l_url 		= htmlentities(Request::getVar('license_url', '', 'post'));
			$l_text 	= htmlentities(Request::getVar('details', '', 'post'));

			if (!$l_title && !$l_url && !$l_text)
			{
				$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_SUGGESTION_ERROR'));
			}
			else
			{
				// Include support scripts
				include_once( PATH_CORE . DS . 'administrator' . DS . 'components'
					. DS . 'com_support' . DS . 'tables' . DS . 'ticket.php' );
				include_once( PATH_CORE . DS . 'administrator' . DS . 'components'
					. DS . 'com_support' . DS . 'tables' . DS . 'comment.php' );
				$juser = JFactory::getUser();

				// Load the support config
				$sparams = Component::params('com_support');

				$row = new \Components\Support\Tables\Ticket( $this->_database );
				$row->created = JFactory::getDate()->toSql();
				$row->login = $juser->get('username');
				$row->email = $juser->get('email');
				$row->name = $juser->get('name');
				$row->summary = Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_SUGGESTION_NEW');

				$report 	 	= Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_TITLE') . ': '. $l_title ."\r\n";
				$report 	   .= Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_URL') . ': '. $l_url ."\r\n";
				$report 	   .= Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_COMMENTS') . ': '. $l_text ."\r\n";
				$row->report 	= $report;
				$row->referrer 	= Request::getVar('HTTP_REFERER', NULL, 'server');
				$row->type	 	= 0;
				$row->severity	= 'normal';

				$admingroup = $this->_config->get('admingroup', '');
				$group = \Hubzero\User\Group::getInstance($admingroup);
				$row->group = $group ? $group->get('cn') : '';

				if (!$row->store())
				{
					$this->setError($row->getError());
				}
				else
				{
					$ticketid = $row->id;

					// Notify project admins
					$message  = $row->name . ' ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_SUGGESTED')."\r\n";;
					$message .= '----------------------------'."\r\n";
					$message .=	$report;
					$message .= '----------------------------'."\r\n";

					if ($ticketid)
					{
						$juri = JURI::getInstance();

						$message .= Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_TICKET_PATH') ."\n";
						$message .= $juri->base() . 'support/ticket/' . $ticketid . "\n\n";
					}

					if ($group)
					{
						$members 	= $group->get('members');
						$managers 	= $group->get('managers');
						$admins 	= array_merge($members, $managers);
						$admins 	= array_unique($admins);

						// Send out email to admins
						if (!empty($admins))
						{
							\Components\Projects\Helpers\Html::sendHUBMessage(
								$this->_option,
								$this->_config,
								$this->_project,
								$admins,
								Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_SUGGESTION_NEW'),
								'projects_new_project_admin',
								'admin',
								$message
							);
						}
					}

					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_SUGGESTION_SENT');
				}
			}
		}
		else
		{
			 $view = new \Hubzero\Plugin\View(
				array(
					'folder'=>'projects',
					'element'=>'publications',
					'name'=>'suggestlicense'
				)
			);

			// Output HTML
			$view->option 		= $this->_option;
			$view->database 	= $this->_database;
			$view->project 		= $this->_project;
			$view->uid 			= $this->_uid;
			$view->pid 			= $pid;
			$view->pub 			= $pub;
			$view->task 		= $this->_task;
			$view->config 		= $this->_config;
			$view->pubconfig 	= $this->_pubconfig;
			$view->ajax 		= $ajax;
			$view->route 		= $route;
			$view->version 		= $version;
			$view->url 			= $url;
			$view->title		= $this->_area['title'];

			// Get messages	and errors
			$view->msg = $this->_msg;
			if ($this->getError())
			{
				$view->setError( $this->getError() );
			}
			return $view->loadTemplate();
		}

		// Pass success or error message
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
		}
		elseif (isset($this->_msg) && $this->_msg)
		{
			$this->_message = array('message' => $this->_msg, 'type' => 'success');
		}

		// Redirect
		$this->_referer = $url . '?version=' . $version . '&section=license';
		return;
	}

	/**
	 * Start/save a new version (curation flow)
	 *
	 * @return     string
	 */
	public function makeNewVersion($pub, $oldVersion, $newVersion)
	{
		// Get authors
		$pAuthors 			= new \Components\Publications\Tables\Author( $this->_database );
		$pub->_authors 		= $pAuthors->getAuthors($pub->version_id);
		$pub->_submitter 	= $pAuthors->getSubmitter($pub->version_id, $pub->created_by);

		// Get attachments
		$pContent = new \Components\Publications\Tables\Attachment( $this->_database );
		$pub->_attachments = $pContent->sortAttachments ( $pub->version_id );

		// Transfer data
		$pub->_curationModel->transfer($pub, $oldVersion, $newVersion);

		// Set response message
		$this->set('_msg', Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NEW_VERSION_STARTED'));

		// Set activity message
		$pubTitle = \Hubzero\Utility\String::truncate($newVersion->title, 100);
		$action   = Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_STARTED_VERSION')
					. ' ' . $newVersion->version_label . ' ';
		$action .=  Lang::txt('PLG_PROJECTS_PUBLICATIONS_OF_PUBLICATION') . ' "' . $pubTitle . '"';
		$this->set('_activity', $action);

		// Record action, notify team
		$this->onAfterSave( $pub, $newVersion->version_number );

	}

	/**
	 * Start/save a new version
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	protected function _newVersion()
	{
		// Incoming
		$pid  = $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$ajax = Request::getInt('ajax', 0);
		$label = trim(Request::getVar( 'version_label', '', 'post' ));

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$row  = new \Components\Publications\Tables\Version( $this->_database );

		// If publication not found, raise error
		$pub = $objP->getPublication($pid, 'default', $this->_project->id);
		if (!$pub)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'));
			$this->_task = '';
			return $this->browse();
		}

		// Load master type
		$mt   			= new \Components\Publications\Tables\MasterType( $this->_database );
		$pub->_type   	= $mt->getType($pub->base);
		$pub->_project 	= $this->_project;

		// Get curation model
		$pub->_curationModel = new \Components\Publications\Models\Curation($pub->_type->curation);

		// Set pub assoc and load curation
		$pub->_curationModel->setPubAssoc($pub);

		// Build pub url
		$route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$url = Route::url($route . '&pid=' . $pid);

		// Check if dev version is already there
		if ($row->checkVersion($pid, 'dev'))
		{
			// Redirect
			$this->_referer = $url.'?version=dev';
			return;
		}

		// Load default version
		$row->loadVersion($pid, 'default');
		$oldid = $row->id;
		$now = JFactory::getDate()->toSql();

		// Can't start a new version if there is a finalized or submitted draft
		if ($row->state == 4 || $row->state == 5 || $row->state == 7)
		{
			// Determine redirect path
			$this->_referer = $url . '?version=default';
			return;
		}

		// Saving new version
		if ($this->_task == 'savenew')
		{
			$used_labels = $row->getUsedLabels( $pid, 'dev');
			if (!$label)
			{
				$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VERSION_LABEL_NONE') );
			}
			elseif ($label && in_array($label, $used_labels))
			{
				$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VERSION_LABEL_USED') );
			}
			else
			{
				// Create new version
				$new 				=  new \Components\Publications\Tables\Version( $this->_database );
				$new 				= $row; // copy of default version
				$new->id 			= 0;
				$new->created 		= $now;
				$new->created_by 	= $this->_uid;
				$new->modified 		= $now;
				$new->modified_by 	= $this->_uid;
				$new->rating 		= '0.0';
				$new->state 		= 3;
				$new->version_label = $label;
				$new->doi 			= '';
				$new->secret 		= strtolower(\Components\Projects\Helpers\Html::generateCode(10, 10, 0, 1, 1));
				$new->version_number= $pub->versions + 1;
				$new->main 			= 0;
				$new->release_notes = NULL; // Release notes will need to be different
				$new->submitted 	= NULL;
				$new->reviewed 		= NULL;
				$new->reviewed_by   = 0;
				$new->curation		= NULL; // Curation manifest needs to reflect any new requirements
				$new->params		= NULL; // Accept fresh configs

				if ($new->store())
				{
					$newid = $new->id;

					// Curation
					if ($this->useBlocks)
					{
						$this->makeNewVersion($pub, $row, $new);

						// Redirect
						$this->_referer = $url . '?version=dev';
						return;
					}

					// Get attachments
					$pContent = new \Components\Publications\Tables\Attachment( $this->_database );
					$attachments = $pContent->getAttachments( $oldid );

					jimport('joomla.filesystem.file');
					jimport('joomla.filesystem.folder');

					// Build publication path
					$base_path = $this->_pubconfig->get('webpath');
					$oldpath = \Components\Publications\Helpers\Html::buildPubPath($pid, $oldid, $base_path, $pub->secret, 1);
					$newpath = \Components\Publications\Helpers\Html::buildPubPath($pid, $newid, $base_path, $new->secret, 1);

					// Create new path
					if (!is_dir( $newpath ))
					{
						JFolder::create( $newpath );
					}

					// Copy attachments from default to new version
					if ($attachments && !$this->useBlocks)
					{
						foreach ($attachments as $att)
						{
							$pAttach = new \Components\Publications\Tables\Attachment( $this->_database );
							$pAttach->publication_id 		= $att->publication_id;
							$pAttach->title 				= $att->title;
							$pAttach->role 					= $att->role;
							$pAttach->element_id 			= $att->element_id;
							$pAttach->path 					= $att->path;
							$pAttach->vcs_hash 				= $att->vcs_hash;
							$pAttach->vcs_revision 			= $att->vcs_revision;
							$pAttach->object_id 			= $att->object_id;
							$pAttach->object_name 			= $att->object_name;
							$pAttach->object_instance 		= $att->object_instance;
							$pAttach->object_revision 		= $att->object_revision;
							$pAttach->type 					= $att->type;
							$pAttach->params 				= $att->params;
							$pAttach->attribs 				= $att->attribs;
							$pAttach->ordering 				= $att->ordering;
							$pAttach->publication_version_id= $newid;
							$pAttach->created_by 			= $this->_uid;
							$pAttach->created 				= $now;
							if (!$pAttach->store())
							{
								continue;
							}
						}
					}

					// Copy other items
					if (!$this->useBlocks)
					{
						// Copy attachment files
						if (is_dir($oldpath))
						{
							JFolder::copy($oldpath, $newpath, '', true);
						}
					}

					// Get authors
					$pa = new \Components\Publications\Tables\Author( $this->_database );
					$authors = $pa->getAuthors($oldid);

					// Copy authors from default to new version
					if ($authors)
					{
						foreach ($authors as $author)
						{
							$pAuthor 							= new \Components\Publications\Tables\Author( $this->_database );
							$pAuthor->user_id 					= $author->user_id;
							$pAuthor->ordering 					= $author->ordering;
							$pAuthor->credit 					= $author->credit;
							$pAuthor->role 						= $author->role;
							$pAuthor->status 					= $author->status;
							$pAuthor->organization 				= $author->organization;
							$pAuthor->name 						= $author->name;
							$pAuthor->project_owner_id 			= $author->project_owner_id;
							$pAuthor->publication_version_id 	= $newid;
							$pAuthor->created 					= $now;
							$pAuthor->created_by 				= $this->_uid;
							if (!$pAuthor->createAssociation())
							{
								continue;
							}
						}
					}

					// Copy gallery images
					if (!$this->useBlocks)
					{
						$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
						$screenshots = $pScreenshot->getScreenshots( $oldid );
						if ($screenshots)
						{
							foreach ($screenshots as $shot)
							{
								$pShot 							= new \Components\Publications\Tables\Screenshot( $this->_database );
								$pShot->filename 				= $shot->filename;
								$pShot->srcfile 				= $shot->srcfile;
								$pShot->publication_id 			= $shot->publication_id;
								$pShot->publication_version_id 	= $newid;
								$pShot->title 					= $shot->title;
								$pShot->created 				= $now;
								$pShot->created_by 				= $this->_uid;
								$pShot->ordering 				= $shot->ordering;
								if (!$pShot->store())
								{
									continue;
								}
							}
						}

						// Copy image files
						$g_oldpath = \Components\Publications\Helpers\Html::buildPubPath($pid, $oldid, $base_path, 'gallery', 1);
						$g_newpath = \Components\Publications\Helpers\Html::buildPubPath($pid, $newid, $base_path, 'gallery', 1);
						if (is_dir($g_oldpath))
						{
							JFolder::copy($g_oldpath, $g_newpath, '', true);
						}
					}

					// Copy access info
					$pAccess = new \Components\Publications\Tables\Access( $this->_database );
					$access_groups = $pAccess->getGroups($oldid);
					if ($access_groups)
					{
						foreach ($access_groups as $ag)
						{
							$pNewAccess = new \Components\Publications\Tables\Access( $this->_database );
							$pNewAccess->publication_version_id = $newid;
							$pNewAccess->group_id = $ag->group_id;
							if (!$pNewAccess->store())
							{
								continue;
							}
						}
					}

					// Copy audience info
					$pAudience = new \Components\Publications\Tables\Audience( $this->_database );
					if ($pAudience->loadByVersion($oldid))
					{
						$pAudienceNew = new \Components\Publications\Tables\Audience( $this->_database );
						$pAudienceNew = $pAudience;
						$pAudienceNew->publication_version_id = $newid;
						$pAudienceNew->store();
					}

					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NEW_VERSION_STARTED');

					// Record activity
					$pubtitle = \Hubzero\Utility\String::truncate($new->title, 100);
					$action  = Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_STARTED_VERSION').' '.$new->version_label.' ';
					$action .=  Lang::txt('PLG_PROJECTS_PUBLICATIONS_OF_PUBLICATION').' "'.$pubtitle.'"';
					$objAA = new \Components\Projects\Tables\Activity ( $this->_database );
					$aid = $objAA->recordActivity( $this->_project->id, $this->_uid,
						   $action, $pid, $pubtitle,
						   Route::url('index.php?option=' . $this->_option . a .
						   'alias=' . $this->_project->alias . '&active=publications' . a .
						   'pid=' . $pid) . '/?version=' . $new->version_number, 'publication', 1 );
				}
				else
				{
					$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ERROR_SAVING_NEW_VERSION') );
				}
			}
		}
		// Need to ask for new version label
		else
		{
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'=>'projects',
					'element'=>'publications',
					'name'=>'newversion'
				)
			);

			// Output HTML
			$view->option 		= $this->_option;
			$view->database 	= $this->_database;
			$view->project 		= $this->_project;
			$view->uid 			= $this->_uid;
			$view->pid 			= $pid;
			$view->pub 			= $pub;
			$view->task 		= $this->_task;
			$view->config 		= $this->_config;
			$view->pubconfig 	= $this->_pubconfig;
			$view->ajax 		= $ajax;
			$view->route 		= $route;
			$view->url 			= $url;
			$view->title		= $this->_area['title'];

			// Get messages	and errors
			$view->msg = $this->_msg;
			if ($this->getError())
			{
				$view->setError( $this->getError() );
			}
			return $view->loadTemplate();
		}

		// Pass success or error message
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
		}
		elseif (isset($this->_msg) && $this->_msg)
		{
			$this->_message = array('message' => $this->_msg, 'type' => 'success');
		}

		// Redirect
		$this->_referer = $url.'?version=dev';
		return;
	}

	/**
	 * Review publication
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	public function review()
	{
		// Incoming
		$pid 		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$version 	= Request::getVar('version', '');
		$pubdate 	= Request::getVar('publish_date');

		\Hubzero\Document\Assets::addComponentStylesheet('com_projects', 'assets/css/calendar');

		// Check that version number exists
		$row = new \Components\Publications\Tables\Version( $this->_database );
		$version = $version && $row->checkVersion($pid, $version) ? $version : 'dev';

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );

		// If publication not found, raise error
		$pub = $objP->getPublication($pid, $version, $this->_project->id);
		if (!$pub)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'));
			$this->_task = '';
			return $this->browse();
		}

		// Master type
		$this->_base = $pub->base;

		// Get active panels
		$this->_getPanels();

		// Instantiate publication version
		$row->loadVersion($pid, $version);

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'review'
			)
		);

		// Build pub url
		$view->route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $pid);

		// Append breadcrumbs
		$url =  $view->url . '?version='.$version;
		Pathway::append(
			stripslashes($row->title),
			$url
		);

		// Get master publication types
		$mt = new \Components\Publications\Tables\MasterType( $this->_database );
		$choices = $mt->getTypes('alias', 1);

		// Check that choices apply to a particular project
		$choices = $this->_getAllowedTypes($choices);

		// Get type info
		$view->_category = new \Components\Publications\Tables\Category( $this->_database );
		$view->_category->load($pub->category);
		$view->_category->_params = new JParameter( $view->_category->params );

		// What's the last visited panel
		$view->params 		= new JParameter( $row->params );
		$view->lastpane 	= $view->params->get('stage', 'content');
		$indexes 			= $this->_getIndex($row, $view->lastpane, '');
		$view->last_idx 	= $indexes['last_idx'];
		$view->current_idx 	= $indexes['current_idx'];

		// Checked areas
		$view->checked = $this->_checkDraft( $pub->base, $row, $version );

		// Areas required for publication
		$view->required = $this->_getPanels( true, $pub->base );

		// Areas that can be updated after publication
		$view->mayupdate = $this->_updateAllowed;

		// Check if all required area are filled in
		$view->publication_allowed = $this->_checkPublicationPermit($view->checked, $pub->base);

		// Get detailed information
		// Get authors
		$pa = new \Components\Publications\Tables\Author( $this->_database );
		$view->authors = $pa->getAuthors($row->id);

		// Get attachments
		$pContent = new \Components\Publications\Tables\Attachment( $this->_database );
		$view->primary = $pContent->getAttachments( $row->id, $filters = array('role' => '1') );
		$view->secondary = $pContent->getAttachments( $row->id, $filters = array('role' => '0') );

		// Build publication paths (to access attachments and images)
		$base_path = $this->_pubconfig->get('webpath');
		if ($version == 'dev')
		{
			$view->fpath = \Components\Projects\Helpers\Html::getProjectRepoPath($pub->project_alias);
		}
		else
		{
			$view->fpath = \Components\Publications\Helpers\Html::buildPubPath($pub->id, $pub->version_id, $base_path, $pub->secret, $root = 1);
		}
		$gallery_path = \Components\Publications\Helpers\Html::buildPubPath($pub->id, $pub->version_id, $base_path, 'gallery');

		// Get project file path
		$view->prefix = $this->_config->get('offroot', 0) ? '' : PATH_CORE;
		$view->project_path = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);

		// Get tags
		$view->model = new \Components\Publications\Models\Publication($pub);
		$view->model->getTagCloud( 1 );
		$view->tags = $view->model->_tagCloud;

		// Get license info
		$pLicense = new \Components\Publications\Tables\License( $this->_database );
		$view->license = $pLicense->getLicense($pub->license_type);

		// Sys group
		$cn = $this->_config->get('group_prefix', 'pr-').$this->_project->alias;
		$view->sysgroup = new \Hubzero\User\Group();
		if (\Hubzero\User\Group::exists($cn))
		{
			$view->sysgroup = \Hubzero\User\Group::getInstance( $cn );
		}

		// Is access restricted?
		$paccess = new \Components\Publications\Tables\Access( $this->_database );
		$view->access_groups = $paccess->getGroups($pub->version_id, $pub->id, $version, $cn);

		// Get gallery images
		/*
		$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
		$gallery = $pScreenshot->getScreenshots( $pub->version_id );
		$view->shots = \Components\Publications\Helpers\Html::showGallery($gallery, $gallery_path, $pub->id, $pub->version_id);
		*/

		// Get JS
		\Hubzero\Document\Assets::addComponentScript('com_publications', 'assets/js/publications');

		// Output HTML
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->juser		= JFactory::getUser();
		$view->pid 			= $pid;
		$view->version 		= $version;
		$view->pub 			= $pub;
		$view->row 			= $row;
		$view->task 		= $this->_task;
		$view->config 		= $this->_config;
		$view->pubconfig 	= $this->_pubconfig;
		$view->choices 		= $choices;
		$view->panels 		= $this->_panels;
		$view->title		= $this->_area['title'];
		$view->pubdate		= $pubdate;

		// Master type params (determines management options)
		$mType = $mt->getType($this->_base);
		$typeParams = new JParameter( $mType->params );

		// Showing submitter?
		if ($typeParams->get('show_submitter'))
		{
			$view->submitter = $pa->getSubmitter($row->id, $row->created_by);
		}

		// Merge with publication master type params
		$view->pubconfig->merge( $typeParams );

		// Get type helper
		$view->_pubTypeHelper = $this->_pubTypeHelper->dispatch($this->_base, 'getHelper');
		$view->_typeHelper	  = $this->_pubTypeHelper;

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Save publication
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	public function save()
	{
		// Incoming
		$move 		= Request::getInt( 'move', 0 );
		$section 	= Request::getVar( 'section', 'version' );
		$toolid 	= Request::getVar( 'toolid', 0 );
		$pid 		= Request::getInt( 'pid', 0 );
		$version 	= Request::getVar( 'version', '' );
		$primary 	= Request::getVar( 'primary', 1 );
		$base 		= Request::getVar( 'base', 'files' );
		$selections = Request::getVar( 'selections', array(), 'post' );
		$inreview 	= Request::getInt( 'review', 0 );
		$step 		= Request::getVar( 'step', '' );

		$layout 	= $section;
		$newpub 	= 0;
		$newversion = 0;
		$now = JFactory::getDate()->toSql();

		// Check that version exists
		$row = new \Components\Publications\Tables\Version( $this->_database );
		$version = $row->checkVersion($pid, $version) ? $version : 'default';

		// Get selected content
		if ($section == 'content' or $section == 'gallery')
		{
			$selections = $this->_parseSelections($selections);

			// Check for primary content
			if ($section == 'content' && $primary && (empty($selections) || $selections['count'] == 0))
			{
				$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NO_CONTENT_SELECTED') );
			}
			else
			{
				$arr = explode("::", $selections['first']);
				$first_type = urldecode($arr[0]);
				$first_item = (isset($arr[1])) ? urldecode($arr[1]) : '';
			}
		}

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$mt   = new \Components\Publications\Tables\MasterType( $this->_database );

		// If publication not found, raise error
		if (!$objP->load($pid) && $section != 'content')
		{
			throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'), 404);
			return;
		}
		// Save new publication
		elseif (!$objP->id && $primary)
		{
			 // Flag as new publication
			$newpub = 1;

			if (!$this->getError())
			{
				// Determine publication master type
				$choices = $mt->getTypes('alias', 1);

				// Check what choices apply to a particular project
				$choices = $this->_getAllowedTypes($choices);

				$mastertype = in_array($base, $choices) ? $base : 'files';

				// Need to provision a project
				if (!is_object($this->_project) or !$this->_project->id)
				{
					$this->_project 					= new \Components\Projects\Tables\Project( $this->_database );
					$this->_project->provisioned 		= 1;
					$random 							= strtolower(\Components\Projects\Helpers\Html::generateCode(10, 10, 0, 1, 1));
					$this->_project->alias 	 			= 'pub-' . $random;
					$this->_project->title 	 			= $this->_project->alias;
					$this->_project->type 	 			= $base == 'tools' ? 2 : 3; // content publication
					$this->_project->state   			= 1;
					$this->_project->created 			= JFactory::getDate()->toSql();
					$this->_project->created_by_user 	= $this->_uid;
					$this->_project->owned_by_user 		= $this->_uid;
					$this->_project->setup_stage 		= 3;

					// Get project type params
					require_once( PATH_CORE. DS . 'components' . DS
						. 'com_projects' . DS . 'tables' . DS . 'type.php');
					$objT = new \Components\Projects\Tables\Type( $this->_database );
					$this->_project->params = $objT->getParams ($this->_project->type);

					// Save changes
					if (!$this->_project->store())
					{
						$this->setError( $this->_project->getError() );
						return false;
					}

					if (!$this->_project->id)
					{
						$this->_project->checkin();
					}
				}

				// Determine publication type
				$objT = new \Components\Publications\Tables\Category( $this->_database );

				// Get type params
				$mType 		= $mt->getType($mastertype);
				$typeParams = new JParameter( $mType->params );
				$cat 		= $typeParams->get('default_category');
				$cat		= $cat ? $cat : $objT->getCatId($this->_pubconfig->get('default_category', 'dataset'));

				// Determine title
				$title = $this->_pubTypeHelper->dispatch($mastertype, 'getPubTitle',
						$data = array('item' => $first_item)
				);

				$title = $title ? $title : Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_DEFAULT_TITLE');

				// Make a new publication entry
				$objP->master_type 		= $mt->getTypeId($mastertype);
				$objP->category 		= $cat;
				$objP->project_id 		= $this->_project->id;
				$objP->created_by 		= $this->_uid;
				$objP->created 			= $now;
				$objP->access 			= 0;
				if (!$objP->store())
				{
					throw new Exception( $objP->getError(), 500);
					return false;
				}
				if (!$objP->id)
				{
					$objP->checkin();
				}
				$pid 		= $objP->id;
				$this->_pid = $pid;

				// Initizalize Git repo and transfer files from member dir
				if ($this->_project->provisioned == 1 && $newpub)
				{
					if (!$this->_prepDir())
					{
						// Roll back
						$this->_project->delete();
						$objP->delete();
						throw new Exception($this->getError(), 500);
						return false;
					}
					else
					{
						// Add creator as project owner
						$objO = new \Components\Projects\Tables\Owner( $this->_database );
						if (!$objO->saveOwners ( $this->_project->id,
							$this->_uid, $this->_uid,
							0, 1, 1, 1 ))
						{
							$this->setError( Lang::txt('COM_PROJECTS_ERROR_SAVING_AUTHORS')
								. ': ' . $objO->getError());
							return false;
						}
					}
				}

				// Make a new dev version entry
				$row 					= new \Components\Publications\Tables\Version( $this->_database );
				$row->publication_id 	= $pid;
				$row->title 			= $title;
				$row->state 			= 3; // dev
				$row->main 				= 1;
				$row->created_by 		= $this->_uid;
				$row->created 			= $now;
				$row->version_number 	= 1;
				$row->license_type 		= 0;
				$row->access 			= 0;

				// Get hash code for version (to be used as a dir name to guard against direct file access)
				$code = strtolower(\Components\Projects\Helpers\Html::generateCode(10, 10, 0, 1, 1));
				$row->secret = $code;

				$row->params = 'stage=content'."\n";

				if (!$row->store())
				{
					// Roll back
					$objP->delete();

					throw new Exception($row->getError(), 500);
					return false;
				}
				if (!$row->id)
				{
					$row->checkin();
				}
				$vid = $row->id;

				// Proccess attachments
				$added = $this->_processContent( $pid, $vid, $selections, $primary, $row->secret, $row->state, $newpub);

				// Roll back on error
				if ($added < 1)
				{
					$objP->delete();
					$row->delete();
					if ($this->_project->provisioned == 1 && $newpub)
					{
						$this->_project->delete();
					}

					$this->setError( Lang::txt('COM_PROJECTS_ERROR_ATTACHING_CONTENT'));
				}
				else
				{
					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_NEW_PUB_STARTED');
				}

			} // end if no error (new pub)
		}
		elseif ($objP->project_id != $this->_project->id)
		{
			// Publication belongs to another project
			throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_PROJECT_ERROR'), 404);
			return;
		}

		// Master type
		$this->_base = $mt->getTypeAlias($objP->master_type);

		// Saving existing publication
		if (!$newpub && !$this->getError())
		{
			// Instantiate publication version
			$row = new \Components\Publications\Tables\Version( $this->_database );
			if (!$row->loadVersion( $pid, $version ))
			{
				throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VERSION_NOT_FOUND'), 404 );
				return;
			}
			// Disable editing for some DOI-related info, if published
			$canedit = ($row->state == 1 || $row->state == 0) ? 0 : 1;

			// Areas required for publication
			$required = $this->_getPanels( true );

			// Make sure version has a secret id
			if (!$row->secret)
			{
				$code = strtolower(\Components\Projects\Helpers\Html::generateCode(10, 10, 0, 1, 1));
				$row->secret = $code;
				$row->store();
			}

			// Save sections
			switch ($section)
			{
				case 'version':
					$label = trim(Request::getVar( 'label', '', 'post' ));
					$used_labels = $row->getUsedLabels( $pid, $version );
					if ($label && in_array($label, $used_labels))
					{
						$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VERSION_LABEL_USED') );
					}
					elseif ($label)
					{
						$row->version_label = $label;
						if ($row->store())
						{
							$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_VERSION_LABEL_SAVED');
						}
					}
					break;

				case 'content':
					if ($version == 'dev' || ($version == 'default'
						&& (!$primary || $row->state == 4 || $row->state == 5)))
					{
						// Check for primary content
						if ($primary && (empty($selections) || $selections['count'] == 0) )
						{
							$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NO_CONTENT_SELECTED') );
						}
						else
						{
							$added = $this->_processContent( $row->publication_id, $row->id,
								$selections, $primary, $row->secret, $row->state, 0 );
						}

						$this->_msg = $primary
							? Lang::txt('PLG_PROJECTS_PUBLICATIONS_PRIMARY_CONTENT_SAVED')
							: Lang::txt('PLG_PROJECTS_PUBLICATIONS_SUP_CONTENT_SAVED');
					}
					elseif ($version == 'default')
					{
						// Published version! cannot update primary content
						$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ERROR_CANNOT_SAVE_PRIMARY') );
					}
					break;

				case 'description':
					if ($step == 'metadata')
					{
						$row->metadata = $this->_processMetadata( $objP->category );
					}
					else
					{
						$title 			= trim(Request::getVar( 'title', '', 'post' ));
						$title 			= htmlspecialchars($title);
						$abstract 		= trim(Request::getVar( 'abstract', '', 'post' ));
						$abstract 		= \Hubzero\Utility\Sanitize::clean(htmlspecialchars($abstract));
						$description 	= trim(Request::getVar( 'description', '', 'post', 'none', 2 ));

						if ($canedit)
						{
							if (!$title)
							{
								$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_MISSING_REQUIRED_INFO') );
							}
							$row->title 		= $title;
							$row->abstract 		= $abstract ? \Hubzero\Utility\String::truncate($abstract, 250) : $title;
							$row->description 	= $description;
						}
					}

					if (!$row->store())
					{
						throw new Exception($row->getError(), 500);
						return false;
					}

					if (!$this->getError())
					{
						$this->_msg = $step == 'metadata'
							? Lang::txt('PLG_PROJECTS_PUBLICATIONS_METADATA_SAVED')
							: Lang::txt('PLG_PROJECTS_PUBLICATIONS_DESCRIPTION_SAVED');
					}

					break;

				case 'authors':
					$selections = explode("##", $selections);
					if (count($selections) > 0)
					{
						if ($this->_processAuthors($row->id, $selections))
						{
							$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_SAVED');
						}
					}
					elseif (in_array('authors', $required))
					{
						$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_NO_AUTHORS_SAVED') );
					}

					break;

				case 'access':
					// Incoming
					$access = Request::getInt( 'access', 0, 'post' );

					if ($access >= 2)
					{
						$access_groups = Request::getVar( 'access_group', 0, 'post' );

						// Sys group
						$cn = $this->_config->get('group_prefix', 'pr-').$this->_project->alias;
						$sysgroup = new \Hubzero\User\Group();
						if (\Hubzero\User\Group::exists($cn))
						{
							$sysgroup = \Hubzero\User\Group::getInstance( $cn );
						}

						$paccess = new \Components\Publications\Tables\Access( $this->_database );
						$paccess->saveGroups($row->id, $access_groups, $sysgroup->get('gidNumber'));
						$private = Request::getVar( 'private', 0, 'post' );
						$access = $private ? 3 : 2;
					}

					$row->access = $access;
					if (!$row->store())
					{
						throw new Exception($row->getError(), 500);
						return false;
					}

					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_ACCESS_SAVED');
					break;

				case 'license':
					// Incoming
					$license = trim(Request::getVar( 'license', 0, 'post' ));
					$text 	 = Request::getVar( 'license_text', '', 'post', 'array' );
					$agree 	 = Request::getVar( 'agree', 0, 'post', 'array' );

					// Get standard license info
					$objL = new \Components\Publications\Tables\License( $this->_database);
					$selected_license = $objL->getLicenseByName ($license);

					if ($selected_license)
					{
						if ($selected_license->agreement == 1
							&& (empty($agree) || !isset($agree[$license]) || $agree[$license] == 0))
						{
							$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_NEED_AGREEMENT') );
						}
						elseif ($selected_license->customizable == 1
							&& $selected_license->text && (empty($text)
							|| !isset($text[$license]) || $text[$license] == ''))
						{
							$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_NEED_TEXT') );
						}
						else
						{
							$row->license_text = isset($text[$license]) ? stripslashes(rtrim($text[$license])) : '';
							$row->license_type = $selected_license->id;
							if (!$row->store())
							{
								throw new Exception($row->getError(), 500);
								return false;
							}
							$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_SAVED');
						}
					}
					else
					{
						$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_LICENSE_SELECTION_NOT_FOUND') );
					}

					break;

				case 'audience':
					if ($this->_processAudience($row->publication_id, $row->id))
					{
						$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUDIENCE_SAVED');
					}

					break;

				case 'gallery':
					$this->_processGallery($row->publication_id, $row->id, $selections);
					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_SAVED');
					break;

				case 'tags':
					$tagsHelper = new \Components\Publications\Helpers\Tags( $this->_database);
					$tags = trim(Request::getVar('tags', '', 'post'));
					$tagsHelper->tag_object($this->_uid, $row->publication_id, $tags, 1);

					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_TAGS_SAVED');

					// Save category
					$objT = new \Components\Publications\Tables\Category( $this->_database );
					$cat = Request::getInt( 'pubtype', 0 );
					if ($cat && $objP->category != $cat)
					{
						$objP->category = $cat;
						$objP->store();
					}
					break;

				case 'citations':
					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_CITATIONS_SAVED');
					break;

				case 'notes':
					$notes = trim(Request::getVar( 'notes', '', 'post', 'none', 2 ));
					$notes = stripslashes($notes);
					$row->release_notes = $notes;
					if (!$row->store())
					{
						throw new Exception($row->getError(), 500);
						return false;
					}
					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_NOTES_SAVED');
					break;
			}
		}

		// Save last accomplished step
		if (!$this->getError())
		{
			// Get last accomplished section
			$pubparams = new JParameter( $row->params );
			$lastpane = $pubparams->get('stage', 'content');

			// Get next and last accomplished step
			$indexes = $this->_getIndex($row, $lastpane, $section);
			$last_idx = $indexes['last_idx'];
			$next_idx = $indexes['next_idx'];

			// Get active panels
			$this->_getPanels();

			if ($move)
			{
				// Determine next section & layout
				if ($section == 'content' && $primary)
				{
					$layout = 'supportingdocs';
				}
				elseif ($section == 'description')
				{
					$add_metadata = Request::getInt( 'add_metadata', 0 );
					$layout = $add_metadata ? 'metadata' : 'authors';
					$section = $add_metadata ? 'description' : 'authors';
				}
				else
				{
					if ($next_idx == count($this->_panels))
					{
						// last step accomplished
						$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_DRAFT_COMPLETE');
						$section = 'version';
					}
					else
					{
						$section = $this->_panels[$next_idx];
					}
				}
			}
			else
			{
				// Determine layout
				$primary = Request::getInt( 'primary', 0, 'post' );
				if ($section == 'content' && !$primary)
				{
					$layout = 'supportingdocs';
				}
				elseif ($section == 'description')
				{
					$layout = $step == 'metadata' ? 'metadata' : 'description';
				}
			}

			// Save visit to panel (only when moving one step at a time)
			if ($next_idx > $last_idx && ($next_idx == $last_idx + 1))
			{
				$nextstep = isset($this->_panels[$next_idx]) && $lastpane != 'review' ? $this->_panels[$next_idx] : 'review';
				$row->saveParam( $row->id, 'stage', $nextstep  );
			}
		}

		// Record activity
		if (!$this->getError() && $newpub && !$this->_project->provisioned)
		{
			// Record action, notify team
			$this->onAfterCreate($row);
		}

		// Pass success or error message
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
		}
		elseif (isset($this->_msg) && $this->_msg)
		{
			$this->_message = array('message' => $this->_msg, 'type' => 'success');
		}

		// Determine redirect path
		$url = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$url = Route::url($url . '&pid=' . $pid);

		if ($section == 'review' or $inreview)
		{
			$url .= '?action=review';
		}
		else
		{
			$url .= '?section=' . $section;
			$url .= $section != $layout ?  '&step=' . $layout : '';
			$url .= $move ? '&move=' . $move : '';
		}
		$url .= $version != 'default' ? '&version=' . $version : '';

		// Redirect
		$this->_referer = $url;
		return;
	}

	/**
	 * Check if there is available space for publishing
	 *
	 * @return     string
	 */
	protected function _overQuota()
	{
		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );

		// Get all publications
		$rows = $objP->getRecords(array('project' => $this->_project->id, 'dev' => 1, 'ignore_access' => 1));

		// Get used space
		$dirsize 	   = \Components\Publications\Helpers\Html::getDiskUsage($rows);
		$params  	   = new JParameter( $this->_project->params );
		$quota   	   = $params->get('pubQuota')
						? $params->get('pubQuota')
						: \Components\Projects\Helpers\Html::convertSize(floatval($this->_config->get('pubQuota', '1')), 'GB', 'b');

		if (($quota - $dirsize) <= 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Change publication status
	 *
	 * @return     string
	 */
	public function publishDraft()
	{
		// Incoming
		$pid 		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$confirm 	= Request::getInt('confirm', 0);
		$version 	= Request::getVar('version', 'dev');
		$agree   	= Request::getInt('agree', 0);
		$pubdate 	= Request::getVar('publish_date', '', 'post');
		$submitter 	= Request::getInt('submitter', $this->_uid, 'post');
		$notify 	= 1;

		$block  	= Request::getVar( 'section', '' );
		$sequence  	= Request::getInt( 'step', 0 );
		$element  	= Request::getInt( 'element', 0 );

		// Load review step
		if (!$confirm && $this->_task != 'revert')
		{
			$this->_task = 'review';
			return $this->editDraft();
		}

		// Start url
		$route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias='
						. $this->_project->alias . '&active=publications';

		// Determine redirect path
		$url = Route::url($route . '&pid=' . $pid);

		// Agreement to terms is required
		if ($confirm && !$agree)
		{
			$url .= '/?action= ' . $this->_task . '&version=' . $version;
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_REVIEW_AGREE_TERMS_REQUIRED') );
			$this->_message = array('message' => $this->getError(), 'type' => 'error');

			// Redirect
			$this->_referer = $url;
			return;
		}

		// Check against quota
		if ($this->_overQuota())
		{
			$url .= '/?action= ' . $this->_task . '&version=' . $version;
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NO_DISK_SPACE') );
			$this->_message = array('message' => $this->getError(), 'type' => 'error');

			// Redirect
			$this->_referer = $url;
			return;
		}

		// Load publication model
		$model  = new \Components\Publications\Models\Publication( $pid, $version);

		// Error loading publication record
		if (!$model->exists())
		{
			$this->_referer = Route::url($route);
			$this->_message = array(
				'message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'),
				'type' => 'error');
			return;
		}

		// Curated flow?
		if ($this->useBlocks)
		{
			$model->setCuration();
			$complete = $model->_curationModel->_progress->complete;

			// Require DOI?
			$requireDoi = isset($model->_curationModel->_manifest->params->require_doi)
						? $model->_curationModel->_manifest->params->require_doi : 0;
		}
		else
		{
			// Checked areas
			$checked = $this->_checkDraft( $model->_type->alias, $model->version, $version );

			// Check if all required areas are filled in
			$complete = $this->_checkPublicationPermit($checked, $model->_type->alias);

			$requireDoi = true;
		}

		// Make sure the publication belongs to the project
		if ($this->_project->id != $model->_project->id)
		{
			$this->_referer = Route::url($route);
			$this->_message = array(
				'message' => Lang::txt('Oups! The publication you are trying to change is hosted by another project.'),
				'type' => 'error');
			return;
		}

		// Check that version label was not published before
		$used_labels = $model->version->getUsedLabels( $pid, $version );
		if (!$model->version->version_label || in_array($model->version->version_label, $used_labels))
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VERSION_LABEL_USED') );
		}

		// Is draft complete?
		if (!$complete && $this->_task != 'revert')
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_ALLOWED') );
		}

		// Is revert allowed?
		$revertAllowed = $this->_pubconfig->get('graceperiod', 0);
		if ($revertAllowed && $model->version->state == 1
			&& $model->version->accepted && $model->version->accepted != '0000-00-00 00:00:00')
		{
			$monthFrom = JFactory::getDate($model->version->accepted . '+1 month')->toSql();
			if (strtotime($monthFrom) < strtotime(JFactory::getDate()))
			{
				$revertAllowed = 0;
			}
		}

		// Embargo?
		if ($pubdate)
		{
			$pubdate = $this->parseDate($pubdate);

			$tenYearsFromNow = JFactory::getDate(strtotime("+10 years"))->toSql();

			// Stop if more than 10 years from now
			if ($pubdate > $tenYearsFromNow)
			{
				$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ERROR_EMBARGO') );
			}
		}

		// Main version?
		$main = $this->_task == 'republish' ? $model->version->main : 1;
		$main_vid = $model->version->getMainVersionId($pid); // current default version

		// Save version before changes
		$originalStatus = $model->version->state;

		// Checks
		if ($this->_task == 'republish' && $model->version->state != 0)
		{
			// Can only re-publish unpublished version
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_CANNOT_REPUBLISH') );
		}
		elseif ($this->_task == 'revert' && $model->version->state != 5 && !$revertAllowed)
		{
			// Can only revert a pending resource
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_CANNOT_REVERT') );
		}

		// On error
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
			$this->_referer = $url;
			return;
		}

		// Determine state
		$state = 5; // Default - pending approval
		if ($this->_task == 'share' || $this->_task == 'revert')
		{
			$state = 4; // No approval needed
		}
		elseif ($this->_task == 'republish')
		{
			$state = 1; // No approval needed
		}
		else
		{
			$model->version->submitted = JFactory::getDate()->toSql();

			// Save submitter
			$pa = new \Components\Publications\Tables\Author( $this->_database );
			$pa->saveSubmitter($model->version->id, $submitter, $this->_project->id);

			if ($this->_pubconfig->get('autoapprove') == 1 )
			{
				$state = 1;
			}
			else
			{
				$apu = $this->_pubconfig->get('autoapproved_users');
				$apu = explode(',', $apu);
				$apu = array_map('trim',$apu);

				$juser = JFactory::getUser();
				if (in_array($juser->get('username'),$apu))
				{
					// Set status to published
					$state = 1;
				}
				else
				{
					// Set status to pending review (submitted)
					$state = 5;
				}
			}
		}

		// Save state
		$model->version->state 		= $state;
		$model->version->main 		= $main;
		if ($this->_task != 'revert')
		{
			$model->version->rating 		= '0.0';
			$model->version->published_up   = $this->_task == 'republish'
				? $model->version->published_up : JFactory::getDate()->toSql();
			$model->version->published_up  	= $pubdate ? $pubdate : $model->version->published_up;
			$model->version->published_down = '';
		}
		$model->version->modified    = JFactory::getDate()->toSql();
		$model->version->modified_by = $this->_uid;

		// Issue DOI
		if ($requireDoi > 0 && $this->_task == 'publish' && !$model->version->doi)
		{
			// Get DOI service
			$doiService = new \Components\Publications\Models\Doi($model);
			$extended = $state == 5 ? false : true;
			$doi = $doiService->register($extended);

			// Store DOI
			if ($doi)
			{
				$model->version->doi = $doi;
			}

			// Can't proceed without a valid DOI
			if (!$doi || $doiService->getError())
			{
				$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_ERROR_DOI')
					. ' ' . $doiService->getError());
			}
		}

		// Proceed if no error
		if (!$this->getError())
		{
			if ($this->useBlocks && $state == 1)
			{
				$model->version->curation = json_encode($this->model->_curationModel->_manifest);
			}

			// Save data
			if (!$model->version->store())
			{
				throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_FAILED'), 403);
				return;
			}

			// Remove main flag from previous default version
			if ($main && $main_vid && $main_vid != $model->version->id)
			{
				$model->version->removeMainFlag($main_vid);
			}

			// Mark as curated
			if ($this->useBlocks)
			{
				$model->version->saveParam($model->version->id, 'curated', 1);
			}
		}

		// OnAfterPublish
		$this->onAfterChangeState( $model, $model->version, $originalStatus );

		// Redirect
		$this->_referer = $url;
		return;
	}

	/**
	 * On after change status
	 *
	 * @return     string
	 */
	public function onAfterChangeState( $pub, $row, $originalStatus = 3 )
	{
		$state  = $row->state;
		$notify = 1; // Notify administrators/curators?

		// Log activity in curation history
		if (isset($pub->_curationModel))
		{
			$pub->_curationModel->saveHistory($pub, $this->_uid, $originalStatus, $state, 0 );
		}

		// Display status message
		switch ($state)
		{
			case 1:
			default:
				$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_SUCCESS_PUBLISHED');
				$action 	= $this->_task == 'republish'
							? Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_REPUBLISHED')
							: Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_PUBLISHED');
				break;

			case 4:
				$this->_msg = $this->_task == 'revert'
							? Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_SUCCESS_REVERTED')
							: Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_SUCCESS_SAVED') ;
				$action 	= $this->_task == 'revert'
							? Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_REVERTED')
							: Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_SAVED');
				$notify = 0;
				break;

			case 5:
				$this->_msg = $originalStatus == 7
							? Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_SUCCESS_PENDING_RESUBMITTED')
							: Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_SUCCESS_PENDING');
				$action 	= $originalStatus == 7
							? Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_RESUBMITTED')
							: Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_ACTIVITY_SUBMITTED');
				break;
		}
		$this->_msg .= ' <a href="'.Route::url('index.php?option=com_publications' . a .
			    'id=' . $row->publication_id ) .'">'. Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VIEWIT').'</a>';

		$pubtitle = \Hubzero\Utility\String::truncate($row->title, 100);
		$action .= ' ' . $row->version_label . ' ';
		$action .=  Lang::txt('PLG_PROJECTS_PUBLICATIONS_OF_PUBLICATION') . ' "' . html_entity_decode($pubtitle).'"';
		$action  = htmlentities($action, ENT_QUOTES, "UTF-8");

		// Record activity
		if (!$this->_project->provisioned && !$this->getError())
		{
			$objAA = new \Components\Projects\Tables\Activity ( $this->_database );
			$aid = $objAA->recordActivity( $this->_project->id, $this->_uid,
					$action, $row->publication_id, $pubtitle,
					Route::url('index.php?option=' . $this->_option . a .
					'alias=' . $this->_project->alias . '&active=publications' . a .
					'pid=' . $row->publication_id) . '/?version=' . $row->version_number,
					'publication', 1 );
		}

		// Send out notifications
		$profile = \Hubzero\User\Profile::getInstance($this->_uid);
		$actor 	 = $profile
				? $profile->get('name')
				: Lang::txt('PLG_PROJECTS_PUBLICATIONS_PROJECT_MEMBER');
		$juri 	 = JURI::getInstance();
		$sef	 = 'publications' . DS . $row->publication_id . DS . $row->version_number;
		$link 	 = rtrim($juri->base(), DS) . DS . trim($sef, DS);
		$message = $actor . ' ' . html_entity_decode($action) . '  - ' . $link;

		// Notify admin group
		if ($notify)
		{
			$admingroup = $this->_config->get('admingroup', '');
			$group = \Hubzero\User\Group::getInstance($admingroup);
			$admins = array();

			if ($admingroup && $group)
			{
				$members 	= $group->get('members');
				$managers 	= $group->get('managers');
				$admins 	= array_merge($members, $managers);
				$admins 	= array_unique($admins);

				\Components\Projects\Helpers\Html::sendHUBMessage(
					'com_projects',
					$this->_config,
					$this->_project,
					$admins,
					Lang::txt('COM_PROJECTS_EMAIL_ADMIN_NEW_PUB_STATUS'),
					'projects_new_project_admin',
					'publication',
					$message
				);
			}

			// Notify curators by email
			if ($this->useBlocks && isset($pub->_type))
			{
				$curatorMessage = ($state == 5) ? $message . "\n" . "\n" . Lang::txt('PLG_PROJECTS_PUBLICATIONS_EMAIL_CURATORS_REVIEW') . ' ' . rtrim($juri->base(), DS) . DS . 'publications/curation' : $message;

				$curatorgroups = array($pub->_type->curatorgroup);
				if ($this->_pubconfig->get('curatorgroup', ''))
				{
					$curatorgroups[] = $this->_pubconfig->get('curatorgroup', '');
				}
				$admins = array();
				foreach ($curatorgroups as $curatorgroup)
				{
					if (trim($curatorgroup) && $group = \Hubzero\User\Group::getInstance($curatorgroup))
					{
						$members 	= $group->get('members');
						$managers 	= $group->get('managers');
						$admins 	= array_merge($members, $managers, $admins);
						$admins 	= array_unique($admins);
					}
				}
				\Components\Publications\Helpers\Html::notify(
					$this->_pubconfig,
					$pub,
					$admins,
					Lang::txt('PLG_PROJECTS_PUBLICATIONS_EMAIL_CURATORS'),
					$curatorMessage
				);
			}
		}

		// Notify project managers (in all cases)
		$objO = new \Components\Projects\Tables\Owner($this->_database);
		$managers = $objO->getIds($this->_project->id, 1, 1);
		if (!$this->_project->provisioned && !empty($managers))
		{
			\Components\Projects\Helpers\Html::sendHUBMessage(
				'com_projects',
				$this->_config,
				$this->_project,
				$managers,
				Lang::txt('COM_PROJECTS_EMAIL_MANAGERS_NEW_PUB_STATUS'),
				'projects_admin_notice',
				'publication',
				$message
			);
		}

		// Produce archival package
		if (isset($pub->_curationModel) && ($state == 1 || $state == 5))
		{
			$pub->_curationModel->package();
		}
		elseif (!$this->useBlocks)
		{
			// Finalize attachments for publication
			$published = $this->_publishAttachments($row);

			// Produce archival package
			$this->archivePub($row->publication_id, $row->id);
		}

		// Pass success or error message
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
		}
		elseif (isset($this->_msg) && $this->_msg)
		{
			$this->_message = array('message' => $this->_msg, 'type' => 'success');
		}

		return;
	}

	/**
	 * Parse embargo date
	 *
	 * @return     string
	 */
	public function parseDate( $pubdate )
	{
		$date = explode('-', $pubdate);
		if (count($date) == 3)
		{
			$year 	= $date[0];
			$month 	= $date[1];
			$day 	= $date[2];
			if (intval($month) && intval($day) && intval($year))
			{
				if (strlen($day) == 1)
				{
					$day = '0' . $day;
				}

				if (strlen($month) == 1)
				{
					$month = '0' . $month;
				}
				if (checkdate($month, $day, $year))
				{
					$pubdate = JFactory::getDate(gmmktime(0, 0, 0, $month, $day, $year))->toSql();
				}
				// Prevent date before current
				if ($pubdate < JFactory::getDate()->toSql())
				{
					$pubdate = JFactory::getDate()->toSql();
				}
			}
		}

		return $pubdate;
	}

	/**
	 * Unpublish version/delete draft
	 *
	 * @return     string
	 */
	public function cancelDraft()
	{
		// Incoming
		$pid 		= $this->_pid ? $this->_pid : Request::getInt('pid', 0);
		$confirm 	= Request::getInt('confirm', 0);
		$version 	= Request::getVar('version', 'default');
		$ajax 		= Request::getInt('ajax', 0);

		// Determine redirect path
		$route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$url = Route::url($route . '&pid=' . $pid);

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );

		// If publication not found, raise error
		$pub = $objP->getPublication($pid, $version, $this->_project->id);
		if (!$pub)
		{
			if ($pid)
			{
				$this->_referer = $url;
				return;
			}
			else
			{
				throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'), 404);
				return;
			}
		}

		// Instantiate publication version
		$row = new \Components\Publications\Tables\Version( $this->_database );
		if (!$row->loadVersion($pid, $version))
		{
			throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VERSION_NOT_FOUND'), 404);
			return;
		}

		// Save version ID
		$vid = $row->id;

		// Append breadcrumbs
		if (!$ajax)
		{
			Pathway::append(
				stripslashes($pub->title),
				$url
			);
		}

		// Can only unpublish published version or delete a draft
		if ($pub->state != 1 && $pub->state != 3 && $pub->state != 4)
		{
			$this->setError(Lang::txt('PLG_PROJECTS_PUBLICATIONS_CANT_DELETE'));
		}

		// Get published versions count
		$objV = new \Components\Publications\Tables\Version( $this->_database );
		$publishedCount = $objV->getPublishedCount($pid);

		// Unpublish/delete version
		if ($confirm)
		{
			if (!$this->getError())
			{
				$pubtitle = \Hubzero\Utility\String::truncate($row->title, 100);
				$objAA = new \Components\Projects\Tables\Activity ( $this->_database );

				if ($pub->state == 1)
				{
					// Unpublish published version
					$row->published_down 	= JFactory::getDate()->toSql();
					$row->modified 			= JFactory::getDate()->toSql();
					$row->modified_by 		= $this->_uid;
					$row->state 			= 0;

					if (!$row->store())
					{
						throw new Exception( Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_UNPUBLISH_FAILED'), 403);
						return;
					}
					else
					{
						$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_VERSION_UNPUBLISHED');

						// Add activity
						$action  = Lang::txt('PLG_PROJECTS_PUBLICATIONS_ACTIVITY_UNPUBLISHED');
						$action .= ' '.strtolower(Lang::txt('version')).' '.$row->version_label.' '
						.Lang::txt('PLG_PROJECTS_PUBLICATIONS_OF').' '.strtolower(Lang::txt('publication')).' "'
						.$pubtitle.'" ';

						$aid = $objAA->recordActivity( $this->_project->id, $this->_uid,
							   $action, $pid, $pubtitle,
							   Route::url('index.php?option=' . $this->_option . a .
							   'alias=' . $this->_project->alias . '&active=publications' . a .
							   'pid=' . $pid) . '/?version=' . $row->version_number, 'publication', 0 );
					}
				}
				elseif ($pub->state == 3 || $pub->state == 4)
				{
					$vlabel = $row->version_label;

					// Delete draft version
					if (!$row->delete())
					{
						throw new Exception( Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_DELETE_DRAFT_FAILED'), 403);
						return;
					}

					// Delete authors
					$pa = new \Components\Publications\Tables\Author( $this->_database );
					$authors = $pa->deleteAssociations($vid);

					// Delete attachments
					$pContent = new \Components\Publications\Tables\Attachment( $this->_database );
					$pContent->deleteAttachments($vid);

					// Delete screenshots
					$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
					$pScreenshot->deleteScreenshots($vid);

					jimport('joomla.filesystem.file');
					jimport('joomla.filesystem.folder');

					// Build publication path
					$path    =  JPATH_ROOT . DS . trim($this->_pubconfig->get('webpath'), DS)
							. DS .  \Hubzero\Utility\String::pad( $pid );

					// Build version path
					$vPath = $path . DS . \Hubzero\Utility\String::pad( $vid );

					// Delete all version files
					if (is_dir($vPath))
					{
						JFolder::delete($vPath);
					}

					// Delete access accosiations
					$pAccess = new \Components\Publications\Tables\Access( $this->_database );
					$pAccess->deleteGroups($vid);

					// Delete audience
					$pAudience = new \Components\Publications\Tables\Audience( $this->_database );
					$pAudience->deleteAudience($vid);

					// Delete publication existence
					if ($pub->versions == 0)
					{
						// Delete all files
						if (is_dir($path))
						{
							JFolder::delete($path);
						}

						$objP->delete($pid);
						$objP->deleteExistence($pid);
						$url  = Route::url($route);

						// Delete related publishing activity from feed
						$objAA = new \Components\Projects\Tables\Activity( $this->_database );
						$objAA->deleteActivityByReference($this->_project->id, $pid, 'publication');
					}

					// Add activity
					$action  = Lang::txt('PLG_PROJECTS_PUBLICATIONS_ACTIVITY_DRAFT_DELETED');
					$action .= ' '.$vlabel.' ';
					$action .=  Lang::txt('PLG_PROJECTS_PUBLICATIONS_OF_PUBLICATION').' "'.$pubtitle.'"';

					$aid = $objAA->recordActivity( $this->_project->id, $this->_uid,
						   $action, $pid, '', '', 'publication', 0 );

					$this->_msg = Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_DRAFT_DELETED');
				}
			}
		}
		else
		{
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'=>'projects',
					'element'=>'publications',
					'name'=>'cancel'
				)
			);

			// Output HTML
			$view->option 			= $this->_option;
			$view->database 		= $this->_database;
			$view->project 			= $this->_project;
			$view->uid 				= $this->_uid;
			$view->pid 				= $pid;
			$view->version 			= $version;
			$view->pub 				= $pub;
			$view->publishedCount 	= $publishedCount;
			$view->task 			= $this->_task;
			$view->config 			= $this->_config;
			$view->pubconfig 		= $this->_pubconfig;
			$view->ajax 			= $ajax;
			$view->route			= $route;
			$view->url 				= $url;
			$view->title		  	= $this->_area['title'];

			// Get messages	and errors
			$view->msg = $this->_msg;
			if ($this->getError())
			{
				$view->setError( $this->getError() );
			}
			return $view->loadTemplate();
		}

		// Pass success or error message
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
		}
		elseif (isset($this->_msg) && $this->_msg)
		{
			$this->_message = array('message' => $this->_msg, 'type' => 'success');
		}

		$url.= $version != 'default' ? '?version='.$version : '';
		$this->_referer = $url;
		return;

	}

	//----------------------------------------
	// Process steps
	//----------------------------------------

	/**
	 * Edit content
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	protected function _editContent()
	{
		// Incoming
		$pid 		= Request::getInt( 'pid', 0 );
		$vid 		= Request::getInt( 'vid', 0 );

		$ajax 		= Request::getInt('ajax', 0);
		$no_html 	= Request::getInt('no_html', 0);
		$move 		= Request::getInt('move', 0);
		$role 		= Request::getInt('role', 0);

		$item 		= urldecode(Request::getVar( 'item', '' ));
		$parts 		= explode('::', $item);
		$type 		= array_shift($parts);
		$type 		= strtolower($type);
		$item 		= array_pop($parts);

		if (!$vid || !$pid)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_ERROR_EDIT_CONTENT'));
		}
		if (!$item)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_ERROR_EDIT_CONTENT'));
		}

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'edititem'
			)
		);

		// Get attachment information
		$row= new \Components\Publications\Tables\Attachment( $this->_database );
		$row->loadAttachment($vid, $item );

		// Build pub url
		$view->route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $pid);

		$view->row 		= $row;
		$view->item 	= $item;
		$view->type 	= $type;
		$view->role		= $role;
		$view->vid 		= $vid;
		$view->pid 		= $pid;
		$view->ajax 	= $ajax;
		$view->move 	= $move;
		$view->no_html 	= $no_html;
		$view->option 	= $this->_option;
		$view->project 	= $this->_project;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		$view->msg = isset($this->_message) ? $this->_message : '';
		return $view->loadTemplate();
	}

	/**
	 * Save content
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	protected function _saveContent()
	{
		// Incoming
		$pid 		= Request::getInt( 'pid', 0 );
		$vid 		= Request::getInt( 'vid', 0 );

		$ajax 		= Request::getInt('ajax', 0);
		$no_html 	= Request::getInt('no_html', 0);
		$move 		= Request::getInt('move', 0);
		$title 		= Request::getVar('title', '');
		$role 		= Request::getInt('role', 0);

		$item 		= urldecode(Request::getVar( 'item', '' ));
		$type 		= Request::getVar('type', 'file');

		if (!$vid || !$pid || !$item)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_ERROR_EDIT_CONTENT'));
		}

		// Get version label
		$row = new \Components\Publications\Tables\Version( $this->_database );
		$row->load($vid);
		$version = $row->version_number;

		// Save any changes to selections/ordering
		$selections = Request::getVar( 'selections', '', 'post' );
		$selections = $this->_parseSelections($selections);
		$this->_processContent( $pid, $vid, $selections, $role );

		$now = JFactory::getDate()->toSql();

		$objPA = new \Components\Publications\Tables\Attachment( $this->_database );
		if ($objPA->loadAttachment( $vid, $item ))
		{
			if ($title && $objPA->title != $title)
			{
				$objPA->modified = $now;
				$objPA->modified_by = $this->_uid;
			}
			$objPA->title = $title;
		}
		else
		{
			$objPA 							= new \Components\Publications\Tables\Attachment( $this->_database );
			$objPA->publication_id 			= $pid;
			$objPA->publication_version_id 	= $vid;
			$objPA->path 					= $item;
			$objPA->type 					= $type;
			$objPA->created_by 				= $this->_uid;
			$objPA->created 				= JFactory::getDate()->toSql();
			$objPA->title 					= $title ? $title : '';
			$objPA->role 					= $role;
		}

		// Pass success or error message
		if ($objPA->store())
		{
			$this->_message = array('message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_ITEM_SAVED'), 'type' => 'success');
		}
		else
		{
			$this->_message = array('message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_CONTENT_ERROR_SAVING_ITEM'), 'type' => 'error');
		}

		// Redirect
		$url = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$url = Route::url($url . '&pid=' . $pid);

		$url .= '?section=content&primary='.$role;
		$url .= '&version='.$version;
		$url .= $move ? '&move=' . $move : '';

		// Redirect
		$this->_referer = $url;
		return;
	}

	/**
	 * Show content item full info (AJAX)
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	protected function _loadContentItem()
	{
		// Incoming
		$pid 	= Request::getInt( 'pid', 0 );
		$vid 	= Request::getInt( 'vid', 0 );
		$role 	= Request::getInt('role', 0);
		$move 	= Request::getInt('move', 0);
		$item 	= urldecode(Request::getVar( 'item', '' ));

		$parts = explode('::', $item);
		$type = array_shift($parts);
		$type = strtolower($type);
		$item = array_pop($parts);
		$hash = '';

		if (!$type || !$item)
		{
			return '<p class="error">' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_ERROR_LOAD_ITEM') . '</p>';
		}

		// Contribute process outside of projects
		if (!is_object($this->_project) or !$this->_project->id)
		{
			$this->_project = new \Components\Projects\Tables\Project( $this->_database );
			$this->_project->provisioned = 1;
		}

		// Build pub url
		$route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$url = Route::url($route . '&pid=' . $pid);

		// Get attachment info
		$att = new \Components\Publications\Tables\Attachment( $this->_database );
		$att->loadAttachment($vid, $item, $type );

		// Get project file path
		$project_path = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);

		$canedit = (!is_object($this->_project) or !$this->_project->id) ? 0 : 1;

		// Draw item
		$itemHtml = $this->_pubTypeHelper->dispatchByType($type, 'drawItem',
		$data = array(
				'att' 		=> $att,
				'item'		=> $item,
				'canedit' 	=> $canedit,
				'pid' 		=> $pid,
				'vid'		=> $vid,
				'url'		=> $url,
				'option'	=> $this->_option,
				'move'		=> $move,
				'role'		=> $role,
				'path'		=> $project_path
		));

		return $itemHtml;
	}

	/**
	 * Show content options (AJAX)
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	protected function _showOptions()
	{
		// Incoming
		$pid 	= Request::getInt( 'pid', 0 );
		$vid 	= Request::getInt( 'vid', 0 );
		$picked = Request::getVar( 'serveas', '' );
		$base 	= Request::getVar( 'base', '' );

		// Contribute process outside of projects
		if (!is_object($this->_project) or !$this->_project->id)
		{
			$this->_project = new \Components\Projects\Tables\Project( $this->_database );
			$this->_project->provisioned = 1;
		}

		// Instantiate pub attachment
		$objPA = new \Components\Publications\Tables\Attachment( $this->_database );

		// Get selections
		$selections = Request::getVar( 'selections', '');
		$selections = $this->_parseSelections($selections);

		// Allowed choices
		$options = array('download', 'tardownload', 'inlineview', 'invoke', 'video', 'external');

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'primaryoptions'
			)
		);

		$view->used = NULL;
		$view->cStatus = NULL;

		if (!$this->_project->provisioned)
		{
			// Check if selections are the same as in another publication
			$view->used = $this->_pubTypeHelper->dispatch($base, 'checkDuplicate',
				$data = array('pid' => $pid, 'selections' => $selections));

			// Check if selections are of the right status to publish
			$view->cStatus = $this->_pubTypeHelper->dispatch($base, 'checkContentStatus',
				$data = array('pid' => $pid, 'selections' => $selections));
		}

		$view->duplicateV = NULL;
		$view->original_serveas = '';

		// Get original content
		if ($vid)
		{
			$original = $objPA->getAttachments($vid, $filters = array('role' => 1));
			if ($original)
			{
				$params = new JParameter( $original[0]->params );
				$view->original_serveas = $params->get('serveas');
			}

			// Check against duplication
			$view->duplicateV = $this->_pubTypeHelper->dispatch($base, 'checkVersionDuplicate',
				$data = array('vid' => $vid, 'pid' => $pid, 'selections' => $selections));
		}

		// Get serveas and choices depending on content selection
		$serve = $this->_pubTypeHelper->dispatch($base, 'getServeAs',
			$data = array('vid' => $vid, 'selections' => $selections,
				'original_serveas' => $view->original_serveas, 'params' => $this->params));

		$serveas = ($serve && isset($serve['serveas'])) ? $serve['serveas'] : 'external';
		$view->choices = ($serve && isset($serve['choices'])) ? $serve['choices'] : array();

		// Something got picked?
		if ($picked && in_array($picked, $options))
		{
			$serveas = $picked;
		}

		// Build pub url
		$view->route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias
					. '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $pid);

		$view->selections 	= $selections;
		$view->serveas 		= $serveas;
		$view->pid 			= $pid;
		$view->picked 		= $picked;
		$view->base 		= $base;
		$view->option 		= $this->_option;
		$view->project 		= $this->_project;

		// Get type helper
		$view->_pubTypeHelper = $this->_pubTypeHelper->dispatch($base, 'getHelper');

		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Process content
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      integer  	$pid
	 * @param      integer  	$vid
	 * @param      array  		$selections
	 * @param      integer  	$primary
	 * @param      string  		$secret
	 * @param      integer  	$state
	 * @param      integer  	$newpub			Is this a new publication?
	 * @param      boolean  	$update_hash
	 *
	 * @return     integer
	 */
	protected function _processContent( $pid, $vid, $selections, $primary,
			$secret = '', $state = 0, $newpub = 0, $update_hash = 1 )
	{
		// Incoming
		$serveas = Request::getVar('serveas', '');

		$added = 0;

		$objPA = new \Components\Publications\Tables\Attachment( $this->_database );

		// Get original content
		$filters = array();
		$filters['select'] = 'a.path, a.type, a.object_name';
		$filters['role'] = $primary ? 1 : '0';
		$original = $objPA->getAttachments($vid, $filters);

		// Get attachment types
		$types = $this->_pubTypeHelper->getTypes();

		// Save attachments
		foreach ($types as $base)
		{
			$added = $this->_pubTypeHelper->dispatch($base, 'saveAttachments',
					$data = array(
						'selections'	=> $selections,
						'pid' 			=> $pid,
						'vid'			=> $vid,
						'uid'			=> $this->_uid,
						'option'		=> $this->_option,
						'update_hash'	=> $update_hash,
						'newpub'		=> $newpub,
						'state'			=> $state,
						'secret'		=> $secret,
						'primary'		=> $primary,
						'added'			=> $added,
						'serveas'		=> $serveas
					));
		}

		// Delete attachments if not selected
		if (count($original) > 0)
		{
			foreach ($original as $old)
			{
				$this->_pubTypeHelper->dispatchByType($old->type, 'cleanupAttachments',
						$data = array(
							'selections'	=> $selections,
							'pid' 			=> $pid,
							'vid'			=> $vid,
							'uid'			=> $this->_uid,
							'secret'		=> $secret,
							'old'			=> $old
				));
			}
		}

		return $added;
	}

	/**
	 * Publish attachments
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      object  		$row
	 * @param      string  		$which
	 *
	 * @return     integer
	 */
	protected function _publishAttachments($row, $which = 'all')
	{
		$published = 0;

		// Set filters
		$filters = array();
		if ($which != 'all')
		{
			$filters['role'] = $which == 'primary' ? 1 : '0';
		}

		// Get attachments
		$pContent = new \Components\Publications\Tables\Attachment( $this->_database );
		$attachments = $pContent->getAttachments( $row->id, $filters);

		// Do we have attachments to publish?
		if (!$attachments || empty($attachments))
		{
			return false;
		}

		// Get relevant attachment types
		$types = array();
		foreach ($attachments as $att)
		{
			if (!in_array($att->type, $types))
			{
				$types[] = $att->type;
			}
		}

		// Publish attachments
		foreach ($types as $type)
		{
			$published = $this->_pubTypeHelper->dispatchByType(
				$type,
				'publishAttachments',
				$data = array(
					'attachments'	=> $attachments,
					'row' 			=> $row,
					'uid'			=> $this->_uid
				)
			);
		}

		return $published;
	}

	/**
	 * Process authors
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      integer  	$vid
	 * @param      array  		$selections
	 *
	 * @return     boolean
	 */
	protected function _processAuthors( $vid, $selections )
	{
		$pAuthor = new \Components\Publications\Tables\Author( $this->_database );
		$now = JFactory::getDate()->toSql();

		// Get original authors
		$oauthors = $pAuthor->getAuthors($vid, 2);

		// Save/update authors
		$order = 1;
		foreach ($selections as $sel)
		{
			if ($sel == '' || intval($sel) == 0) {
				continue;
			}
			if ($pAuthor->loadAssociationByOwner($sel, $vid))
			{
				if ($pAuthor->ordering != $order) {
					$pAuthor->modified = $now;
					$pAuthor->modified_by = $this->_uid;
				}

				$pAuthor->ordering = $order;
				$pAuthor->status = 1;
				if ($pAuthor->updateAssociationByOwner())
				{
					$order++;
				}
			}
			else
			{
				$pAuthor = new \Components\Publications\Tables\Author( $this->_database );

				$profile = $pAuthor->getProfileInfoByOwner($sel);
				$invited = $profile->invited_name ? $profile->invited_name : $profile->invited_email;

				$firstName = '';
				$lastName  = '';

				$pAuthor->project_owner_id = $sel;
				$pAuthor->publication_version_id = $vid;
				$pAuthor->user_id = $profile->uidNumber ? $profile->uidNumber : 0;
				$pAuthor->ordering = $order;
				$pAuthor->status = 1;
				$pAuthor->organization = $profile->organization ? $profile->organization : '';
				$pAuthor->name = $profile && $profile->name ? $profile->name : $invited;
				$pAuthor->firstName = $firstName;
				$pAuthor->lastName = $lastName;
				$pAuthor->created = $now;
				$pAuthor->created_by = $this->_uid;
				if (!$pAuthor->createAssociation())
				{
					continue;
				}
				else
				{
					$order++;
				}
			}
		}

		// Delete authors if not selected
		if (count($oauthors) > 0)
		{
			foreach ($oauthors as $old)
			{
				if (!in_array($old->project_owner_id, $selections))
				{
					$pAuthor->deleteAssociationByOwner($old->project_owner_id, $vid);
				}
			}
		}
		return true;
	}

	/**
	 * Show author (AJAX)
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string (html)
	 */
	protected function _showAuthor()
	{
		// Incoming
		$uid 		= Request::getInt('uid', 0);
		$vid 		= Request::getInt('vid', 0);
		$owner		= Request::getInt('owner', 0);
		$move 		= Request::getInt('move', 0);

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'author'
			)
		);

		// Get author information
		$pAuthor 		= new \Components\Publications\Tables\Author( $this->_database );
		$view->author 	= $pAuthor->getAuthorByOwnerId($vid, $owner);
		$view->owner 	= $owner;
		$view->order 	= $pAuthor->getCount($vid);

		// Build pub url
		$view->route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias
			. '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $this->_pid);

		$view->project 	= $this->_project;
		$view->pid 		= $this->_pid;
		$view->vid 		= $vid;
		$view->option 	= $this->_option;
		$view->move 	= $move;
		$view->canedit	= 1;
		return $view->loadTemplate();
	}

	/**
	 * Edit author view
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	protected function _editAuthor()
	{
		// AJAX
		// Incoming
		$uid 		= Request::getInt('uid', 0);
		$vid 		= Request::getInt('vid', 0);
		$pid 		= Request::getInt('pid', 0);
		$ajax 		= Request::getInt('ajax', 0);
		$no_html 	= Request::getInt('no_html', 0);
		$move 		= Request::getInt('move', 0);
		$owner 		= Request::getInt('owner', 0);
		$new 		= Request::getVar('new', '');

		if (!$vid || !$pid)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_ERROR_EDIT_AUTHOR'));
		}
		if (!$uid && !$owner && !$new)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_ERROR_EDIT_AUTHOR'));
		}

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'editauthor'
			)
		);

		// Get author information
		$pAuthor = new \Components\Publications\Tables\Author( $this->_database );
		$view->author = $pAuthor->getAuthorByOwnerId($vid, $owner);

		if (!$view->author)
		{
			if ($owner)
			{
				 $this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_ERROR_EDIT_AUTHOR'));
			}
			else
			{
				$view->author = new \Components\Publications\Tables\Author( $this->_database );
				$view->author->p_name 			= '';
				$view->author->p_organization 	= '';
				$view->author->invited_name 	= '';
				$view->author->invited_email 	= '';
				$view->author->givenName 		= '';
				$view->author->surname 			= '';
				$view->author->picture 			= '';
				$view->author->username 		= '';

				// Are we adding someone new?
				if ($new)
				{
					$newm = explode(',' , $new);
					$new  = trim($newm[0]);

					// Are we adding a registered user?
					$parts =  preg_split("/[(]/", $new);
					if (count($parts) == 2)
					{
						$name = $parts[0];
						$uid = preg_replace('/[)]/', '', $parts[1]);
						$uid = is_numeric($uid) ? $uid : '';
					}
					elseif (intval($new))
					{
						$uid = $new;
					}
					else
					{
						// Instantiate a new registration object
						include_once(JPATH_ROOT . DS . 'components' . DS . 'com_members' . DS . 'models' . DS . 'registration.php');
						$xregistration = new MembersModelRegistration();

						$regex = '/^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-]+)+/';
						// Is this an email?
						if (preg_match($regex, strtolower($new)))
						{
							$uid = $xregistration->getEmailId(strtolower($new));
							$view->author->invited_email = strtolower($new);
						}
						else
						{
							// This must be a name
							$view->author->p_name = $new;
							$view->author->invited_name = $new;
						}
					}

					if ($uid)
					{
						// Owner already?
						$author = $pAuthor->getAuthorByUid($vid, $uid);

						if ($author)
						{
							$view->author 	= $author;
							$view->owner 	=  $author->project_owner_id;
						}
						else
						{
							$profile = \Hubzero\User\Profile::getInstance($uid);
							$view->author->givenName 		= $profile->get('givenName');
							$view->author->surname 			= $profile->get('surname');
							$view->author->picture 			= $profile->get('picture');
							$view->author->username 		= $profile->get('username');
							$view->author->p_name 			= $profile->get('name');
							$view->author->p_organization 	= $profile->get('organization');
						}
					}
				}
			}
		}

		// Build pub url
		$view->route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $pid);

		$view->uid 		= $uid;
		$view->vid 		= $vid;
		$view->pid 		= $pid;
		$view->owner 	= $owner;
		$view->ajax 	= $ajax;
		$view->move 	= $move;
		$view->no_html 	= $no_html;
		$view->option 	= $this->_option;
		$view->project 	= $this->_project;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		$view->msg = isset($this->_message) ? $this->_message : '';
		return $view->loadTemplate();
	}

	/**
	 * Save author info
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return   void (redirect)
	 */
	protected function _saveAuthor()
	{
		// Incoming
		$uid 		= Request::getInt(	'uid', 0);
		$vid 		= Request::getInt( 'vid', 0 );
		$pid 		= Request::getInt( 'pid', 0 );
		$email 		= Request::getVar( 'email', '', 'post' );
		$firstName 	= Request::getVar( 'firstName', '', 'post' );
		$lastName 	= Request::getVar( 'lastName', '', 'post' );
		$org 		= Request::getVar( 'organization', '', 'post' );
		$credit 	= Request::getVar( 'credit', '', 'post' );
		$move 		= Request::getInt( 'move', 0 );
		$owner 		= Request::getInt(	'owner', 0);
		$new 		= 0;
		$sendInvite = 0;

		$regex = '/^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_-])+(.[a-zA-Z0-9_-]+)+/';

		// Save any changes to selections/ordering
		$selections = Request::getVar('selections', '', 'post');
		$selections = explode("##", $selections);
		if (count($selections) > 0 && trim($selections[0]) != '')
		{
			$this->_processAuthors($vid, $selections);
		}

		$now = JFactory::getDate()->toSql();
		if (!$vid)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_ERROR_EDIT_AUTHOR'));
		}

		// Get owner class
		$objO = new \Components\Projects\Tables\Owner( $this->_database );

		// Instantiate a new registration object
		include_once(JPATH_ROOT . DS . 'components' . DS . 'com_members' . DS . 'models' . DS . 'registration.php');
		$xregistration = new MembersModelRegistration();

		// Get current owners
		$owners = $objO->getIds($this->_project->id, 'all', 1);

		// Save new owner (or find existing owner by uid/email)
		if (!$owner)
		{
			$email = preg_match($regex, $email) ? $email : '';
			$name = $firstName . ' ' . $lastName;

			if ($email && !$uid)
			{
				// Do we have a registered user with this email?
				$uid = $xregistration->getEmailId($email);
			}

			// Check that profile exists
			if ($uid)
			{
				$profile = \Hubzero\User\Profile::getInstance($uid);
				$uid = $profile->get('uidNumber') ? $uid : 0;
			}

			if ($uid)
			{
				$owner = $objO->getOwnerId($this->_project->id, $uid);
			}
			elseif ($email)
			{
				$owner = $objO->checkInvited( $this->_project->id, $email );
			}
			else
			{
				$owner = $objO->checkInvitedByName( $this->_project->id, trim($name));
			}

			if ($owner && $objO->load($owner))
			{
				if ($email && $objO->invited_email != $email)
				{
					$sendInvite = 1;
				}
				$objO->status = $objO->userid ? 1 : 0;
				$objO->invited_name = $objO->userid ? $objO->invited_name : $name;
				$objO->invited_email = $objO->userid ? $objO->invited_email : $email;
				$objO->store();
			}
			elseif ($email || trim($name))
			{
				// Generate invitation code
				$code = \Components\Projects\Helpers\Html::generateCode();
				$objO->projectid = $this->_project->id;
				$objO->userid = $uid;
				$objO->status = $uid ? 1 : 0;
				$objO->added = JFactory::getDate()->toSql();
				$objO->role = 2;
				$objO->invited_email = $email;
				$objO->invited_name = $name;
				$objO->store();
				$owner = $objO->id;
				$sendInvite = $email ? 1 : 0;
			}

			$new = 1;
		}

		// Not part of team - throw an error
		if (!$owner)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_ERROR_EDIT_AUTHOR'));
		}

		// Get version label
		$row = new \Components\Publications\Tables\Version( $this->_database );
		$row->load($vid);
		$version = $row->version_number;

		// Get author information
		$pAuthor = new \Components\Publications\Tables\Author( $this->_database );
		$exists = 0;
		if ($pAuthor->loadAssociationByOwner( $owner, $vid ))
		{
			$pAuthor->modified = $now;
			$pAuthor->modified_by = $this->_uid;
			$exists= 1;
		}
		else
		{
			$pAuthor->created = $now;
			$pAuthor->created_by = $this->_uid;
			$pAuthor->publication_version_id = $vid;
			$pAuthor->project_owner_id = $owner;
			$pAuthor->user_id = intval($uid);
			$pAuthor->ordering = $pAuthor->getLastOrder($vid) + 1;
		}
		$pAuthor->status = 1;

		// Get info from user profile (if registered) or project owner profile (if invited)
		$profile = $pAuthor->getProfileInfoByOwner($owner);

		// Get default name
		$default_invited = $profile->invited_name
		? $profile->invited_name : $profile->invited_email;
		$default_name = $profile && $profile->name ? $profile->name : $default_invited;
		$name = '';

		// Determine first and last names from default name
		if ($profile->uidNumber)
		{
			$nameParts 		= explode(" ", $profile->name);
			$part_lastname  = end($nameParts);
			$part_firstname = count($nameParts) > 1 ? $nameParts[0] : '';
		}
		else
		{
			$nameParts 		= explode(" ", $profile->invited_name);
			$part_lastname  = end($nameParts);
			$part_firstname = count($nameParts) > 1 ? $nameParts[0] : '';
		}
		$default_firstname 	= $profile && $profile->givenName ? $profile->givenName : $part_firstname ;
		$default_lastname 	= $profile && $profile->surname ? $profile->surname : $part_lastname;

		$saved = 0;
		if (!$this->getError())
		{
			$pAuthor->organization = $org ? $org : $profile->organization;
			if (!$firstName && !$lastName)
			{
				$pAuthor->firstName = $default_firstname;
				$pAuthor->lastName = $default_lastname;
			}
			else
			{
				$pAuthor->firstName = $firstName;
				$pAuthor->lastName = $lastName;
			}
			// Make up name from first and last name
			$name = $pAuthor->firstName . ' ' . $pAuthor->lastName;

			$pAuthor->name = $pAuthor->firstName && $pAuthor->lastName ? $name : $default_name;
			$pAuthor->credit = $credit;

			// Save new info
			if ($exists) {
				if ($pAuthor->updateAssociationByOwner())
				{
					$saved = 1;
				}
			}
			else
			{
				if ($pAuthor->createAssociation())
				{
					$saved = 1;
				}
			}
		}

		// Update project owner (invited)
		if (!$new && !$profile->uidNumber && $objO->load($owner))
		{
			$update = 0;
			$user   = 0;

			// Save email only if valid and new
			if ($email)
			{
				if (preg_match($regex, $email))
				{
					$invitee = $objO->checkInvited( $this->_project->id, $email );

					// Do we have a registered user with this email?
					$user = $xregistration->getEmailId($email);

					// Duplicate? - stop
					if ($invitee && $invitee != $owner)
					{
						// Stop
					}
					elseif (in_array($user, $owners))
					{
						// Stop
					}
					elseif ($email != $objO->invited_email)
					{
						$objO->invited_email = $email;
						$objO->userid = $objO->userid ? $objO->userid : $user;
						$update = 1;
						$sendInvite = 1;
					}
				}
			}
			if ($update || $name)
			{
				$objO->invited_name = $name;
				$objO->store();
			}
		}

		// (Re)send email invitation
		if ($sendInvite && $email)
		{
			// TBD
		}

		// Pass success or error message
		if ($saved)
		{
			$this->_message = array('message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_INFO_SAVED'), 'type' => 'success');
		}
		elseif (!$this->getError())
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUTHORS_ERROR_SAVING_AUTHOR_INFO'));
		}
		if ($this->getError())
		{
			$this->_message = array('message' => $this->getError(), 'type' => 'error');
		}

		// Redirect
		$url  = $this->_project->provisioned
			  ? 'index.php?option=com_publications&task=submit'
			  : 'index.php?option=com_projects'
			  . '&alias=' . $this->_project->alias . '&active=publications';
		$url  = Route::url($url . '&pid=' . $pid);
		$url .= '?section=authors';
		$url .= '&version='.$version;
		$url .= $move ? '&move=' . $move : '';

		// Redirect
		$this->_referer = $url;
		return;
	}

	/**
	 * Process metadata
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      integer  	$rtype
	 *
	 * @return     string
	 */
	protected function _processMetadata( $rtype = 0 )
	{
		// Incoming
		$rtype = $rtype ? $rtype : Request::getInt( 'rtype', 0 );
		$nbtag = Request::getVar( 'nbtag', array(), 'request', 'array' );
		$metadata = '';

		// Get custom areas, add wrapper tags, and compile into fulltext
		$cat = new \Components\Publications\Tables\Category( $this->_database );
		$cat->load( $rtype );

		$fields = array();
		if (trim($cat->customFields) != '')
		{
			$fs = explode("\n", trim($cat->customFields));
			foreach ($fs as $f)
			{
				$fields[] = explode('=', $f);
			}
		}

		foreach ($nbtag as $tagname=>$tagcontent)
		{
			$tagcontent = trim(stripslashes($tagcontent));
			if ($tagcontent != '')
			{
				$metadata .= "\n" . '<nb:' . $tagname . '>' . $tagcontent . '</nb:' . $tagname . '>' . "\n";
			} else
			{
				foreach ($fields as $f)
				{
					if ($f[0] == $tagname && end($f) == 1)
					{
						$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_REQUIRED_FIELD_CHECK', $f[1]) );
						return;
					}
				}
			}
		}
		return $metadata;
	}

	/**
	 * Process audience
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      integer  	$pid
	 * @param      integer  	$vid
	 *
	 * @return     boolean
	 */
	protected function _processAudience( $pid, $vid )
	{
		// Incoming
		$sel    = Request::getVar( 'audience', '', 'post' );
		$noshow = Request::getVar( 'no_audience', false, 'post' );

		$picked = array();
		$picked = explode('-', $sel);
		$result = 0;

		$pAudience = new \Components\Publications\Tables\Audience( $this->_database );
		if (!$pAudience->loadByVersion($vid))
		{
			$pAudience->publication_id = $pid;
			$pAudience->publication_version_id = $vid;
		}
		for ( $k = 0 ; $k <=5; $k++)
		{
			$lev = 'level'.$k;
			$pAudience->$lev = ($noshow || !in_array ( $lev, $picked)) ? 0 : 1;
			if (in_array ( $lev, $picked)) {
				$result = 1;
			}
		}

		if ($noshow == false && $result == 0)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUDIENCE_NO_SELECTIONS') );
			return false;
		}

		$pAudience->created = JFactory::getDate()->toSql();
		$pAudience->created_by = $this->_uid;

		if (!$pAudience->store())
		{
			$this->setError( $pAudience->getError() );
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Show audience
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string (html)
	 */
	protected function _showAudience()
	{
		// Incoming
		$sel    = Request::getVar( 'audience', '' );
		$noshow = Request::getVar( 'no_audience', false );

		$picked = array();
		$picked = explode('-', $sel);

		$pAudience = new \Components\Publications\Tables\Audience( $this->_database );
		$ral = new \Components\Publications\Tables\AudienceLevel( $this->_database );
		$levels = $ral->getLevels( 4, array(), 0 );
		$audience = array();
		$result = 0;

		// Build our object
		if (!empty($levels))
		{
			for ($k=0; $k < count($levels); $k++)
			{
				$label = 'label'.$k;
				$desc = 'desc'.$k;
				$lev = 'level'.$k;
				$pAudience->$label = $levels[$k]->title;
				$pAudience->$desc = $levels[$k]->description;
				if (in_array($lev, $picked))
				{
					$result = 1;
					$pAudience->$lev = 1;
				}
				else
				{
					$pAudience->$lev = 0;
				}
			}
		}

		if ($result == 1)
		{
			$view = new \Hubzero\Component\View(array(
				'base_path' => PATH_CORE . DS . 'components' . DS . 'com_publications' . DS . 'site',
				'name'      => 'view',
				'layout'    => '_audience',
			));
			$view->audience      = $pAudience;
			$view->showtips      = false;
			$view->numlevels     = 4;
			$view->hideEmpty     = false;
			return $view->loadTemplate();
		}
		else
		{
			return Lang::txt('PLG_PROJECTS_PUBLICATIONS_AUDIENCE_NOT_SHOWN');
		}
	}

	/**
	 * Process gallery
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      integer  	$pid
	 * @param      integer  	$vid
	 * @param      array		$selections
	 *
	 * @return     boolean
	 */
	protected function _processGallery( $pid, $vid, $selections )
	{
		$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
		$now = JFactory::getDate()->toSql();

		// Get original screenshots
		$originals = $pScreenshot->getScreenshots( $vid );

		// Get project file path
		$fpath = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);

		$prefix = $this->_config->get('offroot', 0) ? '' : PATH_APP ;
		$from_path = $prefix.$fpath;

		// Get screenshot path
		$webpath = $this->_pubconfig->get('webpath');
		$galleryPath = \Components\Publications\Helpers\Html::buildPubPath($pid, $vid, $webpath, 'gallery');

		if (isset($selections['files']) && count($selections['files']) > 0)
		{
			$ordering = 1;
			foreach ($selections['files'] as $file)
			{
				$file = urldecode($file);

				// Include Git Helper
				$this->_getGitHelper($fpath);

				// Get Git hash
				$hash = $this->_git->gitLog($file, '' , 'hash');
				$src = $this->_createScreenshot ( $file, $hash, $from_path, $galleryPath, 'name' );

				if ($pScreenshot->loadFromFilename($file, $vid))
				{
					$pScreenshot->ordering = $ordering;
				}
				elseif ($src)
				{
					$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
					$pScreenshot->filename = $file;
					$pScreenshot->srcfile = $src;
					$pScreenshot->publication_id = $pid;
					$pScreenshot->publication_version_id = $vid;
					$pScreenshot->title = basename($file);
					$pScreenshot->created = $now;
					$pScreenshot->created_by = $this->_uid;
					$pScreenshot->ordering = $ordering;
				}

				if ($pScreenshot->store())
				{
					$ordering++;
				}
			}
		}

		// Delete screenshots if not selected
		if (count($originals) > 0)
		{
			$selected = isset($selections['files']) && count($selections['files']) > 0	? $selections['files'] : array();

			jimport('joomla.filesystem.file');
			foreach ($originals as $old)
			{
				if (!in_array($old->filename, $selected))
				{
					$pScreenshot->deleteScreenshot($old->filename, $vid);

					// Clean up files
					$thumb = \Components\Projects\Helpers\Html::createThumbName($old->srcfile, '_tn', $extension = 'png');
					if (is_file(PATH_APP . $galleryPath . DS . $old->srcfile))
					{
						JFile::delete(PATH_APP . $galleryPath . DS . $old->srcfile);
					}
					if (is_file(PATH_APP . $galleryPath . DS . $thumb))
					{
						JFile::delete(PATH_APP . $galleryPath . DS . $thumb);
					}
				}
			}
		}

		// Path to publication thumb
		$pubPath = \Components\Publications\Helpers\Html::buildPubPath($pid, $vid, $webpath);
		$thumb = PATH_APP . $pubPath . DS . 'thumb.gif';

		// Get new screenshot list
		$updated = $pScreenshot->getScreenshots( $vid );

		// Remove pub thumbnail
		if (empty($updated) && is_file($thumb))
		{
			JFile::delete($thumb);
		}
		else
		{
			// Refresh publication thumbnail
			$firstScreenshot = empty($updated[0]) ? NULL : $updated[0];
			if (is_object($firstScreenshot) && is_file(PATH_APP . $galleryPath . DS . $firstScreenshot->srcfile))
			{
				if (is_file($thumb))
				{
					JFile::delete($thumb);
				}
				JFile::copy(PATH_APP . $galleryPath . DS . $firstScreenshot->srcfile, $thumb);
				$hi = new \Hubzero\Image\Processor($thumb);
				if (count($hi->getErrors()) == 0)
				{
					$hi->resize(100, false, true, true);
					$hi->save($thumb);
				}
			}
		}

		return true;
	}

	/**
	 * Edit screenshot
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string (html)
	 */
	protected function _editScreenshot()
	{
		// AJAX
		// Incoming
		$ima 		= Request::getVar('ima', '');
		$vid 		= Request::getInt('vid', 0);
		$pid 		= Request::getInt('pid', 0);
		$ajax 		= Request::getInt('ajax', 0);
		$no_html 	= Request::getInt('no_html', 0);
		$move 		= Request::getInt('move', 0);
		$filename 	= basename($ima);

		if (!$vid || !$pid)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_GETTING_IMAGE'));
		}
		if (!$ima)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_GETTING_IMAGE'));
		}

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'editimage'
			)
		);

		// Load screenshot info if any
		$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
		if ($pScreenshot->loadFromFilename($ima, $vid))
		{
			$view->file = $pScreenshot->srcfile;
			$view->thumb = \Components\Projects\Helpers\Html::createThumbName($pScreenshot->srcfile, '_tn', $extension = 'png');
		}
		elseif (!$this->getError())
		{
			// Get project file path
			$fpath =  \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);

			// Include Git Helper
			$this->_getGitHelper($fpath);

			// Get Git hash
			$hash = $this->_git->gitLog($ima, '' , 'hash');

			// Get full & thumb image names
			$view->file = \Components\Projects\Helpers\Html::createThumbName($filename, '-'.substr($hash, 0, 3));
			$view->thumb = \Components\Projects\Helpers\Html::createThumbName($filename, '-'.substr($hash, 0, 3).'_tn', $extension = 'png');
		}
		else
		{
			$view->file = '';
			$view->thumb = '';
		}

		// Build pub url
		$view->route = $this->_project->provisioned
					? 'index.php?option=com_publications&task=submit'
					: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $pid);

		$view->shot 		= $pScreenshot;
		$view->ima 			= $ima;
		$view->vid 			= $vid;
		$view->pid 			= $pid;
		$view->ajax 		= $ajax;
		$view->no_html 		= $no_html;
		$view->move 		= $move;
		$view->option 		= $this->_option;
		$view->project 		= $this->_project;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		$view->msg = isset($this->_message) ? $this->_message : '';
		return $view->loadTemplate();
	}

	/**
	 * Load screenshot
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string (html)
	 */
	protected function _loadScreenshot()
	{
		// AJAX
		// Incoming
		$ima 	= Request::getVar('ima', '');
		$vid 	= Request::getInt('vid', 0);
		$pid 	= Request::getInt('pid', 0);
		$move 	= Request::getInt('move', 0);

		$hash 	= '';
		$src 	= '';
		$title 	= '';

		if (!$vid || !$pid)
		{
			return Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_GETTING_IMAGE');
		}
		if (!$ima)
		{
			return Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_GETTING_IMAGE');
		}
		else
		{
			$ima = str_replace('file::', '', $ima);
		}

		// Is image?
		$ext = \Components\Projects\Helpers\Html::getFileExtension($ima);
		if (!in_array(strtolower($ext), $this->_image_ext) && !in_array(strtolower($ext), $this->_video_ext))
		{
			return Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_WRONG_EXT') . ' ' . $ext;
		}

		// Get screenshot path
		$webpath = $this->_pubconfig->get('webpath');
		$gallery_path = \Components\Publications\Helpers\Html::buildPubPath($pid, $vid, $webpath, 'gallery');

		// Does screenshot already exist?
		$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
		if ($pScreenshot->loadFromFilename($ima, $vid))
		{
			$thumb = \Components\Projects\Helpers\Html::createThumbName($pScreenshot->srcfile, '_tn', $extension = 'png');
			$src = $gallery_path. DS .$thumb;
			$title = $pScreenshot->title ? $pScreenshot->title : basename($pScreenshot->filename);
		}
		else
		{
			// Get project file path
			$fpath = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);

			$prefix = $this->_config->get('offroot', 0) ? '' : JPATH_ROOT ;
			$from_path = $prefix . $fpath;

			// Include Git Helper
			$this->_getGitHelper($fpath);

			// Get Git hash
			$hash = $this->_git->gitLog($ima, '' , 'hash');

			$src = $this->_createScreenshot ( $ima, $hash, $from_path, $gallery_path );
			$title = basename($ima);
		}

		if ($src)
		{
			// Output HTML
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'=>'projects',
					'element'=>'publications',
					'name'=>'screenshot'
				)
			);

			// Build pub url
			$view->route = $this->_project->provisioned
				? 'index.php?option=com_publications&task=submit'
				: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
			$view->url = Route::url($view->route . '&pid=' . $pid);

			$view->project 	= $this->_project;
			$view->option 	= $this->_option;
			$view->pid 		= $pid;
			$view->vid 		= $vid;
			$view->ima 		= $ima;
			$view->title 	= $title;
			$view->src 		= $src;
			$view->move 	= $move;
			return $view->loadTemplate();
		}
		else
		{
			return Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_GETTING_IMAGE');
		}
	}

	/**
	 * Create screenshot
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      string  		$ima
	 * @param      string 		$hash
	 * @param      string		$from_path
	 * @param      string		$gallery_path
	 * @param      string		$return
	 *
	 * @return     string (image source or hashed name)
	 */
	protected function _createScreenshot( $ima, $hash, $from_path, $gallery_path, $return = 'src' )
	{
		$src = '';
		$filename = basename($ima);

		$hashed = \Components\Projects\Helpers\Html::createThumbName($filename, '-'.substr($hash, 0, 6));
		$thumb = \Components\Projects\Helpers\Html::createThumbName($filename, '-'.substr($hash, 0, 6) . '_tn', $extension = 'png');

		// Make sure the path exist
		if (!is_dir( JPATH_ROOT.$gallery_path ))
		{
			jimport('joomla.filesystem.folder');
			JFolder::create( JPATH_ROOT . $gallery_path );
		}
		jimport('joomla.filesystem.file');
		if (!file_exists($from_path. DS .$ima))
		{
			return false;
		}
		if (!JFile::copy($from_path. DS .$ima, JPATH_ROOT . $gallery_path. DS .$hashed))
		{
			return false;
		}
		else
		{
			// Is image?
			$ext = \Components\Projects\Helpers\Html::getFileExtension($filename);
			if (in_array(strtolower($ext), $this->_image_ext))
			{
				// Also create a thumbnail
				JFile::copy($from_path . DS .$ima, JPATH_ROOT . $gallery_path . DS . $thumb);
				if (is_file(JPATH_ROOT . $gallery_path . DS . $thumb))
				{
					$hi = new \Hubzero\Image\Processor(JPATH_ROOT . $gallery_path . DS . $thumb);
					if (count($hi->getErrors()) == 0)
					{
						$hi->resize(100, false, false, true);
						$hi->save(JPATH_ROOT . $gallery_path . DS . $thumb);
						$src = $gallery_path. DS . $thumb;
					}
					else
					{
						return false;
					}
				}
			}
			else
			{
				// Do we have a thumbnail from Google?
				$objRFile = new \Components\Projects\Tables\RemoteFile ($this->_database);
				$remote   = $objRFile->getConnection($this->_project->id, '', 'google', $ima);
				$default  = '';

				if ($remote)
				{
					$rthumb = substr($remote['id'], 0, 20) . '_' . strtotime($remote['modified']) . '.png';
					$imagepath = trim($this->_config->get('imagepath', '/site/projects'), DS);
					$to_path = $imagepath . DS . strtolower($this->_project->alias) . DS . 'preview';
					if ($rthumb && is_file(JPATH_ROOT. DS . $to_path . DS . $rthumb))
					{
						$default = $to_path . DS . $rthumb;
					}
				}

				// Copy default video thumbnail
				$default = $default ? $default
						: trim($this->_pubconfig->get('video_thumb', 'components/com_publications/images/video_thumb.gif'), DS);

				if (is_file(JPATH_ROOT . DS . $default))
				{
					JFile::copy(JPATH_ROOT . DS . $default, JPATH_ROOT . $gallery_path . DS . $thumb);
					$hi = new \Hubzero\Image\Processor(JPATH_ROOT . $gallery_path . DS . $thumb);
					if (count($hi->getErrors()) == 0)
					{
						$hi->resize(100, false, false, true);
						$hi->save(JPATH_ROOT . $gallery_path . DS . $thumb);
						$src = $gallery_path. DS . $thumb;
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
		}

		return $return == 'src' ? $src : $hashed;
	}

	/**
	 * Save screenshot
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     void (redirect)
	 */
	protected function _saveScreenshot()
	{
		// Incoming
		$ima 		= urldecode(Request::getVar( 'ima', '', 'post' ));
		$vid 		= Request::getInt( 'vid', 0, 'post' );
		$pid 		= Request::getInt( 'pid', 0, 'post' );
		$title 		= Request::getVar( 'title', '', 'post' );
		$srcfile 	= Request::getVar( 'srcfile', '', 'post' );
		$move 		= Request::getInt( 'move', 0 );

		// Save any changes to selections/ordering
		$selections = Request::getVar( 'selections', '', 'post' );
		$selections = $this->_parseSelections($selections);
		$this->_processGallery( $pid, $vid, $selections );
		$now = JFactory::getDate()->toSql();

		if (!$vid || !$pid)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_GETTING_IMAGE'));
		}
		if (!$ima)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_GETTING_IMAGE'));
		}

		// Get version label
		$row = new \Components\Publications\Tables\Version( $this->_database );
		$row->load($vid);
		$version = $row->version_number;

		$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
		if ($pScreenshot->loadFromFilename( $ima, $vid ))
		{
			if ($title && $pScreenshot->title != $title)
			{
				$pScreenshot->modified 				= $now;
				$pScreenshot->modified_by 			= $this->_uid;
				$pScreenshot->title 				= $title;
			}
		}
		else
		{
			$pScreenshot 							= new \Components\Publications\Tables\Screenshot( $this->_database );
			$pScreenshot->filename 					= $ima;
			$pScreenshot->srcfile 					= $srcfile;
			$pScreenshot->publication_id 			= $pid;
			$pScreenshot->publication_version_id 	= $vid;
			$pScreenshot->title 					= $title ? $title : basename($ima);
			$pScreenshot->created 					= $now;
			$pScreenshot->created_by 				= $this->_uid;
		}

		// Pass success or error message
		if ($pScreenshot->store())
		{
			$this->_message = array('message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_IMAGE_SAVED'), 'type' => 'success');
		}
		else
		{
			$this->_message = array('message' => Lang::txt('PLG_PROJECTS_PUBLICATIONS_GALLERY_ERROR_SAVING_IMAGE'), 'type' => 'error');
		}

		// Build pub url
		$route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$url = Route::url($route . '&pid=' . $pid);

		$url .= '?section=gallery';
		$url .= '&version=' . $version;
		$url .= $move ? '&move=' . $move : '';

		// Redirect
		$this->_referer = $url;
		return;
	}

	/**
	 * Parse tags
	 *
	 * @param      string  		$tag_string
	 * @param      integer 		$keep
	 *
	 * @return     array
	 */
	protected function _parseTags( $tag_string, $keep = 0 )
	{
		$newwords = array();

		// If the tag string is empty, return the empty set.
		if ($tag_string == '')
		{
			return $newwords;
		}

		// Perform tag parsing
		$tag_string = trim($tag_string);
		$raw_tags = explode(',',$tag_string);

		foreach ($raw_tags as $raw_tag)
		{
			$raw_tag = trim($raw_tag);
			$nrm_tag = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $raw_tag));
			if ($keep != 0)
			{
				$newwords[$nrm_tag] = $raw_tag;
			}
			else
			{
				$newwords[] = $nrm_tag;
			}
		}
		return $newwords;
	}

	/**
	 * Create tags
	 *
	 * @param      string  		$newtag
	 *
	 * @return     array
	 */
	protected function _createTags( $newtag = '' )
	{
		$tagarray = array();
		$newTags = $this->_parseTags($newtag);
		$rawTags = $this->_parseTags($newtag, 1);
		$tagObj = new \Components\Tags\Tables\Tag( $this->_database );

		foreach ($newTags as $tag)
		{
			$tag = trim($tag);
			if ($tag != '')
			{
				if ($tagObj->loadTag($tag))
				{
					$tagarray[] = $tagObj->id;
				}
				else
				{
					// Create tag
					$tagObj = new \Components\Tags\Tables\Tag( $this->_database );
					if (get_magic_quotes_gpc())
					{
						$tag = addslashes($tag);
					}
					$tagObj->tag = $tag;
					$tagObj->raw_tag = isset($rawTags[$tag]) ? $rawTags[$tag] : $tag;
					if ($tagObj->store())
					{
						$tagObj->checkin();
						if ($tagObj->id)
						{
							$tagarray[] = $tagObj->id;
						}
					}
				}
			}
		}
		return $tagarray;
	}

	/**
	 * Suggest tags (AJAX)
	 *
	 * Marked for re-write
	 * @return   string (html)
	 */
	public function suggestTags()
	{
		// AJAX
		// Incoming
		$vid 		= Request::getInt('vid', 0);
		$pid 		= Request::getInt('pid', 0);
		$limit 		= Request::getInt('limit', 8);
		$newtag 	= Request::getVar('tags', '');
		$tcount 	= 1; // minimum number of tagged objects

		// Get original/new selections
		$selections = Request::getVar('selections', '');
		$selections = explode("##", $selections);
		$attached = array();
		if (count($selections) > 0)
		{
			foreach ($selections as $sel)
			{
				if (trim($sel) != '' && intval($sel))
				{
					$attached[] = trim($sel);
				}
			}
		}

		// Some new tags are provided
		if ($newtag)
		{
			$new = $this->_createTags($newtag);
			$attached = array_merge($attached, $new);
		}
		array_unique($attached);

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'tags'
			)
		);

		if (!$vid || !$pid)
		{
			$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_TAGS_ERROR_NO_PUBLICATION'));
		}
		else
		{
			$objP = new \Components\Publications\Tables\Publication( $this->_database );
			$pub = $objP->getPublication($pid, 'default');
			if (!$pub)
			{
				$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_TAGS_ERROR_NO_PUBLICATION'));
			}
		}

		if (!$this->getError())
		{
			// Get attached tags
			$tagsHelper = new \Components\Publications\Helpers\Tags( $this->_database);
			$view->original = $tagsHelper->getTags($pid);

			$view->attached_tags = $tagsHelper->getPickedTags($attached);

			// Get suggestions
			$view->tags = $tagsHelper->getSuggestedTags($pub->title, $pub->cat_alias, $view->attached_tags, $limit, $tcount );
		}

		// Build pub url
		$view->route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $pid);

		$view->pid 		= $pid;
		$view->vid 		= $vid;
		$view->option 	= $this->_option;
		$view->project 	= $this->_project;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		$view->msg = isset($this->_message) ? $this->_message : '';
		return $view->loadTemplate();

	}

	/**
	 * Show publication versions
	 *
	 * @return     string (html)
	 */
	public function versions()
	{
		// Incoming
		$pid = $this->_pid ? $this->_pid : Request::getInt('pid', 0);

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $this->_database );
		$objV = new \Components\Publications\Tables\Version( $this->_database );

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'versions'
			)
		);

		$view->pub = $objP->getPublication($pid, 'default', $this->_project->id);
		if (!$view->pub)
		{
			throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_NOT_FOUND'), 404);
			return;
		}

		// Build pub url
		$view->route = $this->_project->provisioned
			? 'index.php?option=com_publications&task=submit'
			: 'index.php?option=com_projects&alias=' . $this->_project->alias . '&active=publications';
		$view->url = Route::url($view->route . '&pid=' . $pid);

		// Append breadcrumbs
		Pathway::append(
			stripslashes($view->pub->title),
			$view->url
		);

		// Get versions
		$view->versions = $objV->getVersions( $pid, $filters = array('withdev' => 1));

		// Output HTML
		$view->option 		= $this->_option;
		$view->database 	= $this->_database;
		$view->project 		= $this->_project;
		$view->uid 			= $this->_uid;
		$view->pid 			= $pid;
		$view->task 		= $this->_task;
		$view->config 		= $this->_config;
		$view->pubconfig 	= $this->_pubconfig;
		$view->title		= $this->_area['title'];

		// Get messages	and errors
		$view->msg = $this->_msg;
		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	/**
	 * Contribute from outside projects
	 *
	 * @return     string (html)
	 */
	public function contribute()
	{
		// Get user info
		$juser = JFactory::getUser();

		$view = new \Hubzero\Plugin\View(
			array(
				'folder'=>'projects',
				'element'=>'publications',
				'name'=>'browse',
				'layout'=>'intro'
			)
		);
		$view->option  		= $this->_option;
		$view->pubconfig 	= $this->_pubconfig;
		$view->outside  	= 1;
		$view->juser   		= $juser;
		$view->uid 			= $this->_uid;
		$view->database		= $this->_database;

		// Get publications
		if (!$juser->get('guest'))
		{
			$view->filters = array();

			// Get user projects
			$obj = new \Components\Projects\Tables\Project( $this->_database );
			$view->filters['projects']  = $obj->getUserProjectIds($juser->get('id'), 0, 1);

			$view->filters['mine']		= $juser->get('id');
			$view->filters['dev']		= 1;
			$view->filters['sortby']	= 'mine';
			$view->filters['limit']  	= Request::getInt( 'limit', 3 );

			// Get publications created by user
			$objP = new \Components\Publications\Tables\Publication( $this->_database );
			$view->mypubs = $objP->getRecords( $view->filters );

			// Get pub count
			$count = $objP->getCount( $view->filters );
			$view->mypubs_count = ($count && is_array($count)) ? count($count) : 0;

			// Get other pubs that user can manage
			$view->filters['coauthor'] = 1;
			$view->coauthored = $objP->getRecords( $view->filters );
			$coauthored = $objP->getCount( $view->filters );
			$view->coauthored_count = ($coauthored && is_array($coauthored)) ? count($coauthored) : 0;
		}

		return $view->loadTemplate();
	}

	//----------------------------------------
	// Private functions
	//----------------------------------------

	/**
	 * Get panels
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return    void
	 */
	protected function _getPanels( $required = false, $base = NULL)
	{
		$this->_panels = array();
		$rPanels 	   = array();

		$base = $base ? $base : $this->_base;

		// Get master type params
		$typeParams = NULL;
		if ($base)
		{
			$mt = new \Components\Publications\Tables\MasterType( $this->_database );
			$mType = $mt->getType($base);
			$typeParams = new JParameter( $mType->params );
		}

		// Available panels and default config
		$panels = array(
			'content' 		=> 2,
			'description' 	=> 2,
			'authors'		=> 2,
			'audience'		=> 0,
			'gallery'		=> 1,
			'tags'			=> 1,
			'access'		=> 0,
			'license'		=> 2,
			'citations'		=> 1,
			'notes'			=> 1
		);

		// Skip some panels if set in params
		foreach ($panels as $panel => $val)
		{
			$on = $val;
			if ($typeParams)
			{
				$on = $typeParams->get('show_' . trim($panel), $val);
			}

			if ($on > 0)
			{
				$this->_panels[] = trim($panel);
			}

			if ($on == 2)
			{
				$rPanels[] = trim($panel);
			}
		}

		if ($required == true)
		{
			return $rPanels;
		}
	}

	/**
	 * Check if publication may be published
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      array 		$checked
	 *
	 * @return     boolean
	 */
	protected function _checkPublicationPermit( $checked = array(), $base = NULL )
	{
		$publication_allowed = true;
		$required = $this->_getPanels( true , $base);

		foreach ($required as $req)
		{
			if (isset($checked[$req]) && $checked[$req] != 1)
			{
				$publication_allowed = false;
			}
		}

		return $publication_allowed;
	}

	/**
	 * Check what's missing in draft
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      string  		$type master type
	 * @param      object 		$row
	 * @param      string		$version
	 *
	 * @return     array
	 */
	protected function _checkDraft( $type, $row, $version = 'dev' )
	{
		$checked = array();

		if (!isset($this->_panels))
		{
			// Get active panels
			$this->_getPanels( false, $type);
		}

		$required = $this->_getPanels( true, $type );

		// Check each enabled panel
		foreach ($this->_panels as $key => $value)
		{
			$checked[$value] = $version == 'dev' ? 0 : 1;

			if ($value == 'description')
			{
				// Check description
				$checked['description'] = $row->abstract ? 1 : 0;
			}
			elseif ($value == 'content')
			{
				// Get primary attachments
				$pContent = new \Components\Publications\Tables\Attachment( $this->_database );
				$attachments = $pContent->getAttachments ( $row->id, $filters = array('role' => 1) );

				// Check content
				$checked['content'] = $this->_pubTypeHelper->dispatch($type, 'checkContent',
					$data = array('pid' => $row->id, 'attachments' => $attachments));
			}
			elseif ($value == 'authors')
			{
				// Check authors
				$pAuthor = new \Components\Publications\Tables\Author( $this->_database );
				$checked['authors'] = $pAuthor->getCount($row->id) >= 1 ? 1 : 0;
			}
			elseif ($value == 'license')
			{
				// Check license
				$checked['license'] = ($row->license_type) ? 1 : 0;
			}
			elseif ($value == 'audience')
			{
				// Check audience
				$pAudience = new \Components\Publications\Tables\Audience( $this->_database );
				$checked['audience'] = $pAudience->loadByVersion($row->id) ? 1 : 0;
			}
			elseif ($value == 'access')
			{
				// Check access - public by default
				$checked['access'] = 1;
			}
			elseif ($value == 'gallery')
			{
				// Check sreenshots
				$pScreenshot = new \Components\Publications\Tables\Screenshot( $this->_database );
				$checked['gallery'] = count($pScreenshot->getScreenshots( $row->id )) > 0 ? 1 : 0;
			}
			elseif ($value == 'tags')
			{
				// Check tags
				$tagsHelper = new \Components\Publications\Helpers\Tags( $this->_database);
				$checked['tags'] = $tagsHelper->countTags($row->publication_id) > 0 ? 1 : 0;
			}
			elseif ($value == 'citations')
			{
				// Check citations
				include_once( JPATH_ROOT . DS . 'components'
					. DS . 'com_citations' . DS . 'tables' . DS . 'association.php' );

				$assoc 	= new \Components\Citations\Tables\Association($this->_database);
				$filters = array('tbl' => 'publication', 'oid' => $row->publication_id);
				$checked['citations'] = ($assoc->getCount($filters) > 0) ? 1 : 0;
			}
			elseif ($value == 'notes')
			{
				// Check release notes
				$checked['notes'] = $row->release_notes ? 1 : 0;
			}
		}

		return $checked;
	}

	/**
	 * Get current position in pub contribution process
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      object  		$row
	 * @param      string 		$lastpane
	 * @param      string		$current
	 *
	 * @return     array
	 */
	protected function _getIndex($row, $lastpane, $current)
	{
		$check = array();

		if (!isset($this->_panels))
		{
			// Get active panels
			$this->_getPanels();
		}

		// Get active and last visted index
		$last_idx = 0;
		$current_idx = 0;
		$next_idx = 0;

		while ($panel = current($this->_panels))
		{
		    if ($panel == $lastpane)
			{
		        $last_idx = key($this->_panels);
		    }
			if ($panel == $current)
			{
		        $current_idx = key($this->_panels);
				$next_idx = key($this->_panels) + 1;
		    }
			if ($lastpane == 'review')
			{
				$current_idx = 0;
			}
		    next($this->_panels);
		}

		$check['last_idx'] 		= $last_idx;
		$check['current_idx'] 	= $current_idx;
		$check['next_idx'] 		= $next_idx;

		return $check;
	}

	/**
	 * Parse selections
	 *
	 * @param      array  		$selections
	 *
	 * @return     array
	 */
	protected function _parseSelections( $selections = '' )
	{
		$mt = new \Components\Publications\Tables\MasterType( $this->_database );

		if ($selections)
		{
			$sels = explode("##", $selections);

			// Get available types
			$types = $mt->getTypes('alias');

			// Start selections array
			$selections = array('first' => $sels[0]);
			$count 		= 0;

			foreach ($types as $type)
			{
				$selections[$type] = $this->_pubTypeHelper->dispatch($type, 'parseSelections',
									 $data = array('sels' => $sels));
				$count 			   = $count + count($selections[$type]);
			}

			$selections['count'] = $count;
			return $selections;
		}

		return false;
	}

	/**
	 * Get member path
	 *
	 * @return     string
	 */
	protected function _getMemberPath()
	{
		// Get members config
		$mconfig = Component::params( 'com_members' );

		// Build upload path
		$dir  = \Hubzero\Utility\String::pad( $this->_uid );
		$path = DS . trim($mconfig->get('webpath', '/site/members'), DS) . DS . $dir . DS . 'files';

		if (!is_dir( PATH_APP . $path ))
		{
			if (!\JFolder::create( PATH_APP . $path ))
			{
				$this->setError(\Lang::txt('UNABLE_TO_CREATE_UPLOAD_PATH'));
				return;
			}
		}

		return PATH_APP . $path;
	}

	/**
	 * Prep file directory (provisioned project)
	 *
	 * @param      boolean		$force
	 *
	 * @return     boolean
	 */
	protected function _prepDir($force = true)
	{
		jimport('joomla.filesystem.folder');

		if (empty($this->_project) || !$this->_project->alias)
		{
			$this->setError( Lang::txt('UNABLE_TO_CREATE_UPLOAD_PATH') );
			return;
		}

		// Get member files path
		$memberPath = $this->_getMemberPath();

		// Get project path
		$path = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias, 'files', false);

		if (!is_dir( $path ))
		{
			if (!JFolder::create( $path ))
			{
				$this->setError( Lang::txt('UNABLE_TO_CREATE_UPLOAD_PATH') );
				return;
			}
		}

		// Include Git Helper
		$this->_getGitHelper($path);

		// Initialize Git
		$this->_git->iniGit();

		// Copy files from member directory
		if (!JFolder::copy($memberPath, $path, '', true))
		{
			$this->setError( Lang::txt('COM_PROJECTS_FAILED_TO_COPY_FILES') );
			return false;
		}

		// Read copied files
		$fileSystem = new \Hubzero\Filesystem\Filesystem();
		$get = $fileSystem->files($path);

		$num = count($get);
		$checkedin = 0;

		// Check-in copied files
		if ($get)
		{
			foreach ($get as $file)
			{
				$file = str_replace($path . DS, '', $file);
				if (is_file($path . DS . $file))
				{
					// Git add
					$commitMsg = '';
					$this->_git->gitAdd($file, $commitMsg);
					$this->_git->gitCommit($commitMsg);
					$checkedin++;
				}
			}
		}
		if ($num == $checkedin)
		{
			// Clean up member files
			JFolder::delete($memberPath);
			return true;
		}

		return false;
	}

	/**
	 * Get data as CSV file
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      integer  	$db_name
	 * @param      integer  	$version
	 *
	 * @return     string data
	 */
	protected function _getCsvData($db_name = '', $version = '', $tmpFile = '')
	{
		if (!$db_name || !$version)
		{
			return false;
		}

		mb_internal_encoding('UTF-8');

		// component path for "com_dataviewer"
		$dv_com_path = JPATH_ROOT . DS . 'components' . DS . 'com_dataviewer';

		require_once($dv_com_path . DS . 'dv_config.php');
		require_once($dv_com_path . DS . 'lib' . DS . 'db.php');
		require_once($dv_com_path . DS . 'modes' . DS . 'mode_dsl.php');
		require_once($dv_com_path . DS . 'filter' . DS . 'csv.php');

		$dv_conf = get_conf(NULL);
		$dd = get_dd(NULL, $db_name, $version);
		$dd['serverside'] = false;

		$sql = query_gen($dd);
		$result = get_results($sql, $dd);

		ob_start();
		filter($result, $dd, true);
		$csv = ob_get_contents();
		ob_end_clean();

		if ($csv && $tmpFile)
		{
			$handle = fopen($tmpFile, 'w');
			fwrite($handle, $csv);
			fclose($handle);

			return true;
		}

		return $csv;
	}

	/**
	 * Get disk space
	 *
	 * @param      string	$option
	 * @param      object  	$project
	 * @param      string  	$case
	 * @param      integer  $by
	 * @param      string  	$action
	 * @param      object 	$config
	 * @param      string  	$app
	 *
	 * @return     string
	 */
	protected function pubDiskSpace( $option, $project, $action, $config)
	{
		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  =>'projects',
				'element' =>'publications',
				'name'    =>'diskspace'
			)
		);

		// Include styling and js
		\Hubzero\Document\Assets::addPluginStylesheet('projects', 'files','css/diskspace');
		\Hubzero\Document\Assets::addPluginScript('projects', 'files','js/diskspace');

		$database = JFactory::getDBO();

		// Build query
		$filters = array();
		$filters['limit'] 	 		= Request::getInt('limit', 25);
		$filters['start'] 	 		= Request::getInt('limitstart', 0);
		$filters['sortby']   		= Request::getVar( 't_sortby', 'title');
		$filters['sortdir']  		= Request::getVar( 't_sortdir', 'ASC');
		$filters['project']  		= $project->id;
		$filters['ignore_access']   = 1;
		$filters['dev']   	 		= 1; // get dev versions

		// Instantiate project publication
		$objP = new \Components\Publications\Tables\Publication( $database );

		// Get all publications
		$view->rows = $objP->getRecords($filters);

		// Get used space
		$view->dirsize = \Components\Publications\Helpers\Html::getDiskUsage($view->rows);
		$view->params  = new JParameter( $project->params );
		$view->quota   = $view->params->get('pubQuota')
						? $view->params->get('pubQuota')
						: \Components\Projects\Helpers\Html::convertSize(floatval($config->get('pubQuota', '1')), 'GB', 'b');

		// Get total count
		$results = $objP->getCount($filters);
		$view->total = ($results && is_array($results)) ? count($results) : 0;

		$view->action 	= $action;
		$view->project 	= $project;
		$view->option 	= $option;
		$view->config 	= $config;
		$view->title	= isset($this->_area['title']) ? $this->_area['title'] : '';

		return $view->loadTemplate();
	}

	/**
	 * Archive data in a publication and package
	 *
	 * OLD FLOW - Marked for deprecation
	 * @param      object  	$pub	Publication object
	 * @param      object  	$row	Version object
	 *
	 * @return     string data
	 */
	public function archivePub( $pid, $vid)
	{
		if (!$pid || !$vid)
		{
			return false;
		}

		$database = JFactory::getDBO();

		// Archival name
		$tarname = Lang::txt('Publication') . '_' . $pid . '.zip';

		$pubconfig = Component::params( 'com_publications' );

		// Load publication & version classes
		$objP  = new \Components\Publications\Tables\Publication( $database );
		$objV  = new \Components\Publications\Tables\Version( $database );

		if (!$objP->load($pid) || !$objV->load($vid))
		{
			return false;
		}

		// Start README
		$readme  = $objV->title . "\n ";
		$readme .= 'Version ' . $objV->version_label . "\n ";

		// Get authors
		$pa = new \Components\Publications\Tables\Author( $database );
		$authors = $pa->getAuthors($vid);

		$tmpFile   = '';
		$tmpReadme = '';

		$readme .= 'Authors: ' . "\n ";

		foreach ($authors as $author)
		{
			$readme .= ($author->name) ? $author->name : $author->p_name;
			$org = ($author->organization) ? $author->organization : $author->p_organization;

			if ($org)
			{
				$readme .= ', ' . $org;
			}
			$readme .= "\n ";
		}

		$readme .= 'doi:' . $objV->doi . "\n ";
		$readme .= '#####################################' . "\n ";

		$readme .= "\n ";
		$readme .= 'License: ' . "\n ";

		// Get license type
		$objL = new \Components\Publications\Tables\License( $database);
		$license = '';
		if ($objL->loadLicense($objV->license_type))
		{
			$license = $objL->title . "\n ";
		}

		$license .= $objV->license_text ? "\n " . $objV->license_text . "\n " : '';
		$readme .= $license . "\n ";
		$readme .= '#####################################' . "\n ";
		$readme .= "\n ";
		$readme .= 'Included Publication Materials:' . "\n ";

		// Build publication path
		$base_path = $pubconfig->get('webpath');
		$path = \Components\Publications\Helpers\Html::buildPubPath($pid, $vid, $base_path);

		$galleryPath = JPATH_ROOT . $path . DS . 'gallery';
		$dataPath 	 = JPATH_ROOT . $path . DS . 'data';
		$contentPath = JPATH_ROOT . $path . DS . $objV->secret;

		$tarpath = JPATH_ROOT . $path . DS . $tarname;

		$zip = new ZipArchive;
		if ($zip->open($tarpath, ZipArchive::OVERWRITE) === TRUE)
		{
			// Get joomla libraries
			jimport('joomla.filesystem.folder');
			jimport('joomla.filesystem.file');

			$i = 0;

			// Get attachments
			$pContent 	= new \Components\Publications\Tables\Attachment( $database );
			$sDocs 		= $pContent->getAttachmentsArray( $vid, '4' );
			$pDocs 		= $pContent->getAttachmentsArray( $vid, '1' );

			$mFolder 	= Lang::txt('Publication') . '_' . $pid;

			// Add primary and supporting content
			$mainFiles = array();
			if (is_dir($contentPath))
			{
				$mainFiles = JFolder::files($contentPath, '.', true, true);
			}

			if (!empty($mainFiles) && ($pDocs || $sDocs))
			{
				foreach ($mainFiles as $e)
				{
					$fileinfo = pathinfo($e);
					$a_dir  = $fileinfo['dirname'];
					$a_dir	= trim(str_replace($contentPath, '', $a_dir), DS);

					$fPath  = $a_dir && $a_dir != '.' ? $a_dir . DS : '';
					$fPath .= basename($e);

					if (in_array($fPath, $pDocs))
					{
						$fPath = $mFolder . DS . 'content' . DS . $fPath;
					}
					elseif (in_array($fPath, $sDocs))
					{
						$fPath = $mFolder . DS . 'supporting' . DS . $fPath;
					}
					else
					{
						// Skip everything else
						continue;
					}
					$readme .= str_replace($mFolder . DS, '', $fPath) . "\n ";

					$zip->addFile($e, $fPath);
					$i++;
				}
			}

			// Add data files
			$dataFiles = array();
			if (is_dir($dataPath))
			{
				$dataFiles = JFolder::files($dataPath, '.', true, true);
			}

			if (!empty($dataFiles))
			{
				foreach ($dataFiles as $e)
				{
					// Skip thumbnails
					if (preg_match("/_tn.gif/", $e) || preg_match("/_medium.gif/", $e))
					{
						continue;
					}

					$fileinfo = pathinfo($e);
					$a_dir  = $fileinfo['dirname'];
					$a_dir	= trim(str_replace($dataPath, '', $a_dir), DS);

					$fPath = $a_dir && $a_dir != '.' ? $a_dir . DS : '';
					$fPath = $mFolder . DS . 'data' . DS . $fPath . basename($e);

					$readme .= str_replace($mFolder . DS, '', $fPath) . "\n ";
					$zip->addFile($e, $fPath);
					$i++;
				}
			}

			// Get gallery info
			$pScreenshot = new \Components\Publications\Tables\Screenshot( $database );
			$gImages = $pScreenshot->getScreenshotArray( $vid );

			// Add screenshots
			$galleryFiles = array();
			if (is_dir($galleryPath))
			{
				$galleryFiles = JFolder::files($galleryPath, '.', true, true);
			}

			if (!empty($galleryFiles) && !empty($gImages))
			{
				$g = 1;
				foreach ($galleryFiles as $e)
				{
					$fPath = trim(str_replace($galleryPath . DS, '', $e), DS);
					if (!isset($gImages[$fPath]))
					{
						continue;
					}

					$gName = $g . '-' . basename($gImages[$fPath]);

					$fPath = $mFolder . DS . 'gallery' . DS . $gName;

					$readme .= str_replace($mFolder . DS, '', $fPath) . "\n ";
					$zip->addFile($e, $fPath);
					$i++;
					$g++;
				}
			}

			// Database type?
			$mainContent = $pContent->getAttachments( $vid, array('role' => 1));
			if (!empty($mainContent) && $mainContent[0]->type == 'data')
			{
				$firstChild = $mainContent[0];
				$db_name 	= $firstChild->object_name;
				$db_version = $firstChild->object_revision;

				// Add CSV file
				if ($db_name && $db_version)
				{
					$tmpFile 	= JPATH_ROOT . $path . DS . 'data.csv';
					$csvFile 	= $mFolder . DS . 'data.csv';
					$csv 		= $this->_getCsvData($db_name, $db_version, $tmpFile);

					if ($csv && file_exists($tmpFile))
					{
						$readme .= str_replace($mFolder . DS, '', $csvFile) . "\n ";
						$zip->addFile($tmpFile, $csvFile);
						$i++;
					}
				}
			}

			if ($i > 0 && $readme)
			{
				$tmpReadme = JPATH_ROOT . $path . DS . 'README.txt';
				$rmFile  = $mFolder . DS . 'README.txt';

				$readme .= str_replace($mFolder . DS, '', $rmFile) . "\n ";
				$readme .= '#####################################' . "\n ";
				$readme .= 'Archival package produced ' . JFactory::getDate()->toSql();

				$handle  = fopen($tmpReadme, 'w');
				fwrite($handle, $readme);
				fclose($handle);

				$zip->addFile($tmpReadme, $rmFile);
			}

		    $zip->close();
		}
		else
		{
		    return false;
		}

		// Delete temp files
		if (file_exists($tmpReadme))
		{
			unlink($tmpReadme);
		}
		if (file_exists($tmpFile))
		{
			unlink($tmpFile);
		}

		return $tarpath;
	}

	/**
	 * Get Git helper
	 *
	 *
	 * @return     void
	 */
	protected function _getGitHelper($path)
	{
		if (!isset($this->_git))
		{
			// Git helper
			include_once( JPATH_ROOT . DS . 'components' . DS .'com_projects'
				. DS . 'helpers' . DS . 'githelper.php' );
			$this->_git = new \Components\Projects\Helpers\Git($path);
		}
		elseif ($path)
		{
			$this->_git->set('_path');
		}
	}

	/**
	 * Get supported master types applicable to individual project
	 *
	 * OLD FLOW - Marked for deprecation
	 * @return     string
	 */
	private function _getAllowedTypes($tChoices)
	{
		$choices = array();

		if (is_object($this->_project) && $this->_project->id && !empty($tChoices))
		{
			foreach ($tChoices as $choice)
			{
				$pluginName = is_object($choice) ? $choice->alias : $choice;

				// We need a plugin
				if (!Plugin::isEnabled('projects', $pluginName))
				{
					continue;
				}

				$params = Plugin::params('projects', $pluginName);

				// Get restrictions from plugin params
				$projects = $params->get('restricted')
					? \Components\Projects\Helpers\Html::getParamArray($params->get('restricted')) : array();

				if (!empty($projects))
				{
					if (!in_array($this->_project->alias, $projects))
					{
						continue;
					}
				}

				$choices[] = $choice;
			}
		}
		return $choices;
	}

	/**
	 * Serve publication-related file (via public link)
	 *
	 * @param   int  	$projectid
	 * @return  void
	 */
	public function serve( $type = '', $projectid = 0, $query = '')
	{
		$this->_area = $this->onProjectAreas();
		if ($type != $this->_area['name'])
		{
			return false;
		}
		$data = json_decode($query);

		if (!isset($data->pid) || !$projectid)
		{
			return false;
		}

		$disp 	= isset($data->disp) ? $data->disp : 'inline';
		$type 	= isset($data->type) ? $data->type : 'file';
		$folder = isset($data->folder) ? $data->folder : 'wikicontent';
		$fpath	= isset($data->path) ? $data->path : 'inline';

		if ($type != 'file')
		{
			return false;
		}

		$database = JFactory::getDBO();

		// Instantiate a project
		$obj = new \Components\Projects\Tables\Project( $database );

		// Get referenced path
		$pubconfig = Component::params( 'com_publications' );
		$base_path = $pubconfig->get('webpath');
		$pubPath = \Components\Publications\Helpers\Html::buildPubPath($data->pid, $data->vid, $base_path, $folder, $root = 0);

		$serve = PATH_APP . $pubPath . DS . $fpath;

		// Ensure the file exist
		if (!file_exists($serve))
		{
			// Throw error
			throw new Exception(Lang::txt('COM_PROJECTS_FILE_NOT_FOUND'), 404);
			return;
		}

		// Initiate a new content server and serve up the file
		$xserver = new \Hubzero\Content\Server();
		$xserver->filename($serve);
		$xserver->disposition($disp);
		$xserver->acceptranges(false); // @TODO fix byte range support
		$xserver->saveas(basename($fpath));

		if (!$xserver->serve())
		{
			// Should only get here on error
			throw new Exception(Lang::txt('COM_PUBLICATIONS_SERVER_ERROR'), 404);
		}
		else
		{
			exit;
		}

		return;
	}
}