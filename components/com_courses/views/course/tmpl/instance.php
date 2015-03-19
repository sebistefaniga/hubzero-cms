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

//get objects
$config 	=& JFactory::getConfig();
$database 	=& JFactory::getDBO();

//is membership control managed on course?
$membership_control = $this->gparams->get('membership_control', 1);

//get no_html request var
$no_html = JRequest::getInt( 'no_html', 0 );
?>

<?php if (!$no_html) : ?>
	<div class="innerwrap">
		<div id="page_container">
			<div id="page_sidebar">
				<?php
					//default logo
					//$default_logo = DS.'components'.DS.$this->option.DS.'assets'.DS.'img'.DS.'course_default_logo.png';

					//logo link - links to course overview page
					$link = JRoute::_('index.php?option='.$this->option.'&gid='.$this->course->get('cn'));

					//path to course uploaded logo
					//$path = '/site/courses/'.$this->course->get('gidNumber').DS.$this->course->get('logo');

					//if logo exists and file is uploaded use that logo instead of default
					//$src = ($this->course->get('logo') != '' && is_file(JPATH_ROOT.$path)) ? $path : $default_logo;
				?>
				<div id="page_identity">
					<a href="<?php echo $link; ?>" title="<?php echo $this->course->get('description'); ?> Home">
						<?php echo $this->course->get('description'); ?> Home
					</a>
				</div><!-- /#page_identity -->
				
				<ul id="course_options">
					<?php if(in_array($this->user->get("id"), $this->course->get("invitees"))) : ?>
						<?php if($membership_control == 1) : ?>
							<li>
								<a class="course-invited" href="/courses/<?php echo $this->course->get("cn"); ?>/accept">Accept Course Invitation</a>
							</li>
						<?php endif; ?>
					<?php elseif($this->course->get('join_policy') == 3 && !in_array($this->user->get("id"), $this->course->get("members"))) : ?>
						<li><span class="course-closed">Course Closed</span></li>
					<?php elseif($this->course->get('join_policy') == 2 && !in_array($this->user->get("id"), $this->course->get("members"))) : ?>
						<li><span class="course-inviteonly">Course is Invite Only</span></li>
					<?php elseif($this->course->get('join_policy') == 0 && !in_array($this->user->get("id"), $this->course->get("members"))) : ?>
						<?php if($membership_control == 1) : ?> 
							<li>
								<a class="course-join" href="/courses/<?php echo $this->course->get("cn"); ?>/join">Join Course</a>
							</li>
						<?php endif; ?> 
					<?php elseif($this->course->get('join_policy') == 1 && !in_array($this->user->get("id"), $this->course->get("members"))) : ?>
						<?php if($membership_control == 1) : ?>
							<?php if(in_array($this->user->get("id"), $this->course->get("applicants"))) : ?>
								<li><span class="course-pending">Request Waiting Approval</span></li>
							<?php else : ?>
								<li>
									<a class="course-request" href="/courses/<?php echo $this->course->get("cn"); ?>/join">Request Course Membership</a>
								</li>
							<?php endif; ?>
						<?php endif; ?>
					<?php else : ?>
						<?php $isManager = (in_array($this->user->get("id"), $this->course->get("managers"))) ? true : false; ?>
						<?php $canCancel = (($isManager && count($this->course->get("managers")) > 1) || (!$isManager && in_array($this->user->get("id"), $this->course->get("members")))) ? true : false; ?>
						<li class="no-float">
							<a href="javascript:void(0);" class="dropdown course-<?php echo ($isManager) ? "manager" : "member" ?>">
								Course <?php echo ($isManager) ? "Manager" : "Member" ?>
								<span class="caret"></span>
							</a>
							<ul class="dropdown-menu pull-right">
								<?php if($isManager) : ?>
									<?php if($membership_control == 1) : ?> 
										<li><a class="course-invite" href="/courses/<?php echo $this->course->get("cn"); ?>/invite">Invite Members</a></li>
									<?php endif; ?>
									<li><a class="course-edit" href="/courses/<?php echo $this->course->get("cn"); ?>/edit">Edit Course Settings</a></li>
									<li><a class="course-customize" href="/courses/<?php echo $this->course->get("cn"); ?>/customize">Customize Course</a></li>
									<li><a class="course-outline" href="/courses/<?php echo $this->course->get("cn"); ?>/editoutline">Edit Outline</a></li>
									<li><a class="course-pages" href="/courses/<?php echo $this->course->get("cn"); ?>/managepages">Manage Course Pages</a></li>
									<?php if($membership_control == 1) : ?> 
										<li class="divider"></li>
									<?php endif; ?>
								<?php endif; ?>
								<?php if($canCancel) : ?>
									<?php if($membership_control == 1) : ?> 
										<li><a class="course-cancel" href="/courses/<?php echo $this->course->get("cn"); ?>/cancel">Cancel Course Membership</a></li>
										<?php if($isManager): ?>
											<li class="divider"></li>
										<?php endif; ?>
									<?php endif; ?>
								<?php endif; ?>
								<?php if($isManager) : ?>
									<?php if($membership_control == 1) : ?> 
										<li><a class="course-delete" href="/courses/<?php echo $this->course->get("cn"); ?>/delete">Delete Course</a></li>
									<?php endif; ?>
								<?php endif; ?>
							</ul>
						</li>
					<?php endif; ?>
				</ul><!-- /#page_options -->
				
				<ul id="page_menu">
					<?php
						echo Hubzero_Course_Helper::displayCourseMenu($this->course, $this->sections, $this->hub_course_plugins, $this->course_plugin_access, $this->pages, $this->tab);
					?>
				</ul><!-- /#page_menu -->
				
				<div id="page_info">
					<?php 
						$dateFormat = '%d %b, %Y';
						$timeFormat = '%I:%M %p';
						$tz = 0;
						if (version_compare(JVERSION, '1.6', 'ge'))
						{
							$dateFormat = 'd M, Y';
							$timeFormat = 'h:m a';
							$tz = true;
						}
					?>
					<div class="course-info">
						<ul>
							<li class="info-join-policy">
								<span class="label">Starts</span>
								<span class="value"><?php echo JHTML::_('date', $this->instance->start_date, $dateFormat, $tz); ?></span>
							</li>
							<li class="info-created">
								<span class="label">Ends</span>
								<span class="value"><?php echo JHTML::_('date', $this->instance->end_date, $dateFormat, $tz); ?></span>
							</li>
						</ul>
					</div>
				</div>
			</div><!-- /#page_sidebar --> 
			
			<div id="page_main">
				<?php if($this->course->get('type') == 3) : ?>
					<a href="/home" id="special-course-tab" class="" title="<?php echo $config->getValue("sitename"); ?> :: Learn more about this course page and access to more <?php echo $config->getValue("sitename"); ?> content.">
						<?php echo $config->getValue("sitename"); ?>
						<span></span>
					</a>
				<?php endif; ?>
				
				<div id="page_header">
					<h2><a href="/courses/<?php echo $this->course->get("cn"); ?>"><?php echo $this->course->get('description'); ?></a></h2>
					<span class="divider">►</span>
					<h3>
						<?php
							foreach($this->hub_course_plugins as $cat)
							{
								if($this->tab == $cat['name'])
								{
									echo $cat['title'];
								}
							}
						?>
					</h3>
					
					<?php
						if($this->tab == 'overview') : 
							$gt = new CoursesTags( $database );
							echo $gt->get_tag_cloud(0,0,$this->course->get('gidNumber'));
						endif;
					?>
				</div><!-- /#page_header -->
				<div id="page_notifications">
					<?php
						foreach($this->notifications as $notification) {
							echo "<p class=\"{$notification['type']}\">{$notification['message']}</p>";
						}
					?>
				</div><!-- /#page_notifications -->
				
				<div id="page_content" class="course_<?php echo $this->tab; ?>">
					<?php endif; ?>

					<?php
						echo Hubzero_Course_Helper::displayCourseContent($this->sections, $this->hub_course_plugins, $this->tab);
					?>

					<?php if (!$no_html) : ?>
				</div><!-- /#page_content -->
			</div><!-- /#page_main -->
		</div><!-- /#page_container -->
	</div><!-- /.innerwrap -->
<?php endif; ?>