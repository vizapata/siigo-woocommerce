(function ($) {
  'use strict';

  const metabox_id = '#vizapata_sw_integration_invoice_metabox'
  const build_request = () => ({
    action: 'vizapata_sw_integration_generate_invoice',
    payment_id: $(`${metabox_id} select`).val(),
    post_id: vizapata_sw_integration.post_id,
    security: vizapata_sw_integration.security
  })
  const vz_sw_generate_invoice = () => {
    $(`${metabox_id} button`).addClass('disabled').prop('disabled', true);
    $(`${metabox_id} .messages`).empty()
    $.post(vizapata_sw_integration.ajax_url, build_request(), function (response) {
      if (!response.error) {
        $(metabox_id).replaceWith(response.content)
      } else {
        $(`${metabox_id} .messages`).empty().append(`<div class="alert alert-error">${response.message}</div>`)
      }
      enable_button();
    });
    return false;
  }
  const enable_button = () => $(`${metabox_id} button`).removeClass('disabled').prop('disabled', false).off('click', vz_sw_generate_invoice).on('click', vz_sw_generate_invoice)

  $(enable_button)
})(jQuery);