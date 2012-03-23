<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die('Restricted access');

require_once(JApplicationHelper::getPath('toolbar_html'));

switch($task) {
    case 'edit':
    case 'editArea':
    case 'editContract':
    case 'editSection':
    case 'editSupportGroup':
        $cid = JRequest::getVar('cid',array(0),'','array');
        if(!is_array($cid))
            $cid = array(0);
        TOOLBAR_CLS::_EDIT($cid[0]);
        break;

    case 'add':
    case 'addArea':
    case 'addContract':
    case 'addSection':
    case 'addSupportGroup':
    case 'editA':
        $id = JRequest::getVar('id',0,'','int');
        TOOLBAR_CLS::_EDIT($id);
        break;

    default:
        TOOLBAR_CLS::_DEFAULT();
        break;
}