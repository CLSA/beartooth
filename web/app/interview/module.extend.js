// extend the framework's module
console.log( cenozoApp.module( 'interview' ).getFileUrl( 'module.js' ) );
define( [ cenozoApp.module( 'interview' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'interview' );

  cenozo.insertPropertyAfter( module.columnList, 'uid', 'qnaire', {
    column: 'qnaire.name',
    title: 'Questionnaire'
  } );

  // add future_appointment as a hidden input (to be used below)
  module.addInput( '', 'future_appointment', { type: 'hidden' } );
  module.addInputAfter( '', 'participant', 'qnaire_id', {
    title: 'Questionnaire',
    type: 'enum',
    constant: true
  } );

  /* ######################################################################################################## */
  cenozo.providers.decorator( 'cnInterviewViewDirective', [
    '$delegate', 'CnInterviewModelFactory',
    function( $delegate, CnInterviewModelFactory ) {
      var directive = $delegate[0];

      // hack to make sure the address and user columns don't show in the appointment list for site interviews
      angular.extend( directive, {
        templateUrl: cenozoApp.getFileUrl( 'interview', 'view.tpl.html' ),
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewModelFactory.root;
          $scope.model.viewModel.afterView( function() {
            if( 'site' == $scope.model.viewModel.record.type ) {
              var appointmentListScope = cenozo.findChildDirectiveScope(
                cenozo.getScopeByQuerySelector( '[name="appointmentList"]').$$childHead,
                'cnRecordList'
              );

              if( null != appointmentListScope ) {
                var dataArray = appointmentListScope.dataArray;
                var addressIndex = dataArray.findIndexByProperty( 'key', 'address_summary' );
                if( null != addressIndex ) dataArray.splice( addressIndex, 1 );
                var userIndex = dataArray.findIndexByProperty( 'key', 'formatted_user_id' );
                if( null != userIndex ) dataArray.splice( userIndex, 1 );
              }
            }
          } );
        }
      } );

      return $delegate;
    }
  ] );

  // extend the list factory
  cenozo.providers.decorator( 'CnInterviewListFactory', [
    '$delegate', 'CnHttpFactory',
    function( $delegate, CnHttpFactory ) {
      var instance = $delegate.instance;
      $delegate.instance = function( parentModel ) {
        var object = instance( parentModel );

        // enable the add button if:
        //   1) the interview list's parent is a participant model
        //   2) all interviews are complete for this participant
        //   3) another qnaire is available for this participant
        object.afterList( function() {
          object.parentModel.getAddEnabled = function() { return false; };
          if( 'participant' == object.parentModel.getSubjectFromState() ) {
            var queueRank = null;
            var qnaireRank = null;
            var lastInterview = null;
            // get the participant's last interview
            CnHttpFactory.instance( {
              path: object.parentModel.getServiceCollectionPath(),
              data: {
                modifier: { order: { 'qnaire.rank': true }, limit: 1 },
                select: { column: [ { table: 'qnaire', column: 'rank' }, 'end_datetime' ] }
              },
              onError: function( response ) {} // ignore errors
            } ).query().then( function( response ) {
              if( 0 < response.data.length ) lastInterview = response.data[0];

              // get the participant's current queue rank
              return CnHttpFactory.instance( {
                path: object.parentModel.getServiceCollectionPath().replace( '/interview', '' ),
                data: {
                  select: { column: [
                    { table: 'queue', column: 'rank', alias: 'queueRank' },
                    { table: 'qnaire', column: 'rank', alias: 'qnaireRank' }
                  ] }
                },
                onError: function( response ) {} // ignore errors
              } ).query().then( function( response ) {
                queueRank = response.data.queueRank;
                qnaireRank = response.data.qnaireRank;
              } );
            } ).then( function( response ) {
              object.parentModel.getAddEnabled = function() {
                return object.parentModel.$$getAddEnabled() &&
                       null != queueRank &&
                       null != qnaireRank && (
                         null == lastInterview || (
                           null != lastInterview.end_datetime &&
                           lastInterview.rank != qnaireRank
                         )
                       );
              };
            } );
          }
        } );

        return object;
      };
      return $delegate;
    }
  ] );

  // extend the view factory
  cenozo.providers.decorator( 'CnInterviewViewFactory', [
    '$delegate',
    function( $delegate ) {
      var instance = $delegate.instance;
      $delegate.instance = function( parentModel, root ) {
        var object = instance( parentModel, root );

        function getAppointmentEnabled( type ) {
          var completed = null !== object.record.end_datetime;
          var future = object.record.future_appointment;
          return 'add' == type ? ( !completed && !future ) : future;
        }

        function updateEnableFunctions() {
          object.appointmentModel.getAddEnabled = function() {
            return angular.isDefined( object.appointmentModel.module.actions.add ) &&
                   getAppointmentEnabled( 'add' );
          };
          object.appointmentModel.getDeleteEnabled = function() {
            return angular.isDefined( object.appointmentModel.module.actions.delete ) &&
                   getAppointmentEnabled( 'delete' );
          };
        }

        // override onView
        object.onView = function() {
          return object.$$onView().then( function() {
            // set the correct type and refresh the list
            if( angular.isDefined( self.appointmentModel ) ) {
              if( self.appointmentModel.type != self.record.type ) {
                self.appointmentModel.type = self.record.type;
                self.appointmentModel.listModel.onList( true );
              }
              updateEnableFunctions();
            }
          } );
        };

        // override appointment list's onDelete
        object.deferred.promise.then( function() {
          if( angular.isDefined( object.appointmentModel ) ) {
            object.appointmentModel.listModel.onDelete = function( record ) {
              return object.appointmentModel.listModel.$$onDelete( record ).then( function() { object.onView(); } );
            };
          }
        } );

        return object;
      };
      return $delegate;
    }
  ] );

  // extend the model factory
  cenozo.providers.decorator( 'CnInterviewModelFactory', [
    '$delegate', 'CnHttpFactory',
    function( $delegate, CnHttpFactory ) {
      var instance = $delegate.instance;
      // extend getBreadcrumbTitle
      // (metadata's promise will have already returned so we don't have to wait for it)
      function extendObject( object ) {
        angular.extend( object, {
          getBreadcrumbTitle: function() {
            var qnaire = object.metadata.columnList.qnaire_id.enumList.findByProperty(
              'value', object.viewModel.record.qnaire_id );
            return qnaire ? qnaire.name : 'unknown';
          },

          // extend getMetadata
          getMetadata: function() {
            return object.$$getMetadata().then( function() {
              return CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: 'rank' }
                }
              } ).query().then( function success( response ) {
                object.metadata.columnList.qnaire_id.enumList = [];
                response.data.forEach( function( item ) {
                  object.metadata.columnList.qnaire_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } );
            } );
          }
        } );
      }

      extendObject( $delegate.root );

      $delegate.instance = function() {
        var object = instance();
        extendObject( object );
        return object;
      };

      return $delegate;
    }
  ] );

} );