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
 * @author    Nicholas J. Kisseberth <nkissebe@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$juri = JURI::getInstance();
$sef = Route::url('index.php?option='.$this->option.'&task=details&id='.$this->row->id);
if (substr($sef,0,1) == '/') {
	$sef = substr($sef,1,strlen($sef));
}

$message  = Lang::txt('EVENTS_CAL_LANG_ACT_MODIFIED_BY', $this->juser->get('name'), $this->juser->get('username'));
$message .= ''."\n";
$message .= ''."\n";
$message .= Lang::txt('EVENTS_CAL_LANG_EVENT_TITLE').': '.stripslashes($this->row->title)."\n\n";
$message .= Lang::txt('EVENTS_CAL_LANG_EVENT_DESCRIPTION').': '.stripslashes($this->row->content)."\n\n";
$message .= ''."\n";
$message .= $juri->base().$sef."\n";

echo $message;
