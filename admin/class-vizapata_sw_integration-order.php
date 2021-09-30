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
}
