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

namespace Components\Citations\Site\Controllers;

use Components\Citations\Tables\Citation;
use Components\Citations\Tables\Association;
use Components\Citations\Tables\Type;
use Components\Citations\Tables\Author;
use Components\Citations\Tables\Tags;
use Components\Citations\Helpers\Download;
use Components\Citations\Helpers\Format;
use Hubzero\Component\SiteController;
use Hubzero\Utility\Sanitize;
use Exception;

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Citations controller class for citation entries
 */
class Citations extends SiteController
{
	/**
	 * Execute a task
	 *
	 * @return  void
	 */
	public function execute()
	{
		// disable default task - stop fallback when user enters bad task
		$this->disableDefaultTask();

		// register empty task and intro as the main display task
		$this->registerTask('', 'display');
		$this->registerTask('intro', 'display');

		// execute parent function
		parent::execute();
	}

	/**
	 * Default component view
	 *
	 * @return     void
	 */
	public function displayTask()
	{
		// Set the page title
		$this->_buildTitle();

		// Set the pathway
		$this->_buildPathway();

		// Instantiate a new view
		$this->view->title = Lang::txt(strtoupper($this->_option));

		$this->view->database = $this->database;

		// Load the object
		$row = new Citation($this->database);
		$this->view->yearlystats = $row->getStats();

		// Get some stats
		$this->view->typestats = array();
		$ct = new Type($this->database);
		$types = $ct->getType();
		foreach ($types as $t)
		{
			$this->view->typestats[$t['type_title']] = $row->getCount(array('type' => $t['id']), false);
		}

		//are we allowing importing
		$this->view->allow_import = $this->config->get('citation_import', 1);
		$this->view->allow_bulk_import = $this->config->get('citation_bulk_import', 1);
		$this->view->isAdmin = false;
		if (User::authorize($this->_option, 'import'))
		{
			$this->view->isAdmin = true;
		}

		// Output HTML
		$this->view->messages = ($this->getComponentMessage()) ? $this->getComponentMessage() : array();

		$this->view
			->setLayout('display')
			->display();
	}

	/**
	 * Browse entries
	 *
	 * @return  void
	 */
	public function browseTask()
	{
		// Instantiate a new view
		$this->view->title    = Lang::txt(strtoupper($this->_option));
		$this->view->database = $this->database;
		$this->view->config   = $this->config;
		$this->view->isAdmin  = false;
		if (User::authorize($this->_option, 'import'))
		{
			$this->view->isAdmin = true;
		}

		// get the earliest year we have citations for
		$query = "SELECT c.year FROM `#__citations` as c WHERE c.published=1 AND c.year <> 0 AND c.year IS NOT NULL ORDER BY c.year ASC LIMIT 1";
		$this->view->database->setQuery($query);
		$earliest_year = $this->view->database->loadResult();
		$earliest_year = ($earliest_year) ? $earliest_year : 1990;

		// Incoming
		$this->view->filters = array(
			// Paging filters
			'limit'           => Request::getInt('limit', 50, 'request'),
			'start'           => Request::getInt('limitstart', 0, 'get'),
			// Search/filtering params
			'id'              => Request::getInt('id', 0),
			'tag'             => Request::getVar('tag', '', 'request', 'none', 2),
			'search'          => Request::getVar('search', ''),
			'type'            => Request::getVar('type', ''),
			'author'          => Request::getVar('author', ''),
			'publishedin'     => Request::getVar('publishedin', ''),
			'year_start'      => Request::getInt('year_start', $earliest_year),
			'year_end'        => Request::getInt('year_end', date("Y")),
			'filter'          => Request::getVar('filter', ''),
			'sort'            => Request::getVar('sort', 'created DESC'),
			'reftype'         => Request::getVar('reftype', array('research' => 1, 'education' => 1, 'eduresearch' => 1, 'cyberinfrastructure' => 1)),
			'geo'             => Request::getVar('geo', array('us' => 1, 'na' => 1,'eu' => 1, 'as' => 1)),
			'aff'             => Request::getVar('aff', array('university' => 1, 'industry' => 1, 'government' => 1)),
			'startuploaddate' => Request::getVar('startuploaddate', '0000-00-00'),
			'enduploaddate'   => Request::getVar('enduploaddate', '0000-00-00'),
			'group'           => ''
		);

		// Do we have a group filter?
		if ($group = Request::getVar('group', ''))
		{
			$this->view->filters['scope']    = 'groups';
			$this->view->filters['scope_id'] = $group;

			// Only used so we only have one param on form filters
			$this->view->filters['group']    = $group;
		}

		// Affiliation filter
		$this->view->filter = array(
			'all'    => Lang::txt('COM_CITATIONS_ALL'),
			'aff'    => Lang::txt('COM_CITATIONS_AFFILIATED'),
			'nonaff' => Lang::txt('COM_CITATIONS_NONAFFILIATED')
		);
		if (!in_array($this->view->filters['filter'], array_keys($this->view->filter)))
		{
			$this->view->filters['filter'] = '';
		}

		// Sort Filter
		$this->view->sorts = array(
			'sec_cnt DESC' => Lang::txt('COM_CITATIONS_CITEDBY'),
			'year DESC'    => Lang::txt('COM_CITATIONS_YEAR'),
			'created DESC' => Lang::txt('COM_CITATIONS_NEWEST'),
			'title ASC'    => Lang::txt('COM_CITATIONS_TITLE'),
			'author ASC'   => Lang::txt('COM_CITATIONS_AUTHOR'),
			'journal ASC'  => Lang::txt('COM_CITATIONS_JOURNAL')
		);
		if (!in_array($this->view->filters['sort'], array_keys($this->view->sorts)))
		{
			$this->view->filters['sort'] = 'created DESC';
		}

		// Handling ids of the the boxes checked for download
		$referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
		$session = \JFactory::getSession();

		// If it's new search remove all user citation checkmarks
		if (isset($_POST['filter']))
		{
			$this->view->filters['idlist'] = "";
			$session->set('idlist', $this->view->filters['idlist']);
		}
		else
		{
			$this->view->filters['idlist'] = Request::getVar('idlist', $session->get('idlist'));
			$session->set('idlist', $this->view->filters['idlist']);
		}

		// Reset the filter if the user came from a different section
		if (strpos($referer, "/citations/browse") == false)
		{
			$this->view->filters['idlist'] = "";
			$session->set('idlist', $this->view->filters['idlist']);
		}

		// Convert upload dates to correct time format
		if ($this->view->filters['startuploaddate'] == '0000-00-00'
			|| $this->view->filters['startuploaddate'] == '0000-00-00 00:00:00'
			|| $this->view->filters['startuploaddate'] == '')
		{
			$this->view->filters['startuploaddate'] = '0000-00-00 00:00:00';
		}
		else
		{
			$this->view->filters['startuploaddate'] = \JFactory::getDate($this->view->filters['startuploaddate'])->format('Y-m-d 00:00:00');
		}
		if ($this->view->filters['enduploaddate'] == '0000-00-00'
			|| $this->view->filters['enduploaddate'] == '0000-00-00 00:00:00'
			|| $this->view->filters['enduploaddate'] == '')
		{
			$this->view->filters['enduploaddate'] = \JFactory::getDate()->modify('+1 DAY')->format('Y-m-d 00:00:00');
		}
		else
		{
			$this->view->filters['enduploaddate'] = \JFactory::getDate($this->view->filters['enduploaddate'])->format('Y-m-d 00:00:00');
		}

		// Make sure the end date for the upload search isn't before the start date
		if ($this->view->filters['startuploaddate'] > $this->view->filters['enduploaddate'])
		{
			$this->setRedirect(
				Route::url('index.php?option=com_citations&task=browse'),
				Lang::txt('COM_CITATIONS_END_DATE_MUST_BE_AFTER_START_DATE'),
				'error'
			);
			return;
		}

		// Clean up filters a little
		array_walk($this->view->filters, function(&$val, &$key)
		{
			if (!is_array($val))
			{
				$val = trim($val);
				$val = str_replace('"', '', $val);
				$key = $val;
			}
		});

		// Instantiate a new citations object
		$obj = new Citation($this->database);

		// Get a record count
		$this->view->total = $obj->getCount($this->view->filters, $this->view->isAdmin);

		// Get records
		$this->view->citations = $obj->getRecords($this->view->filters, $this->view->isAdmin);

		// Add some data to our view for form filtering/sorting
		$ct = new Type($this->database);
		$this->view->types = $ct->getType();

		// get groups
		$this->view->groups = \Hubzero\User\Group::find(array(
			'type'      => array('1','3'),
			'published' => 1,
			'approved'  => 1,
			'fields'    => array('gidNumber', 'cn')
		));

		// Get the users id to make lookup
		$users_ip = Request::ip();

		// Get the param for ip regex to use machine ip
		$ip_regex = array('10.\d{2,5}.\d{2,5}.\d{2,5}');

		$use_machine_ip = false;
		foreach ($ip_regex as $ipr)
		{
			$match = preg_match('/' . $ipr . '/i', $users_ip);
			if ($match)
			{
				$use_machine_ip = true;
			}
		}

		// Make url based on if were using machine ip or users
		if ($use_machine_ip)
		{
			$url = 'http://worldcatlibraries.org/registry/lookup?IP=' . $_SERVER['SERVER_ADDR'];
		}
		else
		{
			$url = 'http://worldcatlibraries.org/registry/lookup?IP=' . $users_ip;
		}

		// Get the resolver
		$r = null;
		if (function_exists('curl_init'))
		{
			$cURL = curl_init();
			curl_setopt($cURL, CURLOPT_URL, $url);
			curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($cURL, CURLOPT_TIMEOUT, 10);
			$r = curl_exec($cURL);
			curl_close($cURL);
		}

		// Parse the returned xml
		$this->view->openurl = array(
			'link' => '',
			'text' => '',
			'icon' => ''
		);

		// Parse the return from resolver lookup
		$resolver = null;
		$xml = simplexml_load_string($r);
		if (isset($xml->resolverRegistryEntry))
		{
			$resolver = $xml->resolverRegistryEntry->resolver;
		}

		// If we have resolver set vars for creating open urls
		if ($resolver != null)
		{
			$this->view->openurl['link'] = $resolver->baseURL;
			$this->view->openurl['text'] = $resolver->linkText;
			$this->view->openurl['icon'] = $resolver->linkIcon;
		}

		// Set the page title
		$this->_buildTitle();

		// Set the pathway
		$this->_buildPathway();

		// Pass any error messages to the view
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Get any messages
		$this->view->messages = ($this->getComponentMessage()) ? $this->getComponentMessage() : array();

		// Are we allowing importing?
		$this->view->allow_import = $this->config->get('citation_import', 1);
		$this->view->allow_bulk_import = $this->config->get('citation_bulk_import', 1);

		// Output HTML
		$this->view->display();
	}

	/**
	 * View a citation entry
	 *
	 * @return  void
	 */
	public function viewTask()
	{
		// get request vars
		$id = Request::getInt('id', 0);

		//make sure we have an id
		if (!$id || $id == 0)
		{
			App::abort(404, Lang::txt('COM_CITATIONS_MUST_HAVE_ID'));
			return;
		}

		// set vars for view
		$this->view->database = $this->database;

		//get the citation
		$this->view->citation = new Citation($this->view->database);
		$this->view->citation->load($id);

		//make sure we got a citation
		if (!isset($this->view->citation->title) || $this->view->citation->title == '')
		{
			App::abort(404, Lang::txt('COM_CITATIONS_NO_CITATION_WITH_ID'));
			return;
		}

		// make sure citation is published
		if (!$this->view->citation->published)
		{
			App::abort(404, Lang::txt('COM_CITATIONS_NOT_PUBLISHED'));
			return;
		}

		//load citation associations
		$assoc = new Association($this->database);
		$this->view->associations = $assoc->getRecords(array('cid' => $id));

		//open url stuff
		$this->view->openUrl = $this->openUrl();

		//make sure title isnt too long
		$this->view->maxTitleLength = 50;
		$this->view->shortenedTitle = (strlen($this->view->citation->title) > $this->view->maxTitleLength) ? substr($this->view->citation->title, 0, $this->view->maxTitleLength) . '&hellip;' : $this->view->citation->title;

		// Set the page title
		$document = \JFactory::getDocument();
		$document->setTitle(Lang::txt('COM_CITATIONS_CITATION') . ": " . $this->view->shortenedTitle);

		// Set the pathway
		if (Pathway::count() <= 0)
		{
			Pathway::append(Lang::txt(strtoupper($this->_name)), 'index.php?option=' . $this->_option);
		}
		Pathway::append(Lang::txt('COM_CITATIONS_BROWSE'), 'index.php?option=' . $this->_option . '&task=browse');
		Pathway::append($this->view->shortenedTitle, 'index.php?option=' . $this->_option . '&task=view&id=' . $this->view->citation->id);

		//get this citation type to see if we have a template override for this type
		$citationType = new Type($this->database);
		$type = $citationType->getType($this->view->citation->type);
		$typeAlias = $type[0]['type'];

		// Build paths to type specific overrides
		$application = \JFactory::getApplication();
		$componentTypeOverride = JPATH_ROOT . DS . 'components' . DS . 'com_citations' . DS . 'views' . DS . 'citations' . DS . 'tmpl' . DS . $typeAlias . '.php';
		$tempalteTypeOverride  = JPATH_ROOT . DS . 'templates' . DS . $application->getTemplate() . DS . 'html' . DS . 'com_citations' . DS . 'citations' . DS . $typeAlias . '.php';

		//if we found an override use it
		if (file_exists($tempalteTypeOverride) || file_exists($componentTypeOverride))
		{
			$this->view->setLayout($typeAlias);
		}

		//get any messages & display view
		$this->view->messages = ($this->getComponentMessage()) ? $this->getComponentMessage() : array();
		$this->view->config   = $this->config;
		$this->view->juser    = User::getRoot();
		$this->view->display();
	}

	/**
	 * Get Open URL
	 *
	 * @return  string
	 */
	private function openUrl()
	{
		// var to store open url stuff
		$openUrl = array(
			'link' => '',
			'text' => '',
			'icon' => ''
		);

		// get the users id to make lookup
		$userIp = Request::ip();

		// get the param for ip regex to use machine ip
		$ipRegex = array('10.\d{2,5}.\d{2,5}.\d{2,5}');

		$useMachineIp = false;
		foreach ($ipRegex as $ipr)
		{
			$match = preg_match('/' . $ipr . '/i', $userIp);
			if ($match)
			{
				$useMachineIp = true;
			}
		}

		// make url based on if were using machine ip or users
		if ($useMachineIp)
		{
			$url = 'http://worldcatlibraries.org/registry/lookup?IP=' . $_SERVER['SERVER_ADDR'];
		}
		else
		{
			$url = 'http://worldcatlibraries.org/registry/lookup?IP=' . $userIp;
		}

		// get the resolver
		$r = null;
		if (function_exists('curl_init'))
		{
			$cURL = curl_init();
			curl_setopt($cURL, CURLOPT_URL, $url);
			curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($cURL, CURLOPT_TIMEOUT, 10);
			$r = curl_exec($cURL);
			curl_close($cURL);
		}

		// parse the return from resolver lookup
		$xml = simplexml_load_string($r);
		$resolver = $xml->resolverRegistryEntry->resolver;

		// if we have resolver set vars for creating open urls
		if ($resolver != null)
		{
			$openUrl['link'] = $resolver->baseURL;
			$openUrl['text'] = $resolver->linkText;
			$openUrl['icon'] = $resolver->linkIcon;
		}

		return $openUrl;
	}

	/**
	 * Redirect to login form
	 *
	 * @return  void
	 */
	public function loginTask()
	{
		$this->setRedirect(
			Route::url('index.php?option=com_users&view=login&return=' . base64_encode(Route::url('index.php?option=' . $this->_option . '&task=' . $this->_task, false, true))),
			Lang::txt('COM_CITATIONS_NOT_LOGGEDIN'),
			'warning'
		);
		return;
	}

	/**
	 * Show a form for adding an entry
	 *
	 * @return  void
	 */
	public function addTask()
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
		// Check if they're logged in
		if (User::isGuest())
		{
			$this->loginTask();
			return;
		}

		// Check if admin
		$isAdmin = false;
		if (User::authorize($this->_option, 'manage'))
		{
			$isAdmin = true;
		}

		// are we allowing user to add citation
		$allowImport = $this->config->get('citation_import', 1);
		if ($allowImport == 0
		|| ($allowImport == 2 && User::get('usertype') == 'Super Administrator'))
		{
			// Redirect
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option, false),
				Lang::txt('COM_CITATION_EDIT_NOTALLOWED'),
				'warning'
			);
			return;
		}

		// get the citation types
		$ct = new Type($this->database);
		$types = $ct->getType();

		$fields = array();
		foreach ($types as $type)
		{
			if (isset($type['fields']))
			{
				$f = $type['fields'];
				if (strpos($f, ',') !== false)
				{
					$f = str_replace(',', "\n", $f);
				}

				$f = array_map('trim', explode("\n", $f));
				$f = array_values(array_filter($f));

				$fields[strtolower(str_replace(' ', '', $type['type_title']))] = $f;
			}
		}

		// add an empty value for the first type
		array_unshift($types, array(
			'type'       => '',
			'type_title' => ' - Select a Type &mdash;'
		));

		// Incoming - expecting an array id[]=4232
		$id = Request::getInt('id', 0);

		// Pub author
		$pubAuthor = false;

		// Load the associations object
		$assoc = new Association($this->database);

		// Get associations
		if ($id)
		{
			$this->view->assocs = $assoc->getRecords(array('cid' => $id), $isAdmin);

			$pubAuthor = $this->isPubAuthor($this->view->assocs);
		}

		// Is user authorized to edit citations?
		if (!$isAdmin && !$pubAuthor)
		{
			$id = 0;
		}

		// Load the object
		$this->view->row = new Citation($this->database);
		$this->view->row->load($id);

		//make sure title isnt too long
		$maxTitleLength = 30;
		$shortenedTitle = (strlen($this->view->row->title) > $maxTitleLength)
						? substr($this->view->row->title, 0, $maxTitleLength) . '&hellip;'
						: $this->view->row->title;

		// Set the pathway
		$pathway = \JFactory::getApplication()->getPathway();
		Pathway::append(Lang::txt(strtoupper($this->_option)), 'index.php?option=' . $this->_option);
		if ($id && $id != 0)
		{
			Pathway::append($shortenedTitle, 'index.php?option=' . $this->_option . '&task=view&id=' . $this->view->row->id);
		}
		Pathway::append(Lang::txt('COM_CITATIONS_EDIT'), 'index.php?option=' . $this->_option . '&task=edit&id=' . $this->view->row->id);

		// Set the page title
		$document = \JFactory::getDocument();
		$document->setTitle(Lang::txt('COM_CITATIONS_CITATION') . $shortenedTitle);

		//push jquery to doc
		$document = \JFactory::getDocument();
		$document->addScriptDeclaration('var fields = ' . json_encode($fields) . ';');

		// Instantiate a new view
		$this->view->title  = Lang::txt(strtoupper($this->_option)) . ': ' . Lang::txt(strtoupper($this->_option) . '_' . strtoupper($this->_task));
		$this->view->config = $this->config;

		// No ID, so we're creating a new entry
		// Set the ID of the creator
		if (!$id)
		{
			$this->view->row->uid = User::get('id');

			// It's new - no associations to get
			$this->view->assocs = array();

			//tags & badges
			$this->view->tags   = array();
			$this->view->badges = array();
		}
		else
		{
			//tags & badges
			$this->view->tags   = Format::citationTags($this->view->row, $this->database, false);
			$this->view->badges = Format::citationBadges($this->view->row, $this->database, false);
		}

		//get the citation types
		$ct = new Type($this->database);
		$this->view->types = $ct->getType();

		// Output HTML
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		$this->view
			->setLayout('edit')
			->display();
	}

	/**
	 * Determine if user is part of publication project and is allowed to edit citation
	 *
	 * @param   array  $assocs
	 * @return  void
	 */
	public function isPubAuthor($assocs)
	{
		if (!$assocs)
		{
			return false;
		}
		if (!is_file(PATH_ROOT . DS . 'components' . DS . 'com_publications' . DS . 'tables' . DS . 'publication.php'))
		{
			return false;
		}

		require_once(PATH_ROOT . DS . 'components' . DS . 'com_publications' . DS . 'tables' . DS . 'publication.php');
		require_once(PATH_ROOT . DS . 'components' . DS . 'com_projects' . DS . 'tables' . DS . 'owner.php');

		// Get connections to publications
		foreach ($assocs as $entry)
		{
			if ($entry->tbl == 'publication')
			{
				$pubID = $entry->oid;
				$objP = new \Components\Publications\Tables\Publication($this->database);

				if ($objP->load($pubID))
				{
					$objO = new \ProjectOwner($this->database);

					if ($objO->isOwner(User::get('id'), $objP->project_id))
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Save an entry
	 *
	 * @return  void
	 */
	public function saveTask()
	{
		// Check if they're logged in
		if (User::isGuest())
		{
			$this->loginTask();
			return;
		}

		//get the posted vars
		$c = $_POST;
		if (isset($c['format_type']))
		{
			$c['format'] = $c['format_type'];
		}

		//get tags
		$tags = trim(Request::getVar('tags', ''));
		unset($c['tags']);

		//get badges
		$badges = trim(Request::getVar('badges', ''));
		unset($c['badges']);

		// clean vars
		foreach ($c as $key => $val)
		{
			if (!is_array($val))
			{
				$c[$key] = Sanitize::stripScripts($val);
			}
		}

		// Bind incoming data to object
		$row = new Citation($this->database);
		if (!$row->bind($c))
		{
			$this->setError($row->getError());
			$this->editTask();
			return;
		}

		// New entry so set the created date
		if (!$row->id)
		{
			$row->created = \Date::toSql();
		}

		// Field named 'uri' due to conflict with existing 'url' variable
		$row->url = Request::getVar('uri', '', 'post');
		$row->url = Sanitize::clean($row->url);
		if (!filter_var($row->url, FILTER_VALIDATE_URL))
		{
			$row->url = null;
		}

		// Check content for missing required data
		if (!$row->check())
		{
			$this->setError($row->getError());
			$this->editTask();
			return;
		}

		// Store new content
		if (!$row->store())
		{
			$this->setError($row->getError());
			$this->editTask();
			return;
		}

		// Incoming associations
		$arr = Request::getVar('assocs', array());

		$ignored = array();

		foreach ($arr as $a)
		{
			$a = array_map('trim', $a);

			// Initiate extended database class
			$assoc = new Association($this->database);

			//check to see if we should delete
			if (isset($a['id']) && $a['tbl'] == '' && $a['oid'] == '')
			{
				// Delete the row
				if (!$assoc->delete($a['id']))
				{
					$this->setError($assoc->getError());
					$this->editTask();
					return;
				}
			}
			else if ($a['tbl'] != '' || $a['oid'] != '')
			{
				$a['cid'] = $row->id;

				// bind the data
				if (!$assoc->bind($a))
				{
					$this->setError($assoc->getError());
					$this->editTask();
					return;
				}

				// Check content
				if (!$assoc->check())
				{
					$this->setError($assoc->getError());
					$this->editTask();
					return;
				}

				// Store new content
				if (!$assoc->store())
				{
					$this->setError($assoc->getError());
					$this->editTask();
					return;
				}
			}
		}

		//check if we are allowing tags
		if ($this->config->get('citation_allow_tags', 'no') == 'yes')
		{
			$ct1 = new Tags($row->id);
			$ct1->setTags($tags, User::get('id'), 0, 1, '');
		}

		//check if we are allowing badges
		if ($this->config->get('citation_allow_badges', 'no') == 'yes')
		{
			$ct2 = new Tags($row->id);
			$ct2->setTags($badges, User::get('id'), 0, 1, 'badge');
		}

		// Redirect
		if ($this->config->get('citation_single_view', 1))
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&task=view&id=' . $row->id),
				Lang::txt('COM_CITATIONS_CITATION_SAVED')
			);
		}
		else
		{
			$this->setRedirect(
				Route::url('index.php?option=' . $this->_option . '&task=browse'),
				Lang::txt('COM_CITATIONS_CITATION_SAVED')
			);
		}

		return;
	}

	/**
	 * Delete one or more entries
	 *
	 * @return     void
	 */
	public function deleteTask()
	{
		// Check if they're logged in
		if (User::isGuest())
		{
			$this->loginTask();
			return;
		}

		// Incoming (we're expecting an array)
		$ids = Request::getVar('id', array());
		if (!is_array($ids))
		{
			$ids = array();
		}

		// Make sure we have IDs to work with
		if (count($ids) > 0)
		{
			// Loop through the IDs and delete the citation
			$citation = new Citation($this->database);
			$assoc    = new Association($this->database);
			$author   = new Author($this->database);
			foreach ($ids as $id)
			{
				// Fetch and delete all the associations to this citation
				$isAdmin = (User::get("usertype") == "Super Administrator") ? true : false;
				$assocs = $assoc->getRecords(array('cid' => $id), $isAdmin);
				foreach ($assocs as $a)
				{
					$assoc->delete($a->id);
				}

				// Fetch and delete all the authors to this citation
				$authors = $author->getRecords(array('cid' => $id), $isAdmin);
				foreach ($authors as $a)
				{
					$author->delete($a->id);
				}

				// Delete the citation
				$citation->delete($id);
			}
		}

		// Redirect
		$this->setRedirect(
			Route::url('index.php?option=' . $this->_option)
		);
	}

	/**
	 * Download a citation
	 *
	 * @return  string
	 */
	public function downloadTask()
	{
		// Incoming
		$id = Request::getInt('id', 0, 'request');
		$format = strtolower(Request::getVar('format', 'bibtex', 'request'));

		// Esnure we have an ID to work with
		if (!$id)
		{
			throw new Exception(Lang::txt('COM_CITATIONS_NO_CITATION_ID'), 500);
		}

		// Load the citation
		$row = new Citation($this->database);
		$row->load($id);

		// Set the write path
		$path = PATH_APP . DS . trim($this->config->get('uploadpath', '/site/citations'), DS);

		$formatter = new Download();
		$formatter->setFormat($format);

		// Set some vars
		$doc  = $formatter->formatReference($row);
		$mime = $formatter->getMimeType();
		$file = 'download_' . $id . '.' . $formatter->getExtension();

		// Ensure we have a directory to write files to
		if (!is_dir($path))
		{
			jimport('joomla.filesystem.folder');
			if (!\JFolder::create($path))
			{
				throw new Exception(Lang::txt('COM_CITATIONS_UNABLE_TO_CREATE_UPLOAD_PATH'), 500);
			}
		}

		// Write the contents to a file
		$fp = fopen($path . DS . $file, "w") or die("can't open file");
		fwrite($fp, $doc);
		fclose($fp);

		$this->_serveup(false, $path, $file, $mime);

		die; // REQUIRED
	}

	/**
	 * Download a batch of entries
	 *
	 * @return  void
	 */
	public function downloadbatchTask()
	{
		// get the submit buttons value
		$download = Request::getVar('download', '', 'post');

		// get the citations we want to export
		$citationsString = Request::getVar('idlist', '' , "post");
		$citations       = explode('-', $citationsString);

		// return to browse mode if we really dont wanna download
		if (strtolower($download) != 'endnote'
		 && strtolower($download) != 'bibtex')
		{
			return $this->displayTask();
		}

		// var to hold output
		$doc = '';

		// for each citation we want to downlaod
		foreach ($citations as $c)
		{
			$cc = new Citation($this->database);
			$cc->load($c);

			//get the badges
			$ct = new Tags($cc->id);
			$cc->badges = $ct->render('string', array('label' => 'badge'));

			$cd = new Download();
			$cd->setFormat(strtolower($download));
			$doc .= $cd->formatReference($cc) . "\r\n\r\n";

			$mine = $cd->getMimeType();
		}

		$ext = (strtolower($download) == 'bibtex') ? '.bib' : '.enw';

		// filename
		$filename = 'citations_export_' . strtolower($download) . '_' . \JFactory::getDate()->format('Y_m_d') . $ext;

		// output file
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: Attachment; filename=' . $filename);
		header('Pragma: no-cache');
		echo $doc;
		exit();
	}

	/**
	 * Check if an array is empty, ignoring keys in the $ignored list
	 *
	 * @param   array    $b        Array of data to check
	 * @param   array    $ignored  Array of keys to bypass
	 * @return  boolean  True if empty, false if not
	 */
	private function _isempty($b, $ignored=array())
	{
		foreach ($ignored as $ignore)
		{
			if (array_key_exists($ignore, $b))
			{
				$b[$ignore] = NULL;
			}
		}
		if (array_key_exists('id', $b))
		{
			$b['id'] = NULL;
		}
		$values = array_values($b);
		$e = true;
		foreach ($values as $v)
		{
			if ($v)
			{
				$e = false;
			}
		}
		return $e;
	}

	/**
	 * Serve up a file
	 *
	 * @param   boolean  $inline  Serve inline?
	 * @param   string   $p       File path
	 * @param   string   $f       File name
	 * @param   string   $mime    Mime type
	 * @return  void
	 */
	private function _serveup($inline = false, $p, $f, $mime)
	{
		// Clean all output buffers (needs PHP > 4.2.0)
		while (@ob_end_clean());

		$fsize = filesize($p . DS. $f);
		$mod_date = date('r', filemtime($p . DS . $f));

		$cont_dis = $inline ? 'inline' : 'attachment';

		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Expires: 0");

		header("Content-Transfer-Encoding: binary");
		header(
			'Content-Disposition:' . $cont_dis . ';'
			. ' filename="' . $f . '";'
			. ' modification-date="' . $mod_date . '";'
			. ' size=' . $fsize . ';'
		); //RFC2183
		header("Content-Type: " . $mime); // MIME type
		header("Content-Length: " . $fsize);

		// No encoding - we aren't using compression... (RFC1945)
		//header("Content-Encoding: none");
		//header("Vary: none");

		$this->_readfile_chunked($p . DS . $f);
		// The caller MUST 'die();'
	}

	/**
	 * Read a file in chunks
	 *
	 * @param   unknown  $filename  File name
	 * @param   boolean  $retbytes
	 * @return  mixed
	 */
	private function _readfile_chunked($filename, $retbytes=true)
	{
		$chunksize = 1*(1024*1024); // How many bytes per chunk
		$buffer = '';
		$cnt =0;
		$handle = fopen($filename, 'rb');
		if ($handle === false)
		{
			return false;
		}
		while (!feof($handle))
		{
			$buffer = fread($handle, $chunksize);
			echo $buffer;
			if ($retbytes)
			{
				$cnt += strlen($buffer);
			}
		}
		$status = fclose($handle);
		if ($retbytes && $status)
		{
			return $cnt; // Return num. bytes delivered like readfile() does.
		}
		return $status;
	}

	/**
	 * Return the citation format
	 *
	 * @return  void
	 */
	public function getformatTask()
	{
		echo 'format' . Request::getVar('format', 'apa');
	}

	/**
	 * Serve up an image
	 *
	 * @return  void
	 */
	public function downloadimageTask()
	{
		// get the image we want to serve
		$image = Request::getVar('image', '');

		// if we dont have an image were done
		if ($image == '') return;

		// file details
		$image_details = pathinfo($image);

		// make sure we have an image
		$image_headers = @get_headers($image);
		if (!is_array($image_headers) || strstr($image_headers[0], '200') === false)
		{
			exit();
		}

		//ouput image based on type
		switch ($image_details['extension'])
		{
			case 'gif':
				$image_resource = imagecreatefromgif($image);
				header('Content-Type: image/gif');
				imagegif($image_resource);
				break;
			case 'jpg':
			case 'jpeg':
				$image_resource = imagecreatefromjpeg($image);
				header('Content-Type: image/jpeg');
				imagejpeg($image_resource);
				break;
			case 'png':
				$image_resource = imagecreatefrompng($image);
				header('Content-Type: image/png');
				imagepng($image_resource);
				break;
		}
		exit();
	}

	/**
	 * Method to set the document path
	 *
	 * @return  void
	 */
	protected function _buildPathway()
	{
		$pathway = \JFactory::getApplication()->getPathway();

		if (Pathway::count() <= 0)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option)),
				'index.php?option=' . $this->_option
			);
		}
		if ($this->_task)
		{
			Pathway::append(
				Lang::txt(strtoupper($this->_option) . '_' . strtoupper($this->_task)),
				'index.php?option=' . $this->_option . '&task=' . $this->_task
			);
		}
	}

	/**
	 * Method to build and set the document title
	 *
	 * @return  void
	 */
	protected function _buildTitle()
	{
		$this->_title = Lang::txt(strtoupper($this->_option));
		if ($this->_task)
		{
			$this->_title .= ': ' . Lang::txt(strtoupper($this->_option) . '_' . strtoupper($this->_task));
		}
		$document = \JFactory::getDocument();
		$document->setTitle($this->_title);
	}
}
