<?php
/**
 * Joomla! component sexypolling
 *
 * @version $Id: sexypolls.php 2012-04-05 14:30:25 svn $
 * @author 2GLux.com
 * @package Sexy Polling
 * @subpackage com_sexypolling
 * @license GNU/GPL
 *
 */

// no direct access
defined('_JEXEC') or die('Restircted access');

jimport('joomla.application.component.controlleradmin');

class ClsControllerComplaints extends JControllerAdmin
{
	/**
	 * Constructor.
	 *
	 * @param	array	$config	An optional associative array of configuration settings.
	 *
	 * @return	ContactControllerContacts
	 * @see		JController
	 * @since	1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->registerTask('unfeatured',	'featured');
	}


	/**
	 * Proxy for getModel.
	 *
	 * @param	string	$name	The name of the model.
	 * @param	string	$prefix	The prefix for the PHP class name.
	 *
	 * @return	JModel
	 * @since	1.6
	 */
	public function getModel($name = 'complaint', $prefix = 'ClsModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
	
		/**
		 * Method to save the submitted ordering values for records via AJAX.
		 *
		 * @return	void
		 *
		 * @since   3.0
		 */
		public function saveOrderAjax()
		{
			// Get the input
			$pks   = $this->input->post->get('cid', array(), 'array');
			$order = $this->input->post->get('order', array(), 'array');
			// Sanitize the input
			JArrayHelper::toInteger($pks);
			JArrayHelper::toInteger($order);
		
			// Get the model
			$model = $this->getModel();
		
			// Save the ordering
			$return = $model->saveorder($pks, $order);
		
			if ($return)
			{
				echo "1";
			}
		
			// Close the application
			JFactory::getApplication()->close();
		}
}
