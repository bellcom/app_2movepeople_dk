(function ($) {
  'use strict';

  // Enable page layout.
  pageLayout.init();

  // Sidr.
  $('.sidr-toggle--right').sidr({
    name: 'sidr-main',
    side: 'right',
    renaming: false,
    body: '.layout__wrapper',
    source: '.sidr-source-provider'
  });

  // Enable autogrow on milestone textarea.
  $('.path-dashboard .purpose textarea').autogrow();

  // Update milestone form on the fly.
  $('.dashboard-overview .form-control').on('change', function (event) {
    var $element = $(this);
    var $parent = $element.parents('.panel');
    var $submit_button = $parent.find('.js-form-submit.glyphicon-refresh');

    $submit_button.click();
  });

})(jQuery);
