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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$this->css();
?>
<header id="content-header">
	<h2><?php echo $this->title; ?></h2>

	<?php if ($this->authorized) { ?>
	<div id="content-header-extra">
		<ul id="useroptions">
			<li class="last"><a class="icon-add add btn" href="<?php echo Route::url('index.php?option='.$this->option.'&task=add'); ?>"><?php echo Lang::txt('EVENTS_ADD_EVENT'); ?></a></li>
		</ul>
	</div><!-- / #content-header-extra -->
	<?php } ?>
</header><!-- / #content-header -->

<nav>
	<ul class="sub-menu">
		<li<?php if ($this->task == 'year') { echo ' class="active"'; } ?>><a href="<?php echo Route::url('index.php?option='.$this->option.'&year='.$this->year); ?>"><span><?php echo Lang::txt('EVENTS_CAL_LANG_REP_YEAR'); ?></span></a></li>
		<li<?php if ($this->task == 'month') { echo ' class="active"'; } ?>><a href="<?php echo Route::url('index.php?option='.$this->option.'&year='.$this->year.'&month='.$this->month); ?>"><span><?php echo Lang::txt('EVENTS_CAL_LANG_REP_MONTH'); ?></span></a></li>
		<li<?php if ($this->task == 'week') { echo ' class="active"'; } ?>><a href="<?php echo Route::url('index.php?option='.$this->option.'&year='.$this->year.'&month='.$this->month.'&day='.$this->day.'&task=week'); ?>"><span><?php echo Lang::txt('EVENTS_CAL_LANG_REP_WEEK'); ?></span></a></li>
		<li<?php if ($this->task == 'day') { echo ' class="active"'; } ?>><a href="<?php echo Route::url('index.php?option='.$this->option.'&year='.$this->year.'&month='.$this->month.'&day='.$this->day); ?>"><span><?php echo Lang::txt('EVENTS_CAL_LANG_REP_DAY'); ?></span></a></li>
	</ul>
</nav>

<section class="main section">
	<div class="subject">
	<?php if (count($this->rows) > 0) { ?>
		<ul class="events">
			<li>
				<dl class="event-details">
					<dt><?php echo JHTML::_('date', $this->year.'-'.$this->month.'-'.$this->day.' 00:00:00', Lang::txt('DATE_FORMAT_HZ1'), 'UTC'); ?></dt>
				</dl>
				<div class="ewrap">
					<ul class="events">
					<?php
						foreach ($this->rows as $row)
						{
							$this->view('item')
							     ->set('option', $this->option)
							     ->set('task', $this->task)
							     ->set('row', $row)
							     ->set('fields', $this->fields)
							     ->set('categories', $this->categories)
							     ->set('showdate', 0)
							     ->display();
						}
					?>
					</ul>
				</div>
			</li>
		</ul>
	<?php } else { ?>
		<p class="warning"><?php echo Lang::txt('EVENTS_CAL_LANG_NO_EVENTFOR').' <strong>'.\Components\Events\Helpers\Html::getDateFormat($this->year,$this->month,$this->day,0).'</strong>'; ?></p>
	<?php } ?>
	</div><!-- / .subject -->
	<div class="aside">
		<form action="<?php echo Route::url('index.php?option='.$this->option.'&year='.$this->year.'&month='.$this->month.'&day='.$this->day); ?>" method="get" id="event-categories">
			<fieldset>
				<select name="category">
					<option value=""><?php echo Lang::txt('EVENTS_ALL_CATEGORIES'); ?></option>
				<?php
				if ($this->categories)
				{
					foreach ($this->categories as $id=>$title)
					{
					?>
						<option value="<?php echo $id; ?>"<?php if ($this->category == $id) { echo ' selected="selected"'; } ?>><?php echo stripslashes($title); ?></option>
					<?php
					}
				}
				?>
				</select>
				<input type="submit" value="<?php echo Lang::txt('EVENTS_GO'); ?>" />
			</fieldset>
		</form>

		<div class="calendarwrap">
			<p class="datenav">
				<?php
				$this_date = new \Components\Events\Helpers\EventsDate();
				$this_date->setDate( $this->year, $this->month, 0 );

				$prev_year = clone($this_date);
				$prev_year->addMonths( -12 );
				$next_year = clone($this_date);
				$next_year->addMonths( +12 );
				?>
				<a class="prv" href="<?php echo Route::url('index.php?option='.$this->option.'&'.$prev_year->toDateURL($this->task)); ?>" title="<?php echo Lang::txt('EVENTS_CAL_LANG_PREVIOUSYEAR'); ?>">&lsaquo;</a>
				<a class="nxt" href="<?php echo Route::url('index.php?option='.$this->option.'&'.$next_year->toDateURL($this->task)); ?>" title="<?php echo Lang::txt('EVENTS_CAL_LANG_NEXTYEAR'); ?>">&rsaquo;</a>
				<?php echo $this->year; ?>
			</p>
		</div><!-- / .calendarwrap -->

		<div class="calendarwrap">
			<?php
			$this->view('calendar')
			     ->set('option', $this->option)
			     ->set('task', $this->task)
			     ->set('year', $this->year)
			     ->set('month', $this->month)
			     ->set('day', $this->day)
			     ->set('offset', $this->offset)
			     ->set('shownav', 1)
			     ->display();
			?>
		</div><!-- / .calendarwrap -->
	</div><!-- / .aside -->
</section><!-- / .main section -->
