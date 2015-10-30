/**
 * @ngdoc service
 * @name axis.spreadsheetInput
 * @description
 * # spreadsheetInput
 * Enables a fancy user-friendly spreadsheet input.
 */

(function(){
  'use strict';

  angular
    .module('axis')
    .factory('spreadsheetInput', spreadsheetInput);

  /** @ngInject */
  function spreadsheetInput() {
    var defaultSheet = [
      ['data1', 'data2'],
      [30, 50],
      [200, 20],
      [100, 10],
      [400, 40],
      [150, 15],
      [250, 25]
    ];

    var parseSheet = function(scope) {
      if (scope.inputs.inputData) {
        scope.chartData = []; // Empty, or else new column names will break ng-grid
        scope.columns = []; // Clear existing
        scope.config.data.columns = [];
        scope.chartData = angular.copy(scope.inputs.inputData);
        var cols = [];

        // Convert objects into arrays. Might be better long-term to use C3's JSON input.
        // Lots of this stuff is C3-specific. TODO move to c3Service.
        if (scope.chartData.length > 0) {
          scope.columns = scope.chartData[0].filter(function(v) {
            return v != undefined && v !== ''; /* jshint ignore:line */
          });

          scope.chartData.shift();
          angular.forEach(scope.columns, function(colName, index) {
            var column = [];
            column.push(colName);
            angular.forEach(scope.chartData, function(datum) {
              if (datum[index]) {
                column.push(datum[index]);
              }
            });

            cols.push(column);
          });

          scope.config.data.columns = cols;
        }
      }

      return scope;
    };

    var convertColsToRows = function(columns) {
      var data = [];
      var headers = [];
      for (var i = 0; i < columns.length; i++) {
        headers.push(columns[i].shift());
        for (var j = 0; j < columns[i].length; j++) {
          if (!data[j]) {
            data[j] = [];
          }

          data[j][i] = columns[i][j];
        }
      }

      return [headers].concat(data);
    };

    // Public API here
    return {
      /**
       * Service name
       * @type {String}
       */
      name: 'spreadsheetInputService',

      /**
       * Validate spreadsheet input
       * @param  {array} value   The output from HOT.getData()
       * @return {boolean}       True if validates, false if not.
       */
      validate: function(value) {
        return value instanceof Array; // TODO write a real validator.
      },

      /**
       * The default sheet to populate AxisJS with.
       * @type {array}
       */
      defaultData: defaultSheet,

      /**
       * Parses sheet into columns. Called whenever sheet updated.
       * @param  {object} scope The AxisJS scope object.
       * @return {object}       The updated scope object.
       */
      input: function(scope) {
        return parseSheet(scope);
      },

      /**
       * Convert array columns to Handsontable-compatible array.
       * @param  {array} data An array of array columns.
       * @return {array}      Array with header column values as first element.
       */
      convert: function(data) {
        return convertColsToRows(data);
      }
    };
  }
})();
