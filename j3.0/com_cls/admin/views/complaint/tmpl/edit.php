<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

$row = $_SESSION['row'];
$lists = $_SESSION['lists'];
$user_type = $_SESSION['user_type'];

editComplaint($row, $lists, $user_type);

function editComplaint($row, $lists, $user_type) {
    jimport('joomla.filter.output');
    JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

    //add the links to the external files into the head of the webpage (note the 'administrator' in the path, which is not nescessary if you are in the frontend)
    $document =JFactory::getDocument();
    $document->addScript(JURI::base(true).'/components/com_cls/swfupload/swfupload.js');
    $document->addScript(JURI::base(true).'/components/com_cls/swfupload/swfupload.queue.js');
    $document->addScript(JURI::base(true).'/components/com_cls/swfupload/fileprogress.js');
    $document->addScript(JURI::base(true).'/components/com_cls/swfupload/handlers.js');
    $document->addStyleSheet(JURI::base(true).'/components/com_cls/swfupload/default.css');

    //when we send the files for upload, we have to tell Joomla our session, or we will get logged out
    $session = JFactory::getSession();

    $swfUploadHeadJs ='
    var swfu;

    window.onload = function() {
    var settings = {
    //this is the path to the flash file, you need to put your components name into it
    flash_url : "'.JURI::base(true).'/components/com_cls/swfupload/swfupload.swf",

    //we can not put any vars into the url for complicated reasons, but we can put them into the post...
    upload_url: "index.php",
    post_params: {
    "option" : "com_cls",
    "task" : "upload_picture",
    "id" : "'.$row->id.'",
    "'.$session->getName().'" : "'.$session->getId().'",
    "format" : "raw"
},
//you need to put the session and the "format raw" in there, the other ones are what you would normally put in the url
file_size_limit : "8 MB",
//client side file chacking is for usability only, you need to check server side for security
file_types : "*.jpg;*.jpeg;*.gif;*.png",
file_types_description : "Images only",
file_upload_limit : 20,
file_queue_limit : 20,
custom_settings : {
progressTarget : "fsUploadProgress",
cancelButtonId : "btnCancel"
},
debug: false,

// Button settings
button_image_url: "'.JURI::base(true).'/components/com_cls/swfupload/TestImageNoText_65x29.png",
button_width: "65",
button_height: "29",
button_placeholder_id: "spanButtonPlaceHolder",
button_text: \'<span class="theFont">Select</span>\',
button_text_style: ".theFont { font-size: 13; }",
button_text_left_padding: 5,
button_text_top_padding: 5,

// The event handler functions are defined in handlers.js
file_queued_handler : fileQueued,
file_queue_error_handler : fileQueueError,
file_dialog_complete_handler : fileDialogComplete,
upload_start_handler : uploadStart,
upload_progress_handler : uploadProgress,
upload_error_handler : uploadError,
upload_success_handler : uploadSuccess,
upload_complete_handler : uploadComplete,
queue_complete_handler : queueComplete     // Queue plugin event
};
swfu = new SWFUpload(settings);
};';

    //add the javascript to the head of the html document
    if((int)$row->id != 0)
        $document->addScriptDeclaration($swfUploadHeadJs);

    JHTML::_('behavior.modal');

    //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        Joomla.submitbutton = function(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'complaint.cancel') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.message_source && form.message_source.value == "")
                alert('Message Source is required');
            else if(form.name.value == "" && form.email.value == "" && form.phone.value == "" && form.address.value == "" && form.ip_address.value == "")
                alert('Sender is required');
            else if(form.raw_message && form.raw_message.value == "")
                alert('Raw message is required');
            else if(form.message_priority && form.message_priority.value == "")
                alert('Complaint priority is required');
            else
                submitform(pressbutton);
        }

        function actionChanged() {
            document.getElementById('action').value = document.getElementById('action_taken').value;
        }
        </script>

        <ul class="nav nav-tabs" id="myTabTabs">
            <li class="active"><a href="#details" data-toggle="tab"><?php echo JText::_('Details'); ?></a></li>
            <?php if((int)$row->id != 0): ?>
            <li class=""><a href="#pictures" data-toggle="tab"><?php echo JText::_('Pictures') . (count($row->pictures) ? ' (' .  count($row->pictures) . ')' : ''); ?></a></li>
            <li class=""><a href="#notifications" data-toggle="tab"><?php echo JText::_('Notifications'); ?></a></li>
            <?php if($user_type != 'Guest' and $user_type != 'Supervisor' and $user_type != 'Level 2'): ?><li class=""><a href="#activity_log" data-toggle="tab"><?php echo JText::_('Activity Log'); ?></a></li><?php endif; ?>
            <li class=""><a href="#actions" data-toggle="tab"><?php echo JText::_('Actions'); ?></a></li>
            <?php endif; ?>
        </ul>

        <div class="tab-content" id="myTabContent">

        <div id="details" class="tab-pane active">

        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Complainant Information'); ?></legend>
            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="name">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->name;
                    else
                        echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @$row->name, '" />';
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
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->email;
                    else
                        echo '<input class="inputbox" type="text" name="email" id="email" size="60" value="', @$row->email, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="phone">
                        <?php echo JText::_( 'Phone' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->phone;
                    else
                        echo '<input class="inputbox" type="text" name="phone" id="phone" size="60" value="', @$row->phone, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="address">
                        <?php echo JText::_( 'Address' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->address;
                    else
                        echo '<input class="inputbox" type="text" name="address" id="address" size="60" value="', @$row->address, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="ip_address">
                        <?php echo JText::_( 'Sender IP' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->id != 0 and ($row->confirmed_closed == 'Y' or $user_type != 'System Administrator'))
                        echo @$row->ip_address;
                    else
                        echo '<input class="inputbox" type="text" name="ip_address" id="ip_address" size="60" value="', @$row->ip_address, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <label for="gender">
                        <?php echo JText::_('Gender'); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1' and $user_type != 'Level 2'))
                        echo @$row->gender;
                    else
                        echo $lists['gender'];
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="preferred_contact">
                        <?php echo JText::_( 'Preferred Contact Method' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->preferred_contact;
                    else
                        echo $lists['preferred_contact'];
                    ?>
                </td>
            </tr>
            </table>

            <legend><?php echo JText::_('Complaint Information'); ?></legend>
            <table class="admintable">
            <?php if(property_exists($row, 'message_id')): ?>
            <tr>
                <td width="200" class="key">
                    <label for="message_id">
                        <?php echo JText::_( 'Message ID' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->message_id; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'date_received')): ?>
            <tr>
                <td width="200" class="key">
                    <label for="date_received">
                        <?php echo JText::_( 'Date Received' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_received; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->id == 0): ?>
            <tr>
                <td class="key">
                    <label for="date_received">
                        <?php echo JText::_( 'Date Received' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        JHTML::_('behavior.calendar');
                        echo JHTML::calendar(date('Y-m-d H:i:s'), 'date_received', 'date_received', '%Y-%m-%d');
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="key">
                    <label for="message_source">
                        <?php echo JText::_( 'Message Source' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->id != 0 and ($row->confirmed_closed == 'Y' or $user_type != 'System Administrator'))
                        echo @$row->message_source;
                    else
                        echo $lists['source'];
                    ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="raw_message">
                        <?php echo JText::_( 'Raw Message' ); ?>
                    </label>
                </td>
                <td>
                        <?php
                        if($row->id != 0 and ($row->confirmed_closed == 'Y' or $user_type != 'System Administrator'))
                            echo '<pre>', @$row->raw_message, '</pre>';
                        else
                            echo '<textarea name="raw_message" id="raw_message" cols="80" rows="5">', @$row->raw_message, '</textarea>';
                        ?>
                </td>
            </tr>
            <?php if(property_exists($row, 'processed_message')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="processed_message">
                        <?php echo JText::_( 'Processed Message' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo '<pre>', @$row->processed_message, '</pre>';
                    else
                        echo '<textarea name="processed_message" id="processed_message" cols="80" rows="5">', @$row->processed_message, '</textarea>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(isset($row->date_processed)): ?>
            <tr>
                <td class="key">
                    <label for="date_processed">
                        <?php echo JText::_( 'Date Processed' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_processed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'editor_id') and $row->date_processed != ''): ?>
            <tr>
                <td class="key">
                    <label for="editor_id">
                        <?php echo JText::_( 'Processed by' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or $user_type != 'System Administrator')
                        echo @$row->editor;
                    else
                        echo $lists['editor'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            </table>

            <legend><?php echo JText::_('Complaint Categorization'); ?></legend>
            <table class="admintable">
            <?php if(isset($row->related_to_pb)): ?>
            <tr>
                <td width="200" class="key">
                    <label for="related_to_pb">
                        <?php echo JText::_('Related to Project Benefits'); ?>
                    </label>
                </td>
                <td>
                    <?php
                        $related_to_pb[0] = new stdClass();
                        $related_to_pb[0]->value = 0;
                        $related_to_pb[0]->text = JText::_('JNo');
                        $related_to_pb[1] = new stdClass();
                        $related_to_pb[1]->value = 1;
                        $related_to_pb[1]->text = JText::_('JYes');

                        if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1' and $user_type != 'Level 2'))
                            echo @$related_to_pb[$row->related_to_pb]->text;
                        else
                            echo JHTML::_('select.radiolist', $related_to_pb, 'related_to_pb', null, 'value', 'text', $row->related_to_pb);
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(isset($row->issue_type)): ?>
            <tr>
                <td width="200" class="key">
                    <label for="issue_type">
                        <?php echo JText::_('Issue Type'); ?>
                    </label>
                </td>
                <td>
                    <?php
                        $issue_type[0] = new stdClass();
                        $issue_type[0]->value = 1;
                        $issue_type[0]->text = JText::_('Complaint');
                        $issue_type[1] = new stdClass();
                        $issue_type[1]->value = 2;
                        $issue_type[1]->text = JText::_('Grievance');

                        if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1' and $user_type != 'Level 2'))
                            echo @$issue_type[$row->issue_type]->text;
                        else
                            echo JHTML::_('select.radiolist', $issue_type, 'issue_type', null, 'value', 'text', $row->issue_type);
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'complaint_area_id')): ?>
            <tr>
                <td class="key">
                    <label for="complaint_area_id">
                        <?php
                        // echo JText::_( 'Complaint Area' );
                        echo JText::_( 'Complaint Category' );
                        ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->complaint_area;
                    else
                        echo $lists['area'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'support_group_id')): ?>
            <tr>
                <td class="key">
                    <label for="support_group_id">
                        <?php echo JText::_( 'Assign to Support Group' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->support_group;
                    else
                        echo $lists['support_group'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'contract_id')): ?>
            <tr>
                <td class="key">
                    <label for="contract_id">
                        <?php echo JText::_( 'Contract' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->contract;
                    else
                        echo $lists['contract'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'location')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="location">
                        <?php echo JText::_( 'Location where issue was identified' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo '<a href="index.php?option=com_cls&view=viewlocation&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">View Map</a>';
                    else
                        echo '<input type="hidden" name="location" id="location" value="', @$row->location, '" /><a href="index.php?option=com_cls&view=editlocation&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">'.( empty($row->location) ? 'Add Location' : 'Edit Location' ).'</a>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'message_priority')): ?>
            <tr>
                <td class="key">
                    <label for="priority">
                        <?php echo JText::_( 'Complaint Priority' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->message_priority;
                    else
                        echo $lists['priority'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            </table>

            <?php if($row->date_processed != ''): ?>
            <legend><?php echo JText::_('Resolution'); ?></legend>
            <table class="admintable">
            <?php if(property_exists($row, 'resolution') and $row->date_processed != ''): ?>
            <tr>
                <td width="200" class="key" valign="top">
                    <label for="resolution">
                        <?php echo JText::_( 'Resolution' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1' and $user_type != 'Level 2'))
                        echo @$row->resolution;
                    else
                        echo '<textarea name="resolution" id="resolution" cols="80" rows="3">', @$row->resolution, '</textarea>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(isset($row->date_resolved)): ?>
            <tr>
                <td width="200" class="key">
                    <label for="date_resolved">
                        <?php echo JText::_( 'Date Resolved' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_resolved; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'resolver_id') and $row->date_processed != ''): ?>
            <tr>
                <td class="key">
                    <label for="resolver_id">
                        <?php echo JText::_( 'Resolved by' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or $user_type != 'System Administrator')
                        echo @$row->resolver;
                    else
                        echo $lists['resolver'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'confirmed_closed') and $row->date_processed != '' and $row->date_resolved != ''): ?>
            <tr>
                <td width="200" class="key">
                    <label for="confirmed_closed">
                        <?php echo JText::_( 'Resolved and Closed' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1')) {
                        if($row->confirmed_closed == 'Y') {
                            $difference = strtotime($row->date_closed) - strtotime($row->date_received);

                            $days = floor($difference / 86400);
                            $difference = $difference - ($days * 86400);

                            $hours = floor($difference / 3600);
                            $difference = $difference - ($hours * 3600);

                            $minutes = floor($difference / 60);
                            $difference = $difference - ($minutes * 60);

                            $seconds = $difference;

                            if($days > 0)
                                $time_to_resolve = sprintf("%d %s %01d:%02d:%02d", $days, ($days == 1) ? "day" :  "days", $hours, $minutes, $seconds);
                            else
                                $time_to_resolve = sprintf("%01d:%02d:%02d", $hours, $minutes, $seconds);

                            echo 'On ', @$row->date_closed, ' and took <b>', $time_to_resolve, '</b>';
                        }
                        else
                            echo @$row->confirmed_closed;
                    }
                    else
                        echo $lists['confirmed'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            </table>
            <?php endif; ?>

            <legend><?php echo JText::_('Comments'); ?></legend>
            <table class="admintable">
            <?php if(property_exists($row, 'comments')): ?>
            <tr>
                <td width="200" class="key" valign="top">
                    <label for="comments">
                        <?php echo JText::_( 'Comments' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        if(isset($row->comments))
                            echo '<pre>', $row->comments, '</pre>';
                        if($row->confirmed_closed != 'Y' and $user_type != 'Guest') {
                            echo JText::_('Add your comment here'), ':<br />';
                            echo '<textarea name="comments" id="comments" cols="80" rows="3"></textarea>';
                        }
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="action" id="action" value="" />

        <input type="hidden" name="task" value="complaint.edit" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="controller" value="complaint" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        <?php echo JHtml::_('form.token'); ?>
        </form>

        </div>

        <div id="pictures" class="tab-pane">

        <?php if(isset($row->id) and count($row->pictures)): ?>
        <fieldset class="adminform">
            <legend><?php echo JText::_('Pictures'); ?></legend>
            <?php
            foreach($row->pictures as $i => $picture)
                echo '<a class="modal" href="'.JURI::base(true).'/'.$picture->path.'"><img src="'.JURI::base(true).'/'.$picture->path.'" border="0" alt="Picture #'.$i.'" style="max-height:150px;max-width:150px" /></a> ';
            ?>
        </fieldset>
        <div class="clr"></div>
        <?php endif; ?>

        <?php if((int)$row->id != 0): ?>
        <?php if($row->confirmed_closed != 'Y' and $user_type != 'Guest' and $user_type != 'Supervisor'): ?>
        <form id="form1" action="index.php" method="post" enctype="multipart/form-data">
        <fieldset class="adminform">
            <legend>Upload Picture</legend>
            <div class="fieldset flash" id="fsUploadProgress"><span class="legend">Upload Queue</span></div>
            <div id="divStatus">0 Files Uploaded</div>
                <div>
                    <span id="spanButtonPlaceHolder"></span>
                    <input id="btnCancel" type="button" value="Cancel All Uploads" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left:2px;font-size:8pt;height:29px;" />
                </div>
        </fieldset>
        </form>
        <?php endif; ?>
        <?php endif; ?>

        </div>

        <div id="notifications" class="tab-pane">

        <?php if((int)$row->id != 0): ?>
        <form action="index.php" method="post" name="notificationForm">
        <fieldset class="adminform">
            <legend><?php echo JText::_('Notifications'); ?></legend>

            <p><i>Save your changes before sending a notification.</i></p>

            <table class="admintable">
            <?php if($row->phone != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send acknowledgment notification by SMS' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("select status from #__complaint_message_queue where complaint_id = $row->id and msg_type = 'Acknowledgment'");
                    $status = $db->loadResult();
                    if($status == '')
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_acknowledge\';document.notificationForm.submit();">Click here</a>';
                    elseif($status == 'Pending' or $status == 'Outgoing')
                        echo JText::_('Message is in the queue');
                    elseif($status == 'Sent')
                        echo JText::_('Message is sent');
                    elseif($status == 'Failed')
                        echo 'Failed to send. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_acknowledge\';document.notificationForm.submit();">Click here</a> to try again.';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->email != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send acknowledgment notification by Email' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.action = 'Email acknowledgment sent' and m.description like '%#$row->message_id%' order by m.id desc limit 1");
                    $status = $db->loadObject();
                    if(!is_object($status))
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_email_acknowledge\';document.notificationForm.submit();">Click here</a> to send';
                    else
                        echo 'Sent by ' . $status->user . ' on ' . $status->date . '. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_email_acknowledge\';document.notificationForm.submit();">Click here</a> to send again';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Acknowledgment made in person' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.action = 'Acknowledgment made in person' and m.description like '%#$row->message_id%' order by m.id desc limit 1");
                    $status = $db->loadObject();
                    if(!is_object($status))
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_inperson_acknowledge\';document.notificationForm.submit();">Click here</a> to record';
                    else
                        echo 'Made by ' . $status->user . ' on ' . $status->date . '. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_inperson_acknowledge\';document.notificationForm.submit();">Click here</a> to record again';
                ?>
                </td>
            </tr>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Acknowledgment made by phone' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.action = 'Acknowledgment made by phone' and m.description like '%#$row->message_id%' order by m.id desc limit 1");
                    $status = $db->loadObject();
                    if(!is_object($status))
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_phone_acknowledge\';document.notificationForm.submit();">Click here</a> to record';
                    else
                        echo 'Made by ' . $status->user . ' on ' . $status->date . '. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_phone_acknowledge\';document.notificationForm.submit();">Click here</a> to record again';
                ?>
                </td>
            </tr>
            <?php if($row->phone != '' and $row->date_resolved != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send resolution notification by SMS' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("select status from #__complaint_message_queue where complaint_id = $row->id and msg_type = 'Resolved'");
                    $status = $db->loadResult();
                    if($status == '')
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_resolve\';document.notificationForm.submit();">Click here</a>';
                    elseif($status == 'Pending' or $status == 'Outgoing')
                        echo JText::_('Message is in the queue');
                    elseif($status == 'Sent')
                        echo JText::_('Message is sent');
                    elseif($status == 'Failed')
                        echo 'Failed to send. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_resolve\';document.notificationForm.submit();">Click here</a> to try again.';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->email != '' and $row->date_resolved != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send resolution notification by Email' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.action = 'Email resolution acknowledgment sent' and m.description like '%#$row->message_id%' order by m.id desc limit 1");
                    $status = $db->loadObject();
                    if(!is_object($status))
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_email_resolve\';document.notificationForm.submit();">Click here</a> to send';
                    else
                        echo 'Sent by ' . $status->user . ' on ' . $status->date . '. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_email_resolve\';document.notificationForm.submit();">Click here</a> to send again';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->date_resolved != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Resolution acknowledgment made in person' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.action = 'Resolution acknowledgment made in person' and m.description like '%#$row->message_id%' order by m.id desc limit 1");
                    $status = $db->loadObject();
                    if(!is_object($status))
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_inperson_resolve\';document.notificationForm.submit();">Click here</a> to record';
                    else
                        echo 'Made by ' . $status->user . ' on ' . $status->date . '. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_inperson_resolve\';document.notificationForm.submit();">Click here</a> to record again';
                ?>
                </td>
            </tr>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Resolution acknowledgment made by phone' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =JFactory::getDBO();
                    $db->setQuery("SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.action = 'Resolution acknowledgment made by phone' and m.description like '%#$row->message_id%' order by m.id desc limit 1");
                    $status = $db->loadObject();
                    if(!is_object($status))
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_phone_resolve\';document.notificationForm.submit();">Click here</a> to record';
                    else
                        echo 'Made by ' . $status->user . ' on ' . $status->date . '. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_phone_resolve\';document.notificationForm.submit();">Click here</a> to record again';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            </table><br />

            <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Type'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Msg From'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Msg To'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Date'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Status'); ?>
                    </th>
                    <th width="49%" align="left">Msg</th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($row->notifications_queue); $i < $n; $i++) {
                $row_i = &$row->notifications_queue[$i];
                JFilterOutput::objectHTMLSafe($row_i, ENT_QUOTES);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $i+1; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->msg_type; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->msg_from; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->msg_to; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->date_created; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->status; ?>
                    </td>
                    <td align="left">
                        <?php echo $row_i->msg; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            </table>
            </div>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        </form>
        <?php endif; ?>

        </div>

        <div id="activity_log" class="tab-pane">

        <?php if((int)$row->id != 0): ?>
        <?php if($user_type != 'Guest' and $user_type != 'Supervisor' and $user_type != 'Level 2'): ?>
        <fieldset class="adminform">
            <legend><?php echo JText::_('Activity Log'); ?></legend>
            <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('User'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Action'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Date'); ?>
                    </th>
                    <th width="68%" align="left"><?php echo JText::_('Description'); ?></th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($row->activity_log); $i < $n; $i++) {
                $row_i = &$row->activity_log[$i];
                JFilterOutput::objectHTMLSafe($row_i, ENT_QUOTES);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $i+1; ?>
                    </td>
                    <td align="center">
                        <?php if($row_i->user_id == 0) echo 'System'; else echo $row_i->user; ?>
                    </td>
                    <td align="center" nowrap>
                        <?php echo $row_i->action; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->date; ?>
                    </td>
                    <td align="left">
                        <?php echo $row_i->description; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            </table><br />
            <a href="index.php?option=com_cls&view=notifications&filter_search=<?php echo $row->message_id ?>" target="_blank"><?php echo JText::_('View full log'); ?></a>
        </div>
        </fieldset>
        <?php endif; ?>
        <?php endif; ?>

        </div>

        <div id="actions" class="tab-pane">
        <?php if((int)$row->id != 0): ?>
            <?php
            if($row->confirmed_closed != 'Y' and $user_type != 'Guest' and $user_type != 'Supervisor') {
                echo JText::_('Summary of action taken'), ':<br />';
                echo '<textarea name="action_taken" id="action_taken" onchange="actionChanged()" cols="70" rows="12" style="width:507px;"></textarea>';
            }
            ?>

            <fieldset class="adminform">
                <legend><?php echo JText::_('Actions Taken'); ?></legend>
                <div id="tablecell">
                <table class="adminlist">
                <thead>
                    <tr>
                        <th width="1%">
                            <?php echo JText::_('NUM'); ?>
                        </th>
                        <th width="10%" align="center">
                            <?php echo JText::_('User'); ?>
                        </th>
                        <th width="10%" align="center">
                            <?php echo JText::_('Date'); ?>
                        </th>
                        <th width="68%" align="left"><?php echo JText::_('Description'); ?></th>
                    </tr>
                </thead>
                <?php
                $k = 0;
                for($i=0, $n=count($row->actions_taken); $i < $n; $i++) {
                    $row_i = &$row->actions_taken[$i];
                    JFilterOutput::objectHTMLSafe($row_i, ENT_QUOTES);
                    ?>
                    <tr class="<?php echo "row$k"; ?>">
                        <td>
                            <?php echo $i+1; ?>
                        </td>
                        <td align="center">
                            <?php if($row_i->user_id == 0) echo 'System'; else echo $row_i->user; ?>
                        </td>
                        <td align="center">
                            <?php echo $row_i->date; ?>
                        </td>
                        <td align="left">
                            <?php echo $row_i->description; ?>
                        </td>
                    </tr>
                    <?php
                    $k = 1 - $k;
                }
                ?>
                </table><br />
                <a href="index.php?option=com_cls&view=notifications&filter_search=<?php echo $row->message_id ?>" target="_blank"><?php echo JText::_('View full log'); ?></a>
            </div>
            </fieldset>
        <?php endif; ?>
        </div>

        </div>

        <div class="clr"></div>
    <?php
    }
?>