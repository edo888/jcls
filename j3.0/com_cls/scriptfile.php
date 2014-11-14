<?php
/**
 * Joomla! component sexypolling
 *
 * @version $Id: scriptfile.php 2012-04-05 14:30:25 svn $
 * @author 2GLux.com
 * @package Sexy Polling
 * @subpackage com_sexypolling
 * @license GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restircted access');

class com_clsInstallerScript {

    /**
     * method to install the component
     *
     * @return void
     */
    function install($parent) {
	    // backup old user.xml file
	    copy('components/com_users/models/forms/user.xml', 'components/com_users/models/forms/user.xml.bak');
	    
        // update user.xml file
        file_put_contents('components/com_users/models/forms/user.xml', file_get_contents(dirname(__FILE__).'/user_extend.xml'));
    }

    /**
     * method to uninstall the component
     *
     * @return void
     */
    function uninstall($parent) {
	    // todo: revert user.xml file
    }

    /**
     * method to update the component
     *
     * @return void
     */
    function update($parent) {
        $this->install($parent);
    }
}