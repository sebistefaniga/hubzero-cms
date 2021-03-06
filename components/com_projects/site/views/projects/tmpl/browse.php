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
	->js();

if (in_array($this->filters['reviewer'], array('sponsored', 'sensitive')))
{
	$this->css('reviewers')
		->css('jquery.fancybox.css', 'system');
}

$sortbyDir = $this->filters['sortdir'] == 'ASC' ? 'DESC' : 'ASC';

// Get count
$total = $this->model->entries('count', $this->filters);

?>
<header id="content-header">
	<h2><?php echo $this->title; ?></h2>

	<div id="content-header-extra">
		<ul id="useroptions">
			<li><a class="btn icon-add" href="<?php echo Route::url('index.php?option=' . $this->option . '&task=start'); ?>"><?php echo Lang::txt('COM_PROJECTS_START_NEW'); ?></a></li>
		</ul>
	</div><!-- / #content-header-extra -->
</header><!-- / #content-header -->

<form method="get" id="browseForm" action="<?php echo Route::url('index.php?option=' . $this->option . '&task=browse'); ?>">
	<section class="main section">
		<div class="container data-entry">
			<input class="entry-search-submit" type="submit" value="<?php echo Lang::txt('Search'); ?>" />
			<fieldset class="entry-search">
				<legend></legend>
				<label for="entry-search-field"><?php echo Lang::txt('Enter keyword or phrase'); ?></label>
				<input type="text" name="search" id="entry-search-field" value="<?php echo $this->escape($this->filters['search']); ?>" placeholder="<?php echo Lang::txt('Enter keyword or phrase'); ?>" />
				<input type="hidden" name="sortby" value="<?php echo $this->escape($this->filters['sortby']); ?>" />
			</fieldset>
		</div>
		<div class="container">
			<?php
			$qs  = ($this->filters['search'] ? '&search=' . $this->escape($this->filters['search']) : '');
			$qs .= ($this->filters['start'] ? '&limitstart=' . $this->escape($this->filters['start']) : '');
			$qs .= ($this->filters['limit'] ? '&limit=' . $this->escape($this->filters['limit']) : '');
			$qs .= '&sortdir=' . $sortbyDir;
			if ($this->filters['reviewer'])
			{
				$qs .= '&reviewer=' . $this->filters['reviewer'];
			}
			?>
			<ul class="entries-menu order-options">
				<li><a<?php echo ($this->filters['sortby'] == 'owner') ? ' class="active"' : ''; ?> href="<?php echo Route::url('index.php?option=' . $this->option . '&task=browse&sortby=owner' . $qs); ?>" title="<?php echo Lang::txt('COM_PROJECTS_SORT_BY') . ' ' . Lang::txt('COM_PROJECTS_OWNER'); ?>">&darr; <?php echo Lang::txt('COM_PROJECTS_OWNER'); ?></a></li>
				<li><a<?php echo ($this->filters['sortby'] == 'title') ? ' class="active"' : ''; ?> href="<?php echo Route::url('index.php?option=' . $this->option . '&task=browse&sortby=title' . $qs); ?>" title="<?php echo Lang::txt('COM_PROJECTS_SORT_BY') . ' ' . Lang::txt('COM_PROJECTS_TITLE'); ?>">&darr; <?php echo Lang::txt('COM_PROJECTS_TITLE'); ?></a></li>
			</ul>
		<?php if (in_array($this->filters['reviewer'], array('sponsored', 'sensitive'))) { ?>
			<ul class="entries-menu filter-options">
				<li><a class="filter-all<?php if ($this->filters['filterby'] == 'all') { echo ' active'; } ?>" href="<?php echo Route::url('index.php?option=' . $this->option . '&task=browse' . $qs . '&filterby=all&sortby=' . $this->filters['sortby']); ?>"><?php echo ucfirst(Lang::txt('COM_PROJECTS_FILTER_ALL')); ?></a></li>
				<li><a class="filter-pending<?php if ($this->filters['filterby'] == 'pending') { echo ' active'; } ?>" href="<?php echo Route::url('index.php?option=' . $this->option . '&task=browse' . $qs . '&filterby=pending&sortby=' . $this->filters['sortby']); ?>"><?php echo ucfirst(Lang::txt('COM_PROJECTS_FILTER_PENDING')); ?></a></li>
			</ul>
		<?php } ?>
			<div class="clearfix"></div>
			<div class="container-block">
			<?php
			if ($rows = $this->model->entries('list', $this->filters))
			{
				// Display List of items
				$this->view('_list')
				     ->set('rows', $rows)
				     ->set('filters', $this->filters)
				     ->set('model', $this->model)
				     ->set('option', $this->option)
				     ->display();

				// Pagination
				$pageNav = new \JPagination(
					$total,
					$this->filters['start'],
					$this->filters['limit']
				);
				$pageNav->setAdditionalUrlParam('sortby', $this->filters['sortby']);
				$pageNav->setAdditionalUrlParam('filterby', $this->filters['filterby']);
				$pageNav->setAdditionalUrlParam('reviewer', $this->filters['reviewer']);
				$pageNav->setAdditionalUrlParam('sortdir', $this->filters['sortdir']);

				$pagenavhtml = $pageNav->getListFooter();
				$pagenavhtml = str_replace('projects/?','projects/browse/?', $pagenavhtml);
				?>
				<fieldset>
					<?php echo $pagenavhtml; ?>
				</fieldset>
			<?php	echo '<div class="clear"></div>';
			} 
			else 
			{
				if (User::isGuest())
				{
					echo '<p class="noresults">' . Lang::txt('COM_PROJECTS_NO_PROJECTS_FOUND').' '.Lang::txt('COM_PROJECTS_PLEASE').' <a href="' . Route::url('index.php?option=' . $this->option . '&task=browse&action=login') .'">'.Lang::txt('COM_PROJECTS_LOGIN').'</a> '.Lang::txt('COM_PROJECTS_TO_VIEW_PRIVATE_PROJECTS') . '</p>';
				}
			else
			{
				if (in_array($this->filters['reviewer'], array('sponsored', 'sensitive')))
				{
					$txt = $this->filters['filterby'] == 'pending'
					? Lang::txt('COM_PROJECTS_NO_REVIEWER_PROJECTS_FOUND_PENDING')
					: Lang::txt('COM_PROJECTS_NO_REVIEWER_PROJECTS_FOUND_ALL');
					echo '<p class="noresults">' . $txt . '</p>';
				}
				else
				{
					echo '<p class="noresults">' . Lang::txt('COM_PROJECTS_NO_AUTHOROZED_PROJECTS_FOUND') . '</p>';
				}
			}
			} ?>
			</div>
		</div>
	</section><!-- / .main section -->
</form>
