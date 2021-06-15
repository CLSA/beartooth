'use strict';

var cenozo = angular.module( 'cenozo' );

cenozo.controller( 'HeaderCtrl', [
  '$scope', '$state', 'CnBaseHeader', 'CnSession', 'CnHttpFactory', 'CnModalMessageFactory',
  async function( $scope, $state, CnBaseHeader, CnSession, CnHttpFactory, CnModalMessageFactory ) {
    // copy all properties from the base header
    await CnBaseHeader.construct( $scope );

    // add custom operations here by adding a new property to $scope.operationList

    await CnSession.promise;

    CnSession.alertHeader = null == CnSession.user.assignment
                          ? undefined
                          : 'You are currently in a ' + CnSession.user.assignment.type + ' assignment';
    CnSession.onAlertHeader = async function() {
      // we need to re-update the session data to make sure that assignment type is up to date
      await CnSession.updateData();
      await CnSession.promise;

      var controlName = CnSession.user.assignment.type + '_control';
      if( angular.isDefined( cenozoApp.module( 'assignment' ).actions[controlName] ) ) {
        await $state.go( 'assignment.' + controlName );
      } else {
        await CnModalMessageFactory.instance( {
          title: 'Switch Roles For ' + CnSession.user.assignment.type.ucWords() + ' Assignment',
          message:
            'You cannot access your assignment under your current site and role. ' +
            'The site and role selection dialog will now be opened, please use it to switch to the site and ' +
            'role under which you started the assignment.\n\n' +
            'Once you have switched you will be able to access your assignment.',
          error: true
        } ).show();

        await CnSession.showSiteRoleModal();
      }
    };

    // don't allow users to log out if they have an active assignment
    var logoutOperation = $scope.operationList.findByProperty( 'title', 'Logout' );
    var baseExecuteFn = logoutOperation.execute;
    logoutOperation.execute = async function() {
      // private function to redirect the user to the assignment control
      async function showAssignmentExists( assignmentType ) {
        var controlName = assignmentType + '_control';
        var hasAccess = angular.isDefined( cenozoApp.module( 'assignment' ).actions[controlName] );

        await CnModalMessageFactory.instance( {
          title: 'Active ' + ( assignmentType ? assignmentType.ucWords()+' ' : '' ) + 'Assignment Detected',
          message: 'You cannot log out while in an open assignment!\n\n' + ( hasAccess
            ? 'In order to log out you will need to close your open assignment. ' +
              'You will now be redirected to your assignment.'
            : 'In order to log out you will need to close your open assignment, however, you cannot access ' +
              'your assignment from your current site and role. ' +
              'The site and role selection dialog will now be opened, please use it to switch to the site and ' +
              'role under which you started the assignment.\n\n' +
              'Once you have switched you will be able to access your assignment.'
          ),
          error: true
        } ).show();

        // check if the role has access to the assignment module
        if( hasAccess ) await $state.go( 'assignment.' + controlName );
        else await CnSession.showSiteRoleModal();
      }

      var response = await CnHttpFactory.instance( {
        path: 'assignment/0',
        data: { select: { column: [ { table: 'qnaire', column: 'type' } ] } },
        onError: async function( error ) {
          if( 307 == error.status ) {
            // 307 means the user has no active assignment
            await baseExecuteFn();
          } else if( 403 == error.status ) {
            // 403 means there is an assignment, but under a different site
            await showAssignmentExists();
          } else { CnModalMessageFactory.httpError( error ); }
        }
      } ).get();

      // active assignment detected
      await showAssignmentExists( response.data.type );
    };
  }
] );
