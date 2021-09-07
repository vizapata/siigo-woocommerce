<?php

class Vizapata_sw_integration_Activator
{

	public static function activate()
	{
		if (!get_option('vizapata_sw_integration_siigo_api_url', false)) {
			add_option('vizapata_sw_integration_siigo_api_url', 'https://api.siigo.com/', false);
		}
	}
}
