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

namespace Components\Wiki\Models;

use Components\Wiki\Helpers\Parser;
use Components\Wiki\Tables;
use Hubzero\Base\Model;
use Hubzero\Base\ItemList;
use Hubzero\Utility\String;
use Request;
use Lang;
use User;
use Date;

require_once(dirname(__DIR__) . DS . 'tables' . DS . 'page.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'log.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'attachment.php');

require_once(__DIR__ . DS . 'author.php');
require_once(__DIR__ . DS . 'revision.php');
require_once(__DIR__ . DS . 'tags.php');
require_once(__DIR__ . DS . 'comment.php');

/**
 * Wiki model for a page
 */
class Page extends Model
{
	/**
	 * JRegistry
	 *
	 * @var object
	 */
	private $_params = null;

	/**
	 * JRegistry
	 *
	 * @var object
	 */
	private $_config = null;

	/**
	 * WikiModelIterator
	 *
	 * @var object
	 */
	private $_comments = NULL;

	/**
	 * Comment count
	 *
	 * @var integer
	 */
	private $_comments_count = NULL;

	/**
	 * Revisions count
	 *
	 * @var integer
	 */
	private $_revisions_count = null;

	/**
	 * WikiModelIterator
	 *
	 * @var object
	 */
	private $_revisions = null;

	/**
	 * WikiModelRevision
	 *
	 * @var object
	 */
	private $_revision = null;

	/**
	 * JUser
	 *
	 * @var object
	 */
	private $_creator = null;

	/**
	 * WikiModelIterator
	 *
	 * @var object
	 */
	private $_authors = null;

	/**
	 * WikiModelAdapter
	 *
	 * @var object
	 */
	private $_adapter = null;

	/**
	 * Constructor
	 *
	 * @param   integer $id    [ID, pagename, object, array]
	 * @param   string  $scope Page scope
	 * @return  void
	 */
	public function __construct($oid, $scope='')
	{
		$this->_db = \JFactory::getDBO();

		$this->_tbl = new Tables\Page($this->_db);

		$pagename = '';

		if ($oid)
		{
			if (is_numeric($oid))
			{
				$this->_tbl->load($oid);
			}
			else if (is_string($oid))
			{
				$this->_tbl->load($oid, $scope);
				$pagename = $oid;
			}
			else if (is_object($oid) || is_array($oid))
			{
				$this->bind($oid);
				$pagename = (is_object($oid) ? $oid->pagename : $oid['pagename']);
			}
		}

		$space = strtolower(strstr($this->get('pagename', $pagename), ':', true));
		$space = $space ? $space : '';
		$this->set('namespace', $space);

		if (!$this->get('group_cn'))
		{
			$this->set('group_cn', Request::getVar('cn', ''));
		}

		/*if ($space == 'special')
		{
			$this->set('title', ltrim(strstr($this->get('pagename'), ':'), ':'));
		}*/

		$this->set('title', $this->_tbl->getTitle());

		$this->_params = new \JRegistry($this->get('params'));
	}

	/**
	 * Returns a reference to a page model
	 * Can be called with a numeric page ID, object, array, or
	 * pagename + page scope
	 *
	 * @param   mixed  $oid   [ID, pagename, object, array]
	 * @param   string $scope Page scope
	 * @return  object WikiModelPage
	 */
	static function &getInstance($pagename, $scope='')
	{
		static $instances;

		if (!isset($instances))
		{
			$instances = array();
		}

		if (is_object($pagename))
		{
			$key = $scope . '/' . $pagename->pagename;
		}
		else if (is_array($pagename))
		{
			$key = $scope . '/' . $pagename['pagename'];
		}
		else
		{
			$key = $scope . '/' . $pagename;
		}

		if (!isset($instances[$key]))
		{
			$instances[$key] = new self($pagename, $scope);
		}

		return $instances[$key];
	}

	/**
	 * Strip punctuation, spaces, make lowercase
	 *
	 * @param   string $data Text to normalize
	 * @return  string
	 */
	public function normalize($data)
	{
		return $this->_tbl->normalize($data);
	}

	/**
	 * Has the offering started?
	 *
	 * @return  boolean
	 */
	public function isLocked()
	{
		if ($this->get('state') == 1)
		{
			return true;
		}
		return false;
	}

	/**
	 * Has the offering started?
	 *
	 * @return  boolean
	 */
	public function isStatic()
	{
		if ($this->param('mode') == 'static')
		{
			return true;
		}
		return false;
	}

	/**
	 * Returns whether a user is an author for a given page
	 *
	 * @param   integer $user_id
	 * @return  boolean True if user is an author
	 */
	public function isAuthor($user_id=0)
	{
		if ($this->get('author-' . $user_id, null) === null)
		{
			if (!$user_id)
			{
				$user_id = User::get('id');
			}
			$wpa = new Tables\Author($this->_db);
			$this->set('author-' . $user_id, $wpa->isAuthor($this->get('id'), $user_id));
		}

		return $this->get('author-' . $user_id, false);
	}

	/**
	 * Get the creator of this entry
	 *
	 * Accepts an optional property name. If provided
	 * it will return that property value. Otherwise,
	 * it returns the entire JUser object
	 *
	 * @param   string $property Property to find
	 * @param   mixed  $default  Value to return if property not found
	 * @return  mixed
	 */
	public function creator($property=null, $default=null)
	{
		if (!($this->_creator instanceof \JUser))
		{
			$this->_creator = User::getInstance($this->get('created_by'));
			if (!$this->_creator)
			{
				$this->_creator = new \JUser();
			}
		}
		if ($property)
		{
			return $this->_creator->get($property, $default);
		}
		return $this->_creator;
	}

	/**
	 * Get the pagename without the namespace
	 *
	 * @return  string
	 */
	public function denamespaced()
	{
		return ltrim(strstr($this->get('pagename'), ':'), ':');
	}

	/**
	 * Set and get a specific revision
	 * Defaults to current revision if no version is specified
	 *
	 * @return  void
	 */
	public function revision($version=null)
	{
		// If no revision is set AND no specific version is passed ...
		if (!isset($this->_revision) && !$version)
		{
			// Set the revision to the current version
			$this->_revision = new Revision((int) $this->get('version_id'));
		}

		// If version is specified AND (no revision set or (revision is set and version doesn't match)) ...
		if ($version
		 && (
				!isset($this->_revision)
			|| (isset($this->_revision) && $version != $this->_revision->get('version'))
			)
		)
		{
			$this->_revision = null;

			if (isset($this->_revisions))
			{
				switch ($version)
				{
					case 'first':
						$this->revisions()->first();
						$this->_revision = $this->revisions()->current();
					break;

					case 'current':
						$this->revisions()->last();
						$this->_revision = $this->revisions()->current();
					break;

					default:
						foreach ($this->revisions('list') as $key => $revision)
						{
							if ($revision->get('version') == $version)
							{
								$this->_revision = $revision;
								break;
							}
						}
					break;
				}
			}

			if (!$this->_revision)
			{
				switch ($version)
				{
					case 'first':
						$this->_revision = new Revision($this->_tbl->getRevision('first'));
					break;

					case 'current':
						$this->_revision = new Revision((int) $this->get('version_id'));
					break;

					default:
						$this->_revision = new Revision((int) $version, $this->get('id'));
					break;
				}
			}
		}

		return $this->_revision;
	}

	/**
	 * Get a count or list of revisions
	 *
	 * @param   string  $rtrn    Data format to return
	 * @param   array   $filters Filters to apply to data fetch
	 * @param   boolean $clear   Clear cached data?
	 * @return  mixed
	 */
	public function revisions($what='list', $filters=array(), $clear=false)
	{
		if (!isset($filters['pageid']))
		{
			$filters['pageid'] = $this->get('id');
		}
		if (!isset($filters['approved']))
		{
			$filters['approved'] = array(0, 1);
		}
		if (!isset($filters['sortby']))
		{
			$filters['sortby'] = 'version ASC';
		}

		switch (strtolower($what))
		{
			case 'count':
				if (!is_numeric($this->_revisions_count) || $clear)
				{
					$tbl = new Tables\Revision($this->_db);
					$this->_revisions_count = $tbl->getRecordsCount($filters);
				}
				return $this->_revisions_count;
			break;

			case 'list':
			case 'results':
			default:
				if (!($this->_revisions instanceof ItemList) || $clear)
				{
					$results = array();

					$tbl = new Tables\Revision($this->_db);
					if (($results = $tbl->getRecords($filters)))
					{
						foreach ($results as $key => $result)
						{
							$results[$key] = new Revision($result);
						}
					}

					$this->_revisions = new ItemList($results);
				}

				return $this->_revisions;
			break;
		}
	}

	/**
	 * Get a count or list of authors
	 *
	 * @param   string  $rtrn    Data format to return
	 * @param   array   $filters Filters to apply to data fetch
	 * @param   boolean $clear   Clear cached data?
	 * @return  mixed
	 */
	public function authors($what='list', $filters=array(), $clear=false)
	{
		if (!isset($filters['pageid']))
		{
			$filters['pageid'] = $this->get('id');
		}

		switch (strtolower($what))
		{
			case 'string':
				$authors = array();
				foreach ($this->authors('list') as $author)
				{
					$authors[] = $author->get('username');
				}
				return implode(', ', $authors);
			break;

			case 'count':
				if (!isset($this->_revisions_count) || $clear)
				{
					$this->_revisions_count = $this->authors('list')->total();
				}
				return $this->_revisions_count;
			break;

			case 'list':
			case 'results':
			default:
				if (!($this->_revisions instanceof ItemList) || $clear)
				{
					$results = array();

					$tbl = new Tables\Author($this->_db);
					if (($results = $tbl->getAuthors($this->get('id'))))
					{
						foreach ($results as $key => $result)
						{
							$results[$key] = new Author($result);
						}
					}

					$this->_revisions = new ItemList($results);
				}

				return $this->_revisions;
			break;
		}
	}

	/**
	 * Get a count or list of comments
	 *
	 * @param   string  $rtrn    Data format to return
	 * @param   array   $filters Filters to apply to data fetch
	 * @param   boolean $clear   Clear cached data?
	 * @return  mixed   Returns an integer or array depending upon format chosen
	 */
	public function comments($rtrn='list', $filters=array(), $clear=false)
	{
		if (!isset($filters['pageid']))
		{
			$filters['pageid'] = $this->get('id');
		}
		if (!isset($filters['parent']))
		{
			$filters['parent'] = '0';
		}
		if (!isset($filters['status']))
		{
			$filters['status'] = array(self::APP_STATE_PUBLISHED, self::APP_STATE_FLAGGED);
		}

		switch (strtolower($rtrn))
		{
			case 'count':
				if (!is_numeric($this->_comments_count) || $clear)
				{
					$tbl = new Tables\Comment($this->_db);

					$this->_comments_count = $tbl->find('count', $filters);
				}
				return $this->_comments_count;
			break;

			case 'list':
			case 'results':
			default:
				if (!($this->_comments instanceof ItemList) || $clear)
				{
					if (!isset($filters['parent']))
					{
						$filters['parent'] = 0;
					}

					$tbl = new Tables\Comment($this->_db);

					if ($results = $tbl->find('list', $filters))
					{
						foreach ($results as $key => $result)
						{
							$results[$key] = new Comment($result);
						}
					}
					else
					{
						$results = array();
					}
					$this->_comments = new ItemList($results);
				}
				return $this->_comments;
			break;
		}
	}

	/**
	 * Get tags on this entry
	 *
	 * @param   string  $what  Data format to return
	 * @param   integer $admin Return admin tags?
	 * @return  mixed
	 */
	public function tags($what='cloud', $admin=0)
	{
		$cloud = new Tags(($this->get('id') ? $this->get('id') : -1));

		return $cloud->render($what, array('admin' => $admin));
	}

	/**
	 * Tag the entry
	 *
	 * @param   string  $tags    Tags to apply
	 * @param   integer $user_id ID of tagger
	 * @param   integer $admin   Tag as admin? 0=no, 1=yes
	 * @return  boolean
	 */
	public function tag($tags=null, $user_id=0, $admin=0)
	{
		$cloud = new Tags($this->get('id'));

		return $cloud->setTags($tags, $user_id, $admin);
	}

	/**
	 * Generate and return various links to the entry
	 * Link will vary depending upon action desired, such as edit, delete, etc.
	 *
	 * @param   string  $type The type of link to return
	 * @return  boolean
	 */
	public function link($type='', $params=null)
	{
		return $this->_adapter()->link($type, $params);
	}

	/**
	 * Return a formatted timestamp for created date
	 *
	 * @param   string $as What data to return
	 * @return  string
	 */
	public function created($as='')
	{
		return $this->_date($as, 'created');
	}

	/**
	 * Return a formatted timestamp for modified date
	 *
	 * @param   string $as What data to return
	 * @return  string
	 */
	public function modified($as='')
	{
		return $this->_date($as, 'modified');
	}

	/**
	 * Return a formatted timestamp
	 *
	 * @param   string $as What data to return
	 * @return  string
	 */
	private function _date($as='', $property)
	{
		switch (strtolower($as))
		{
			case 'date':
				return Date::of($this->get($property))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return Date::of($this->get($property))->toLocal(Lang::txt('TIME_FORMAT_HZ1'));
			break;

			default:
				return $this->get($property);
			break;
		}
	}

	/**
	 * Get the content of the record.
	 * Optional argument to determine how content should be handled
	 *
	 * parsed - performs parsing on content (i.e., converting wiki markup to HTML)
	 * clean  - parses content and then strips tags
	 * raw    - as is, no parsing
	 *
	 * @param   string  $as      Format to return content in [parsed, clean, raw]
	 * @param   integer $shorten Number of characters to shorten text to
	 * @return  mixed   String or Integer
	 */
	public function content($as='parsed', $shorten=0)
	{
		$as = strtolower($as);

		switch ($as)
		{
			case 'parsed':
				if ($this->get('pagetext_parsed'))
				{
					return $this->get('pagetext_parsed');
				}

				$p = Parser::getInstance();

				$wikiconfig = array(
					'option'   => 'com_wiki',
					'scope'    => $this->get('scope'),
					'pagename' => $this->get('pagename'),
					'pageid'   => $this->get('id'),
					'filepath' => $this->config('uploadpath'),
					'domain'   => ''
				);

				$this->set('pagetext_parsed', $p->parse(stripslashes($this->get('pagetext')), $wikiconfig));

				if ($shorten)
				{
					$content = String::truncate($this->get('pagetext_parsed'), $shorten, array('html' => true));
					return $content;
				}

				return $this->get('pagetext_parsed');
			break;

			case 'clean':
				$content = strip_tags($this->content('parsed'));
				if ($shorten)
				{
					$content = String::truncate($content, $shorten);
				}
				return $content;
			break;

			case 'raw':
			default:
				$content = $this->get('pagetext');
				if ($shorten)
				{
					$content = String::truncate($content, $shorten);
				}
				return $content;
			break;
		}
	}

	/**
	 * Get a param value
	 *
	 * @param   string $key     Property to return
	 * @param   mixed  $default Value to return if key isn't found
	 * @return  mixed
	 */
	public function param($key='', $default=null)
	{
		if ($key)
		{
			return $this->_params->get((string) $key, $default);
		}
		return $this->_params;
	}

	/**
	 * Get a configuration value
	 *
	 * @param   string $key     Property to return
	 * @param   mixed  $default Value to return if key isn't found
	 * @return  mixed
	 */
	public function config($key='', $default=null)
	{
		if (!isset($this->_config))
		{
			$this->_config = Component::params('com_wiki');
		}
		if ($key)
		{
			return $this->_config->get((string) $key, $default);
		}
		return $this->_config;
	}

	/**
	 * Get permissions for a user
	 *
	 * @param   string  $action
	 * @param   string  $item
	 * @return  boolean
	 */
	public function access($action='view', $item='page')
	{
		if (!$this->config('access-check-done', false))
		{
			$this->config()->set('access-page-view', true);
			$this->config()->set('access-page-manage', false);
			$this->config()->set('access-page-admin', false);
			$this->config()->set('access-page-create', false);
			$this->config()->set('access-page-delete', false);
			$this->config()->set('access-page-edit', false);
			$this->config()->set('access-page-modify', false);

			$this->config()->set('access-comment-view', false);
			$this->config()->set('access-comment-create', false);
			$this->config()->set('access-comment-delete', false);
			$this->config()->set('access-comment-edit', false);

			// Check if they are logged in
			if (User::isGuest())
			{
				// Not logged-in = can only view
				$this->config()->set('access-check-done', true);
			}

			$option = Request::getCmd('option', 'com_wiki');

			if (!$this->config('access-check-done', false))
			{
				// Is a group set?
				if (trim($this->get('group_cn', '')))
				{
					$group = \Hubzero\User\Group::getInstance($this->get('group_cn'));

					// Is this a group manager?
					if ($group && $group->is_member_of('managers', User::get('id')))
					{
						// Allow access to all options
						$this->config()->set('access-page-manage', true);
						$this->config()->set('access-page-create', true);
						$this->config()->set('access-page-delete', true);
						$this->config()->set('access-page-edit', true);
						$this->config()->set('access-page-modify', true);

						$this->config()->set('access-comment-view', true);
						$this->config()->set('access-comment-create', true);
						$this->config()->set('access-comment-delete', true);
						$this->config()->set('access-comment-edit', true);
					}
					else
					{
						// Check permissions based on the page mode (knol/wiki)
						switch ($this->param('mode'))
						{
							// Knowledge article
							// This means there's a defined set of authors
							case 'knol':
								if ($this->get('created_by') == User::get('id')
								 || $this->isAuthor(User::get('id')))
								{
									$this->config()->set('access-page-create', true);
									$this->config()->set('access-page-delete', true);
									$this->config()->set('access-page-edit', true);
									$this->config()->set('access-page-modify', true);
								}
								else if ($this->param('allow_changes'))
								{
									$this->config()->set('access-page-modify', true); // This allows users to suggest changes
								}

								if ($this->param('allow_comments'))
								{
									$this->config()->set('access-comment-view', true);
									$this->config()->set('access-comment-create', true);
								}
							break;

							// Standard wiki
							default:
								// 1 = private to group, 2 = ...um, can't remember
								if ($group && $group->is_member_of('members', User::get('id')))
								{
									$this->config()->set('access-page-create', true);
									if ($this->get('state') != 1)
									{
										$this->config()->set('access-page-delete', true);
										$this->config()->set('access-page-edit', true);
										$this->config()->set('access-page-modify', true);
									}

									$this->config()->set('access-comment-view', true);
									$this->config()->set('access-comment-create', true);
								}
							break;
						}
					}
				}
				// Check if they're a site admin (from Joomla)
				else if (User::authorize($option, 'manage'))
				{
					$this->config()->set('access-page-admin', true);
					$this->config()->set('access-page-manage', true);
					$this->config()->set('access-page-create', true);
					$this->config()->set('access-page-delete', true);
					$this->config()->set('access-page-edit', true);
					$this->config()->set('access-page-modify', true);

					$this->config()->set('access-comment-view', true);
					$this->config()->set('access-comment-create', true);
					$this->config()->set('access-comment-delete', true);
					$this->config()->set('access-comment-edit', true);

					$this->config()->set('access-check-done', true);
				}
				// No group = Site wiki
				else
				{
					$this->config()->set('access-page-create', true);

					// Check permissions based on the page mode (knol/wiki)
					switch ($this->param('mode'))
					{
						// Knowledge article
						// This means there's a defined set of authors
						case 'knol':
							if ($this->get('created_by') == User::get('id')
							 || $this->isAuthor(User::get('id')))
							{
								$this->config()->set('access-page-delete', true);
								$this->config()->set('access-page-edit', true);
								$this->config()->set('access-page-modify', true);
							}
							else if ($this->param('allow_changes'))
							{
								$this->config()->set('access-page-modify', true); // This allows users to suggest changes
							}

							if ($this->param('allow_comments'))
							{
								$this->config()->set('access-comment-view', true);
								$this->config()->set('access-comment-create', true);
							}
						break;

						// Standard wiki
						default:
							$this->config()->set('access-page-delete', true);
							$this->config()->set('access-page-edit', true);
							$this->config()->set('access-page-modify', true);

							$this->config()->set('access-comment-view', true);
							$this->config()->set('access-comment-create', true);
						break;
					}
				}
				$this->config()->set('access-check-done', true);
			}
		}

		return $this->config('access-' . (string) $item . '-' . strtolower($action));
	}

	/**
	 * Rename a page
	 *
	 * @param   string  $newpagename New page name
	 * @return  boolean True on success, False on error
	 */
	public function rename($newpagename)
	{
		// Are they just changing case of characters?
		if (!trim($newpagename))
		{
			$this->setError(Lang::txt('No new name provided.'));
			return false;
		}

		$newpagename = $this->_tbl->normalize($newpagename);

		// Are they just changing case of characters?
		if (strtolower($this->get('pagename')) == strtolower($newpagename))
		{
			$this->setError(Lang::txt('New name matches old name.'));
			return false;
		}

		// Check that no other pages are using the new title
		$p = new self($newpagename, $this->get('scope'));
		if ($p->exists())
		{
			$this->setError(Lang::txt('COM_WIKI_ERROR_PAGE_EXIST') . ' ' . Lang::txt('CHOOSE_ANOTHER_PAGENAME'));
			return false;
		}

		$this->set('pagename', $newpagename);

		if (!$this->store(true, 'page_renamed'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Store changes to this entry
	 *
	 * @param   boolean $check  Perform data validation check?
	 * @param   string  $action Action beign performed
	 * @return  boolean False if error, True on success
	 */
	public function store($check=true, $action='page_edited')
	{
		// Validate data?
		if ((bool) $check)
		{
			// Is data valid?
			if (!$this->_tbl->check())
			{
				$this->setError($this->_tbl->getError());
				return false;
			}
		}

		$action = (!$this->exists() ? 'page_created' : $action);

		// Attempt to store data
		if (!$this->_tbl->store())
		{
			$this->setError($this->_tbl->getError());
			return false;
		}

		$this->log($action);

		return true;
	}

	/**
	 * Delete a record
	 *
	 * @return  boolean True on success, false on error
	 */
	public function delete()
	{
		// Remove authors
		foreach ($this->authors() as $author)
		{
			if (!$author->delete())
			{
				$this->setError($author->getError());
				return false;
			}
		}

		// Remove comments
		foreach ($this->comments() as $comment)
		{
			if (!$comment->delete())
			{
				$this->setError($comment->getError());
				return false;
			}
		}

		// Remove revisions
		foreach ($this->revisions() as $revision)
		{
			if (!$revision->delete())
			{
				$this->setError($revision->getError());
				return false;
			}
		}

		// Remove tags
		$this->tag('');

		// Remove files
		jimport('joomla.filesystem.folder');
		$path = PATH_APP . DS . trim($this->config('filepath', '/site/wiki'), DS);
		if (is_dir($path . DS . $this->get('id')))
		{
			if (!\JFolder::delete($path . DS . $this->get('id')))
			{
				$this->setError(Lang::txt('COM_WIKI_UNABLE_TO_DELETE_FOLDER'));
			}
		}

		// Remove record from the database
		if (!$this->_tbl->delete())
		{
			$this->setError($this->_tbl->getError());
			return false;
		}

		$this->log('page_deleted');

		// Clear cached data
		$cache = \JCache::getInstance('', array(
			'defaultgroup' => '',
			'storage'      => Config::get('cache_handler', ''),
			'caching'      => true,
			'cachebase'    => Config::get('cache_path', PATH_APP . '/cache')
		));
		$cache->clean('wiki');

		// Hey, no errors!
		return true;
	}

	/**
	 * Log an action
	 *
	 * @param   string  $action  Action taken
	 * @param   integer $user_id Optional ID of user the action was taken on/with
	 * @return  void
	 */
	public function log($action='page_created', $user_id=0)
	{
		$data = new \stdClass();
		foreach ($this->_tbl->getProperties() as $property)
		{
			if ($this->get($property))
			{
				$data->$property = $this->get($property);
			}
		}

		$log = new Tables\Log($this->_db);
		$log->pid       = (int) $this->get('id');
		$log->uid       = ($user_id ? $user_id : User::get('id'));
		$log->timestamp = Date::toSql();
		$log->action    = (string) $action;
		$log->actorid   = $user->get('id');
		$log->comments  = json_encode($data);
		if (!$log->store())
		{
			$this->setError($log->getError());
		}
	}

	/**
	 * Calculate the average rating for the page
	 *
	 * @return  integer
	 */
	public function calculateRating()
	{
		return $this->_tbl->calculateRating();
	}

	/**
	 * Update the list of authors
	 *
	 * @param   array   $authors List of authors
	 * @return  boolean
	 */
	public function updateAuthors($authors)
	{
		return $this->_tbl->updateAuthors($authors);
	}

	/**
	 * Get the adapter
	 *
	 * @return  object
	 */
	protected function _adapter()
	{
		if (!($this->_adapter instanceof BaseAdapter))
		{
			$scope = 'site';
			if ($this->get('group_cn'))
			{
				$scope = strtolower(Request::getCmd('option'));
				$scope = ($scope ? substr($scope, 4) : 'site');
			}
			$cls = __NAMESPACE__ . '\\Adapters\\' . ucfirst($scope);

			if (!class_exists($cls))
			{
				$path = __DIR__ . '/adapters/' . $scope . '.php';
				if (!is_file($path))
				{
					throw new \InvalidArgumentException(Lang::txt('Invalid adapter type of "%s"', $scope));
				}
				include_once($path);
			}

			$this->_adapter = new $cls(
				$this->get('pagename'),
				$this->get('scope'),
				$this->get('group_cn')
			);
		}

		return $this->_adapter;
	}
}

