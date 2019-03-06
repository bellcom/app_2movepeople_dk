// Document ready
(function ($) {
  'use strict';

  // Enable page layout
  pageLayout.init();

  // Sidr
  $('.slinky-menu')
    .find('ul, li, a')
    .removeClass();

  $('.sidr-toggle--right').sidr({
    name: 'sidr-main',
    side: 'right',
    renaming: false,
    body: '.layout__wrapper',
    source: '.sidr-source-provider'
  });

  // Slinky
  $('.sidr .slinky-menu').slinky({
    title: true,
    label: ''
  });

  // Enable / disable Bootstrap tooltips, based upon touch events
  if (Modernizr.touchevents) {
    $('[data-toggle="tooltip"]').tooltip('hide');
  }
  else {
    $('[data-toggle="tooltip"]').tooltip();
  }

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
