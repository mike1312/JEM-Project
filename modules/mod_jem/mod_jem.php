<?php
/**
 * @version 2.1.3
 * @package JEM
 * @subpackage JEM Module
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;

// get helper
require_once(dirname(__FILE__).'/helper.php');

require_once(JPATH_SITE.'/components/com_jem/helpers/route.php');
require_once(JPATH_SITE.'/components/com_jem/helpers/helper.php');
require_once(JPATH_SITE.'/components/com_jem/classes/output.class.php');

JFactory::getLanguage()->load('com_jem', JPATH_SITE.'/components/com_jem');

$list = modJEMHelper::getList($params);

// check if any results returned
$items = count($list);
if (!$items) {
	//return;
}

require(JModuleHelper::getLayoutPath('mod_jem'));