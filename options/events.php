<?php
namespace mp_ssv_mailchimp;
use mp_ssv_general\SSV_General;

if (!defined('ABSPATH')) {
    exit;
}

if (SSV_General::isValidPOST(SSV_MailChimp::ADMIN_REFERER_OPTIONS)) {
    if (isset($_POST['reset'])) {
        SSV_MailChimp::resetOptions();
    } else {
        update_option(SSV_MailChimp::OPTION_CREATE_LIST, SSV_General::sanitize($_POST['email_on_registration'], 'boolean'));
    }
}
?>
<form method="post" action="#">
    <table class="form-table">
        <tr>
            <th scope="row">Create Lists</th>
            <td>
                <label>
                    <input type="hidden" name="email_on_registration" value="false"/>
                    <input type="checkbox" name="email_on_registration" value="true" <?= checked(get_option(SSV_MailChimp::OPTION_CREATE_LIST), true, false) ?> />
                    Create a mailing list for all new events created. Users registering for this event will automatically be added.
                </label>
            </td>
        </tr>
    </table>
    <?= SSV_General::getFormSecurityFields(SSV_MailChimp::ADMIN_REFERER_OPTIONS); ?>
</form>
