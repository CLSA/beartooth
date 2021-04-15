define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'interviewing_instance', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {}, // standard
    name: {
      singular: 'interviewing instance',
      plural: 'interviewing instances',
      possessive: 'interviewing instance\'s',
      friendlyColumn: 'username'
    },
    columnList: {
      type: {
        column: 'interviewing_instance.type',
        title: 'Type'
      },
      name: {
        column: 'user.name',
        title: 'Name'
      },
      interviewer: {
        column: 'interviewer.name',
        title: 'Interviewer',
        help: 'Blank for site-based interviewing instances.'
      },
      active: {
        column: 'user.active',
        title: 'Active',
        type: 'boolean'
      },
      last_access_datetime: {
        title: 'Last Activity',
        type: 'datetime'
      }
    },
    defaultOrder: {
      column: 'name',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    active: {
      title: 'Active',
      type: 'boolean'
    },
    type: {
      title: 'Type',
      type: 'enum'
    },
    username: {
      title: 'Username',
      type: 'string'
    },
    password: {
      title: 'Password',
      type: 'string',
      regex: '^((?!(password)).){8,}$', // length >= 8 and can't have "password"
      isExcluded: 'view',
      help: 'Passwords must be at least 8 characters long and cannot contain the word "password"'
    },
    interviewer_user_id: {
      title: 'Interviewer',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
        where: [ 'user.first_name', 'user.last_name', 'user.name' ]
      },
      help: 'Determines which interviewer this instance belongs to, or blank if this is a site instance.'
    }
  } );

  if( angular.isDefined( module.actions.edit ) ) {
    module.addExtraOperation( 'view', {
      title: 'Set Password',
      operation: async function( $state, model ) { await model.viewModel.setPassword(); }
    } );
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewingInstanceAdd', [
    'CnInterviewingInstanceModelFactory',
    function( CnInterviewingInstanceModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewingInstanceModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewingInstanceList', [
    'CnInterviewingInstanceModelFactory',
    function( CnInterviewingInstanceModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewingInstanceModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewingInstanceView', [
    'CnInterviewingInstanceModelFactory',
    function( CnInterviewingInstanceModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewingInstanceModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewingInstanceAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewingInstanceListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewingInstanceViewFactory', [
    'CnBaseViewFactory', 'CnModalPasswordFactory', 'CnModalMessageFactory', 'CnHttpFactory',
    function( CnBaseViewFactory, CnModalPasswordFactory, CnModalMessageFactory, CnHttpFactory ) {
      var object = function( parentModel, root ) {
        CnBaseViewFactory.construct( this, parentModel, root, 'activity' );

        // custom operation
        this.setPassword = async function() {
          var response = await CnModalPasswordFactory.instance( {
            confirm: false,
            showCancel: true
          } ).show();

          if( angular.isObject( response ) ) {
            await CnHttpFactory.instance( {
              path: 'interviewing_instance/' + this.record.getIdentifier(),
              data: { password: response.requestedPass },
              onError: function( error ) {
                if( 403 == error.status ) {
                  CnModalMessageFactory.instance( {
                    title: 'Unable To Change Password',
                    message: 'Sorry, you do not have access to resetting the password for this interviewing instance.',
                    error: true
                  } ).show();
                } else { CnModalMessageFactory.httpError( error ); }
              }
            } ).patch();

            await CnModalMessageFactory.instance( {
              title: 'Password Reset',
              message: 'The password has been successfully changed.'
            } ).show();
          }
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewingInstanceModelFactory', [
    'CnBaseModelFactory',
    'CnInterviewingInstanceAddFactory', 'CnInterviewingInstanceListFactory', 'CnInterviewingInstanceViewFactory',
    function( CnBaseModelFactory,
              CnInterviewingInstanceAddFactory, CnInterviewingInstanceListFactory, CnInterviewingInstanceViewFactory ) {
      var object = function( root ) {
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnInterviewingInstanceAddFactory.instance( this );
        this.listModel = CnInterviewingInstanceListFactory.instance( this );
        this.viewModel = CnInterviewingInstanceViewFactory.instance( this, root );
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
