<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

/*
 * Define constants for all pages
 */
if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
}
define('JV', (version_compare(JVERSION, '3', 'l')) ? 'j2' : 'j3');

// Require the base controller
require_once JPATH_COMPONENT.DS.'helpers'.DS.'helper.php';

// Initialize the controller
$controller	= JControllerLegacy::getInstance('cls');

$document = JFactory::getDocument();

// Perform the Request task
if(JV == 'j2')
	$controller->execute( JRequest::getCmd('task'));
else
	$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

function clsLog($action, $description) {
	$db   = JFactory::getDBO();
	$user = JFactory::getUser();
	$description = mysql_real_escape_string($description);
	$db->setQuery("insert into #__complaint_notifications values(null, {$user->id}, '$action', now(), '$description')");
	$db->query();
}