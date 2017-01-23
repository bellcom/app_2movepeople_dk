/**
 * @file
 * Contains the definition of the behaviour jsTestGreenWeight.
 */

(function ($, Drupal, drupalSettings) {
  "use strict";
  /**
   * Attaches the JS test behavior to weight div.
   */
  Drupal.behaviors.jsTestGreenWeight = {
    attach: function (context, settings) {
      var weight = drupalSettings.bc_2movepeople_dashboard.js_weights.green;
      var newDiv = $('<div></div>').css('color', 'green').html('I have a weight of ' + weight);
      $('#js-weights').append(newDiv);
    }
  };
})(jQuery, Drupal, drupalSettings);
