<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'controller.php');
require_once(JApplicationHelper::getPath('admin_html'));

function clsLog($action, $description) {
    $db   =& JFactory::getDBO();
    $user =& JFactory::getUser();
    $description = mysql_real_escape_string($description);
    $db->setQuery("insert into #__complaint_notifications values(null, {$user->id}, '$action', now(), '$description')");
    $db->query();
}


switch(JRequest::getCmd('c', 'complaints')) {
    case 'notifications': $controller = new CLSController(array('default_task' => 'showNotifications')); break;
    case 'reports': $controller = new CLSController(array('default_task' => 'showReports')); break;
    case 'complaints': $controller = new CLSController(array('default_task' => 'showComplaints')); break;
    case 'view_location': $controller = new CLSController(array('default_task' => 'viewLocation')); break;
    case 'edit_location': $controller = new CLSController(array('default_task' => 'editLocation')); break;
    case 'view_section_map': $controller = new CLSController(array('default_task' => 'viewSectionMap')); break;
    case 'edit_section_map': $controller = new CLSController(array('default_task' => 'editSectionMap')); break;
    case 'contracts': $controller = new CLSController(array('default_task' => 'showContracts')); break;
    case 'sections': $controller = new CLSController(array('default_task' => 'showSections')); break;
    default: $controller = new CLSController(array('default_task' => 'showComplaints')); break;
}

$task = JRequest::getVar('task');
$controller->execute(JRequest::getVar('task'));
$controller->redirect();