'use strict';

var path = require('path');
var conf = require('./gulp/conf');

var _ = require('lodash');
var wiredep = require('wiredep');

function listFiles() {
  var wiredepOptions = _.extend({}, conf.wiredep, {
    dependencies: true,
    devDependencies: true
  });

  return wiredep(wiredepOptions).js
    .concat([
      path.join(conf.paths.src, '/app/**/*.module.js'),
      path.join(conf.paths.src, '/app/**/*.js'),
      path.join(conf.paths.src, '/**/*.spec.js'),
      path.join(conf.paths.src, '/**/*.mock.js'),
      path.join(conf.paths.src, '/**/*.html'),
      path.join('bower_components/bootstrap-sass-official/assets/javascripts/bootstrap/dropdown.js'),
      path.join('bower_components/bootstrap-colorselector/lib/bootstrap-colorselector-0.2.0/js/bootstrap-colorselector.js')
    ]);
}

module.exports = function(config) {

  var configuration = {
    files: listFiles(),

    client: {
      captureConsole: true
    },

    singleRun: true,

    autoWatch: false,

    frameworks: ['jasmine', 'angular-filesort'],

    angularFilesort: {
      whitelist: [path.join(conf.paths.src, '/**/!(*.html|*.spec|*.mock).js')]
    },

    ngHtml2JsPreprocessor: {
      stripPrefix: 'src/',
      moduleName: 'axis'
    },

    browsers : ['PhantomJS'],

    plugins : [
      'karma-phantomjs-launcher',
      'karma-angular-filesort',
      'karma-jasmine',
      'karma-ng-html2js-preprocessor',
      'karma-nyan-reporter',
      'karma-coverage'
    ],

    preprocessors: {
      'src/**/*.html': ['ng-html2js'],
      'src/**/!(*.spec).js': ['coverage']
    },

    reporters: ['nyan', 'coverage'],

    coverageReporter: {
      reporters: [
        {type: 'lcov'},
        {type: 'text-summary'}
      ],
      instrumenterOptions: {
        istanbul: { noCompact: true }
      }
    }
  };

  // This block is needed to execute Chrome on Travis
  // If you ever plan to use Chrome and Travis, you can keep it
  // If not, you can safely remove it
  // https://github.com/karma-runner/karma/issues/1144#issuecomment-53633076
  if(configuration.browsers[0] === 'Chrome' && process.env.TRAVIS) {
    configuration.customLaunchers = {
      'chrome-travis-ci': {
        base: 'Chrome',
        flags: ['--no-sandbox']
      }
    };
    configuration.browsers = ['chrome-travis-ci'];
  }

  config.set(configuration);
};
