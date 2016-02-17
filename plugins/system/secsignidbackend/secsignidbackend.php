<?php

// $Id: secsignidbackend.php,v 1.2 2014/12/15 15:50:07 titus Exp $


defined('_JEXEC') or die;
include_once JPATH_ROOT . '/media/com_secsignid/SecSignIDApi/phpApi/SecSignIDApi.php';
jimport('joomla.application.component.helper');

class plgSystemSecsignidbackend extends JPlugin
{
    private $_is_varified = false;
    private $_enable_for;

    public function __construct(&$subject, $config)
    {
        // call Parent construstor
        parent::__construct($subject, $config);
        $this->_enable_for = JFactory::getUser()->get('id');

        $app = JFactory::getApplication();
        if ($app->isSite()) {
            return true;
        }

        if ($this->_enable_for) {
            $this->_is_varified = $this->_isVerified();
        }

        $this->loadLanguage();
    }

    /**
     * Display Accesspass for SecSignId
     */
    function onAfterRender()
    {
        $secsignmode = JComponentHelper::getParams('com_secsignid')->get('secsign_mode');

        //only if selected in joomla admin backend
        if ($secsignmode == 'secsignidandjoomla') {

            $app = JFactory::getApplication();
            if (!$this->_enable_for || $this->_is_varified || $app->isSite()) {
                return true;
            }

            $secsignid = $this->getSecSignId();

            //log in if user has no secsignid
            if ($secsignid == null) {
                return true;
            }

            $buffer = JResponse::getBody();
            $session = JFactory::getSession();
            ob_start();

            // contact secsign id server and request accesspass
            try {
                $secSignIDApi = new SecSignIDApi();
                $secsignid_params = $session->get('secsignid_params');
                if (!$secsignid_params) {
                    $servicename = JComponentHelper::getParams('com_secsignid')->get('secsign_backend_servicename');
                    $authsession = $secSignIDApi->requestAuthSession($secsignid, $servicename, $_SERVER['SERVER_NAME']);
                    if (isset($authsession)) {
                        $secsignid_params = $authsession->getAuthSessionAsArray();
                        $secsignid_params['secsignid'] = $secsignid;
                    }
                    $session->set('secsignid_params', $secsignid_params);
                }
            } catch (Exception $e) {
                JLog::addLogger(array('text_file' => 'secsignadmin.log'));
                JLog::add('An error occured when requesting AccessPass: ' . $e->getMessage(), JLog::ERROR, 'secsignadmin');
            }

            $errormsg = $session->get('errormsg');
            require_once 'tmpl/default.php';

            $secsign_html = ob_get_contents();
            ob_end_clean();

            $buffer = preg_replace('%<body.*>([\w\W]*)</body>%i', $secsign_html, $buffer);
            JResponse::setBody($buffer);
        }
    }

    /**
     * Check response and verify Access Code.
     */
    private function verify()
    {
        $jinput = JFactory::getApplication()->input;
        $app = JFactory::getApplication();
        $message = "";
        $cancel = false;

        //ok
        if ($jinput->get('check_authsession')) {
            try {
                // create a new session instance which is needed to check its status
                $authsession = new AuthSession();
                $authsession->createAuthSessionFromArray(array(
                    'requestid' => $jinput->get('secsignidrequestid'),
                    'secsignid' => $this->getSecSignId(),
                    'authsessionid' => $jinput->get('secsignidauthsessionid'),
                    'servicename' => $jinput->get('secsignidservicename'),
                    'serviceaddress' => $jinput->get('secsignidserviceaddress')
                ));

                $secSignIDApi = new SecSignIDApi();
                $authSessionState = $secSignIDApi->getAuthSessionState($authsession);
                $session = JFactory::getSession();

                if ($authSessionState == AuthSession::AUTHENTICATED) {
                    $secSignIDApi->releaseAuthSession($authsession);
                    $user = $session->get('user');
                    $user->secsignauth = $this->_is_varified;
                    $session->set('user', $user);
                    $session->set('secsignid_params', null);
                    $session->set('errormsg', null);
                    $redirect_url = $app->input->get('redirect', 'index.php');
                    $app->redirect($redirect_url);
                } else if ($authSessionState == AuthSession::DENIED) {
                    $session->set('errormsg', 'denied');
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
                    JLog::addLogger(array('text_file' => 'secsignadmin.log'));
                    JLog::add('Auth session expired or connection error.', JLog::WARNING, 'secsignadmin');
                    $redirect_url = $app->input->get('redirect', 'index.php');
                    $app->redirect($redirect_url);
                }
            } catch (Exception $e) {
                $session->set('errormsg', 'noresponse');
                JLog::addLogger(array('text_file' => 'secsignadmin.log'));
                JLog::add('An error occured when verifying AccessPass: ' . $e->getMessage(), JLog::ERROR, 'secsignadmin');
                $redirect_url = $app->input->get('redirect', 'index.php');
                $app->redirect($redirect_url);
            }
        }

        //cancel
        if ($jinput->get('cancel_authsession') or $cancel == true) {
            $secSignIDApi = new SecSignIDApi();
            $authsession = new AuthSession();
            $authsession->createAuthSessionFromArray(array(
                'requestid' => $jinput->get('secsignidrequestid'),
                'secsignid' => $this->getSecSignId(),
                'authsessionid' => $jinput->get('secsignidauthsessionid'),
                'servicename' => $jinput->get('secsignidservicename'),
                'serviceaddress' => $jinput->get('secsignidserviceaddress')
            ));
            $secSignIDApi->cancelAuthSession($authsession);
            $session = JFactory::getSession();
            $session->set('secsignid_params', null);
            $session->set('errormsg', null);
            // redirect to logout
            $logoutLink = JRoute::_('index.php?option=com_login&task=logout&' . JSession::getFormToken() . '=1');
            $app->redirect(htmlspecialchars_decode($logoutLink));
        }
    }

    /**
     * Returns SecSignId for specific user
     */
    function getSecSignId()
    {
        $user = JFactory::getUser();
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query
            ->select($db->quoteName(array('secsignid')))
            ->from($db->quoteName('#__secsignid_login'))
            ->where($db->quoteName('joomla_user_id') . ' = ' . $db->quote($user->id));
        $db->setQuery($query);
        $secsignid = $db->loadResult();
        return $secsignid;
    }

    /**
     * Check if user is verified.
     */
    private function _isVerified()
    {
        $user = JFactory::getSession()->get('user');
        if (!($user instanceof JUser)) {
            return false;
        }

        if (isset($user->secsignauth)) {
            return true;
        }
        return false;
    }

    /**
     * Get the verify method from parameter
     */
    function onAfterRoute()
    {
        if (!$this->_enable_for) {
            return true;
        }
        $input = JFactory::getApplication()->input;

        if ('secsignauth' != strtolower($input->get('plugin', false))) {
            return true;
        }
        $method = $input->get('method');
        echo $this->$method();
        exit;
    }
}


