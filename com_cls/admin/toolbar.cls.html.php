<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die('Restricted access');

class TOOLBAR_CLS {

    function _EDIT($cid)    {
        $cid = JRequest::getVar('cid',array(0));

        $text = ( $cid[0] ? JText::_( 'Edit' ) : JText::_( 'New' ) );

        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Viewer');

        JToolBarHelper::title(JText::_('Complaint').': <small><small>[ ' . $text . ' ]</small></small>');
        if($user_type != 'Viewer')
            JToolBarHelper::save();
        if($text !== JText::_('New') and $user_type != 'Viewer')
            JToolBarHelper::apply();
        if($cid[0])
            JToolBarHelper::cancel('cancel', 'Close');
        else
            JToolBarHelper::cancel();

        JToolBarHelper::help('screen.cls.new', true);
    }

    function _DEFAULT() {
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Viewer');

        if(JRequest::getCmd('c', 'complaints') == 'complaints')
            JToolBarHelper::title(JText::_('Complaints'));
        elseif(JRequest::getCmd('c', 'complaints') == 'notifications')
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Notifications ]</small></small>');
        else
            JToolBarHelper::title(JText::_('Complaints') . ' <small><small>[ Reports ]</small></small>');

        if(JRequest::getCmd('c', 'complaints') == 'complaints') {
            if($user_type == 'Super User' or $user_type == 'Admin')
                JToolBarHelper::addNewX();
            JToolBarHelper::editListX();
            if($user_type == 'Super User')
                JToolBarHelper::deleteList();
        }

        if($user_type == 'Super User')
            JToolBarHelper::preferences('com_cls', '550', '570', 'Settings');

        JToolBarHelper::help('screen.cls', true);
    }
}