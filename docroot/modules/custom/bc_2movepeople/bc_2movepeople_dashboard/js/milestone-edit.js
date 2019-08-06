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
    //  $('#accordion-milestones-collapse-' + anchor).collapse({'show' : true,
    // 'parent': '#accordion-milestones'});
  }

  // A custom hook for ajax commands changing CSS classes.
  // See:
  // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Ajax%21InvokeCommand.php/class/InvokeCommand/8.2.x
  $.fn.alterClass = function (selector, classToAdd) {
    var $element = $(selector);

    $element
        .removeClass('is-remind-warning')
        .removeClass('is-remind-expired')
        .removeClass('is-completed')
        .addClass(classToAdd);
  };

})(jQuery, Drupal, drupalSettings);
