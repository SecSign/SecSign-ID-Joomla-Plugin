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

//login logout redirects
$document = JFactory::getDocument();
$app = JFactory::getApplication();
$user	= JFactory::getUser();

$type	= modLoginHelper::getType();
$menu = $app->getMenu();

$secsignLoginParam = $params->get('secsign_frontend_login', "");
$secsignLogoutParam = $params->get('secsign_frontend_logout', "");
$secsignSecure = $params->get('secsign_frontend_secure', "");
$secsignLogin = $menu->getItem($secsignLoginParam)->link;
$secsignLogout = $menu->getItem($secsignLogoutParam)->link;
$return = JURI::current();

if ($type == 'logout' && $secsignLogout){
    $return = JURI::base().$secsignLogout;
} elseif($secsignLogin) {
    $return = JURI::base().$secsignLogin;
}

//current URL if error etc.
$currentUrl = JURI::current();

// the component com_secsignid will store an array in user state 'secsignid.login.params'
$secsignid_params = $app->getUserState('secsignid.login.params');

// reset user state 'secsignid.login.params'? this also means if user hits reload-button of browser the login process will fail...
if($secsignid_params != NULL && count($secsignid_params) > 0){
    $app->setUserState('secsignid.login.params', array());
}

require JModuleHelper::getLayoutPath('mod_secsignid_login', $params->get('layout', 'default'));
