<?php
$payments = get_option('wc_settings_woo_siigo_payment_type_list');
$payment_name = get_post_meta($post->ID, '_siigo_payment_name', true);
$default_payment_id = get_option('wc_settings_woo_siigo_payment_type_id');
$invoice_id = get_post_meta($post->ID, '_siigo_invoice_id', true);
$invoice_number = get_post_meta($post->ID, '_siigo_invoice_name', true);
$order = wc_get_order($post->ID);
$is_paid = $order->is_paid();
?>
<div id="vizapata_sw_integration_invoice_metabox">
  <?php if (!$is_paid) { ?>
    <p><?php _e('The order has no payment yet', 'vizapata_sw_integration'); ?></p>
  <?php } else if (empty($invoice_id)) { ?>
    <p><?php _e('The order is paid but the electronic invoice was not generated yet', 'vizapata_sw_integration'); ?></p>
    <div class="messages"></div>
    <p>
      <label for="vizapata_sw_integration_payment_id"><?php _e('Choose payment method', 'vizapata_sw_integration') ?></label>
      <select id="vizapata_sw_integration_payment_id" class="form-control">
        <?php
        foreach ($payments as $item) {
          if ($item->active) {
        ?>
            <option value="<?php print $item->id ?>" <?php print $default_payment_id == $item->id ? 'selected' : ''; ?>>
              <?php print $item->name; ?>
            </option>
        <?php
          }
        }
        ?>
      </select>
    </p>
    <button type="button" class="button button-secondary"><?php _e('Generate electronic invoice', 'vizapata_sw_integration') ?></button>
  <?php } else {
    $url = add_query_arg(
      array(
        'post' => $post->ID,
        'action' => 'download-invoice',
      ),
      admin_url('post.php')
    );
  ?>
    <p>
      <?php print sprintf(__('Electronic invoice %s has been generated', 'vizapata_sw_integration'), '<strong>' . $invoice_number . '</strong>'); ?>
      <?php print sprintf(__('The payment was registered to "%s"', 'vizapata_sw_integration'), '<strong>' .$payment_name .'</strong>'); ?>
    </p>
    <a href="<?php print wp_nonce_url($url, '_order' . $post->ID); ?>" target="_blank" class="button button-secondary"><?php _e('Download electronic invoice', 'vizapata_sw_integration'); ?></a>
  <?php } ?>
</div>