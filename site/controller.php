<?php
if (JDEBUG) {
    // http://php.net/manual/de/function.error-reporting.php
    error_reporting(E_ALL | E_STRICT);
    ini_set('dispay_errors', '1');
}

// $Id: controller.php,v 1.2 2014/12/01 15:04:28 titus Exp $

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controller library
jimport('joomla.application.component.controller');
jimport('joomla.error.log');
jimport('joomla.log.log');
jimport('joomla.application.component.helper');

include_once JPATH_ROOT . '/media/com_secsignid/SecSignIDApi/phpApi/SecSignIDApi.php';

/**
 * SecSign ID component controller that will request an auth session for a given SecSign ID
 * from the SecPKI server.
 *
 * @copyright    Copyright (C) 2014 SecSign Technologies Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt.
 */
class SecSignIdController extends JControllerLegacy
{
    /**
     * Method to display the view
     * @see http://docs.joomla.org/Developing_a_Model-View-Controller_Component_-_Part_1
     * @access    public
     */
    public function display($cachable = false, $urlparams = false)
    {
        parent::display($cachable = false, $urlparams = false);
    }

    /**
     * Method to cancel auth session and log out a user.
     */
    public function cancelAuthSession()
    {
        JRequest::checkToken('post') or jexit(JText::_('JInvalid_Token'));
        $app = JFactory::getApplication();

        // open the log file
        JLog::addLogger(array('text_file' => 'secsign.log'));

        // Populate the data array:
        $data = JRequest::get( 'post' );
        // get the HTTP get parameters which were set by the SecSign ID login form module
        $authSessionId = JRequest::getVar('secsignidauthsessionid', NULL, 'post', 'STRING');
        $secSignId = JRequest::getVar('secsigniduserid', NULL, 'post', 'STRING');
        $requestId = JRequest::getVar('secsignidrequestid', NULL, 'post', 'STRING');
        $serviceName = JRequest::getVar('secsignidservicename', NULL, 'post', 'STRING');
        $serviceAddress = JRequest::getVar('secsignidserviceaddress', NULL, 'post', 'STRING');
        $authSessionIconData = JRequest::getVar('secsignidauthsessionicondata', NULL, 'post', 'STRING');

        if (($authSessionId == NULL) || ($secSignId == NULL) || ($requestId == NULL)) {
            // missing SecSign ID login data -> send the user back to where he came from
            JLog::add('SecSign ID login component missing HTTP get parameters from the login form module', JLog::WARNING, 'secsign');
            $app->setUserState('secsignid.login.params', array());
            $app->setUserState('users.login.form.data', array());
            $app->redirect(JRoute::_($data['return'], false));

            return;
        }

        JLog::add('SecSign ID login component canceling authsession=' . $authSessionId . ' of SecSign ID=' . $secSignId, JLog::WARNING, 'secsign');
        // create SecPKI connector
        $secSignIDApi = NULL;
        try {
            $secSignIDApi = $this->getSecSignIDApiInstance();
        } catch (Exception $e) {
            $app->setUserState('secsignid.login.params', array('error' => 'SecSign ID auth session check status request failed: ' . $e->getMessage()));
            JLog::add('SecSign ID auth session check status request failed: ' . $e->getMessage(), JLog::WARNING, 'secsign');
        }

        if (!$secSignIDApi->prerequisite()) {
            $app->setUserState('secsignid.login.params', array('error' => 'SecSign ID plugin error: the php extension \'curl\' is not installed or enabled. Please install or enable \'curl\' before you can use SecSign ID.'));
            JLog::add('SecSign ID plugin error: the php extension \'curl\' is not installed or enabled. Please install or enable \'curl\' before you can use SecSign ID.', JLog::WARNING, 'secsign');
            return;
        }

        // restore the auth session object from the HTTP POST parameters
        $authSession = new AuthSession();
        $authSession->createAuthSessionFromArray(array(
            'secsignid' => $secSignId,
            'authsessionid' => $authSessionId,
            'requestid' => $requestId,
            'servicename' => $serviceName,
            'serviceaddress' => $serviceAddress,
            'authsessionicondata' => $authSessionIconData));

        // cancel auth session
        try {
            $secSignIDApi->cancelAuthSession($authSession); // just ask the server for the status. this returns immediately
        } catch (Exception $e) {
            $app->setUserState('secsignid.login.params', array('error' => 'SecSign ID login error: ' . $e->getMessage()));
            JLog::add('SecSign ID login error: ' . $e->getMessage(), JLog::WARNING, 'secsign');
        }

        // call logout user and go back to start page
        $app->setUserState('secsignid.login.params', array());
        $app->setUserState('users.login.form.data', array());
        $app->redirect(JRoute::_($data['return'], false));
    }

    /**
     * Method to log in a user.
     */
    public function getAuthSessionState()
    {
        JRequest::checkToken('post') or jexit(JText::_('JInvalid_Token'));
        $app = JFactory::getApplication();

        // open the log file
        JLog::addLogger(array('text_file' => 'secsign.log'));

        // Populate the data array:
        $secsignidlogin_params = array();
        $data = JRequest::get( 'post' );

        // Set the return URL if empty.
        if (empty($data['return'])) {
            $data['return'] = JURI::base();
        }

        // get the HTTP get parameters which were set by the SecSign ID login form module
        $authSessionId = JRequest::getVar('secsignidauthsessionid', NULL, 'post', 'STRING');
        $secSignId = JRequest::getVar('secsigniduserid', NULL, 'post', 'STRING');
        $requestId = JRequest::getVar('secsignidrequestid', NULL, 'post', 'STRING');
        $serviceName = JRequest::getVar('secsignidservicename', NULL, 'post', 'STRING');
        $serviceAddress = JRequest::getVar('secsignidserviceaddress', NULL, 'post', 'STRING');
        $authSessionIconData = JRequest::getVar('secsignidauthsessionicondata', NULL, 'post', 'STRING');

        if (($authSessionId == NULL) || ($serviceName == NULL) || ($secSignId == NULL) || ($requestId == NULL)) {
            // missing SecSign ID login data -> send the user back to where he came from
            JLog::add('SecSign ID login component missing HTTP get parameters from the login form module: authSessionId=' . $authSessionId . ' serviceName=' . $serviceName . ' secSignId=' . $secSignId . ' requestId=' . $requestId, JLog::WARNING, 'secsign');

            $app->setUserState('secsignid.login.params', array('error' => 'SecSign ID login component missing HTTP get parameters from the login form module'));
            $app->setUserState('users.login.form.data', array());
            $app->redirect(JRoute::_($data['return'], false));

            return;
        }

        JLog::add('SecSign ID login component checking the state of the auth session=' .$authSessionId . ' of SecSign ID=' . $secSignId, JLog::INFO, 'secsign');
        // create SecPKI connector
        $secSignIDApi = NULL;
        try {
            $secSignIDApi = $this->getSecSignIDApiInstance();
        } catch (Exception $e) {
            $secsignidlogin_params['error'] = 'SecSign ID auth session check status request failed: ' . $e->getMessage();
            JLog::add('SecSign ID auth session check status request failed: ' . $e->getMessage(), JLog::INFO, 'secsign');
        }

        if (!$secSignIDApi->prerequisite()) {
            $app->setUserState('secsignid.login.params', array('error' => 'SecSign ID plugin error: the php extension \'curl\' is not installed or enabled. Please install or enable \'curl\' before you can use SecSign ID.'));
            JLog::add('SecSign ID plugin error: the php extension \'curl\' is not installed or enabled. Please install or enable \'curl\' before you can use SecSign ID.', JLog::WARNING, 'secsign');
            return;
        }


        // restore the auth session object from the HTTP POST parameters
        $authSession = new AuthSession();
        $authSession->createAuthSessionFromArray(array(
            'secsignid' => $secSignId,
            'authsessionid' => $authSessionId,
            'requestid' => $requestId,
            'servicename' => $serviceName,
            'serviceaddress' => $serviceAddress,
            'authsessionicondata' => $authSessionIconData));

        // get auth session status
        $authsessionStatus = AuthSession::NOSTATE;
        try {
            $authsessionStatus = $secSignIDApi->getAuthSessionState($authSession); // just ask the server for the status. this returns immediately
        } catch (Exception $e) {
            $secsignidlogin_params['error'] = 'SecSign ID login error: ' . $e->getMessage();
            JLog::add('SecSign ID login error: ' . $e->getMessage(), JLog::WARNING, 'secsign');
        }

        JLog::add('SecSign ID login component got state=' . $authsessionStatus . ' from server for session=' . $authSessionId . ' of SecSign ID=' . $secSignId, JLog::INFO, 'secsign');

        if (AuthSession::PENDING == $authsessionStatus || AuthSession::FETCHED == $authsessionStatus) {
            $secsignidlogin_params['secsignid'] = $secSignId;
            $secsignidlogin_params['authsessionid'] = $authSessionId;
            $secsignidlogin_params['servicename'] = $serviceName;
            $secsignidlogin_params['requestid'] = $requestId;
            $secsignidlogin_params['serviceaddress'] = $serviceAddress;
            $secsignidlogin_params['authsessionicondata'] = $authSessionIconData;

            $secsignidlogin_params['msg'] = 'Authentication Session is still pending. Please accept the correct access pass on your smartphone.';

            // redirect to the SecSign ID login form module that show existing auth session
            $app->setUserState('secsignid.login.params', $secsignidlogin_params);

            $app->setUserState('users.login.form.data', array());
            $app->redirect(JRoute::_('index.php', false));

            return;
        }

        // there is just one chance to get right auth session state. this is when user hits button.
        // whether the auth session was accepted the user will be logged in
        // otherwise go back to login form
        // in both cases there is no need to keep the auth session. withdraw or dispose it.
        try {
            if (AuthSession::AUTHENTICATED != $authsessionStatus && AuthSession::DENIED != $authsessionStatus) {
                $secSignIDApi->cancelAuthSession($authSession);
            }
        } catch (Exception $e) {
            $secsignidlogin_params['error'] = 'SecSign ID login error: ' . $e->getMessage();
            JLog::add('SecSign ID login error: ' . $e->getMessage(), JLog::WARNING, 'secsign');
        }


        if (AuthSession::AUTHENTICATED != $authsessionStatus) {
            // auth session not accepted by the user on his smart phone
            JLog::add('SecSign ID login component denied the login of SecSign ID=' . $secSignId .
                ' as state of the auth session=' . $authSessionId . ' is ' . $authsessionStatus, JLog::INFO, 'secsign');

            // send the user back to where he came from
            $app->setUserState('secsignid.login.params', $secsignidlogin_params);
            $app->setUserState('users.login.form.data', array());
            $app->redirect(JRoute::_($data['return'], false));
        } else {
            // the user has accepted the auth session on his smart phone
            JLog::add('SecSign ID login component accepted the login of SecSign ID=' . $secSignId .
                ' since the user accepted the auth session=' . $authSessionId . ' on his smart phone. Auth session status is ' . $authsessionStatus, JLog::INFO, 'secsign');

            $data['authenticatedSecSignID'] = $secSignId; //$authSession->getSecSignID();



            // Get the log in options.
            $options = array();
            $options['remember'] = JRequest::getBool('remember', false);
            $options['return'] = $data['return'];

            // Get the log in credentials.
            $credentials = array();
            $credentials['authenticatedSecSignID'] = $data['authenticatedSecSignID'];
            $credentials['secSignIDAuthCalled'] = "true";

            // Perform the log in. This will call the authentication plug-ins. One of them is the SecSign ID 
            // authentication plug-in which understand these parameters.
            $error = $app->login($credentials, $options);

            // Check if the log in succeeded.
            if (!JError::isError($error)) {
                $app->setUserState('users.login.form.data', array());
                $app->redirect(JRoute::_($data['return'], false));
            } else {
                $data['remember'] = (int)$options['remember'];
                $app->setUserState('users.login.form.data', $data);
                $app->redirect(JRoute::_('index.php?option=com_users&view=login', false));
            }

            $app->setUserState('secsignid.login.params', $secsignidlogin_params);
        }
    }

    /**
     * Requests a auth session. All auth session data is stored in applications UserState fields.
     * After that the page is reloaded. During this the secsigner id module is rendered again which gets its data from applications UserState fields.
     */
    public function requestAuthSession()
    {
        JLog::addLogger(array('text_file' => 'secsign.log'));
        JRequest::checkToken('post') or jexit(JText::_('JInvalid_Token'));

        $app = JFactory::getApplication();

        // get the alleged SecSign ID entered by the user
        $uncheckedSecSignId = JRequest::getVar('username', '', 'method', 'username');
        $secSignIdRequestor = JRequest::getVar('requesting_service', '', 'method', 'requesting_service');

        JLog::add('SecSign ID login component request auth session from SecSign ID server for SecSign ID=' . $uncheckedSecSignId, JLog::INFO, 'secsign');
        $secsignidlogin_params = array();

        $authSessionErrMsg = NULL;

        //
        //
        // first check whether secsign id was bound to a joomla user
        //
        //


        // Load the profile data from the database.
        // The user must have added a SecSignID to his profile using the SecSignID_profile plug-in.
        $db = JFactory::getDbo();
        $results = NULL;

        // check if table exists
        try {
            $db->setQuery("SELECT joomla_user_id, joomla_user, secsignid FROM #__secsignid_login WHERE #__secsignid_login.secsignid = '" . $uncheckedSecSignId . "'");
            $results = $db->loadRowList();
        } catch (JException $e) {

            $app->setUserState('secsignid.login.params', array('error' => 'Creating SecSignIDApi instance failed: ' . $e->getMessage()));
            JLog::add('Creating SecSignIDApi instance failed: ' . $e->getMessage(), JLog::ERROR, 'secsign');
            return;
        }

        // Check for a database error.
        if ($db->getErrorNum()) {
            $message = 'Could not search the data base for a user with SecSignID "' . $uncheckedSecSignId . '": ' . $db->getErrorMsg();

            $app->setUserState('secsignid.login.params', array('error' => $message), JLog::ERROR, 'secsign');
            JLog::add($message);
            return;
        }

        // check if result is empty. in that case no mapping between a user and the entered secsign id exists
        if (NULL == $results) {
            // no Joomla user has added this authenticated SecSignID to his profile
            $message = "The SecSignID '" . $uncheckedSecSignId . "' does not belong to any Joomla user name. If you want to assign your SecSign ID to an account please contact the website administrator.";

            // titus: this doesnt work and I donnu why...
            // $message = JText::sprintf('PLG_SECSIGNID_NO_JOOMLA_USER', $uncheckedSecSignId);
            $app->setUserState('secsignid.login.params', array('error' => $message));
            JLog::add($message, JLog::INFO, 'secsign');
            return;
        }

        // found more than one user who was assigned the entered secsign id
        if (count($results) != 1) {
            $message = 'Found ' . count($results) . ' users with SecSignID "' . $uncheckedSecSignId . '" in the data base. Please contact the website administrator';
            $app->setUserState('secsignid.login.params', array('error' => $message));
            JLog::add($message, JLog::INFO, 'secsign');

            return;
        }

        //
        //
        // now we can try to get an authentication session for user
        //
        //    

        $secSignIDApi = NULL;
        try {
            $secSignIDApi = $this->getSecSignIDApiInstance();
        } catch (Exception $e) {
            $secsignidlogin_params['error'] = 'Creating SecSignIDApi instance failed: ' . $e->getMessage();
            JLog::add('Creating SecSignIDApi instance failed: ' . $e->getMessage(), JLog::ERROR, 'secsign');
        }

        if (!$secSignIDApi->prerequisite()) {
            $app->setUserState('secsignid.login.params', array('error' => 'SecSign ID plugin error: the php extension \'curl\' is not installed or enabled. Please install or enable \'curl\' before you can use SecSign ID.'));
            JLog::add('SecSign ID plugin error: the php extension \'curl\' is not installed or enabled. Please install or enable \'curl\' before you can use SecSign ID.', JLog::ERROR, 'secsign');
            return;
        }

        // request auth session
        try {
            $servicename = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_servicename');
            $authsession = $secSignIDApi->requestAuthSession($uncheckedSecSignId, $servicename, $_SERVER['SERVER_NAME']);
        } catch (Exception $e) {
            $authSessionErrMsg = $e->getMessage();
            JLog::add('SecSign ID auth session request failed: ' . $e->getMessage(), JLog::WARNING, 'secsign');
        }

        if ($authsession != NULL) {
            JLog::add('SecSign ID login component received authsession=' . $authsession->getAuthSessionID() . ' from SecSign ID Server for SecSign ID=' . $uncheckedSecSignId, JLog::INFO, 'secsign');

            $secsignidlogin_params['secsignid'] = $authsession->getSecSignID();
            $secsignidlogin_params['authsessionid'] = $authsession->getAuthSessionID();
            $secsignidlogin_params['servicename'] = $authsession->getRequestingServiceName();
            $secsignidlogin_params['serviceaddress'] = $authsession->getRequestingServiceAddress();
            $secsignidlogin_params['requestid'] = $authsession->getRequestID();
            $secsignidlogin_params['authsessionicondata'] = $authsession->getIconData();
        } else {
            if ($authSessionErrMsg == NULL) {
                // this is unusual. the only possible reason for auth session = NULL is a thrown exception in method $secpki->getAuthSession(...)
                $authSessionErrMsg = "Login with SecSign ID not possible at the moment.";
            }
        }

        if ($authSessionErrMsg != NULL) {
            $secsignidlogin_params['error'] = $authSessionErrMsg;
            JLog::add('SecSign ID auth session request failed: ' . $authSessionErrMsg, JLog::ERROR, 'secsign');
        }


        // set secsigner id login parameters
        // redirect to joomla page where the secsigner id module is rendered
        $app->setUserState('secsignid.login.params', $secsignidlogin_params);


        // redirect to the SecSignID login form module that generates a SecSignID token
        $app->setUserState('users.login.form.data', array());
        $app->redirect(JRoute::_('index.php', false));
    }

    /**
     * gets an instance of secsigner id connector
     */
    private function getSecSignIDApiInstance()
    {
        // connector
        $secSignIDApi = new SecSignIDApi("https://httpapi.secsign.com", // server url
            443); // port
        $secSignIDApi->setPluginName("SecSignID-Joomla 2.5");

        function logFromSecSignIDApi($message)
        {
            JLog::addLogger(array('text_file' => 'secsign.log'));
            JLog::add('SecSignIDApi: ' . $message, JLog::INFO, 'secsign');
        }

        ;
        $secSignIDApi->setLogger('logFromSecSignIDApi'); // sets a reference to a function or the name of the function. if it is callable this will be used to log messages

        return $secSignIDApi;
    }
}
