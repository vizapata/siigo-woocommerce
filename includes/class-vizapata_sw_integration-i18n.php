<?php

class Vizapata_sw_integration_i18n
{
	public function load_plugin_textdomain()
	{
		load_plugin_textdomain(
			'vizapata_sw_integration',
			false,
			dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
		);
	}
}
