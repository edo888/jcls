<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

JHTML::_('behavior.tooltip');

$config =& JComponentHelper::getParams('com_cls');
$raw_complaint_warning_period = (int) $config->get('raw_complaint_warning_period', 2);
$processed_complaint_warning_period = (int) $config->get('processed_complaint_warning_period', 4);

jimport('joomla.filter.output');
?>
<h3>Complaints List</h3>
<form action="<?php echo JURI::base(); ?>index.php?option=com_cls" method="post" name="adminForm">

<table>
    <tr>
        <td align="left" width="100%">
            <?php echo JText::_('Filter'); ?>:
            <input type="text" name="search" id="search" value="<?php echo $this->lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
            <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
            <!--button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button-->
        </td>
        <td nowrap="nowrap">
            <?php echo $this->lists['area']; ?>
            <?php echo $this->lists['source']; ?>
            <?php echo $this->lists['priority']; ?>
            <?php echo $this->lists['status']; ?>
        </td>
    </tr>
</table>

<div id="tablecell">
    <table class="adminlist">
    <thead>
        <tr>
            <th width="4">
                <?php echo JText::_('NUM'); ?>
            </th>
            <!--th width="1%" align="center">
                <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->rows ); ?>);" />
            </th-->
            <th width="20" class="title">
                <?php echo JHTML::_('grid.sort', 'Message ID', 'm.message_id', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th>
            <th width="10" align="center">
                <?php echo JHTML::_('grid.sort', 'Source', 'm.message_source', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th>
            <th width="30" align="center">
                <?php echo JHTML::_('grid.sort', 'Sender', 'sender', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th>
            <!--th width="6%" align="center">
                <?php echo JHTML::_('grid.sort', 'Received', 'm.date_received', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th-->
            <th width="20" align="center">
                <?php echo JHTML::_('grid.sort', 'Area', 'g.area', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th>
            <th width="15" align="center">
                <?php echo JHTML::_('grid.sort', 'Priority', 'm.message_priority', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th>
            <th width="15" align="center">
                <?php echo JHTML::_('grid.sort', 'Processed', 'm.date_processed', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th>
            <!--th width="6%" align="center">
                <?php echo JHTML::_('grid.sort', 'Editor', 'e.name', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th-->
            <th width="15" align="center">
                <?php echo JHTML::_('grid.sort', 'Resolved', 'm.date_resolved', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th>
            <!--th width="6%" align="center">
                <?php echo JHTML::_('grid.sort', 'Resolver', 'u.name', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th-->
            <!--th width="1%" nowrap="nowrap">
                <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$this->lists['order_Dir'], @$this->lists['order']); ?>
            </th-->
        </tr>
    </thead>
    <?php
    $k = 0;
    for($i=0, $n=count($this->rows); $i < $n; $i++) {
        $row = &$this->rows[$i];
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        if($row->date_processed == '' and $raw_complaint_warning_period*24*60*60 < time() - strtotime($row->date_received))
            JError::raiseNotice(0, 'Complaint #' . $row->message_id . ' is not processed yet.');
        if($row->confirmed_closed == 'N' and $row->date_processed != '' and $processed_complaint_warning_period*24*60*60 < time() - strtotime($row->date_processed))
            JError::raiseNotice(0, 'Complaint #' . $row->message_id . ' is not resolved yet.');

        $link        = JRoute::_('index.php?option=com_cls&task=edit&cid[]='. $row->id);
        $checked     = JHTML::_('grid.checkedout',$row,$i);
        ?>
        <tr class="<?php echo "row$k"; ?>">
            <td>
                <?php echo $this->pageNav->getRowOffset( $i ); ?>
            </td>
            <!--td align="center">
                <?php echo $checked; ?>
            </td-->
            <td align="center" nowrap="nowrap">
                <?php /*<a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Complaint' ); ?>">*/ ?>
                    <?php echo $row->message_id; ?><?php /*</a>*/ ?>
            </td>
            <td align="center" nowrap="nowrap">
                <?php echo $row->message_source; ?>
            </td>
            <td align="center">
                <?php echo $row->sender; ?>
            </td>
            <!--td align="center">
                <?php echo date('Y-m-d', strtotime($row->date_received)); ?>
            </td-->
            <td align="center" nowrap="nowrap">
                <?php echo $row->area; ?>
            </td>
            <td align="center" nowrap="nowrap">
                <?php echo $row->message_priority; ?>
            </td>
            <td align="center" nowrap="nowrap">
                <?php
                if($row->date_processed)
                    echo date('Y-m-d', strtotime($row->date_processed));
                ?>
            </td>
            <!--td align="center">
                <?php echo $row->editor; ?>
            </td-->
            <td align="center" nowrap="nowrap">
                <?php
                if($row->date_resolved)
                    echo date('Y-m-d', strtotime($row->date_resolved));
                ?>
            </td>
            <!--td align="center">
                <?php echo $row->resolver; ?>
            </td-->
            <!--td align="center">
                <?php echo $row->id; ?>
            </td-->
        </tr>
        <?php
        $k = 1 - $k;
    }
    ?>
    <tfoot>
        <td colspan="13">
            <?php echo $this->pageNav->getListFooter(); ?>
        </td>
    </tfoot>
    </table>
</div>

<input type="hidden" name="option" value="com_cls" />
<input type="hidden" name="view" value="complaints" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
<input type="hidden" name="filter_order_Dir" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>