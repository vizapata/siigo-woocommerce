(function ($) {
  'use strict';

  const metabox_id = '#vizapata_sw_integration_invoice_metabox'
  const request = {
    action: 'vizapata_sw_integration_generate_invoice',
    post_id: vizapata_sw_integration.post_id,
    security: vizapata_sw_integration.security
  }
  const vz_sw_generate_invoice = () => {
    $(`${metabox_id} button`).addClass('disabled').prop('disabled', true);
    $(`${metabox_id} .messages`).empty()
    $.post(vizapata_sw_integration.ajax_url, request, function (response) {
      $(`${metabox_id} button`).removeClass('disabled').prop('disabled', false);
      if (!response.error) {
        $(metabox_id).replaceWith(response.content)
      } else {
        $(`${metabox_id} .messages`).empty().append(`<div class="alert alert-error">${response.message}</div>`)
      }
    });

    return false;
  }

  $(() => $(`${metabox_id} button`).click(vz_sw_generate_invoice))

})(jQuery);