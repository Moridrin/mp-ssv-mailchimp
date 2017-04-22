<?php
/**
 * Plugin Name: SSV MailChimp
 * Plugin URI: http://bosso.nl/ssv-mailchimp/
 * Description: SSV MailChimp is an add-on for both the SSV Events and the SSV Frontend Members plugin.
 * Version: 3.1.0
 * Author: moridrin
 * Author URI: http://nl.linkedin.com/in/jberkvens/
 * License: WTFPL
 * License URI: http://www.wtfpl.net/txt/copying/
 */
namespace mp_ssv_mailchimp;

if (!defined('ABSPATH')) {
    exit;
}

require_once 'general/general.php';
require_once 'functions.php';
require_once "options/options.php";

#region Class
global $wpdb;
define('SSV_MAILCHIMP_PATH', plugin_dir_path(__FILE__));
define('SSV_MAILCHIMP_URL', plugins_url() . '/ssv-mailchimp/');
define('SSV_MAILCHIMP_CUSTOM_FIELDS_TABLE', $wpdb->prefix . "ssv_mailchimp_custom_fields");

class SSV_MailChimp
{
    const PATH = SSV_MAILCHIMP_PATH;
    const URL = SSV_MAILCHIMP_URL;

    const OPTION_API_KEY = 'ssv_mailchimp__api_key';
    const OPTION_MAX_REQUEST_COUNT = 'ssv_mailchimp__max_request_count';
    const OPTION_USERS_LIST = 'ssv_mailchimp__users_list';
    const OPTION_MERGE_TAG_LINKS = 'ssv_mailchimp__merge_tag_links';
    const OPTION_CREATE_LIST = 'ssv_mailchimp__create_list';

    const ADMIN_REFERER_OPTIONS = 'ssv_mailchimp__admin_referer_options';

    #region resetOptions()
    /**
     * This function sets all the options for this plugin back to their default value
     */
    public static function resetOptions()
    {
        delete_option(self::OPTION_API_KEY);
        delete_option(self::OPTION_MAX_REQUEST_COUNT);
    }

    #endregion

    public static function getLists()
    {
        $apiKey = get_option(self::OPTION_API_KEY);
        if (empty($apiKey)) {
            return array();
        }
        $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists';
        $ch           = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $curl_results = json_decode(curl_exec($ch), true)["lists"];
        curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $curl_results = is_array($curl_results) ? $curl_results : array();
        return array_column($curl_results, 'name', 'id');
    }

    public static function getMergeFields($listID)
    {
        $apiKey = get_option(self::OPTION_API_KEY);
        if (empty($apiKey) || empty($listID)) {
            return array();
        }
        $maxRequest = get_option(self::OPTION_MAX_REQUEST_COUNT);
        if ($maxRequest < 1) {
            $maxRequest = 10;
        }
        $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/merge-fields?count=' . $maxRequest;
        $ch           = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $curl_results = json_decode(curl_exec($ch), true)["merge_fields"];
        curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $curl_results = is_array($curl_results) ? $curl_results : array();
        return array_column($curl_results, 'tag');
    }
}
#endregion
