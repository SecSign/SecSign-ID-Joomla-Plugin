<?php
// $Id: secsignid.php,v 1.2 2014/12/01 15:04:28 titus Exp $

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
jimport('joomla.form.form');
jimport( 'joomla.version' );

/**
 * The output of this script is shown when the SecSignID menu item in the
 * components menu of the back end is selected.
 *
 * @copyright    Copyright (C) 2014 SecSign Technologies Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt.
 */

$lang = JFactory::getLanguage();
$help_url  = 'https://www.secsign.com/joomla-tutorial/';
if($lang->getTag() == "de-DE"){
    $help_url = 'https://www.secsign.com/de/joomla-tutorial-de/';
}
JToolBarHelper::help( 'MY_COMPONENT_HELP_VIEW_TYPE1', false, $help_url );

// Options button.
if (JFactory::getUser()->authorise('core.admin', 'com_secsignid')) {
    //menu value
    JToolBarHelper::title('SecSign ID');
    JToolBarHelper::preferences('com_secsignid', '500');

    $app = JFactory::getApplication();

    $version = new JVersion();
    if(intval(substr($version->getShortVersion(),0,1)) < 3){
        $data = JRequest::get('post');
    } else {
        $data = $app->input->post->getArray();
    }

    // Check if passwords are deactivated
    $globalPwDeactivated = JComponentHelper::getParams('com_secsignid')->get('secsign_mode');
    if ($globalPwDeactivated) {
        JFactory::getApplication()->enqueueMessage(JText::_('Passwords are globally deactivated in the SecSign ID component options panel.'), 'warning');
    }

    echo '<div id="secsigncom" style="width:90%;max-width:700px; margin: 20px auto;">';
    echo '<img src="' . JURI::root() . 'media/com_secsignid/images/secsignidlogo.png" style="margin: 20px auto;display: block;"><hr>';
    echo JText::_('COM_SECSIGNID_BACKEND_INFO');

    if ($data) {

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $table_secsignid_login = $db->quoteName('#__secsignid_login');
        $table_secsignid_pw = $db->quoteName('#__secsignid_pw');
        $columnPw = $db->quoteName("deactivate_password_login");
        $columnUserid = $db->quoteName("joomla_user_id");
        $columnsPw = $db->quoteName(array('joomla_user_id', 'deactivate_password_login'));
        $columnsLogin = $db->quoteName(array('joomla_user_id', 'joomla_user', 'secsignid'));

        //BACKUP BOTH TABLES (?)

        //truncate, and update pw deactivations
        $db->truncateTable('#__secsignid_pw');
        $result = $db->execute();

        if($data['deactivatepassword']) {
            $query
                ->insert($table_secsignid_pw)
                ->columns($columnsPw);

            //update all password deactivations with given IDs
            foreach ($data['deactivatepassword'] as $id) {
                $values = array($db->quote($id), 1);
                $query->values(implode(',', $values));
            }

            $db->setQuery($query);
            $result = $db->execute();
        }

        //truncate, and update secsignids
        if($data['secsignid']){
            $db->truncateTable('#__secsignid_login');
            $result = $db->execute();
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->insert($table_secsignid_login)->columns($columnsLogin);

            //update all SecSign IDs
            foreach ($data['secsignid'] as $id => $secsignid) {
                $secsignidlist = explode(',', $secsignid);
                foreach($secsignidlist as $singlesecsignid) {
                    $query->values(implode(',', array($db->quote($id), $db->quote($data['username'][$id]), $db->quote(str_replace(' ','',$singlesecsignid)))));
                }
            }
            $db->setQuery($query);
            $result = $db->execute();
        }

        // Check for a database error.
        if ($db->getErrorNum()) {
            echo '<p>' . 'Data base error when updating SecSignID table: ' . $db->getErrorMsg() . '</p>';
        } else {
            JFactory::getApplication()->enqueueMessage(JText::_('COM_SECSIGNID_SAVED'));
        }
    }

    //Get custom field
    JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
    $secsignlist = JFormHelper::loadFieldType('Secsignidusers', false);

    echo '
    <form action="' . JRoute::_('index.php?option=com_secsignid') . '" method="post" name="adminForm" id="adminForm">
			' . $secsignlist->getInput() . '
		    <button type="submit" class="btn hasTooltip" title="Submit">'.JText::_('COM_SECSIGNID_SAVE').'</button>
	</form>
    ';

} else {
    JFactory::getApplication()->enqueueMessage(JText::_('COM_SECSIGNID_NO_PERMISSION'), 'error');
}

echo '</div>
<style>
#secsigncom table{width: 100%;}
#secsigncom table td{text-align: center;}
#secsigncom table thead {background-color: #C5E7F8;font-weight: bold;color: #626769;}
#secsigncom table thead tr{height: 30px;}
#secsigncom table tr{height: 40px;}
#secsigncom button{width: 200px; margin: 30px 0 0 0;}
</style>';