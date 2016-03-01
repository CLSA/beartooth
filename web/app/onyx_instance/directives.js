define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOnyxInstanceAdd', function () {
    return {
      templateUrl: 'app/onyx_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOnyxInstanceView', function () {
    return {
      templateUrl: 'app/onyx_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
