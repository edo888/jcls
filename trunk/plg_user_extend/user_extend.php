<?php
/**
* @version   $Id$
* @package   User Extend
* @copyright Copyright (C) 2008 - 2010 Edvard Ananyan. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access');

/**
 * User Extend Plugin
 *
 */
class plgSystemUser_Extend extends JPlugin {
    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @access protected
     * @param  object $subject The object to observe
     * @param  array  $config  An array that holds the plugin configuration
     * @since  1.0
     */
    function plgSystemUser_Extend(& $subject, $config) {
        parent::__construct($subject, $config);
    }

    /**
     * Load the user parameters from custom xml
     *
     * @access public
     */
    function onAfterInitialise() {
        JUser::getParameters(false, JPATH_SITE.DS.'plugins'.DS.'system'.DS.'user_extend');
    }
}