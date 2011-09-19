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

class CLSViewComplain extends JView {
    function display($tpl = null) {
        CLSView::showToolbar();

        $session =& JFactory::getSession();

        $this->assignRef('session', $session);
        $this->assignRef('captcha', self::createCaptcha());

        parent::display($tpl);
    }

    function createCaptcha() {
        $secret = strtoupper(substr(sha1(mt_rand(0, 500).microtime()), 2, 5));
        $session =& JFactory::getSession();
        $session->set('cls_captcha', $secret);

        $img = imagecreatefromgif('components/com_cls/captcha.gif');
        $width  = imagesx($img);
        $height = imagesy($img);

        for($i = 0; $i < 5; $i++) {
            $color = imagecolorallocate($img, rand(0, 200), rand(0, 200), rand(0, 200));
            imagettftext($img, 12, mt_rand(-30, 30), 15 + $i * 12, 15, $color, 'components/com_cls/lsans.ttf', $secret{$i});
        }

        ob_start();
        imagegif($img);
        $img = ob_get_contents();
        ob_end_clean();

        return base64_encode($img);
    }
}