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

require_once 'general/general.php';
require_once "options/options.php";

function ssv_register_ssv_mailchimp()
{
    /* Database */
    global $wpdb;
    /** @noinspection PhpIncludeInspection */
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . "ssv_mailchimp_merge_fields";
    $wpdb->show_errors();
    $sql
        = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `member_tag` varchar(30) NOT NULL,
            `mailchimp_tag` varchar(30) NOT NULL,
            `is_deletable` tinyint(4) NOT NULL DEFAULT '1'
		) $charset_collate;";
    dbDelta($sql);
}

register_deactivation_hook(__FILE__, 'ssv_register_ssv_mailchimp');

function ssv_get_member_fields_select_for_javascript($disabled, $member_field_names)
{
    ob_start();
    echo '<select name="member_\' + id + \'"';
    if ($disabled) {
        echo "disabled";
    }
    echo '><option></option>';

    foreach ($member_field_names as $field) {
        $field = json_decode(json_encode($field), true);
        $name = stripslashes($field["meta_value"]);
        echo '<option value="' . $name . '">' . $name . '</option>';
    }
    echo '</select>';
    return ob_get_clean();
}

function ssv_get_merge_fields_select($id, $tag_name, $disabled, $mailchimp_merge_tags)
{
    if ($id == "") {
        $s = uniqid('', true);
        $id = base_convert($s, 16, 36);
    }
    echo '<select name="mailchimp_' . $id . '" ';
    if ($disabled) {
        echo "disabled";
    }
    echo '>';
    foreach ($mailchimp_merge_tags as $tag) {
        echo '<option value="' . $tag . '" ';
        if ($tag == $tag_name) {
            echo "selected";
        }
        echo '>' . $tag . '</option>';
    }
    echo '</select>';
}

function ssv_get_merge_fields_select_for_javascript($disabled, $mailchimp_merge_tags)
{
    ob_start();
    echo '<select name="mailchimp_\' + id + \'" ';
    if ($disabled) {
        echo "disabled";
    }
    echo '>';
    foreach ($mailchimp_merge_tags as $tag) {
        echo '<option value="' . $tag . '" ';
        echo '>' . $tag . '</option>';
    }
    echo '</select>';
    return ob_get_clean();
}

function ssv_get_merge_fields($listID)
{
    $apiKey = get_option('ssv_mailchimp_api_key');
    $maxRequest = get_option('ssv_mailchimp_max_request');
    if ($maxRequest < 1) {
        $maxRequest = 10;
    }

    $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/merge-fields?count=' . $maxRequest;
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $curl_results = json_decode(curl_exec($ch), true)["merge_fields"];
    $results = array();
    foreach ($curl_results as $result => $value) {
        $results[] = $value["tag"];
    }
    curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $results;
}

function ssv_update_mailchimp_member($user)
{
    $member = array();
    $merge_fields = array();
    global $wpdb;
    $table_name = $wpdb->prefix . "ssv_mailchimp_merge_fields";
    $fields = $wpdb->get_results("SELECT * FROM $table_name");
    foreach ($fields as $field) {
        $field = json_decode(json_encode($field), true);
        $member_field = stripslashes($field["member_tag"]);
        $mailchimp_merge_tag = stripslashes($field["mailchimp_tag"]);
        $merge_fields[$mailchimp_merge_tag] = get_user_meta($user->ID, $member_field, true);
    }
    $merge_fields['FNAME'] = get_user_meta($user->ID, "first_name", true);
    $merge_fields['LNAME'] = get_user_meta($user->ID, "last_name", true);
    $member["email_address"] = $user->user_email;
    $member["status"] = "subscribed";
    $member["merge_fields"] = $merge_fields;

    $apiKey = get_option('ssv_mailchimp_api_key');
    $listID = get_option('mailchimp_member_sync_list_id');
    $memberId = md5(strtolower($member['email_address']));
    $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberId;
    $ch = curl_init($url);

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

function ssv_remove_mailchimp_member($user_id)
{
    $member = FrontendMember::get_by_id($user_id);
    $apiKey = get_option('ssv_mailchimp_api_key');
    $listID = get_option('mailchimp_member_sync_list_id');
    $memberId = md5(strtolower($member->user_email));
    $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberId;
    $ch = curl_init($url);

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

add_action('delete_user', 'ssv_remove_mailchimp_member');

function ssv_get_member_fields_select($member_field_names, $selected_member_field_name, $disabled)
{
    ob_start();
    echo '<select name="member_' . $selected_member_field_name . '" ';
    if ($disabled) {
        echo "disabled";
    }
    echo '><option></option>';
    foreach ($member_field_names as $field) {
        $field = json_decode(json_encode($field), true);
        $name = stripslashes($field["meta_value"]);
        if ($name == $selected_member_field_name) {
            echo '<option value="' . $name . '" selected>' . $name . '</option>';
        } else {
            echo '<option value="' . $name . '">' . $name . '</option>';
        }
    }
    echo '</select>';
    return ob_get_clean();
}
