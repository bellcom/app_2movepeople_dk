(function ($, Drupal) {
    'use strict';

    var url = document.location.toString();
    var anchor = '';
    if (url.indexOf("#") > 0) {
        anchor = url.substring(url.indexOf("#") + 1);
    }

    if (anchor) {
        $('.collapse').collapse('hide');
        $('#accordion-milestones-collapse-' + anchor).collapse('show');
    } else {
        $('.collapse').first().collapse('show');
    }
})(jQuery, Drupal);