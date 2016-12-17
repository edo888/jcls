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

class SupportGroupTableSupportGroup extends JTable {

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct()
    {
        $db   = JFactory::getDBO();
        parent::__construct('#__complaint_support_groups', 'id', $db);
    }
}


class ClsControllerSupportGroup extends JControllerForm {

    function __construct($default = array()) {
        parent::__construct($default);

        $task = $_REQUEST['task'];
        $this->registerTask('add' , 'editsupportgroup');
        $this->registerTask('edit', 'editsupportgroup');
        $this->registerTask('save', 'savesupportgroup');
        $this->registerTask('apply', 'savesupportgroup');
        $this->registerTask('remove', 'removesupportgroup');
        $this->registerTask('cancel', 'close');

    }

    function close() {
        $link = 'index.php?option=com_cls&view=supportgroups';
        $this->setRedirect($link, $msg);
    }

    function editsupportgroup() {
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

        $link = 'index.php?option=com_cls&view=supportgroup&layout=edit';
        if($id != 0)
            $link .= '&id='.$id;
        $this->setRedirect($link, $msg);
    }

    function saveSupportGroup() {
        $db = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $assigned_users = JRequest::getVar('users', array(), 'default', 'array');
        //echo '<pre>', print_r($assigned_users, true), '</pre>';
        //exit;

        if($id == 0) { // going to insert new support group
            // constructing the section object
            $support_group = new SupportGroupTableSupportGroup;
            $support_group->set('name', JRequest::getVar('name'));
            $support_group->set('description', JRequest::getVar('description'));
            $support_group->store();

            $group_id = $db->insertid();

            // assign users to support group
            foreach($assigned_users as $user_id) {
                $query = "insert into #__complaint_support_groups_users_map value(null, $group_id, $user_id)";
                $db->setQuery($query);
                $db->query();
            }

            // adding notification
            clsLog('New support group', 'New support group created #' . $group_id);

            $this->setRedirect('index.php?option=com_cls&view=supportgroups', JText::_('Support Group successfully created'));
        } else { // going to update section
            // constructing the support group object
            $support_group = new SupportGroupTableSupportGroup;
            $support_group->set('id', $id);
            $support_group->set('name', null);
            $support_group->set('description', null);
            $support_group->load();

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $support_group->set('name', JRequest::getVar('name'));
                $support_group->set('description', JRequest::getVar('description'));

                // storing updated data
                $support_group->store();

                // delete assigned users
                $query = "delete from #__complaint_support_groups_users_map where group_id = $id";
                $db->setQuery($query);
                $db->query();

                // assign users to support group
                foreach($assigned_users as $user_id) {
                    $query = "insert into #__complaint_support_groups_users_map value(null, $id, $user_id)";
                    $db->setQuery($query);
                    $db->query();
                }

                // adding notification
                clsLog('Support Group updated', 'The user updated Support Group #' . $section->id . ' data');
            }

            if($_REQUEST['task'] == 'save')
                $this->setRedirect('index.php?option=com_cls&view=supportgroups', JText::_('Support Group successfully saved'));
            elseif($_REQUEST['task'] == 'apply')
                $this->setRedirect('index.php?option=com_cls&task=supportgroup.edit&id='.$id, JText::_('Support Group successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function removeSupportGroup() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        if($user_type == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaint_support_groups where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();

                $query = "delete from #__complaint_support_groups_users_map where group_id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Support Group removed', 'The support group with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls&view=supportgroups', JText::_('Support Group(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to deleted"));
        }
    }

}
