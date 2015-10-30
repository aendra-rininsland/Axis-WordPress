(function(){
  'use strict';

  describe('Controller: HeadController', function () {

    // load the controller's module
    beforeEach(function(){
      // This test suite doesn't seem to be actually loading test.config.yaml.
      // TODO make this test suite more robust.
      var store = {
        'theme': 'test'
      };

      spyOn(localStorage, 'getItem').and.callFake(function (key) {
        return store[key];
      });
      spyOn(localStorage, 'setItem').and.callFake(function (key, value) {
        store[key] = value + '';
        return store[key];
      });
      spyOn(localStorage, 'clear').and.callFake(function () {
          store = {};
      });

      module('axis');
    });

    var HeadController,
        scope;

    // Initialize the controller and a mock scope
    beforeEach(inject(function ($controller, $rootScope, $httpBackend) {
      scope = $rootScope.$new();
      $httpBackend.expectGET('assets/i18n/en_GB.json');
      $httpBackend.whenGET('assets/i18n/en_GB.json').respond('{}');

      $httpBackend.expectGET('default.config.yaml');
      $httpBackend.whenGET('default.config.yaml').respond('stylesheet: "themes/default.css"');

      $httpBackend.expectGET('config.yaml');
      $httpBackend.whenGET('config.yaml').respond('stylesheet: "themes/test.css"\nfonts:\n    - "http://www.thetimes.co.uk/fonts/Solido-Bold.css"\n    - "http://www.thetimes.co.uk/fonts/Solido-ExtraBold.css"\n    - "http://www.thetimes.co.uk/fonts/Solido-Book-Italic.css"');

      HeadController = $controller('HeadController as head', {
        $scope: scope
      });
      scope.$digest();
      $httpBackend.flush();
    }));

    it('should attach a stylesheet from config', function () {
      expect(scope.head.stylesheet).toBe('themes/test.css');
    });

    it('should load an array of font URLs from config', function () {
      expect(scope.head.fonts.length).toBe(3);
    });
  });
})();
