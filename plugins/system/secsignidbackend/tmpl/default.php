<?php

// $Id: default.php,v 1.2 2014/12/15 15:50:07 titus Exp $

// no direct access
defined('_JEXEC') or die;

jimport('joomla.filesystem.file');

//Load language file
$lang = JFactory::getLanguage();
$lang->load('plg_system_secsignid_backend', JPATH_ADMINISTRATOR);

$app = JFactory::getApplication();
$doc = JFactory::getDocument();
?>
<script src="<?php echo JURI::base() . 'modules/mod_secsignid_backend/js/SecSignIDApi.js'; ?>"></script>

<style>
    .accesspass_secsignid_login {
        display:block;
        position:relative;
        width:180px;
        height:240px;
        margin:0 auto;
    }

    .accesspass_icon_secsignid_login {
        display:block;
        position:absolute;
        margin-top:100px;
        margin-left:40px;
        width:88px;
        height:88px;
    }

    .newsitem_text .login-greeting.secsignid_login {
        display:none;
    }

    /*
    .newsitem_text table.table_secsignid_login {
        width:40%;
        float:left;
    }*/

    table.table_secsignid_login {
        position:relative;
        display:block;
        z-index:10000;
    }

    table.table_secsignid_login,
    table.table_secsignid_login tr,
    table.table_secsignid_login tr td,
    fieldset.userdata.secsignid_login {
        border:none;
    }

    fieldset.userdata.secsignid_login {
        padding:0px;
    }

    table.table_secsignid_login,
    fieldset.userdata.secsignid_login {
        text-align:left;
        width:100%;
        margin:0 auto;
    }

    table.table_secsignid_login tr {
        width:100%;
    }

    table.table_secsignid_login td {
        padding:10px 2% 4px 2%‡;
    }

    .button_secsignid_login {
        display:block;
        position:relative;
        height:27px;
        line-height:16px;

        color:#333;
        border-style:solid;
        border-width:thin;
        border-top-color: #BBB;
        border-right-color:#BBB;
        border-bottom-color:#CCC;
        border-left-color:#BBB;
        background: -webkit-gradient(linear, left top, left bottom, from(#FFF), to(#e1e1e1));
        background: -webkit-linear-gradient(top, #FFF, #e1e1e1);
        background: -moz-linear-gradient(top,  #FFF,  #e1e1e1);
        background: -o-linear-gradient(top, #FFF, #e1e1e1);
        background: linear-gradient(to bottom, #FFF, #e1e1e1);

        border-radius: 3px;
        background-clip:padding-box;
    }

    .button_secsignid_login.blue {
        background: -webkit-gradient(linear, left top, left bottom, from(#7eb5ff), to(#0070b5));
        background: -webkit-linear-gradient(top, #7eb5ff, #0070b5);
        background: -moz-linear-gradient(top,  #7eb5ff,  #0070b5);
        background: -o-linear-gradient(top, #7eb5ff, #0070b5);
        background: linear-gradient(to bottom, #7eb5ff, #0070b5);

        border:solid 1px #0070b5;
        color:#FFF;
    }

    .button_secsignid_login:hover {
        background: -webkit-gradient(linear, left top, left bottom, from(#efefef), to(#fff));
        background: -webkit-linear-gradient(top, #efefef, #fff);
        background: -moz-linear-gradient(top,  #efefef,  #fff);
        background: -o-linear-gradient(top, #efefef, #fff);
        background: linear-gradient(to bottom, #efefef, #fff);
        box-shadow:0px 0px 4px 1px rgba(0, 51, 102, 0.3);

        cursor:pointer;
    }

    .button_secsignid_login.blue:hover {
        background: -webkit-gradient(linear, left top, left bottom, from(#85b9ff), to(#02639f));
        background: -webkit-linear-gradient(top, #85b9ff, #02639f);
        background: -moz-linear-gradient(top,  #85b9ff,  #02639f);
        background: -o-linear-gradient(top, #85b9ff, #02639f);
        background: linear-gradient(to bottom, #85b9ff, #02639f);
        box-shadow:0px 0px 6px 1px rgba(0, 51, 102, 0.4);
    }

    #secsign #modlgn-username {
        width: 100%;
        padding: 4px;
        margin-top: 4px;
        box-sizing: border-box;
        -moz-box-sizing: border-box;
        -webkit-box-sizing: border-box;
    }

    #secsign .secsign_row {
        padding: 10px 0 0 0;
    }

    #secsign .button_secsignid_login {
        width: 100%;
        margin-top: 8px;
        display: inline-block;
    }

    #secsign .button_secsignid_form {
        width: 100%;
        margin-top: 8px;
        display: inline-block;
    }

    #secsign .secsignid_logout{
        width:100%;
        max-width:150px;
    }

    #secsign .button_secsignid_big {
        width: 48%;
    }

    #secsign .button_secsignid_right {
        float: right;
    }

    #secsign .secsignid_no_graphic{
        background: none !important;
    }

    .accesspass_icon_secsignid_login_small {
        display: block;
        position: relative;
        width: 100%;
        max-width: 100px;
        height: auto;
        margin: 0 auto;
    }

    #secsign .secsignid_error {
        color: #ff0000;
    }

    .clear {
        clear: both;
    }

    .secsignid_wrapper {
        max-width: 600px;
        margin: 50px auto;
    }

</style>
<script>

    //JS for responsive layout
    window.onload= function() {
        var width = document.getElementById("secsign").offsetWidth;
        responsive(width);
    };

    window.addEventListener('resize', function(){
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
            if(accesspass_bg) accesspass_bg.className = "accesspass_secsignid_login";
            if(accesspass) accesspass.className = "accesspass_icon_secsignid_login";
        } else {
            if(accesspass_bg) accesspass_bg.className = "secsignid_no_graphic";
            if(accesspass) accesspass.className = "accesspass_icon_secsignid_login accesspass_icon_secsignid_login_small";
        }
    }
</script>
<?php
$root = JURI::root();
if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) {
    $root = JString::str_ireplace("http:", "https:", $root);
}
?>

<body>

<!-- polling -->
<script>
    var timeTillAjaxSessionStateCheck = 3700;
    var checkSessionStateTimerId = -1;

    function ajaxCheckForSessionState(){
        var secSignIDApi = new SecSignIDApi({posturl:"<?php echo JUri::base(true)?>/modules/mod_secsignid_backend/bridge/signin-bridge.php"});
        secSignIDApi.getAuthSessionState(
            '<?php echo $secsignid_params['secsignid'] ?>',
            '<?php echo $secsignid_params['requestid'] ?>',
            '<?php echo $secsignid_params['authsessionid'] ?>',
            function rMap(responseMap) {
                if(responseMap) {
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
                        jQuery("button[name='check_authsession']").click();
                    }
                }
            }
        );
    }

    for (var timerId = 1; timerId < 5000; timerId++) {
        clearTimeout(timerId);
    }

    jQuery(document).ready(function () {

        checkSessionStateTimerId = window.setInterval(function () {
            ajaxCheckForSessionState();

        }, timeTillAjaxSessionStateCheck);
    });


</script>
<!-- end polling -->


<div id="secsign" class="secsignid_wrapper">
    <?php
    $pretext = JComponentHelper::getParams('com_secsignid')->get('secsign_backend_pretext');
    if($pretext){
        echo "<p style='text-align: center'>".$pretext."<br>&nbsp;</p>";
    }
    ?>
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
        <?php
        switch ($errormsg) {
            case "pending":
                echo '<p class="secsignid_error">'. JText::_('MOD_SECSIGNID_ACCESSPASS_PENDING') .'</p>';
                break;
            case "denied":
                echo '<p class="secsignid_error">'.JText::_('MOD_SECSIGNID_ACCESSPASS_DENIED') .'</p>';
                break;
            case "noresponse":
                echo '<p class="secsignid_error">'.JText::_('MOD_SECSIGNID_ACCESSPASS_NORESPONSE') .'</p>';
                break;
            default:
                echo '<p>'.JText::_('MOD_SECSIGNID_ACCESSPASS_HELP');
        }
        ?>
    </p>

    <div class="secsign_row">
        <form class="button_secsignid_form" id="secsignid_cancel"
              action="index.php?plugin=secsignauth&method=verify"
              method="post" id="login-form-secsignid">
            <div class="cancel-button">
                <button style="width:100%;" class="button_secsignid_login" value="1"
                        name="cancel_authsession"
                        type="submit"><?php echo JText::_('MOD_SECSIGNID_CANCEL'); ?></button>
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
              action="index.php?plugin=secsignauth&method=verify"
              method="post" id="login-form-secsignid">
            <fieldset class="userdata secsignid_login">
                <button style="width:100%;float:right;" class="button_secsignid_login blue" value="1"
                        name="check_authsession" type="submit"><?php echo 'OK' ?></button>
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
        <?php
        $posttext = JComponentHelper::getParams('com_secsignid')->get('secsign_backend_posttext');
        if($posttext){
            echo "<p style='text-align: center'>".$posttext."</p>";
        }
        ?>
    </div>
</div>
</body>