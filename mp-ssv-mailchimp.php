<?php
/**
* Plugin Name: SSV MailChimp
* Plugin URI: http://studentensurvival.com/plugins/mp-ssv-mailchimp
* Description: SSV MailChimp is an add-on for both the SSV Events and the SSV Frontend Members plugin.
* Version: 1.0
* Author: Jeroen Berkvens
* Author URI: http://nl.linkedin.com/in/jberkvens/
* License: WTFPL
* License URI: http://www.wtfpl.net/txt/copying/
*/

include_once "options/options.php";

function mp_ssv_register_mp_ssv_mailchimp(){
	if (!is_plugin_active('mp-ssv-events/mp-ssv-events.php') && !is_plugin_active('mp-ssv-frontend-members/mp-ssv-frontend-members.php')) {
		wp_die('Sorry, but this plugin requires <a href="http://studentensurvival.com/plugins/mp-ssv-frontend-members">SSV Frontend Members</a> or <a href="http://studentensurvival.com/plugins/mp-ssv-events">SSV Events</a> to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
	}
}
register_deactivation_hook( __FILE__, 'mp_ssv_register_mp_ssv_mailchimp' );

function mp_ssv_get_member_fields_select_for_javascript($disabled, $member_field_names) {
	?><select name="member_' + id + '" <?php if ($disabled) { echo "disabled"; } ?>><option></option><?php
		foreach ($member_field_names as $field) {
			$field = json_decode(json_encode($field),true);
			$name = stripslashes($field["meta_value"]);
			echo '<option value="'.$name.'">'.$name.'</option>';
		}
	?></select><?php
}

function mp_ssv_get_merge_fields_select($id, $tag_name, $disabled, $mailchimp_merge_tags) {
	if ($id == "") {
		$s = uniqid('', true);
		$id = base_convert($s, 16, 36);
	}
	?><select name="mailchimp_<?php echo $id; ?>" <?php if ($disabled) { echo "disabled"; } ?>><?php
		foreach ($mailchimp_merge_tags as $tag) {
			echo '<option value="'.$tag.'" ';
			if ($tag == $tag_name) { echo "selected"; }
			echo '>'.$tag.'</option>';
		}
		?></select><?php
}

function mp_ssv_get_merge_fields_select_for_javascript($disabled, $mailchimp_merge_tags) {
	?><select name="mailchimp_' + id + '" <?php if ($disabled) { echo "disabled"; } ?>><?php
		foreach ($mailchimp_merge_tags as $tag) {
			echo '<option value="'.$tag.'" ';
			echo '>'.$tag.'</option>';
		}
		?></select><?php
}

function mp_ssv_get_merge_fields($listID) {
	$apiKey = get_option('mp_ssv_mailchimp_api_key');
	$maxRequest = get_option('mp_ssv_mailchimp_max_request');
	if ($maxRequest < 1) {
		$maxRequest = 10;
	}

	$memberCenter = substr($apiKey,strpos($apiKey,'-')+1);
	$url = 'https://' . $memberCenter . '.api.mailchimp.com/3.0/lists/' . $listID . '/merge-fields?count='.$maxRequest;
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
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	return $results;
}

function mp_ssv_update_mailchimp_member($user) {
	$member = array();
	$merge_fields = array();
	$merge_fields['FNAME'] = get_user_meta($user->ID, "first_name", true);
	$merge_fields['LNAME'] = get_user_meta($user->ID, "last_name", true);
	$member["email_address"] = $user->user_email;
	$member["status"] = "subscribed";
	$member["merge_fields"] = $merge_fields;

	$apiKey = get_option('mp_ssv_mailchimp_api_key');
	$listID = get_option('mailchimp_member_sync_list_id');
	$memberId = md5(strtolower($member['email_address']));
	$memberCenter = substr($apiKey,strpos($apiKey,'-')+1);
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

	$curl_results = json_decode(curl_exec($ch), true);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	return $httpCode;
}

function mp_ssv_get_member_fields_select($member_field_names, $selected_member_field_name, $disabled) {
	?>
	<select name="member_<?php echo $selected_member_field_name; ?>" <?php if ($disabled) { echo "disabled"; } ?>><option></option>
		<?php
		foreach ($member_field_names as $field) {
			$field = json_decode(json_encode($field),true);
			$name = stripslashes($field["meta_value"]);
			if ($name == $selected_member_field_name) {
				echo '<option value="'.$name.'" selected>'.$name.'</option>';
			} else {
				echo '<option value="'.$name.'">'.$name.'</option>';
			}
		}
		?>
	</select>
	<?php
}
?>