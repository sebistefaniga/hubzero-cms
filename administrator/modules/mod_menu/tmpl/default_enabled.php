<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// No direct access.
defined('_JEXEC') or die;

$shownew = (boolean) $params->get('shownew', 1);
$user = User::getRoot();
$lang = JFactory::getLanguage();

//
// Site SubMenu
//
$menu->addChild(
	new \Modules\Menu\Node(Lang::txt('JSITE'), '#'), true
);
$menu->addChild(
	new \Modules\Menu\Node(Lang::txt('MOD_MENU_CONTROL_PANEL'), 'index.php', 'class:cpanel')
);

$menu->addSeparator();

/*
$menu->addChild(
	new \Modules\Menu\Node(Lang::txt('MOD_MENU_USER_PROFILE'), 'index.php?option=com_admin&task=profile.edit&id=' . $user->id, 'class:profile')
);
$menu->addSeparator();
*/

if ($user->authorise('core.admin'))
{
	$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_CONFIGURATION'), 'index.php?option=com_config', 'class:config'));
	$menu->addSeparator();
}

$chm = $user->authorise('core.admin', 'com_checkin');
$cam = $user->authorise('core.manage', 'com_cache');

if ($chm || $cam)
{
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_MAINTENANCE'), 'index.php?option=com_checkin', 'class:maintenance'), true
	);

	if ($chm)
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_GLOBAL_CHECKIN'), 'index.php?option=com_checkin', 'class:checkin'));
		$menu->addSeparator();
	}
	if ($cam)
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_CLEAR_CACHE'), 'index.php?option=com_cache', 'class:clear'));
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_PURGE_EXPIRED_CACHE'), 'index.php?option=com_cache&view=purge', 'class:purge'));
		$menu->addSeparator();
	}

	if ($user->authorise('core.admin'))
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_UPDATE'), 'index.php?option=com_update', 'class:update'));
	}

	$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_SYS_LDAP'), 'index.php?option=com_system&controller=ldap', 'class:ldap'));
	$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_SYS_GEO'), 'index.php?option=com_system&controller=geodb', 'class:geo'));
	$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_SYS_APC'), 'index.php?option=com_system&controller=apc', 'class:apc'));
	//$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_SYS_SCRIPTS'), 'index.php?option=com_system&controller=scripts', 'class:scripts'));
	$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_SYS_ROUTES'), 'index.php?option=com_redirect', 'class:routes'));

	$menu->getParent();
}

$menu->addSeparator();
if ($user->authorise('core.admin'))
{
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_SYSTEM_INFORMATION'), 'index.php?option=com_system&controller=info', 'class:info')
	);
	$menu->addSeparator();
}

$menu->addChild(
	new \Modules\Menu\Node(Lang::txt('MOD_MENU_LOGOUT'), Route::url('index.php?option=com_login&task=logout&' . JSession::getFormToken() . '=1'), 'class:logout')
);

$menu->getParent();

//
// Users Submenu
//
if ($user->authorise('core.manage', 'com_users'))
{
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_USERS'), '#'), true
	);
	$createUser = $shownew && $user->authorise('core.create', 'com_users');
	$createGrp  = $user->authorise('core.admin', 'com_users');

	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_USER_MANAGER'), 'index.php?option=com_users&view=users', 'class:user') //, $createUser
	);

	/*if ($createUser)
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_ADD_USER'), 'index.php?option=com_users&task=user.add', 'class:newarticle')
		);
		$menu->getParent();
	}*/

	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_MEMBERS'), 'index.php?option=com_members', 'class:members'), $createUser
	);
	if ($createUser)
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_MEMBERS_ADD_MEMBER'), 'index.php?option=com_members&task=add', 'class:newuser')
		);
		$menu->getParent();
	}
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_GROUPS'), 'index.php?option=com_groups', 'class:groups')
	);

	if ($createGrp)
	{
		$menu->addSeparator();
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_GROUPS'), 'index.php?option=com_users&view=groups', 'class:groups'), $createUser
		);
		if ($createUser)
		{
			$menu->addChild(
				new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_ADD_GROUP'), 'index.php?option=com_users&task=group.add', 'class:newarticle')
			);
			$menu->getParent();
		}

		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_LEVELS'), 'index.php?option=com_users&view=levels', 'class:levels'), $createUser
		);

		if ($createUser)
		{
			$menu->addChild(
				new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_ADD_LEVEL'), 'index.php?option=com_users&task=level.add', 'class:newarticle')
			);
			$menu->getParent();
		}
	}

	$menu->addSeparator();
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_NOTES'), 'index.php?option=com_users&view=notes', 'class:user-note'), $createUser
	);

	if ($createUser)
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_ADD_NOTE'), 'index.php?option=com_users&task=note.add', 'class:newarticle')
		);
		$menu->getParent();
	}

	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_USERS_NOTE_CATEGORIES'), 'index.php?option=com_categories&view=categories&extension=com_users', 'class:category'),
		$createUser
	);

	if ($createUser)
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_CONTENT_NEW_CATEGORY'), 'index.php?option=com_categories&task=category.add&extension=com_users', 'class:newarticle')
		);
		$menu->getParent();
	}

	/*$menu->addSeparator();
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_MASS_MAIL_USERS'), 'index.php?option=com_users&view=mail', 'class:massmail')
	);*/

	$menu->getParent();
}

//
// Menus Submenu
//
if ($user->authorise('core.manage', 'com_menus'))
{
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_MENUS'), '#'), true
	);
	$createMenu = $shownew && $user->authorise('core.create', 'com_menus');

	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_MENU_MANAGER'), 'index.php?option=com_menus&view=menus', 'class:menumgr'), $createMenu
	);
	if ($createMenu)
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_MENU_MANAGER_NEW_MENU'), 'index.php?option=com_menus&view=menu&layout=edit', 'class:newarticle')
		);
		$menu->getParent();
	}
	$menu->addSeparator();

	// Menu Types
	foreach ($this->getMenus() as $menuType)
	{
		$alt = '*' . $menuType->sef . '*';
		if ($menuType->home == 0)
		{
			$titleicon = '';
		}
		elseif ($menuType->home == 1 && $menuType->language == '*')
		{
			//$titleicon = ' <span>'.JHtml::_('image', 'menu/icon-16-default.png', '*', array('title' => Lang::txt('MOD_MENU_HOME_DEFAULT')), true).'</span>';
			$titleicon = ' <span class="home" title="' . Lang::txt('MOD_MENU_HOME_DEFAULT') . '">' . '*' . '</span>';
		}
		elseif ($menuType->home > 1)
		{
			//$titleicon = ' <span>'.JHtml::_('image', 'menu/icon-16-language.png', $menuType->home, array('title' => Lang::txt('MOD_MENU_HOME_MULTIPLE')), true).'</span>';
			$titleicon = ' <span class="home multiple" title="' . Lang::txt('MOD_MENU_HOME_MULTIPLE') . '">' . $menuType->home . '</span>';
		}
		else
		{
			/*$image = JHtml::_('image', 'mod_languages/' . $menuType->image . '.gif', NULL, NULL, true, true);
			if (!$image)
			{
				//$titleicon = ' <span>'.JHtml::_('image', 'menu/icon-16-language.png', $alt, array('title' => $menuType->title_native), true).'</span>';
				$titleicon = ' <span title="' . $menuType->title_native . '">' . $alt . '</span>';
			}
			else
			{*/
				//$titleicon = ' <span>'.JHtml::_('image', 'mod_languages/'.$menuType->image.'.gif', $alt, array('title'=>$menuType->title_native), true).'</span>';
				$titleicon = ' <span title="' . $menuType->title_native . '">' . $alt . '</span>';
			//}
		}
		$menu->addChild(
			new \Modules\Menu\Node($menuType->title, 'index.php?option=com_menus&view=items&menutype=' . $menuType->menutype, 'class:menu', null, null, $titleicon), $createMenu
		);

		if ($createMenu)
		{
			$menu->addChild(
				new \Modules\Menu\Node(Lang::txt('MOD_MENU_MENU_MANAGER_NEW_MENU_ITEM'), 'index.php?option=com_menus&view=item&layout=edit&menutype=' . $menuType->menutype, 'class:newarticle')
			);
			$menu->getParent();
		}
	}
	$menu->getParent();
}

//
// Content Submenu
//
if ($user->authorise('core.manage', 'com_content'))
{
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_CONTENT'), '#'), true
	);
	$createContent = $shownew && $user->authorise('core.create', 'com_content');
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_CONTENT_ARTICLE_MANAGER'), 'index.php?option=com_content', 'class:article'), $createContent
	);
	if ($createContent)
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_CONTENT_NEW_ARTICLE'), 'index.php?option=com_content&task=article.add', 'class:newarticle')
		);
		$menu->getParent();
	}

	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_CONTENT_CATEGORY_MANAGER'), 'index.php?option=com_categories&extension=com_content', 'class:category'), $createContent
	);
	if ($createContent)
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_CONTENT_NEW_CATEGORY'), 'index.php?option=com_categories&task=category.add&extension=com_content', 'class:newarticle')
		);
		$menu->getParent();
	}
	/*$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_COM_CONTENT_FEATURED'), 'index.php?option=com_content&view=featured', 'class:featured')
	);
	*/
	if ($user->authorise('core.manage', 'com_media'))
	{
		$menu->addSeparator();
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_MEDIA_MANAGER'), 'index.php?option=com_media', 'class:media'));
	}

	$menu->getParent();
}

//
// Components Submenu
//

// Get the authorised components and sub-menus.
$components = $this->getComponents(true);

// Check if there are any components, otherwise, don't render the menu
if ($components)
{
	$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_COMPONENTS'), '#'), true);

	foreach ($components as &$component)
	{
		if (in_array($component->element, array('com_members', 'com_groups', 'com_system')))
		{
			continue;
		}
		if (!empty($component->submenu))
		{
			// This component has a db driven submenu.
			$menu->addChild(new \Modules\Menu\Node($component->text, $component->link, $component->img), true);
			foreach ($component->submenu as $sub)
			{
				$menu->addChild(new \Modules\Menu\Node($sub->text, $sub->link, $sub->img));
			}
			$menu->getParent();
		}
		else
		{
			$menu->addChild(new \Modules\Menu\Node($component->text, $component->link, $component->img));
		}
	}
	$menu->getParent();
}

//
// Extensions Submenu
//
$im = $user->authorise('core.manage', 'com_installer');
$mm = $user->authorise('core.manage', 'com_modules');
$pm = $user->authorise('core.manage', 'com_plugins');
$tm = $user->authorise('core.manage', 'com_templates');
$lm = $user->authorise('core.manage', 'com_languages');

if ($im || $mm || $pm || $tm || $lm)
{
	$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_EXTENSIONS_EXTENSIONS'), '#'), true);

	if ($im)
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_EXTENSIONS_EXTENSION_MANAGER'), 'index.php?option=com_installer', 'class:install'));
		$menu->addSeparator();
	}

	if ($mm)
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_EXTENSIONS_MODULE_MANAGER'), 'index.php?option=com_modules', 'class:module'));
	}

	if ($pm)
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_EXTENSIONS_PLUGIN_MANAGER'), 'index.php?option=com_plugins', 'class:plugin'));
	}

	if ($tm)
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_EXTENSIONS_TEMPLATE_MANAGER'), 'index.php?option=com_templates', 'class:themes'));
	}

	if ($lm)
	{
		$menu->addChild(new \Modules\Menu\Node(Lang::txt('MOD_MENU_EXTENSIONS_LANGUAGE_MANAGER'), 'index.php?option=com_languages', 'class:language'));
	}
	$menu->getParent();
}

//
// Help Submenu
//
if ($params->get('showhelp', 0) == 1)
{
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP'), '#'), true
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_DOCUMENTATION'), 'http://hubzero.org/documentation', 'class:help', false, '_blank')
	);
	$menu->addSeparator();

	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_SUPPORT_OFFICIAL_FORUM'), 'http://hubzero.org/answers', 'class:help-forum', false, '_blank')
	);
	if ($forum_url = $params->get('forum_url'))
	{
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_SUPPORT_CUSTOM_FORUM'), $forum_url, 'class:help-forum', false, '_blank')
		);
	}
	/*$debug = $lang->setDebug(false);
	if ($lang->hasKey('MOD_MENU_HELP_SUPPORT_OFFICIAL_LANGUAGE_FORUM_VALUE') && Lang::txt('MOD_MENU_HELP_SUPPORT_OFFICIAL_LANGUAGE_FORUM_VALUE') != '')
	{
		$forum_url = 'http://hubzero.org/forum/?f=' . (int) Lang::txt('MOD_MENU_HELP_SUPPORT_OFFICIAL_LANGUAGE_FORUM_VALUE');
		$lang->setDebug($debug);
		$menu->addChild(
			new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_SUPPORT_OFFICIAL_LANGUAGE_FORUM'), $forum_url, 'class:help-forum', false, '_blank')
		);
	}
	$lang->setDebug($debug);*/
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_HUBZERO'), 'http://hubzero.org/support', 'class:help-docs', false, '_blank')
	);
	/*$menu->addSeparator();
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_LINKS'), '#', 'class:weblinks'), true
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_EXTENSIONS'), 'http://hubzero.org/extensions=', 'class:help-jed', false, '_blank')
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_TRANSLATIONS'), 'http://hubzero.org/translations', 'class:help-trans', false, '_blank')
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_RESOURCES'), 'http://hubzero.org/documentation', 'class:help-jrd', false, '_blank')
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_COMMUNITY'), 'http://hubzero.org/community', 'class:help-community', false, '_blank')
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_SECURITY'), 'http://hubzero.org/security', 'class:help-security', false, '_blank')
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_DEVELOPER'), 'http://hubzero.org/developer', 'class:help-dev', false, '_blank')
	);
	$menu->addChild(
		new \Modules\Menu\Node(Lang::txt('MOD_MENU_HELP_SHOP'), 'http://hubzero.org/store', 'class:help-shop', false, '_blank')
	);
	$menu->getParent();*/
	$menu->getParent();
}
