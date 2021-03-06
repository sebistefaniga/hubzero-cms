<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2014 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

defined('_JEXEC') or die('Restricted access');

JHTML::_('behavior.framework', true);
JHTML::_('behavior.modal');

// Include global scripts
$this->addScript($this->baseurl . '/templates/' . $this->template . '/js/hub.js');

// Get browser info to set some classes
$browser = new \Hubzero\Browser\Detector();
$cls = array(
	$browser->name(),
	$browser->name() . $browser->major(),
	$this->params->get('header', 'light')
);

// Prepend site name to document title
$this->setTitle(Config::get('sitename') . ' - ' . $this->getTitle());
?>
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="<?php echo end($cls); ?> ie ie6"> <![endif]-->
<!--[if IE 7 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="<?php echo end($cls); ?> ie ie7"> <![endif]-->
<!--[if IE 8 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="<?php echo end($cls); ?> ie ie8"> <![endif]-->
<!--[if IE 9 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="<?php echo end($cls); ?> ie ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html dir="<?php echo $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="<?php echo implode(' ', $cls); ?>"> <!--<![endif]-->
	<head>
		<link rel="stylesheet" type="text/css" media="all" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/index.css?v=<?php echo filemtime(JPATH_ROOT . '/templates/' . $this->template . '/css/index.css'); ?>" />

		<jdoc:include type="head" />

	<?php if ($theme = $this->params->get('theme', '')) { ?>
		<link rel="stylesheet" type="text/css" media="all" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/themes/<?php echo $theme; ?>.css" />
	<?php } ?>

		<!--[if lt IE 9]>
			<script type="text/javascript" src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/html5.js"></script>
		<![endif]-->

		<!--[if IE 9]>
			<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie9.css" />
		<![endif]-->
		<!--[if IE 8]>
			<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie8.css" />
		<![endif]-->
	</head>
	<body>
		<jdoc:include type="modules" name="notices" />
		<div id="outer-wrap">
			<jdoc:include type="modules" name="helppane" />

			<div id="top">
				<div id="splash">
					<div class="inner-wrap">

						<header id="masthead" role="banner">
							<h1>
								<a href="<?php echo $this->baseurl; ?>" title="<?php echo Config::get('sitename'); ?>">
									<span><?php echo Config::get('sitename'); ?></span>
								</a>
							</h1>

							<nav id="account" role="navigation">
							<?php if (!User::isGuest()) {
									$profile = \Hubzero\User\Profile::getInstance(User::get('id'));
							?>
								<ul class="menu loggedin">
									<li>
										<div id="account-info">
											<img src="<?php echo $profile->getPicture(); ?>" alt="<?php echo $profile->get('name'); ?>" width="30" height="30" />
											<a class="account-details" href="<?php echo Route::url($profile->getLink()); ?>">
												<?php echo stripslashes($profile->get('name')); ?> 
												<span class="account-email"><?php echo $profile->get('email'); ?></span>
											</a>
										</div>
										<ul>
											<li id="account-dashboard">
												<a href="<?php echo Route::url($profile->getLink() . '&active=dashboard'); ?>"><span><?php echo Lang::txt('TPL_KAMELEON_ACCOUNT_DASHBOARD'); ?></span></a>
											</li>
											<li id="account-profile">
												<a href="<?php echo Route::url($profile->getLink() . '&active=profile'); ?>"><span><?php echo Lang::txt('TPL_KAMELEON_ACCOUNT_PROFILE'); ?></span></a>
											</li>
											<li id="account-messages">
												<a href="<?php echo Route::url($profile->getLink() . '&active=messages'); ?>"><span><?php echo Lang::txt('TPL_KAMELEON_ACCOUNT_MESSAGES'); ?></span></a>
											</li>
											<li id="account-logout">
												<a href="<?php echo Route::url('index.php?option=com_users&view=logout'); ?>"><span><?php echo Lang::txt('TPL_KAMELEON_LOGOUT'); ?></span></a>
											</li>
										</ul>
									</li>
								</ul>
							<?php } else { ?>
								<ul class="menu loggedout">
									<li id="account-login">
										<a href="<?php echo Route::url('index.php?option=com_users&view=login'); ?>" title="<?php echo Lang::txt('TPL_KAMELEON_LOGIN'); ?>"><?php echo Lang::txt('TPL_KAMELEON_LOGIN'); ?></a>
									</li>
									<li id="account-register">
										<a href="<?php echo Route::url('index.php?option=com_members&controller=register'); ?>" title="<?php echo Lang::txt('TPL_KAMELEON_SIGN_UP'); ?>"><?php echo Lang::txt('TPL_KAMELEON_REGISTER'); ?></a>
									</li>
								</ul>
								<?php /* <jdoc:include type="modules" name="account" /> */ ?>
							<?php } ?>
							</nav><!-- / #account -->

							<nav id="nav" role="main">
								<jdoc:include type="modules" name="user3" />
							</nav><!-- / #nav -->
						</header><!-- / #masthead -->

						<div id="sub-masthead">
						<?php if ($this->countModules('helppane')) : ?>
							<p id="tab">
								<a href="<?php echo Route::url('index.php?option=com_support'); ?>" title="<?php echo Lang::txt('TPL_KAMELEON_NEED_HELP'); ?>">
									<span><?php echo Lang::txt('TPL_KAMELEON_HELP'); ?></span>
								</a>
							</p>
						<?php endif; ?>
							<jdoc:include type="modules" name="search" />
							<div id="trail">
								<?php if (!$this->countModules('welcome')) : ?>
								<jdoc:include type="modules" name="breadcrumbs" />
								<?php else: ?>
								<span class="pathway"><?php echo Lang::txt('TPL_KAMELEON_TAGLINE'); ?></span>
								<?php endif; ?>
							</div><!-- / #trail -->
						</div><!-- / #sub-masthead -->

						<div class="inner">
							<div class="wrap">
							<?php if ($this->getBuffer('message')) : ?>
								<jdoc:include type="message" />
							<?php endif; ?>
								<jdoc:include type="modules" name="welcome" />
							</div><!-- / .wrap -->
						</div><!-- / .inner -->

					</div><!-- / .inner-wrap -->
				</div><!-- / #splash -->
			</div><!-- / #top -->

			<div id="wrap">
				<main id="content" class="<?php echo Request::getCmd('option', ''); ?>" role="main">
					<div class="inner">
					<?php if ($this->countModules('left or right')) : ?>
						<section class="main section">
					<?php endif; ?>

					<?php if ($this->countModules('left')) : ?>
							<aside class="aside">
								<jdoc:include type="modules" name="left" />
							</aside><!-- / .aside -->
					<?php endif; ?>
					<?php if ($this->countModules('left or right')) : ?>
							<div class="subject">
					<?php endif; ?>

								<!-- start component output -->
								<jdoc:include type="component" />
								<!-- end component output -->

					<?php if ($this->countModules('left or right')) : ?>
							</div><!-- / .subject -->
					<?php endif; ?>
					<?php if ($this->countModules('right')) : ?>
							<aside class="aside">
								<jdoc:include type="modules" name="right" />
							</aside><!-- / .aside -->
					<?php endif; ?>

					<?php if ($this->countModules('left or right')) : ?>
						</section><!-- / .main section -->
					<?php endif; ?>
					</div><!-- / .inner -->
				</main><!-- / #content -->

				<footer id="footer">
					<jdoc:include type="modules" name="footer" />
				</footer><!-- / #footer -->
			</div><!-- / #wrap -->
		</div>
		<jdoc:include type="modules" name="endpage" />
	</body>
</html>