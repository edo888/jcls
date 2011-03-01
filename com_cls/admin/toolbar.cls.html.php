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
        $user_type = $user->getParam('role', 'Viewer');

        if(JRequest::getCmd('task') == 'edit' or JRequest::getCmd('task') == 'add') {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' ]</small></small>');
            if($user_type != 'Viewer')
                JToolBarHelper::save();
            if($text !== JText::_('New') and $user_type != 'Viewer' and $mainframe->isAdmin())
                JToolBarHelper::apply();
            if($cid[0])
                JToolBarHelper::cancel('cancel', 'Close');
            else
                JToolBarHelper::cancel();

            JToolBarHelper::help('screen.cls.new', true);
        } elseif(JRequest::getCmd('task') == 'editContract' or JRequest::getCmd('task') == 'addContract') {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' Contract ]</small></small>');
            if($user_type != 'Viewer')
                JToolBarHelper::save('saveContract');
            if($text !== JText::_('New') and $user_type != 'Viewer')
                JToolBarHelper::apply('applyContract');
            if($cid[0])
                JToolBarHelper::cancel('cancelContract', 'Close');
            else
                JToolBarHelper::cancel('cancelContract');

            JToolBarHelper::help('screen.cls.contracts', true);
        } elseif(JRequest::getCmd('task') == 'editSection' or JRequest::getCmd('task') == 'addSection') {
            JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' Section ]</small></small>');
            if($user_type != 'Viewer')
                JToolBarHelper::save('saveSection');
            if($text !== JText::_('New') and $user_type != 'Viewer')
                JToolBarHelper::apply('applySection');
            if($cid[0])
                JToolBarHelper::cancel('cancelSection', 'Close');
            else
                JToolBarHelper::cancel('cancelSection');

            JToolBarHelper::help('screen.cls.sections', true);
        }
    }

    function _DEFAULT() {
        global $mainframe;
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Viewer');

        if(JRequest::getCmd('c', 'complaints') == 'complaints')
            JToolBarHelper::title(JText::_('Complaints'));
        elseif(JRequest::getCmd('c', 'complaints') == 'notifications')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Notifications ]</small></small>');
        elseif(JRequest::getCmd('c', 'complaints') == 'contracts')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Contracts ]</small></small>');
        elseif(JRequest::getCmd('c', 'complaints') == 'sections')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Sections ]</small></small>');
        else
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Reports ]</small></small>');

        if(JRequest::getCmd('c', 'complaints') == 'complaints' and $mainframe->isAdmin()) {
            if($user_type == 'Super User' or $user_type == 'Admin')
                JToolBarHelper::addNewX();
            JToolBarHelper::editListX();
            if($user_type == 'Super User')
                JToolBarHelper::deleteList();
        } elseif(JRequest::getCmd('c', 'complaints') == 'contracts') {
            if($user_type == 'Super User' or $user_type == 'Admin')
                JToolBarHelper::addNewX('addContract');
            JToolBarHelper::editListX('editContract');
            if($user_type == 'Super User')
                JToolBarHelper::deleteList('', 'removeContract');
        } elseif(JRequest::getCmd('c', 'complaints') == 'sections') {
            if($user_type == 'Super User' or $user_type == 'Admin')
                JToolBarHelper::addNewX('addSection');
            JToolBarHelper::editListX('editSection');
            if($user_type == 'Super User')
                JToolBarHelper::deleteList('', 'removeSection');
        }

        if($user_type == 'Super User' and $mainframe->isAdmin())
            JToolBarHelper::preferences('com_cls', '550', '570', 'Settings');

        if(JRequest::getCmd('c', 'complaints') == 'complaints')
            JToolBarHelper::help('screen.cls', true);
        elseif(JRequest::getCmd('c', 'complaints') == 'contracts')
            JToolBarHelper::help('screen.cls.contracts', true);
        elseif(JRequest::getCmd('c', 'complaints') == 'sections')
            JToolBarHelper::help('screen.cls.sections', true);
    }
}