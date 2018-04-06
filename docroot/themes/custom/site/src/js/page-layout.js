// |--------------------------------------------------------------------------
// | Page layout
// |--------------------------------------------------------------------------
// |
// | This jQuery script is written by
// | Morten Nissen
// |

var pageLayout = (function ($) {
    'use strict';

    var pub = {};

    /**
     * Instantiate
     */
    pub.init = function () {
        registerBootEventHandlers();
        registerEventHandlers();
    };

    /**
     * Register boot event handlers
     */
    function registerBootEventHandlers() {
      $(window).load(function() {
        $("#page-wrapper").removeClass("preload");
      });

      $(".datepicker" ).datepicker();

      $(window).scroll(function() {
        $('#sidebar-wrapper').scrollTop($(this).scrollTop());
      });
    }

    /**
     * Register event handlers
     */
    function registerEventHandlers() {
        /**
         * Bar rating.
         */
        Drupal.behaviors.barRating = {
            attach: function (context) {
                $('.form-select.rating', context).once('barrating').barrating('show', {
                    theme: 'bars-square',
                    showValues: true,
                    showSelectedRating: false
                });
            }
        }
    }

    /**
     * Footer attached
     */
    function footerAttached() {
        if ($('body').hasClass('footer-attached')) {
            var $footer = $('.footer');
            var footerHeight = $footer.outerHeight(true);

            $('.inner-wrapper').css('padding-bottom', footerHeight);
        }
    }

    return pub;
})(jQuery);


/* JS for house illustration on services page */

// Top
jQuery(document).ready(function($){
$("#top").mouseenter(function () { // show  
    $(".info-modal").show();
});
});

jQuery(document).ready(function($){
$(".info-modal").mouseleave(function () { // hide  on mouse out
    $(".info-modal").hide();
});
    });
jQuery(document).ready(function($){    
$(".middle, .bottom").mouseenter(function () { // show  
    $(".info-modal").hide();
});
    });
// Left
jQuery(document).ready(function($){    
$("#left").mouseenter(function () { // show  
    $(".info-modal-left").show();
});
    });
jQuery(document).ready(function($){    
$(".info-modal-left").mouseleave(function () { // hide on mouse out
    $(".info-modal-left").hide();
});
    });
jQuery(document).ready(function($){    
$(".right, .bottom, .top").mouseenter(function () { // show  
    $(".info-modal-left").hide();
});
    });
// Right
jQuery(document).ready(function($){   
$("#right").mouseenter(function () { // show  
    $(".info-modal-right").show();
});
    });
jQuery(document).ready(function($){   
$(".info-modal-right").mouseleave(function () { // hide on mouse out 
    $(".info-modal-right").hide();
});
    });
jQuery(document).ready(function($){   
$(".left, .bottom, .top").mouseenter(function () { // show  
    $(".info-modal-right").hide();
});
    });
// Bottom
jQuery(document).ready(function($){   
$("#bottom").mouseenter(function () { // show  
    $(".info-modal-bottom").show();
});
    });
jQuery(document).ready(function($){ 
$(".info-modal-bottom").mouseleave(function () { // hide on mouse out
    $(".info-modal-bottom").hide();
});
    });
jQuery(document).ready(function($){ 
$(".right, .left, .top").mouseenter(function () { // show  
    $(".info-modal-bottom").hide();
});
    });

// Overall action / Just for UX purpose
jQuery(document).ready(function($){ 
$("body").click(function () { // show  
    $(".info-modal-bottom, .info-modal-left, .info-modal-right, .info-modal").hide();
});
    });
