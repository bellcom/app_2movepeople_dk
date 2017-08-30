(function ($, Drupal, drupalSettings) {
    'use strict';

    /*Menu-toggle*/
    $("#menu-toggle").click(function(e) {
      e.preventDefault();
      $("#page-wrapper").toggleClass("active");
    });

})(jQuery, Drupal, drupalSettings);