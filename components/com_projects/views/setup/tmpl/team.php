<?php
/**
 * @package		HUBzero CMS
 * @author		Alissa Nedossekina <alisa@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$this->css()
	->js()
	->js('setup')
	->css('jquery.fancybox.css', 'system');

// Display page title
$this->view('_title')
     ->set('project', $this->project)
     ->set('step', $this->step)
     ->set('gid', $this->gid)
     ->set('group', $this->group)
     ->set('option', $this->option)
     ->set('title', $this->title)
     ->display();

?>

<section class="main section" id="setup">
	<?php
		// Display status message
		$this->view('_statusmsg', 'projects')
		     ->set('error', $this->getError())
		     ->set('msg', $this->msg)
		     ->display();
	?>
	<?php
		// Display metadata
		$this->view('_metadata')
		     ->set('project', $this->project)
		     ->set('step', $this->step)
		     ->set('option', $this->option)
		     ->display();
	?>
	<?php
	// Display steps
	$this->view('_steps')
	     ->set('project', $this->project)
	     ->set('step', $this->step)
	     ->display();
	?>
	<div class="clear"></div>
	<div class="setup-wrap">
		<form id="hubForm" method="post" action="index.php">
			<div class="explaination">
				<h4><?php echo JText::_('COM_PROJECTS_HOWTO_TITLE_ROLES'); ?></h4>
				<p><span class="italic prominent"><?php echo ucfirst(JText::_('COM_PROJECTS_LABEL_COLLABORATORS')); ?></span> <?php echo JText::_('COM_PROJECTS_CAN'); ?>:</p>
				<ul>
					<li><?php echo JText::_('COM_PROJECTS_HOWTO_ROLES_COLLABORATOR_CAN_ONE'); ?></li>
					<li><?php echo JText::_('COM_PROJECTS_HOWTO_ROLES_COLLABORATOR_CAN_TWO'); ?></li>
					<li><?php echo JText::_('COM_PROJECTS_HOWTO_ROLES_COLLABORATOR_CAN_THREE'); ?></li>
				</ul>
				<p><span class="italic prominent"><?php echo ucfirst(JText::_('COM_PROJECTS_LABEL_OWNERS')); ?></span> <?php echo JText::_('COM_PROJECTS_CAN'); ?>:</p>
				<ul>
					<li><?php echo JText::_('COM_PROJECTS_HOWTO_ROLES_MANAGER_CAN_ONE'); ?></li>
					<li><?php echo JText::_('COM_PROJECTS_HOWTO_ROLES_MANAGER_CAN_TWO'); ?></li>
					<li><strong><?php echo JText::_('COM_PROJECTS_HOWTO_ROLES_MANAGER_CAN_THREE'); ?></strong></li>
				</ul>
				<?php if ($this->gid) { ?>
					<h4><?php echo JText::_('COM_PROJECTS_HOWTO_GROUP_PROJECT'); ?></h4>
					<p><?php echo JText::_('COM_PROJECTS_HOWTO_GROUP_EXPLAIN'); ?></p>
				<?php } ?>
			</div>
			<fieldset>
				<?php 
				// Display form fields
				$this->view('_form')
				     ->set('project', $this->project)
				     ->set('step', $this->step)
				     ->set('gid', $this->gid)
				     ->set('option', $this->option)
				     ->set('controller', 'setup')
				     ->set('section', $this->section)
				     ->display();
				?>
				<legend><?php echo JText::_('COM_PROJECTS_ADD_TEAM'); ?></legend>
				<div id="cbody">
					<?php echo $this->content; ?>
				</div>
			</fieldset>
			<div class="clear"></div>
			<div class="submitarea">
				<input type="submit" value="<?php echo JText::_('COM_PROJECTS_SAVE_AND_CONTINUE'); ?>" class="btn btn-success" id="gonext" />
			</div>
		</form>
	</div>
</section>