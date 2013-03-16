<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Initialize the controller
$controller = JControllerLegacy::getInstance('clsFront');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

/*
require_once(JPATH_COMPONENT_ADMINISTRATOR.'/controller.php');
require_once(JPATH_COMPONENT.'/cls.html.php');
require_once(JPATH_COMPONENT.'/controller.php');

// Component Helper
jimport('joomla.application.component.helper');

// Create the controller
switch(JRequest::getCmd('c', 'complaints')) {
    case 'view_location': $controller = new CLSController(array('default_task' => 'viewLocation')); break;
    case 'edit_location': $controller = new CLSController(array('default_task' => 'editLocation')); break;
    default: $controller = new CLSControllerFront(); break;
}

// Perform the Request task
$controller->execute(JRequest::getVar('task'));
$controller->redirect();
*/