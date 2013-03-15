<?php 
// no direct access
defined('_JEXEC') or die('Restircted access');

$id = (int)$_REQUEST['id'];
$db   = JFactory::getDBO();
        $query = 'select c.* from #__complaint_support_groups as c where c.id = ' . $id;
        $db->setQuery($query);
        $row = $db->loadObject();

        $query = "select u.id as user_id, u.name, c.group_id from #__users as u left join #__complaint_support_groups_users_map as c on (u.id = c.user_id and (c.group_id is null or c.group_id = $id)) where u.params like '%role=Level 2%'";
        $db->setQuery($query);
        if($row == null)
        	$row = new JObject;
        $row->users = $db->loadObjectList();

        foreach($row->users as $i => $u) {
            if($u->group_id != $id)
                $row->users[$i]->group_id = '';
        }

        $lists = array();
        
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

editSupportGroup($row, $lists, $user_type);        

        function editSupportGroup($row, $lists, $user_type) {
        	jimport('joomla.filter.output');
        	JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);
        
        	JHTML::_('behavior.modal');
        
        	//echo '<pre>', print_r($row, true), '</pre>';
        	?>
                <script language="javascript" type="text/javascript">
                Joomla.submitbutton = function(pressbutton) {
                    var form = document.adminForm;
                    if(pressbutton == 'supportgroup.cancel') {
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
                            <label for="alias">
                                <?php echo JText::_( 'Name' ); ?>
                            </label>
                        </td>
                        <td>
                            <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @$row->name, '" />'; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="key" valign="top">
                            <label for="path">
                                <?php echo JText::_( 'Description' ); ?>
                            </label>
                        </td>
                        <td>
                                <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @$row->description, '</textarea>'; ?>
                        </td>
                    </tr>
                    </table>
                </fieldset>
        
                <fieldset class="adminform">
                    <legend><?php echo JText::_('Users'); ?></legend>
        
                    <table class="admintable">
                    <tr>
                        <td width="200" class="key" valign="top">
                            <?php echo JText::_( 'User Selection' ); ?>
                        </td>
                        <td>
                            <select multiple="multiple" size="15" class="inputbox" id="users" name="users[]">
                                <?php
                                    foreach($row->users as $user) {
                                        if($user->group_id)
                                            echo '<option value="'.$user->user_id.'" selected>'.$user->name.'</option>';
                                        else
                                            echo '<option value="'.$user->user_id.'">'.$user->name.'</option>';
                                    }
                                ?>
                            </select>
                        </td>
                    </tr>
                    </table>
                </fieldset>
        
                <div class="clr"></div>
        
                <input type="hidden" name="task" value="" />
                <input type="hidden" name="view" value="SupportGroup" />
                <input type="hidden" name="option" value="com_cls" />
                <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
                <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
                <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
                <?php echo JHtml::_('form.token'); ?>
                </form>
            <?php
            }
?>