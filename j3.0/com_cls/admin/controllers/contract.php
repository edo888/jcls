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

class ContractTableContract extends JTable {

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct()
    {
        $db   = JFactory::getDBO();
        parent::__construct('#__complaint_contracts', 'id', $db);
    }
}


class ClsControllerContract extends JControllerForm {

    function __construct($default = array()) {
        parent::__construct($default);

        $task = $_REQUEST['task'];
        $this->registerTask('add' , 'editContract');
        $this->registerTask('edit', 'editContract');
        $this->registerTask('save', 'saveContract');
        $this->registerTask('apply', 'saveContract');
        $this->registerTask('remove', 'removeContract');
        $this->registerTask('cancel', 'close');

    }

    function close() {
        $link = 'index.php?option=com_cls&view=contracts';
        $this->setRedirect($link, $msg);
    }

    function editContract() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $id = (int)$_REQUEST['id'];

        $link = 'index.php?option=com_cls&view=contract&layout=edit';
        if($id != 0)
            $link .= '&id='.$id;
        $this->setRedirect($link, $msg);
    }

    function saveContract() {
        $db = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        if($id == 0) { // going to insert new contract
            // constructing the contract object
            $contract = new ContractTableContract;
            $contract->set('name', JRequest::getVar('name'));
            $contract->set('contract_id', JRequest::getVar('contract_id'));
            $contract->set('start_date', JRequest::getVar('start_date'));
            $contract->set('end_date', JRequest::getVar('end_date'));
            $contract->set('contractors', JRequest::getVar('contractors'));
            $contract->set('section_id', JRequest::getInt('section_id'));
            $contract->set('description', JRequest::getVar('description'));
            if (!$contract->store()) {
                $mainframe = JFactory::getApplication();
                $mainframe->enqueueMessage(JText::_('Cannot save contract information'), 'message');
                $mainframe->enqueueMessage($contract->getError(), 'error');
                JRequest::setVar('task', 'editContract');
                return $this->execute('editContract');
            }

            // adding notification
            clsLog('New contract', 'New contract created #' . $db->insertid());

            $this->setRedirect('index.php?option=com_cls&view=contracts', JText::_('Contract successfully created'));
        } else { // going to update section
            // constructing the contract object
            $contract = new ContractTableContract;
            $contract->set('id', $id);
            $contract->set('name', null);
            $contract->set('contract_id', null);
            $contract->set('start_date', null);
            $contract->set('end_date', null);
            $contract->set('contractors', null);
            $contract->set('section_id', null);
            $contract->set('description', null);
            $contract->load();

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $contract->set('name', JRequest::getVar('name'));
                $contract->set('contract_id', JRequest::getVar('contract_id'));
                $contract->set('start_date', JRequest::getVar('start_date'));
                $contract->set('end_date', JRequest::getVar('end_date'));
                $contract->set('contractors', JRequest::getVar('contractors'));
                $contract->set('section_id', JRequest::getInt('section_id'));
                $contract->set('description', JRequest::getVar('description'));

                // storing updated data
                if (!$contract->store()) {
                    $mainframe = JFactory::getApplication();
                    $mainframe->enqueueMessage(JText::_('Cannot save contract information'), 'message');
                    $mainframe->enqueueMessage($contract->getError(), 'error');
                    JRequest::setVar('task', 'editContract');
                    return $this->execute('editContract');
                }

                clsLog('Contract updated', 'The user updated contract #' . $contract->id . ' data');
            }

            if($_REQUEST['task'] == 'save')
                $this->setRedirect('index.php?option=com_cls&view=contracts', JText::_('Contract successfully saved'));
            elseif($_REQUEST['task'] == 'apply')
                $this->setRedirect('index.php?option=com_cls&view=contract&layout=edit&id='.$id, JText::_('Contract successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function removeContract() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        if($user_type == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaint_contracts where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Contract removed', 'The contract with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls&view=contracts', JText::_('Contract(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to delete"));
        }
    }

}
