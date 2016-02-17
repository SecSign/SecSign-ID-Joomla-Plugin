<?php
// $Id: default.php,v 1.6 2015/01/08 17:36:09 titus Exp $

// no direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.helper');


/**
 * SecSign ID login module form.
 * Asks the user for his SecSign ID, displays the authentication session access pass created by the SecPKI server and
 * waits for the user to confirm/accept the auth session.
 *
 * @copyright    Copyright (C) 2011, 2012, 2013 SecSign Technologies Inc. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt.
 */

JHtml::_('behavior.keepalive');
?>

<script>
    //Parameters
    var url = "";
    var title = "<?php echo JComponentHelper::getParams('com_secsignid')->get('secsign_backend_servicename'); ?>";
    var secsignPluginPath = '<?php echo JURI::base() ?>../media/com_secsignid/';
    var apiurl = '<?php echo JURI::base() ?>../media/com_secsignid/SecSignIDApi/signin-bridge.php';
    var errormsg = "<?php echo JText::_('COM_SECSIGNID_FE_20'); ?>";
    var noresponse = "<?php echo JText::_('COM_SECSIGNID_FE_21'); ?>";
    var nosecsignid = "<?php echo JText::_('COM_SECSIGNID_FE_19'); ?>";
    var secsignid = "";

    //setup default values if empty
    if(url ==""){ url = document.URL; }
    if(title ==""){ title = document.title; }
</script>
<?php
// add public CSS stylesheet and JS files
$document = JFactory::getDocument();
JHtml::_('stylesheet', JUri::base() . '../media/com_secsignid/css/secsignid_layout.css');
JHtml::_('script', JUri::base() . '../media/com_secsignid/SecSignIDApi/SecSignIDApi.js');
JHtml::_('script', JUri::base() . '../media/com_secsignid/js/secsignfunctionsBE.js');
$view = JRequest::getVar('view', 0);
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

<script>
jQuery.noConflict();

//Load SecSignID API
jQuery.getScript(secsignPluginPath+"SecSignIDApi/SecSignIDApi.js", function () {

    //hide Joomla Logins
    jQuery("#element-box").css('display','none');
    if(jQuery("#section-box").length){
        //hide Joomla Box J2.5
        jQuery("#secsignidplugincontainer").appendTo("#content-box").css('margin','40px 0');
        jQuery("#secsignid-login-secsignid").appendTo("#form-login").css('margin','20px 0');
    } else {
        //hide Joomla Box J3.x
        jQuery("#secsignidplugincontainer").appendTo("#content");
        jQuery("#secsignid-login-secsignid").appendTo("#form-login").css('margin','20px 0');
    }

    //polling
    var timeTillAjaxSessionStateCheck = 3700;
    var checkSessionStateTimerId = -1;

    function ajaxCheckForSessionState() {
        var secSignIDApi = new SecSignIDApi({posturl: apiurl});
        secSignIDApi.getAuthSessionState(
            jQuery("input[name='secsigniduserid']").val(),
            jQuery("input[name='secsignidrequestid']").val(),
            jQuery("input[name='secsignidauthsessionid']").val(),
            function rMap(responseMap) {
                if (responseMap) {
                    // check if response map contains error message or if authentication state could not be fetched from server.
                    if ("errormsg" in responseMap) {
                        //enable buttons
                        jQuery("#secloginbtn").prop("disabled", false);
                        //clear interval
                        window.clearInterval(checkSessionStateTimerId);
                        return;
                    } else if (!("authsessionstate" in responseMap)) {
                        return;
                    }
                    if (responseMap["authsessionstate"] == undefined || responseMap["authsessionstate"].length < 1) {
                        // got answer without an auth session state. this is not parsable and will throw the error UNKNOWN
                        //enable buttons
                        jQuery("#secloginbtn").prop("disabled", false);
                        //clear interval
                        window.clearInterval(checkSessionStateTimerId);
                        return;
                    }

                    // everything okay. authentication state can be checked
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

                    //3 Login, 24568 show error, 017 do nothing
                    if (authSessionStatus == SESSION_STATE_AUTHENTICATED) {
                        //Log In
                        window.clearInterval(checkSessionStateTimerId);
                        jQuery("#secsignid-accesspass-form").submit();
                    } else if ((authSessionStatus == SESSION_STATE_DENIED) || (authSessionStatus == SESSION_STATE_EXPIRED)
                        || (authSessionStatus == SESSION_STATE_SUSPENDED) || (authSessionStatus == SESSION_STATE_INVALID) || (authSessionStatus == SESSION_STATE_CANCELED)) {
                        //Show Error
                        window.clearInterval(checkSessionStateTimerId);
                        jQuery("#secsignid-page-accesspass").fadeOut(
                            function () {
                                var secsignid = jQuery("input[name='secsigniduserid']").val();
                                var requestId = jQuery("input[name = 'secsignidrequestid']").val();
                                var authsessionId = jQuery("input[name = 'secsignidauthsessionid']").val();

                                //error message
                                var errormsg ="";
                                if (authSessionStatus == SESSION_STATE_DENIED){
                                    errormsg = "SecSign ID session denied.";
                                } else if (authSessionStatus == SESSION_STATE_EXPIRED){
                                    errormsg = "SecSign ID session expired.";
                                } else if (authSessionStatus == SESSION_STATE_SUSPENDED){
                                    errormsg = "SecSign ID session suspended.";
                                } else if (authSessionStatus == SESSION_STATE_INVALID) {
                                    errormsg = "SecSign ID session invalid.";
                                } else if (authSessionStatus == SESSION_STATE_CANCELED) {
                                    errormsg = "SecSign ID session canceled.";
                                }

                                // check if response map contains message.
                                if ("message" in responseMap) {
                                    errormsg = responseMap["message"];
                                }

                                clearSecsignForm();
                                jQuery("#secsignid-page-login").fadeIn();
                                jQuery("#secsignid-error").html(errormsg).fadeIn();
                                jQuery("#secloginbtn").prop("disabled", false);
                                var secSignIDApi = new SecSignIDApi({posturl: apiurl});
                                secSignIDApi.cancelAuthSession(secsignid, requestId, authsessionId, function rMap(responseMap) {
                                });
                            }
                        );
                    }
                }
            }
        );
    }

    //polling timeout
    for (var timerId = 1; timerId < 5000; timerId++) {
        clearTimeout(timerId);
    }

    jQuery(document).ready(function (event) {
        clearSecsignForm();

        //check if cookie available for secsign or password login
        if(docCookies.getItem('secsignJoomlaBackendlogin')==1){
            if(jQuery("#section-box").length){
                //Joomla Box J2.5
                jQuery("#secsignidplugincontainer").fadeOut(
                    function () {
                        jQuery("#element-box").fadeIn();
                    }
                );
            } else {
                //Joomla Box J3.x
                jQuery("#secsignidplugincontainer").fadeOut(
                    function () {
                        jQuery("#element-box").fadeIn();
                    }
                );
            }
        }

        /* Button & Page logic */
        jQuery("#secsignid-pw").click(function (event) {
            event.preventDefault();
            docCookies.setItem('secsignJoomlaBackendlogin', '1', 2592000);
            if(jQuery("#section-box").length){
                //Joomla Box J2.5
                jQuery("#secsignidplugincontainer").fadeOut(
                    function () {
                        jQuery("#element-box").fadeIn();
                    }
                );
            } else {
                //Joomla Box J3.x
                jQuery("#secsignidplugincontainer").fadeOut(
                    function () {
                        jQuery("#element-box").fadeIn();
                    }
                );
            }
        });

        jQuery("#secsignid-login-secsignid").click(function (event) {
            event.preventDefault();
            jQuery("#element-box").fadeOut(
                function () {
                    jQuery("#secsignidplugincontainer").fadeIn();
                    docCookies.setItem('secsignJoomlaBackendlogin', '0', 2592000);
                }
            );
        });

        jQuery("#secsignid-infobutton").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-login").fadeOut(
                function () {
                    jQuery("#secsignid-page-info").fadeIn();
                }
            );
        });

        jQuery("#secsignid-info-secsignid").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-info").fadeOut(
                function () {
                    jQuery("#secsignid-page-login").fadeIn();
                }
            );
        });

        jQuery("#secsignid-questionbutton").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-accesspass").fadeOut(
                function () {
                    jQuery("#secsignid-page-question").fadeIn();
                }
            );
        });

        jQuery("#secsignid-question-secsignid").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-question").fadeOut(
                function () {
                    jQuery("#secsignid-page-accesspass").fadeIn();
                }
            );
        });

        /* Cancel Session */
        jQuery("#secsignid-cancelbutton").click(function (event) {
            event.preventDefault();
            jQuery("#secsignid-page-accesspass").fadeOut(
                function () {
                    var secsignid = jQuery("input[name='secsigniduserid']").val();
                    var requestId = jQuery("input[name = 'secsignidrequestid']").val();
                    var authsessionId = jQuery("input[name = 'secsignidauthsessionid']").val();

                    clearSecsignForm();
                    jQuery("#secsignid-page-login").fadeIn();
                    jQuery("#secloginbtn").prop("disabled", false);

                    var secSignIDApi = new SecSignIDApi({posturl: apiurl});
                    secSignIDApi.cancelAuthSession(secsignid, requestId, authsessionId, function rMap(responseMap) {
                    });
                }
            );
        });

        /* Accesspass */
        jQuery("#secsignid-loginform").submit(function (event) {

                //disable button to prevent frozen state
                jQuery("#secloginbtn").prop("disabled", true);

                var requestid = '';
                if (requestid == '') {
                    //load Accesspass with preloader
                    event.preventDefault();
                    secsignid = jQuery("input[name='secsigniduserid']").val();

                    if (secsignid == "") {
                        //back to login screen
                        jQuery("#secsignid-page-accesspass").fadeOut(
                            function () {
                                //enable buttons
                                jQuery("#secloginbtn").prop("disabled", false);
                                //clear interval
                                window.clearInterval(checkSessionStateTimerId);
                                jQuery("#secsignid-page-login").fadeIn();
                            }
                        );
                        jQuery("#secsignid-error").html(nosecsignid).fadeIn();
                    } else {

                        //if remember me is clicked, set cookie otherwise delete
                        if (jQuery('#rememberme').is(':checked')) {
                            docCookies.setItem('secsignRememberMe', secsignid, 2592000);
                        } else {
                            docCookies.removeItem('secsignRememberMe');
                        }

                        jQuery("#secsignid-page-login").fadeOut(
                            function () {
                                jQuery("#secsignid-page-accesspass").fadeIn();
                                jQuery("#accesspass-secsignid").text(secsignid);
                            }
                        );

                        //request auth session
                        var secsignid = jQuery("input[name='secsigniduserid']").val();
                        var secSignIDApi = new SecSignIDApi({posturl: apiurl});
                        secSignIDApi.requestAuthSession(secsignid, title, url, '', function rMap(responseMap) {

                            if ("errormsg" in responseMap) {
                                //back to login screen
                                jQuery("#secsignid-page-accesspass").fadeOut(
                                    function () {
                                        jQuery("#secsignid-page-login").fadeIn();
                                    }
                                );
                                jQuery("#secsignid-error").html(responseMap["errormsg"]).fadeIn();
                            } else {
                                if ("authsessionicondata" in responseMap && responseMap["authsessionicondata"] != '') {
                                    //fill hidden form
                                    jQuery("input[name='secsigniduserid']").val(responseMap["secsignid"]);
                                    jQuery("input[name='secsignidauthsessionid']").val(responseMap["authsessionid"]);
                                    jQuery("input[name='secsignidrequestid']").val(responseMap["requestid"]);
                                    jQuery("input[name='secsignidserviceaddress']").val(responseMap["serviceaddress"]);
                                    jQuery("input[name='secsignidservicename']").val(responseMap["servicename"]);

                                    //show Accesspass
                                    jQuery("#secsignid-accesspass-img").fadeOut(
                                        function () {
                                            jQuery("#secsignid-accesspass-img").attr('src', 'data:image/png;base64,' + responseMap["authsessionicondata"]).fadeIn();
                                        }
                                    );

                                    //activate polling
                                    checkSessionStateTimerId = window.setInterval(function () {
                                        ajaxCheckForSessionState();
                                    }, timeTillAjaxSessionStateCheck);


                                } else {
                                    //back to login screen
                                    jQuery("#secsignid-page-accesspass").fadeOut(
                                        function () {
                                            jQuery("#secsignid-page-login").fadeIn();
                                            jQuery("#secloginbtn").prop("disabled", false);
                                        }
                                    );
                                    jQuery("#secsignid-error").html(noresponse).fadeIn();
                                }
                            }
                        });
                    }
                }
            }
        );
    });
});
</script>

<?php
//show php or joomla error message for secsign
$session = JFactory::getSession();
$message="";
$message = $session->get('secsignerror');
if($message!=""){
    $message = "<div id='secsignid-error-php'>".$message."</div>";
}
$session->set('secsignerror', "");
?>

<div id="secsignidplugincontainer">
    <div id="secsignidplugin">
        <!-- Page Login -->
        <div id="secsignid-page-login">
            <div class="secsignidlogo"></div>
            <div id="secsignid-error"></div>
            <?php echo $message ?>
            <form id="secsignid-loginform">
                <div class="form-group">
                    <input type="text" class="form-control login-field" value="" placeholder="SecSign ID"
                           id="login-secsignid" name="secsigniduserid" autocapitalize="off" autocorrect="off">
                    <label class="login-field-icon fui-user" for="login-secsignid"></label>
                </div>

                <div id="secsignid-checkbox">
		        <span>
	                <input id="rememberme" name="rememberme" type="checkbox" value="rememberme" checked>
	                <label for="rememberme"><?php echo JText::_('COM_SECSIGNID_FE_1'); ?></label>
	            </span>
                </div>
                <button id="secloginbtn" type="submit">Log in</button>
            </form>
            <div class="secsignid-login-footer">
                <a href="#" class="infobutton" id="secsignid-infobutton">Info</a>
                <a href="#" class="linktext" id="secsignid-pw"><?php echo JText::_('COM_SECSIGNID_FE_2'); ?></a>

                <div class="clear"></div>
            </div>
        </div>

        <!-- Page Password Login -->
        <div id="secsignid-page-pw">
            <div class="secsignidlogo"></div>
            <form>
                <div class="form-group">
                    <input type="text" class="form-control login-field" value="" placeholder="Username"
                           id="login-user" autocapitalize="off" autocorrect="off">
                    <label class="login-field-icon fui-user" for="login-secsignid"></label>
                </div>

                <div class="form-group">
                    <input type="password" class="form-control login-field" value="" placeholder="Password"
                           id="login-pw" autocapitalize="off" autocorrect="off">
                    <label class="login-field-icon fui-user" for="login-secsignid"></label>
                </div>
                <button id="pwdloginbtn" type="submit">Log in</button>
            </form>
            <div class="secsignid-login-footer">
                <a class="linktext" href="#" id="secsignid-login-secsignid"><?php echo JText::_('COM_SECSIGNID_FE_3'); ?></a>

                <div class="clear"></div>
            </div>
        </div>

        <!-- Page Info SecSign Login -->
        <div id="secsignid-page-info">
            <div class="secsignidlogo secsignidlogo-left"></div>
            <h3 id="headinginfo"><?php echo JText::_('COM_SECSIGNID_FE_4'); ?></h3>

            <div class="clear"></div>
            <p><?php echo JText::_('COM_SECSIGNID_FE_5'); ?></p>
            <a id="secsignid-learnmore" href="<?php echo JText::_('COM_SECSIGNID_LINK_MORE'); ?>" target="_blank"><?php echo JText::_('COM_SECSIGNID_FE_6'); ?></a>

            <img style="margin: 0 auto;width: 100%;display: block;max-width: 200px;" src="<?php echo JURI::base() ?>../media/com_secsignid/images/secsignhelp.png">

            <a class="linktext" id="secsignid-info-secsignid" href="#"><?php echo JText::_('COM_SECSIGNID_FE_7'); ?></a>

            <a style="color: #fff; text-decoration: none;" href="<?php echo JText::_('COM_SECSIGNID_LINK_HOW'); ?>" target="_blank"
               id="secsignidapp1"><?php echo JText::_('COM_SECSIGNID_FE_8A'); ?></a>

            <div class="clear"></div>
        </div>

        <!-- Page Accesspass -->
        <div id="secsignid-page-accesspass">
            <div class="secsignidlogo"></div>

            <div id="secsignid-accesspass-container">
                <img id="secsignid-accesspass-img" src="<?php echo JURI::base() ?>../media/com_secsignid/images/preload.gif">
            </div>

            <div id="secsignid-accesspass-info">
                <a href="#" class="infobutton" id="secsignid-questionbutton">Info</a>

                <p class="accesspass-id"><?php echo JText::_('COM_SECSIGNID_FE_9'); ?> <b id="accesspass-secsignid"></b></p>

                <div class="clear"></div>
            </div>

            <form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post" id="secsignid-accesspass-form">
                <button  id="secsignid-cancelbutton" type="submit"><?php echo JText::_('COM_SECSIGNID_FE_10'); ?></button>

                <!-- OK -->
                <input type="hidden" name="check_authsession" id="check_authsession" value="1"/>
                <input type="hidden" name="option" value="com_secsignid"/>
                <input type="hidden" name="task" value="getAuthSessionState"/>

                <!-- Cancel -->
                <input type="hidden" name="cancel_authsession" id="cancel_authsession" value="1"/>
                <input type="hidden" name="option" value="com_secsignid"/>
                <input type="hidden" name="task" value="cancelAuthSession"/>

                <!-- Values -->
                <input type="hidden" name="return" value=""/>
                <input type="hidden" name="secsigniduserid" value=""/>
                <input type="hidden" name="secsignidauthsessionid" value=""/>
                <input type="hidden" name="secsignidrequestid" value=""/>
                <input type="hidden" name="secsignidservicename" value=""/>
                <input type="hidden" name="secsignidserviceaddress" value=""/>
                <input type="hidden" name="secsignidauthsessionicondata" value=""/>
            </form>
        </div>

        <!-- Page Question SecSign Accesspass -->
        <div id="secsignid-page-question">
            <div class="secsignidlogo secsignidlogo-left"></div>
            <h3 id="headingquestion"><?php echo JText::_('COM_SECSIGNID_FE_11'); ?></h3>

            <div class="clear"></div>
            <p><?php echo JText::_('COM_SECSIGNID_FE_12'); ?></p>
            <ol>
                <li><?php echo JText::_('COM_SECSIGNID_FE_13'); ?></li>
                <li><?php echo JText::_('COM_SECSIGNID_FE_14'); ?></li>
                <li><?php echo JText::_('COM_SECSIGNID_FE_15'); ?></li>
                <li><?php echo JText::_('COM_SECSIGNID_FE_16'); ?></li>
            </ol>

            <a class="linktext" id="secsignid-question-secsignid" href="#"><?php echo JText::_('COM_SECSIGNID_FE_17'); ?></a>

            <a style="color: #fff; text-decoration: none;" href="<?php echo JText::_('COM_SECSIGNID_LINK_TRYIT'); ?>" target="_blank" id="secsignidapp2"><?php echo JText::_('COM_SECSIGNID_FE_18'); ?></a>

            <div class="clear"></div>
        </div>
    </div>
</div>