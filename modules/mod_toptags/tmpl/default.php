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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$tags = $this->tags;

$exclude = explode(',', $this->params->get('exclude', ''));
$exclude = array_map('trim', $exclude);

$tl = array();
if (count($tags) > 0)
{
	$html  = '<ol class="tags">' . "\n";
	foreach ($tags as $tag)
	{
		if (!in_array($tag->raw_tag, $exclude))
		{
			$tl[$tag->tag] = "\t" . '<li><a href="' . Route::url('index.php?option=com_tags&tag=' . $this->escape($tag->tag)) . '">' . $this->escape($tag->raw_tag) . '</a></li>';
		}
	}
	if ($this->params->get('sortby') == 'alphabeta')
	{
		ksort($tl);
	}
	$html .= implode("\n", $tl);
	$html .= '</ol>' . "\n";
	if ($this->params->get('morelnk'))
	{
		$html .= '<p class="more"><a href="' . Route::url('index.php?option=com_tags') . '">' . Lang::txt('MOD_TOPTAGS_MORE') . '</a></p>' . "\n";
	}
}
else
{
	$html  = '<p>' . $this->params->get('message', 'No tags found.') . '</p>' . "\n";
}
echo $html;