<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

// Require the base controller
require_once JPATH_COMPONENT.'/helpers/helper.php';

// Initialize the controller
$controller = JControllerLegacy::getInstance('cls');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

function clsLog($action, $description) {
    $db   = JFactory::getDBO();
    $user = JFactory::getUser();
    $description = mysql_real_escape_string($description);
    $db->setQuery("insert into #__complaint_notifications values(null, {$user->id}, '$action', now(), '$description')");
    $db->query();
}