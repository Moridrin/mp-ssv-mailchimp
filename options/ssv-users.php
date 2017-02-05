<?php
/**
 * Created by PhpStorm.
 * User: moridrin
 * Date: 21-1-17
 * Time: 7:38
 */
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
$links = get_option(SSV_MailChimp::OPTION_MERGE_TAG_LINKS, array());
?>
<form method="post" action="#">
    <div style="overflow-x: auto;">
        <table id="custom-tags-placeholder" class="sortable"></table>
        <button type="button" onclick="mp_ssv_add_new_custom_merge_tag()">Add Link</button>
    </div>
    <script>
        i = <?= count($links) + 1 ?>;
        mp_ssv_sortable_table('custom-tags-placeholder');
        function mp_ssv_add_new_custom_merge_tag() {
            mp_ssv_add_new_merge_tag(i, null, null);
            i++;
        }
        <?php foreach($links as $link): ?>
        <?php $link = json_decode($link, true); ?>
        mp_ssv_add_new_merge_tag('<?= $link->ID ?>', '<?= $link->fieldName ?>', '<?= $link->tagName ?>');
        <?php endforeach; ?>
    </script>
    <?= SSV_General::getFormSecurityFields(SSV_MailChimp::ADMIN_REFERER_OPTIONS); ?>
</form>
