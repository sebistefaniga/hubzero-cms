<?php
/**
 * @package     hubzero-cms
 * @author      Shawn Rice <zooley@purdue.edu>
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
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
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$no_html = Request::getInt('no_html', 0);
$user_messaging = $this->config->get('user_messaging', 0);

$prefix = $this->profile->get("name") . "'s";
$edit = false;
$password = false;
$messaging = false;

$tab = $this->tab;
$tab_name = "Dashboard";

//are we allowed to messagin user
switch ($user_messaging)
{
	case 0:
		$mssaging = false;
		break;
	case 1:
		$common = \Hubzero\User\Helper::getCommonGroups(User::get("id"), $this->profile->get('uidNumber') );
		if (count($common) > 0)
		{
			$messaging = true;
		}
		break;
	case 2:
		$messaging = true;
		break;
}

//if user is this member turn on editing and password change, turn off messaging
if ($this->profile->get('uidNumber') == User::get("id"))
{
	if ($this->tab == "profile")
	{
		$edit = true;
		$password = true;
	}
	$messaging = false;
	$prefix = "My";
}

//no messaging if guest
if (User::isGuest())
{
	$messaging = false;
}

if (!$no_html)
{
	$this->css()
	     ->js();
?>
<header id="content-header" class="content-header">
	<div id="page_header">
		<?php if ($this->profile->get('uidNumber') == User::get("id")) : ?>
			<?php
				$cls = '';
				$span_title = "Public Profile :: Your profile is currently public.";
				$title = "Public Profile :: Click here to set your profile private.";
				if ($this->profile->get('public') != 1)
				{
					$cls = "private";
					$span_title = "Private Profile :: Your profile is currently private.";
					$title = "Private Profile :: Click here to set your profile public.";
				}
			?>

			<?php if ($this->tab == 'profile') : ?>
				<a id="profile-privacy" href="<?php echo Route::url($this->profile->getLink()); ?>" data-uidnumber="<?php echo $this->profile->get('uidNumber'); ?>" class="<?php echo $cls; ?> tooltips" title="<?php echo $title; ?>">
					<?php echo $title; ?>
				</a>
			<?php else: ?>
				<span id="profile-privacy">
					<?php echo $span_title; ?>
				</span>
			<?php endif; ?>
		<?php endif; ?>
		<h2>
			<a href="<?php echo Route::url($this->profile->getLink()); ?>">
				<?php echo $this->escape(stripslashes($this->profile->get('name'))); ?>
			</a>
		</h2>
		<span>&rsaquo;</span>
		<h3><?php echo $tab_name; ?></h3>
	</div>
</header>

<div class="innerwrap">
	<div id="page_container">
		<div id="page_sidebar">
			<?php
				$src = \Hubzero\User\Profile\Helper::getMemberPhoto($this->profile, 0, false);
				$link = Route::url($this->profile->getLink());
			?>
			<div id="page_identity">
				<?php $title = ($this->profile->get('uidNumber') == User::get("id")) ? "Go to my Dashboard" : "Go to " . $this->profile->get('name') . "'s Profile"; ?>
				<a href="<?php echo $link; ?>" id="page_identity_link" title="<?php echo $title; ?>">
					<img src="<?php echo $src; ?>" alt="<?php echo Lang::txt('The profile picture for %s', $this->escape(stripslashes($this->profile->get('name')))); ?>" />
				</a>
			</div><!-- /#page_identity -->
			<?php if ($messaging): ?>
			<ul id="member_options">
				<li>
					<a class="message tooltips" title="Message :: Send a message to <?php echo $this->escape(stripslashes($this->profile->get('name'))); ?>" href="<?php echo Route::url('index.php?option=com_members&id=' . User::get("id") . '&active=messages&task=new&to[]=' . $this->profile->get('uidNumber')); ?>">
						<?php echo Lang::txt('Message'); ?>
					</a>
				</li>
			</ul>
			<?php endif; ?>
			<ul id="page_menu">
				<?php foreach ($this->cats as $k => $c) : ?>
					<?php
						$key = key($c);
						if (!$key)
						{
							continue;
						}
						$name = $c[$key];
						$url = Route::url($this->profile->getLink() . '&active=' . $key);
						$cls = ($this->tab == $key) ? 'active' : '';
						$tab_name = ($this->tab == $key) ? $name : $tab_name;

						$metadata = $this->sections[$k]['metadata'];
						$meta_count = (isset($metadata['count']) && $metadata['count'] != "") ? $metadata['count'] : "";
						if (isset($metadata['alert']) && $metadata['alert'] != "")
						{
							$meta_alert = $metadata['alert'];
							$cls .= ' with-alert';
						}
						else
						{
							$meta_alert = '';
						}

						if (!isset($c['icon']))
						{
							$c['icon'] = 'f009';
						}
					?>
					<li class="<?php echo $cls; ?>">
						<a class="<?php echo $key; ?>" data-icon="<?php echo '&#x' . $c['icon']; ?>" title="<?php echo $prefix." ".$name; ?>" href="<?php echo $url; ?>">
							<?php echo $name; ?>
						</a>
						<span class="meta">
							<?php if ($meta_count) : ?>
								<span class="count"><?php echo $meta_count; ?></span>
							<?php endif; ?>
						</span>
						<?php echo $meta_alert; ?>
					</li>
				<?php endforeach; ?>
			</ul><!-- /#page_menu -->

			<?php
				$thumb = '/site/stats/contributor_impact/impact_' . $this->profile->get('uidNumber') . '_th.gif';
				$full = '/site/stats/contributor_impact/impact_' . $this->profile->get('uidNumber') . '.gif';
			?>
			<?php if (file_exists(PATH_APP . $thumb)) : ?>
				<a id="member-stats-graph" rel="lightbox" title="<?php echo $this->profile->get("name") . "'s Impact Graph"; ?>" data-name="<?php echo $this->profile->get("name"); ?>" data-type="Impact Graph" href="<?php echo $full; ?>">
					<img src="<?php echo $thumb; ?>" alt="<?php echo $this->profile->get("name") . "'s Impact Graph"; ?>" />
				</a>
			<?php endif; ?>

		</div><!-- /#page_sidebar -->
		<div id="page_main">
		<?php if ($edit || $password) : ?>
			<ul id="page_options">
				<?php if ($edit) : ?>
					<li>
						<a class="edit tooltips" id="edit-profile" title="Edit Profile :: Edit <?php if ($this->profile->get('uidNumber') == User::get("id")) { echo "my"; } else { echo $this->profile->get("name") . "'s"; } ?> profile." href="<?php echo Route::url($this->profile->getLink() . '&task=edit'); ?>">
							<?php echo Lang::txt('Edit profile'); ?>
						</a>
					</li>
				<?php endif; ?>
				<?php if ($password) : ?>
					<li>
						<a class="password tooltips" id="change-password" title="Change Password :: Change your password" href="<?php echo Route::url($this->profile->getLink() . '&task=changepassword'); ?>">
							<?php echo Lang::txt('Change Password'); ?>
						</a>
					</li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>
			<div id="page_notifications">
				<?php
					if ($this->getError())
					{
						echo '<p class="error">' . implode('<br />', $this->getErrors()) . '</p>';
					}
				?>
			</div>
			<div id="page_content" class="member_<?php echo $this->tab; ?>">
				<?php
					}
					if ($this->overwrite_content)
					{
						echo $this->overwrite_content;
					}
					else
					{
						foreach ($this->sections as $s)
						{
							if ($s['html'] != '')
							{
								echo $s['html'];
							}
						}
					}
					if (!$no_html) {
				?>
			</div><!-- /#page_content -->
		</div><!-- /#page_main -->
	</div> <!-- //#page_container -->
</div><!-- /.innerwrap -->
<?php } ?>
