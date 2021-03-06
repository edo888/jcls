<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

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
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Name', 'm.name', $listDirn, $listOrder); ?>
                    </th>
                    <th width="61%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Description', 'm.description', $listDirn, $listOrder); ?>
                    </th>
                    <th width="16%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Complaints Count', 'complaints_count', $listDirn, $listOrder); ?>
                    </th>
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

                $link        = JRoute::_('index.php?option=com_cls&task=supportgroup.edit&id='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                 <tr class="row<?php echo $i % 2; ?>">
                    <td>
                        <?php echo $i + 1; ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                     <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Support Group' ); ?>">
                            <?php echo $row->name; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->description; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo JRoute::_('index.php?option=com_cls&filter_support_group_id='. $row->id); ?>" title="<?php echo JText::_('View Complaints'); ?>"><?php echo $row->complaints_count; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6">
                        <?php echo $this->pagination->getListFooter(); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <?php
        $manifest_details = JInstaller::parseXMLInstallFile(JPATH_ADMINISTRATOR .'/components/com_cls/cls.xml');
        $version = $manifest_details['version'];
        ?>
        <div style="text-align:right;"><b>Version:</b> <?php echo $version; ?></div>

        <input type="hidden" name="view" value="supportgroups" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
        <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>