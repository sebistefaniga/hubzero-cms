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


namespace Components\Publications\Models\Type;

use Hubzero\Base\Object;

/**
 * DATABASES master type helper class
 */
class Databases extends Object
{
	/**
	 * JDatabase
	 *
	 * @var object
	 */
	var $_database       	= NULL;

	/**
	 * Project
	 *
	 * @var object
	 */
	var $_project      	 	= NULL;

	/**
	 * Base alias
	 *
	 * @var integer
	 */
	var $_base   		 	= 'databases';

	/**
	 * Attachment type
	 *
	 * @var string
	 */
	var $_attachmentType 	= 'data';

	/**
	 * Selection type (single/multi)
	 *
	 * @var boolean
	 */
	var $_multiSelect 	 	= false;

	/**
	 * Allow change to selection after draft is started?
	 *
	 * @var boolean
	 */
	var $_changeAllowed  	= false;


	/**
	 * Allow to create a new publication with exact same content?
	 *
	 * @var boolean
	 */
	var $_allowDuplicate  	= false;

	/**
	 * Unique attachment properties
	 *
	 * @var array
	 */
	var $_attProperties 	= array('object_name', 'object_revision');

	/**
	 * Data
	 *
	 * @var array
	 */
	var $_data   		 = array();

	/**
	 * Serve as (default value)
	 *
	 * @var string
	 */
	var $_serveas   	= 'external';

	/**
	 * Serve as choices
	 *
	 * @var string
	 */
	var $_serveChoices  = array('external');

	/**
	 * Constructor
	 *
	 * @param      object  &$db      	 JDatabase
	 * @return     void
	 */
	public function __construct( &$db, $project = NULL, $data = array() )
	{
		$this->_database = $db;
		$this->_project  = $project;
		$this->_data 	 = $data;
	}

	/**
	 * Set
	 *
	 * @param      string 	$property
	 * @param      string 	$value
	 * @return     mixed
	 */
	public function __set($property, $value)
	{
		$this->_data[$property] = $value;
	}

	/**
	 * Get
	 *
	 * @param      string 	$property
	 * @return     mixed
	 */
	public function __get($property)
	{
		if (isset($this->_data[$property]))
		{
			return $this->_data[$property];
		}
	}

	/**
	 * Dispatch task
	 *
	 * @param      string  $task
	 * @return     void
	 */
	public function dispatch( $task = NULL )
	{
		$output 		 = NULL;

		switch ( $task )
		{
			case 'getServeAs':
				$output = $this->_getServeAs();
				break;

			case 'checkContent':
				$output = $this->_checkContent();
				break;

			case 'checkMissing':
				$output = $this->_checkMissing();
				break;

			case 'drawItem':
				$output = $this->_drawItem();
				break;

			case 'saveAttachments':
				$output = $this->_saveAttachments();
				break;

			case 'publishAttachments':
				$output = $this->_publishAttachments();
				break;

			case 'getPubTitle':
				$output = $this->_getPubTitle();

			default:
				break;
		}

		return $output;
	}

	/**
	 * Get serveas options (_showOptions function in plg_projects_publications)
	 *
	 * @return     void
	 */
	protected function _getServeAs()
	{
		$result = array('serveas' => $this->_serveas, 'choices' => $this->_serveChoices);

		return $result;
	}

	/**
	 * Get publication title for newly created draft
	 *
	 * @return     void
	 */
	protected function _getPubTitle($title = '')
	{
		// Incoming data
		$item = $this->__get('item');

		// Get project database object
		$objPD = new \Components\Projects\Tables\Database($this->_database);
		if ($objPD->loadRecord($item))
		{
			$title = $objPD->title;
		}

		return $title;

	}

	/**
	 * Check content
	 *
	 * @return     void
	 */
	protected function _checkContent()
	{
		// Incoming data
		$attachments = $this->__get('attachments');

		if ($attachments && count($attachments) > 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Check missing content
	 *
	 * @return     void
	 */
	protected function _checkMissing()
	{
		// Incoming data
		$item  = $this->__get('item');

		if (!$item)
		{
			return false;
		}

		$dataid = $item->object_id;
		$dbName = $item->object_name;

		$data = new \Components\Projects\Tables\Database($this->_database);
		if (!$data->loadRecord($dbName))
		{
			return true;
		}

		return false;
	}

	/**
	 * Draw selected item html
	 *
	 * @return     void
	 */
	protected function _drawItem()
	{
		// Incoming data
		$att   		= $this->__get('att');
		$item   	= $this->__get('item');

		$dbName = $att->id ? $att->object_name : $item;

		$data = new \Components\Projects\Tables\Database($this->_database);
		if (!$data->loadRecord($dbName))
		{
			return false;
		}

		$title = $att->title ? $att->title : $data->title;

		$html = '<span class="' . $this->_base . '">' . $title . '</span>';
		if ($data->source_file) {
		$html.= '<span class="c-iteminfo">' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_SOURCE_FILE')
			. ': ' . \Components\Projects\Helpers\Html::shortenFileName($data->source_file, 40) . '</span>';
		}

		return $html;

	}

	/**
	 * Save picked items as publication attachments
	 *
	 * @return     void
	 */
	protected function _saveAttachments()
	{
		// Incoming data
		$selections 	= $this->__get('selections');
		$option  		= $this->__get('option');
		$vid  			= $this->__get('vid');
		$pid  			= $this->__get('pid');
		$uid  			= $this->__get('uid');
		$update_hash  	= $this->__get('update_hash');
		$primary  		= $this->__get('primary');
		$added  		= $this->__get('added');
		$serveas  		= $this->__get('serveas');
		$state  		= $this->__get('state');
		$secret  		= $this->__get('secret');
		$newpub  		= $this->__get('newpub');

		if (isset($selections['databases']) && count($selections['databases']) > 0 )
		{
			$database_name = $selections['databases'][0];
			$dbVersion = NULL;

			// Get database object and load record
			$objData = new \Components\Projects\Tables\Database($this->_database);
			$objData->loadRecord($database_name);

			// Load component configs
			$pubconfig = Component::params( 'com_publications' );
			$config = Component::params( 'com_projects' );

			$objPA = new \Components\Publications\Tables\Attachment( $this->_database );

			// Original database not found
			if (!$objData->id)
			{
				if ($newpub == 1)
				{
					// Can't proceed
					return false;
				}
				else
				{
					// Original got deleted, can't do much
					return true;
				}
			}

			// Build publication path
			$base_path 		= $pubconfig->get('webpath');
			$publishPath 	= \Components\Publications\Helpers\Html::buildPubPath($pid, $vid, $base_path, 'data', 1);
			$pPath 			= Route::url('index.php?option=com_publications&id=' . $pid . '&vid=' . $vid . '&task=serve');

			// Create new version path
			if (!is_dir( $publishPath ))
			{
				if (!\JFolder::create( $publishPath ))
				{
					$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_UNABLE_TO_CREATE_PATH') );
					return '<p class="error">' . $this->getError() . '</p>';
				}
			}

			// First-time clone
			if ($newpub)
			{
				$result = Event::trigger( 'projects.clone_database', array( $database_name, $this->_project, $pPath) );
				$dbVersion = $result && isset($result[0]) ? $result[0] : NULL;

				// Failed to clone
				if (!$dbVersion)
				{
					$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_ERROR_FAILED_DB_CLONE') );
					return false;
				}
			}

			// Save attachment data
			if ($objPA->loadAttachment($vid, $database_name, $this->_attachmentType))
			{
				$rtime = $objPA->modified ? strtotime($objPA->modified) : NULL;
				if ($objPA->object_id != $objData->id || strtotime($objData->updated) > $rtime )
				{
					// New database instance - need to clone again and get a new version number
					$result 			= Event::trigger( 'projects.clone_database', array( $database_name, $this->_project, $pPath) );
					$dbVersion 			= $result && isset($result[0]) ? $result[0] : NULL;
					$objPA->modified_by = $uid;
					$objPA->modified 	= Date::toSql();
				}
				else
				{
					// No changes
					$dbVersion = $objPA->object_revision;
				}
			}
			else
			{
				$objPA = new \Components\Publications\Tables\Attachment( $this->_database );
				$objPA->publication_id 			= $pid;
				$objPA->publication_version_id 	= $vid;
				$objPA->type 					= $this->_attachmentType;
				$objPA->created_by 				= $uid;
				$objPA->created 				= Date::toSql();
			}

			// We do need a revision number!
			if (!$dbVersion)
			{
				return false;
			}

			// NEW determine accompanying files and copy them in the right location
			$this->_publishDataFiles($objData, $publishPath);

			// Save object information
			$objPA->object_id   	= $objData->id;
			$objPA->object_name 	= $database_name;
			$objPA->object_revision = $dbVersion;

			// Build link path
			$objPA->path 			= 'dataviewer' . DS . 'view' . DS . 'publication:dsl'
										. DS . $database_name . DS . '?v=' . $dbVersion;

			$objPA->ordering 		= $added;
			$objPA->role 			= $primary;
			$objPA->title 			= $objPA->title ? $objPA->title : $objData->title;
			$objPA->params 			= $primary  == 1 && $serveas ? 'serveas='.$serveas : $objPA->params;

			if ($objPA->store())
			{
				$added++;
			}
		}

		return $added;
	}

	/**
	 * Publish supporting database files
	 *
	 * @param      object  	$objPD
	 *
	 * @return     boolean or error
	 */
	protected function _publishDataFiles($objPD, $publishPath = '')
	{
		if (!$objPD->id)
		{
			return false;
		}

		// Get project repo path
		$repoPath = \Components\Projects\Helpers\Html::getProjectRepoPath($this->_project->alias);

		// Get data definition
		$dd = json_decode($objPD->data_definition, true);

		$files 	 = array();
		$columns = array();

		foreach ($dd['cols'] as $colname => $col)
		{
			if (isset($col['linktype']) && $col['linktype'] == "repofiles")
			{
				$dir = '';
				if (isset($col['linkpath']) && $col['linkpath'] != '')
				{
					$dir = $col['linkpath'];
				}
				$columns[$col['idx']] = $dir;
			}
		}

		// No files to publish
		if (empty($columns))
		{
			return false;
		}

		$repoPath = $objPD->source_dir ? $repoPath . DS . $objPD->source_dir : $repoPath;
		$csv = $repoPath . DS . $objPD->source_file;

		$files = array();

		if (file_exists($csv) && ($handle = fopen($csv, "r")) !== FALSE)
		{
			// Check if expert mode CSV
			$expert_mode = false;
			$col_labels = fgetcsv($handle);
			$col_prop = fgetcsv($handle);
			$data_start = fgetcsv($handle);

			if (isset($data_start[0]) && $data_start[0] == 'DATASTART')
			{
				$expert_mode = true;
			}

			// Non expert mode
			if (!$expert_mode) {
				$handle = fopen($csv, "r");
				$col_labels = fgetcsv($handle);
			}

			while ($r = fgetcsv($handle))
			{
				for ($i = 0; $i < count($col_labels); $i++)
				{
					if (isset($columns[$i]))
					{
						if ((isset($r[$i]) && $r[$i] != ''))
						{
							$file = $columns[$i] ? $columns[$i] . DS . trim($r[$i]) : trim($r[$i]);
							if (file_exists( $repoPath . DS . $file))
							{
								$files[] = $file;
							}
						}
					}
				}
			}
		}

		// Copy files from repo to published location
		if (!empty($files))
		{
			foreach ($files as $file)
			{
				if (!file_exists( $repoPath . DS . $file))
				{
					continue;
				}

				// If parent dir does not exist, we must create it
				if (!file_exists(dirname($publishPath . DS . $file)))
				{
					\JFolder::create(dirname($publishPath . DS . $file));
				}

				\JFile::copy($repoPath . DS . $file, $publishPath . DS . $file);

				// Get file extention
				$ext = \Components\Projects\Helpers\Html::getFileExtension($file);

				// Image formats
				$image_formats = array('png', 'gif', 'jpg', 'jpeg', 'tiff', 'bmp');

				// Image file?
				if (!in_array(strtolower($ext), $image_formats))
				{
					continue;
				}

				// Generate thumbnail
				$thumb 	= \Components\Publications\Helpers\Html::createThumbName($file, '_tn', $extension = 'gif');
				$tpath  = dirname($thumb) == '.' ? $publishPath : $publishPath . DS . dirname($thumb);
				\JFile::copy($repoPath . DS . $file, $publishPath . DS . $thumb);

				$hi = new \Hubzero\Image\Processor($publishPath . DS . $thumb);
				if (count($hi->getErrors()) == 0)
				{
					$hi->resize(100, false, false, true);
					$hi->save($publishPath . DS . $thumb);
				}
				else
				{
					return false;
				}

				// Generate medium image
				$med 	= \Components\Publications\Helpers\Html::createThumbName($file, '_medium', $extension = 'gif');
				$mpath  = dirname($med) == '.' ? $publishPath : $publishPath . DS . dirname($med);
				\JFile::copy($repoPath . DS . $file, $publishPath . DS . $med);

				$hi = new \Hubzero\Image\Processor($publishPath . DS . $med);
				if (count($hi->getErrors()) == 0)
				{
					$hi->resize(800, false, false, true);
					$hi->save($publishPath . DS . $thumb);
				}
				else
				{
					return false;
				}
			}
		}
	}

	/**
	 * Publish attachments (draft submission step)
	 *
	 * @return     void
	 */
	protected function _publishAttachments()
	{
		// Incoming data
		$attachments= $this->__get('attachments');
		$row  		= $this->__get('row');
		$uid  		= $this->__get('uid');

		$published = 0;

		// Load component configs
		$pubconfig = Component::params( 'com_publications' );
		$base_path 	= $pubconfig->get('webpath');

		foreach ($attachments as $att)
		{
			if ($att->type != $this->_attachmentType)
			{
				continue;
			}

			$database_name = $att->object_name;
			$database_rev  = $att->object_revision;

			// Build publication path
			$publishPath = \Components\Publications\Helpers\Html::buildPubPath($row->publication_id, $row->id, $base_path, 'data', 1);
			$pPath = Route::url('index.php?option=com_publications&id=' . $row->publication_id . '&vid=' . $row->id . '&task=serve');

			// Get database object and load record
			$objData = new \Components\Projects\Tables\Database($this->_database);
			$objData->loadRecord($database_name);

			if (!$objData->id)
			{
				// Can't do much
				break;
			}

			// Get last record update time
			$rtime = $att->modified ? strtotime($att->modified) : NULL;

			// Db updated, clone again
			if (strtotime($objData->updated) > $rtime)
			{
				// New database instance - need to clone again and get a new version number
				$result 	= Event::trigger( 'projects.clone_database', array( $database_name, $this->_project, $pPath) );
				$dbVersion  = $result && isset($result[0]) ? $result[0] : NULL;

				// Update attatchment record with new revision & path
				if ($dbVersion)
				{
					// Make sure all data files are in the right location
					$this->_publishDataFiles($objData, $publishPath);

					$objAtt = new \Components\Publications\Tables\Attachment( $this->_database );
					$objAtt->load($att->id);
					$objAtt->path 			 = 'dataviewer' . DS . 'view' . DS . 'publication:dsl'
												. DS . $database_name . DS . '?v=' . $dbVersion;
					$objAtt->object_revision = $dbVersion;
					$objAtt->modified_by 	 = $uid;
					$objAtt->modified 		 = Date::toSql();
					$objAtt->store();
					$published++;
				}
			}
		}

		return $published;
	}
}
