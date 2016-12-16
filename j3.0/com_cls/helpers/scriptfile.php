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
        if(copy('components/com_users/models/forms/user.xml', 'components/com_users/models/forms/user.xml.bak'))
            echo '<p>'.JText::_('Joomla user parameters file backed up successfully').'</p>';
        else
            echo '<p>'.JText::_('Joomla user parameters file failed to be backed up').'</p>';
        
        // update user.xml file
        if(file_put_contents('components/com_users/models/forms/user.xml', file_get_contents(dirname(__FILE__).'/user_extend.xml')))
            echo '<p>'.JText::_('Joomla user parameters extended successfully').'</p>';
        else
            echo '<p>'.JText::_('Joomla user parameters failed to be extended').'</p>';
        
        // installing plugin
        $plugin_installer = new JInstaller;
        if($plugin_installer->install(dirname(__FILE__).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'plg_cls_users'))
            echo '<p>'.JText::_('CLS Users plugin installed successfully').'</p>';
        else
            echo '<p>'.JText::_('CLS Users plugin installation failed').'</p>';
        
        // enabling plugin
        $db = JFactory::getDBO();
        $db->setQuery('update #__extensions set enabled = 1 where element = "cls_users" and folder = "system"');
        $db->query();
    }

    /**
     * method to uninstall the component
     *
     * @return void
     */
    function uninstall($parent) {
        // todo: revert user.xml file
        
        $db = JFactory::getDBO();
        
        // uninstalling plugin
        $plugin_installer = new JInstaller;
        $db->setQuery("select extension_id from #__extensions where name = 'System - CLS Users' and type = 'plugin' and element = 'cls_users'");
        $cis_plugin = $db->loadObject();
        if($plugin_installer->uninstall($cis_plugin->extension_id))
            echo '<p>'.JText::_('CLS Users plugin uninstalled successfully').'</p>';
        else
            echo '<p>'.JText::_('CLS Users plugin uninstallation failed').'</p>';
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