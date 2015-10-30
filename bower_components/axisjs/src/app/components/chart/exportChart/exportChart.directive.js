/**
 * @ngdoc directive
 * @name axis.directive:exportChart
 * @description
 * Inlines styles and renders to canvas.
 * Also does some PNG and SVG stuff. It's not very well-written...
 *
 * Most of this is shamelessly stolen from Quartz's ChartBuilder.
 * @TODO Refactor the hell out of this.
 */
/*jshint -W083 */

(function(){
  'use strict';

  angular
    .module('axis')
    .directive('exportChart', exportChart);

  /** @ngInject */
  function exportChart (outputService) {
    return {
      restrict: 'A',
      link: function postLink(scope, element, attrs) {
        var main = scope.main; // This is the entire main controller scope. @TODO isolate.

        element.on('click', function(){
          createChartImages(main.config.chartWidth);
          if (attrs.exportChart !== 'save') {
            outputService(main, attrs.exportChart);
          }
        });

        var styles;

        var createChartImages = function(width) {
          // Remove all defs, which botch PNG output
          angular.element('defs').remove();

          // Copy CSS styles to Canvas
          inlineAllStyles();

          // Create PNG image
          var canvas = angular.element('#canvas').empty()[0];

          if (!width) {
            // Zoom! Enhance!
            angular.element('#chart > svg').attr('transform', 'scale(2)');
            canvas.width = angular.element('#chart > svg').width() * 2;
            canvas.height = angular.element('#chart > svg').height() *2;
          } else {
            var scaleFactor = (width / angular.element('#chart').width()) * 2;
            angular.element('#chart > svg').attr('transform', 'scale(' + scaleFactor + ')');
            canvas.width = angular.element('#chart > svg').width() * scaleFactor;
            canvas.height = angular.element('#chart > svg').height() * scaleFactor;
          }


          var canvasContext = canvas.getContext('2d');
          var svg = document.getElementsByTagName('svg')[0];
          var serializer = new XMLSerializer();
          svg = serializer.serializeToString(svg);

          canvasContext.drawSvg(svg,0,0);
          var filename = [];
          for (var i=0; i < main.columns.length; i++) {
            filename.push(main.columns[i]);
          }

          if(main.chartTitle) {
            filename.unshift(main.chartTitle);
          }

          filename = filename.join('-').replace(/[^\w\d]+/gi, '-');

          angular.element('.savePNG').attr('href', canvas.toDataURL('png'))
            .attr('download', function(){ return filename + '_axisJS.png';
            });

          var svgContent = createSVGContent(angular.element('#chart > svg')[0]);

          $('.saveSVG').attr('href','data:text/svg,'+ svgContent.source[0])
            .attr('download', function(){ return filename + '_axisJS.svg';});
        };

        // This needs to be more abstracted. Currently it's built to handle C3's quirks.

        /* Take styles from CSS and put as inline SVG attributes so that Canvg
           can properly parse them. */
        var inlineAllStyles = function() {
          var chartStyle = {},
              selector;

          // Get rules from c3.css
          for (var i = 0; i <= document.styleSheets.length - 1; i++) {
            if (document.styleSheets[i].href && document.styleSheets[i].href.indexOf('c3.css') !== -1) {
              if (document.styleSheets[i].rules !== undefined) {
                chartStyle = angular.extend(chartStyle, document.styleSheets[i].rules);
              } else {
                chartStyle = angular.extend(chartStyle, document.styleSheets[i].cssRules);
              }
            }
          }

          if (chartStyle !== null && chartStyle !== undefined) {
            // SVG doesn't use CSS visibility and opacity is an attribute, not a style property. Change hidden stuff to "display: none"
            var changeToDisplay = function(){
              if (angular.element(this).css('visibility') === 'hidden' || angular.element(this).css('opacity') === '0') {
                angular.element(this).css('display', 'none');
              }
            };

            // Inline apply all the CSS rules as inline
            for (i = 0; i < Object.keys(chartStyle).length; i++) {
              if (chartStyle[i].type === 1) {
                selector = chartStyle[i].selectorText;
                styles = makeStyleObject(chartStyle[i]);
                angular.element('svg *').each(changeToDisplay);
                angular.element(selector).not('.c3-chart path').not('.c3-legend-item-tile').css(styles);
              }

              /* C3 puts line colour as a style attribute, which gets overridden
                 by the global ".c3 path, .c3 line" in c3.css. The .not() above
                 prevents that, but now we need to set fill to "none" to prevent
                 weird beziers.

                 Which screws with pie charts and whatnot, ergo the is() callback.
              */
              angular.element('.c3-chart path')
                .filter(function(){
                  return angular.element(this).css('fill') === 'none';
                })
                .attr('fill', 'none');

              angular.element('.c3-chart path')
                .filter(function(){
                  return angular.element(this).css('fill') !== 'none';
                })
                .attr('fill', function(){
                  return angular.element(this).css('fill');
                });
            }
          }
        };

        // Create an object containing all the CSS styles.
        // TODO move into inlineAllStyles
        var makeStyleObject = function (rule) {
          var styleDec = rule.style;
          var output = {};
          var s;

          for (s = 0; s < styleDec.length; s++) {
            output[styleDec[s]] = styleDec[styleDec[s]];
          }
          return output;
        };

        // Create a SVG.
        var createSVGContent = function(svg) {
          /*
            Copyright (c) 2013 The New York Times

            Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
            The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

            SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
          */

          //via https://github.com/NYTimes/svg-crowbar

          var prefix = {
            xmlns: 'http://www.w3.org/2000/xmlns/',
            xlink: 'http://www.w3.org/1999/xlink',
            svg: 'http://www.w3.org/2000/svg'
          };

          var doctype = '<?xml version="1.0" standalone="no"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">';


          svg.setAttribute('version', '1.1');

          // Disabled defs because it was screwing up SVG output
          //var defsEl = document.createElement("defs");
          //svg.insertBefore(defsEl, svg.firstChild); //TODO   .insert("defs", ":first-child")

          var styleEl = document.createElement('style');
          //defsEl.appendChild(styleEl);
          styleEl.setAttribute('type', 'text/css');


          // removing attributes so they aren't doubled up
          svg.removeAttribute('xmlns');
          svg.removeAttribute('xlink');

          // These are needed for the svg
          if (!svg.hasAttributeNS(prefix.xmlns, 'xmlns')) {
            svg.setAttributeNS(prefix.xmlns, 'xmlns', prefix.svg);
          }

          if (!svg.hasAttributeNS(prefix.xmlns, 'xmlns:xlink')) {
            svg.setAttributeNS(prefix.xmlns, 'xmlns:xlink', prefix.xlink);
          }

          var source = (new XMLSerializer()).serializeToString(svg).replace('</style>', '<![CDATA[' + styles + ']]></style>');

          // Quick 'n' shitty hacks to remove stuff that prevents AI from opening SVG
          source = source.replace(/\sfont-.*?: .*?;/gi, '');
          source = source.replace(/\sclip-.*?="url\(http:\/\/.*?\)"/gi, '');
          source = source.replace(/\stransform="scale\(2\)"/gi, '');
          // not needed but good so it validates
          source = source.replace(/<defs xmlns="http:\/\/www.w3.org\/1999\/xhtml">/gi, '<defs>');

          return {svg: svg, source: [doctype + source]};
        };
      }
    };
  }
})();
