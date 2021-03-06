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

// No direct access.
defined('_JEXEC') or die;

$app = JFactory::getApplication();
$doc = JFactory::getDocument();

// Load CSS
$doc->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/template.css');
$doc->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/common/icons.css');
$doc->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/cpanel.css');
if ($this->params->get('theme') && $this->params->get('theme') != 'gray')
{
	$doc->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/themes/' . $this->params->get('theme') . '.css');
}

// Load language direction CSS
if ($this->direction == 'rtl')
{
	$doc->addStyleSheet($this->baseurl . '/templates/' . $this->template . '/css/common/rtl.css');
}

$doc->addScript($this->baseurl . '/templates/' . $this->template . '/js/index.js');

$browser = new \Hubzero\Browser\Detector();
$b = $browser->name();
$v = $browser->major();
?>
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie8"> <![endif]-->
<!--[if IE 9 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html dir="<?php echo $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="<?php echo $b . ' ' . $b . $v; ?>"> <!--<![endif]-->
	<head>
		<jdoc:include type="head" />
<?php if ($b == 'firefox' && intval($v) < 4 && $browser->getBrowserMinorVersion() < 5) { ?>
		<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/firefox.css" rel="stylesheet" type="text/css" />
<?php } ?>
		<!--[if IE 7]>
			<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie7.css" rel="stylesheet" type="text/css" />
			<script src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/html5.js" type="text/javascript"></script>
		<![endif]-->
		<!--[if IE 8]>
			<link href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/browser/ie8.css" rel="stylesheet" type="text/css" />
			<script src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/html5.js" type="text/javascript"></script>
		<![endif]-->
	</head>
	<body id="cpanel-body">
		<jdoc:include type="modules" name="notices" />
		<header id="header" role="banner">
			<h1><a href="<?php echo JURI::root(); ?>"><?php echo $app->getCfg('sitename'); ?></a></h1>

			<ul class="user-options">
				<?php
					//Display an harcoded logout
					$task = JRequest::getCmd('task');
					if ($task == 'edit' || $task == 'editA' || JRequest::getInt('hidemainmenu'))
					{
						$logoutLink = '';
					}
					else
					{
						$logoutLink = JRoute::_('index.php?option=com_login&task=logout&' . JUtility::getToken() . '=1');
					}
					$hideLinks= JRequest::getBool('hidemainmenu');
					$output = array();
					// Print the Preview link to Main site.
					//$juser = JFactory::getUser();
					//$output[] = '<span class="viewsite"><a href="'.JURI::root().'" rel="external">'.JText::_('JGLOBAL_VIEW_SITE').'</a></span>';
					//$output[] = '<span>' . $juser->get('name') .' (' . $juser->get('username') . ')</span>';
					// Print the logout link.
					$output[] = ($hideLinks ? '<li class="disabled"><span class="logout">' : '<li><a class="logout" href="' . $logoutLink . '">') . JText::_('TPL_HUBBASICADMIN_LOGOUT') . ($hideLinks ? '</span></li>' : '</a></li>');
					// Reverse rendering order for rtl display.
					if ($this->direction == "rtl") :
						$output = array_reverse($output);
					endif;
					// Output the items.
					foreach ($output as $item) :
					echo $item;
					endforeach;
				?>
			</ul>

			<div class="clr"></div>
		</header><!-- / header -->

		<div id="wrap">
			<nav role="navigation" class="main-navigation">
				<div class="inner-wrap">
					<jdoc:include type="modules" name="menu" />
					<div class="clr"><!-- We need this for the drop downs --></div>
				</div>
			</nav><!-- / .navigation -->

			<section id="component-content">
				<div id="toolbar-box" class="toolbar-box">
					<jdoc:include type="modules" name="title" />
					<jdoc:include type="modules" name="toolbar" />
				</div><!-- / #toolbar-box -->

				<?php if (!JRequest::getInt('hidemainmenu') && $this->countModules('submenu') > 1): ?>
				<nav role="navigation" class="sub-navigation">
					<jdoc:include type="modules" name="submenu" />
				</nav><!-- / .sub-navigation -->
				<?php endif; ?>
				<section id="main" class="<?php echo JRequest::getCmd('option', ''); ?>">
					<!-- Notifications begins -->
					<jdoc:include type="message" />
					<!-- Notifications ends -->
					<!-- Content begins -->
					<div class="cpanel col width-48 fltlft">
						<?php if ($this->countModules('icon')>1):?>
							<jdoc:include type="modules" name="icon" style="cpanel" />
						<?php else:?>
							<jdoc:include type="modules" name="icon" style="cpanel" />
						<?php endif;?>
					</div>
					<div class="cpanel col width-48 fltrt">
						<?php /*<jdoc:include type="component" />*/ ?>
						<jdoc:include type="modules" name="cpanel" style="cpanel" />
					</div>
					<!-- Content ends -->
					<noscript>
						<?php echo JText::_('JGLOBAL_WARNJAVASCRIPT') ?>
					</noscript>
					<div class="clr"></div>
				</section><!-- / #main -->
			</section><!-- / #content -->
		</div><!-- / #wrap -->

		<footer id="footer">
			<section class="basement">
				<p class="copyright">
					<?php echo JText::sprintf('TPL_HUBBASICADMIN_COPYRIGHT', '<a href="' . JURI::root() . '">'. $app->getCfg('sitename') . '</a>', date("Y")); ?>
				</p>
				<p class="promotion">
					<?php echo JText::sprintf('TPL_HUBBASICADMIN_POWERED_BY', \Hubzero\Version\Version::VERSION); ?>
				</p>
			</section><!-- / .basement -->
		</footer><!-- / #footer -->
	</body>
</html>