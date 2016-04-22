<?php
function mp_ssv_mailchimp_settings_page_frontend_members_tab() {
	$mailchimp_merge_tags = mp_ssv_get_merge_fields(get_option('mailchimp_member_sync_list_id'));
	
	global $wpdb;
	$table_name = $wpdb->prefix."mp_ssv_mailchimp_merge_fields";
	$fields = $wpdb->get_results("SELECT * FROM $table_name");
	$table_name = $wpdb->prefix."mp_ssv_frontend_members_field_meta";
	$member_field_names = $wpdb->get_results("SELECT meta_value FROM $table_name WHERE meta_key = 'name'");
	?>
	<br/>Add & Synchronize members to this MailChimp List.
	<form method="post" action="#">
		<table id="container" class="form-table">
			<tr>
				<th scope="row">List ID</th>
				<td><input type="text" class="regular-text" name="mailchimp_member_sync_list_id" value="<?php echo get_option('mailchimp_member_sync_list_id'); ?>"/></td>
			</tr>
			<tr>
				<th scope="row">Membber Field Name</th>
				<th scope="row">*|MERGE|* tag</th>
			</tr>
			<tr>
				<td><?php echo mp_ssv_get_member_fields_select($member_field_names, "first_name", true); ?></td>
				<td> <?php mp_ssv_get_merge_fields_select("first_Name", "FNAME", true, $mailchimp_merge_tags); ?> </td>
				<td></td>
			</tr>
			<tr>
				<td><?php echo mp_ssv_get_member_fields_select($member_field_names, "last_name", true); ?></td>
				<td><?php mp_ssv_get_merge_fields_select("last_Name", "LNAME", true, $mailchimp_merge_tags); ?></td>
				<td></td>
			</tr>
			<?php 
			foreach ($fields as $field) {
				$field = json_decode(json_encode($field),true);
				$seleted_member_field = stripslashes($field["member_tag"]);
				$selected_mailchimp_merge_tag = stripslashes($field["mailchimp_tag"]);
				?>
				<tr>
					<td><?php echo mp_ssv_get_member_fields_select($member_field_names, $seleted_member_field, false); ?></td>
					<td><?php mp_ssv_get_merge_fields_select($seleted_member_field, $selected_mailchimp_merge_tag, false, $mailchimp_merge_tags); ?></td>
					<td><input type="hidden" name="submit_option_<?php echo $seleted_member_field; ?>"></td>
				</tr>
				<?php
			}
			?>
		</table>
		<button type="button" id="add_field_button" onclick="mp_ssv_add_new_field()">Add Field</button>
		<?php submit_button(); ?>
	</form>
	<script src="https://code.jquery.com/jquery-2.2.0.js"></script>
	<script src="https://code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<script>
	function mp_ssv_add_new_field() {
		var id = Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
		$("#container > tbody:last-child").append(
			$('<tr id="' + id + '">').append(
				$('<td>').append(
					'<?php echo mp_ssv_get_member_fields_select_for_javascript(false, $member_field_names); ?>'
				)
			).append(
				$('<td>').append(
					'<?php echo mp_ssv_get_merge_fields_select_for_javascript(false, $mailchimp_merge_tags); ?>'
				)
			).append(
				$('<td>').append(
					'<input type="hidden" name="submit_option_' + id + '">'
				)
			)
		);
	}
	</script>
	<?php
}

function mp_ssv_mailchimp_settings_page_frontend_members_tab_save() {
	global $wpdb;
	$table_name = $wpdb->prefix."mp_ssv_mailchimp_merge_fields";
	$wpdb->delete($table_name, array('is_deletable' => 1));
	$seleted_member_field = "";
	$selected_mailchimp_merge_tag = "";
	foreach( $_POST as $id => $val ) {
		if ($id == "mailchimp_member_sync_list_id") {
			update_option('mailchimp_member_sync_list_id', $val);
		} else if (strpos($id, "member_") !== false) {
			$seleted_member_field = $val;
		} else if (strpos($id, "mailchimp_") !== false) {
			$selected_mailchimp_merge_tag = $val;
		} else if (strpos($id, "submit_option_") !== false) {
			if ($seleted_member_field != "") {
				$wpdb->insert(
					$table_name,
					array(
						'member_tag' => $seleted_member_field,
						'mailchimp_tag' => $selected_mailchimp_merge_tag
					),
					array(
						'%s',
						'%s'
					) 
				);
			}
		}
	}
}
?>