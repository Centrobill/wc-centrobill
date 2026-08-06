<?php
/**
 * Self-submitting form that POSTs the customer to an external HPP
 *
 * @var string $method HTTP method for the form (e.g. POST).
 * @var string $url    Target payment provider URL.
 * @var array  $fields Hidden field name => value pairs to submit.
 */

defined('ABSPATH') || exit;

$method = strtoupper($method) === 'GET' ? 'GET' : 'POST';
?>

<p id="centrobill-redirect-message"><?php esc_html_e('Redirecting…', 'woocommerce-gateway-centrobill'); ?></p>

<form id="centrobill-redirect-form" method="<?php echo esc_attr($method); ?>" action="<?php echo esc_url($url); ?>">
    <?php foreach ($fields as $name => $value) : ?>
        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" />
    <?php endforeach; ?>
    <noscript>
        <button type="submit"><?php esc_html_e('Continue to payment', 'woocommerce-gateway-centrobill'); ?></button>
    </noscript>
</form>

<script type="text/javascript">
    document.getElementById('centrobill-redirect-form').submit();
</script>
