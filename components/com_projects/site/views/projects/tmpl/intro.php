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

$this->css('introduction.css', 'system')
     ->css()
     ->js();

$rows = $this->model->entries('list', $this->filters);

?>
<div id="content-header">
	<h2><?php echo $this->title; ?></h2>
</div><!-- / #content-header -->

<div id="content-header-extra">
    <ul id="useroptions">
    	<li><a class="btn icon-browse" href="<?php echo Route::url('index.php?option=' . $this->option . '&task=browse'); ?>"><?php echo Lang::txt('COM_PROJECTS_BROWSE_PUBLIC_PROJECTS'); ?></a></li>
	</ul>
</div><!-- / #content-header-extra -->

<div class="clear"></div>

<?php
	// Display status message
	$this->view('_statusmsg', 'projects')
	     ->set('error', $this->getError())
	     ->set('msg', $this->msg)
	     ->display();
?>

<div class="clear block">&nbsp;</div>

<section id="introduction" class="section">
 <div id="introbody">
	<div class="grid">
		<div class="col span5">
			<h3><?php echo Lang::txt('COM_PROJECTS_INTRO_COLLABORATION_MADE_EASY'); ?></h3>
			<p><?php echo Lang::txt('COM_PROJECTS_INTRO_COLLABORATION_HOW'); ?></p>
			<p><a href="<?php echo Route::url('index.php?option=' . $this->option . '&task=start'); ?>" id="projects-intro-start" class="btn icon-next"><?php echo Lang::txt('COM_PROJECTS_START_PROJECT'); ?></a></p>
		</div>
		<div class="col span4 omega">
			<h3><?php echo Lang::txt('COM_PROJECTS_INTRO_WHAT_YOU_GET'); ?></h3>
			<ul>
				<li><?php echo Lang::txt('COM_PROJECTS_INTRO_GET_REPOSITORY'); ?></li>
				<li><?php echo Lang::txt('COM_PROJECTS_INTRO_GET_WIKI'); ?></li>
				<li><?php echo Lang::txt('COM_PROJECTS_INTRO_GET_TODO'); ?></li>
				<li><?php echo Lang::txt('COM_PROJECTS_INTRO_GET_BLOG'); ?></li>
				<?php if ($this->publishing) { ?>
				<li><?php echo Lang::txt('COM_PROJECTS_INTRO_GET_PUBLISHING'); ?></li>
				<?php } ?>
			</ul>
			<p><a href="<?php echo Route::url('index.php?option=' . $this->option . '&task=features'); ?>" id="projects-intro-features" class="btn"><?php echo Lang::txt('COM_PROJECTS_LEARN_MORE'); ?></a></p>
		</div>
	</div>
 </div>
</section><!-- / #introduction.section -->

<div class="clear"></div>
<section class="section myprojects">
	<div class="grid">
	<div class="col span2">
		<h2><?php echo Lang::txt('COM_PROJECTS_MY_PROJECTS'); ?></h2>
	</div>
	<div class="col span10 omega">
		<?php
		if (count($rows) > 0) { ?>
			<ul class="flow">
				<?php foreach ($rows as $row)
				{
					$setup = ($row->inSetup()) ? Lang::txt('COM_PROJECTS_COMPLETE_SETUP') : '';
				?>
				<li <?php if ($setup) { echo 'class="s-dev"'; } else if ($row->get('state') == 0) { echo 'class="s-inactive"'; } else if ($row->get('state') == 5) { echo 'class="s-pending"'; } ?>>
				<?php  if (!$setup && !$row->isPublic()) { ?><span class="s-private">&nbsp;</span><?php }  ?>
					<a href="<?php echo Route::url('index.php?option=' . $this->option . '&task=view&alias=' . $row->get('alias')); ?>"><img src="<?php echo Route::url('index.php?option=' . $this->option . '&alias=' . $row->get('alias') . '&task=media'); ?>" alt="" /><span class="block"><?php echo \Hubzero\Utility\String::truncate($this->escape($row->get('title')), 30); ?></span></a><?php if ($setup) { ?><span class="s-complete"><?php echo Lang::txt('COM_PROJECTS_COMPLETE_SETUP'); ?></span><?php } else if ($row->get('state') == 0) { ?><span class="s-suspended"><?php echo Lang::txt('COM_PROJECTS_STATUS_INACTIVE'); ?></span> <?php } else if ($row->get('state') == 5) { ?><span class="s-suspended"><?php echo Lang::txt('COM_PROJECTS_STATUS_PENDING'); ?></span> <?php } ?>
				<?php if ($row->get('newactivity') && $row->isActive() && !$setup) { ?><span class="s-new"><?php echo $row->get('newactivity'); ?></span><?php } ?>
				</li>
				<?php }	?>
			</ul>
		<?php } else { ?>
			<div class="noresults"><?php echo (User::isGuest()) ? Lang::txt('COM_PROJECTS_PLEASE').' <a href="'.Route::url('index.php?option=' . $this->option . '&task=intro&action=login') . '" id="projects-intro-login">'.Lang::txt('COM_PROJECTS_LOGIN').'</a> '.Lang::txt('COM_PROJECTS_TO_VIEW_YOUR_PROJECTS') : Lang::txt('COM_PROJECTS_YOU_DONT_HAVE_PROJECTS'); ?></div>
		<?php }	?>
	</div>
</div>
</section>
<div class="clear"></div>

