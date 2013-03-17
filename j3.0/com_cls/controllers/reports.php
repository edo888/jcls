<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

jimport('joomla.application.component.controllerform');
jimport('joomla.database.table');

class ClsFrontControllerReports extends JControllerForm {

    function __construct($default = array()) {
        parent::__construct($default);

        $task = $_REQUEST['task'];
        $this->registerTask('show' , 'showStatistics');
        $this->registerTask('cancel', 'close');

    }

    function close() {
        $link = 'index.php?option=com_cls';
        $this->setRedirect($link, $msg);
    }

    function showStatistics() {
        $startdate = $_REQUEST['startdate'];
        $enddate = $_REQUEST['enddate'];
        $link = 'index.php?option=com_cls&view=reports&startdate='.$startdate.'&enddate='.$enddate;
        $this->setRedirect($link, $msg);
    }

}
