<?php
// $Id: secsignidauth.php,v 1.3 2015/04/09 13:48:17 titus Exp $

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('joomla.error.log');
jimport('joomla.log.log');


/**
 * SecSignID Authentication Plugin.
 *
 * @copyright    Copyright (C) 2014 SecSign Technologies Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt.
 */
class plgAuthenticationSecSignIdAuth extends JPlugin
{
    /**
     * This method should handle any authentication and report back to the subject
     *
     * @access    public
     * @param   array $credentials Array holding the user credentials
     * @param    array $options Array of extra options
     * @param    object $response Authentication response object
     * @return    boolean
     * @since 1.5
     */
    function onUserAuthenticate($credentials, $options, & $response)
    {
        // open the log file
        JLog::addLogger(array('text_file' => 'secsign.log'));

        // check if we have an authenticated SecSignID set by the component SecSignIdControllerSecSignId
        $authenticatedSecSignId = $credentials['authenticatedSecSignID'];
        $secSignIDAuthCalled = array_key_exists("secSignIDAuthCalled", $credentials);

        if (!$secSignIDAuthCalled) {
            return;
        }

        JLog::add('onUserAuthenticate authenticatedSecSignId=' . $authenticatedSecSignId, JLog::INFO, 'secsign');

        if (($authenticatedSecSignId != NULL) && (strlen($authenticatedSecSignId) > 0)) {
            $message = '';
            $success = 0;
            $exception = false;

            // map the authenticated SecSignID to a Joomla username
            $joomlaUserName = '';

            // Load the profile data from the database. 
            // The user must have added a SecSignID to his profile using the SecSignID_profile plug-in.
            $db = JFactory::getDbo();

            // check if table exists
            try {
                $db->setQuery("SELECT joomla_user_id, joomla_user, secsignid FROM #__secsignid_login WHERE #__secsignid_login.secsignid = '" . $authenticatedSecSignId . "'");
                $results = $db->loadRowList();
            } catch (JException $e) {
                $exception = true;
                $message = $e->getMessage();
            }

            // Check for a database error.
            if (!$exception) {
                if ($db->getErrorNum()) {
                    $message = 'Could not search the data base for a user with SecSignID "' . $authenticatedSecSignId .
                        '": ' . $db->getErrorMsg();
                    $session = JFactory::getSession();
                    $session->set('secsignerror', $message);
                    JLog::add($message, JLog::ERROR, 'secsign');
                } else {
                    if (NULL == $results) {
                        // no Joomla user has added this authenticated SecSignID to his profile

                        $lang =& JFactory::getLanguage();
                        $lang->load('com_secsignid',JPATH_ADMINISTRATOR);
                        $message = JText::_('PLG_AUTH_SECSIGNID_ERROR_NOUSER1')." ". $$authenticatedSecSignId ." ".JText::_('PLG_AUTH_SECSIGNID_ERROR_NOUSER2');
                        $session = JFactory::getSession();
                        $session->set('secsignerror', $message);
                        JLog::add($message, JLog::WARNING, 'secsign');
                    } else {
                        if (count($results) != 1) {
                            $message = 'Found ' . count($results) . ' users with SecSignID "' . $authenticatedSecSignId . '" in the data base.';
                            JLog::add($message, JLog::WARNING, 'secsign');
                        } else {
                            // the only result line contains the Joomla user name who has added the authenticated SecSignID to his profile
                            $joomlaUserName = $results[0][1];
                        }
                    }
                }
            }

            if (NULL != $joomlaUserName) {
                JLog::add('Authenticated SecSignID=' . $authenticatedSecSignId . ' mapped to Joomla user name=' . $joomlaUserName, JLog::INFO, 'secsign');
                $message = JText::_('JGLOBAL_AUTH_ACCESS_GRANTED');
                $success = 1;
            }

            $response->type = 'SecSignID';
            if ($success == 1) {
                JLog::add('SecSignID login of Joomla user ' . $joomlaUserName . ' OK.', JLog::INFO, 'secsign');

                $response->status = JAuthentication::STATUS_SUCCESS;
                $response->error_message = '';
                $response->username = $joomlaUserName;
                //$response->fullname = $joomlaUserName;
            } else {
                JLog::add('SecSignID login denied.', JLog::INFO, 'secsign');

                $response->status = JAuthentication::STATUS_FAILURE;
                $response->error_message = $message;
            }
        } else {
            $response->status = JAuthentication::STATUS_FAILURE;
            //$response->error_message	= JText::sprintf('JGLOBAL_AUTH_FAILED', "Given SecSign ID username is null or empty.");
            $response->error_message = JText::_("Given SecSign ID username is null or empty.");

        }
    }
}
