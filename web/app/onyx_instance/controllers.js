define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OnyxInstanceAddCtrl', [
    '$scope', 'CnOnyxInstanceSingleton',
    function( $scope, CnOnyxInstanceSingleton ) {
      $scope.cnAdd = CnOnyxInstanceSingleton.cnAdd;
      $scope.cnList = CnOnyxInstanceSingleton.cnList;
      CnOnyxInstanceSingleton.promise.then( function() {
        $scope.record = $scope.cnAdd.createRecord();
      } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OnyxInstanceListCtrl', [
    '$scope', 'CnOnyxInstanceSingleton',
    function( $scope, CnOnyxInstanceSingleton ) {
      $scope.cnList = CnOnyxInstanceSingleton.cnList;
      $scope.cnList.load().catch( function exception() { cnFatalError(); } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OnyxInstanceViewCtrl', [
    '$stateParams', '$scope', 'CnOnyxInstanceSingleton',
    function( $stateParams, $scope, CnOnyxInstanceSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnOnyxInstanceSingleton );
      $scope.cnView.load( $stateParams.id ).catch( function exception() { cnFatalError(); } );
      $scope.patch = cnPatch( $scope );
    }
  ] );

} );
