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

namespace Components\Wiki\Site\Controllers;

use Hubzero\Component\SiteController;
use Components\Wiki\Models\Book;
use Components\Wiki\Models\Page as Article;
use Components\Wiki\Models\Revision;
use Components\Wiki\Helpers\Parser;
use Components\Wiki\Tables;
use Exception;
use Pathway;
use Request;
use Event;
use User;
use Lang;
use Date;

/**
 * Wiki controller class for pages
 */
class Page extends SiteController
{
	/**
	 * Book model
	 *
	 * @var  object
	 */
	public $book = null;

	/**
	 * Constructor
	 *
	 * @param   array  $config  Optional configurations
	 * @return  void
	 */
	public function __construct($config=array())
	{
		$this->_base_path = dirname(__DIR__);
		if (isset($config['base_path']))
		{
			$this->_base_path = $config['base_path'];
		}

		$this->_sub = false;
		if (isset($config['sub']))
		{
			$this->_sub = $config['sub'];
		}

		$this->_group = false;
		if (isset($config['group']))
		{
			$this->_group = $config['group'];
		}

		if ($this->_sub)
		{
			Request::setVar('task', Request::getWord('action'));
		}

		$this->book = new Book(($this->_group ? $this->_group : '__site__'));

		parent::__construct($config);
	}

	/**
	 * Execute a task
	 *
	 * @return  void
	 */
	public function execute()
	{
		if (!$this->book->pages('count'))
		{
			if ($result = $this->book->scribe($this->_option))
			{
				$this->setError($result);
			}

			JPROFILE ? \JProfiler::getInstance('Application')->mark('afterWikiSetup') : null;
		}

		$this->page = $this->book->page();

		if (in_array($this->page->get('namespace'), array('image', 'file')))
		{
			$this->setRedirect(
				'index.php?option=' . $this->_option . '&controller=media&scope=' . $this->page->get('scope') . '&pagename=' . $this->page->get('pagename') . '&task=download'
			);
			return;
		}

		parent::execute();
	}

	/**
	 * Display a page
	 *
	 * @return  void
	 */
	public function displayTask()
	{
		$this->view->book      = $this->book;
		$this->view->page      = $this->page;
		$this->view->config    = $this->config;
		$this->view->base_path = $this->_base_path;
		$this->view->sub       = $this->_sub;

		// Prep the pagename for display
		$this->view->title = $this->page->get('title'); //getTitle();

		// Set the page's <title> tag
		$document = \JFactory::getDocument();
		if ($this->_sub)
		{
			$document->setTitle($document->getTitle() . ': ' . $this->view->title);
		}
		else
		{
			$document->setTitle(($this->_sub ? Lang::txt('COM_GROUPS') . ': ' : '') . Lang::txt('COM_WIKI') . ': ' . $this->view->title);
		}

		// Set the pathway
		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_name)),
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller
			);
		}

		// Is this a special page?
		if ($this->page->get('namespace') == 'special')
		{
			// Set the layout
			$this->view->setLayout('special');
			$this->view->layout = $this->page->denamespaced();
			$this->view->page->set('scope', Request::getVar('scope', ''));
			$this->view->page->set('group_cn', $this->_group);
			$this->view->message = $this->_message;

			// Ensure the special page exists
			if (!in_array(strtolower($this->view->layout), $this->book->special()))
			{
				$this->setRedirect(
					Route::url('index.php?option=' . $this->_option . '&scope=' . $this->view->page->get('scope'))
				);
				return;
			}

			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}

			$this->view->display();
			return;
		}

		// Does a page exist for the given pagename?
		if (!$this->page->exists() || $this->page->isDeleted())
		{
			// No! Ask if they want to create a new page
			$this->view->setLayout('doesnotexist');
			if ($this->_group)
			{
				$this->page->set('group_cn', $this->_group);
				$this->page->set('scope', $this->_group . '/wiki');
			}

			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}

			$this->view->display();
			return;
		}

		if ($this->page->get('group_cn') && !$this->_group)
		{
			$this->setRedirect(
				Route::url('index.php?option=com_groups&scope=' . $this->page->get('scope') . '&pagename=' . $this->page->get('pagename'))
			);
			return;
		}

		// Check if the page is group restricted and the user is authorized
		if (!$this->page->access('view', 'page'))
		{
			throw new Exception(Lang::txt('COM_WIKI_WARNING_NOT_AUTH'), 403);
		}

		if ($this->page->get('scope') && !$this->page->get('group_cn'))
		{
			$bits = explode('/', $this->page->get('scope'));
			$s = array();
			foreach ($bits as $bit)
			{
				$bit = trim($bit);
				if ($bit != '/' && $bit != '')
				{
					$p = Article::getInstance($bit, implode('/', $s));
					if ($p->exists())
					{
						Pathway::append(
							$p->get('title'),
							$p->link()
						);
					}
					$s[] = $bit;
				}
			}
		}

		Pathway::append(
			$this->view->title,
			$this->page->link()
		);

		// Retrieve a specific version if given
		$this->view->version  = Request::getInt('version', 0);
		$this->view->revision = $this->page->revision($this->view->version);

		if (!$this->view->revision->exists())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}

			$this->view
				->setLayout('nosuchrevision')
				->display();
			return;
		}

		if (Request::getVar('format', '') == 'raw')
		{
			Request::setVar('no_html', 1);

			echo nl2br($this->view->revision->get('pagetext'));
			return;
		}
		elseif (Request::getVar('format', '') == 'printable')
		{
			echo $this->view->revision->get('pagehtml');
			return;
		}

		// Load the wiki parser
		$wikiconfig = array(
			'option'   => $this->_option,
			'scope'    => $this->page->get('scope'),
			'pagename' => $this->page->get('pagename'),
			'pageid'   => $this->page->get('id'),
			'filepath' => '',
			'domain'   => $this->page->get('group_cn')
		);

		$p = Parser::getInstance();

		// Parse the text
		if (intval($this->book->config('cache', 1)))
		{
			// Caching
			// Default time is 15 minutes
			$cache = \JFactory::getCache('wiki', 'callback');
			$cache->setCaching(1);
			$cache->setLifeTime(intval($this->book->config('cache_time', 15)));

			$this->view->revision->set('pagehtml', $cache->call(
				array($p, 'parse'),
				$this->view->revision->get('pagetext'), $wikiconfig, true, true
			));
		}
		else
		{
			$this->view->revision->set('pagehtml', $p->parse($this->view->revision->get('pagetext'), $wikiconfig, true, true));
		}

		JPROFILE ? \JProfiler::getInstance('Application')->mark('afterWikiParse') : null;

		// Handle display events
		$this->page->event = new \stdClass();

		$results = Event::trigger('wiki.onAfterDisplayTitle', array($this->page, &$this->view->revision, $this->config));
		$this->page->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = Event::trigger('wiki.onBeforeDisplayContent', array(&$this->page, &$this->view->revision, $this->config));
		$this->page->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = Event::trigger('wiki.onAfterDisplayContent', array(&$this->page, &$this->view->revision, $this->config));
		$this->page->event->afterDisplayContent = trim(implode("\n", $results));

		$this->view->message = $this->_message;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Show a form for creating an entry
	 *
	 * @return  void
	 */
	public function newTask()
	{
		$this->editTask();
	}

	/**
	 * Show a form for editing an entry
	 *
	 * @return  void
	 */
	public function editTask()
	{
		// Check if they are logged in
		if (User::isGuest())
		{
			$url = Request::getVar('REQUEST_URI', '', 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($url))
			);
			return;
		}

		// Check if the page is locked and the user is authorized
		if ($this->page->get('state') == 1 && !$this->page->access('manage'))
		{
			$this->setRedirect(
				Route::url($this->page->link()),
				Lang::txt('COM_WIKI_WARNING_NOT_AUTH_EDITOR'),
				'warning'
			);
			return;
		}

		// Check if the page is group restricted and the user is authorized
		if (!$this->page->access('edit') && !$this->page->access('modify'))
		{
			$this->setRedirect(
				Route::url($this->page->link()),
				Lang::txt('COM_WIKI_WARNING_NOT_AUTH_EDITOR'),
				'warning'
			);
			return;
		}

		$this->view->setLayout('edit');

		// Load the page
		$ischild = false;
		if ($this->page->get('id') && $this->_task == 'new')
		{
			$this->page->set('id', 0);
			$ischild = true;
		}

		// Get the most recent version for editing
		if (!is_object($this->revision))
		{
			$this->revision = $this->page->revision('current'); //getCurrentRevision();
			$this->revision->set('created_by', User::get('id'));
			$this->revision->set('summary', '');
		}

		// If an existing page, pull its tags for editing
		if (!$this->page->exists())
		{
			$this->page->set('access', 0);
			$this->page->set('created_by', User::get('id'));

			if ($this->_group)
			{
				$this->page->set('group_cn', $this->_group);
				$this->page->set('scope', $this->_group . '/' . $this->_sub);
			}

			if ($ischild && $this->page->get('pagename'))
			{
				$this->revision->set('pagetext', '');
				$this->page->set('scope', $this->page->get('scope') . ($this->page->get('scope') ? '/' . $this->page->get('pagename') : $this->page->get('pagename')));
				$this->page->set('pagename', '');
				$this->page->set('title', Lang::txt('COM_WIKI_NEW_PAGE'));
			}
		}

		$this->view->tags = trim(Request::getVar('tags', $this->page->tags('string'), 'post'));
		$this->view->authors = trim(Request::getVar('authors', $this->page->authors('string'), 'post'));

		// Prep the pagename for display
		// e.g. "MainPage" becomes "Main Page"
		$this->view->title = (trim($this->page->get('title')) ? $this->page->get('title') : Lang::txt('COM_WIKI_NEW_PAGE'));

		// Set the page's <title> tag
		$document = \JFactory::getDocument();
		if ($this->_sub)
		{
			$document->setTitle($document->getTitle() . ': ' . $this->view->title);
		}
		else
		{
			$document->setTitle(Lang::txt(strtoupper($this->_option)) . ': ' . $this->view->title . ': ' . Lang::txt(strtoupper($this->_option . '_' . $this->_task)));
		}

		// Set the pathway
		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option . '&controller=' . $this->_controller
			);
		}
		if (!$this->_sub)
		{
			Pathway::append(
				$this->view->title,
				$this->page->link()
			);
			Pathway::append(
				Lang::txt(strtoupper($this->_option . '_' . $this->_task)),
				$this->page->link() . '&task=' . $this->_task
			);
		}

		$this->view->preview = NULL;

		// Are we previewing?
		if ($this->preview)
		{
			// Yes - get the preview so we can parse it and display
			$this->view->preview = $this->preview;

			$pageid = $this->page->get('id');
			$lid = Request::getInt('lid', 0, 'post');
			if ($lid != $this->page->get('id'))
			{
				$pageid = $lid;
			}

			// Parse the HTML
			$wikiconfig = array(
				'option'   => $this->_option,
				'scope'    => $this->page->get('scope'),
				'pagename' => ($this->page->exists() ? $this->page->get('pagename') : 'Tmp:' . $pageid),
				'pageid'   => $pageid,
				'filepath' => '',
				'domain'   => $this->_group
			);

			$p = Parser::getInstance();

			$this->revision->set('pagehtml', $p->parse($this->revision->get('pagetext'), $wikiconfig, true, true));
		}

		$this->view->sub       = $this->_sub;
		$this->view->base_path = $this->_base_path;
		$this->view->message   = $this->_message;
		$this->view->page      = $this->page;
		$this->view->book      = $this->book;
		$this->view->revision  = $this->revision;

		// Pull a tree of pages in this wiki
		$items = $this->book->pages('list', array(
			'group'  => $this->_group,
			'sortby' => 'pagename ASC, scope ASC',
			'state'  => array(0, 1)
		));
		$tree = array();
		if ($items)
		{
			foreach ($items as $k => $branch)
			{
				// Since these will be parent pages, we need to add the item's pagename to the scope
				$branch->set('scope', ($branch->get('scope') ? $branch->get('scope') . '/' . $branch->get('pagename') : $branch->get('pagename')));
				$branch->set('scopeName', $branch->get('scope'));
				// Strip the group name from the beginning of the scope for display.
				if ($this->_group)
				{
					$branch->set('scopeName', substr($branch->get('scope'), strlen($this->_group . '/wiki/')));
				}
				// Push the item to the tree
				$tree[$branch->get('scope')] = $branch;
			}
			ksort($tree);
		}
		$this->view->tree = $tree; //$items;

		$this->view->tplate = trim(Request::getVar('tplate', ''));

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Save a wiki page
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Check if they are logged in
		if (User::isGuest())
		{
			$url = Request::getVar('REQUEST_URI', '', 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($url))
			);
			return;
		}

		// Incoming revision
		$rev = Request::getVar('revision', array(), 'post', 'none', 2);
		//$rev['pageid'] = (isset($rev['pageid'])) ? intval($rev['pageid']) : 0;

		$this->revision = $this->page->revision('current');
		$this->revision->set('version', $this->revision->get('version') + 1);
		if (!$this->revision->bind($rev))
		{
			$this->setError($this->revision->getError());
			$this->editTask();
			return;
		}
		$this->revision->set('id', 0);

		// Incoming page
		$page = Request::getVar('page', array(), 'post', 'none', 2);

		$this->page = new Article(intval($rev['pageid']));
		if (!$this->page->bind($page))
		{
			$this->setError($this->page->getError());
			$this->editTask();
			return;
		}
		$this->page->set('pagename', trim(Request::getVar('pagename', '', 'post')));
		$this->page->set('scope', trim(Request::getVar('scope', '', 'post')));

		// Get parameters
		$params = new \JRegistry($this->page->get('params', ''));
		$params->loadArray(Request::getVar('params', array(), 'post'));

		$this->page->set('params', $params->toString());

		// Get the previous version to compare against
		if (!$rev['pageid'])
		{
			// New page - save it to the database
			$this->page->set('created_by', User::get('id'));

			$old = new Revision(0);
		}
		else
		{
			// Get the revision before changes
			$old = $this->page->revision('current');
		}

		// Was the preview button pushed?
		$this->preview = trim(Request::getVar('preview', ''));
		if ($this->preview)
		{
			// Set the component task
			if (!$rev['pageid'])
			{
				Request::setVar('task', 'new');
				$this->_task = 'new';
			}
			else
			{
				Request::setVar('task', 'edit');
				$this->_task = 'edit';
			}

			// Push on through to the edit form
			$this->editTask();
			return;
		}

		// Check content
		// First, make sure the pagetext isn't empty
		if ($this->revision->get('pagetext') == '')
		{
			$this->setError(Lang::txt('COM_WIKI_ERROR_MISSING_PAGETEXT'));
			$this->editTask();
			return;
		}

		// Store new content
		if (!$this->page->store(true))
		{
			$this->setError($this->page->getError());
			$this->editTask();
			return;
		}

		// Get allowed authors
		if (!$this->page->updateAuthors(Request::getVar('authors', '', 'post')))
		{
			$this->setError($this->page->getError());
			$this->editTask();
			return;
		}

		// Get the upload path
		$wpa = new Tables\Attachment($this->database);
		$path = $wpa->filespace();

		// Rename the temporary upload directory if it exist
		$lid = Request::getInt('lid', 0, 'post');
		if ($lid != $this->page->get('id'))
		{
			if (is_dir($path . DS . $lid))
			{
				jimport('joomla.filesystem.folder');
				if (!\JFolder::move($path . DS . $lid, $path . DS . $this->page->get('id')))
				{
					$this->setError(\JFolder::move($path . DS . $lid, $path . DS . $this->page->get('id')));
				}
				$wpa->setPageID($lid, $this->page->get('id'));
			}
		}

		$this->revision->set('pageid',   $this->page->get('id'));
		$this->revision->set('pagename', $this->page->get('pagename'));
		$this->revision->set('scope',    $this->page->get('scope'));
		$this->revision->set('group_cn', $this->page->get('group_cn'));
		$this->revision->set('version',  $this->revision->get('version') + 1);

		if ($this->page->param('mode', 'wiki') == 'knol')
		{
			// Set revisions to NOT approved
			$this->revision->set('approved', 0);
			// If an author or the original page creator, set to approved
			if ($this->page->get('created_by') == User::get('id')
			 || $this->page->isAuthor(User::get('id')))
			{
				$this->revision->set('approved', 1);
			}
		}
		else
		{
			// Wiki mode, approve revision
			$this->revision->set('approved', 1);
		}

		// Compare against previous revision
		// We don't want to create a whole new revision if just the tags were changed
		if (rtrim($old->get('pagetext')) != rtrim($this->revision->get('pagetext')))
		{
			// Transform the wikitext to HTML
			$this->revision->set('pagehtml', '');
			$this->revision->set('pagehtml', $this->revision->content('parsed'));

			// Parse attachments
			/*$a = new Tables\Attachment($this->database);
			$a->pageid = $this->page->id;
			$a->path = $path;

			$this->revision->pagehtml = $a->parse($this->revision->pagehtml);*/
			if ($this->page->access('manage') || $this->page->access('edit'))
			{
				$this->revision->set('approved', 1);
			}

			// Store content
			if (!$this->revision->store(true))
			{
				$this->setError(Lang::txt('COM_WIKI_ERROR_SAVING_REVISION'));
				$this->editTask();
				return;
			}

			$this->page->set('version_id', $this->revision->get('id'));
			$this->page->set('modified', $this->revision->get('created'));
		}
		else
		{
			$this->page->set('modified', Date::toSql());
		}

		if (!$this->page->store(true))
		{
			// This really shouldn't happen.
			$this->setError(Lang::txt('COM_WIKI_ERROR_SAVING_PAGE'));
			$this->editTask();
			return;
		}

		// Process tags
		$this->page->tag(Request::getVar('tags', ''));

		// Redirect
		$this->setRedirect(
			Route::url($this->page->link())
		);
	}

	/**
	 * Delete a page
	 *
	 * @return  void
	 */
	public function deleteTask()
	{
		// Check if they are logged in
		if (User::isGuest())
		{
			$url = Request::getVar('REQUEST_URI', '', 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($url))
			);
			return;
		}

		if (!is_object($this->page))
		{
			$this->setRedirect(
				Route::url($this->page->link('base')),
				Lang::txt('COM_WIKI_ERROR_PAGE_NOT_FOUND'),
				'error'
			);
			return;
		}

		// Make sure they're authorized to delete
		if (!$this->page->access('delete'))
		{
			$this->setRedirect(
				Route::url($this->page->link('base')),
				Lang::txt('COM_WIKI_ERROR_NOTAUTH'),
				'error'
			);
			return;
		}

		$confirmed = Request::getInt('confirm', 0, 'post');

		switch ($confirmed)
		{
			case 1:
				// Check for request forgeries
				Request::checkToken() or jexit('Invalid Token');

				$this->page->set('state', 2);
				if (!$this->page->store(false, 'page_removed'))
				{
					$this->setError(Lang::txt('COM_WIKI_UNABLE_TO_DELETE'));
				}

				$cache = \JFactory::getCache('wiki', 'callback');
				$cache->clean('wiki');
			break;

			default:
				$this->view->page      = $this->page;
				$this->view->config    = $this->config;
				$this->view->base_path = $this->_base_path;
				$this->view->sub       = $this->_sub;

				// Prep the pagename for display
				// e.g. "MainPage" becomes "Main Page"
				$this->view->title = $this->page->get('title');

				// Set the page's <title> tag
				$document = \JFactory::getDocument();
				$document->setTitle(Lang::txt(strtoupper($this->_option)) . ': ' . $this->view->title . ': ' . Lang::txt(strtoupper($this->_option . '_' . $this->_task)));

				// Set the pathway
				if (Pathway::count() <= 0)
				{
					Pathway::append(
						Lang::txt(strtoupper($this->_option)),
						'index.php?option=' . $this->_option . '&controller=' . $this->_controller
					);
				}
				Pathway::append(
					$this->view->title,
					$this->page->link()
				);
				Pathway::append(
					Lang::txt(strtoupper($this->_option . '_' . $this->_task)),
					$this->page->link('delete')
				);

				$this->view->message = $this->_message;

				foreach ($this->getErrors() as $error)
				{
					$this->view->setError($error);
				}

				$this->view->display();
				return;
			break;
		}

		$this->setRedirect(
			Route::url($this->page->link('base'))
		);
	}

	/**
	 * Show a form to rename a page
	 *
	 * @return  void
	 */
	public function renameTask()
	{
		// Check if they are logged in
		if (User::isGuest())
		{
			$url = Request::getVar('REQUEST_URI', '', 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($url))
			);
			return;
		}

		// Make sure they're authorized to delete
		if (!$this->page->access('edit'))
		{
			$this->setRedirect(
				Route::url($this->page->link('base')),
				Lang::txt('COM_WIKI_ERROR_NOTAUTH'),
				'error'
			);
			return;
		}

		$this->view->page      = $this->page;
		$this->view->config    = $this->config;
		$this->view->base_path = $this->_base_path;
		$this->view->sub       = $this->_sub;

		// Prep the pagename for display
		// e.g. "MainPage" becomes "Main Page"
		$this->view->title = $this->page->get('title');

		// Set the page's <title> tag
		$document = \JFactory::getDocument();
		$document->setTitle(Lang::txt(strtoupper($this->_name)) . ': ' . $this->view->title . ': ' . Lang::txt('RENAME'));

		// Set the pathway
		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_name)),
				'index.php?option=' . $this->_option
			);
		}
		Pathway::append(
			$this->view->title,
			$this->page->link()
		);
		Pathway::append(
			Lang::txt(strtoupper('COM_WIKI_RENAME')),
			$this->page->link('rename')
		);

		$this->view->message = $this->_message;

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output HTML
		$this->view
			->setLayout('rename')
			->display();
	}

	/**
	 * Save the new page name
	 *
	 * @return  void
	 */
	public function saverenameTask()
	{
		// Check for request forgeries
		Request::checkToken() or jexit('Invalid Token');

		// Check if they are logged in
		if (User::isGuest())
		{
			$url = Request::getVar('REQUEST_URI', '', 'server');
			$this->setRedirect(
				Route::url('index.php?option=com_users&view=login&return=' . base64_encode($url))
			);
			return;
		}

		// Incoming
		$oldpagename = trim(Request::getVar('oldpagename', '', 'post'));
		$newpagename = trim(Request::getVar('newpagename', '', 'post'));
		$scope       = trim(Request::getVar('scope', '', 'post'));

		// Load the page
		$this->page = new Article($oldpagename, $scope);

		// Attempt to rename
		if (!$this->page->rename($newpagename))
		{
			$this->setError($this->page->getError());
			$this->renameTask();
			return;
		}

		// Redirect to the newly named page
		$this->setRedirect(
			Route::url($this->page->link())
		);
	}

	/**
	 * Output the contents of a wiki page as a PDF
	 *
	 * Based on work submitted by Steven Maus <steveng4235@gmail.com> (2014)
	 *
	 * @return     void
	 */
	public function pdfTask()
	{
		// Does a page exist for the given pagename?
		if (!$this->page->exists() || $this->page->isDeleted())
		{
			// No! Ask if they want to create a new page
			$this->view->setLayout('doesnotexist');
			if ($this->_group)
			{
				$this->page->set('group_cn', $this->_group);
				$this->page->set('scope', $this->_group . '/wiki');
			}

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

		// Retrieve a specific version if given
		$this->view->revision = $this->page->revision(Request::getInt('version', 0));
		if (!$this->view->revision->exists())
		{
			foreach ($this->getErrors() as $error)
			{
				$this->view->setError($error);
			}

			$this->view
				->set('page', $this->page)
				->setLayout('nosuchrevision')
				->display();
			return;
		}

		Request::setVar('format', 'pdf');

		// Set the view page content to current revision html
		$this->view->page = $this->page;

		// Load the wiki parser
		$wikiconfig = array(
			'option'   => $this->_option,
			'scope'    => $this->page->get('scope'),
			'pagename' => $this->page->get('pagename'),
			'pageid'   => $this->page->get('id'),
			'filepath' => '',
			'domain'   => $this->page->get('group_cn')
		);

		$p = Parser::getInstance();

		// Parse the text
		$this->view->revision->set('pagehtml', $p->parse($this->view->revision->get('pagetext'), $wikiconfig, true, true));

		//build url to wiki with no html
		$wikiPageUrl = 'https://' . $_SERVER['HTTP_HOST'] . DS . 'wiki' . DS . $wikiconfig['pagename'] . '?format=printable';

		//path to wiki file
		$wikiPageFolder = PATH_APP . DS . 'site' . DS . 'wiki' . DS . 'pdf';
		$wikiPagePdf = $wikiPageFolder . DS . $wikiconfig['pagename'] . '.pdf';

		// check for upload path
		if (!is_dir($wikiPageFolder))
		{
			// Build the path if it doesn't exist
			jimport('joomla.filesystem.folder');
			if (!JFolder::create($wikiPageFolder))
			{
				$this->setRedirect(
					Route::url('index.php?option=' . $this->_option . '&id=' . $id),
					Lang::txt('Unable to create the filepath.'),
					'error'
				);
				return;
			}
		}

		// check multiple places for wkhtmltopdf lib
		// fallback on phantomjs
		$cmd = '';
		$fallback = '';
		if (file_exists('/usr/bin/wkhtmltopdf') && file_exists('/usr/bin/xvfb-run'))
		{
			//$cmd = '/usr/bin/wkhtmltopdf ' . $wikiPageUrl . ' ' . $wikiPagePdf;
			$cmd = '/usr/bin/xvfb-run -a -s "-screen 0 640x480x16" wkhtmltopdf ' . $wikiPageUrl . ' ' . $wikiPagePdf;
		}
		else if (file_exists('/usr/local/bin/wkhtmltopdf') && file_exists('/usr/local/bin/xvfb-run'))
		{
			//$cmd = '/usr/local/bin/wkhtmltopdf ' . $wikiPageUrl . ' ' . $wikiPagePdf;
			$cmd = '/usr/local/bin/xvfb-run -a -s "-screen 0 640x480x16" wkhtmltopdf ' . $wikiPageUrl . ' ' . $wikiPagePdf;
		}

		if (file_exists('/usr/bin/phantomjs'))
		{
			$rasterizeFile = PATH_CORE . DS . 'components' . DS . 'com_wiki' . DS . 'assets' . DS . 'js' . DS . 'rasterize.js';
			$fallback = '/usr/bin/phantomjs --ssl-protocol=any --ignore-ssl-errors=yes --web-security=false ' . $rasterizeFile . ' ' . $wikiPageUrl . ' ' . $wikiPagePdf . ' 8.5in*11in';
			if (!$cmd)
			{
				$cmd = $fallback;
			}
		}
		if (isset($cmd))
		{
			// exec command
			$task = exec($cmd, $ouput, $status);

			// wkhtmltopdf failed, so let's try phantomjs
			if (!file_exists($wikiPagePdf) && $fallback && $cmd != $fallback)
			{
				exec($fallback, $ouput, $status);
			}
		}

				//make sure we have a file to output
		if (!file_exists($wikiPagePdf))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&id=' . $id),
				Lang::txt('COM_NEWSLETTER_VIEW_OUTPUT_PDFERROR'),
				'error'
			);
			return;
		}

		//output as attachment
		header("Content-type: application/pdf");
		header("Content-Disposition: attachment; filename=" . str_replace(' ', '_', $wikiconfig['pagename']) . ".pdf");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo file_get_contents($wikiPagePdf);

		exit();
	}
}
