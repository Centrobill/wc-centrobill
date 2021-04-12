<?php
global $current_section;
$tabs = apply_filters('wc_centrobill_local_gateways_tab', []);
?>
<div class="wc-centrobill-settings-logo">
    <img src=""/>
</div>

<div class="wc-centrobill-advanced-settings-nav local-gateways">
    <?php foreach ($tabs as $id => $tab) : ?>
        <a class="nav-link" href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $id); ?>">
            <?php echo esc_attr($tab); ?>
        </a>
    <?php endforeach; ?>
</div>
<div class="clear"></div>
