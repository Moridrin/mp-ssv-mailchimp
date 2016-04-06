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

function mp_ssv_get_member_fields_select_for_javascript($disabled, $fields_in_tab) {
	?><select name="member_' + id + '" <?php if ($disabled) { echo "disabled"; } ?>><option></option><?php
	foreach ($fields_in_tab as $field) {
		$field = json_decode(json_encode($field),true);
		$database_component = stripslashes($field["component"]);
		$title = stripslashes($field["title"]);
		if (strpos($database_component, "name=\"") !== false) {
			$identifier = preg_replace("/.*name=\"/","",stripslashes($database_component));
			$identifier = preg_replace("/\".*/","",$identifier);
			$identifier = strtolower($identifier);
			echo "<option>".$identifier."</option>";
		} else if (strpos($database_component, "select") !== false || strpos($database_component, "radio") !== false || strpos($database_component, "role checkbox") !== false) {
			$identifier = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '_', str_replace(" ", "_", $title)));
			echo "<option>".$identifier."</option>";
		}
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
?>