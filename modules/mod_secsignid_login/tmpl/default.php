<?php
// $Id: default.php,v 1.15 2015/04/16 13:36:50 titus Exp $

// no direct access
defined('_JEXEC') or die;
jimport('joomla.filesystem.file');
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
// check whether jquery is available
if (!JFactory::getApplication()->get('jquery')) {
    JFactory::getApplication()->set('jquery', true);
    $document = JFactory::getDocument();
    $document->addScript(JURI::root() . "media/com_secsignid/js/2.1.1.jquery.min.js");
}
/**
 * add module own CSS stylesheet & js files
 */
$document = JFactory::getDocument();
$document->addScriptDeclaration('
    //Parameters
    var url = "";
    var title = "'.JComponentHelper::getParams('com_secsignid')->get('secsign_frontend_servicename').'";
    var secsignPluginPath = "'.JURI::base().'media/com_secsignid/";
    var apiurl = "'.JURI::base().'media/com_secsignid/bridge/signin-bridge.php";
    var errormsg = "'.JText::_('COM_SECSIGNID_FE_20').'";
    var noresponse = "'.JText::_('COM_SECSIGNID_FE_21').'";
    var nosecsignid = "'.JText::_('COM_SECSIGNID_FE_19').'";
    var secsignid = "";
    var frameoption = "";

    if (url == "") {
        url = document.URL;
    }
    if (title == "") {
        title = document.title;
    }
    if (typeof backend == "undefined") {
        var backend = false;
    }
');
JHtml::_('stylesheet', JUri::base() . 'media/com_secsignid/css/secsignid_layout.css');
JHtml::_('script', JUri::base() . 'media/com_secsignid/bridge/SecSignIDApi.js');
JHtml::_('script', JUri::base() . 'media/com_secsignid/bridge/secsignfunctions.js');
$view = JRequest::getVar('view', 0);
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

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


<?php if ($type == 'logout') : ?>
    <div id="secsignidplugincontainer">
        <noscript>
            <div class="secsignidlogo"></div>
            <p><?php echo JText::_('MOD_SECSIGNID_NO_JS'); ?></p>
            <a style="color: #fff; text-decoration: none;" id="noscriptbtn"
               href="https://www.secsign.com/support/" target="_blank">SecSign Support</a>
        </noscript>
        <div style="display:none;" id="secsignidplugin">
            <!-- Page Login -->
            <div id="secsignid-page-logout">
                <div class="secsignidlogo"></div>
                <div id="secsignid-error"></div>
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


                    <button id="seclogoutbtn" type="submit"><?php echo JText::_('JLOGOUT'); ?></button>
                    <input type="hidden" name="option" value="com_users"/>
                    <input type="hidden" name="task" value="user.logout"/>
                    <input type="hidden" name="return" value="<?php echo $return; ?>"/>
                    <?php echo JHtml::_('form.token'); ?>
                </form>

            </div>
        </div>
    </div>
<?php else : ?>


    <div id="secsignidplugincontainer">
        <noscript>
            <div class="secsignidlogo"></div>
            <p><?php echo JText::_('MOD_SECSIGNID_NO_JS'); ?></p>
            <a style="color: #fff; text-decoration: none;" id="noscriptbtn"
               href="https://www.secsign.com/support/" target="_blank">SecSign Support</a>
        </noscript>
        <div style="display:none;" id="secsignidplugin">
            <!-- Page Login -->
            <div id="secsignid-page-login">
                <div class="secsignidlogo"></div>
                <div id="secsignid-error"></div>
                <?php echo $message ?>
                <form id="secsignid-loginform">
                    <div class="form-group">
                        <input type="text" class="form-control login-field" value="" placeholder="SecSign ID"
                               id="login-secsignid" name="secsigniduserid">
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
                <form action="" method="post" id="login-form">
                    <div class="form-group">
                        <input  id="login-user" type="text" name="username" class="form-control login-field" tabindex="0"
                               size="18" placeholder="Username">
                    </div>
                    <div class="form-group">
                        <input  id="login-pw" type="password" name="password" class="form-control login-field" tabindex="0"
                               size="18" placeholder="Password">
                    </div>
                    <button type="submit" tabindex="0" name="Submit" id="pwdloginbtn">Log in</button>
                    <input type="hidden" name="option" value="com_users">
                    <input type="hidden" name="task" value="user.login">
                    <input type="hidden" name="return" value="<?php echo $return; ?>">
                    <?php echo JHtml::_('form.token'); ?>
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

                <img style="margin: 0 auto;width: 100%;display: block;max-width: 200px;"
                     src="<?php echo JURI::base() ?>media/com_secsignid/images/secsignhelp.png">

                <a class="linktext" id="secsignid-info-secsignid" href="#"><?php echo JText::_('COM_SECSIGNID_FE_7'); ?></a>

                <a style="color: #fff; text-decoration: none;"
                   href="<?php echo JText::_('COM_SECSIGNID_LINK_HOW'); ?>" target="_blank"
                   id="secsignidapp1"><?php echo JText::_('COM_SECSIGNID_FE_8A'); ?></a>

                <div class="clear"></div>
            </div>

            <!-- Page Accesspass -->
            <div id="secsignid-page-accesspass">
                <div class="secsignidlogo"></div>

                <div id="secsignid-accesspass-container">
                    <img id="secsignid-accesspass-img"
                         src="<?php echo JURI::base() ?>media/com_secsignid/images/preload.gif">
                </div>

                <div id="secsignid-accesspass-info">
                    <a href="#" class="infobutton" id="secsignid-questionbutton">Info</a>

                    <p class="accesspass-id"><?php echo JText::_('COM_SECSIGNID_FE_9'); ?> <b id="accesspass-secsignid"></b></p>

                    <div class="clear"></div>
                </div>

                <form action="<?php echo JRoute::_('index.php', true, $params->get('usesecure')); ?>" method="post"
                      id="secsignid-accesspass-form">
                    <button id="secsignid-cancelbutton" type="submit"><?php echo JText::_('COM_SECSIGNID_FE_10'); ?></button>

                    <!-- OK -->
                    <input type="hidden" name="check_authsession" id="check_authsession" value="1"/>
                    <input type="hidden" name="option" value="com_secsignid"/>
                    <input type="hidden" name="task" value="getAuthSessionState"/>

                    <!-- Cancel
                    <input type="hidden" name="cancel_authsession" id="cancel_authsession" value="0"/>
                    -->

                    <!-- Values -->
                    <input type="hidden" name="return" value="<?php echo $return; ?>"/>
                    <input type="hidden" name="secsigniduserid" value=""/>
                    <input type="hidden" name="secsignidauthsessionid" value=""/>
                    <input type="hidden" name="secsignidrequestid" value=""/>
                    <input type="hidden" name="secsignidservicename" value=""/>
                    <input type="hidden" name="secsignidserviceaddress" value=""/>
                    <input type="hidden" name="secsignidauthsessionicondata" value=""/>
                    <?php echo JHtml::_('form.token'); ?>
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

                <a style="color: #fff; text-decoration: none;" class="button-secsign blue"
                   href="<?php echo JText::_('COM_SECSIGNID_LINK_TRYIT'); ?>" target="_blank" id="secsignidapp2"><?php echo JText::_('COM_SECSIGNID_FE_18'); ?></a>

                <div class="clear"></div>
            </div>
        </div>
    </div>


<?php endif; ?>
