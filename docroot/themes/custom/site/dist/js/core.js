// |--------------------------------------------------------------------------
// | Flexy header
// |--------------------------------------------------------------------------
// |
// | This jQuery script is written by
// |
// | Morten Nissen
// | hjemmesidekongen.dk
// |

var flexy_header = (function ($) {
    'use strict';

    var pub = {},
        $header_static = $('.flexy-header--static'),
        $header_sticky = $('.flexy-header--sticky'),
        options = {
            update_interval: 100,
            tolerance: {
                upward: 20,
                downward: 10
            },
            offset: _get_offset_from_elements_bottom($header_static),
            classes: {
                pinned: "flexy-header--pinned",
                unpinned: "flexy-header--unpinned"
            }
        },
        was_scrolled = false,
        last_distance_from_top = 0;

    /**
     * Instantiate
     */
    pub.init = function (options) {
        registerEventHandlers();
        registerBootEventHandlers();
    };

    /**
     * Register boot event handlers
     */
    function registerBootEventHandlers() {
        $header_sticky.addClass(options.classes.unpinned);

        setInterval(function() {

            if (was_scrolled) {
                document_was_scrolled();

                was_scrolled = false;
            }
        }, options.update_interval);
    }

    /**
     * Register event handlers
     */
    function registerEventHandlers() {
        $(window).scroll(function(event) {
            was_scrolled = true;
        });
    }

    /**
     * Get offset from element bottom
     */
    function _get_offset_from_elements_bottom($element) {
        var element_height = $element.outerHeight(true),
            element_offset = $element.offset().top;

        return (element_height + element_offset);
    }

    /**
     * Document was scrolled
     */
    function document_was_scrolled() {
        var current_distance_from_top = $(window).scrollTop();

        // If past offset
        if (current_distance_from_top >= options.offset) {

            // Downwards scroll
            if (current_distance_from_top > last_distance_from_top) {

                // Obey the downward tolerance
                if (Math.abs(current_distance_from_top - last_distance_from_top) <= options.tolerance.downward) {
                    return;
                }

                $header_sticky.removeClass(options.classes.pinned).addClass(options.classes.unpinned);
            }

            // Upwards scroll
            else {

                // Obey the upward tolerance
                if (Math.abs(current_distance_from_top - last_distance_from_top) <= options.tolerance.upward) {
                    return;
                }

                // We are not scrolled past the document which is possible on the Mac
                if ((current_distance_from_top + $(window).height()) < $(document).height()) {
                    $header_sticky.removeClass(options.classes.unpinned).addClass(options.classes.pinned);
                }
            }
        }

        // Not past offset
        else {
            $header_sticky.removeClass(options.classes.pinned).addClass(options.classes.unpinned);
        }

        last_distance_from_top = current_distance_from_top;
    }

    return pub;
})(jQuery);

// |--------------------------------------------------------------------------
// | Flexy navigation
// |--------------------------------------------------------------------------
// |
// | This jQuery script is written by
// |
// | Morten Nissen
// | hjemmesidekongen.dk
// |

var flexy_navigation = (function ($) {
    'use strict';

    var pub = {},
        layout_classes = {
            'navigation': '.flexy-navigation',
            'obfuscator': '.flexy-navigation__obfuscator',
            'dropdown': '.flexy-navigation__item--dropdown',
            'dropdown_megamenu': '.flexy-navigation__item__dropdown-megamenu',

            'is_upgraded': 'is-upgraded',
            'navigation_has_megamenu': 'has-megamenu',
            'dropdown_has_megamenu': 'flexy-navigation__item--dropdown-with-megamenu',
        };

    /**
     * Instantiate
     */
    pub.init = function (options) {
        registerEventHandlers();
        registerBootEventHandlers();
    };

    /**
     * Register boot event handlers
     */
    function registerBootEventHandlers() {

        // Upgrade
        upgrade();
    }

    /**
     * Register event handlers
     */
    function registerEventHandlers() {}

    /**
     * Upgrade elements.
     * Add classes to elements, based upon attached classes.
     */
    function upgrade() {
        var $navigations = $(layout_classes.navigation);

        // Navigations
        if ($navigations.length > 0) {
            $navigations.each(function(index, element) {
                var $navigation = $(this),
                    $megamenus = $navigation.find(layout_classes.dropdown_megamenu),
                    $dropdown_megamenu = $navigation.find(layout_classes.dropdown_has_megamenu);

                // Has already been upgraded
                if ($navigation.hasClass(layout_classes.is_upgraded)) {
                    return;
                }

                // Has megamenu
                if ($megamenus.length > 0) {
                    $navigation.addClass(layout_classes.navigation_has_megamenu);

                    // Run through all megamenus
                    $megamenus.each(function(index, element) {
                        var $megamenu = $(this),
                            has_obfuscator = $('html').hasClass('has-obfuscator') ? true : false;

                        $megamenu.parents(layout_classes.dropdown)
                            .addClass(layout_classes.dropdown_has_megamenu)
                            .hover(function() {

                                if (has_obfuscator) {
                                    obfuscator.show();
                                }
                            }, function() {
                                
                                if (has_obfuscator) {
                                    obfuscator.hide();
                                }
                            });
                    });
                }

                // Is upgraded
                $navigation.addClass(layout_classes.is_upgraded);
            });
        }
    }

    return pub;
})(jQuery);

/*! sidr - v2.2.1 - 2016-02-17
 * http://www.berriart.com/sidr/
 * Copyright (c) 2013-2016 Alberto Varela; Licensed MIT */

(function () {
  'use strict';

  var babelHelpers = {};

  babelHelpers.classCallCheck = function (instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  };

  babelHelpers.createClass = function () {
    function defineProperties(target, props) {
      for (var i = 0; i < props.length; i++) {
        var descriptor = props[i];
        descriptor.enumerable = descriptor.enumerable || false;
        descriptor.configurable = true;
        if ("value" in descriptor) descriptor.writable = true;
        Object.defineProperty(target, descriptor.key, descriptor);
      }
    }

    return function (Constructor, protoProps, staticProps) {
      if (protoProps) defineProperties(Constructor.prototype, protoProps);
      if (staticProps) defineProperties(Constructor, staticProps);
      return Constructor;
    };
  }();

  babelHelpers;

  var sidrStatus = {
    moving: false,
    opened: false
  };

  var helper = {
    // Check for valids urls
    // From : http://stackoverflow.com/questions/5717093/check-if-a-javascript-string-is-an-url

    isUrl: function isUrl(str) {
      var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
      '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|' + // domain name
      '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
      '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
      '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
      '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator

      if (pattern.test(str)) {
        return true;
      } else {
        return false;
      }
    },


    // Add sidr prefixes
    addPrefixes: function addPrefixes($element) {
      this.addPrefix($element, 'id');
      this.addPrefix($element, 'class');
      $element.removeAttr('style');
    },
    addPrefix: function addPrefix($element, attribute) {
      var toReplace = $element.attr(attribute);

      if (typeof toReplace === 'string' && toReplace !== '' && toReplace !== 'sidr-inner') {
        $element.attr(attribute, toReplace.replace(/([A-Za-z0-9_.\-]+)/g, 'sidr-' + attribute + '-$1'));
      }
    },


    // Check if transitions is supported
    transitions: function () {
      var body = document.body || document.documentElement,
          style = body.style,
          supported = false,
          property = 'transition';

      if (property in style) {
        supported = true;
      } else {
        (function () {
          var prefixes = ['moz', 'webkit', 'o', 'ms'],
              prefix = undefined,
              i = undefined;

          property = property.charAt(0).toUpperCase() + property.substr(1);
          supported = function () {
            for (i = 0; i < prefixes.length; i++) {
              prefix = prefixes[i];
              if (prefix + property in style) {
                return true;
              }
            }

            return false;
          }();
          property = supported ? '-' + prefix.toLowerCase() + '-' + property.toLowerCase() : null;
        })();
      }

      return {
        supported: supported,
        property: property
      };
    }()
  };

  var $$2 = jQuery;

  var bodyAnimationClass = 'sidr-animating';
  var openAction = 'open';
  var closeAction = 'close';
  var transitionEndEvent = 'webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend';
  var Menu = function () {
    function Menu(name) {
      babelHelpers.classCallCheck(this, Menu);

      this.name = name;
      this.item = $$2('#' + name);
      this.openClass = name === 'sidr' ? 'sidr-open' : 'sidr-open ' + name + '-open';
      this.menuWidth = this.item.outerWidth(true);
      this.speed = this.item.data('speed');
      this.side = this.item.data('side');
      this.displace = this.item.data('displace');
      this.timing = this.item.data('timing');
      this.method = this.item.data('method');
      this.onOpenCallback = this.item.data('onOpen');
      this.onCloseCallback = this.item.data('onClose');
      this.onOpenEndCallback = this.item.data('onOpenEnd');
      this.onCloseEndCallback = this.item.data('onCloseEnd');
      this.body = $$2(this.item.data('body'));
    }

    babelHelpers.createClass(Menu, [{
      key: 'getAnimation',
      value: function getAnimation(action, element) {
        var animation = {},
            prop = this.side;

        if (action === 'open' && element === 'body') {
          animation[prop] = this.menuWidth + 'px';
        } else if (action === 'close' && element === 'menu') {
          animation[prop] = '-' + this.menuWidth + 'px';
        } else {
          animation[prop] = 0;
        }

        return animation;
      }
    }, {
      key: 'prepareBody',
      value: function prepareBody(action) {
        var prop = action === 'open' ? 'hidden' : '';

        // Prepare page if container is body
        if (this.body.is('body')) {
          var $html = $$2('html'),
              scrollTop = $html.scrollTop();

          $html.css('overflow-x', prop).scrollTop(scrollTop);
        }
      }
    }, {
      key: 'openBody',
      value: function openBody() {
        if (this.displace) {
          var transitions = helper.transitions,
              $body = this.body;

          if (transitions.supported) {
            $body.css(transitions.property, this.side + ' ' + this.speed / 1000 + 's ' + this.timing).css(this.side, 0).css({
              width: $body.width(),
              position: 'absolute'
            });
            $body.css(this.side, this.menuWidth + 'px');
          } else {
            var bodyAnimation = this.getAnimation(openAction, 'body');

            $body.css({
              width: $body.width(),
              position: 'absolute'
            }).animate(bodyAnimation, {
              queue: false,
              duration: this.speed
            });
          }
        }
      }
    }, {
      key: 'onCloseBody',
      value: function onCloseBody() {
        var transitions = helper.transitions,
            resetStyles = {
          width: '',
          position: '',
          right: '',
          left: ''
        };

        if (transitions.supported) {
          resetStyles[transitions.property] = '';
        }

        this.body.css(resetStyles).unbind(transitionEndEvent);
      }
    }, {
      key: 'closeBody',
      value: function closeBody() {
        var _this = this;

        if (this.displace) {
          if (helper.transitions.supported) {
            this.body.css(this.side, 0).one(transitionEndEvent, function () {
              _this.onCloseBody();
            });
          } else {
            var bodyAnimation = this.getAnimation(closeAction, 'body');

            this.body.animate(bodyAnimation, {
              queue: false,
              duration: this.speed,
              complete: function complete() {
                _this.onCloseBody();
              }
            });
          }
        }
      }
    }, {
      key: 'moveBody',
      value: function moveBody(action) {
        if (action === openAction) {
          this.openBody();
        } else {
          this.closeBody();
        }
      }
    }, {
      key: 'onOpenMenu',
      value: function onOpenMenu(callback) {
        var name = this.name;

        sidrStatus.moving = false;
        sidrStatus.opened = name;

        this.item.unbind(transitionEndEvent);

        this.body.removeClass(bodyAnimationClass).addClass(this.openClass);

        this.onOpenEndCallback();

        if (typeof callback === 'function') {
          callback(name);
        }
      }
    }, {
      key: 'openMenu',
      value: function openMenu(callback) {
        var _this2 = this;

        var $item = this.item;

        if (helper.transitions.supported) {
          $item.css(this.side, 0).one(transitionEndEvent, function () {
            _this2.onOpenMenu(callback);
          });
        } else {
          var menuAnimation = this.getAnimation(openAction, 'menu');

          $item.css('display', 'block').animate(menuAnimation, {
            queue: false,
            duration: this.speed,
            complete: function complete() {
              _this2.onOpenMenu(callback);
            }
          });
        }
      }
    }, {
      key: 'onCloseMenu',
      value: function onCloseMenu(callback) {
        this.item.css({
          left: '',
          right: ''
        }).unbind(transitionEndEvent);
        $$2('html').css('overflow-x', '');

        sidrStatus.moving = false;
        sidrStatus.opened = false;

        this.body.removeClass(bodyAnimationClass).removeClass(this.openClass);

        this.onCloseEndCallback();

        // Callback
        if (typeof callback === 'function') {
          callback(name);
        }
      }
    }, {
      key: 'closeMenu',
      value: function closeMenu(callback) {
        var _this3 = this;

        var item = this.item;

        if (helper.transitions.supported) {
          item.css(this.side, '').one(transitionEndEvent, function () {
            _this3.onCloseMenu(callback);
          });
        } else {
          var menuAnimation = this.getAnimation(closeAction, 'menu');

          item.animate(menuAnimation, {
            queue: false,
            duration: this.speed,
            complete: function complete() {
              _this3.onCloseMenu();
            }
          });
        }
      }
    }, {
      key: 'moveMenu',
      value: function moveMenu(action, callback) {
        this.body.addClass(bodyAnimationClass);

        if (action === openAction) {
          this.openMenu(callback);
        } else {
          this.closeMenu(callback);
        }
      }
    }, {
      key: 'move',
      value: function move(action, callback) {
        // Lock sidr
        sidrStatus.moving = true;

        this.prepareBody(action);
        this.moveBody(action);
        this.moveMenu(action, callback);
      }
    }, {
      key: 'open',
      value: function open(callback) {
        var _this4 = this;

        // Check if is already opened or moving
        if (sidrStatus.opened === this.name || sidrStatus.moving) {
          return;
        }

        // If another menu opened close first
        if (sidrStatus.opened !== false) {
          var alreadyOpenedMenu = new Menu(sidrStatus.opened);

          alreadyOpenedMenu.close(function () {
            _this4.open(callback);
          });

          return;
        }

        this.move('open', callback);

        // onOpen callback
        this.onOpenCallback();
      }
    }, {
      key: 'close',
      value: function close(callback) {
        // Check if is already closed or moving
        if (sidrStatus.opened !== this.name || sidrStatus.moving) {
          return;
        }

        this.move('close', callback);

        // onClose callback
        this.onCloseCallback();
      }
    }, {
      key: 'toggle',
      value: function toggle(callback) {
        if (sidrStatus.opened === this.name) {
          this.close(callback);
        } else {
          this.open(callback);
        }
      }
    }]);
    return Menu;
  }();

  var $$1 = jQuery;

  function execute(action, name, callback) {
    var sidr = new Menu(name);

    switch (action) {
      case 'open':
        sidr.open(callback);
        break;
      case 'close':
        sidr.close(callback);
        break;
      case 'toggle':
        sidr.toggle(callback);
        break;
      default:
        $$1.error('Method ' + action + ' does not exist on jQuery.sidr');
        break;
    }
  }

  var i;
  var $ = jQuery;
  var publicMethods = ['open', 'close', 'toggle'];
  var methodName;
  var methods = {};
  var getMethod = function getMethod(methodName) {
    return function (name, callback) {
      // Check arguments
      if (typeof name === 'function') {
        callback = name;
        name = 'sidr';
      } else if (!name) {
        name = 'sidr';
      }

      execute(methodName, name, callback);
    };
  };
  for (i = 0; i < publicMethods.length; i++) {
    methodName = publicMethods[i];
    methods[methodName] = getMethod(methodName);
  }

  function sidr(method) {
    if (method === 'status') {
      return sidrStatus;
    } else if (methods[method]) {
      return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
    } else if (typeof method === 'function' || typeof method === 'string' || !method) {
      return methods.toggle.apply(this, arguments);
    } else {
      $.error('Method ' + method + ' does not exist on jQuery.sidr');
    }
  }

  var $$3 = jQuery;

  function fillContent($sideMenu, settings) {
    // The menu content
    if (typeof settings.source === 'function') {
      var newContent = settings.source(name);

      $sideMenu.html(newContent);
    } else if (typeof settings.source === 'string' && helper.isUrl(settings.source)) {
      $$3.get(settings.source, function (data) {
        $sideMenu.html(data);
      });
    } else if (typeof settings.source === 'string') {
      var htmlContent = '',
          selectors = settings.source.split(',');

      $$3.each(selectors, function (index, element) {
        htmlContent += '<div class="sidr-inner">' + $$3(element).html() + '</div>';
      });

      // Renaming ids and classes
      if (settings.renaming) {
        var $htmlContent = $$3('<div />').html(htmlContent);

        $htmlContent.find('*').each(function (index, element) {
          var $element = $$3(element);

          helper.addPrefixes($element);
        });
        htmlContent = $htmlContent.html();
      }

      $sideMenu.html(htmlContent);
    } else if (settings.source !== null) {
      $$3.error('Invalid Sidr Source');
    }

    return $sideMenu;
  }

  function fnSidr(options) {
    var transitions = helper.transitions,
        settings = $$3.extend({
      name: 'sidr', // Name for the 'sidr'
      speed: 200, // Accepts standard jQuery effects speeds (i.e. fast, normal or milliseconds)
      side: 'left', // Accepts 'left' or 'right'
      source: null, // Override the source of the content.
      renaming: true, // The ids and classes will be prepended with a prefix when loading existent content
      body: 'body', // Page container selector,
      displace: true, // Displace the body content or not
      timing: 'ease', // Timing function for CSS transitions
      method: 'toggle', // The method to call when element is clicked
      bind: 'touchstart click', // The event(s) to trigger the menu
      onOpen: function onOpen() {},
      // Callback when sidr start opening
      onClose: function onClose() {},
      // Callback when sidr start closing
      onOpenEnd: function onOpenEnd() {},
      // Callback when sidr end opening
      onCloseEnd: function onCloseEnd() {} // Callback when sidr end closing

    }, options),
        name = settings.name,
        $sideMenu = $$3('#' + name);

    // If the side menu do not exist create it
    if ($sideMenu.length === 0) {
      $sideMenu = $$3('<div />').attr('id', name).appendTo($$3('body'));
    }

    // Add transition to menu if are supported
    if (transitions.supported) {
      $sideMenu.css(transitions.property, settings.side + ' ' + settings.speed / 1000 + 's ' + settings.timing);
    }

    // Adding styles and options
    $sideMenu.addClass('sidr').addClass(settings.side).data({
      speed: settings.speed,
      side: settings.side,
      body: settings.body,
      displace: settings.displace,
      timing: settings.timing,
      method: settings.method,
      onOpen: settings.onOpen,
      onClose: settings.onClose,
      onOpenEnd: settings.onOpenEnd,
      onCloseEnd: settings.onCloseEnd
    });

    $sideMenu = fillContent($sideMenu, settings);

    return this.each(function () {
      var $this = $$3(this),
          data = $this.data('sidr'),
          flag = false;

      // If the plugin hasn't been initialized yet
      if (!data) {
        sidrStatus.moving = false;
        sidrStatus.opened = false;

        $this.data('sidr', name);

        $this.bind(settings.bind, function (event) {
          event.preventDefault();

          if (!flag) {
            flag = true;
            sidr(settings.method, name);

            setTimeout(function () {
              flag = false;
            }, 100);
          }
        });
      }
    });
  }

  jQuery.sidr = sidr;
  jQuery.fn.sidr = fnSidr;

}());
!function(e){var t;e.fn.slinky=function(a){var s=e.extend({label:"Back",title:!1,speed:300,resize:!0},a),i=e(this),n=i.children().first();i.addClass("slinky-menu");var r=function(e,t){var a=Math.round(parseInt(n.get(0).style.left))||0;n.css("left",a-100*e+"%"),"function"==typeof t&&setTimeout(t,s.speed)},l=function(e){i.height(e.outerHeight())},d=function(e){i.css("transition-duration",e+"ms"),n.css("transition-duration",e+"ms")};if(d(s.speed),e("a + ul",i).prev().addClass("next"),e("li > ul",i).prepend('<li class="header">'),s.title===!0&&e("li > ul",i).each(function(){var t=e(this).parent().find("a").first().text(),a=e("<h2>").text(t);e("> .header",this).append(a)}),s.title||s.label!==!0){var o=e("<a>").text(s.label).prop("href","#").addClass("back");e(".header",i).append(o)}else e("li > ul",i).each(function(){var t=e(this).parent().find("a").first().text(),a=e("<a>").text(t).prop("href","#").addClass("back");e("> .header",this).append(a)});e("a",i).on("click",function(a){if(!(t+s.speed>Date.now())){t=Date.now();var n=e(this);/#/.test(this.href)&&a.preventDefault(),n.hasClass("next")?(i.find(".active").removeClass("active"),n.next().show().addClass("active"),r(1),s.resize&&l(n.next())):n.hasClass("back")&&(r(-1,function(){i.find(".active").removeClass("active"),n.parent().parent().hide().parentsUntil(i,"ul").first().addClass("active")}),s.resize&&l(n.parent().parent().parentsUntil(i,"ul")))}}),this.jump=function(t,a){t=e(t);var n=i.find(".active");n=n.length>0?n.parentsUntil(i,"ul").length:0,i.find("ul").removeClass("active").hide();var o=t.parentsUntil(i,"ul");o.show(),t.show().addClass("active"),a===!1&&d(0),r(o.length-n),s.resize&&l(t),a===!1&&d(s.speed)},this.home=function(t){t===!1&&d(0);var a=i.find(".active"),n=a.parentsUntil(i,"li").length;n>0&&(r(-n,function(){a.removeClass("active")}),s.resize&&l(e(a.parentsUntil(i,"li").get(n-1)).parent())),t===!1&&d(s.speed)},this.destroy=function(){e(".header",i).remove(),e("a",i).removeClass("next").off("click"),i.removeClass("slinky-menu").css("transition-duration",""),n.css("transition-duration","")};var c=i.find(".active");return c.length>0&&(c.removeClass("active"),this.jump(c,!1)),this}}(jQuery);
!function(t){"function"==typeof define&&define.amd?define(["jquery"],t):"object"==typeof module&&module.exports?module.exports=t(require("jquery")):t(jQuery)}(function(t){var e=function(){function e(){var e=this,n=function(){var n=["br-wrapper"];""!==e.options.theme&&n.push("br-theme-"+e.options.theme),e.$elem.wrap(t("<div />",{"class":n.join(" ")}))},i=function(){e.$elem.unwrap()},a=function(n){return t.isNumeric(n)&&(n=Math.floor(n)),t('option[value="'+n+'"]',e.$elem)},r=function(){var n=e.options.initialRating;return n?a(n):t("option:selected",e.$elem)},o=function(){var n=e.$elem.find('option[value="'+e.options.emptyValue+'"]');return!n.length&&e.options.allowEmpty?(n=t("<option />",{value:e.options.emptyValue}),n.prependTo(e.$elem)):n},l=function(t){var n=e.$elem.data("barrating");return"undefined"!=typeof t?n[t]:n},s=function(t,n){null!==n&&"object"==typeof n?e.$elem.data("barrating",n):e.$elem.data("barrating")[t]=n},u=function(){var t=r(),n=o(),i=t.val(),a=t.data("html")?t.data("html"):t.text(),l=null!==e.options.allowEmpty?e.options.allowEmpty:!!n.length,u=n.length?n.val():null,d=n.length?n.text():null;s(null,{userOptions:e.options,ratingValue:i,ratingText:a,originalRatingValue:i,originalRatingText:a,allowEmpty:l,emptyRatingValue:u,emptyRatingText:d,readOnly:e.options.readonly,ratingMade:!1})},d=function(){e.$elem.removeData("barrating")},c=function(){return l("ratingText")},f=function(){return l("ratingValue")},g=function(){var n=t("<div />",{"class":"br-widget"});return e.$elem.find("option").each(function(){var i,a,r,o;i=t(this).val(),i!==l("emptyRatingValue")&&(a=t(this).text(),r=t(this).data("html"),r&&(a=r),o=t("<a />",{href:"#","data-rating-value":i,"data-rating-text":a,html:e.options.showValues?a:""}),n.append(o))}),e.options.showSelectedRating&&n.append(t("<div />",{text:"","class":"br-current-rating"})),e.options.reverse&&n.addClass("br-reverse"),e.options.readonly&&n.addClass("br-readonly"),n},p=function(){return l("userOptions").reverse?"nextAll":"prevAll"},h=function(t){a(t).prop("selected",!0),e.$elem.change()},m=function(){t("option",e.$elem).prop("selected",function(){return this.defaultSelected}),e.$elem.change()},v=function(t){t=t?t:c(),t==l("emptyRatingText")&&(t=""),e.options.showSelectedRating&&e.$elem.parent().find(".br-current-rating").text(t)},y=function(t){return Math.round(Math.floor(10*t)/10%1*100)},b=function(){e.$widget.find("a").removeClass(function(t,e){return(e.match(/(^|\s)br-\S+/g)||[]).join(" ")})},w=function(){var n,i,a=e.$widget.find('a[data-rating-value="'+f()+'"]'),r=l("userOptions").initialRating,o=t.isNumeric(f())?f():0,s=y(r);if(b(),a.addClass("br-selected br-current")[p()]().addClass("br-selected"),!l("ratingMade")&&t.isNumeric(r)){if(o>=r||!s)return;n=e.$widget.find("a"),i=a.length?a[l("userOptions").reverse?"prev":"next"]():n[l("userOptions").reverse?"last":"first"](),i.addClass("br-fractional"),i.addClass("br-fractional-"+s)}},$=function(t){return l("allowEmpty")&&l("userOptions").deselectable?f()==t.attr("data-rating-value"):!1},x=function(n){n.on("click.barrating",function(n){var i,a,r=t(this),o=l("userOptions");return n.preventDefault(),i=r.attr("data-rating-value"),a=r.attr("data-rating-text"),$(r)&&(i=l("emptyRatingValue"),a=l("emptyRatingText")),s("ratingValue",i),s("ratingText",a),s("ratingMade",!0),h(i),v(a),w(),o.onSelect.call(e,f(),c(),n),!1})},R=function(e){e.on("mouseenter.barrating",function(){var e=t(this);b(),e.addClass("br-active")[p()]().addClass("br-active"),v(e.attr("data-rating-text"))})},V=function(t){e.$widget.on("mouseleave.barrating blur.barrating",function(){v(),w()})},O=function(e){e.on("touchstart.barrating",function(e){e.preventDefault(),e.stopPropagation(),t(this).click()})},C=function(t){t.on("click.barrating",function(t){t.preventDefault()})},S=function(t){x(t),e.options.hoverState&&(R(t),V(t))},T=function(t){t.off(".barrating")},j=function(t){var n=e.$widget.find("a");O&&O(n),t?(T(n),C(n)):S(n)};this.show=function(){l()||(n(),u(),e.$widget=g(),e.$widget.insertAfter(e.$elem),w(),v(),j(e.options.readonly),e.$elem.hide())},this.readonly=function(t){"boolean"==typeof t&&l("readOnly")!=t&&(j(t),s("readOnly",t),e.$widget.toggleClass("br-readonly"))},this.set=function(t){var n=l("userOptions");0!==e.$elem.find('option[value="'+t+'"]').length&&(s("ratingValue",t),s("ratingText",e.$elem.find('option[value="'+t+'"]').text()),s("ratingMade",!0),h(f()),v(c()),w(),n.silent||n.onSelect.call(this,f(),c()))},this.clear=function(){var t=l("userOptions");s("ratingValue",l("originalRatingValue")),s("ratingText",l("originalRatingText")),s("ratingMade",!1),m(),v(c()),w(),t.onClear.call(this,f(),c())},this.destroy=function(){var t=f(),n=c(),a=l("userOptions");T(e.$widget.find("a")),e.$widget.remove(),d(),i(),e.$elem.show(),a.onDestroy.call(this,t,n)}}return e.prototype.init=function(e,n){return this.$elem=t(n),this.options=t.extend({},t.fn.barrating.defaults,e),this.options},e}();t.fn.barrating=function(n,i){return this.each(function(){var a=new e;if(t(this).is("select")||t.error("Sorry, this plugin only works with select fields."),a.hasOwnProperty(n)){if(a.init(i,this),"show"===n)return a.show(i);if(a.$elem.data("barrating"))return a.$widget=t(this).next(".br-widget"),a[n](i)}else{if("object"==typeof n||!n)return i=n,a.init(i,this),a.show();t.error("Method "+n+" does not exist on jQuery.barrating")}})},t.fn.barrating.defaults={theme:"",initialRating:null,allowEmpty:null,emptyValue:"",showValues:!1,showSelectedRating:!0,deselectable:!0,reverse:!1,readonly:!1,fastClicks:!0,hoverState:!0,silent:!1,onSelect:function(t,e,n){},onClear:function(t,e){},onDestroy:function(t,e){}},t.fn.barrating.BarRating=e});
;(function($){
  //pass in just the context as a $(obj) or a settings JS object
  $.fn.autogrow = function(opts) {
    var that = $(this).css({overflow: 'hidden', resize: 'none'}) //prevent scrollies
        , selector = that.selector
        , defaults = {
          context: $(document) //what to wire events to
          , animate: true //if you want the size change to animate
          , speed: 200 //speed of animation
          , fixMinHeight: true //if you don't want the box to shrink below its initial size
          , cloneClass: 'autogrowclone' //helper CSS class for clone if you need to add special rules
          , onInitialize: false //resizes the textareas when the plugin is initialized
        }
    ;
    opts = $.isPlainObject(opts) ? opts : {context: opts ? opts : $(document)};
    opts = $.extend({}, defaults, opts);
    that.each(function(i, elem){
      var min, clone;
      elem = $(elem);
      //if the element is "invisible", we get an incorrect height value
      //to get correct value, clone and append to the body.
      if (elem.is(':visible') || parseInt(elem.css('height'), 10) > 0) {
        min = parseInt(elem.css('height'), 10) || elem.innerHeight();
      } else {
        clone = elem.clone()
            .addClass(opts.cloneClass)
            .val(elem.val())
            .css({
              position: 'absolute'
              , visibility: 'hidden'
              , display: 'block'
            })
        ;
        $('body').append(clone);
        min = clone.innerHeight();
        clone.remove();
      }
      if (opts.fixMinHeight) {
        elem.data('autogrow-start-height', min); //set min height
      }
      elem.css('height', min);

      if (opts.onInitialize && elem.length) {
        resize.call(elem[0]);
      }
    });
    opts.context
        .on('keyup paste', selector, resize)
    ;

    function resize (e){
      var box = $(this)
          , oldHeight = box.innerHeight()
          , newHeight = this.scrollHeight
          , minHeight = box.data('autogrow-start-height') || 0
          , clone
      ;
      if (oldHeight < newHeight) { //user is typing
        this.scrollTop = 0; //try to reduce the top of the content hiding for a second
        if(opts.animate) {
          box.stop().animate({height: newHeight}, {duration: opts.speed, complete: notifyGrown});
        } else {
          box.innerHeight(newHeight);
          notifyGrown();
        }

      } else if (!e || e.which == 8 || e.which == 46 || (e.ctrlKey && e.which == 88)) { //user is deleting, backspacing, or cutting
        if (oldHeight > minHeight) { //shrink!
          //this cloning part is not particularly necessary. however, it helps with animation
          //since the only way to cleanly calculate where to shrink the box to is to incrementally
          //reduce the height of the box until the $.innerHeight() and the scrollHeight differ.
          //doing this on an exact clone to figure out the height first and then applying it to the
          //actual box makes it look cleaner to the user
          clone = box.clone()
          //add clone class for extra css rules
              .addClass(opts.cloneClass)
              //make "invisible", remove height restriction potentially imposed by existing CSS
              .css({position: 'absolute', zIndex:-10, height: ''})
              //populate with content for consistent measuring
              .val(box.val())
          ;
          box.after(clone); //append as close to the box as possible for best CSS matching for clone
          do { //reduce height until they don't match
            newHeight = clone[0].scrollHeight - 1;
            clone.innerHeight(newHeight);
          } while (newHeight === clone[0].scrollHeight);
          newHeight++; //adding one back eliminates a wiggle on deletion
          clone.remove();
          box.focus(); // Fix issue with Chrome losing focus from the textarea.

          //if user selects all and deletes or holds down delete til beginning
          //user could get here and shrink whole box
          newHeight < minHeight && (newHeight = minHeight);
          if(oldHeight > newHeight) {
            if(opts.animate) {
              box.stop().animate({height: newHeight}, {duration: opts.speed, complete: notifyShrunk});
            } else {
              box.innerHeight(newHeight);
              notifyShrunk();
            }
          }

        } else { //just set to the minHeight
          box.innerHeight(minHeight);
        }
      }
    }

    // Trigger event to indicate a textarea has grown.
    function notifyGrown() {
      opts.context.trigger('autogrow:grow');
    }

    // Trigger event to indicate a textarea has shrunk.
    function notifyShrunk() {
      opts.context.trigger('autogrow:shrink');
    }

    return that;
  }
})(jQuery);
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
      $(".datepicker" ).datepicker();
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

    return pub;
})(jQuery);


/* JS for house illustration on services page */

// Top
jQuery(document).ready(function ($) {
    $("#top").mouseenter(function () { // show
        $(".info-modal").show();
    });
});

jQuery(document).ready(function ($) {
    $(".info-modal").mouseleave(function () { // hide  on mouse out
        $(".info-modal").hide();
    });
});
jQuery(document).ready(function ($) {
    $(".middle, .bottom").mouseenter(function () { // show
        $(".info-modal").hide();
    });
});
// Left
jQuery(document).ready(function ($) {
    $("#left").mouseenter(function () { // show
        $(".info-modal-left").show();
    });
});
jQuery(document).ready(function ($) {
    $(".info-modal-left").mouseleave(function () { // hide on mouse out
        $(".info-modal-left").hide();
    });
});
jQuery(document).ready(function ($) {
    $(".right, .bottom, .top").mouseenter(function () { // show
        $(".info-modal-left").hide();
    });
});
// Right
jQuery(document).ready(function ($) {
    $("#right").mouseenter(function () { // show
        $(".info-modal-right").show();
    });
});
jQuery(document).ready(function ($) {
    $(".info-modal-right").mouseleave(function () { // hide on mouse out
        $(".info-modal-right").hide();
    });
});
jQuery(document).ready(function ($) {
    $(".left, .bottom, .top").mouseenter(function () { // show
        $(".info-modal-right").hide();
    });
});
// Bottom
jQuery(document).ready(function ($) {
    $("#bottom").mouseenter(function () { // show
        $(".info-modal-bottom").show();
    });
});
jQuery(document).ready(function ($) {
    $(".info-modal-bottom").mouseleave(function () { // hide on mouse out
        $(".info-modal-bottom").hide();
    });
});
jQuery(document).ready(function ($) {
    $(".right, .left, .top").mouseenter(function () { // show
        $(".info-modal-bottom").hide();
    });
});

// Overall action / Just for UX purpose
jQuery(document).ready(function ($) {
    $("body").click(function () { // show
        $(".info-modal-bottom, .info-modal-left, .info-modal-right, .info-modal").hide();
    });
});

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

//# sourceMappingURL=core.js.map