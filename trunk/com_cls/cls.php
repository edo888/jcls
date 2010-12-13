<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_COMPONENT.DS.'controller.php');

// Component Helper
jimport('joomla.application.component.helper');

// Create the controller
$controller = new CLSController();

// Perform the Request task
$controller->execute(JRequest::getVar('task', null, 'default', 'display'));
$controller->redirect();