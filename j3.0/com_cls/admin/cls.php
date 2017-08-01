<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

// Require the base controller
require_once JPATH_COMPONENT.'/helpers/helper.php';

// Initialize the controller
$controller = JControllerLegacy::getInstance('cls');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

function clsLog($action, $description) {
    $db   = JFactory::getDBO();
    $user = JFactory::getUser();
    $description = $db->escape($description);
    $db->setQuery("insert into #__complaint_notifications values(null, {$user->id}, '$action', now(), '$description')");
    $db->query();
}

function gbv_encrypt($message, $password = '', $method = 'aes-256-cbc') {
    $nonce_size = openssl_cipher_iv_length($method);
    $nonce = openssl_random_pseudo_bytes($nonce_size);
    if($password == '')
        $password = gbv_generate_password();

    $key = substr(sha1($password, true), 0, 16);
    list($enc_key, $auth_key) = array(hash_hmac('sha256', 'ENCRYPTION', $key, true), hash_hmac('sha256', 'AUTHENTICATION', $key, true));
    $cipher_text = openssl_encrypt($message, $method, $enc_key, OPENSSL_RAW_DATA, $nonce);

    $mac = hash_hmac('sha256', $nonce.$cipher_text, $auth_key, true);

    return array($password, 'enc:' . base64_encode($mac.$nonce.$cipher_text));
}

function gbv_decrypt($message, $password, $method = 'aes-256-cbc') {
    if(substr($message, 0, strlen('enc:')) == 'enc:')
        $message = substr($message, strlen('enc:'));
    $message = base64_decode($message, true);
    if($message === false)
        return false;

    $key = substr(sha1($password, true), 0, 16);
    list($enc_key, $auth_key) = array(hash_hmac('sha256', 'ENCRYPTION', $key, true), hash_hmac('sha256', 'AUTHENTICATION', $key, true));
    $hash_size = mb_strlen(hash('sha256', '', true), '8bit');
    $mac = mb_substr($message, 0, $hash_size, '8bit');

    $message = mb_substr($message, $hash_size, null, '8bit');
    $calculated = hash_hmac('sha256', $message, $auth_key, true);

    $nonce = openssl_random_pseudo_bytes(32);
    if(hash_hmac('sha256', $mac, $nonce) !== hash_hmac('sha256', $calculated, $nonce))
        return false;

    $nonce_size = openssl_cipher_iv_length($method);
    $nonce = mb_substr($message, 0, $nonce_size, '8bit');
    $cipher_text = mb_substr($message, $nonce_size, null, '8bit');
    $plain_text = openssl_decrypt($cipher_text, $method, $enc_key, OPENSSL_RAW_DATA, $nonce);

    return $plain_text;
}

function gbv_generate_password($length = 10) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($chars);
    $rand = '';
    for($i = 0; $i < $length; $i++)
        $rand .= $chars[mt_rand(0, $len - 1)];

    return $rand;
}