<?php
/**
 * Plugin Name: SSV MailChimp
 * Plugin URI: http://studentensurvival.com/plugins/ssv-mailchimp
 * Description: SSV MailChimp is an add-on for both the SSV Events and the SSV Frontend Members plugin.
 * Version: 1.0
 * Author: Jeroen Berkvens
 * Author URI: http://nl.linkedin.com/in/jberkvens/
 * License: WTFPL
 * License URI: http://www.wtfpl.net/txt/copying/
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once 'general/general.php';
require_once "options/options.php";


if (SSV_General::usersPluginActive()) {
    #region Update Member
    function mp_ssv_mailchimp_update_member($user)
    {
        $member       = array();
        $merge_fields = array();
        global $wpdb;
        $table_name = $wpdb->prefix . "ssv_mailchimp_merge_fields";
        $fields     = $wpdb->get_results("SELECT * FROM $table_name");
        foreach ($fields as $field) {
            $field                              = json_decode(json_encode($field), true);
            $member_field                       = stripslashes($field["member_tag"]);
            $mailchimp_merge_tag                = stripslashes($field["mailchimp_tag"]);
            $merge_fields[$mailchimp_merge_tag] = get_user_meta($user->ID, $member_field, true);
        }
        $merge_fields['FNAME']   = get_user_meta($user->ID, "first_name", true);
        $merge_fields['LNAME']   = get_user_meta($user->ID, "last_name", true);
        $member["email_address"] = $user->user_email;
        $member["status"]        = "subscribed";
        $member["merge_fields"]  = $merge_fields;

        $apiKey       = get_option('ssv_mailchimp_api_key');
        $listID       = get_option('mailchimp_member_sync_list_id');
        $memberId     = md5(strtolower($member['email_address']));
        $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberId;
        $ch           = curl_init($url);

        $json = json_encode($member);

        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        json_decode(curl_exec($ch), true);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode;
    }

    add_action(SSV_General::HOOK_USERS_SAVE_MEMBER, 'mp_ssv_mailchimp_update_member');
    #endregion

    #region Register Scripts
    function mp_ssv_mailchimp_admin_scripts()
    {
        wp_enqueue_script('mp-ssv-merge-tag-selector', SSV_MailChimp::URL . '/js/mp-ssv-merge-tag-selector.js', array('jquery'));
        wp_localize_script(
            'mp-ssv-merge-tag-selector',
            'settings',
            array(
                'field_options' => array_values(SSV_Users::getInputFieldNames()),
                'tag_options'   => SSV_MailChimp::getMergeFields(get_option(SSV_MailChimp::OPTION_USERS_LIST)),
            )
        );
    }

    add_action('admin_enqueue_scripts', 'mp_ssv_mailchimp_admin_scripts');
    #endregion
}

#region Delete Member
function mp_ssv_mailchimp_remove_member($user_id)
{
    $member       = FrontendMember::get_by_id($user_id);
    $apiKey       = get_option('ssv_mailchimp_api_key');
    $listID       = get_option('mailchimp_member_sync_list_id');
    $memberId     = md5(strtolower($member->user_email));
    $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberId;
    $ch           = curl_init($url);

    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    json_decode(curl_exec($ch), true);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode;
}

add_action('delete_user', 'mp_ssv_mailchimp_remove_member');
#endregion

#region Class
define('SSV_MAILCHIMP_PATH', plugin_dir_path(__FILE__));
define('SSV_MAILCHIMP_URL', plugins_url() . '/ssv-mailchimp/');

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
        $apiKey     = get_option(self::OPTION_API_KEY);
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
        $apiKey     = get_option(self::OPTION_API_KEY);
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
