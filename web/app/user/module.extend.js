// extend the framework's module
define( [ cenozoApp.module( 'user' ).getFileUrl( 'module.js' ], function() {
  'use strict';

  var module = cenozoApp.module( 'user' );

  // extend the view factory
  cenozo.providers.decorator( 'CnUserViewFactory', [
    '$delegate', 'CnHttpFactory',
    function( $delegate, CnHttpFactory ) {
      var instance = $delegate.instance;
      $delegate.instance = function( parentModel, root ) {
        var object = instance( parentModel, root );
        object.afterView( function() {
          CnHttpFactory.instance( {
            path: 'user/' + object.record.id + '/assignment',
            data: {
              modifier: { where: { column: 'assignment.end_datetime', operator: '=', value: null } },
              select: { column: [ 'id' ] }
            }
          } ).get().then( function( response ) {
            if( 0 < response.data.length ) {
              // add the view assignment button
              module.addExtraOperation( 'view', {
                title: 'View Active Assignment',
                operation: function( $state, model ) {
                  $state.go( 'assignment.view', { identifier: response.data[0].id } );
                }
              } );
            } else {
              // remove the view assignment button, if found
              module.removeExtraOperation( 'view', 'View Active Assignment' );
            }
          } );
        } );
        return object;
      };
      return $delegate;
    }
  ] );

} );
