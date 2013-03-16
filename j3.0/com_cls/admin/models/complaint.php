<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

// import Joomla modelform library
jimport('joomla.application.component.modeladmin');

class ClsModelComplaint extends JModelLegacy {

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   type    The table type to instantiate
     * @param   string  A prefix for the table class name. Optional.
     * @param   array   Configuration array for model. Optional.
     * @return  JTable  A database object
     * @since   1.6
     */
    public function getTable($type = 'Complaint', $prefix = 'ComplaintTable', $config = array()) {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Method to get the record form.
     *
     * @param   array   $data       Data for the form.
     * @param   boolean $loadData   True if the form is to load its own data (default case), false if not.
     * @return  mixed   A JForm object on success, false on failure
     * @since   1.6
     */
    public function editComplaint($req) {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $id = (int)$req['id'];

        $query = 'select c.*, e.name as editor, r.name as resolver, a.area as complaint_area, p.name as contract, s.name as support_group from #__complaints as c left join #__complaint_areas as a on (c.complaint_area_id = a.id) left join #__users as e on (c.editor_id = e.id) left join #__users as r on (c.resolver_id = r.id) left join #__complaint_contracts as p on (c.contract_id = p.id) left join #__complaint_support_groups as s on (c.support_group_id = s.id) where c.id = ' . $id;
        $db->setQuery($query);
        $row = $db->loadObject();

        if($row == NULL) {
            $row = new JObject;
        }
        // complaint pictures
        if($id != 0) {
            $query = 'select * from #__complaint_pictures where complaint_id = ' . $id;
            $db->setQuery($query);
            $row->pictures = $db->loadObjectList();
        }

        $row->id = (int)$row->id == '' ? 0 : $row->id;
        // notifications queue
        $query = "select * from #__complaint_message_queue where complaint_id = $row->id order by id desc";
        $db->setQuery($query);
        $row->notifications_queue = $db->loadObjectList();

        // activity log
        $query = "SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.description like '%#$row->message_id%' order by m.id desc limit 10";
        $db->setQuery($query);
        $row->activity_log = $db->loadObjectList();

        // area_id list
        $query = 'select * from #__complaint_areas';
        $db->setQuery($query);
        $areas = $db->loadObjectList();
        //$area[] = array('key' => '', 'value' => '- Select Area -');
        $area[] = array('key' => '', 'value' => '- Select Category -');
        foreach($areas as $a)
            $area[] = array('key' => $a->id, 'value' => $a->area);
        $lists['area'] = JHTML::_('select.genericlist', $area, 'complaint_area_id', null, 'key', 'value', $row->complaint_area_id);

        // contract_id list
        $query = 'select * from #__complaint_contracts';
        $db->setQuery($query);
        $contracts = $db->loadObjectList();
        $contract[] = array('key' => '', 'value' => '- Select Contract -');
        foreach($contracts as $a)
            $contract[] = array('key' => $a->id, 'value' => $a->name);
        $lists['contract'] = JHTML::_('select.genericlist', $contract, 'contract_id', null, 'key', 'value', $row->contract_id);

        // editor list
        $query = 'select * from #__users where params like "%\"role\":\"Level 1\"%" or params like "%\"role\":\"System Administrator\"%"';
        $db->setQuery($query);
        $editors = $db->loadObjectList();
        $editor[] = array('key' => '', 'value' => '- Select Editor -');
        foreach($editors as $e)
            $editor[] = array('key' => $e->id, 'value' => $e->name);
        $lists['editor'] = JHTML::_('select.genericlist', $editor, 'editor_id', null, 'key', 'value', $row->editor_id);

        // resolver list
        $query = 'select * from #__users where params like "%\"role\":\"Level 1\"%" or params like "%\"role\":\"System Administrator\"%"';
        $db->setQuery($query);
        $resolvers = $db->loadObjectList();
        $resolver[] = array('key' => '', 'value' => '- Select Resolver -');
        foreach($resolvers as $r)
            $resolver[] = array('key' => $r->id, 'value' => $r->name);
        $lists['resolver'] = JHTML::_('select.genericlist', $resolver, 'resolver_id', null, 'key', 'value', $row->resolver_id);

        // support groups list
        $query = 'select * from #__complaint_support_groups';
        $db->setQuery($query);
        $support_groups = $db->loadObjectList();
        $support_group[] = array('key' => '', 'value' => '- Select Support Group -');
        foreach($support_groups as $g)
            $support_group[] = array('key' => $g->id, 'value' => $g->name);
        $lists['support_group'] = JHTML::_('select.genericlist', $support_group, 'support_group_id', null, 'key', 'value', $row->support_group_id);

        // source list
        $lists['source'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Source -' ), array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Email', 'value' => 'Email'), array('key' => 'Website', 'value' => 'Website'), array('key' => 'Telephone Call', 'value' => 'Telephone Call'), array('key' => 'Personal Visit', 'value' => 'Personal Visit'), array('key' => 'Field Visit by Project Staff', 'value' => 'Field Visit by Project Staff'), array('key' => 'Other', 'value' => 'Other')), 'message_source', null, 'key', 'value', $row->message_source);

        // preferred contact list
        $lists['preferred_contact'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Contact Method -' ), array('key' => 'Email', 'value' => 'Email'), array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Telephone Call', 'value' => 'Telephone Call')), 'preferred_contact', null, 'key', 'value', $row->preferred_contact);

        // priority list
        //$lists['priority'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Priority -' ), array('key' => 'Low', 'value' => 'Low'), array('key' => 'Medium', 'value' => 'Medium'), array('key' => 'High', 'value' => 'High')), 'message_priority', null, 'key', 'value', $row->message_priority);

        $config = JComponentHelper::getParams('com_cls');
        JHTML::_('behavior.tooltip');

        $priority[0] = new stdClass();
        $priority[0]->value = 'Low';
        $priority[0]->text = JHTML::tooltip($config->get('low_priority_description', ''), JText::_('Low'), '', JText::_('Low'));
        $priority[1] = new stdClass();
        $priority[1]->value = 'Medium';
        $priority[1]->text = JHTML::tooltip($config->get('medium_priority_description', ''), JText::_('Medium'), '', JText::_('Medium'));
        $priority[2] = new stdClass();
        $priority[2]->value = 'High';
        $priority[2]->text = JHTML::tooltip($config->get('high_priority_description', ''), JText::_('High'), '', JText::_('High'));

        $lists['priority'] = JHTML::_('select.radiolist', $priority, 'message_priority', null, 'value', 'text', $row->message_priority);

        // confirmed_closed list
        $lists['confirmed'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Confirmation -' ), array('key' => 'Y', 'value' => 'Yes'), array('key' => 'N', 'value' => 'No')), 'confirmed_closed', null, 'key', 'value', $row->confirmed_closed);

        $_SESSION['lists'] = $lists;
        $_SESSION['row'] = $row;
        $_SESSION['user_type'] = $user_type;

    }

}
