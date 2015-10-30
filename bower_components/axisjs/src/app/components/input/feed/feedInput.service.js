/**
 * @ngdoc service
 * @name axis.feedInput
 * @description
 * # feedInput
 * Basic feed-based input service to extend.
 * @TODO fill this out and write unit tests.
 * @NB current ignored by istanbul (see below comment)
 */

(function(){
  'use strict';

  angular
    .module('axis')
    .factory('feedInput', feedInput);

  /* istanbul ignore next *//** @ngInject */
  function feedInput($q, $http, $window, $interpolate) {
    /**
     * The feed URL to query.
     * @type {String}
     */
    this.feedUrl = 'https://www.random.org/integers/?num={{ count }}&min={{ min }}&max={{ max }}&col=1&base=10&format=plain&rnd=new';

    /**
     * Creates a bunch of promises to query a RSS feed
     * @param  {object} inputs Object containing symbol, start date and end date.
     * @return {array}        Array of promises
     */
    var getData = function(inputs) {
      var exp = $interpolate(this.feedUrl);
      var symbols = inputs.symbol ? inputs.symbol.split(/[,\s]/) : ['NWS'];
      var dateStart = inputs.dateStart ? inputs.dateStart : $window.moment().subtract(31, 'days').format('YYYY-MM-DD');
      var dateEnd = inputs.dateEnd ? inputs.dateEnd : $window.moment().format('YYYY-MM-DD');
      var endpoint;
      var financialData = [];

      for (var i = 0; i < symbols.length; i++) {
        endpoint = exp({symbol: symbols[i], dateStart: dateStart, dateEnd: dateEnd});
        financialData.push($http.get(endpoint)); // Add promise to array, to resolve in parseData
      }

      return financialData;
    };

    var parseData = function(scope) {
      if (scope.inputs.inputData) {
        var financialData = getData({
          symbol: scope.inputs.inputData.symbol,
          dateStart: typeof scope.inputs.inputData.dateStart === 'string' ? scope.inputs.inputData.dateStart : $window.moment(scope.inputs.inputData.dateStart).format('YYYY-MM-DD'),
          dateEnd: typeof scope.inputs.inputData.dateEnd === 'string' ? scope.inputs.inputData.dateEnd : $window.moment(scope.inputs.inputData.dateEnd).format('YYYY-MM-DD')
        });

        // Resolve all the promises and populate the chart
        $q.all(financialData).then(function(res){
          scope.chartData = []; // Empty, or else new column names will break ng-grid
          scope.columns = []; // Clear existing
          scope.config.data.columns = [];
          var cols = [];
          var colNames = [];

          angular.forEach(res, function(item, index){
            var stockData = item.data.query.results.quote;
            if (index === 0) {
              var dates = stockData.map(function(v){
                return v.Date;
              });
              dates.unshift('Date');
              cols.push(dates);
              colNames.push('Date');
            }

            if (stockData.length > 0) {
              var symbol = stockData[0].Symbol;
              var col = stockData.map(function(v){
                return v.Adj_Close;
              });
              col.unshift(symbol); // Add symbol name as item header
              cols.push(col);
              colNames.push(symbol);
            }
          });

          if (cols.length > 0) {
            scope.chartData = cols;
            scope.columns = colNames;
            scope.config.data.columns = cols;
            scope.config.data.x = 'Date';
            scope.config.financial = scope.chartData; // Needed to repopulate on load; unique to this.
            scope.config.title.source = 'Source: Yahoo';
            scope.config.axis.x.tick = scope.config.axis.x.tick ? scope.config.axis.x.tick : {format: '%Y-%m-%d'};
            scope.config.axis.x.type = 'timeseries';
            // scope.updateData(); // This might be needed somehow.
          }
        });
      }

      return scope;
    };

    var populateInput = function(columns) { // This is untested. @TODO test.
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
      name: 'feedInputService',

      /**
       * Validate spreadsheet input
       * @param  {array} value   The output from HOT.getData()
       * @return {boolean}       True if validates, false if not.
       */
      validate: function(value) {
        return value instanceof Array; // TODO write a real validator.
      },

      /**
       * The default data to populate Axis with.
       * @type {array}
       */
      defaultData: {
        symbol: 'NWS',
        dateStart: $window.moment().subtract(31, 'days').format('YYYY-MM-DD'),
        dateEnd: $window.moment().format('YYYY-MM-DD')
      },

      /**
       * Parses sheet into columns. Called whenever sheet updated.
       * @param  {object} scope The AxisJS scope object.
       * @return {object}       The updated scope object.
       */
      input: function(scope) {
        return parseData(scope);
      },

      /**
       * Convert loaded data to date ranges and symbol
       * @param  {array} data An array of array columns.
       * @return {array}      Array with header column values as first element.
       */
      convert: function(data) {
        return populateInput(data);
      }
    };
  }
})();
