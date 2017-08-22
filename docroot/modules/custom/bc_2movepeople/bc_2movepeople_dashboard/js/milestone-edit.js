(function ($, Drupal) {
    'use strict';
    
    var url = document.location.toString();
    var anchor = '';
    if (url.indexOf("#") > 0) {
      anchor = url.substring(url.indexOf("#")+1);
    }
    
    $(window).load(function() {
      if (anchor) {
        var elem = $("h3[data-progression-id="+anchor+"]");
        var index = $("h3.ui-accordion-header").index(elem);
        $( "#accordion" ).accordion( "option", "active", index );
      }
    });

})(jQuery, Drupal);