<?php
// $Id: mod_secsignid_login.php,v 1.2 2014/12/01 15:04:28 titus Exp $

// no direct access
defined('_JEXEC') or die;
jimport('joomla.application.component.helper');

/**
 * SecSign ID login module. 
 * Asks the user for his SecSign ID, displays the authentication acces pass created by the SecPKI server and
 * waits for the user to confirm/accept the authentication session.
 *
 * @copyright	Copyright (C) 2014 SecSign Technologies Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt.
 */

// Include the syndicate functions only once
require_once dirname(__FILE__).'/helper.php';

// include the default Joomla 1.6.3 mod_login once
require_once dirname(__FILE__).'/../mod_login/helper.php';

//$params->def('greeting', 1);

// ask the default Joomla 1.6.3 mod_login
$type	= modLoginHelper::getType();
$return	= modLoginHelper::getReturnURL($params, $type);
$secsignLogin = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_login');
$secsignLogout = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_logout');
$secsignSecure = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_secure');

if ($type == 'logout' && $secsignLogout){
    $url = JRoute::_('index.php?Itemid=' . $secsignLogout, true, $secsignSecure);
    $return = urlencode(base64_encode($url));
} elseif($secsignLogin) {
    $url = JRoute::_('index.php?Itemid=' . $secsignLogin, true, $secsignSecure);
    $return = urlencode(base64_encode($url));
}

// ask the application
$user	= JFactory::getUser();
$app    = JFactory::getApplication();

// the component com_secsignid will store an array in user state 'secsignid.login.params'
$secsignid_params = $app->getUserState('secsignid.login.params');

// reset user state 'secsignid.login.params'? this also means if user hits reload-button of browser the login process will fail...
if($secsignid_params != NULL && count($secsignid_params) > 0){
    $app->setUserState('secsignid.login.params', array());
}

require JModuleHelper::getLayoutPath('mod_secsignid_login', $params->get('layout', 'default'));
