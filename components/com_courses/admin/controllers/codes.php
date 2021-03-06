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

namespace Components\Courses\Admin\Controllers;

use Hubzero\Component\AdminController;
use PHPQRCode\QRcode;
use Exception;

require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'course.php');

/**
 * Courses controller class for membership codes
 */
class Codes extends AdminController
{
	/**
	 * Displays a list of codes
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		// Get configuration
		$app = \JFactory::getApplication();

		// Incoming
		$this->view->filters = array(
			'section' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.section',
				'section',
				0
			),
			'search' => urldecode($app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.search',
				'search',
				''
			)),
			'redeemed' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.redeemed',
				'redeemed',
				'-1'
			),
			// Filters for returning results
			'limit' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limit',
				'limit',
				Config::get('list_limit'),
				'int'
			),
			'start' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limitstart',
				'limitstart',
				0,
				'int'
			)
		);

		$this->view->section = \CoursesModelSection::getInstance($this->view->filters['section']);
		if (!$this->view->section->exists())
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=courses', false)
			);
			return;
		}
		$this->view->offering = \CoursesModelOffering::getInstance($this->view->section->get('offering_id'));
		$this->view->course = \CoursesModelCourse::getInstance($this->view->offering->get('course_id'));

		// In case limit has been changed, adjust limitstart accordingly
		$this->view->filters['start'] = ($this->view->filters['limit'] != 0 ? (floor($this->view->filters['start'] / $this->view->filters['limit']) * $this->view->filters['limit']) : 0);

		$this->view->filters['count'] = true;

		$this->view->total = $this->view->section->codes($this->view->filters);

		$this->view->filters['count'] = false;

		$this->view->rows = $this->view->section->codes($this->view->filters);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Create a new course
	 *
	 * @return  void
	 */
	public function addTask()
	{
		$this->editTask();
	}

	/**
	 * Displays an edit form
	 *
	 * @return  void
	 */
	public function editTask($model=null)
	{
		Request::setVar('hidemainmenu', 1);

		if (!is_object($model))
		{
			// Incoming
			$id = Request::getVar('id', array(0));

			// Get the single ID we're working with
			if (is_array($id))
			{
				$id = (!empty($id)) ? $id[0] : 0;
			}

			$model = new \CoursesModelSectionCode($id);
		}

		$this->view->row = $model;

		if (!$this->view->row->get('offering_id'))
		{
			$this->view->row->set('offering_id', Request::getInt('offering', 0));
		}

		$this->view->section = \CoursesModelSection::getInstance($this->view->row->get('section_id'));

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
	 * Saves changes to a course or saves a new entry if creating
	 *
	 * @return void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$fields = Request::getVar('fields', array(), 'post');

		// Instantiate a Course object
		$model = new \CoursesModelSectionCode($fields['id']);

		if (!$model->bind($fields))
		{
			$this->addComponentMessage($model->getError(), 'error');
			$this->editTask($model);
			return;
		}

		if (!$model->store(true))
		{
			$this->addComponentMessage($model->getError(), 'error');
			$this->editTask($model);
			return;
		}

		// Output messsage and redirect
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&section=' . $model->get('section_id'), false),
			Lang::txt('COM_COURSES_CODE_SAVED')
		);
	}

	/**
	 * Removes a course and all associated information
	 *
	 * @return	void
	 */
	public function deleteTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$ids = Request::getVar('id', array());

		// Get the single ID we're working with
		if (!is_array($ids))
		{
			$ids = array();
		}

		$num = 0;

		// Do we have any IDs?
		if (!empty($ids))
		{
			foreach ($ids as $id)
			{
				// Load the code
				$model = new \CoursesModelSectionCode($id);

				// Ensure we found a record
				if (!$model->exists())
				{
					continue;
				}

				// Delete record
				if (!$model->delete())
				{
					throw new Exception(Lang::txt('COM_COURSES_ERROR_UNABLE_TO_REMOVE_ENTRY'), 500);
				}

				$num++;
			}
		}

		// Redirect back to the courses page
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&section=' . Request::getInt('section', 0), false),
			Lang::txt('COM_COURSES_ITEMS_REMOVED', $num)
		);
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return  void
	 */
	public function generateTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		$section = Request::getInt('section', 0);
		$num = Request::getInt('num', 1);

		$expires = Request::getVar('expires', array());
		$expires = implode('-', $expires) . ' 12:00:00';

		if ($num > 0)
		{
			$codes = array();
			for ($i = 0; $i < $num; $i++)
			{
				$model = new \CoursesModelSectionCode(0);
				$model->set('code', $this->_generateCode());
				$model->set('section_id', $section);
				$model->set('expires', $expires);
				if (!$model->store(true))
				{
					$this->setError($model->getError());
				}
			}
		}

		if ($this->getError())
		{
			echo implode('<br />', $this->getErrors());
			die();
		}

		if (!Request::getInt('no_html', 0))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&section=' . $section, false)
			);
		}
	}

	/**
	 * Generate QR code
	 *
	 * @return  void
	 */
	public function qrcodeTask()
	{
		$no_html = Request::getInt('no_html', 0);
		$code = Request::getVar('code');

		if (!$code)
		{
			throw new Exception(Lang::txt('No code provided'), 500);
		}

		$url = rtrim(Request::base(), '/') . '/' . ltrim(Route::url('index.php?option=' . $this->_option . '&controller=courses&task=redeem&code=' . $code), '/');

		if ($no_html)
		{
			echo QRcode::png($url);
			return;
		}

		echo QRcode::text($url);
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return  void
	 */
	public function optionsTask()
	{
		$section = Request::getInt('section', 0);

		$this->view->section = \CoursesModelSection::getInstance($section);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Generate a coupon code
	 *
	 * @return  string
	 */
	private function _generateCode()
	{
		$chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$res = '';
		for ($i = 0; $i < 10; $i++)
		{
			$res .= $chars[mt_rand(0, strlen($chars)-1)];
		}
		return $res;
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return  void
	 */
	public function cancelTask()
	{
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&section=' . Request::getInt('section', 0), false)
		);
	}

	/**
	 * Quote a value for a CSV file
	 *
	 * @param   string  $val
	 * @return  string
	 */
	public static function quoteCsv($val)
	{
		if (!isset($val))
		{
			return '';
		}

		if (strpos($val, "\n") !== false || strpos($val, ',') !== false)
		{
			return '"' . str_replace(array('\\', '"'), array('\\\\', '""'), $val) . '"';
		}

		return $val;
	}

	/**
	 * Quote a CSV row
	 *
	 * @param   array   $vals 
	 * @return  string
	 */
	public function quoteCsvRow($vals)
	{
		return implode(',', array_map(array($this, 'quoteCsv'), $vals)) . "\n";
	}

	/**
	 * Export codes as a CSV file
	 *
	 * @return  void
	 */
	public function exportTask()
	{
		$fields  = array('id', 'code', 'created', 'expires', 'redeemed', 'redeemed by');
		$rows    = array();
		$section = Request::getInt('section', 0);

		if (!$section)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&section=' . Request::getInt('section', 0), false),
				Lang::txt('No section specified'),
				'warning'
			);
			return;
		}

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (is_array($ids) ? $ids : array($ids));

		// Do we have any IDs?
		if (empty($ids))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&section=' . Request::getInt('section', 0), false),
				Lang::txt('No codes selected'),
				'warning'
			);
			return;
		}

		// Output header
		@ob_end_clean();

		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Expires: 0");

		header("Content-Transfer-Encoding: binary");
		header('Content-type: text/comma-separated-values');
		header('Content-disposition: attachment; filename="section_' . $section . '_codes.csv"');

		echo $this->quoteCsvRow($fields);

		foreach ($ids as $id)
		{
			// Load the code
			$model = new \CoursesModelSectionCode($id);

			// Ensure we found a record
			if (!$model->exists())
			{
				continue;
			}

			$row = array(
				$model->get('id'),
				$model->get('code'),
				$model->get('created'),
				$model->get('expires'),
				$model->get('redeemed'),
				$model->get('redeemed_by'),
			);

			echo $this->quoteCsvRow($row);
		}

		exit;
	}
}
