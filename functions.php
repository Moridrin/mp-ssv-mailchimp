<?php
#region Register
use mp_ssv_general\SSV_General;
use mp_ssv_general\User;
use mp_ssv_mailchimp\SSV_MailChimp;
use mp_ssv_users\SSV_Users;

register_activation_hook(__FILE__, 'mp_ssv_general_register_plugin');
#endregion

#region Update Member
/**
 * @param User $user
 *
 * @return mixed
 */
function mp_ssv_mailchimp_update_member($user)
{
    if (SSV_General::usersPluginActive()) {
        $mailchimpMember = array();
        $mergeFields     = array();
        $links           = get_option(SSV_MailChimp::OPTION_MERGE_TAG_LINKS, array());
        foreach ($links as $link) {
            $link                              = json_decode($link, true);
            $mailchimp_merge_tag               = strtoupper($link["tagName"]);
            $member_field                      = $link["fieldName"];
            $value                             = $user->getMeta($member_field);
            $mergeFields[$mailchimp_merge_tag] = $value;
        }
        $mailchimpMember["email_address"] = $user->user_email;
        $mailchimpMember["status"]        = "subscribed";
        $mailchimpMember["merge_fields"]  = $mergeFields;

        $apiKey       = get_option(SSV_MailChimp::OPTION_API_KEY);
        $listID       = get_option(SSV_MailChimp::OPTION_USERS_LIST);
        $memberId     = md5(strtolower($mailchimpMember['email_address']));
        $memberCenter = substr($apiKey, strpos($apiKey, '-') + 1);
        $url          = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/members/' . $memberId;
        $ch           = curl_init($url);

        $json = json_encode($mailchimpMember);

        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $tmp = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (array_key_exists('merge_fields', $tmp)) {
            foreach ($links as $link) {
                $link                              = json_decode($link, true);
                $mailchimp_merge_tag               = strtoupper($link["tagName"]);
                $member_field                      = $link["fieldName"];
                $value                             = $user->getMeta($member_field);
                $mergeFields[$mailchimp_merge_tag] = $value;
            }
        }
    }
    return null;
}

add_action(SSV_General::HOOK_USERS_SAVE_MEMBER, 'mp_ssv_mailchimp_update_member');
#endregion

#region Register Scripts
function mp_ssv_mailchimp_admin_scripts()
{
    if (SSV_General::usersPluginActive()) {
        wp_enqueue_script('mp-ssv-merge-tag-selector', SSV_MailChimp::URL . '/js/mp-ssv-merge-tag-selector.js', array('jquery'));
        wp_localize_script(
            'mp-ssv-merge-tag-selector',
            'merge_tag_settings',
            array(
                'field_options' => array_values(SSV_Users::getInputFieldNames()),
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
    if (SSV_General::usersPluginActive()) {
        $member       = User::getByID($user_id);
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
        curl_close($ch);

        return $user_id;
    }
    return $user_id;
}

add_action('delete_user', 'mp_ssv_mailchimp_remove_member');
#endregion
