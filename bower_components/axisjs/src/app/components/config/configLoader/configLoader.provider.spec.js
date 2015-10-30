describe('Service: configLoader', function () {
  'use strict';

  // load the service's module
  beforeEach(module('axis'));

  // instantiate service
  var configProvider, config;
  beforeEach(inject(function (_configProvider_) {
    configProvider = _configProvider_;
    config = undefined;
  }));

  it('should load the default config', inject(function ($httpBackend) {
    $httpBackend.expectGET('assets/i18n/en_GB.json');
    $httpBackend.whenGET('assets/i18n/en_GB.json').respond('{}');
    $httpBackend.whenGET('default.config.yaml').respond('colors:\n  - label: "neutral 1"\n    value: "#78B8DF"\n  - label: "neutral 2"\n    value: "#AFCBCE"');
    $httpBackend.whenGET('config.yaml').respond('colors:\n  - label: "neutral 1"\n    value: "#78B8DF"\n  - label: "neutral 2"\n    value: "#AFCBCE"');
    $httpBackend.expectGET('default.config.yaml');
    $httpBackend.expectGET('config.yaml');

    configProvider.then(function(data){ // TODO figure out why this is needed.
      config = data;
    });

    $httpBackend.flush();

    expect(config.colors.length).toBe(2);
  }));

  // TODO make this spec work.
  xit('should return an empty object if request 404s', inject(function ($httpBackend) {
    $httpBackend.expectGET('assets/i18n/en_GB.json');
    $httpBackend.whenGET('assets/i18n/en_GB.json').respond('{}');
    $httpBackend.expectGET('default.config.yaml');
    $httpBackend.whenGET('default.config.yaml').respond(404, '{}');
    $httpBackend.expectGET('config.yaml');
    $httpBackend.whenGET('config.yaml').respond(404, '{}');

    configProvider.then(function(data){ // TODO figure out why this is needed.
      dump(data);
      config = data;
    });

    $httpBackend.flush();

    expect(config).toBe({});
  }));

  // TODO decide what to do when promise is rejected.
  xit('should reject the promise on any other HTTP error code', inject(function ($httpBackend) {
    $httpBackend.expectGET('assets/i18n/en_GB.json');
    $httpBackend.whenGET('assets/i18n/en_GB.json').respond('{}');
    $httpBackend.expectGET('default.config.yaml');
    $httpBackend.whenGET('default.config.yaml').respond(500, '');
    $httpBackend.expectGET('config.yaml');
    $httpBackend.whenGET('config.yaml').respond(500, '');

    configProvider.then(function(data){ // TODO figure out why this is needed.
      config = data;
    });

    $httpBackend.flush();
  }));
});
