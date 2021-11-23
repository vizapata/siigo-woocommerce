<form action='options.php' method='post'>
  <?php
  settings_fields('vizapata_sw_integration_options');
  do_settings_sections('vizapata_sw_integration_options');
  submit_button();
  ?>
</form>