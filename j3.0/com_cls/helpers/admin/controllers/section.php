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

class sectionTableSection extends JTable {

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct()
    {
        $db   = JFactory::getDBO();
        parent::__construct('#__complaint_sections', 'id', $db);
    }
}


class ClsControllerSection extends JControllerForm {

    function __construct($default = array()) {
        parent::__construct($default);

        $task = $_REQUEST['task'];
        $this->registerTask('add' , 'editSection');
        $this->registerTask('edit', 'editSection');
        $this->registerTask('save', 'saveSection');
        $this->registerTask('apply', 'saveSection');
        $this->registerTask('remove', 'removeSection');
        $this->registerTask('cancel', 'close');

    }

    function close() {
        $link = 'index.php?option=com_cls&view=sections';
        $this->setRedirect($link, $msg);
    }

    function editSection() {

        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $ids = $this->input->get('cid', array(), 'array');
        $id = JRequest::getInt('id', intval($ids[0]));

        $link = 'index.php?option=com_cls&view=section&layout=edit';
        if($id != 0)
            $link .= '&id='.$id;
        $this->setRedirect($link, $msg);
    }

    function saveSection() {
        $db = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        if($id == 0) { // going to insert new section
            // constructing the section object
            $section = new sectionTableSection;
            $section->set('name', JRequest::getVar('name'));
            $section->set('description', JRequest::getVar('description'));
            $section->set('polyline', JRequest::getVar('polyline'));
            $section->set('polygon', JRequest::getVar('polygone'));
            if (!$section->store()) {
                $mainframe = JFactory::getApplication();
                $mainframe->enqueueMessage(JText::_('Cannot save section information'), 'message');
                $mainframe->enqueueMessage($section->getError(), 'error');
                JRequest::setVar('task', 'editSection');
                return $this->execute('editSection');
            }

            // adding notification
            clsLog('New section', 'New section created #' . $db->insertid());

            $this->setRedirect('index.php?option=com_cls&view=sections', JText::_('Location successfully created'));
        } else { // going to update section
            // constructing the section object
            $section = new sectionTableSection;
            $section->set('id', $id);
            $section->set('name', null);
            $section->set('polyline', null);
            $section->set('polygon', null);
            $section->set('description', null);
            $section->load();

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $section->set('name', JRequest::getVar('name'));
                $section->set('description', JRequest::getVar('description'));
                $section->set('polyline', JRequest::getVar('polyline'));
                $section->set('polygon', JRequest::getVar('polygon'));

                // storing updated data
                if (!$section->store()) {
                    $mainframe = JFactory::getApplication();
                    $mainframe->enqueueMessage(JText::_('Cannot save section information'), 'message');
                    $mainframe->enqueueMessage($section->getError(), 'error');
                    JRequest::setVar('task', 'editSection');
                    return $this->execute('editSection');
                }

                clsLog('Location updated', 'The user updated section #' . $section->id . ' data');
            }

            if($_REQUEST['task'] == 'save')
                $this->setRedirect('index.php?option=com_cls&view=sections', JText::_('Location successfully saved'));
            elseif($_REQUEST['task'] == 'apply')
            $this->setRedirect('index.php?option=com_cls&task=section.edit&id='.$id, JText::_('Location successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function removeSection() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        if($user_type == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaint_sections where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Location removed', 'The location with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls&view=sections', JText::_('Location(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to delete"));
        }
    }

}
