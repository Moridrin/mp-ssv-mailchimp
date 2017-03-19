<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 5-2-17
 * Time: 22:58
 */
if (!defined('ABSPATH')) {
    exit;
}

if (SSV_General::isValidPOST(SSV_MailChimp::ADMIN_REFERER_OPTIONS)) {
    if (isset($_POST['reset'])) {
        SSV_MailChimp::resetOptions();
    } else {
        update_option(SSV_MailChimp::OPTION_CREATE_LIST, filter_var($_POST['email_on_registration'], FILTER_VALIDATE_BOOLEAN));
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
