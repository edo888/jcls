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
$query = 'select c.* from #__complaint_sections as c where c.id = ' . $id;
$db->setQuery($query);
$row = $db->loadObject();

$user = JFactory::getUser();
$user_type = $user->getParam('role', 'Guest');

$lists = array();

editSection($row, $lists, $user_type);

function editSection($row, $lists, $user_type) {
    jimport('joomla.filter.output');
    JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

    JHTML::_('behavior.modal');

    //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        Joomla.submitbutton = function(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'section.cancel') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.name && form.name.value == "")
                alert('Name is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="name">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @JRequest::getVar('name', $row->name), '" />'; ?>
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
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Tag on the map' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        if($user_type != 'System Administrator' and $user_type != 'Level 1')
                            echo '<a href="index.php?option=com_cls&view=viewsectionmap&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">View Map</a>';
                        else
                            echo '<input type="hidden" name="polygon" id="polygon" value="', @$row->polygon, '" /><input type="hidden" name="polyline" id="polyline" value="', @$row->polyline, '" /><a href="index.php?option=com_cls&view=editsectionmap&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">'.( (empty($row->polygon) and empty($row->polyline)) ? 'Add a tag' : 'Edit the tag' ).'</a>';
                    ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="c" value="section" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
         <?php echo JHtml::_('form.token'); ?>
        </form>
<?php } ?>