<?php
/**
 * Joomla! component sexypolling
 *
 * @version $Id: view.html.php 2012-04-05 14:30:25 svn $
 * @author 2GLux.com
 * @package Sexy Polling
 * @subpackage com_sexypolling
 * @license GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restircted access');

// Import Joomla! libraries
jimport( 'joomla.application.component.view');


class ClsViewSupportGroups extends JViewLegacy {
	
	protected $items;
	protected $pagination;
	protected $state;
	
	/**
	 * Display the view
	 *
	 * @return	void
	 */
    public function display($tpl = null) {
    	
    	$user = JFactory::getUser();
    	$user_type = $user->getParam('role', 'Guest');
    	$user_type = $user->getParam('role', 'System Administrator');
    	 
    	// guest cannot see this list
    	 if($user_type == 'Guest' or $user_type == 'Level 2') {
    		$app = JFactory::getApplication();
    		$app->redirect('index.php?option=com_cls&view=reports');
    		return;
    	}
    	
    	$this->items		= $this->get('Items');
    	$this->pagination	= $this->get('Pagination');
    	$this->state		= $this->get('State');
    	
       		
       	$this->addToolbar();
       	$this->sidebar = JHtmlSidebar::render();
		parent::display($tpl);
    }
    
    /**
     * Add the page title and toolbar.
     *
     * @since	1.6
     */
	protected function addToolbar()
	{
		$mainframe = JFactory::getApplication();
		$user = JFactory::getUser();
		$user_type = $user->getParam('role', 'Guest');
		$user_type = $user->getParam('role', 'System Administrator');
		
            if($user_type == 'System Administrator' or $user_type == 'Level 1')
                JToolBarHelper::addNew('supportgroup.add');
            JToolBarHelper::editList('supportgroup.edit');
            if($user_type == 'System Administrator')
                JToolBarHelper::deleteList('', 'supportgroup.remove');
	    
		if($user_type == 'System Administrator' and $mainframe->isAdmin())
            JToolBarHelper::preferences('com_cls', '550', '570', 'Settings');
		
		 JToolBarHelper::help('screen.cls.supportgroups', true);
		JToolBarHelper::divider();
	}
	
	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 *
	 * @since   3.0
	 */
	protected function getSortFields()
	{
		return array(
				'm.name' => JText::_('Name'),
				'm.description' => JText::_('Description'),
				'm.id' => JText::_('id')
		);
	}
	
}