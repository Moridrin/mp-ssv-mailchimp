<?php
#region Register
use mp_ssv_events\models\Event;
use mp_ssv_events\models\Registration;
use mp_ssv_general\SSV_General;
use mp_ssv_general\User;
use mp_ssv_mailchimp\SSV_MailChimp;
use mp_ssv_users\SSV_Users;

register_activation_hook(__FILE__, 'mp_ssv_general_register_plugin');
register_activation_hook(SSV_MAILCHIMP_PATH . 'ssv-mailchimp.php', 'mp_ssv_general_register_plugin');
#endregion

#region Update Member From User
/**
 * @param User|int $user
 */
function mp_ssv_mailchimp_update_member_from_user($user)
{
    $user = $user instanceof User ? $user : User::getByID($user);
    $listID = get_option(SSV_MailChimp::OPTION_USERS_LIST);
    mp_ssv_mailchimp_update_member($user, $listID);
}
#endregion

#region Update Member From Registration
/**
 * @param Registration $registration
 */
function mp_ssv_mailchimp_update_member_from_registration($registration)
{
    $user   = $registration->user;
    $listID = $registration->event->mailchimpList;
    mp_ssv_mailchimp_update_member($user, $listID);
}

#endregion

#region Update Member
/**
 * @param User   $user
 * @param string $listID
 */
function mp_ssv_mailchimp_update_member($user, $listID)
{
    $mailchimpMember = array();
    $mergeFields     = array();
    $links           = get_option(SSV_MailChimp::OPTION_MERGE_TAG_LINKS, array());
    foreach ($links as $link) {
        $link                            = json_decode($link, true);
        $mailchimpMergeTag               = strtoupper($link["tagName"]);
        $memberField                     = $link["fieldName"];
        $value                           = $user->getMeta($memberField);
        $mergeFields[$mailchimpMergeTag] = $value;
    }
    $mailchimpMember["email_address"] = $user->user_email;
    $mailchimpMember["status"]        = "subscribed";
    $mailchimpMember["merge_fields"]  = $mergeFields;

    $apiKey       = get_option(SSV_MailChimp::OPTION_API_KEY);
    $memberId     = md5(strtolower($mailchimpMember['email_address']));
    $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberId;

    $json     = json_encode($mailchimpMember);
    $auth     = base64_encode('user:' . $apiKey);
    $args     = array(
        'headers' => array(
            'Authorization' => 'Basic ' . $auth,
        ),
        'body'    => $json,
        'method'  => 'PUT',
    );
    $response = json_decode(wp_remote_request($url, $args)['body'], true);
    if (array_key_exists('merge_fields', $response)) {
        foreach ($links as $link) {
            $link                            = json_decode($link, true);
            $mailchimpMergeTag               = strtoupper($link["tagName"]);
            $memberField                     = $link["fieldName"];
            $value                           = $user->getMeta($memberField);
            $mergeFields[$mailchimpMergeTag] = $value;
        }
    }
}

add_action('user_register', 'mp_ssv_mailchimp_update_member_from_user');
add_action(SSV_General::HOOK_USERS_SAVE_MEMBER, 'mp_ssv_mailchimp_update_member_from_user');
add_action(SSV_General::HOOK_EVENTS_NEW_REGISTRATION, 'mp_ssv_mailchimp_update_member_from_registration');
#endregion

#region Event Created
/**
 * @param Event $event
 *
 * @return mixed
 */
function mp_ssv_mailchimp_event_created($event)
{
    if (get_option(SSV_Mailchimp::OPTION_CREATE_LIST) && SSV_General::eventsPluginActive()) {
        $author  = User::getByID($event->post->post_author);
        $newList = array(
            'name'                => $event->getTitle(),
            'contact'             => array(
                'company'  => get_bloginfo(),
                'address1' => $author->getMeta('address_street'),
                'city'     => $author->getMeta('address_city'),
                'state'    => $author->getMeta('address_state'),
                'zip'      => $author->getMeta('address_zip'),
                'country'  => $author->getMeta('address_country'),
            ),
            'permission_reminder' => 'You\'ve signed up for ' . $event->getTitle() . ' on ' . get_bloginfo() . '.',
            'campaign_defaults'   => array(
                'from_name'  => $author->display_name,
                'from_email' => $author->user_email,
                'subject'    => '',
                'language'   => 'en',
            ),
            'email_type_option'   => false,
        );

        $apiKey       = get_option(SSV_MailChimp::OPTION_API_KEY);
        $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/';

        $json   = json_encode($newList);
        $auth   = base64_encode('user:' . $apiKey);
        $args   = array(
            'headers' => array(
                'Authorization' => 'Basic ' . $auth,
            ),
            'body'    => $json,
        );
        $listID = json_decode(wp_remote_post($url, $args)['body'], true)['id'];
        update_post_meta($event->getID(), 'mailchimp_list', $listID);
    }
    return null;
}

add_action(SSV_General::HOOK_USERS_NEW_EVENT, 'mp_ssv_mailchimp_event_created');
#endregion

#region Register Scripts
function mp_ssv_mailchimp_admin_scripts()
{
    wp_enqueue_script('mp-ssv-merge-tag-selector', SSV_MailChimp::URL . '/js/mp-ssv-merge-tag-selector.js', array('jquery'));
    if (SSV_General::usersPluginActive() && get_option(SSV_MailChimp::OPTION_CREATE_LIST)) {
        wp_localize_script(
            'mp-ssv-merge-tag-selector',
            'merge_tag_settings',
            array(
                'field_options' => array_values(SSV_Users::getInputFieldNames()),
                'tag_options'   => SSV_MailChimp::getMergeFields(get_option(SSV_MailChimp::OPTION_USERS_LIST)),
            )
        );
    } else {
        global $wpdb;
        $table  = $wpdb->usermeta;
        $fields = $wpdb->get_results("SELECT meta_key FROM $table");
        $fields = array_column($fields, 'meta_key');
        wp_localize_script(
            'mp-ssv-merge-tag-selector',
            'merge_tag_settings',
            array(
                'field_options' => $fields,
                'tag_options'   => SSV_MailChimp::getMergeFields(get_option(SSV_MailChimp::OPTION_USERS_LIST)),
            )
        );
    }
}

add_action('admin_enqueue_scripts', 'mp_ssv_mailchimp_admin_scripts');
#endregion

#region Delete Member
function mp_ssv_mailchimp_remove_member($user_id)
{
    $member       = User::getByID($user_id);
    $apiKey       = get_option(SSV_Mailchimp::OPTION_API_KEY);
    $listID       = get_option(SSV_Mailchimp::OPTION_USERS_LIST);
    $memberId     = md5(strtolower($member->user_email));
    $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
    $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberId;

    $auth = base64_encode('user:' . $apiKey);
    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . $auth,
        ),
        'method'  => 'DELETE',
    );
    wp_remote_request($url, $args);

    return $user_id;
}

add_action('delete_user', 'mp_ssv_mailchimp_remove_member');
#endregion

#region Update Settings Message.
function mp_ssv_mailchimp_update_settings_notification()
{
    if (empty(get_option(SSV_MailChimp::OPTION_API_KEY))) {
        ?>
        <div class="update-nag notice">
            <p>You still need to set the API Key for SSV MailChimp.</p>
            <p><a href="/wp-admin/admin.php?page=ssv-mailchimp-settings&tab=general">Set Now</a></p>
        </div>
        <?php
    }
    if (empty(get_option(SSV_MailChimp::OPTION_USERS_LIST)) && !get_option(SSV_MailChimp::OPTION_IGNORE_USERS_LIST_MESSAGE)) {
        ?>
        <div class="update-nag notice">
            <p>You still need to set the users list (without this, the users will not be synced with MailChimp).</p>
            <p><a href="/wp-admin/admin.php?page=ssv-mailchimp-settings&tab=users">Set Now</a></p>
            <p><a href="/wp-admin/admin.php?page=ssv-mailchimp-settings&tab=users&action=ignore_message">Dismiss</a></p>
        </div>
        <?php
    }
}

add_action('admin_notices', 'mp_ssv_mailchimp_update_settings_notification');
#endregion
