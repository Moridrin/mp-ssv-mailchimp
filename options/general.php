<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 21-1-17
 * Time: 7:38
 */
namespace mp_ssv_mailchimp;
use mp_ssv_general\SSV_General;

if (!defined('ABSPATH')) {
    exit;
}

if (SSV_General::isValidPOST(SSV_MailChimp::ADMIN_REFERER_OPTIONS)) {
    if (isset($_POST['reset'])) {
        SSV_MailChimp::resetOptions();
    } else {
        update_option(SSV_MailChimp::OPTION_API_KEY, SSV_General::sanitize($_POST['api_key']));
        update_option(SSV_MailChimp::OPTION_MAX_REQUEST_COUNT, SSV_General::sanitize($_POST['max_request']));
    }
}
?>
<form method="post" action="#">
    <table class="form-table">
        <tr>
            <th scope="row">MailChimp API Key</th>
            <td>
                <input type="text" class="regular-text" name="api_key" value="<?= esc_html(get_option(SSV_MailChimp::OPTION_API_KEY)) ?>" title="MailChimp API Key"/>
            </td>
        </tr>
        <tr>
            <th scope="row">Max Request</th>
            <td>
                <label>
                    <input type="number" class="regular-text" name="max_request" value="<?= esc_html(get_option(SSV_MailChimp::OPTION_MAX_REQUEST_COUNT)) ?>" placeholder="10"/>
                    The maximum amount of *|MERGE|* tags returned by Mailchimp.
                </label>
            </td>
        </tr>
    </table>
    <?= SSV_General::getFormSecurityFields(SSV_MailChimp::ADMIN_REFERER_OPTIONS); ?>
</form>
