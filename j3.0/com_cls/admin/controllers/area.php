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

class AreaTableArea extends JTable {

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct() {
        $db   = JFactory::getDBO();
        parent::__construct('#__complaint_areas', 'id', $db);
    }
}


class ClsControllerArea extends JControllerForm {

    function __construct($default = array()) {
        parent::__construct($default);

        $task = $_REQUEST['task'];
        $this->registerTask('add' , 'editArea');
        $this->registerTask('edit', 'editArea');
        $this->registerTask('save', 'saveArea');
        $this->registerTask('apply', 'saveArea');
        $this->registerTask('remove', 'removeArea');
        $this->registerTask('cancel', 'close');

    }

    function close() {
        $link = 'index.php?option=com_cls&view=areas';
        $this->setRedirect($link, $msg);
    }

    function editArea() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $id = (int)$_REQUEST['id'];

        $link = 'index.php?option=com_cls&view=area&layout=edit';
        if($id != 0)
            $link .= '&id='.$id;
        $this->setRedirect($link, $msg);
    }

    function saveArea() {
        $db = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        if($id == 0) { // going to insert new area
            // constructing the section object
            $area = new AreaTableArea;
            $area->set('area', JRequest::getVar('area'));
            $area->set('description', JRequest::getVar('description'));
            if (!$area->store()) {
                $mainframe = JFactory::getApplication();
                $mainframe->enqueueMessage(JText::_('Cannot save complaint category information'), 'message');
                $mainframe->enqueueMessage($area->getError(), 'error');
                JRequest::setVar('task', 'editArea');
                return $this->execute('editArea');
            }

            // adding notification
            clsLog('New complaint category', 'New complaint category created #' . $db->insertid());

            $this->setRedirect('index.php?option=com_cls&view=areas', JText::_('Complaint category successfully created'));
        } else { // going to update section
            // constructing the section object
            $area = new AreaTableArea;
            $area->set('id', $id);
            $area->set('area', null);
            $area->set('description', null);
            $area->load();

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $area->set('area', JRequest::getVar('area'));
                $area->set('description', JRequest::getVar('description'));

                // storing updated data
                if (!$area->store()) {
                    $mainframe = JFactory::getApplication();
                    $mainframe->enqueueMessage(JText::_('Cannot save complaint category information'), 'message');
                    $mainframe->enqueueMessage($area->getError(), 'error');
                    JRequest::setVar('task', 'editArea');
                    return $this->execute('editArea');
                }

                clsLog('Complaint category updated', 'The user updated complaint category #' . $area->id . ' data');
            }

            if($_REQUEST['task'] == 'save')
                $this->setRedirect('index.php?option=com_cls&view=areas', JText::_('Complaint category successfully saved'));
            elseif($_REQUEST['task'] == 'apply')
                $this->setRedirect('index.php?option=com_cls&view=area&layout=edit&id='.$id, JText::_('Complaint category successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function removeArea() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        $user_type = $user->getParam('role', 'Guest');

        if($user_type == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaint_areas where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Complaint category removed', 'The complaint category with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls&view=areas', JText::_('Complaint category(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to delete"));
        }
    }

}
