<?php
/**
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters. All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

?>
<!DOCTYPE html>
<!--[if lt IE 7 ]> <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie8"> <![endif]-->
<!--[if IE 9 ]>    <html dir="<?php echo  $this->direction; ?>" lang="<?php echo  $this->language; ?>" class="ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html dir="<?php echo $this->direction; ?>" lang="<?php echo  $this->language; ?>"> <!--<![endif]-->
	<head>
		<jdoc:include type="head" />
		<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/offline.css" type="text/css" />
<?php if ($this->direction == 'rtl') : ?>
		<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/offline_rtl.css" type="text/css" />
<?php endif; ?>
		<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
	</head>
	<body>
		<jdoc:include type="message" />
		<div id="frame" class="outline">
			<img src="images/joomla_logo_black.jpg" alt="Joomla! Logo" align="middle" />
			<h1>
				<?php echo Config::get('sitename'); ?>
			</h1>
			<p>
				<?php echo Config::get('offline_message'); ?>
			</p>
<?php if (JPluginHelper::isEnabled('authentication', 'openid')) : ?>
			<?php JHTML::_('script', 'openid.js'); ?>
<?php endif; ?>
			<form action="index.php" method="post" name="login" id="form-login">
				<fieldset class="input">
					<p id="form-login-username">
						<label for="username"><?php echo Lang::txt('Username') ?></label><br />
						<input name="username" id="username" type="text" class="inputbox" alt="<?php echo Lang::txt('Username') ?>" size="18" />
					</p>
					<p id="form-login-password">
						<label for="passwd"><?php echo Lang::txt('Password') ?></label><br />
						<input type="password" name="passwd" class="inputbox" size="18" alt="<?php echo Lang::txt('Password') ?>" id="passwd" />
					</p>
					<p id="form-login-remember">
						<label for="remember"><?php echo Lang::txt('Remember me') ?></label>
						<input type="checkbox" name="remember" class="inputbox" value="yes" alt="<?php echo Lang::txt('Remember me') ?>" id="remember" />
					</p>
					<input type="submit" name="Submit" class="button" value="<?php echo Lang::txt('LOGIN') ?>" />
				</fieldset>
				<input type="hidden" name="option" value="com_user" />
				<input type="hidden" name="task" value="login" />
				<input type="hidden" name="return" value="<?php echo base64_encode(Request::base()) ?>" />
				<?php echo JHTML::_( 'form.token' ); ?>
			</form>
		</div>
	</body>
</html>