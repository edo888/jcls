<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_ADMINISTRATOR.DS.'includes'.DS.'toolbar.php');
require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'toolbar.cls.php');
require_once(JPATH_COMPONENT_ADMINISTRATOR.DS.'controller.php');
require_once(JPATH_COMPONENT.DS.'cls.html.php');
require_once(JPATH_COMPONENT.DS.'controller.php');

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