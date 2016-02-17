<?php
// $Id: secsignidprofile.php,v 1.2 2014/12/01 15:04:28 titus Exp $

defined('JPATH_BASE') or die;

jimport('joomla.error.log');
jimport('joomla.log.log');

/**
 * This plug-in allows users to assign a SecSignID to their profile.
 *
 * @copyright	Copyright (C) 2014 SecSign Technologies Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt.
 */
class plgUserSecSignIdProfile extends JPlugin
{
    /**
     * @param	string	$context	The context for the data
     * @param	int		$data		The user id
     * @param	object
     *
     * @return	boolean
     * @since	1.6
     */
    function onContentPrepareData($context, $data)
    {
        // open the log file
        JLog::addLogger(array('text_file' => 'secsign.log'));
		
        // Check we are manipulating a valid form.
        if (!in_array($context, array('com_users.profile','com_users.user', 'com_users.registration', 'com_admin.profile'))) 
        {
            return true;
        }

        if (is_object($data))
        {
            $userId = isset($data->id) ? $data->id : 0;

            // Load the profile data from the database.
            $db = JFactory::getDbo();
            
            try {
                /*$db->setQuery(
                   'SELECT profile_key, profile_value FROM #__user_profiles' .
                   ' WHERE user_id = '.(int) $userId." AND profile_key LIKE 'secsignidprofile.%'" .
                   ' ORDER BY ordering');*/

                $db->setQuery(
                   'SELECT joomla_user_id, secsignid FROM #__secsignid_login' .
                   ' WHERE joomla_user_id = '.(int) $userId.
                   ' ORDER BY secsignid');
            }
            catch (JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }


            $results = $db->loadRowList();

            // Check for a database error.
            if ($db->getErrorNum())
            {
                $dbErrMsg = $db->getErrorMsg();
                $this->_subject->setError($dbErrMsg);
                JLog::add('SecSignID user profile data base error: ' . $dbErrMsg, JLog::ERROR   , 'secsign');

                return false;
            }

            // Merge the profile data.
            $data->secsignidprofile = array(); 
            
            $secsignid_array = array();
            foreach ($results as $v) {
                // $k = str_replace('secsignidprofile.', '', $v[0]);
                // $data->secsignidprofile['secsignid'] .= $v[1]; 
                
                $secsignid_array[] = $v[1];
            }    
                  
            $data->secsignidprofile['secsignid'] = implode(', ', $secsignid_array);
        }

        return true;
    }

    /**
     * @param	JForm	$form	The form to be altered.
     * @param	array	$data	The associated data for the form.
     *
     * @return	boolean
     * @since	1.6
     */
    function onContentPrepareForm($form, $data)
    {
        // open the log file
        JLog::addLogger(array('text_file' => 'secsign.log'));

        // Load user_profile plugin language
        $lang = JFactory::getLanguage();
        $lang->load('plg_user_secsign_profile', JPATH_ADMINISTRATOR);

        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        // Check we are manipulating a valid form.
        if (!in_array($form->getName(), array('com_admin.profile','com_users.user', 'com_users.registration','com_users.profile'))) 
        {
            return true;
        }

        // Add the registration fields to the form.
        JForm::addFormPath(dirname(__FILE__).'/profiles');
        $form->loadFile('profile', false);
        //$form->setFieldAttribute('secsign', 'required', $this->params->get('require_secsign') == 2, 'secsignidprofile');

        return true;
    }

    function onUserAfterSave($data, $isNew, $result, $error)
    {
        // open the log file
        JLog::addLogger(array('text_file' => 'secsign.log'));

        $userId	= JArrayHelper::getValue($data, 'id', 0, 'int');
        $userName = JArrayHelper::getValue($data, 'username', 0, 'string');
        
        if ($userId && $result && isset($data['secsignidprofile']) && (sizeof($data['secsignidprofile']) > 0))
        {
            try
            {
                $db = JFactory::getDbo();
                
                $secsignid_list = $data['secsignidprofile']['secsignid'];
                $secsignid_array = array();
                
                if(strpos($secsignid_list, ",") === false){
                    // just a single element
                    if(strlen($secsignid_list) > 0){
                        array_push($secsignid_array, $secsignid_list);
                    }
                } else {
                    $helparray = explode(",", $secsignid_list);
                    foreach($helparray as $s){
                        array_push($secsignid_array, trim($s));
                    }
                }
                
                $tuples = array();
                foreach($secsignid_array as $secsignid){
                    if(strlen($secsignid) > 0){
                        $tuples[] = $db->quote($secsignid);
                    }
                }
                
                if(sizeof($tuples) > 0){
                    // check if a joomla user id exists which already has been assigned this secsignids
                    try {
                        $db->setQuery('SELECT joomla_user_id, joomla_user, secsignid FROM #__secsignid_login WHERE secsignid IN (' . implode(',', $tuples) . ')');
                    }
                    catch (JException $e)
                    {
                        $this->_subject->setError($e->getMessage());
                        return false;
                    }
                    
                    $results = $db->loadRowList();

                    foreach($results as $r){
                        if($r[0] != $userId){
                            $this->_subject->setError("The SecSign ID '" . $r[2] . "' was already assigned to joomla user '" . $r[1] . "'.");
                            JLog::add("The SecSign ID '" . $r[2] . "' was already assigned to joomla user '" . $r[1] . "'.", JLog::INFO, 'secsign');
                        
                            throw new Exception("The SecSign ID '" . $r[2] . "' was already assigned to joomla user '" . $r[1] . "'.");
                        }
                    }
                    
                
                                                
                    /*$db->setQuery(
                            'DELETE FROM #__user_profiles WHERE user_id = '.$userId .
                                " AND profile_key LIKE 'secsignidprofile.%'");*/
                            
                    /*$db->setQuery(
                            'DELETE FROM #__secsignid_login WHERE joomla_user_id = '.$userId .
                                " AND secsignid = '" . $secsignid . "'");*/
                            
                    $db->setQuery('DELETE FROM #__secsignid_login WHERE joomla_user_id = '.$userId);

                    if (!$db->query()) 
                    {
                        throw new Exception($db->getErrorMsg());
                    }

                    $tuples = array();
                    $order	= 1;

                    foreach($secsignid_array as $secsignid){
                        if(strlen($secsignid) > 0){
                            $tuples[] = '('.$userId.', '.$db->quote($userName).', '.$db->quote($secsignid).')';
                        }
                    }

                    $db->setQuery('INSERT INTO #__secsignid_login VALUES '.implode(', ', $tuples));

                    if (!$db->query()) 
                    {
                        throw new Exception($db->getErrorMsg());
                    }
                } else {
                    // no values at all...
                    try
                    {
                        // user has deleted textfield value. so just delete secsign ids
                        $db = JFactory::getDbo();
                        $db->setQuery('DELETE FROM #__secsignid_login WHERE joomla_user_id = '.$userId);
                        if (!$db->query()) 
                        {
                            throw new Exception($db->getErrorMsg());
                        }
                    }
                    catch (JException $e)
                    {
                        $this->_subject->setError($e->getMessage());
                        return false;
                    }
                }
            }
            catch (JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }

    /**
     * Remove all user profile information for the given user ID
     *
     * Method is called after user data is deleted from the database
     *
     * @param	array		$user		Holds the user data
     * @param	boolean		$success	True if user was succesfully stored in the database
     * @param	string		$msg		Message
     */
    function onUserAfterDelete($user, $success, $msg)
    {
        if (!$success) 
        {
            return false;
        }

        $userId	= JArrayHelper::getValue($user, 'id', 0, 'int');

        if ($userId)
        {
            try
            {
                $db = JFactory::getDbo();
                /*$db->setQuery(
                        'DELETE FROM #__user_profiles WHERE user_id = '.$userId .
                           " AND profile_key LIKE 'secsignidprofile.%'");*/
                $db->setQuery('DELETE FROM #__secsignid_login WHERE joomla_user_id = '.$userId);

                if (!$db->query()) 
                {
                    throw new Exception($db->getErrorMsg());
                }
            }
            catch (JException $e)
            {
                $this->_subject->setError($e->getMessage());
                return false;
            }
        }

        return true;
    }
}
