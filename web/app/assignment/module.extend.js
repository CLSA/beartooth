// we need the participant module for the special CnAssignmentControlFactory
define( [ 'participant' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [ cenozoApp.module( 'assignment' ).getFileUrl( 'module.js' ) ] ), function() {
  'use strict';

  var module = cenozoApp.module( 'assignment' );

  module.identifier.parent.friendly = 'qnaire';

  module.addInputAfter( '', 'participant', 'qnaire', {
    column: 'qnaire.name',
    title: 'Questionnaire',
    type: 'string',
    constant: true
  } );
  module.addInputAfter( '', 'site', 'queue', {
    column: 'queue.title',
    title: 'Queue',
    type: 'string',
    constant: true
  } );

  // used when transitioning to the parent interview state
  module.addInput( '', 'type', {
    column: 'qnaire.type',
    type: 'hidden'
  } );

  // Both home and site control directives are identical, so this function will build the directive object
  // for the directive definitions below
  // Note: the two are defined as distinct directives to make sure that state params aren't confused
  function getAssignmentControlDirective( CnAssignmentControlFactory, CnSession, $window ) {
    return {
      templateUrl: cenozoApp.getFileUrl( 'assignment', 'control.tpl.html' ),
      restrict: 'E',
      controller: function( $scope ) {
        $scope.model = CnAssignmentControlFactory.instance();
        $scope.model.onLoad( false ); // breadcrumbs are handled by the service
      },
      link: function( scope ) {
        // update the script list whenever we regain focus since there may have been script activity
        var focusFn = function() { if( null != scope.model.assignment ) scope.model.loadScriptList(); };
        var win = angular.element( $window ).on( 'focus', focusFn );
        scope.$on( '$destroy', function() { win.off( 'focus', focusFn ); } );

        // close the session's script window whenever this page is unloaded (refreshed or closed)
        $window.onunload = function() { CnSession.closeScript(); };
      }
    };
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAssignmentHomeControl', [
    'CnAssignmentControlFactory', 'CnSession', '$window',
    function( CnAssignmentControlFactory, CnSession, $window ) {
      return getAssignmentControlDirective( CnAssignmentControlFactory, CnSession, $window );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAssignmentSiteControl', [
    'CnAssignmentControlFactory', 'CnSession', '$window',
    function( CnAssignmentControlFactory, CnSession, $window ) {
      return getAssignmentControlDirective( CnAssignmentControlFactory, CnSession, $window );
    }
  ] );

  // extend the model factory
  cenozo.providers.decorator( 'CnAssignmentModelFactory', [
    '$delegate', '$state', 'CnHttpFactory',
    function( $delegate, $state, CnHttpFactory ) {
      var instance = $delegate.instance;
      // extend getBreadcrumbTitle
      // (metadata's promise will have already returned so we don't have to wait for it)
      function extendObject( object ) {
        angular.extend( object, {
          // pass type when transitioning to view state
          transitionToParentViewState: function( subject, identifier ) {
            return $state.go(
              subject + '.view',
              { type: object.viewModel.record.type, identifier: identifier }
            );
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

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentControlFactory', [
    '$q', '$state', '$window', 'CnSession', 'CnHttpFactory',
    'CnParticipantModelFactory', 'CnScriptLauncherFactory', 'CnModalMessageFactory', 'CnModalConfirmFactory',
    function( $q, $state, $window, CnSession, CnHttpFactory,
              CnParticipantModelFactory, CnScriptLauncherFactory, CnModalMessageFactory, CnModalConfirmFactory ) {
      var object = function( root ) {
        var self = this;
        this.scriptLauncher = null;

        // need to 404 if state is undefined or not home/site
        this.type = null;
        if( 'assignment.home_control' == $state.current.name ) this.type = 'home';
        else if( 'assignment.site_control' == $state.current.name ) this.type = 'site';
        if( null == this.type ) {
          if( 'home' != $state.params.type && 'site' != $state.params.type ) $state.go( 'error.404' );
          this.type = $state.params.type;
        }

        this.reset = function() {
          self.assignment = null;
          self.prevAssignment = null;
          self.participant = null;
          self.phoneList = null;
          self.activePhoneCall = false;
          self.qnaireList = null;
          self.activeQnaire = null;
          self.lastQnaire = null;
          self.isScriptListLoading = false;
          self.scriptList = null;
          self.activeScript = null;
          self.phoneCallStatusList = null;
          self.phoneCallList = null;
          self.isAssignmentLoading = false;
          self.isForbidden = false;
          self.isPrevAssignmentLoading = false;
        };

        this.application = CnSession.application.title;
        this.participantModel = CnParticipantModelFactory.instance();

        // map assignment-control query parameters to participant-list
        this.participantModel.queryParameterSubject = 'assignment';

        // override the default column order for the participant list to rank
        this.participantModel.listModel.order = { column: 'rank', reverse: false };

        this.reset();

        // add additional columns to the model
        this.participantModel.addColumn( 'rank', { title: 'Rank', column: 'queue.rank', type: 'rank' }, 0 );
        this.participantModel.addColumn( 'language', { title: 'Language', column: 'language.name' }, 1 );
        this.participantModel.addColumn(
          'availability', { title: 'Availability', column: 'availability_type.name' } );
        if( 'home' == this.type ) {
          this.participantModel.addColumn( 'prev_event_user', { title: 'Previous Interviewer' } );
          this.participantModel.addColumn( 'address_summary', { title: 'Address' } );
        } else { // 'site' == this.type
          this.participantModel.addColumn( 'blood', { title: 'Blood', type: 'boolean' } );
          this.participantModel.addColumn( 'prev_event_site', { title: 'Previous Site' } );
          this.participantModel.addColumn( 'last_completed_datetime', {
            title: 'Home Completed',
            type: 'datetime'
          } );
        }

        // override the default order and set the heading
        this.participantModel.listModel.heading = 'Participant Selection List';

        // override model functions
        this.participantModel.getServiceCollectionPath = function() { return 'participant'; }
        this.participantModel.getServiceData = function( type, columnRestrictLists ) {
          var data = self.participantModel.$$getServiceData( type, columnRestrictLists );
          if( angular.isUndefined( data.modifier.where ) ) data.modifier.where = [];
          data.modifier.where.push( {
            column: 'qnaire.type',
            operator: '=',
            value: self.type
          } );
          data.assignment = true;
          return data;
        };

        // override the onChoose function
        this.participantModel.listModel.onSelect = function( record ) {
          // attempt to assign the participant to the user
          CnModalConfirmFactory.instance( {
            title: 'Begin Assignment',
            message: 'Are you sure you wish to start a new assignment with participant ' + record.uid + '?'
          } ).show().then( function( response ) {
            if( response ) {
              self.isAssignmentLoading = true; // show loading screen right away
              CnHttpFactory.instance( {
                path: 'assignment?operation=open',
                data: { participant_id: record.id },
                onError: function( response ) {
                  if( 409 == response.status ) {
                    // 409 means there is a conflict (the assignment can't be made)
                    CnModalMessageFactory.instance( {
                      title: 'Unable to start assignment with ' + record.uid,
                      message: response.data,
                      error: true
                    } ).show().then( self.onLoad );
                  } else { CnModalMessageFactory.httpError( response ); }
                }
              } ).post().then( self.onLoad );
            }
          } );
        };

        this.onLoad = function( closeScript ) {
          if( angular.isUndefined( closeScript ) ) closeScript = true;
          self.reset();
          self.isAssignmentLoading = true;
          self.isWrongType = false;
          self.isPrevAssignmentLoading = true;

          if( closeScript ) CnSession.closeScript();
          return CnHttpFactory.instance( {
            path: 'assignment/0',
            data: { select: { column: [ 'id', 'interview_id', 'start_datetime',
              { table: 'participant', column: 'id', alias: 'participant_id' },
              { table: 'qnaire', column: 'id', alias: 'qnaire_id' },
              { table: 'qnaire', column: 'name', alias: 'qnaire' },
              { table: 'qnaire', column: 'type', alias: 'type' },
              { table: 'queue', column: 'title', alias: 'queue' }
            ] } },
            onError: function( response ) {
              CnSession.updateData().then( function() {
                self.assignment = null;
                self.participant = null;
                self.isAssignmentLoading = false;
                self.isWrongType = false;
                self.isPrevAssignmentLoading = false;
                self.isForbidden = false;
                if( 307 == response.status ) {
                  // 307 means the user has no active assignment, so load the participant select list
                  CnSession.alertHeader = undefined;
                  self.participantModel.listModel.afterList( function() {
                    CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Select' } ] );
                  } );
                } else if( 403 == response.status ) {
                  CnSession.alertHeader = 'You are currently in a ' + self.type + ' assignment';
                  CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Wrong Site' } ] );
                  self.isForbidden = true;
                } else { CnModalMessageFactory.httpError( response ); }
              } );
            }
          } ).get().then( function( response ) {
            CnSession.updateData().then( function() {
              self.assignment = response.data;
              CnSession.alertHeader = 'You are currently in a ' + self.type + ' assignment';

              // first make sure that we're looking at the right assignment type
              if( self.assignment.type != self.type ) {
                self.isWrongType = self.type;
                self.isAssignmentLoading = false;
                CnSession.setBreadcrumbTrail( [ { title: 'Assignment' } ] );
              } else {
                self.isWrongType = false;

                // get the assigned participant's details
                CnHttpFactory.instance( {
                  path: 'participant/' + self.assignment.participant_id,
                  data: { select: { column: [
                    'id', 'uid', 'honorific', 'first_name', 'other_name', 'last_name', 'global_note',
                    { table: 'language', column: 'code', alias: 'language_code' },
                    { table: 'language', column: 'name', alias: 'language' }
                  ] } }
                } ).get().then( function( response ) {
                  self.participant = response.data;
                  self.participant.getIdentifier = function() {
                    return self.participantModel.getIdentifierFromRecord( self.participant );
                  };
                  CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: self.participant.uid } ] );
                  self.isAssignmentLoading = false;
                } ).then( function() {
                  CnHttpFactory.instance( {
                    path: 'assignment/0/phone_call',
                    data: { select: { column: [ 'end_datetime', 'status',
                      { table: 'phone', column: 'rank' },
                      { table: 'phone', column: 'type' },
                      { table: 'phone', column: 'number' }
                    ] } }
                  } ).query().then( function( response ) {
                    self.phoneCallList = response.data;
                    var len = self.phoneCallList.length
                    self.activePhoneCall = 0 < len && null === self.phoneCallList[len-1].end_datetime
                                         ? self.phoneCallList[len-1]
                                         : null;
                  } );
                } ).then( function() {
                  if( null === self.qnaireList ) {
                    // get the qnaire list and store the current and last qnaires
                    CnHttpFactory.instance( {
                      path: 'qnaire',
                      data: {
                        select: { column: ['id', 'rank', 'delay'] },
                        modifier: { order: 'rank' }
                      }
                    } ).query().then( function( response ) {
                      self.qnaireList = response.data;
                      var len = self.qnaireList.length;
                      if( 0 < len ) {
                        self.activeQnaire = self.qnaireList.findByProperty( 'id', self.assignment.qnaire_id );
                        self.lastQnaire = self.qnaireList[len-1];
                      }
                      self.loadScriptList(); // now load the script list
                    } );
                  }
                } ).then( function() {
                  CnHttpFactory.instance( {
                    path: 'participant/' + self.assignment.participant_id +
                          '/interview/' + self.assignment.interview_id + '/assignment',
                    data: {
                      select: {
                        column: [
                          'start_datetime',
                          'end_datetime',
                          'phone_call_count',
                          { table: 'last_phone_call', column: 'status' },
                          { table: 'user', column: 'first_name' },
                          { table: 'user', column: 'last_name' },
                          { table: 'user', column: 'name' }
                        ]
                      },
                      modifier: { order: { start_datetime: true }, offset: 1, limit: 1 }
                    }
                  } ).query().then( function( response ) {
                    self.prevAssignment = 1 == response.data.length ? response.data[0] : null;
                    self.isPrevAssignmentLoading = false;
                  } );
                } ).then( function() {
                  CnHttpFactory.instance( {
                    path: 'participant/' + self.assignment.participant_id + '/phone',
                    data: {
                      select: { column: [ 'id', 'rank', 'type', 'number', 'international', 'note' ] },
                      modifier: {
                        where: { column: 'phone.active', operator: '=', value: true },
                        order: 'rank'
                      }
                    }
                  } ).query().then( function( response ) {
                    self.phoneList = response.data;
                  } );
                } ).then( function() {
                  CnHttpFactory.instance( {
                    path: 'phone_call'
                  } ).head().then( function( response ) {
                    self.phoneCallStatusList =
                      cenozo.parseEnumList( angular.fromJson( response.headers( 'Columns' ) ).status );
                  } );
                } );
              }
            } );
          } );
        };

        this.changeSiteRole = function() { CnSession.showSiteRoleModal(); };

        this.openNotes = function() {
          if( null != self.participant )
            $state.go( 'participant.notes', { identifier: self.participant.getIdentifier() } );
        };

        this.openHistory = function() {
          if( null != self.participant )
            $state.go( 'participant.history', { identifier: self.participant.getIdentifier() } );
        };

        this.useTimezone = function() {
          if( null != self.participant ) {
            CnSession.setTimezone( { 'participant_id': this.participant.id } ).then( function() {
              $state.go( 'self.wait' ).then( function() { $window.location.reload(); } );
            } );
          }
        };

        this.loadScriptList = function() {
          var promiseList = [];
          if( null != self.assignment ) {
            self.isScriptListLoading = true;

            promiseList.push(
              CnHttpFactory.instance( {
                path: 'participant/' + self.assignment.participant_id,
                data: { select: { column: [ 'withdrawn' ] } }
              } ).get().then( function( response ) {
                if( null != self.participant ) self.participant.withdrawn = response.data.withdrawn;
              } )
            );

            promiseList.push(
              CnHttpFactory.instance( {
                path: 'application/0/script?participant_id=' + self.assignment.participant_id,
                data: {
                  modifier: { order: ['repeated','name'] },
                  select: { column: [
                    'id', 'name', 'repeated', 'url', 'description',
                    { table: 'started_event', column: 'datetime', alias: 'started_datetime' },
                    { table: 'finished_event', column: 'datetime', alias: 'finished_datetime' }
                  ] }
                }
              } ).query().then( function( response ) {
                self.scriptList = response.data;

                if( 0 == self.scriptList.length ) {
                  self.activeScript = null;
                } else {
                  if( null == self.activeScript ||
                      null == self.scriptList.findByProperty( 'id', self.activeScript.id ) ) {
                    self.activeScript = self.scriptList[0];
                  } else {
                    var activeScriptName = self.activeScript.name;
                    self.scriptList.forEach( function( item ) {
                      if( activeScriptName == item.name ) self.activeScript = item;
                    } );
                  }
                }
                self.isScriptListLoading = false;
              } )
            );
          }

          return $q.all( promiseList ).then( function() { self.isScriptListLoading = false; } );
        };

        this.launchScript = function( script ) {
          this.scriptLauncher = CnScriptLauncherFactory.instance( {
            script: script,
            identifier: 'uid=' + self.participant.uid,
            lang: self.participant.language_code
          } );
          this.scriptLauncher.launch().then( function() { self.loadScriptList(); } );
        };

        this.startCall = function( phone ) {
          function postCall() {
            CnHttpFactory.instance( {
              path: 'phone_call?operation=open',
              data: { phone_id: phone.id }
            } ).post().then( self.onLoad );
          }

          // start by updating the voip status
          CnSession.updateVoip().finally( function() {
            if( !CnSession.voip.enabled ) {
              postCall();
            } else {
              if( !CnSession.voip.info ) {
                if( !CnSession.setting.callWithoutWebphone ) {
                  CnModalMessageFactory.instance( {
                    title: 'Webphone Not Found',
                    message: 'You cannot start a call without a webphone connection. ' +
                             'To use the built-in telephone system click on the "Webphone" link under the ' +
                             '"Utilities" submenu and make sure the webphone client is connected.',
                    error: true
                  } ).show();
                } else if( !phone.international ) {
                  CnModalConfirmFactory.instance( {
                    title: 'Webphone Not Found',
                    message: 'You are about to place a call with no webphone connection. ' +
                             'If you choose to proceed you will have to contact the participant without the use ' +
                             'of the software-based telephone system. ' +
                             'If you wish to use the built-in telephone system click "No", then click on the ' +
                             '"Webphone" link under the "Utilities" submenu to connect to the webphone.\n\n' +
                             'Do you wish to proceed without a webphone connection?',
                  } ).show().then( function( response ) {
                    if( response ) postCall();
                  } );
                }
              } else {
                if( phone.international ) {
                  CnModalConfirmFactory.instance( {
                    title: 'International Phone Number',
                    message: 'The phone number you are about to call is international. ' +
                             'The VoIP system cannot place international calls so if you choose to proceed you ' +
                             'will have to contact the participant without the use of the software-based ' +
                             'telephone system.\n\n' +
                             'Do you wish to proceed without a webphone connection?',
                  } ).show().then( function( response ) {
                    if( response ) postCall();
                  } );
                } else {
                  CnHttpFactory.instance( {
                    path: 'voip',
                    data: { action: 'call', phone_id: phone.id }
                  } ).post().then( function( response ) {
                    if( 201 == response.status ) {
                      postCall();
                    } else {
                      CnModalMessageFactory.instance( {
                        title: 'Webphone Error',
                        message: 'The webphone was unable to place your call, please try again. ' +
                                 'If this problem persists then please contact support.',
                        error: true
                      } ).show();
                    }
                  } );
                }
              }
            }
          } );
        };

        this.endCall = function( status ) {
          if( CnSession.voip.enabled && CnSession.voip.info && !this.activePhoneCall.international ) {
            CnHttpFactory.instance( {
              path: 'voip/0',
              onError: function( response ) {
                if( 404 == response.status ) {
                  // ignore 404 errors, it just means there was no phone call found to hang up
                } else { CnModalMessageFactory.httpError( response ); }
              }
            } ).delete();
          }

          CnHttpFactory.instance( {
            path: 'phone_call/0?operation=close',
            data: { status: status }
          } ).patch().then( self.onLoad );
        };

        this.endAssignment = function() {
          if( null != self.assignment ) {
            CnHttpFactory.instance( {
              path: 'assignment/0',
              onError: function( response ) {
                if( 307 == response.status ) {
                  // 307 means the user has no active assignment, so just refresh the page data
                  self.onLoad();
                } else { CnModalMessageFactory.httpError( response ); }
              }
            } ).get().then( function( response ) {
              return CnHttpFactory.instance( {
                path: 'assignment/0?operation=close', data: {}
              } ).patch().then( self.onLoad );
            } );
          }
        };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

} );
