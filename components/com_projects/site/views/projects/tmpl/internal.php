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
$html  = '';

$this->css()
    ->js()
	->css('jquery.fancybox.css', 'system');

$counts = $this->model->get('counts');
$new = isset($counts['new']) && $counts['new'] > 0 ? $counts['new'] : 0;

// Add new activity count to page title
$document = JFactory::getDocument();
$title = $new && $this->active == 'feed'
	? $this->title . ' (' . $new . ')'
	: $this->title;
$document->setTitle( $title );

// Get project params
$params = $this->model->params;

// Get layout from project params or component
$layout = $params->get('layout', $this->config->get('layout', 'standard'));
$theme = $params->get('theme', $this->config->get('theme', 'light'));

if ($layout == 'extended')
{
	// Include extended CSS
	$this->css('extended.css');

	// Include theme CSS
	$this->css('theme' . $theme . '.css');
}
else
{
	$this->css('standard.css');
}

// Get notifications
$notification = Event::trigger('projects.onProjectNotification',
	array( $this->model, $this->active )
);
$notification 	= $notification && !empty($notification)
	? $notification[0] : NULL;

// Get side content
$sideContent = Event::trigger('projects.onProjectExtras',
	array( $this->model, $this->active )
);
$sideContent 	= $sideContent && !empty($sideContent)
	? $sideContent[0] : NULL;

?>
<div id="project-wrap" class="theme">
	<?php if ($layout == 'extended') {
		// Draw top header
		$this->view('_topheader')
		     ->set('model', $this->model)
		     ->set('publicView', false)
		     ->set('option', $this->option)
		     ->display();
		// Draw top menu
		$this->view('_topmenu', 'projects')
		     ->set('model', $this->model)
		     ->set('active', $this->active)
		     ->set('tabs', $this->tabs)
		     ->set('option', $this->option)
		     ->set('guest', User::isGuest())
		     ->set('publicView', false)
		     ->display();
	?>
	<div class="project-inner-wrap">
	<?php
	} else { ?>
	<div id="project-innerwrap">
		<div class="main-menu">
			<?php
			// Draw image
			$this->view('_image', 'projects')
			     ->set('model', $this->model)
			     ->set('option', $this->option)
			     ->display();

			// Draw left menu
			$this->view('_menu', 'projects')
			     ->set('model', $this->model)
			     ->set('active', $this->active)
			     ->set('tabs', $this->tabs)
			     ->set('option', $this->option)
			     ->display();
			?>
		</div><!-- / .main-menu -->
		<div class="main-content">
	<?php
		// Draw traditional header
		$this->view('_header')
		     ->set('model', $this->model)
		     ->set('showPic', 0)
		     ->set('showPrivacy', 2)
		     ->set('goBack', 0)
		     ->set('showUnderline', 1)
		     ->set('option', $this->option)
		     ->display();

		// Member options
		$this->view('_options', 'projects')
		     ->set('model', $this->model)
		     ->set('option', $this->option)
		     ->display();
	} ?>
			<?php
				// Display status message
				$this->view('_statusmsg', 'projects')
				     ->set('error', $this->getError())
				     ->set('msg', $this->msg)
				     ->display();
			?>
			<div id="plg-content" class="content-<?php echo $this->active; ?>">
			<?php if ($notification) { echo $notification; } ?>
			<?php if ($sideContent) { ?>
			<div class="grid">
				<div class="col span9 main-col">
					<?php } ?>
					<?php if ($this->content) { echo $this->content; } ?>
					<?php if ($this->active == 'info') {
							// Display project info
							$this->view('_info')
							     ->set('model', $this->model)
							     ->set('option', $this->option)
							     ->display();
					 } ?>
					<?php if ($sideContent) { ?>
				</div>
				<div class="col span3 omega side-col">
					<div class="side-content">
					<?php echo $sideContent; ?>
					</div>
				</div>
			</div> <!-- / .grid -->
			<?php } ?>
				<div class="clear"></div>
			</div><!-- / plg-content -->
		<?php if ($layout != 'extended') { ?>
		</div><!-- / .main-content -->
		<?php } ?>
	</div>
</div>