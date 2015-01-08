<?php
// $Id: default.php,v 1.9 2015/01/08 17:36:09 titus Exp $

// no direct access
defined('_JEXEC') or die;
jimport('joomla.filesystem.file');
jimport('joomla.application.component.helper');

/**
 * SecSign ID login module form.
 * Asks the user for his SecSign ID, displays the authentication session access pass created by the SecPKI server and
 * waits for the user to confirm/accept the auth session.
 *
 * This file is based on the default Joomla 1.6.3 login form.
 *
 * @copyright    Copyright (C) 2011, 2012, 2013 SecSign Technologies Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt.
 */

JHtml::_('behavior.keepalive');

/**
 * add module own CSS stylesheet
 */
$document = JFactory::getDocument();
$document->addStyleSheet(JURI::base() . 'modules/mod_secsignid_login/css/mod_secsignid_login.css');
$document->addScript(JURI::base() . 'modules/mod_secsignid_login/js/SecSignIDApi.js');
$view = JRequest::getVar('view', 0);
?>



<script>
    //JS for responsive layout
    window.onload = function () {
        var width = document.getElementById("secsign").offsetWidth;
        responsive(width);
    };

    window.addEventListener('resize', function () {
        var width = document.getElementById("secsign").offsetWidth;
        responsive(width);
    });

    function responsive(width) {
        var login = document.getElementById("secsignid_login");
        var info = document.getElementById("secsignid_info");
        var cancel = document.getElementById("secsignid_cancel");
        var ok = document.getElementById("secsignid_ok");
        var accesspass_bg = document.getElementById("secsignid_accesspass_graphic");
        var accesspass = document.getElementById("secsignid_accesspass");

        if (width >= 250) {
            //add classes for big layout
            if (login) login.className = "button_secsignid_login blue button_secsignid_big";
            if (info) info.className = "button_secsignid_login button_secsignid_big button_secsignid_right";
            if (cancel) cancel.className = "button_secsignid_form button_secsignid_big";
            if (ok) ok.className = "button_secsignid_form button_secsignid_big button_secsignid_right";

        } else {
            if (login) login.className = "button_secsignid_login blue";
            if (info) info.className = "button_secsignid_login";
            if (cancel) cancel.className = "button_secsignid_form";
            if (ok) ok.className = "button_secsignid_form";
        }

        if (width >= 180) {
            if (accesspass_bg) accesspass_bg.className = "accesspass_secsignid_login";
            if (accesspass) accesspass.className = "accesspass_icon_secsignid_login";
        } else {
            if (accesspass_bg) accesspass_bg.className = "secsignid_no_graphic";
            if (accesspass) accesspass.className = "accesspass_icon_secsignid_login accesspass_icon_secsignid_login_small";
        }
    }
</script>



<?php if ($type == 'logout') : ?>
    <div id="secsign">
        <form action="<?php
            $secsignLogout = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_logout');
            $secsignSecure = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_secure');
            $url = JRoute::_('index.php?Itemid=' . $secsignLogout, true, $secsignSecure);
            echo $url;?>"
              method="post"
              id="login-form-secsignid">

            <?php
            $secsignGreeting = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_greeting');
            $secsigName = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_name');

            if ($secsignGreeting) : ?>
                <div class="login-greeting secsignid_login">
                    <?php if ($secsigName == 0) : {
                        echo JText::sprintf('MOD_SECSIGNID_LOGIN_HINAME', $user->get('name'));
                    } else : {
                        echo JText::sprintf('MOD_SECSIGNID_LOGIN_HINAME', $user->get('username'));
                    } endif; ?>
                </div>
                <br/>
            <?php endif; ?>


            <div class="logout-button">
                <button class="button_secsignid_login secsignid_logout" value="<?php echo 'Logout' ?>" name="Submit"
                        type="submit"><?php echo JText::_('JLOGOUT'); ?></button>
                <input type="hidden" name="option" value="com_users"/>
                <input type="hidden" name="task" value="user.logout"/>
                <input type="hidden" name="return" value="<?php echo $return; ?>"/>
                <?php echo JHtml::_('form.token'); ?>
            </div>
        </form>
    </div>

<?php else : ?>

    <?php
    $pretext = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_pretext');
    if ($pretext) {
        echo "<p>" . $pretext . "<br>&nbsp;</p>";
    }
    ?>

    <?php
    jimport('joomla.error.log');

    if (!isset($secsignid_params) || $secsignid_params == NULL) {
        // the array $secsignid_params is taken in mod_secsignid_login.php
        // check the array to avoid nullpointer exceptions
        $secsignid_params = array();
    }

    // first check if there is any kind of error
    if (isset($secsignid_params['error'])) {
        echo "<font color=\"#FF0000\">" . $secsignid_params['error'] . "</font><br />";
        //echo JText::sprintf('JGLOBAL_AUTH_FAILED', $secsignid_params['error']);
    }

    // check if there is a message
    if (isset($secsignid_params['msg'])) {
        echo $secsignid_params['msg'] . "<br /><br />";
    }

    // if the user has already entered a SecSign ID in the step before the SecSign ID component will send him 
    // back here with this variable containing the SecSign ID he entered
    if (isset($secsignid_params['secsignid']) && isset($secsignid_params['requestid']) && isset($secsignid_params['authsessionid'])) {
        if (isset($secsign_params['error']) || !function_exists("curl_init")) {
            if (!function_exists("curl_init")) {
                echo "<font color=\"#FF0000\">The php extension 'curl' is not installed or enabled. Therefor SecSign ID Server cannot be reached. Please install the 'curl' extension.</font><br />";
            }
            ?>

            <form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post"
                  id="login-form-secsignid">
                <div class="cancel-button">
                    <button style="width:90%" class="button_secsignid_login" value="<?php echo 'Cancel' ?>"
                            name="Submit" type="submit"><?php echo JText::_('MOD_SECSIGNID_CANCEL'); ?></button>
                    <input type="hidden" name="option" value="com_users"/>
                    <input type="hidden" name="task" value="user.logout"/>
                    <input type="hidden" name="return" value="<?php echo $return; ?>"/>
                    <?php echo JHtml::_('form.token'); ?>
                </div>
            </form>
        <?php
        } else {
        	
        	// check whether jquery is available
        	if(!JFactory::getApplication()->get('jquery')){
			     JFactory::getApplication()->set('jquery',true);
			     $document = JFactory::getDocument();
			     $document->addScript(JURI::root() . "modules/mod_secsignid_login/js/2.1.1.jquery.min.js");
  			}
            ?>
            <!-- polling -->
            <script>
                var timeTillAjaxSessionStateCheck = 3700;
                var checkSessionStateTimerId = -1;

                function ajaxCheckForSessionState(){
                	if(jQuery("#secsign .secsign_row button").attr("checking")){
            			return;
            		}
            		jQuery("#secsign .secsign_row button").attr({"checking": "1", "disabled" : "disabled"});
            		
                    var secSignIDApi = new SecSignIDApi({posturl:"<?php echo JUri::base(true)?>/modules/mod_secsignid_login/bridge/signin-bridge.php"});
                    secSignIDApi.getAuthSessionState(
                        '<?php echo $secsignid_params['secsignid'] ?>',
                        '<?php echo $secsignid_params['requestid'] ?>',
                        '<?php echo $secsignid_params['authsessionid'] ?>',
                        function rMap(responseMap) {
                            if(responseMap) {
                            	jQuery("#secsign .secsign_row button").removeAttr("checking");
                            	jQuery("#secsign .secsign_row button").removeAttr("disabled");
                            	
                                // check if response map contains error message or if authentication state could not be fetched from server.
                                if ("errormsg" in responseMap) {
                                    return;
                                } else if (!("authsessionstate" in responseMap)) {
                                    return;
                                }
                                if (responseMap["authsessionstate"] == undefined || responseMap["authsessionstate"].length < 1) {
                                    // got answer without an auth session state. this is not parsable and will throw the error UNKNOWN
                                    return;
                                }

                                // everything okay. authentication state can be checked...
                                var authSessionStatus = parseInt(responseMap["authsessionstate"]);
                                var SESSION_STATE_NOSTATE = 0;
                                var SESSION_STATE_PENDING = 1;
                                var SESSION_STATE_EXPIRED = 2;
                                var SESSION_STATE_AUTHENTICATED = 3;
                                var SESSION_STATE_DENIED = 4;
                                var SESSION_STATE_SUSPENDED = 5;
                                var SESSION_STATE_CANCELED = 6;
                                var SESSION_STATE_FETCHED = 7;
                                var SESSION_STATE_INVALID = 8;

                                if ((authSessionStatus == SESSION_STATE_AUTHENTICATED) || (authSessionStatus == SESSION_STATE_DENIED) || (authSessionStatus == SESSION_STATE_EXPIRED)
                                    || (authSessionStatus == SESSION_STATE_SUSPENDED) || (authSessionStatus == SESSION_STATE_INVALID) || (authSessionStatus == SESSION_STATE_CANCELED)) {
                                    window.clearInterval(checkSessionStateTimerId);
                                    jQuery("button[name='check_authsession_button']").click();
                                }
                            }
                        }
                    );
                }

                for (var timerId = 1; timerId < 5000; timerId++) {
                    clearTimeout(timerId);
                }
                
                function handleSecSignIdSessionButtons(form_name) {
            		document.getElementById('check_authsession_button').disabled=true;
            		document.getElementById('cancel_authsession_button').disabled=true;
            	
            		document.forms[form_name].submit();
            	
            		return true;
            	}

				var checkFunction = function(){
    	            checkSessionStateTimerId = window.setInterval(function () {
        	            ajaxCheckForSessionState();

            	    }, timeTillAjaxSessionStateCheck);
                };
                
				//if(window.jQuery){
	                jQuery(document).ready(checkFunction);
                //} else {
				//	document.addEventListener("DOMContentLoaded", checkFunction, false);
				//}

            </script>
            <!-- end polling -->


            <div id="secsign">
                <p style="text-align:center;font-weight:bold;">
                    <?php echo JText::sprintf('MOD_SECSIGNID_ACCESSPASS_DESCR', '<i>' . $secsignid_params['secsignid'] . '</i>'); ?>
                </p>

                <div id="secsignid_accesspass_graphic" class="accesspass_secsignid_login"
                     style="background:transparent url(<?php echo JURI::root(); ?>media/com_secsignid/images/accesspass_bg.png) no-repeat scroll;background-size:180px 240px;">
                    <img id="secsignid_accesspass" class="accesspass_icon_secsignid_login"
                         src="data:image/png;base64,<?php echo $secsignid_params['authsessionicondata']; ?>"
                         class="passicon">
                </div>
                <p style="text-align: center;">
                    <?php echo JText::_('MOD_SECSIGNID_ACCESSPASS_HELP'); ?>
                </p>

                <div class="secsign_row">
                    <form class="button_secsignid_form" id="secsignid_cancel"
                          action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>"
                          method="post" id="login-form-secsignid">
                        <div class="cancel-button">
                            <button style="width:100%;" class="button_secsignid_login" value="1"
                                    name="cancel_authsession_button"
                                    id="cancel_authsession_button"
                                    type="submit"
                                    onclick="return handleSecSignIdSessionButtons('secsignid_cancel');"><?php echo JText::_('MOD_SECSIGNID_CANCEL'); ?></button>
							<input type="hidden" name="cancel_authsession" id="cancel_authsession" value="1"/>
                            <input type="hidden" name="option" value="com_secsignid"/>
                            <input type="hidden" name="task" value="cancelAuthSession"/>
                            <input type="hidden" name="return" value="<?php echo $return; ?>"/>

                            <input type="hidden" name="secsigniduserid"
                                   value="<?php echo $secsignid_params['secsignid']; ?>"/>
                            <input type="hidden" name="secsignidauthsessionid"
                                   value="<?php echo $secsignid_params['authsessionid']; ?>"/>
                            <input type="hidden" name="secsignidrequestid"
                                   value="<?php echo $secsignid_params['requestid']; ?>"/>
                            <input type="hidden" name="secsignidservicename"
                                   value="<?php echo $secsignid_params['servicename']; ?>"/>
                            <input type="hidden" name="secsignidserviceaddress"
                                   value="<?php echo $secsignid_params['serviceaddress']; ?>"/>
                            <input type="hidden" name="secsignidauthsessionicondata"
                                   value="<?php echo $secsignid_params['authsessionicondata']; ?>"/>
                            <?php echo JHtml::_('form.token'); ?>
                        </div>
                    </form>
                    <form class="button_secsignid_form" id="secsignid_ok"
                          action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>"
                          method="post" id="login-form-secsignid">
                        <fieldset class="userdata secsignid_login">
                            <button style="width:100%;float:right;" class="button_secsignid_login blue" value="1"
                                    name="check_authsession_button" 
                                    id="check_authsession_button" 
                                    type="submit"
                                    onclick="return handleSecSignIdSessionButtons('secsignid_ok');"><?php echo 'OK' ?></button>
                            <input type="hidden" name="check_authsession" id="check_authsession" value="1"/>
                            <input type="hidden" name="option" value="com_secsignid"/>
                            <input type="hidden" name="task" value="getAuthSessionState"/>
                            <input type="hidden" name="return" value="<?php echo $return; ?>"/>

                            <input type="hidden" name="secsigniduserid"
                                   value="<?php echo $secsignid_params['secsignid']; ?>"/>
                            <input type="hidden" name="secsignidauthsessionid"
                                   value="<?php echo $secsignid_params['authsessionid']; ?>"/>
                            <input type="hidden" name="secsignidrequestid"
                                   value="<?php echo $secsignid_params['requestid']; ?>"/>
                            <input type="hidden" name="secsignidservicename"
                                   value="<?php echo $secsignid_params['servicename']; ?>"/>
                            <input type="hidden" name="secsignidserviceaddress"
                                   value="<?php echo $secsignid_params['serviceaddress']; ?>"/>
                            <input type="hidden" name="secsignidauthsessionicondata"
                                   value="<?php echo $secsignid_params['authsessionicondata']; ?>"/>
                            <?php echo JHtml::_('form.token'); ?>
                        </fieldset>
                    </form>
                    <div class="clear"></div>
                </div>
            </div>
        <?php
        }
    } else {
        // values for SecSignIDApi.requestAuthSession()
        $secSignIdRequestor = $params->get('requestor');
        if (NULL == $secSignIdRequestor) {
            $secSignIdRequestor = $_SERVER['HTTP_HOST']; //"SecSign ID login for Joomla";
        }
        $secSignIdRequestor = $_SERVER['HTTP_HOST'];

        ?>
        <script type="text/javascript">
            function checkSecSignIDInput() {
                var secsignid = document.forms["login-form-secsignid"].username.value;
                if (secsignid == undefined || secsignid.length < 1) {
                    alert("<?php echo JText::_('MOD_SECSIGNID_LOGIN_VALUE_MISSING'); ?>");
                    return false;
                }
                return true;
            }
            
            function handleSecSignIdLoginButtons() {
            	document.getElementById('secsignid_login').disabled=true;
            	document.getElementById('secsignid_info').disabled=true;
            	
            	document.forms['login-form-secsignid'].submit();
            	
            	return true;
            }
        </script>

        <div id="secsign">

            <form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post"
                  id="login-form-secsignid" name="login-form-secsignid" onsubmit="return checkSecSignIDInput();">
                <fieldset class="userdata secsignid_login">
                    <div class="secsign_row" id="form-login-username">
                        <label
                            for="modlgn-username"><?php echo JText::_('MOD_SECSIGNID_LOGIN_VALUE_USERNAME') ?></label>
                        <input id="modlgn-username" type="text" name="username" class="inputbox" value="" size="18"/>
                    </div>
                    <?php if (JPluginHelper::isEnabled('system', 'remember')) : ?>
                        <div class="secsign_row" id="form-login-remember">
                            <label
                                for="modlgn-remember"><?php echo JText::_('MOD_SECSIGNID_LOGIN_REMEMBER_ME') ?></label>
                            <input id="modlgn-remember" type="checkbox" name="remember" class="inputbox" value="yes"/>
                        </div>
                    <?php endif; ?>

                    <div class="secsign_row">
                        <button id="secsignid_login" class="button_secsignid_login blue" value="<?php echo 'Login' ?>"
                                name="Submit"
                                type="submit" onclick="return handleSecSignIdLoginButtons();"><?php echo JText::_('JLOGIN'); ?></button>

                        <button id="secsignid_info" onclick="window.location.href = 'https://www.secsign.com/sign-up/'"
                                class="button_secsignid_login" name="goto" value="signup"
                                type="button"><?php echo JText::_('MOD_SECSIGNID_SIGNUP'); ?></button>

                    </div>
                    <input type="hidden" name="option" value="com_secsignid"/>
                    <input type="hidden" name="task" value="requestAuthSession"/>
                    <input type="hidden" name="requesting_service" value="<?php echo $secSignIdRequestor ?>"/>
                    <input type="hidden" name="return" value="<?php echo $return; ?>"/>
                    <?php echo JHtml::_('form.token'); ?>
                </fieldset>

            </form>


            <div class="clear"></div>

        </div>
    <?php
    }

    $posttext = JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_posttext');
    if ($posttext) {
        echo "<p>" . $posttext . "</p>";
    }


endif; ?>
