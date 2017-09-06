(function ($, Drupal, drupalSettings) {
    'use strict';

    var url = document.location.toString();
    var anchor = '';
    if (url.indexOf("#") > 0) {
        anchor = url.substring(url.indexOf("#") + 1);
    }

    if (anchor) {
      $('.collapse.in').collapse('hide');
      $('#accordion-milestones-collapse-' + anchor).collapse("show");
    //  $('#accordion-milestones-collapse-' + anchor).collapse({'show' : true, 'parent': '#accordion-milestones'});
    }

})(jQuery, Drupal, drupalSettings);