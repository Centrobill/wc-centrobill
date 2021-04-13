<?php
defined('ABSPATH') || exit;
$tabs = apply_filters('wc_centrobill_settings_nav_tabs', []);
?>

<style type="text/css">
    .wc-centrobill-settings-nav {
        margin: 1em 0;
        font-size: 1.1em;
    }
    .wc-centrobill-settings-nav .nav-link:nth-child(n+2) {
        margin-left: 0.5em;
    }
    .wc-centrobill-settings-nav .nav-link-active {
        color: #555;
        font-weight: 700;
    }
</style>

<div class="wc-centrobill-settings-logo">
    <img src="<?php echo wc_centrobill_image_url('centrobill_logo.png'); ?>" />
</div>

<div class="wc-centrobill-settings-nav">
    <?php foreach ($tabs as $id => $tab) : ?>
        <?php if ($_GET['section'] === $id) { $active = 'nav-link-active'; } else { $active = ''; } ?>
        <a class="nav-link <?php echo $active; ?>" href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $id); ?>">
            <?php echo esc_attr($tab); ?>
        </a>
    <?php endforeach; ?>
</div>
<div class="clear"></div>
