define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'appointment_type', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'qnaire',
        column: 'qnaire.name'
      }
    },
    name: {
      singular: 'appointment type',
      plural: 'appointment types',
      possessive: 'appointment type\'s',
      pluralPossessive: 'appointment types\''
    },
    columnList: {
      name: {
        title: 'Name'
      },
      color: {
        title: 'Colour'
      },
      qnaire: {
        column: 'qnaire.name',
        title: 'Questionnaire'
      }
    },
    defaultOrder: {
      column: 'name',
      reverse: false
    }
  } );

  module.addInputGroup( null, {
    name: {
      title: 'Name',
      type: 'string'
    },
    color: {
      title: 'Colour',
      type: 'color'
    },
    qnaire_id: {
      title: 'Questionnaire',
      type: 'enum',
      constant: 'view'
    },
    description: {
      title: 'Description',
      type: 'text'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentTypeAdd', [
    'CnAppointmentTypeModelFactory',
    function( CnAppointmentTypeModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentTypeModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentTypeList', [
    'CnAppointmentTypeModelFactory',
    function( CnAppointmentTypeModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentTypeModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentTypeView', [
    'CnAppointmentTypeModelFactory',
    function( CnAppointmentTypeModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentTypeModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentTypeAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentTypeListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentTypeViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) { CnBaseViewFactory.construct( this, parentModel, root ); }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentTypeModelFactory', [
    'CnBaseModelFactory',
    'CnAppointmentTypeListFactory', 'CnAppointmentTypeAddFactory', 'CnAppointmentTypeViewFactory',
    'CnSession', 'CnHttpFactory', '$q',
    function( CnBaseModelFactory,
              CnAppointmentTypeListFactory, CnAppointmentTypeAddFactory, CnAppointmentTypeViewFactory,
              CnSession, CnHttpFactory, $q ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnAppointmentTypeAddFactory.instance( this );
        this.listModel = CnAppointmentTypeListFactory.instance( this );
        this.viewModel = CnAppointmentTypeViewFactory.instance( this );

        // extend getMetadata
        this.getMetadata = function() {

          var promiseList = [

            this.$$getMetadata(),

            CnHttpFactory.instance( {
              path: 'qnaire',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: 'rank' }
              }
            } ).query().then( function success( response ) {
              self.metadata.columnList.qnaire_id.enumList = [];
              response.data.forEach( function( item ) {
                self.metadata.columnList.qnaire_id.enumList.push( { value: item.id, name: item.name } );
              } );
            } )

          ];

          return $q.all( promiseList );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
