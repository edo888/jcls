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

class ClsViewsupportgroup extends JViewLegacy
{
	protected $form;
	protected $item;
	protected $state;

	/**
	 * Display the view
	 */
	public function display($tpl = null)
	{
		// Initialiase variables.

		$this->addToolbar();
		parent::display($tpl);
	}
	
	protected function addToolbar()
	{
		JRequest::setVar('hidemainmenu', true);
		
		$user		= JFactory::getUser();
		$userId		= $user->get('id');
		$isNew		= ((int)$_REQUEST['id'] == 0);
	
		$text = $isNew ? JText::_( 'New' ) : JText::_( 'Edit' );
		JToolBarHelper::title(   JText::_( 'Support Group' ).': <small><small>[ ' . $text.' ]</small></small>','manage.png' );
	
		// Build the actions for new and existing records.
		if ($isNew)  {
			JToolBarHelper::apply('supportgroup.apply');
			JToolBarHelper::save('supportgroup.save');
			JToolBarHelper::cancel('supportgroup.cancel');
		}
		else {
			JToolBarHelper::apply('supportgroup.apply');
			JToolBarHelper::save('supportgroup.save');
			JToolBarHelper::cancel('supportgroup.cancel','close');
		}
	}

}