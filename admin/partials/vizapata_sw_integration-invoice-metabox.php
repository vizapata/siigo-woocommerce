<?php
$invoice_id = get_post_meta($post->ID, '_siigo_invoice_id', true);
$invoice_number = get_post_meta($post->ID, '_siigo_invoice_name', true);
$order = wc_get_order($post->ID);
$is_paid = $order->is_paid();
?>
<div>
  <?php if (!$is_paid) { ?>
    <p><?php _e('The order has no payment yet', 'vizapata_sw_integration'); ?></p>
  <?php } else if (empty($invoice_id)) { ?>
    <p><?php _e('The order is paid but the electronic invoice was not generated yet', 'vizapata_sw_integration'); ?></p>
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
    <p><?php echo sprintf(__('Electronic invoice %s has been generated', 'vizapata_sw_integration'), '<strong>' . $invoice_number . '</strong>'); ?></p>
    <a href="<?php echo  wp_nonce_url($url, '_order' . $post->ID); ?>" target="_blank" class="button button-secondary"><?php _e('Download electronic invoice', 'vizapata_sw_integration'); ?></a>
  <?php } ?>
</div>