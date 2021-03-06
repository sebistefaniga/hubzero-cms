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
 * @author    Christopher Smoak <csmoak@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Resources\Models;

use Components\Resources\Models\Import;
use Components\Resources\Tables;
use Hubzero\Base\ItemList;
use Hubzero\Base\Model;
use Exception;

// include tables
require_once dirname(dirname(__DIR__)) . DS . 'tables' . DS . 'import.php';

/**
 * Resource Import Model
 */
class Import extends Model
{
	/**
	 * Table
	 *
	 * @var  string
	 */
	protected $_tbl = null;

	/**
	 * Table name
	 *
	 * @var  string
	 */
	protected $_tbl_name = '\\Components\\Resources\\Tables\\Import';

	/**
	 * List of import runs
	 *
	 * @var  object
	 */
	protected $_runs;

	/**
	 * Constructor
	 *
	 * @param   object  $oid  Object Id
	 * @return  void
	 */
	public function __construct($oid = null)
	{
		// create needed objects
		$this->_db = \JFactory::getDBO();

		// load page jtable
		$this->_tbl = new $this->_tbl_name($this->_db);

		// load object
		if (is_numeric($oid))
		{
			$this->_tbl->load($oid);
		}
		else if (is_object($oid) || is_array($oid))
		{
			$this->bind($oid);
		}
	}

	/**
	 * Return raw import data
	 *
	 * @return  string
	 */
	public function getData()
	{
		return file_get_contents($this->getDataPath());
	}

	/**
	 * Return path to imports data file
	 *
	 * @return  string
	 */
	public function getDataPath()
	{
		// make sure we have file
		if (!$file = $this->get('file'))
		{
			throw new Exception(Lang::txt('COM_RESOURCES_IMPORT_MODEL_REQUIRED_FILE'));
		}

		// build path to file
		$filePath = $this->fileSpacePath() . DS . $file;

		// make sure file exists
		if (!file_exists($filePath))
		{
			throw new Exception(Lang::txt('COM_RESOURCES_IMPORT_MODEL_FILE_MISSING', $filePath));
		}

		// make sure we can read the file
		if (!is_readable($filePath))
		{
			throw new Exception(Lang::txt('COM_RESOURCES_IMPORT_MODEL_FILE_NOTREADABLE'));
		}

		return $filePath;
	}

	/**
	 * Return imports filespace path
	 *
	 * @access public
	 * @return string
	 */
	public function fileSpacePath()
	{
		// get com resources params
		$params = Component::params('com_resources');

		// build upload path
		$uploadPath = $params->get('import_uploadpath', '/site/resources/import');
		$uploadPath = PATH_APP . DS . trim($uploadPath, DS) . DS . $this->get('id');

		// return path
		return $uploadPath;
	}

	/**
	 * Return import runs
	 *
	 * @access public
	 * @return string
	 */
	public function runs( $rtrn = 'list', $filters = array(), $clear = false )
	{
		switch (strtolower($rtrn))
		{
			case 'current':
				if (!($this->_runs instanceof ItemList) || $clear)
				{
					$this->_runs = $this->runs('list', $filters, $clear);
				}
				return $this->_runs->first();
			break;

			case 'list':
			default:
				if (!($this->_runs instanceof ItemList) || $clear)
				{
					$tbl = new Tables\Import\Run($this->_db);
					if ($results = $tbl->find( $filters ))
					{
						foreach ($results as $key => $result)
						{
							$results[$key] = new Run($result);
						}
					}
					$this->_runs = new ItemList($results);
				}
				return $this->_runs;
		}
	}

	/**
	 * Mark Import Run
	 *
	 * @param  integer  $dryRun  Dry run mode
	 * @return void
	 */
	public function markRun($dryRun = 1)
	{
		$importRun = new Run();
		$importRun->set('import_id', $this->get('id'));
		$importRun->set('count', $this->get('count'));
		$importRun->set('ran_by', User::get('id'));
		$importRun->set('ran_at', \Date::toSql());
		$importRun->set('dry_run', $dryRun);
		$importRun->store();
	}
}