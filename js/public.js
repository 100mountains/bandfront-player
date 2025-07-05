var $jscomp = $jscomp || {};
$jscomp.scope = {};

$jscomp.findInternal = function(a, e, c) {
  a instanceof String && (a = String(a));
  var h = a.length;
  for (var k = 0; k < h; k++) {
    var m = a[k];
    if (e.call(c, m, k, a)) {
      return {
        i: k,
        v: m
      };
    }
  }
  return {
    i: -1,
    v: void 0
  };
};
$jscomp.ASSUME_ES5 = !1;
$jscomp.ASSUME_NO_NATIVE_MAP = !1;
$jscomp.ASSUME_NO_NATIVE_SET = !1;
$jscomp.SIMPLE_FROUND_POLYFILL = !1;

$jscomp.defineProperty = $jscomp.ASSUME_ES5 ||
  "function" == typeof Object.defineProperties ?
  Object.defineProperty :
  function(a, e, c) {
    a != Array.prototype && a != Object.prototype && (a[e] = c.value);
  };

$jscomp.getGlobal = function(a) {
  return "undefined" != typeof window && window === a ?
    a :
    "undefined" != typeof global && null != global ?
      global :
      a;
};

$jscomp.global = $jscomp.getGlobal(this);

$jscomp.polyfill = function(a, e, c, h) {
  if (e) {
    c = $jscomp.global;
    a = a.split(".");
    for (h = 0; h < a.length - 1; h++) {
      var k = a[h];
      k in c || (c[k] = {});
      c = c[k];
    }
    a = a[a.length - 1];
    h = c[a];
    e = e(h);
    e != h && null != e && $jscomp.defineProperty(c, a, {
      configurable: !0,
      writable: !0,
      value: e
    });
  }
};

$jscomp.polyfill(
  "Array.prototype.find",
  function(a) {
    return a
      ? a
      : function(a, c) {
          return $jscomp.findInternal(this, a, c).v;
        };
  },
  "es6",
  "es3"
);

(function() {
  var a = [],
    e = 0;

  window.generate_the_bfp = function(c) {
    function h(b) {
      var fContainer = b.closest(".bfp-single-player"),
        fPlayer = fContainer.find(".bfp-first-player");
      d("audio").each(function() {
        this.pause();
        this.currentTime = 0;
      });
      fContainer
        .find(".bfp-player-container:not(.bfp-first-player)")
        .hide();
      if (!b.hasClass(".bfp-first-player")) {
        b
          .show()
          .offset(fPlayer.offset())
          .outerWidth(fPlayer.outerWidth());
        b.find("audio")[0].play();
      }
    }

    function k(b, g) {
      if (b + 1 < e || g) {
        var f = b + 1;
        if (
          g &&
          (f == e ||
            0 == d('[playernumber="' + f + '"]')
              .closest("[data-loop]")
              .length ||
            d('[playernumber="' + f + '"]')
              .closest("[data-loop]")[0] !=
              d('[playernumber="' + b + '"]')
                .closest("[data-loop]")[0])
        ) {
          f = d('[playernumber="' + b + '"]')
            .closest("[data-loop]")
            .find("[playernumber]:first")
            .attr("playernumber");
        }
        if (a[f] instanceof d && a[f].is("a")) {
          if (a[f].closest(".bfp-single-player").length) {
            h(a[f].closest(".bfp-player-container"));
          } else if (a[f].is(":visible")) {
            a[f].trigger("click");
          } else {
            k(b + 1, g);
          }
        } else {
          if (
            d(a[f].domNode)
              .closest(".bfp-single-player")
              .length
          ) {
            h(
              d(a[f].domNode).closest(".bfp-player-container")
            );
          } else if (
            d(a[f].domNode)
              .closest(".bfp-player-container")
              .is(":visible")
          ) {
            a[f].domNode.play();
          } else {
            k(b + 1, g);
          }
        }
      }
    }

    function m(b) {
      var prodData = b.data("product");
      d('[data-product="' + prodData + '"]').each(function() {
        var prodEl = d(this).closest(".product"),
          imgEl = prodEl.find("img.product-" + prodData);
        if (
          imgEl.length &&
          0 == prodEl.closest(".bfp-player-list").length &&
          0 == prodEl.find(".bfp-player-list").length
        ) {
          var imgOffset = imgEl.offset(),
            bfpEl = prodEl.find("div.bfp-player");
          if (bfpEl.length) {
            bfpEl.css({
              position: "absolute",
              "z-index": 999999
            }).offset({
              left:
                imgOffset.left + (imgEl.width() - bfpEl.width()) / 2,
              top:
                imgOffset.top + (imgEl.height() - bfpEl.height()) / 2
            });
          }
        }
      });
    }

    if (
      !(
        "boolean" !== typeof c &&
        "undefined" != typeof bfp_global_settings &&
        1 * bfp_global_settings.onload
      ) &&
      "undefined" === typeof generated_the_bfp
    ) {
      generated_the_bfp = !0;
      var d = jQuery;
      d(".bfp-player-container")
        .on("click", "*", function(b) {
          b.preventDefault();
          b.stopPropagation();
          return !1;
        })
        .parent()
        .removeAttr("title");
      d.expr.pseudos.regex = function(b, a, f) {
        a = f[3].split(",");
        var c = /^(data|css):/,
          propTest = a[0].match(c) ? a[0].split(":")[0] : "attr";
        c = a.shift().replace(c, "");
        return new RegExp(
          a.join("").replace(/^\s+|\s+$/g, ""),
          "ig"
        ).test(d(b)[propTest](c));
      };
      var r =
          "undefined" != typeof bfp_global_settings
            ? bfp_global_settings.play_all
            : !0,
        l =
          "undefined" != typeof bfp_global_settings
            ? !(1 * bfp_global_settings.play_simultaneously)
            : !0,
        p =
          "undefined" != typeof bfp_global_settings
            ? 1 * bfp_global_settings.fade_out
            : !0,
        q =
          "undefined" != typeof bfp_global_settings &&
          "ios_controls" in bfp_global_settings &&
          1 * bfp_global_settings.ios_controls
            ? !0
            : !1;
      c = d("audio.bfp-player:not(.track):not([playernumber])");
      var t = d("audio.bfp-player.track:not([playernumber])"),
        n = {
          pauseOtherPlayers: l,
          iPadUseNativeControls: q,
          iPhoneUseNativeControls: q,
          success: function(b, c) {
            var f = d(c).data("duration"),
              eDur = d(c).data("estimated_duration"),
              gAttr = d(c).attr("playernumber");
            if ("undefined" != typeof eDur) {
              b.getDuration = function() {
                return eDur;
              };
            }
            if ("undefined" != typeof f) {
              setTimeout(
                (function(b, c) {
                  return function() {
                    a[b].updateDuration = function() {
                      d(this.media)
                        .closest(".bfp-player")
                        .find(".mejs-duration")
                        .html(c);
                    };
                    a[b].updateDuration();
                  };
                })(gAttr, f),
                50
              );
            }
            if (d(c).attr("volume")) {
              b.setVolume(parseFloat(d(c).attr("volume")));
              0 == b.volume && b.setMuted(!0);
            }
            b.addEventListener("playing", function(aEvt) {
              var cPlayer = d(b),
                sp = cPlayer.closest(".bfp-single-player");
              try {
                var eProd = d(aEvt.detail.target).attr("data-product");
                if ("undefined" != typeof eProd) {
                  var trackUrl =
                    window.location.protocol +
                    "//" +
                    window.location.host +
                    "/" +
                    window.location.pathname
                      .replace(/^\//g, "")
                      .replace(/\/$/g, "") +
                    "?bfp-action=play&bfp-product=" +
                    eProd;
                  d.get(trackUrl);
                }
              } catch (v) {}
              if (sp.length) {
                var aPId = cPlayer
                  .closest(".bfp-player-container")
                  .attr("data-player-id");
                sp
                  .find('.bfp-player-title[data-player-id="' + aPId + '"]')
                  .addClass("bfp-playing");
              }
            });
            b.addEventListener("timeupdate", function() {
              var currDuration = b.getDuration();
              if (!isNaN(b.currentTime) && !isNaN(currDuration)) {
                if (p && 4 > currDuration - b.currentTime) {
                  b.setVolume(b.volume - b.volume / 3);
                } else {
                  if (b.currentTime) {
                    if ("undefined" == typeof b.bkVolume) {
                      b.bkVolume = parseFloat(
                        d(b)
                          .find("audio,video")
                          .attr("volume") || b.volume
                      );
                    }
                    b.setVolume(b.bkVolume);
                    0 == b.bkVolume && b.setMuted(!0);
                  }
                }
              }
            });
            b.addEventListener("volumechange", function() {
              var currDuration = b.getDuration();
              if (
                !isNaN(b.currentTime) &&
                !isNaN(currDuration) &&
                (4 < currDuration - b.currentTime || !p) &&
                b.currentTime
              ) {
                b.bkVolume = b.volume;
              }
            });
            b.addEventListener("ended", function() {
              var cEnd = d(b),
                loopElm = cEnd.closest('[data-loop="1"]');
              cEnd[0].currentTime = 0;
              if (cEnd.closest(".bfp-single-player").length) {
                cEnd
                  .closest(".bfp-single-player")
                  .find(".bfp-playing")
                  .removeClass("bfp-playing");
              }
              if (1 * r || loopElm.length) {
                var fAttr = 1 * cEnd.attr("playernumber");
                isNaN(fAttr) &&
                  (fAttr = 1 * cEnd.find("[playernumber]").attr("playernumber"));
                k(fAttr, loopElm.length);
              }
            });
          }
        };
      l = '.product-type-grouped :regex(name,quantity\\[\\d+\\])';
      c.each(function() {
        var b = d(this);
        b.find("source").attr("src");
        b.attr("playernumber", e);
        n.audioVolume = "vertical";
        try {
          a[e] = new MediaElementPlayer(b[0], n);
        } catch (g) {
          "console" in window && console.log(g);
        }
        e++;
      });
      t.each(function() {
        var b = d(this);
        b.find("source").attr("src");
        b.attr("playernumber", e);
        n.features = ["playpause"];
        try {
          a[e] = new MediaElementPlayer(b[0], n);
        } catch (g) {
          "console" in window && console.log(g);
        }
        e++;
        m(b);
        d(window).on("resize", function() {
          m(b);
        });
      });
      d(l).length || (l = ".product-type-grouped [data-product_id]");
      d(l).length || (l = ".woocommerce-grouped-product-list [data-product_id]");
      d(l).length || (l = '.woocommerce-grouped-product-list [id*="product-"]');
      d(l).each(function() {
        try {
          var b =
            d(this), a = (b.data("product_id") || b.attr("name") || b.attr("id")).replace(/[^\d]/g, ""), c = d(".bfp-player-list.merge_in_grouped_products .product-" + a + ":first .bfp-player-title"), e = d("<table></table>"); c.length && !c.closest(".bfp-first-in-product").length && (c.closest("tr").addClass("bfp-first-in-product"), 0 == c.closest("form").length && c.closest(".bfp-player-list").prependTo(b.closest("form")), e.append(b.closest("tr").prepend("<td>" + c.html() + "</td>")), c.html("").append(e))
        } catch (u) { }
      });
      d(document).on("click",
        "[data-player-id]", function () { var b = d(this), a = b.closest(".bfp-single-player"); if (a.length) { d(".bfp-player-title").removeClass("bfp-playing"); var c = b.attr("data-player-id"); b.addClass("bfp-playing"); h(a.find('.bfp-player-container[data-player-id="' + c + '"]')) } })
        }
    };
    window.bfp_force_init = function () { delete window.generated_the_bfp; generate_the_bfp(!0) }; jQuery(generate_the_bfp); jQuery(window).on("load", function () {
        generate_the_bfp(!0); var a = jQuery, e = window.navigator.userAgent; a("[data-lazyloading]").each(function () {
            var c =
                a(this); c.attr("preload", c.data("lazyloading"))
        }); if (e.match(/iPad/i) || e.match(/iPhone/i)) if ("undefined" != typeof bfp_global_settings ? bfp_global_settings.play_all : 1) a(".bfp-player .mejs-play button").one("click", function () { if ("undefined" == typeof bfp_preprocessed_players) { bfp_preprocessed_players = !0; var c = a(this); a(".bfp-player audio").each(function () { this.play(); this.pause() }); setTimeout(function () { c.trigger("click") }, 500) } })
    }).on("popstate", function () {
        jQuery("audio[data-product]:not([playernumber])").length &&
        bfp_force_init()
    }); jQuery(document).on("scroll wpfAjaxSuccess woof_ajax_done yith-wcan-ajax-filtered wpf_ajax_success berocket_ajax_products_loaded berocket_ajax_products_infinite_loaded lazyload.wcpt", bfp_force_init)
})();
