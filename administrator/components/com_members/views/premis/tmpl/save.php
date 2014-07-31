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
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

JToolBarHelper::title(JText::_('Member Registration') . ': ' . JText::_('PREMIS Data Import'), 'user.png');
JToolBarHelper::addNew();
JToolBarHelper::editList();
JToolBarHelper::deleteList();

?>

<?php if ($this->getError()) { ?>
	<p class="error"><?php echo implode('<br />', $this->getErrors()); ?></p>
<?php
	}
	else
	{
		echo '<p>Import complete</p>';

		echo '<p>Total records processed: ' . ($this->ok + $this->fail) . '<br>';
		echo 'Successfully processed: ' . $this->ok . '<br>';
		echo 'Errors processing: ' . $this->fail . '</p>';

		if ($this->fail)
		{
			echo '<h4>Error log:</h4>';
			echo '<p id="report">';
			foreach ($this->report as $line)
			{
				if ($line['status'] != 'ok')
				{
					echo 'Line ' . $line['line'] . ': ' .  $line['msg'] . '<br>';
				}
			}
			echo '</p>';
		}
	}
?>
