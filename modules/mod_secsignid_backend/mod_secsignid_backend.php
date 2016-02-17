<?php
// $Id: mod_secsignid_backend.php,v 1.2 2014/12/15 15:50:07 titus Exp $

// no direct access
defined('_JEXEC') or die;

/**
 * SecSign ID login module.
 * Asks the user for his SecSign ID, displays the authentication acces pass created by the SecPKI server and
 * waits for the user to confirm/accept the authentication session.
 *
 * @copyright    Copyright (C) 2014 SecSign Technologies Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt.
 */

// Include the syndicate functions only once
require_once dirname(__FILE__) . '/helper.php';

// include the default Joomla 1.6.3 mod_login once
require_once dirname(__FILE__) . '/../mod_login/helper.php';

include_once JPATH_ROOT . '/media/com_secsignid/SecSignIDApi/phpApi/SecSignIDApi.php';

jimport('joomla.application.component.helper');
$secsignmode = JComponentHelper::getParams('com_secsignid')->get('secsign_mode');

//if PW deactivated hide link
if($secsignmode == '1' ) {
    //hide link
    echo"<style>#secsignid-pw{display:none;}</style>";
    //actual detection in plugins/authentication/secsignidauth.php
}

// ask the default Joomla 1.6.3 mod_login
$type = 'login';//modLoginHelper::getType();
$return = modLoginHelper::getReturnURI();//($params, $type);

// ask this module

// ask the application
$user = JFactory::getUser();
$app = JFactory::getApplication();

// the component com_secsignid will store an array in user state 'secsignid.login.params'
$secsignid_params = $app->getUserState('secsignid.login.params');

// reset user state 'secsignid.login.params'? this also means if user hits reload-button of browser the login process will fail...
if ($secsignid_params != NULL && count($secsignid_params) > 0) {
    $app->setUserState('secsignid.login.params', array());
}

$session = JFactory::getSession();
$secsignid_params = $session->get('secsignid_params');
$input = JFactory::getApplication()->input;
$secsignid = $input->get('username', '', 'STR');
$cancel = $input->get('cancel_authsession', '', 'STR');
$ok = $input->get('check_authsession', '', 'STR');

try {
    // create a new session instance which is needed to check its status
    $authsession = new AuthSession();
    $authsession->createAuthSessionFromArray(array(
        'requestid' => $input->get('secsignidrequestid'),
        'secsignid' => $input->get('secsigniduserid'),
        'authsessionid' => $input->get('secsignidauthsessionid'),
        'servicename' => $input->get('secsignidservicename'),
        'serviceaddress' => $input->get('secsignidserviceaddress')
    ));

    $secSignIDApi = new SecSignIDApi();
    $authSessionState = $secSignIDApi->getAuthSessionState($authsession);
    $session = JFactory::getSession();

    if ($authSessionState == AuthSession::AUTHENTICATED) {
        $session->set('errormsg', null);
        $session->set('secsignid_params', null);

        $options = array();
        $credentials = array();
        $options['remember'] = JRequest::getBool('remember', false);
        $options['return'] = 'index.php';
        $credentials['authenticatedSecSignID'] = $input->get('secsigniduserid');
        $credentials['secSignIDAuthCalled'] = "true";

        // Perform the log in. This will call the authentication plug-ins. One of them is the SecSign ID
        // authentication plug-in which understands these parameters.
        $app = JFactory::getApplication('admin');
        $error = $app->login($credentials, $options);
        $redirect_url = $app->input->get('redirect', 'index.php');
        $app->redirect($redirect_url);
    } else if ($authSessionState == AuthSession::DENIED or $authSessionState == AuthSession::CANCELED) {
        $session->set('errormsg', 'denied');
        $session->set('secsignid_params', null);
        JLog::addLogger(array('text_file' => 'secsignadmin.log'));
        JLog::add('Login denied.', JLog::WARNING, 'secsignadmin');
        $redirect_url = $app->input->get('redirect', 'index.php');
        $app->redirect($redirect_url);
    } else if ($authSessionState == AuthSession::PENDING) {
        $session->set('errormsg', 'pending');
        $redirect_url = $app->input->get('redirect', 'index.php');
        $app->redirect($redirect_url);
    } else {
        $session->set('errormsg', 'noresponse');
        //$session->set('secsignid_params', null);
        JLog::addLogger(array('text_file' => 'secsignadmin.log'));
        JLog::add('Auth session expired or connection error.', JLog::WARNING, 'secsignadmin');
        $redirect_url = $app->input->get('redirect', 'index.php');
        $app->redirect($redirect_url);
    }
} catch (Exception $e) {
    $session->set('errormsg', 'noresponse');
    //$session->set('secsignid_params', null);
    JLog::addLogger(array('text_file' => 'secsignadmin.log'));
    JLog::add('An error occured when verifying AccessPass: ' . $e->getMessage(), JLog::ERROR, 'secsignadmin');
}

require JModuleHelper::getLayoutPath('mod_secsignid_backend', $params->get('layout', 'default'));
