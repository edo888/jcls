<?php
/**
* @version   $Id$
* @package   GCLS
* @copyright Copyright (C) 2010-2017 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');

class CLSFrontViewIncident_Reporting extends JViewLegacy {
    function display($tpl = null) {
        $session =& JFactory::getSession();

        $this->assignRef('session', $session);

        parent::display($tpl);
    }
}