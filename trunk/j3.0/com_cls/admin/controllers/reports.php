<?php
/**
 * Joomla! component sexypolling
 *
 * @version $Id: sexypoll.php 2012-04-05 14:30:25 svn $
 * @author 2GLux.com
 * @package Sexy Polling
 * @subpackage com_sexypolling
 * @license GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restircted access');

jimport('joomla.application.component.controllerform');

jimport('joomla.database.table');




class ClsControllerReports extends JControllerForm
{
	
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
		$startdate = $_REQUEST['stratdate'];
		$enddate = $_REQUEST['enddate'];
		$link = 'index.php?option=com_cls&view=reports&stratdate='.$startdate.'&enddate='.$enddate;
		$this->setRedirect($link, $msg);
	}
	
}
