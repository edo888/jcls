<?php
// no direct access
defined('_JEXEC') or die('Restircted access');

$id = (int)$_REQUEST['id'];

$db   = JFactory::getDBO();
$query = 'select c.*, s.name as section_name from #__complaint_contracts as c left join #__complaint_sections as s on (c.section_id = s.id) where c.id = ' . $id;
$db->setQuery($query);
$row = $db->loadObject();

// section list
$query = 'select * from #__complaint_sections';
$db->setQuery($query);
$sections = $db->loadObjectList();
$section[] = array('key' => '', 'value' => '- Select Location -');
foreach($sections as $a)
    $section[] = array('key' => $a->id, 'value' => $a->name);
$lists['section'] = JHTML::_('select.genericlist', $section, 'section_id', null, 'key', 'value', JRequest::getVar('section_id', $row->section_id));

$user = JFactory::getUser();
$user_type = $user->getParam('role', 'Guest');

editContract($row, $lists, $user_type);

function editContract($row, $lists, $user_type) {
    jimport('joomla.filter.output');
    JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

    JHTML::_('behavior.modal');

    //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        Joomla.submitbutton = function(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'contract.cancel') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.name && form.name.value == "")
                alert('Name is required');
            //else if(form.section_id && form.section_id.value == "")
                //alert('Section is required');
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
                    <label for="name">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @JRequest::getVar('name', $row->name), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="contract_id">
                        <?php echo JText::_( 'Contract ID' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="contract_id" id="contract_id" size="60" value="', @JRequest::getVar('contract_id', $row->contract_id), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="start_date">
                        <?php echo JText::_( 'Start Date' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        JHTML::_('behavior.calendar');
                        echo JHTML::calendar(@JRequest::getVar('start_date', $row->start_date), 'start_date', 'start_date', '%Y-%m-%d');
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="end_date">
                        <?php echo JText::_( 'Anticipated End Date' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        JHTML::_('behavior.calendar');
                        echo JHTML::calendar(@JRequest::getVar('end_date', $row->end_date), 'end_date', 'end_date', '%Y-%m-%d');
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="email">
                        <?php echo JText::_( 'Email' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="email" id="email" size="60" value="', @JRequest::getVar('email', $row->email), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="phone">
                        <?php echo JText::_( 'Phone' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="phone" id="phone" size="60" value="', @JRequest::getVar('phone', $row->phone), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="contractors">
                        <?php echo JText::_( 'Contractor(s)' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="contractors" id="contractors" cols="80" rows="5">', @JRequest::getVar('contractors', $row->contractors), '</textarea>'; ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <label for="section_id">
                        <?php echo JText::_( 'Location' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo $lists['section']; ?>
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
        <input type="hidden" name="c" value="contracts" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
         <?php echo JHtml::_('form.token'); ?>
        </form>
<?php } ?>