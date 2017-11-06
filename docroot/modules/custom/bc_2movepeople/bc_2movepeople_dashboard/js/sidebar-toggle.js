(function ($, Drupal, drupalSettings) {
    'use strict';

    /*Menu-toggle*/
    $("#menu-toggle").click(function(e) {
      e.preventDefault();
      $("#page-wrapper").toggleClass("active")
        .one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend',
        function(e) {
          $(window).resize();
        });
    });

})(jQuery, Drupal, drupalSettings);
