<?php
// $Id: secsignid.php,v 1.2 2014/12/01 15:04:28 titus Exp $

/**
 * SecSign ID component that will request an authentication session for a given SecSign ID 
 * from the SecPKI server.
 *
 * @copyright	Copyright (C) 2011 SecSign Technologies Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt.
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import joomla controller library
jimport('joomla.application.component.controller');

// Get an instance of the controller prefixed by SecSignId
$controller = JControllerLegacy::getInstance('SecSignId');

// Perform the Request task
$controller->execute(JRequest::getCmd('task'));

// Redirect if set by the controller
$controller->redirect();