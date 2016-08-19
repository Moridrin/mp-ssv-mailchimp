<?php
include "events-tab.php";
include "frontend-members-tab.php";

function ssv_add_ssv_mailchimp_menu()
{
    add_submenu_page('ssv_settings', 'MailChimp Options', 'MailChimp', 'manage_options', "ssv-mailchimp-options", 'ssv_mailchimp_settings_page');
}

function ssv_mailchimp_settings_page()
{
	$active_tab = "general";
	if(isset($_GET['tab'])) {
		$active_tab = $_GET['tab'];
	}
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if ($active_tab == "general") {
            ssv_mailchimp_settings_page_general_save();
		} else if ($active_tab == "frontend_members") {
            ssv_mailchimp_settings_page_frontend_members_tab_save();
		} else if ($active_tab == "events") {
            ssv_mailchimp_settings_page_events_tab_save();
		}
	}
	?>
	<div class="wrap">
        <h1>SSV MailChimp Options</h1>
		<h2 class="nav-tab-wrapper">
            <a href="?page=ssv-mailchimp-options&tab=general" class="nav-tab <?php if ($active_tab == "general") {
                echo "nav-tab-active";
            } ?>">General</a>
            <?php if (is_plugin_active('ssv-frontend-members/ssv-frontend-members.php')) { ?>
                <a href="?page=ssv-mailchimp-options&tab=frontend_members" class="nav-tab <?php if ($active_tab == "frontend_members") {
                    echo "nav-tab-active";
                } ?>">Frontend Members</a>
			<?php } ?>
            <?php if (is_plugin_active('ssv-events/ssv-events.php')) { ?>
                <a href="?page=ssv-mailchimp-options&tab=events" class="nav-tab <?php if ($active_tab == "events") {
                    echo "nav-tab-active";
                } ?>">Events</a>
			<?php } ?>
            <a href="http://studentensurvival.com/ssv/ssv-mailchimp/" target="_blank" class="nav-tab">Help <img src="<?php echo plugin_dir_url(__DIR__); ?>general/images/link-new-tab.png" width="14px" style="vertical-align:middle"></a>
		</h2>
	</div>
	<?php
	if ($active_tab == "general") {
        ssv_mailchimp_settings_page_general();
	} else if ($active_tab == "frontend_members") {
        ssv_mailchimp_settings_page_frontend_members_tab();
	} else if ($active_tab == "events") {
        ssv_mailchimp_settings_page_events_tab();
	}
}

add_action('admin_menu', 'ssv_add_ssv_mailchimp_menu');


function ssv_mailchimp_settings_page_general()
{
	?>
	<form method="post" action="#">
		<table class="form-table">
			<tr>
				<th scope="row">MailChimp API Key</th>
				<td>
                    <input type="text" class="regular-text" name="ssv_mailchimp_api_key" value="<?php echo get_option('ssv_mailchimp_api_key'); ?>" title="MailChimp API Key"/>
				</td>
			</tr>
			<tr>
				<th scope="row">Max Request</th>
				<td>
                    <label>
                        <input type="number" class="regular-text" name="ssv_mailchimp_max_request" value="<?php echo get_option('ssv_mailchimp_max_request'); ?>" placeholder="10"/>
                        The maximum amount of *|MERGE|* tags returned by Mailchimp.
                    </label>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
	<?php
}

function ssv_mailchimp_settings_page_general_save()
{
    update_option('ssv_mailchimp_api_key', $_POST['ssv_mailchimp_api_key']);
    update_option('ssv_mailchimp_max_request', $_POST['ssv_mailchimp_max_request']);
}
