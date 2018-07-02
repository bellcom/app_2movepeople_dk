(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.menuToggle = {
    attach: function (context) {
      $("#menu-toggle").once('menuToggle').click(function(e) {
        e.preventDefault();
        $("#page-wrapper").toggleClass("active")
          .one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend',
          function(e) {
            $(window).resize();
          });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
