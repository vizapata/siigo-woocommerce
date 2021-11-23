<?php

class Vizapata_sw_integration_Order
{
	public function add_meta_boxes()
	{
		add_meta_box('vizapata_sw_integration_invoice', __('Electronic invoice', 'vizapata_sw_integration'), array($this, 'the_meta_box'), 'shop_order');
	}

	public function the_meta_box($post)
	{
		include_once plugin_dir_path(__FILE__) . 'partials' . DIRECTORY_SEPARATOR . 'vizapata_sw_integration-invoice-metabox.php';
	}

	function admin_enqueue_scripts($hook)
	{
		global $typenow, $post;
		if ($typenow == 'shop_order') {
			wp_register_script('vizapata_sw_integration_metabox', plugins_url('js/metabox-order.js', __FILE__), array('jquery'));
			wp_localize_script(
				'vizapata_sw_integration_metabox',
				'vizapata_sw_integration',
				array(
					'post_id' => $post->ID,
					'security' => wp_create_nonce( 'create-electronic-invoice' ),
					'ajax_url' => admin_url('admin-ajax.php'),
				)
			);
			wp_enqueue_script('vizapata_sw_integration_metabox');
		}
	}
}
