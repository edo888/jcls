<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

$user       = JFactory::getUser();
$userId     = $user->get('id');
$listOrder  = $this->escape($this->state->get('list.ordering'));
$listDirn   = $this->escape($this->state->get('list.direction'));
$saveOrder  = $listOrder == 'sp.ordering';
$sortFields = $this->getSortFields();

$config = JComponentHelper::getParams('com_cls');
$raw_complaint_warning_period = (int) $config->get('raw_complaint_warning_period', 2);

// set separate warning periods for low, medium, high priorities
$action_period_low = (int) $config->get('action_period_low', 30);
$action_period_medium = (int) $config->get('action_period_medium', 10);
$action_period_high = (int) $config->get('action_period_high', 5);

jimport('joomla.filter.output');
?>
<script type="text/javascript">
    Joomla.orderTable = function() {
        table = document.getElementById("sortTable");
        direction = document.getElementById("directionTable");
        order = table.options[table.selectedIndex].value;
        if (order != '<?php echo $listOrder; ?>') {
            dirn = 'asc';
        } else {
            dirn = direction.options[direction.selectedIndex].value;
        }
        Joomla.tableOrdering(order, dirn, '');
    }
</script>
<form action="<?php echo JRoute::_('index.php?option=com_cls'); ?>" method="post" name="adminForm" id="adminForm">
<?php if(!empty( $this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
<?php else : ?>
    <div id="j-main-container">
<?php endif;?>
        <div id="filter-bar" class="btn-toolbar">
            <div class="filter-search btn-group pull-left">
                <label for="filter_search" class="element-invisible"><?php echo JText::_('Search');?></label>
                <input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('Search'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('Search'); ?>" />
            </div>
            <div class="btn-group pull-left">
                <button class="btn hasTooltip" type="submit" title="<?php echo JText::_('Search'); ?>"><i class="icon-search"></i></button>
                <button class="btn hasTooltip" type="button" title="<?php echo JText::_('Clear'); ?>" onclick="document.getElementById('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
            </div>
            <div class="btn-group pull-right hidden-phone">
                <label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
                <?php echo $this->pagination->getLimitBox(); ?>
            </div>
            <div class="btn-group pull-right hidden-phone">
                <label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC');?></label>
                <select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
                    <option value=""><?php echo JText::_('JFIELD_ORDERING_DESC');?></option>
                    <option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING');?></option>
                    <option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING');?></option>
                </select>
            </div>
            <div class="btn-group pull-right">
                <label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY');?></label>
                <select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
                    <option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
                    <?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder);?>
                </select>
            </div>
        </div>
        <div class="clearfix"> </div>
        <table class="table table-striped" id="articleList">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" class="hidden-phone">
                        <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
                    </th>
                    <th width="6%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Message ID', 'm.message_id', $listDirn, $listOrder); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Source', 'm.message_source', $listDirn, $listOrder); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Sender', 'sender', $listDirn, $listOrder); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Received', 'm.date_received', $listDirn, $listOrder); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php
                            // echo JHTML::_('grid.sort', 'Area', 'g.area', @$lists['order_Dir'], $listOrder);
                            echo JHTML::_('grid.sort', 'Category', 'g.area', $listDirn, $listOrder);
                        ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Priority', 'm.message_priority',$listDirn, $listOrder); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Processed', 'm.date_processed', $listDirn, $listOrder); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Processed by', 'e.name', $listDirn, $listOrder); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Resolved', 'm.date_resolved', $listDirn, $listOrder); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Resolved by', 'u.name', $listDirn, $listOrder); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', $listDirn, $listOrder); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
             <?php
            foreach ($this->items as $i => $row) :
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=complaint.edit&id='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);

                if($row->date_processed == '' and $raw_complaint_warning_period*24*60*60 < time() - strtotime($row->date_received))
                    JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not processed yet.');
                if($row->confirmed_closed == 'N' and $row->date_processed != '') {
                    switch($row->message_priority) {
                        case 'Low': if($action_period_low*24*60*60 < time() - strtotime($row->date_processed)) JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not resolved yet.'); break;
                        case 'Medium': if($action_period_medium*24*60*60 < time() - strtotime($row->date_processed)) JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not resolved yet.'); break;
                        case 'High': if($action_period_high*24*60*60 < time() - strtotime($row->date_processed)) JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not resolved yet.'); break;
                    }
                }
                ?>
                <tr class="row<?php echo $i % 2; ?>">
                    <td>
                        <?php echo $i + 1; ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Complaint' ); ?>">
                            <?php echo $row->message_id; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->message_source; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->sender; ?>
                    </td>
                    <td align="center">
                        <?php echo date('Y-m-d', strtotime($row->date_received)); ?>
                    </td>
                    <td align="center">
                        <?php echo $row->area; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->message_priority; ?>
                    </td>
                    <td align="center">
                        <?php
                        if($row->date_processed)
                            echo date('Y-m-d', strtotime($row->date_processed));
                        ?>
                    </td>
                    <td align="center">
                        <?php echo $row->editor; ?>
                    </td>
                    <td align="center">
                        <?php
                        if($row->date_resolved)
                            echo date('Y-m-d', strtotime($row->date_resolved));
                        ?>
                    </td>
                    <td align="center">
                        <?php echo $row->resolver; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="13">
                        <?php echo $this->pagination->getListFooter(); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <input type="hidden" name="view" value="complaints" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
        <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>