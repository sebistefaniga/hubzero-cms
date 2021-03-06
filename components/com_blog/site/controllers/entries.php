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

namespace Components\Blog\Site\Controllers;

use Components\Blog\Models\Archive;
use Components\Blog\Models\Comment;
use Components\Blog\Tables;
use Hubzero\Component\SiteController;
use Hubzero\Utility\String;
use Hubzero\Utility\Sanitize;
use Exception;
use Request;
use Pathway;
use Lang;
use Route;
use User;
use Date;

/**
 * Blog controller class for entries
 */
class Entries extends SiteController
{
	/**
	 * Determines task being called and attempts to execute it
	 *
	 * @return  void
	 */
	public function execute()
	{
		$this->model = new Archive('site', 0);

		$this->_authorize();
		$this->_authorize('entry');
		$this->_authorize('comment');

		$this->registerTask('comments.rss', 'comments');
		$this->registerTask('commentsrss', 'comments');

		$this->registerTask('feed.rss', 'feed');
		$this->registerTask('feedrss', 'feed');

		$this->registerTask('archive', 'display');

		parent::execute();
	}

	/**
	 * Method to set the document path
	 *
	 * @return  void
	 */
	protected function _buildPathway()
	{
		$title = ($this->config->get('title')) ? $this->config->get('title') : Lang::txt(strtoupper($this->_option));

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				$title,
				'index.php?option=' . $this->_option
			);
		}
		if ($this->_task && $this->_task != 'display')
		{
			if ($this->_task != 'entry' && $this->_task != 'savecomment' && $this->_task != 'deletecomment')
			{
				Pathway::append(
					Lang::txt(strtoupper($this->_option) . '_' . strtoupper($this->_task)),
					'index.php?option=' . $this->_option . '&task=' . $this->_task
				);
			}
			$year = Request::getInt('year', 0);
			if ($year)
			{
				Pathway::append(
					$year,
					'index.php?option=' . $this->_option . '&year=' . $year
				);
			}
			$month = Request::getInt('month', 0);
			if ($month)
			{
				Pathway::append(
					sprintf("%02d",$month),
					'index.php?option=' . $this->_option . '&year=' . $year . '&month=' . sprintf("%02d", $month)
				);
			}
			if (isset($this->view->row))
			{
				Pathway::append(
					stripslashes($this->view->row->get('title')),
					'index.php?option=' . $this->_option . '&year=' . $year . '&month=' . sprintf("%02d", $month) . '&alias=' . $this->view->row->get('alias')
				);
			}
		}
	}

	/**
	 * Method to build and set the document title
	 *
	 * @return	void
	 */
	protected function _buildTitle()
	{
		$this->_title = ($this->config->get('title')) ? $this->config->get('title') : Lang::txt(strtoupper($this->_option));
		if ($this->_task && $this->_task != 'display')
		{
			if ($this->_task != 'entry' && $this->_task != 'savecomment' && $this->_task != 'deletecomment')
			{
				$this->_title .= ': ' . Lang::txt(strtoupper($this->_option) . '_' . strtoupper($this->_task));
			}
			$year = Request::getInt('year', 0);
			if ($year)
			{
				$this->_title .= ': ' . $year;
			}
			$month = Request::getInt('month', 0);
			if ($month)
			{
				$this->_title .= ': ' . sprintf("%02d", $month);
			}
			if (isset($this->view->row))
			{
				$this->_title .= ': ' . stripslashes($this->view->row->get('title'));
			}
		}

		\JFactory::getDocument()->setTitle($this->_title);
	}

	/**
	 * Display a list of entries
	 *
	 * @return     void
	 */
	public function displayTask()
	{
		$this->view->config = $this->config;

		// Filters for returning results
		$this->view->filters = array(
			'limit'      => Request::getInt('limit', Config::get('list_limit')),
			'start'      => Request::getInt('limitstart', 0),
			'year'       => Request::getInt('year', 0),
			'month'      => Request::getInt('month', 0),
			'scope'      => $this->config->get('show_from', 'site'),
			'scope_id'   => 0,
			'search'     => Request::getVar('search', ''),
			'authorized' => false,
			'state'      => 'public'
		);
		if ($this->view->filters['scope'] == 'both')
		{
			$this->view->filters['scope'] = '';
		}

		if (!User::isGuest())
		{
			$this->view->filters['state'] = 'registered';

			if ($this->view->config->get('access-manage-component'))
			{
				$this->view->filters['state']      = 'all';
				$this->view->filters['authorized'] = true;
			}
		}

		$this->view->model = $this->model;
		$this->view->year  = $this->view->filters['year'];
		$this->view->month = $this->view->filters['month'];

		$this->_buildTitle();
		$this->_buildPathway();

		$this->view->title = $this->config->get('title', Lang::txt(strtoupper($this->_option)));

		// Get any errors for display
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output HTML
		$this->view->display();
	}

	/**
	 * Display an entry
	 *
	 * @return     void
	 */
	public function entryTask()
	{
		$this->view->config = $this->config;
		$this->view->model  = $this->model;

		$alias = Request::getVar('alias', '');

		if (!$alias)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller)
			);
			return;
		}

		$this->view->row = $this->model->entry($alias);

		if (!$this->view->row->exists())
		{
			throw new Exception(Lang::txt('COM_BLOG_NOT_FOUND'), 404);
		}

		// Check authorization
		if (!$this->view->row->access('view'))
		{
			throw new Exception(Lang::txt('COM_BLOG_NOT_AUTH'), 403);
		}

		// Filters for returning results
		$this->view->filters = array(
			'limit'    => 10,
			'start'    => 0,
			'scope'    => 'site',
			'scope_id' => 0
		);

		if (User::isGuest())
		{
			$this->view->filters['state'] = 'public';
		}
		else
		{
			if (!$this->view->config->get('access-manage-component'))
			{
				$this->view->filters['state'] = 'registered';
			}
		}

		// Push some scripts to the template
		$this->_buildTitle();
		$this->_buildPathway();

		$this->view->title = $this->config->get('title', Lang::txt(strtoupper($this->_option)));

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view
			->setLayout('entry')
			->display();
	}

	/**
	 * Show a form for creating an entry
	 *
	 * @return     void
	 */
	public function newTask()
	{
		return $this->editTask();
	}

	/**
	 * Show a form for editing an entry
	 *
	 * @return     void
	 */
	public function editTask($row = null)
	{
		if (User::isGuest())
		{
			$rtrn = Request::getVar('REQUEST_URI', Route::url('index.php?option=' . $this->_option . '&task=' . $this->_task, false, true), 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($rtrn)),
				Lang::txt('COM_BLOG_LOGIN_NOTICE'),
				'warning'
			);
			return;
		}

		if (is_object($row))
		{
			$this->view->entry = $row;
		}
		else
		{
			$this->view->entry = $this->model->entry(Request::getInt('entry', 0));
		}

		if (!$this->view->entry->exists())
		{
			$this->view->entry->set('allow_comments', 1);
			$this->view->entry->set('state', 1);
			$this->view->entry->set('scope', 'site');
			$this->view->entry->set('created_by', User::get('id'));
		}

		// Push some scripts to the template
		$this->_buildTitle();
		$this->_buildPathway();

		$this->view->title = $this->config->get('title', Lang::txt(strtoupper($this->_option)));

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view
			->setLayout('edit')
			->display();
	}

	/**
	 * Save entry to database
	 *
	 * @return     void
	 */
	public function saveTask()
	{
		if (User::isGuest())
		{
			$rtrn = Request::getVar('REQUEST_URI', Route::url('index.php?option=' . $this->_option . '&task=' . $this->_task), 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($rtrn)),
				Lang::txt('COM_BLOG_LOGIN_NOTICE'),
				'warning'
			);
			return;
		}

		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		$entry = Request::getVar('entry', array(), 'post', 'none', 2);

		// Make sure we don't want to turn off comments
		$entry['allow_comments'] = (isset($entry['allow_comments'])) ? : 0;

		if (isset($entry['publish_up']) && $entry['publish_up'] != '')
		{
			$entry['publish_up']   = Date::of($entry['publish_up'], Config::get('offset'))->toSql();
		}
		if (isset($entry['publish_down']) && $entry['publish_down'] != '')
		{
			$entry['publish_down'] = Date::of($entry['publish_down'], Config::get('offset'))->toSql();
		}

		$row = $this->model->entry(0);

		if (!$row->bind($entry))
		{
			$this->setError($row->getError());
			return $this->editTask($row);
		}

		// Store new content
		if (!$row->store(true))
		{
			$this->setError($row->getError());
			return $this->editTask($row);
		}

		// Process tags
		if (!$row->tag(Request::getVar('tags', '')))
		{
			$this->setError($row->getError());
			return $this->editTask($row);
		}

		$this->setRedirect(
			Route::url($row->link())
		);
	}

	/**
	 * Mark an entry as deleted
	 *
	 * @return  void
	 */
	public function deleteTask()
	{
		if (User::isGuest())
		{
			$rtrn = Request::getVar('REQUEST_URI', Route::url('index.php?option=' . $this->_option, false, true), 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($rtrn)),
				Lang::txt('COM_BLOG_LOGIN_NOTICE'),
				'warning'
			);
			return;
		}

		if (!$this->config->get('access-delete-entry'))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option),
				Lang::txt('COM_BLOG_NOT_AUTHORIZED'),
				'error'
			);
			return;
		}

		// Incoming
		$id = Request::getInt('entry', 0);
		if (!$id)
		{
			return $this->displayTask();
		}

		$process    = Request::getVar('process', '');
		$confirmdel = Request::getVar('confirmdel', '');

		// Initiate a blog entry object
		$entry = $this->model->entry($id);

		// Did they confirm delete?
		if (!$process || !$confirmdel)
		{
			if ($process && !$confirmdel)
			{
				$this->setError(Lang::txt('COM_BLOG_ERROR_CONFIRM_DELETION'));
			}

			// Push some scripts to the template
			$this->_buildTitle();
			$this->_buildPathway();

			// Output HTML
			$this->view->title  = ($this->config->get('title')) ? $this->config->get('title') : Lang::txt(strtoupper($this->_option));
			$this->view->entry  = $entry;
			$this->view->config = $this->config;
			if ($this->getError())
			{
				foreach ($this->getErrors() as $error)
				{
					$this->view->setError($error);
				}
			}
			$this->view->display();
			return;
		}

		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Delete the entry itself
		$entry->set('state', -1);
		if (!$entry->store())
		{
			$this->setError($entry->getError());
		}

		// Return the topics list
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option)
		);
		return;
	}

	/**
	 * Generate an RSS feed of entries
	 *
	 * @return     string RSS
	 */
	public function feedTask()
	{
		if (!$this->config->get('feeds_enabled'))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option)
			);
			return;
		}

		include_once(JPATH_ROOT . DS . 'libraries' . DS . 'joomla' . DS . 'document' . DS . 'feed' . DS . 'feed.php');

		// Set the mime encoding for the document
		$jdoc = \JFactory::getDocument();
		$jdoc->setMimeEncoding('application/rss+xml');

		// Start a new feed object
		$doc = new \JDocumentFeed;
		$doc->link = Route::url('index.php?option=' . $this->_option);

		// Incoming
		$filters = array(
			'limit'    => Request::getInt('limit', Config::get('list_limit')),
			'start'    => Request::getInt('limitstart', 0),
			'year'     => Request::getInt('year', 0),
			'month'    => Request::getInt('month', 0),
			'scope'    => 'site',
			'scope_id' => 0,
			'search'   => Request::getVar('search','')
		);

		if (User::isGuest())
		{
			$filters['state'] = 'public';
		}
		else
		{
			if (!$this->config->get('access-manage-component'))
			{
				$filters['state'] = 'registered';
			}
		}

		// Build some basic RSS document information
		$doc->title  = Config::get('sitename') . ' - ' . Lang::txt(strtoupper($this->_option));
		$doc->title .= ($filters['year'])  ? ': ' . $filters['year'] : '';
		$doc->title .= ($filters['month']) ? ': ' . sprintf("%02d", $filters['month']) : '';

		$doc->description = Lang::txt('COM_BLOG_RSS_DESCRIPTION', Config::get('sitename'));
		$doc->copyright   = Lang::txt('COM_BLOG_RSS_COPYRIGHT', date("Y"), Config::get('sitename'));
		$doc->category    = Lang::txt('COM_BLOG_RSS_CATEGORY');

		// Get the records
		$rows = $this->model->entries('list', $filters);

		// Start outputing results if any found
		if ($rows->total() > 0)
		{
			foreach ($rows as $row)
			{
				$item = new \JFeedItem();

				// Strip html from feed item description text
				$item->description = $row->content('parsed');
				$item->description = html_entity_decode(Sanitize::stripAll($item->description));
				if ($this->config->get('feed_entries') == 'partial')
				{
					$item->description = String::truncate($item->description, 300);
				}

				// Load individual item creator class
				$item->title       = html_entity_decode(strip_tags($row->get('title')));
				$item->link        = Route::url($row->link());
				$item->date        = date('r', strtotime($row->published()));
				$item->category    = '';
				$item->author      = $row->creator('name');

				// Loads item info into rss array
				$doc->addItem($item);
			}
		}

		// Output the feed
		echo $doc->render();
		die;
	}

	/**
	 * Save a comment
	 *
	 * @return     void
	 */
	public function savecommentTask()
	{
		// Ensure the user is logged in
		if (User::isGuest())
		{
			$rtrn = Request::getVar('REQUEST_URI', Route::url('index.php?option=' . $this->_option), 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($rtrn)),
				Lang::txt('COM_BLOG_LOGIN_NOTICE'),
				'warning'
			);
			return;
		}

		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Incoming
		$comment = Request::getVar('comment', array(), 'post', 'none', 2);

		// Instantiate a new comment object and pass it the data
		$row = new Comment($comment['id']);
		if (!$row->bind($comment))
		{
			$this->setError($row->getError());
			return $this->entryTask();
		}

		// Store new content
		if (!$row->store(true))
		{
			$this->setError($row->getError());
			return $this->entryTask();
		}

		/*
		$entry = new Entry($row->get('entry_id'));

		if ($row->get('created_by') != $entry->get('created_by'))
		{
			// Build the "from" data for the e-mail
			$from = array(
				'name'  => Config::get('sitename').' '.Lang::txt('PLG_MEMBERS_BLOG'),
				'email' => Config::get('mailfrom')
			);

			$subject = Lang::txt('PLG_MEMBERS_BLOG_SUBJECT_COMMENT_POSTED');

			// Build the SEF referenced in the message
			$sef = Route::url($entry->link() . '#comments);

			// Message
			$message  = "The following comment has been posted to your blog entry:\r\n\r\n";
			$message .= stripslashes($row->content)."\r\n\r\n";
			$message .= "To view all comments on the blog entry, go to:\r\n";
			$message .= Request::base() . '/' . ltrim($sef, '/') . "\r\n";

			// Send the message
			$activity = array(
				'blog_comment',
				$subject,
				$message,
				$from,
				array($entry->get('created_by')), $this->option)
			);

			if (!Event::trigger('xmessage.onSendMessage', $activity)
			{
				$this->setError(Lang::txt('PLG_MEMBERS_BLOG_ERROR_MSG_MEMBER_FAILED'));
			}
		}
		*/

		return $this->entryTask();
	}

	/**
	 * Delete a comment
	 *
	 * @return  void
	 */
	public function deletecommentTask()
	{
		// Ensure the user is logged in
		if (User::isGuest())
		{
			$this->setError(Lang::txt('COM_BLOG_LOGIN_NOTICE'));
			return $this->entryTask();
		}

		// Incoming
		$id    = Request::getInt('comment', 0);
		$year  = Request::getVar('year', '');
		$month = Request::getVar('month', '');
		$alias = Request::getVar('alias', '');

		if (!$id)
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&year=' . $year . '&month=' . $month . '&alias=' . $alias)
			);
			return;
		}

		// Initiate a blog comment object
		$comment = new Tables\Comment($this->database);
		$comment->load($id);

		if (User::get('id') != $comment->created_by && !$this->config->get('access-delete-comment'))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&year=' . $year . '&month=' . $month . '&alias=' . $alias)
			);
			return;
		}

		// Mark all comments as deleted
		$comment->setState($id, 2);

		// Return the topics list
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option . '&year=' . $year . '&month=' . $month . '&alias=' . $alias),
			($this->getError() ? $this->getError() : null),
			($this->getError() ? 'error' : null)
		);
	}

	/**
	 * Display an RSS feed of comments
	 *
	 * @return  string  RSS
	 */
	public function commentsTask()
	{
		if (!$this->config->get('feeds_enabled'))
		{
			throw new Exception(Lang::txt('Feed not found.'), 404);
		}

		include_once(JPATH_ROOT . DS . 'libraries' . DS . 'joomla' . DS . 'document' . DS . 'feed' . DS . 'feed.php');

		// Set the mime encoding for the document
		$jdoc = \JFactory::getDocument();
		$jdoc->setMimeEncoding('application/rss+xml');

		// Start a new feed object
		$doc = new \JDocumentFeed;
		$doc->link = Route::url('index.php?option=' . $this->_option);

		// Incoming
		$alias = Request::getVar('alias', '');
		if (!$alias)
		{
			throw new Exception(Lang::txt('Feed not found.'), 404);
		}

		$this->entry = $this->model->entry($alias);

		if (!$this->entry->isAvailable())
		{
			throw new Exception(Lang::txt('Feed not found.'), 404);
		}

		$year  = Request::getInt('year', date("Y"));
		$month = Request::getInt('month', 0);

		// Build some basic RSS document information
		$doc->title  = Config::get('sitename') . ' - ' . Lang::txt(strtoupper($this->_option));
		$doc->title .= ($year) ? ': ' . $year : '';
		$doc->title .= ($month) ? ': ' . sprintf("%02d", $month) : '';
		$doc->title .= stripslashes($this->entry->get('title', ''));
		$doc->title .= ': ' . Lang::txt('Comments');

		$doc->description = Lang::txt('COM_BLOG_COMMENTS_RSS_DESCRIPTION', Config::get('sitename'), stripslashes($this->entry->get('title')));
		$doc->copyright   = Lang::txt('COM_BLOG_RSS_COPYRIGHT', date("Y"), Config::get('sitename'));

		$rows = $this->entry->comments('list');

		// Start outputing results if any found
		if ($rows->total() <= 0)
		{
			echo $doc->render();
			return;
		}

		foreach ($rows as $row)
		{
			$this->_comment($doc, $row);
		}

		// Output the feed
		echo $doc->render();
	}

	/**
	 * Recursive method to add comments to a flat RSS feed
	 *
	 * @param   object $doc JDocumentFeed
	 * @param   object $row BlogModelComment
	 * @return	void
	 */
	private function _comment(&$doc, $row)
	{
		// Load individual item creator class
		$item = new \JFeedItem();
		$item->title = Lang::txt('Comment #%s', $row->get('id')) . ' @ ' . $row->created('time') . ' on ' . $row->created('date');
		$item->link  = Route::url($this->entry->link()  . '#c' . $row->get('id'));

		if ($row->isReported())
		{
			$item->description = Lang::txt('COM_BLOG_COMMENT_REPORTED_AS_ABUSIVE');
		}
		else
		{
			$item->description = html_entity_decode(Sanitize::stripAll($row->content('clean')));
		}

		if ($row->get('anonymous'))
		{
			$item->author = Lang::txt('COM_BLOG_ANONYMOUS');
		}
		else
		{
			$item->author = $row->creator('name');
		}
		$item->date     = $row->created();
		$item->category = '';

		$doc->addItem($item);

		if ($row->replies()->total() > 0)
		{
			foreach ($row->replies() as $reply)
			{
				$this->_comment($doc, $reply);
			}
		}
	}

	/**
	 * Method to check admin access permission
	 *
	 * @return  boolean  True on success
	 */
	protected function _authorize($assetType='component', $assetId=null)
	{
		$this->config->set('access-view-' . $assetType, true);

		if (!User::isGuest())
		{
			$asset  = $this->_option;
			if ($assetId)
			{
				$asset .= ($assetType != 'component') ? '.' . $assetType : '';
				$asset .= ($assetId) ? '.' . $assetId : '';
			}

			$at = '';
			if ($assetType != 'component')
			{
				$at .= '.' . $assetType;
			}

			// Admin
			$this->config->set('access-admin-' . $assetType, User::authorise('core.admin', $asset));
			$this->config->set('access-manage-' . $assetType, User::authorise('core.manage', $asset));
			// Permissions
			$this->config->set('access-create-' . $assetType, User::authorise('core.create' . $at, $asset));
			$this->config->set('access-delete-' . $assetType, User::authorise('core.delete' . $at, $asset));
			$this->config->set('access-edit-' . $assetType, User::authorise('core.edit' . $at, $asset));
			$this->config->set('access-edit-state-' . $assetType, User::authorise('core.edit.state' . $at, $asset));
			$this->config->set('access-edit-own-' . $assetType, User::authorise('core.edit.own' . $at, $asset));
		}
	}
}
