<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die('Restricted access');

class TOOLBAR_CLS {

    function _EDIT($cid) {
        $cid = JRequest::getVar('cid',array(0));

        $text = ( $cid[0] ? JText::_( 'Edit' ) : JText::_( 'New' ) );

        global $mainframe;
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        if(JRequest::getCmd('task') == 'edit' or JRequest::getCmd('task') == 'add') {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' ]</small></small>');
            if($user_type != 'Guest')
                JToolBarHelper::save();
            if($text !== JText::_('New') and $user_type != 'Guest' and $mainframe->isAdmin())
                JToolBarHelper::apply();
            if($cid[0])
                JToolBarHelper::cancel('cancel', 'Close');
            else
                JToolBarHelper::cancel();

            JToolBarHelper::help('screen.cls.new', true);
        } elseif(JRequest::getCmd('task') == 'editContract' or JRequest::getCmd('task') == 'addContract') {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' Contract ]</small></small>');
            if($user_type != 'Guest' and $user_type != 'Level 2' and $user_type != 'Supervisor')
                JToolBarHelper::save('saveContract');
            if($text !== JText::_('New') and $user_type != 'Guest' and $user_type != 'Level 2' and $user_type != 'Supervisor')
                JToolBarHelper::apply('applyContract');
            if($cid[0])
                JToolBarHelper::cancel('cancelContract', 'Close');
            else
                JToolBarHelper::cancel('cancelContract');

            JToolBarHelper::help('screen.cls.contracts', true);
        } elseif(JRequest::getCmd('task') == 'editArea' or JRequest::getCmd('task') == 'addArea') {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' Category ]</small></small>');
            if($user_type != 'Guest' and $user_type != 'Level 2' and $user_type != 'Supervisor')
                JToolBarHelper::save('saveArea');
            if($text !== JText::_('New') and $user_type != 'Guest' and $user_type != 'Level 2' and $user_type != 'Supervisor')
                JToolBarHelper::apply('applyArea');
            if($cid[0])
                JToolBarHelper::cancel('cancelArea', 'Close');
            else
                JToolBarHelper::cancel('cancelArea');

            JToolBarHelper::help('screen.cls.areas', true);
        } elseif(JRequest::getCmd('task') == 'editSection' or JRequest::getCmd('task') == 'addSection') {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' Section ]</small></small>');
            if($user_type != 'Guest' and $user_type != 'Level 2' and $user_type != 'Supervisor')
                JToolBarHelper::save('saveSection');
            if($text !== JText::_('New') and $user_type != 'Guest' and $user_type != 'Level 2' and $user_type != 'Supervisor')
                JToolBarHelper::apply('applySection');
            if($cid[0])
                JToolBarHelper::cancel('cancelSection', 'Close');
            else
                JToolBarHelper::cancel('cancelSection');

            JToolBarHelper::help('screen.cls.sections', true);
        } elseif($user_type == 'System Administrator' and (JRequest::getCmd('task') == 'editSupportGroup' or JRequest::getCmd('task') == 'addSupportGroup')) {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' Support Group ]</small></small>');
            JToolBarHelper::save('saveSupportGroup');
            if($text !== JText::_('New'))
                JToolBarHelper::apply('applySupportGroup');
            if($cid[0])
                JToolBarHelper::cancel('cancelSupportGroup', 'Close');
            else
                JToolBarHelper::cancel('cancelSupportGroup');

            JToolBarHelper::help('screen.cls.supportgroups', true);
        }
    }

    function _DEFAULT() {
        global $mainframe;
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        if(JRequest::getCmd('c', 'complaints') == 'complaints')
            JToolBarHelper::title(JText::_('Complaints'));
        elseif(JRequest::getCmd('c', 'complaints') == 'notifications')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Activity Log ]</small></small>');
        elseif(JRequest::getCmd('c', 'complaints') == 'contracts')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Contracts ]</small></small>');
        elseif(JRequest::getCmd('c', 'complaints') == 'areas')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Categories ]</small></small>');
        elseif(JRequest::getCmd('c', 'complaints') == 'sections')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Sections ]</small></small>');
        elseif(JRequest::getCmd('c', 'complaints') == 'SupportGroups')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Support Groups ]</small></small>');
        else
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Reports ]</small></small>');

        if(JRequest::getCmd('c', 'complaints') == 'complaints' and $mainframe->isAdmin()) {
            if($user_type == 'System Administrator' or $user_type == 'Level 1')
                JToolBarHelper::addNewX();
            JToolBarHelper::editListX();
            if($user_type == 'System Administrator')
                JToolBarHelper::deleteList();
        } elseif(JRequest::getCmd('c', 'complaints') == 'contracts') {
            if($user_type == 'System Administrator' or $user_type == 'Level 1')
                JToolBarHelper::addNewX('addContract');
            JToolBarHelper::editListX('editContract');
            if($user_type == 'System Administrator')
                JToolBarHelper::deleteList('', 'removeContract');
        } elseif(JRequest::getCmd('c', 'complaints') == 'areas') {
            if($user_type == 'System Administrator' or $user_type == 'Level 1')
                JToolBarHelper::addNewX('addArea');
            JToolBarHelper::editListX('editArea');
            if($user_type == 'System Administrator')
                JToolBarHelper::deleteList('', 'removeArea');
        } elseif(JRequest::getCmd('c', 'complaints') == 'sections') {
            if($user_type == 'System Administrator' or $user_type == 'Level 1')
                JToolBarHelper::addNewX('addSection');
            JToolBarHelper::editListX('editSection');
            if($user_type == 'System Administrator')
                JToolBarHelper::deleteList('', 'removeSection');
        } elseif(JRequest::getCmd('c', 'complaints') == 'SupportGroups') {
            if($user_type == 'System Administrator' or $user_type == 'Level 1')
                JToolBarHelper::addNewX('addSupportGroup');
            JToolBarHelper::editListX('editSupportGroup');
            if($user_type == 'System Administrator')
                JToolBarHelper::deleteList('', 'removeSupportGroup');
        }

        if($user_type == 'System Administrator' and $mainframe->isAdmin())
            JToolBarHelper::preferences('com_cls', '550', '570', 'Settings');

        if(JRequest::getCmd('c', 'complaints') == 'complaints')
            JToolBarHelper::help('screen.cls', true);
        elseif(JRequest::getCmd('c', 'complaints') == 'contracts')
            JToolBarHelper::help('screen.cls.contracts', true);
        elseif(JRequest::getCmd('c', 'complaints') == 'areas')
            JToolBarHelper::help('screen.cls.areas', true);
        elseif(JRequest::getCmd('c', 'complaints') == 'sections')
            JToolBarHelper::help('screen.cls.sections', true);
        elseif(JRequest::getCmd('c', 'complaints') == 'SupportGroups')
            JToolBarHelper::help('screen.cls.supportgroups', true);
    }
}