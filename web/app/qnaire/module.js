define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'qnaire', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: { column: 'name' },
    name: {
      singular: 'questionnaire',
      plural: 'questionnaires',
      possessive: 'questionnaire\'s',
      pluralPossessive: 'questionnaires\''
    },
    columnList: {
      name: {
        title: 'Name'
      },
      rank: {
        title: 'Rank',
        type: 'rank'
      },
      delay: {
        title: 'Delay',
        type: 'number'
      }
    },
    defaultOrder: {
      column: 'rank',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    rank: {
      column: 'qnaire.rank',
      title: 'Rank',
      type: 'rank'
    },
    name: {
      title: 'Name',
      type: 'string'
    },
    delay: {
      title: 'Delay (weeks)',
      type: 'string',
      format: 'integer',
      minValue: 0
    },
    completed_event_type_id: {
      title: 'Completed Event Type',
      type: 'enum',
      constant: true,
      help: 'The event type which is added to a participant\'s event list when this questionnaire is completed'
    },
    prev_event_type_id: {
      title: 'Previous Event Type',
      type: 'enum',
      help: 'The event type which was added when the coinciding questionnaire of the same type (home or site) ' +
            'during the last phase was completed.'  
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQnaireAdd', [
    'CnQnaireModelFactory',
    function( CnQnaireModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnQnaireModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQnaireList', [
    'CnQnaireModelFactory',
    function( CnQnaireModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnQnaireModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQnaireView', [
    'CnQnaireModelFactory',
    function( CnQnaireModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnQnaireModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        this.deferred.promise.then( function() {
          if( angular.isDefined( self.scriptModel ) )
            self.scriptModel.listModel.heading = 'Mandatory Script List';
          if( angular.isDefined( self.eventTypeModel ) )
            self.eventTypeModel.listModel.heading = 'Required To Begin Event List';
          if( angular.isDefined( self.queueStateModel ) )
            self.queueStateModel.listModel.heading = 'Disabled Queue List';
          if( angular.isDefined( self.quotaModel ) )
            self.quotaModel.listModel.heading = 'Disabled Quota List';
        } );
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireModelFactory', [
    'CnBaseModelFactory', 'CnQnaireAddFactory', 'CnQnaireListFactory', 'CnQnaireViewFactory',
    'CnSession', 'CnHttpFactory',
    function( CnBaseModelFactory, CnQnaireAddFactory, CnQnaireListFactory, CnQnaireViewFactory,
              CnSession, CnHttpFactory ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnQnaireAddFactory.instance( this );
        this.listModel = CnQnaireListFactory.instance( this );
        this.viewModel = CnQnaireViewFactory.instance( this, root );

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            return CnHttpFactory.instance( {
              path: 'event_type',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: { name: false } }
              }
            } ).query().then( function success( response ) {
              self.metadata.columnList.completed_event_type_id.enumList = [];
              self.metadata.columnList.prev_event_type_id.enumList = [];
              response.data.forEach( function( item ) {
                self.metadata.columnList.completed_event_type_id.enumList.push( {
                  value: item.id,
                  name: item.name
                } );
                self.metadata.columnList.prev_event_type_id.enumList.push( {
                  value: item.id,
                  name: item.name
                } );
              } );
            } );
          } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
