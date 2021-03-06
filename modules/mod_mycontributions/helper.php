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

namespace Modules\MyContributions;

use Hubzero\Module\Module;
use JFactory;
use User;

/**
 * Module class for displaying contributions in progress
 */
class Helper extends Module
{
	/**
	 * Get a list of contributions
	 *
	 * @return     array
	 */
	private function _getContributions()
	{
		$database = JFactory::getDBO();

		$query  = "SELECT DISTINCT R.id, R.title, R.type, R.logical_type AS logicaltype, R.created, R.created_by, R.published, R.publish_up, R.standalone, R.rating, R.times_rated, R.alias, R.ranking, rt.type AS typetitle ";
		$query .= "FROM `#__resource_types` AS rt, `#__resources` AS R ";
		$query .= "LEFT JOIN `#__author_assoc` AS aa ON aa.subid=R.id AND aa.subtable='resources' ";
		$query .= "WHERE (aa.authorid='" . User::get('id') . "' OR R.created_by=" . User::get('id') . ") ";
		$query .= "AND R.standalone=1 AND R.type=rt.id AND (R.published=2 OR R.published=3) AND R.type!=7 ";
		$query .= "ORDER BY published ASC, title ASC";

		$database->setQuery($query);

		return $database->loadObjectList();
	}

	/**
	 * Get a list of tools
	 *
	 * @param   integer  $show_questions  Show question count for tool
	 * @param   integer  $show_wishes     Show wish count for tool
	 * @param   integer  $show_tickets    Show ticket count for tool
	 * @param   string   $limit_tools     Number of records to pull
	 * @return  mixed    False if error, otherwise array
	 */
	private function _getToollist($show_questions, $show_wishes, $show_tickets, $limit_tools='40')
	{
		$database = JFactory::getDBO();

		// Query filters defaults
		$filters = array();
		$filters['sortby'] = 'f.published DESC';
		$filters['filterby'] = 'all';

		include_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'tables' . DS . 'tool.php');
		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_tools' . DS . 'models' . DS . 'tool.php');

		// Create a Tool object
		$rows = \ToolsModelTool::getTools($filters, false);
		$limit = 100000;

		if ($rows)
		{
			for ($i=0; $i < count($rows); $i++)
			{
				// What is resource id?
				$rid = \ToolsModelTool::getResourceId($rows[$i]->id);
				$rows[$i]->rid = $rid;

				// Get questions, wishes and tickets on published tools
				if ($rows[$i]->published == 1 && $i <= $limit_tools)
				{
					if ($show_questions)
					{
						// Get open questions
						require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'tables' . DS . 'question.php');
						require_once(PATH_CORE . DS . 'components' . DS . 'com_answers' . DS . 'tables' . DS . 'response.php');
						$aq = new \Components\Answers\Tables\Question($database);

						$filters = array(
							'limit'    => $limit,
							'start'    => 0,
							'filterby' => 'open',
							'sortby'   => 'date',
							'mine'     => 0,
							'tag'      => 'tool' . $rows[$i]->toolname
						);

						$results = $aq->getResults($filters);
						$unanswered = 0;
						if ($results)
						{
							foreach ($results as $r)
							{
								if ($r->rcount == 0)
								{
									$unanswered++;
								}
							}
						}

						$rows[$i]->q = count($results);
						$rows[$i]->q_new = $unanswered;
					}

					if ($show_wishes)
					{
						// Get open wishes
						require_once(PATH_CORE . DS . 'components' . DS . 'com_wishlist' . DS . 'site' . DS . 'controllers' . DS . 'wishlists.php');
						require_once(PATH_CORE . DS . 'components' . DS . 'com_wishlist' . DS . 'tables' . DS . 'wish.php');
						require_once(PATH_CORE . DS . 'components' . DS . 'com_wishlist' . DS . 'tables' . DS . 'wishlist.php');

						$objWishlist = new \Components\Wishlist\Tables\Wishlist($database);
						$objWish = new \Components\Wishlist\Tables\Wish($database);
						$listid = $objWishlist->get_wishlistID($rid, 'resource');

						$rows[$i]->w = 0;
						$rows[$i]->w_new = 0;

						if ($listid)
						{
							$controller = new \Components\Wishlist\Site\Controllers\Wishlists();
							$filters = $controller->getFilters(1);
							$wishes = $objWish->get_wishes($listid, $filters, 1, User::getRoot());
							$unranked = 0;
							if ($wishes)
							{
								foreach ($wishes as $w)
								{
									if ($w->ranked == 0)
									{
										$unranked++;
									}
								}
							}

							$rows[$i]->w = count($wishes);
							$rows[$i]->w_new = $unranked;
						}
					}

					if ($show_tickets)
					{
						// Get open tickets
						$group = $rows[$i]->devgroup;

						// Find support tickets on the user's contributions
						$database->setQuery(
							"SELECT id, summary, category, status, severity, owner, created, login, name,
							 (SELECT COUNT(*) FROM `#__support_comments` as sc WHERE sc.ticket=st.id AND sc.access=0) as comments
							 FROM `#__support_tickets` as st WHERE (st.status=0 OR st.status=1) AND type=0 AND st.group='$group'
							 ORDER BY created DESC
							 LIMIT $limit"
						);
						$tickets = $database->loadObjectList();
						if ($database->getErrorNum())
						{
							echo $database->stderr();
							return false;
						}
						$unassigned = 0;
						if ($tickets)
						{
							foreach ($tickets as $t)
							{
								if ($t->comments == 0 && $t->status == 0 && !$t->owner)
								{
									$unassigned++;
								}
							}
						}

						$rows[$i]->s = count($tickets);
						$rows[$i]->s_new = $unassigned;
					}
				}
			}
		}

		return $rows;
	}

	/**
	 * Get tool status text
	 *
	 * @param   integer  $int  Tool status
	 * @return  string
	 */
	public function getState($int)
	{
		switch ($int)
		{
			case 1: $state = 'registered'; break;
			case 2: $state = 'created';    break;
			case 3: $state = 'uploaded';   break;
			case 4: $state = 'installed';  break;
			case 5: $state = 'updated';    break;
			case 6: $state = 'approved';   break;
			case 7: $state = 'published';  break;
			case 8: $state = 'retired';    break;
			case 9: $state = 'abandoned';  break;
		}
		return $state;
	}

	/**
	 * Get resource type title
	 *
	 * @param   integer  $int  Resource type
	 * @return  string
	 */
	public function getType($int)
	{
		switch ($int)
		{
			case 1:  $type = 'Online Presentation'; break;  // online presentations
			case 3:  $type = 'Publication';         break;  // publications
			case 5:  $type = 'Animation';           break;  // animations
			case 9:  $type = 'Download';            break;  // downloads
			case 39: $type = 'Teaching Material';   break;  // teaching materials
			default: $type = 'Other';               break;
		}
		return $type;
	}

	/**
	 * Display module contents
	 *
	 * @return     void
	 */
	public function display()
	{
		// Get the user's profile
		$xprofile = \Hubzero\User\Profile::getInstance(User::get('id'));

		$administrator = in_array('middleware', $xprofile->get('admin'));

		// show tool contributions separately?
		$this->show_tools = intval($this->params->get('show_tools', 1));

		// get questions on resources?
		$this->show_questions = intval($this->params->get('get_questions', 1));

		// get wishes on resources?
		$this->show_wishes = intval($this->params->get('get_wishes', 1));

		// get tickets on resources?
		$this->show_tickets = intval($this->params->get('get_tickets', 1));

		// how many tools to display?
		$this->limit_tools = intval($this->params->get('limit_tools', 10));

		// how many tools to display?
		$this->limit_other = intval($this->params->get('limit_other', 5));

		// Push the module CSS to the template
		$this->css();

		// Tools in progress
		$this->tools = ($this->show_tools) ? $this->_getToollist($this->show_questions, $this->show_wishes, $this->show_tickets, $this->limit_tools) : array();

		// Other cotnributions
		$this->contributions = $this->_getContributions();

		require $this->getLayoutPath();
	}
}

