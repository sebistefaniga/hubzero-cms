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

/**
 * Resources Plugin class for questions and answers
 */
class plgResourcesQuestions extends \Hubzero\Plugin\Plugin
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
	 * @param      object $resource Current resource
	 * @return     array
	 */
	public function &onResourcesAreas($model)
	{
		$areas = array();

		if (isset($model->resource->toolpublished) || isset($model->resource->revision))
		{
			if (isset($model->resource->thistool)
			 && $model->resource->thistool
			 && ($model->resource->revision=='dev' or !$model->resource->toolpublished))
			{
				$model->type->params->set('plg_questions', 0);
			}
		}
		if ($model->type->params->get('plg_questions')
			&& $model->access('view-all'))
		{
			$areas['questions'] = Lang::txt('PLG_RESOURCES_QUESTIONS');
		}

		return $areas;
	}

	/**
	 * Return data on a resource view (this will be some form of HTML)
	 *
	 * @param      object  $resource Current resource
	 * @param      string  $option    Name of the component
	 * @param      array   $areas     Active area(s)
	 * @param      string  $rtrn      Data to be returned
	 * @return     array
	 */
	public function onResources($model, $option, $areas, $rtrn='all')
	{
		$arr = array(
			'area'     => $this->_name,
			'html'     => '',
			'metadata' => ''
		);

		// Check if our area is in the array of areas we want to return results for
		if (is_array($areas))
		{
			if (!array_intersect($areas, $this->onResourcesAreas($model))
			 && !array_intersect($areas, array_keys($this->onResourcesAreas($model))))
			{
				$rtrn = 'metadata';
			}
		}
		if (!$model->type->params->get('plg_questions'))
		{
			return $arr;
		}

		$this->database = JFactory::getDBO();
		$this->model    = $model;
		$this->option   = $option;

		// Get a needed library
		require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'models' . DS . 'question.php');

		// Get all the questions for this tool
		$this->a = new \Components\Answers\Tables\Question($this->database);

		$this->filters = array(
			'limit'    => Request::getInt('limit', 0),
			'start'    => Request::getInt('limitstart', 0),
			'tag'      => ($this->model->isTool() ? 'tool:' . $this->model->resource->alias : 'resource:' . $this->model->resource->id),
			'q'        => Request::getVar('q', ''),
			'filterby' => Request::getVar('filterby', ''),
			'sortby'   => Request::getVar('sortby', 'withinplugin')
		);

		$this->count = $this->a->getCount($this->filters);

		// Are we returning HTML?
		if ($rtrn == 'all' || $rtrn == 'html')
		{
			switch (strtolower(Request::getWord('action', 'browse')))
			{
				case 'save':
					$arr['html'] = $this->_save();
				break;

				case 'new':
					$arr['html'] = $this->_new();
				break;

				case 'browse':
				default:
					$arr['html'] = $this->_browse();
				break;
			}
		}

		// Are we returning metadata?
		if ($rtrn == 'all' || $rtrn == 'metadata')
		{
			$view = new \Hubzero\Plugin\View(
				array(
					'folder'  => $this->_type,
					'element' => $this->_name,
					'name'    => 'metadata'
				)
			);
			$view->resource = $this->model->resource;
			$view->count    = $this->count;

			$arr['metadata'] = $view->loadTemplate();
		}

		// Return output
		return $arr;
	}

	/**
	 * Show a list of questions attached to this resource
	 *
	 * @return     string
	 */
	private function _browse()
	{
		// Instantiate a view
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  => $this->_type,
				'element' => $this->_name,
				'name'    => 'browse'
			)
		);

		// Are we banking?
		$upconfig = Component::params('com_members');
		$view->banking = $upconfig->get('bankAccounts');

		// Info aboit points link
		$aconfig = Component::params('com_answers');
		$view->infolink = $aconfig->get('infolink', '/kb/points/');

		// Pass the view some info
		$view->option   = $this->option;
		$view->resource = $this->model->resource;

		// Get results
		$view->rows     = $this->a->getResults($this->filters);
		$view->count    = $this->count;
		$view->limit    = $this->params->get('display_limit', 10);

		foreach ($this->getErrors() as $error)
		{
			$view->setError($error);
		}

		return $view->loadTemplate();
	}

	/**
	 * Display a form for adding a question
	 *
	 * @param      object $row
	 * @return     string
	 */
	private function _new($row=null)
	{
		// Login required
		if (User::isGuest())
		{
			$rtrn = Request::getVar('REQUEST_URI', Route::url('index.php?option=' . $this->option . '&id=' . $this->model->resource->id . '&active=' . $this->_name, false, true), 'server');

			App::redirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($rtrn)),
				Lang::txt('PLG_RESOURCES_QUESTIONS_LOGIN_TO_ASK_QUESTION'),
				'warning'
			);
			return;
		}

		$lang = JFactory::getLanguage();
		$lang->load('com_answers');

		$view = new \Hubzero\Plugin\View(
			array(
				'folder'  => $this->_type,
				'element' => $this->_name,
				'name'    => 'question',
				'layout'  => 'new'
			)
		);
		$view->option   = $this->option;
		$view->resource = $this->model->resource;
		$view->juser    = User::getRoot();
		if (is_object($row))
		{
			$view->row  = $row;
		}
		else
		{
			$view->row  = new \Components\Answers\Models\Question(0);
		}
		$view->tag      = $this->filters['tag'];

		// Are we banking?
		$upconfig = Component::params('com_members');
		$view->banking = $upconfig->get('bankAccounts');

		$view->funds = 0;
		if ($view->banking)
		{
			$BTL = new \Hubzero\Bank\Teller($this->database, User::get('id'));
			$funds = $BTL->summary() - $BTL->credit_summary();
			$view->funds = ($funds > 0) ? $funds : 0;
		}

		foreach ($this->getErrors() as $error)
		{
			$view->setError($error);
		}

		return $view->loadTemplate();
	}

	/**
	 * Save a question and redirect to the main listing when done
	 *
	 * @return     void
	 */
	private function _save()
	{
		// Login required
		if (User::isGuest())
		{
			return $this->_browse();
		}

		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		Lang::load('com_answers');

		// Incoming
		$tags   = Request::getVar('tags', '');
		$funds  = Request::getInt('funds', 0);
		$reward = Request::getInt('reward', 0);

		// If offering a reward, do some checks
		if ($reward)
		{
			// Is it an actual number?
			if (!is_numeric($reward))
			{
				App::abort(500, Lang::txt('COM_ANSWERS_REWARD_MUST_BE_NUMERIC'));
				return;
			}
			// Are they offering more than they can afford?
			if ($reward > $funds)
			{
				App::abort(500, Lang::txt('COM_ANSWERS_INSUFFICIENT_FUNDS'));
				return;
			}
		}

		// Initiate class and bind posted items to database fields
		$fields = Request::getVar('question', array(), 'post', 'none', 2);

		$row = new \Components\Answers\Models\Question($fields['id']);
		if (!$row->bind($fields))
		{
			$this->setError($row->getError());
			return $this->_new($row);
		}

		if ($reward && $this->banking)
		{
			$row->set('reward', 1);
		}

		// Ensure the user added a tag
		/*
		if (!$tags)
		{
			$this->setError(Lang::txt('COM_ANSWERS_QUESTION_MUST_HAVE_TAG'));
			return $this->_new($row);
		}
		*/

		// Store new content
		if (!$row->store(true))
		{
			$row->set('tags', $tags);

			$this->setError($row->getError());
			return $this->_new($row);
		}

		// Hold the reward for this question if we're banking
		if ($reward && $this->banking)
		{
			$BTL = new \Hubzero\Bank\Teller($this->database, User::get('id'));
			$BTL->hold($reward, Lang::txt('COM_ANSWERS_HOLD_REWARD_FOR_BEST_ANSWER'), 'answers', $row->get('id'));
		}

		// Add the tags
		$row->tag($tags);

		// Add the tag to link to the resource
		$tag = ($this->model->isTool() ? 'tool:' . $this->model->resource->alias : 'resource:' . $this->model->resource->id);
		$row->addTag($tag, User::get('id'), ($this->model->isTool() ? 0 : 1));

		// Get users who need to be notified on every question
		$config = Component::params('com_answers');
		$apu = $config->get('notify_users', '');
		$apu = explode(',', $apu);
		$apu = array_map('trim', $apu);

		$receivers = array();

		// Get tool contributors if question is about a tool
		if ($tags)
		{
			$tags = explode(',', $tags);
			if (count($tags) > 0)
			{
				require_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'author.php');
				require_once(PATH_CORE . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'version.php');

				$TA = new ToolAuthor($this->database);
				$objV = new ToolVersion($this->database);

				if ($this->model->isTool())
				{
					$toolname = $this->model->resource->alias;

					$rev = $objV->getCurrentVersionProperty($toolname, 'revision');
					$authors = $TA->getToolAuthors('', 0, $toolname, $rev);
					if (count($authors) > 0)
					{
						foreach ($authors as $author)
						{
							$receivers[] = $author->uidNumber;
						}
					}
				}
			}
		}

		if (!empty($apu))
		{
			foreach ($apu as $u)
			{
				$user = User::getInstance($u);
				if ($user)
				{
					$receivers[] = $user->get('id');
				}
			}
		}
		$receivers = array_unique($receivers);

		// Send the message
		if (!empty($receivers))
		{
			// Send a message about the new question to authorized users (specified admins or related content authors)
			$from = array(
				'email'     => Config::get('mailfrom'),
				'name'      => Config::get('sitename') . ' ' . Lang::txt('COM_ANSWERS_ANSWERS'),
				'multipart' => md5(date('U'))
			);

			// Build the message subject
			$subject = Lang::txt('COM_ANSWERS_ANSWERS') . ', ' . Lang::txt('new question about content you author or manage');

			// Build the message
			$eview = new \Hubzero\Mail\View(array(
				'base_path' => PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'site',
				'name'      => 'emails',
				'layout'    => 'question_plaintext'
			));
			$eview->option   = 'com_answers';
			$eview->jconfig  = Config::getRoot();
			$eview->sitename = Config::get('sitename');
			$eview->juser    = User::getRoot();
			$eview->question = $row;
			$eview->id       = $row->get('id', 0);
			$eview->boundary = $from['multipart'];

			$message['plaintext'] = $eview->loadTemplate(false);
			$message['plaintext'] = str_replace("\n", "\r\n", $message['plaintext']);

			// HTML message
			$eview->setLayout('question_html');

			$message['multipart'] = $eview->loadTemplate();
			$message['multipart'] = str_replace("\n", "\r\n", $message['multipart']);

			if (!Event::trigger('xmessage.onSendMessage', array('new_question_admin', $subject, $message, $from, $receivers, 'com_answers')))
			{
				$this->setError(Lang::txt('COM_ANSWERS_MESSAGE_FAILED'));
			}
		}

		// Redirect to the question
		App::redirect(
			Route::url('index.php?option=' . $this->option . '&id=' . $this->model->resource->id . '&active=' . $this->_name)
		);
	}
}

