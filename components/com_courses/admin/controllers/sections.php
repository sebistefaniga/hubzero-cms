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
use Exception;
use stdClass;
use Request;
use Config;
use Route;
use Lang;

require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'section.php');
require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'offering.php');
require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'course.php');
require_once(dirname(dirname(__DIR__)) . DS . 'models' . DS . 'section' . DS . 'badge.php');

/**
 * Courses controller class for managing sections
 */
class Sections extends AdminController
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
	 * Displays a list of courses
	 *
	 * @return	void
	 */
	public function displayTask()
	{
		// Get configuration
		$app = \JFactory::getApplication();

		// Incoming
		$this->view->filters = array(
			'offering' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.offering',
				'offering',
				0
			),
			'search' => urldecode($app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.search',
				'search',
				''
			)),
			'state' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.state',
				'state',
				'-1'
			),
			// Filters for returning results
			'limit' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limit',
				'limit',
				Config::get('config.list_limit'),
				'int'
			),
			'start' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.limitstart',
				'limitstart',
				0,
				'int'
			),
			// Get sorting variables
			'sort' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.sort',
				'filter_order',
				'title'
			),
			'sort_Dir' => $app->getUserStateFromRequest(
				$this->_option . '.' . $this->_controller . '.sortdir',
				'filter_order_Dir',
				'ASC'
			)
		);

		$this->view->offering = \CoursesModelOffering::getInstance($this->view->filters['offering']);
		if (!$this->view->offering->exists())
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=courses', false)
			);
			return;
		}
		$this->view->course = \CoursesModelCourse::getInstance($this->view->offering->get('course_id'));

		// In case limit has been changed, adjust limitstart accordingly
		$this->view->filters['start'] = ($this->view->filters['limit'] != 0 ? (floor($this->view->filters['start'] / $this->view->filters['limit']) * $this->view->filters['limit']) : 0);

		$this->view->filters['count'] = true;

		$this->view->total = $this->view->offering->sections($this->view->filters);

		$this->view->filters['count'] = false;

		$this->view->rows = $this->view->offering->sections($this->view->filters);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
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

			$model = \CoursesModelSection::getInstance($id);
		}

		$this->view->row = $model;

		if (!$this->view->row->get('offering_id'))
		{
			$this->view->row->set('offering_id', Request::getInt('offering', 0));
		}

		$this->view->offering = \CoursesModelOffering::getInstance($this->view->row->get('offering_id'));
		$this->view->course   = \CoursesModelCourse::getInstance($this->view->offering->get('course_id'));
		$this->view->badge    = \CoursesModelSectionBadge::loadBySectionId($this->view->row->get('id'));

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
	 * Function to convert local timestamps to UTC timestamp
	 *
	 * @param  array $dt
	 * @return array
	 */
	private function _datesToUTC($dt)
	{
		if (isset($dt['publish_up']) && $dt['publish_up'] != '')
		{
			$dt['publish_up']   = \JFactory::getDate($dt['publish_up'], Config::get('offset'))->toSql();
		}
		if (isset($dt['publish_down']) && $dt['publish_down'] != '')
		{
			$dt['publish_down'] = \JFactory::getDate($dt['publish_down'], Config::get('offset'))->toSql();
		}
		return $dt;
	}

	/**
	 * Saves changes to a course or saves a new entry if creating
	 *
	 * @return void
	 */
	public function saveTask($redirect=true)
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$fields = Request::getVar('fields', array(), 'post');

		// Instantiate a Course object
		$model = \CoursesModelSection::getInstance($fields['id']);

		if (!$model->bind($fields))
		{
			$this->addComponentMessage($model->getError(), 'error');
			$this->editTask($model);
			return;
		}

		$p = new \JRegistry('');
		$p->loadArray(Request::getVar('params', '', 'post'));

		// Make sure the logo gets carried over
		$op = new \JRegistry($model->get('params'));
		$p->set('logo', $op->get('logo'));

		$model->set('params', $p->toString());

		if (!$model->store(true))
		{
			$this->addComponentMessage($model->getError(), 'error');
			$this->editTask($model);
			return;
		}

		$dates = Request::getVar('dates', array(), 'post');

		//$i=0;
		//$unit_up = '';
		//$unit_down = '';

		foreach ($dates as $i => $dt)
		{
			/*if (!$unit_up && $i == 0)
			{
				$unit_up = $dt['publish_up'];
			}
			if (!$unit_down && $i == 0)
			{
				$unit_down = $dt['publish_down'];
			}*/
			$dt['section_id'] = $model->get('id');
			$dt = $this->_datesToUTC($dt);

			$dtmodel = new \CoursesModelSectionDate($dt['id']);
			if (!$dtmodel->bind($dt))
			{
				$this->setError($dtmodel->getError());
				continue;
			}
			if (!$dtmodel->store(true))
			{
				$this->setError($dtmodel->getError());
				continue;
			}

			if (isset($dt['asset_group']))
			{
				foreach ($dt['asset_group'] as $j => $ag)
				{
					$ag = $this->_datesToUTC($ag);

					if (!isset($ag['publish_up']) || !$ag['publish_up'])
					{
						$ag['publish_up'] = $dt['publish_up'];
					}
					if (!isset($ag['publish_down']) || !$ag['publish_down'])
					{
						$ag['publish_down'] = $dt['publish_down'];
					}

					$ag['section_id'] = $model->get('id');

					$dtmodel = new \CoursesModelSectionDate($ag['id']);
					if (!$dtmodel->bind($ag))
					{
						$this->setError($dtmodel->getError());
						continue;
					}

					if (!$dtmodel->store(true))
					{
						$this->setError($dtmodel->getError());
						continue;
					}

					if (isset($ag['asset_group']))
					{
						foreach ($ag['asset_group'] as $k => $agt)
						{
							$agt = $this->_datesToUTC($agt);

							if (!isset($agt['publish_up']) || !$agt['publish_up'])
							{
								$agt['publish_up'] = $ag['publish_up'];
							}
							if (!isset($agt['publish_down']) || !$agt['publish_down'])
							{
								$agt['publish_down'] = $ag['publish_down'];
							}

							$agt['section_id'] = $model->get('id');

							$dtmodel = new \CoursesModelSectionDate($agt['id']);
							if (!$dtmodel->bind($agt))
							{
								$this->setError($dtmodel->getError());
								continue;
							}

							if (!$dtmodel->store(true))
							{
								$this->setError($dtmodel->getError());
								continue;
							}

							if (isset($agt['asset']))
							{
								foreach ($agt['asset'] as $z => $a)
								{
									$a = $this->_datesToUTC($a);

									if (!isset($a['publish_up']) || !$a['publish_up'])
									{
										$a['publish_up'] = $agt['publish_up'];
									}
									if (!isset($a['publish_down']) || !$a['publish_down'])
									{
										$a['publish_down'] = $agt['publish_down'];
									}

									$a['section_id'] = $model->get('id');

									$dtmodel = new \CoursesModelSectionDate($a['id']);
									if (!$dtmodel->bind($a))
									{
										$this->setError($dtmodel->getError());
										continue;
									}

									if (!$dtmodel->store(true))
									{
										$this->setError($dtmodel->getError());
										continue;
									}
									//$agt['asset'][$z] = $a;
								}
							}
							//$ag['asset_group'][$k] = $agt;
						}
					}
					if (isset($ag['asset']))
					{
						foreach ($ag['asset'] as $z => $a)
						{
							$a = $this->_datesToUTC($a);

							if (!isset($a['publish_up']) || !$a['publish_up'])
							{
								$a['publish_up'] = $ag['publish_up'];
							}
							if (!isset($a['publish_down']) || !$a['publish_down'])
							{
								$a['publish_down'] = $ag['publish_down'];
							}

							$a['section_id'] = $model->get('id');

							$dtmodel = new \CoursesModelSectionDate($a['id']);
							if (!$dtmodel->bind($a))
							{
								$this->setError($dtmodel->getError());
								continue;
							}

							if (!$dtmodel->store(true))
							{
								$this->setError($dtmodel->getError());
								continue;
							}
						}
					}
				}
			}
			if (isset($dt['asset']))
			{
				foreach ($dt['asset'] as $z => $a)
				{
					$a = $this->_datesToUTC($a);

					if (!isset($a['publish_up']) || !$a['publish_up'])
					{
						$a['publish_up'] = $dt['publish_up'];
					}
					if (!isset($a['publish_down']) || !$a['publish_down'])
					{
						$a['publish_down'] = $dt['publish_down'];
					}

					$a['section_id'] = $model->get('id');

					$dtmodel = new \CoursesModelSectionDate($a['id']);
					if (!$dtmodel->bind($a))
					{
						$this->setError($dtmodel->getError());
						continue;
					}

					if (!$dtmodel->store(true))
					{
						$this->setError($dtmodel->getError());
						continue;
					}
					//$agt['asset'][$z] = $a;
				}
			}
		}

		// Process badge info
		$badge = Request::getVar('badge', array(), 'post', 'array', JREQUEST_ALLOWHTML);
		if (isset($badge['published']) && $badge['published'])
		{
			// Get courses config
			$cconfig = Component::params('com_courses');

			// Save the basic badge content
			$badge['section_id'] = $model->get('id');
			$badgeObj = new \CoursesModelSectionBadge($badge['id']);
			$badgeObj->bind($badge);
			$badgeObj->store();

			// See if we have an image coming in as well
			$badge_image = Request::getVar('badge_image', false, 'files', 'array');

			// If so, proceed with saving the image
			if (isset($badge_image['name']) && $badge_image['name'])
			{
				// Get the file extension
				$pathinfo = pathinfo($badge_image['name']);
				$filename = $pathinfo['filename'];
				$ext      = $pathinfo['extension'];

				// Check for square and at least 420 x 420
				$dimensions = getimagesize($badge_image['tmp_name']);

				if ($dimensions[0] != $dimensions[1])
				{
					$this->setError(Lang::txt('COM_COURSES_ERROR_IMG_MUST_BE_SQUARE'));
				}
				else if ($dimensions[0] < 450)
				{
					$this->setError(Lang::txt('COM_COURSES_ERROR_IMG_MIN_WIDTH'));
				}
				else
				{
					// Build the upload path if it doesn't exist
					$uploadDirectory  = PATH_APP . DS . trim($cconfig->get('uploadpath', '/site/courses'), DS);
					$uploadDirectory .= DS . 'badges' . DS . $badgeObj->get('id') . DS;

					// Make sure upload directory exists and is writable
					if (!is_dir($uploadDirectory))
					{
						if (!\JFolder::create($uploadDirectory))
						{
							$this->setError(Lang::txt('COM_COURSES_ERROR_UNABLE_TO_CREATE_UPLOAD_PATH'));
						}
					}
					if (!is_writable($uploadDirectory))
					{
						$this->setError(Lang::txt('COM_COURSES_ERROR_UPLOAD_DIRECTORY_IS_NOT_WRITABLE'));
					}

					// Get the final file path
					$target_path = $uploadDirectory . 'badge.' . $ext;

					if (!$move = move_uploaded_file($badge_image['tmp_name'], $target_path))
					{
						$this->setError(Lang::txt('COM_COURSES_ERROR_FILE_MOVE_FAILED'));
					}
					else
					{
						// Move successful, save the image url to the badge entry
						$img_url = DS . 'courses' . DS . 'badge' . DS . $badgeObj->get('id') . DS . 'image';
						$badgeObj->bind(array('img_url'=>$img_url));
						$badgeObj->store();
					}
				}
			}

			// Process criteria text
			if (strcmp($badgeObj->get('criteria_text'), $badge['criteria']))
			{
				$badgeObj->set('criteria_text_new', $badge['criteria']);
				$badgeObj->store();
				$badgeObj->set('criteria_text_new', NULL);
			}

			// If we don't already have a provider badge id set, then we're processing our initial badge creation
			if ($badgeObj->get('provider_name') && !$badgeObj->get('provider_badge_id') && $badgeObj->get('img_url'))
			{
				$request_type   = $cconfig->get('badges_request_type', 'oauth');
				$badgesHandler  = new \Hubzero\Badges\Wallet(strtoupper($badgeObj->get('provider_name')), $request_type);
				$badgesProvider = $badgesHandler->getProvider();

				if (is_object($badgesProvider))
				{
					$credentials = new stdClass();
					$credentials->consumer_key    = $cconfig->get($badgeObj->get('provider_name').'_consumer_key', 0);
					$credentials->consumer_secret = $cconfig->get($badgeObj->get('provider_name').'_consumer_secret', 0);
					$credentials->issuerId        = $cconfig->get($badgeObj->get('provider_name').'_issuer_id');;
					$badgesProvider->setCredentials($credentials);

					$offering = \CoursesModelOffering::getInstance($model->get('offering_id'));
					$course   = \CoursesModelCourse::getInstance($offering->get('course_id'));

					$data = array();
					$data['Name']          = $course->get('title');
					$data['Description']   = trim($course->get('title')) . ' Badge';
					$data['CriteriaUrl']   = rtrim(Request::root(), '/') . '/courses/badge/' . $badgeObj->get('id') . '/criteria';
					$data['Version']       = '1';
					$data['BadgeImageUrl'] = rtrim(Request::root(), '/') . '/' . trim($badgeObj->get('img_url'), '/');

					if (!$credentials->consumer_key || !$credentials->consumer_secret)
					{
						$this->setError(Lang::txt('COM_COURSES_ERROR_BADGE_MISSING_OPTIONS'));
					}
					else
					{
						try
						{
							$provider_badge_id = $badgesProvider->createBadge($data);
						}
						catch (Exception $e)
						{
							$this->setError($e->getMessage());
						}

						if (isset($provider_badge_id) && $provider_badge_id)
						{
							// We've successfully created a badge, so save that id to the database
							$badgeObj->bind(array('provider_badge_id' => $provider_badge_id));
							$badgeObj->store();
						}
						else
						{
							$this->setError(Lang::txt('COM_COURSES_ERROR_FAILED_TO_SAVE_BADGE'));
						}
					}
				}
			}
		}
		elseif ($badge['id']) // badge exists and is being unpublished
		{
			$badgeObj = new \CoursesModelSectionBadge($badge['id']);
			$badgeObj->bind(array('published' => 0));
			$badgeObj->store();
		}

		if ($this->getError())
		{
			$this->addComponentMessage(implode('<br />', $this->getErrors()), 'error');
			$this->editTask($model);
			return;
		}

		if ($this->_task == 'apply')
		{
			return $this->editTask($model);
		}

		// Output messsage and redirect
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . $model->get('offering_id'), false),
			Lang::txt('COM_COURSES_ITEM_SAVED')
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
		$ids = (!is_array($ids) ? array($ids) : $ids);
		$offering_id = Request::getInt('offering', 0);

		$num = 0;

		// Do we have any IDs?
		if (!empty($ids))
		{
			$offering_id = 0;

			foreach ($ids as $id)
			{
				// Load the course page
				$model = \CoursesModelSection::getInstance($id);

				// Ensure we found the course info
				if (!$model->exists())
				{
					continue;
				}

				$offering_id = $model->get('offering_id');

				// Delete course
				if (!$model->delete())
				{
					throw new Exception(Lang::txt('COM_COURSES_ERROR_UNABLE_TO_REMOVE_ENTRY'), 500);
				}

				$num++;
			}

			if ($num && $offering_id)
			{
				$filters = array(
					'count'       => true,
					'offering_id' => $offering_id,
					'is_default'  => 1
				);
				$offering = \CoursesModelOffering::getInstance($filters['offering_id']);

				if (!$offering->sections($filters))
				{
					$sections = $offering->sections(array(
						'count'    => false,
						'sort'     => 'id',
						'sort_Dir' => 'ASC',
						'limit'    => 1,
						'start'    => 0
					));
					foreach ($sections as $section)
					{
						$section->makeDefault();
					}
				}
			}
		}

		// Redirect back to the courses page
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . $offering_id, false),
			Lang::txt('COM_COURSES_ITEMS_REMOVED', $num)
		);
	}

	/**
	 * Make a section the default one
	 *
	 * @return	void
	 */
	public function makedefaultTask()
	{
		// Incoming
		$id = Request::getVar('id', 0);

		// Get the single ID we're working with
		if (is_array($id))
		{
			$id = (!empty($id)) ? $id[0] : 0;
		}

		$row = \CoursesModelSection::getInstance($id);
		$row->makeDefault();

		// Redirect back to the courses page
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false)
		);
	}

	/**
	 * Set the state of a course
	 *
	 * @return void
	 */
	public function stateTask($state=0)
	{
		// Check for request forgeries
		Request::checkToken('get') or Request::checkToken() or jexit('Invalid Token');

		$state = $this->_task == 'publish' ? 1 : 0;

		// Incoming
		$ids = Request::getVar('id', array());
		$ids = (!is_array($ids) ? array($ids) : $ids);

		// Do we have any IDs?
		$num = 0;
		if (!empty($ids))
		{
			// foreach course id passed in
			foreach ($ids as $id)
			{
				// Load the course page
				$section = \CoursesModelSection::getInstance($id);

				// Ensure we found the course info
				if (!$section->exists())
				{
					continue;
				}

				//set the course to be published and update
				$section->set('state', $state);
				if (!$section->store())
				{
					$this->setError(Lang::txt('COM_COURSES_ERROR_UNABLE_TO_SET_STATE', $id));
					continue;
				}

				$num++;
			}
		}

		if ($this->getErrors())
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				implode('<br />', $this->getErrors()),
				'error'
			);
		}
		else
		{
			// Output messsage and redirect
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false),
				($state ? Lang::txt('COM_COURSES_ITEMS_PUBLISHED', $num) : Lang::txt('COM_COURSES_ITEMS_UNPUBLISHED', $num))
			);
		}
	}

	/**
	 * Cancel a task (redirects to default task)
	 *
	 * @return	void
	 */
	public function cancelTask()
	{
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&offering=' . Request::getInt('offering', 0), false)
		);
	}
}
