<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

$id = (int)$_REQUEST['id'];

$db   = JFactory::getDBO();
$query = 'select c.* from #__complaint_areas as c where c.id = ' . $id;
$db->setQuery($query);
$row = $db->loadObject();

$user = JFactory::getUser();
$user_type = $user->getParam('role', 'Guest');

$lists = array();
editArea($row, $lists, $user_type);

function editArea($row, $lists, $user_type) {
    jimport('joomla.filter.output');
    JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

    JHTML::_('behavior.modal');

    //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        Joomla.submitbutton = function(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'area.cancel') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.area && form.area.value == "")
                alert('Category name is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm" id="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="area">
                        <?php echo JText::_( 'Category Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="area" id="area" size="60" value="', @JRequest::getVar('area', $row->area), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="description">
                        <?php echo JText::_( 'Description' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @JRequest::getVar('description', $row->description), '</textarea>'; ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="view" value="area" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
         <?php echo JHtml::_('form.token'); ?>
        </form>
    <?php
    }
?>