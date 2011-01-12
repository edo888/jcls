<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');

class CLSViewNewComplaint extends JView {
    function display($tpl = null) {
        $session =& JFactory::getSession();
        $db      =& JFactory::getDBO();

        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Viewer');
        if($user_type != 'Viewer' and $user_type != 'Resolver' and $user_type != 'Auditor' and $user_type != 'Super User' and $user_type != 'Administrator') {
            JError::raiseWarning(403, 'You are not authorized to view this page.');
            return;
        }

        $document =& JFactory::getDocument();
        $document->addStyleSheet(JURI::base().'administrator/templates/khepri/css/general.css');

        // source list
        $lists['source'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Source -' ), array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Email', 'value' => 'Email'), array('key' => 'Website', 'value' => 'Website'), array('key' => 'Telephone Call', 'value' => 'Telephone Call'), array('key' => 'Personal Visit', 'value' => 'Personal Visit'), array('key' => 'Field Visit by Project Staff', 'value' => 'Field Visit by Project Staff'), array('key' => 'Other', 'value' => 'Other')), 'message_source', null, 'key', 'value', '');

        $this->assignRef('session', $session);
        $this->assignRef('lists', $lists);

        parent::display($tpl);
    }
}