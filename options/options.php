<?php
if (!defined('ABSPATH')) {
    exit;
}

function ssv_add_ssv_mailchimp_menu()
{
    add_submenu_page('ssv_settings', 'MailChimp Options', 'MailChimp', 'manage_options', __FILE__, 'ssv_mailchimp_settings_page');
}

function ssv_mailchimp_settings_page()
{
    $active_tab = "general";
    if (isset($_GET['tab'])) {
        $active_tab = $_GET['tab'];
    }
    $disabled = empty(get_option(SSV_MailChimp::OPTION_API_KEY));
    ?>
    <div class="wrap">
        <h1>Users Options</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=<?= $_GET['page'] ?>&tab=general" class="nav-tab <?= $active_tab == 'general' ? 'nav-tab-active' : '' ?>">General</a>
            <?php if (SSV_General::usersPluginActive() && !$disabled): ?>
                <a href="?page=<?= $_GET['page'] ?>&tab=users" class="nav-tab <?= $active_tab == 'users' ? 'nav-tab-active' : '' ?>">Users</a>
            <?php endif; ?>
            <?php if (SSV_General::eventsPluginActive() && !$disabled): ?>
                <a href="?page=<?= $_GET['page'] ?>&tab=events" class="nav-tab <?= $active_tab == 'events' ? 'nav-tab-active' : '' ?>">Events</a>
            <?php endif; ?>
            <a href="http://bosso.nl/ssv-mailchimp/" target="_blank" class="nav-tab">
                Help <img src="<?= SSV_General::URL ?>/images/link-new-tab.png" width="14px" style="vertical-align:middle">
            </a>
        </h2>
        <?php
        switch ($active_tab) {
            case "general":
                require_once "general.php";
                break;
            case "users":
                require_once "ssv-users.php";
                break;
            case "events":
                require_once "ssv-events.php";
                break;
        }
        ?>
    </div>
    <?php
}

add_action('admin_menu', 'ssv_add_ssv_mailchimp_menu');

function ssv_mailchimp_general_options_page_content()
{
    ?><h2><a href="?page=<?= str_replace(SSV_MailChimp::PATH, 'ssv-mailchimp/', __FILE__) ?>">Mailchimp Options</a></h2><?php
}

add_action(SSV_General::HOOK_GENERAL_OPTIONS_PAGE_CONTENT, 'ssv_mailchimp_general_options_page_content');
