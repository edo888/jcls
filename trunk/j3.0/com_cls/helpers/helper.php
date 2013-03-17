<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

class ClsHelper {

    /**
     * Configure the Linkbar.
     *
     * @param   string  $vName  The name of the active view.
     *
     * @return  void
     * @since   1.6
     */
    public static function addSubmenu($title, $v, $controller = null, $image = null) {
        $enabled = false;
        $view = JRequest::getWord("view", 'complaints');
        if($view == $v) {
            $img = $v;
            if($image != null) $img = $image;
            JToolBarHelper::title(JText::_($title), $img.'.png');
            $enabled = true;
        }
        $link = 'index.php?option=com_cls&view='.$v;
        if($controller != null) $link .= '&controller='.$controller;

        JHtmlSidebar::addEntry(JText::_($title), $link, $enabled);
    }
}