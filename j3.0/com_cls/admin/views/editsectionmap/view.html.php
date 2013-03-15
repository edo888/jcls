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

class ClsViewEditSectionMap extends JViewLegacy
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
		$user = JFactory::getUser();
		$user_type = $user->getParam('role', 'Guest');
		$user_type = $user->getParam('role', 'System Administrator');
		
		// guest cannot see this list
		 if($user_type == 'Guest') {
			$app = JFactory::getApplication();
			$app->redirect('index.php?option=com_cls&view=reports');
			return;
		}

		parent::display($tpl);
	}
}