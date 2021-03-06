<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
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

namespace Components\Publications\Models\Element;

use Components\Publications\Models\Element as Base;

/**
 * Renders an editor element
 */
class Editor extends Base
{
	/**
	* Element name
	*
	* @var		string
	*/
	protected	$_name = 'Editor';

	/**
	 * Return any options this element may have
	 *
	 * @param   string  $name          Name of the field
	 * @param   string  $value         Value to check against
	 * @param   object  $element       Data Source Object.
	 * @param   string  $control_name  Control name (eg, control[fieldname])
	 * @return  string  HTML
	 */
	public function fetchElement($name, $value, &$element, $control_name)
	{
		$rows = isset($element->rows) ? $element->rows : 6;
		$cols = isset($element->cols) ? $element->cols : 50;
		$editorMacros 	= isset($element->editorMacros)
						? $element->editorMacros : 0;
		$editorMinimal 	= isset($element->editorMinimal)
						? $element->editorMinimal : 1;
		$editorImages 	= isset($element->editorImages)
						? $element->editorImages : 0;

		$classes  = $editorMinimal == 1 ? 'minimal ' : '';
		$classes .= ' no-footer ';
		$classes .= $editorImages == 1 ? 'images ' : '';
		$classes .= $editorMacros == 1 ? 'macros ' : '';

		return '<span class="field-wrap">' . \Components\Wiki\Helpers\Editor::getInstance()->display($control_name.'['.$name.']', $value, '', '', $cols, $rows, false, $control_name.'-'.$name, null, null, array('class' => $classes)) . '</span>';
	}
}