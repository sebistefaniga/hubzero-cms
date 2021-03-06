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

use Components\Wiki\Models\Book;
use Components\Wiki\Models\Revision;
use Hubzero\Component\SiteController;
use Exception;
use Pathway;
use Request;
use User;
use Lang;

/**
 * Wiki controller class for page history
 */
class History extends SiteController
{
	/**
	 * Constructor
	 *
	 * @param      array $config Optional configurations
	 * @return     void
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
	 * @return     void
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

		$this->registerTask('deleterevision', 'delete');

		parent::execute();
	}

	/**
	 * Display a history of the current wiki page
	 *
	 * @return     void
	 */
	public function displayTask()
	{
		$this->view->setLayout('display');

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
				'index.php?option=' . $this->_option
			);
		}
		Pathway::append(
			$this->view->title,
			$this->page->link()
		);
		Pathway::append(
			Lang::txt(strtoupper($this->_option . '_' . $this->_task)),
			$this->page->link() . '&' . ($this->_sub ? 'action' : 'task') . '=' . $this->_task
		);

		$this->view->message = $this->_message;

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Compare two versions of a wiki page
	 *
	 * @return     void
	 */
	public function compareTask()
	{
		include_once(dirname(dirname(__DIR__)) . DS . 'helpers' . DS . 'differenceengine.php');

		$this->view->page      = $this->page;
		$this->view->config    = $this->config;
		$this->view->base_path = $this->_base_path;
		$this->view->sub       = $this->_sub;

		// Incoming
		$oldid = Request::getInt('oldid', 0);
		$diff  = Request::getInt('diff', 0);

		// Do some error checking
		if (!$diff)
		{
			$this->setError(Lang::txt('COM_WIKI_ERROR_MISSING_VERSION'));
			$this->displayTask();
			return;
		}
		if ($diff == $oldid)
		{
			$this->setError(Lang::txt('COM_WIKI_ERROR_SAME_VERSIONS'));
			$this->displayTask();
			return;
		}

		// If no initial page is given, compare to the current revision
		$this->view->revision = $this->page->revision('current');

		$this->view->or = $this->page->revision($oldid);
		$this->view->dr = $this->page->revision($diff);

		// Diff the two versions
		$ota = explode("\n", $this->view->or->get('pagetext'));
		$nta = explode("\n", $this->view->dr->get('pagetext'));

		//$diffs = new Diff($ota, $nta);
		$formatter = new \TableDiffFormatter();
		$this->view->content = $formatter->format(new \Diff($ota, $nta));

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
				'index.php?option=' . $this->_option
			);
		}
		Pathway::append(
			$this->view->title,
			$this->page->link()
		);
		Pathway::append(
			Lang::txt(strtoupper($this->_option . '_' . $this->_task)),
			$this->page->link() . '&' . ($this->_sub ? 'action' : 'task') . '=' . $this->_task
		);

		$this->view->sub     = $this->_sub;
		$this->view->message = $this->_message;
		$this->view->name    = Lang::txt(strtoupper($this->_option));

		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view->display();
	}

	/**
	 * Delete a revision
	 *
	 * @return     void
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

		// Incoming
		$id = Request::getInt('oldid', 0);

		if (!$id || !$this->page->access('delete'))
		{
			$this->setRedirect(
				Route::url($this->page->link('history'))
			);
			return;
		}

		$revision = new Revision($id);

		// Get a count of all approved revisions
		if ($this->page->revisions('count', array('approved' => 1)) <= 1)
		{
			// Can't delete - it's the only approved version!
			$this->setRedirect(
				Route::url($this->page->link('history'))
			);
			return;
		}

		// Mark as deleted
		$revision->set('approved', 2);

		if (!$revision->store())
		{
			$this->setRedirect(
				Route::url($this->page->link('history')),
				Lang::txt('COM_WIKI_ERROR_REMOVING_REVISION'),
				'error'
			);
			return;
		}

		// If we're deleting the current revision, set the current
		// revision number to the previous available revision
		$this->page->revisions('list', array(), true)->last();
		$this->page->set('version_id', $this->page->revisions()->current()->get('id'));
		$this->page->store(false, 'revision_removed');

		$this->setRedirect(
			Route::url($this->page->link('history'))
		);
	}

	/**
	 * Approve a revision
	 *
	 * @return     void
	 */
	public function approveTask()
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

		// Incoming
		$id = Request::getInt('oldid', 0);

		if (!$id || !$this->page->access('manage'))
		{
			$this->setRedirect(
				Route::url($this->page->link())
			);
			return;
		}

		// Load the revision, approve it, and save
		$revision = new Revision($id);
		$revision->set('approved', 1);
		if (!$revision->store())
		{
			throw new Exception($revision->getError(), 500);
		}

		// Get the most recent revision and compare to the set "current" version
		$this->page->revisions('list', array(), true)->last();
		if ($this->page->revisions()->current()->get('id') == $revision->get('id'))
		{
			// The newly approved revision is now the most current
			// So, we need to update the page's version_id
			$this->page->set('version_id', $this->page->revisions()->current()->get('id'));
			$this->page->store(false, 'revision_approved');
		}
		else
		{
			$this->page->log('revision_approved');
		}

		$this->setRedirect(
			Route::url($this->page->link())
		);
	}
}

