cenozoApp.extendModule( { name: 'assignment', dependencies: 'participant', create: module => {

  module.identifier.parent.friendly = 'qnaire';

  module.addInput( '', 'qnaire', {
    column: 'qnaire.name',
    title: 'Questionnaire',
    type: 'string',
    isConstant: true
  }, 'participant' );
  module.addInput( '', 'queue', {
    column: 'queue.title',
    title: 'Queue',
    type: 'string',
    isConstant: true
  }, 'site' );

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
        var win = angular.element( $window ).on( 'focus', async () => {
          await scope.model.updateLimesurveyToken();
          scope.model.loadScriptList();
        } );
        scope.$on( '$destroy', function() { win.off( 'focus' ); } );

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
          transitionToParentViewState: async function( subject, identifier ) {
            await $state.go(
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
        // need to 404 if state is undefined or not home/site
        this.type = null;
        if( 'assignment.home_control' == $state.current.name ) this.type = 'home';
        else if( 'assignment.site_control' == $state.current.name ) this.type = 'site';
        if( null == this.type ) {
          if( 'home' != $state.params.type && 'site' != $state.params.type ) {
            $state.go( 'error.404' );
            throw 'Cannot find user matching assignment type "' + $state.params.type + '", redirecting to 404.';
          }
          this.type = $state.params.type;
        }

        angular.extend( this, {
          scriptLauncher: null,

          reset: function() {
            this.assignment = null;
            this.prevAssignment = null;
            this.participant = null;
            this.phoneList = null;
            this.activePhoneCall = false;
            this.qnaireList = null;
            this.activeQnaire = null;
            this.lastQnaire = null;
            this.scriptList = null;
            this.activeScript = null;
            this.phoneCallStatusList = null;
            this.phoneCallList = null;
            this.isForbidden = false;
            this.isWrongType = false;
            this.isScriptListLoading = false;
            this.isAssignmentLoading = false;
            this.isPrevAssignmentLoading = false;
          },

          application: CnSession.application.title,
          participantModel: CnParticipantModelFactory.instance(),

          onLoad: async function( closeScript ) {
            if( angular.isUndefined( closeScript ) ) closeScript = true;
            this.reset();

            if( closeScript ) CnSession.closeScript();

            var column = [ 'id', 'interview_id', 'start_datetime',
              { table: 'participant', column: 'id', alias: 'participant_id' },
              { table: 'qnaire', column: 'id', alias: 'qnaire_id' },
              { table: 'qnaire', column: 'name', alias: 'qnaire' },
              { table: 'qnaire', column: 'type', alias: 'type' },
              { table: 'queue', column: 'title', alias: 'queue' }
            ];

            if( CnSession.application.checkForMissingHin ) column.push( 'missing_hin' );

            try {
              this.isAssignmentLoading = true;
              this.isPrevAssignmentLoading = true;

              var self = this;
              var response = await CnHttpFactory.instance( {
                path: 'assignment/0',
                data: { select: { column: column } },
                onError: async function( error ) {
                  await CnSession.updateData();

                  self.assignment = null;
                  self.participant = null;
                  self.isForbidden = false;
                  if( 307 == error.status ) {
                    // 307 means the user has no active assignment, so load the participant select list
                    CnSession.alertHeader = undefined;
                    self.participantModel.listModel.afterList( function() {
                      CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Select' } ] );
                    } );
                  } else if( 403 == error.status ) {
                    CnSession.alertHeader = 'You are currently in a ' + self.type + ' assignment';
                    CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Wrong Site' } ] );
                    self.isForbidden = true;
                  } else { CnModalMessageFactory.httpError( error ); }
                }
              } ).get();

              await CnSession.updateData();

              this.assignment = response.data;
              CnSession.alertHeader = 'You are currently in a ' + this.type + ' assignment';

              // show a popup if the participant is missing HIN data
              // Note: this will only show if the participant has consented to provide HIN but hasn't provided an HIN number
              if( CnSession.application.checkForMissingHin && this.assignment.missing_hin ) {
                CnModalMessageFactory.instance( {
                  title: 'Missing HIN',
                  message:
                    'The participant has consented to provide their Health Insurance Number (HIN) but their number is not on file.\n\n' +
                    'Please ask the participant to provide their HIN number.  The details can be added in the participant\'s file ' +
                    'under the "HIN List" section.'
                } ).show();
              }

              // first make sure that we're looking at the right assignment type
              if( this.assignment.type != this.type ) {
                this.isWrongType = this.type;
                this.isAssignmentLoading = false;
                CnSession.setBreadcrumbTrail( [ { title: 'Assignment' } ] );
              } else {
                this.isWrongType = false;

                // get notes from the last interview
                try {
                  var response = await CnHttpFactory.instance( {
                    path: 'interview/' + this.assignment.interview_id + '?last_interview_note=1'
                  } ).get()
                  this.last_interview_note = response.data;
                } catch( error ) {
                  console.error( 'Failed to get the last interview\'s note' );
                }

                // get the assigned participant's details
                try {
                  var response = await CnHttpFactory.instance( {
                    path: 'participant/' + this.assignment.participant_id,
                    data: { select: { column: [
                      'id', 'uid', 'honorific', 'first_name', 'other_name', 'last_name', 'global_note',
                      { table: 'language', column: 'code', alias: 'language_code' },
                      { table: 'language', column: 'name', alias: 'language' }
                    ] } }
                  } ).get();
                  this.participant = response.data;

                  var self = this;
                  this.participant.getIdentifier = function() {
                    return self.participantModel.getIdentifierFromRecord( self.participant );
                  };
                  CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: this.participant.uid } ] );
                } catch( error ) {
                  console.error( 'Failed to get participant\'s details' );
                } finally {
                  this.isAssignmentLoading = false;
                }

                try {
                  var response = await CnHttpFactory.instance( {
                    path: 'assignment/0/phone_call',
                    data: { select: { column: [ 'end_datetime', 'status',
                      { table: 'phone', column: 'rank' },
                      { table: 'phone', column: 'type' },
                      { table: 'phone', column: 'number' }
                    ] } }
                  } ).query();

                  this.phoneCallList = response.data;
                  var len = this.phoneCallList.length
                  this.activePhoneCall = 0 < len && null === this.phoneCallList[len-1].end_datetime
                                       ? this.phoneCallList[len-1]
                                       : null;
                } catch( error ) {
                  console.error( 'Failed to get phone call information' );
                }

                if( null === this.qnaireList ) {
                  try {
                    // get the qnaire list and store the current and last qnaires
                    var response = await CnHttpFactory.instance( {
                      path: 'qnaire',
                      data: {
                        select: { column: ['id', 'rank', 'delay_offset', 'delay_unit'] },
                        modifier: { order: 'rank' }
                      }
                    } ).query();

                    this.qnaireList = response.data;
                    var len = this.qnaireList.length;
                    if( 0 < len ) {
                      this.activeQnaire = this.qnaireList.findByProperty( 'id', this.assignment.qnaire_id );
                      this.lastQnaire = this.qnaireList[len-1];
                    }
                    this.loadScriptList(); // now load the script list
                  } catch( error ) {
                    console.error( 'Failed to get questionnaire list' );
                  }
                }

                try {
                  var response = await CnHttpFactory.instance( {
                    path: 'participant/' + this.assignment.participant_id +
                          '/interview/' + this.assignment.interview_id + '/assignment',
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
                  } ).query();

                  this.prevAssignment = 1 == response.data.length ? response.data[0] : null;
                } catch( error ) {
                  console.error( 'Failed to get previous assignment information' );
                } finally {
                  this.isPrevAssignmentLoading = false;
                }

                try {
                  var response = await CnHttpFactory.instance( {
                    path: 'participant/' + this.assignment.participant_id + '/phone',
                    data: {
                      select: { column: [ 'id', 'rank', 'type', 'number', 'international', 'note' ] },
                      modifier: {
                        where: { column: 'phone.active', operator: '=', value: true },
                        order: 'rank'
                      }
                    }
                  } ).query();

                  this.phoneList = response.data;
                } catch( error ) {
                  console.error( 'Failed to get participant\'s phone list' );
                }

                try {
                  var response = await CnHttpFactory.instance( { path: 'phone_call' } ).head();
                  this.phoneCallStatusList = cenozo.parseEnumList( angular.fromJson( response.headers( 'Columns' ) ).status );
                } catch( error ) {
                  console.error( 'Failed to get database metadata' );
                }
              }
            } catch( error ) {
              // ignore since the http onError function catches this error above
            } finally {
              this.isAssignmentLoading = false;
              this.isPrevAssignmentLoading = false;
            }
          },

          changeSiteRole: function() { CnSession.showSiteRoleModal(); },

          openNotes: async function() {
            if( null != this.participant )
              await $state.go( 'participant.notes', { identifier: this.participant.getIdentifier() } );
          },

          openHistory: async function() {
            if( null != this.participant )
              await $state.go( 'participant.history', { identifier: this.participant.getIdentifier() } );
          },

          useTimezone: async function() {
            if( null != this.participant ) {
              await CnSession.setTimezone( { 'participant_id': this.participant.id } );
              await $state.go( 'self.wait' );
              $window.location.reload();
            }
          },

          loadScriptList: async function() {
            if( null == this.assignment ) return;

            try {
              this.isScriptListLoading = true;

              var response = await CnHttpFactory.instance( {
                path: 'participant/' + this.assignment.participant_id,
                data: { select: { column: [
                  { table: 'hold_type', column: 'name', alias: 'hold' },
                  { table: 'proxy_type', column: 'name', alias: 'proxy' }
                ] } }
              } ).get();

              if( null != this.participant ) {
                this.participant.withdrawn = 'Withdrawn' == response.data.hold;
                this.participant.proxy = null != response.data.proxy;
              }

              var response = await CnHttpFactory.instance( {
                path: 'application/0/script?participant_id=' + this.assignment.participant_id,
                data: {
                  modifier: { order: ['repeated','name'] },
                  select: { column: [
                    'id', 'name', 'repeated', 'supporting', 'url', 'description',
                    { table: 'started_event', column: 'datetime', alias: 'started_datetime' },
                    { table: 'finished_event', column: 'datetime', alias: 'finished_datetime' }
                  ] }
                }
              } ).query();

              this.scriptList = response.data;

              if( 0 == this.scriptList.length ) {
                this.activeScript = null;
              } else {
                if( null == this.activeScript ||
                    null == this.scriptList.findByProperty( 'id', this.activeScript.id ) ) {
                  this.activeScript = this.scriptList[0];
                } else {
                  var activeScriptName = this.activeScript.name;
                  this.scriptList.forEach( item => { if( activeScriptName == item.name ) this.activeScript = item; } );
                }
              }
            } finally {
              this.isScriptListLoading = false;
            }
          },

          scriptLauncherBusy: false,
          launchScript: async function( script ) {
            try {
              this.scriptLauncherBusy = true;
              this.scriptLauncher = CnScriptLauncherFactory.instance( {
                script: script,
                identifier: 'uid=' + this.participant.uid,
                lang: this.participant.language_code
              } );
              await this.scriptLauncher.initialize();
              await this.scriptLauncher.launch( { show_hidden: 1 } );
              await this.loadScriptList();
            } finally {
              this.scriptLauncherBusy = false;
            }

            // check for when the window gets focus back and update the participant details
            if( null != script.name.match( /withdraw|proxy/i ) ) {
              this.updateLimesurveyTokenScriptId = script.id;
            }
          },

          updateLimesurveyTokenScriptId: null,
          updateLimesurveyToken: async function() {
            if( null == this.assignment ) {
              this.updateLimesurveyTokenScriptId = null;
              return;
            }

            if( this.updateLimesurveyTokenScriptId ) {
              var url = 'script/' + this.updateLimesurveyTokenScriptId + '/token/uid=' + this.participant.uid;
              this.updateLimesurveyTokenScriptId = null;

              // the following will process the withdraw or proxy script (in case it was finished)
              try {
                this.scriptLauncherBusy = true;
                await CnHttpFactory.instance( { path: url } ).get();
              } finally {
                this.scriptLauncherBusy = false;
              }
            }
          },

          startCall: async function( phone ) {
            // start by updating the voip status
            try {
              await CnSession.updateVoip();
            } finally {
              var call = false;

              if( !CnSession.voip.enabled ) {
                call = true;
              } else {
                if( !CnSession.voip.info ) {
                  if( !CnSession.setting.callWithoutWebphone ) {
                    await CnModalMessageFactory.instance( {
                      title: 'Webphone Not Found',
                      message: 'You cannot start a call without a webphone connection. ' +
                               'To use the built-in telephone system click on the "Webphone" link under the ' +
                               '"Utilities" submenu and make sure the webphone client is connected.',
                      error: true
                    } ).show();
                  } else if( !phone.international ) {
                    var response = await CnModalConfirmFactory.instance( {
                      title: 'Webphone Not Found',
                      message: 'You are about to place a call with no webphone connection. ' +
                               'If you choose to proceed you will have to contact the participant without the use ' +
                               'of the software-based telephone system. ' +
                               'If you wish to use the built-in telephone system click "No", then click on the ' +
                               '"Webphone" link under the "Utilities" submenu to connect to the webphone.\n\n' +
                               'Do you wish to proceed without a webphone connection?',
                    } ).show();
                    call = response;
                  }
                } else {
                  if( phone.international ) {
                    var response = await CnModalConfirmFactory.instance( {
                      title: 'International Phone Number',
                      message: 'The phone number you are about to call is international. ' +
                               'The VoIP system cannot place international calls so if you choose to proceed you ' +
                               'will have to contact the participant without the use of the software-based ' +
                               'telephone system.\n\n' +
                               'Do you wish to proceed without a webphone connection?',
                    } ).show();
                    call = response;
                  } else {
                    var response = await CnHttpFactory.instance( {
                      path: 'voip',
                      data: { action: 'call', phone_id: phone.id }
                    } ).post();

                    if( 201 == response.status ) {
                      call = true;
                    } else {
                      CnModalMessageFactory.instance( {
                        title: 'Webphone Error',
                        message: 'The webphone was unable to place your call, please try again. ' +
                                 'If this problem persists then please contact support.',
                        error: true
                      } ).show();
                    }
                  }
                }
              }

              if( call ) {
                await CnHttpFactory.instance( { path: 'phone_call?operation=open', data: { phone_id: phone.id } } ).post();
                await this.onLoad();
              }
            }
          },

          endCall: async function( status ) {
            if( CnSession.voip.enabled && CnSession.voip.info && !this.activePhoneCall.international ) {
              try {
                await CnHttpFactory.instance( {
                  path: 'voip/0',
                  onError: function( error ) {
                    if( 404 == error.status ) {
                      // ignore 404 errors, it just means there was no phone call found to hang up
                    } else { CnModalMessageFactory.httpError( error ); }
                  }
                } ).delete();
              } catch( error ) {
                // handled by onError above
              }
            }

            await CnHttpFactory.instance( { path: 'phone_call/0?operation=close', data: { status: status } } ).patch();
            await this.onLoad();
          },

          endAssignment: async function() {
            if( null != this.assignment ) {
              var self = this;
              var response = await CnHttpFactory.instance( {
                path: 'assignment/0',
                onError: async function( error ) {
                  if( 307 == error.status ) {
                    // 307 means the user has no active assignment, so just refresh the page data
                    await self.onLoad();
                  } else { CnModalMessageFactory.httpError( error ); }
                }
              } ).get();

              await CnHttpFactory.instance( { path: 'assignment/0?operation=close', data: {} } ).patch();
              await this.onLoad();
            }
          }
        } );

        var self = this;
        angular.extend( this.participantModel, {
          // map assignment-control query parameters to participant-list
          queryParameterSubject: 'assignment',

          // override model functions
          getServiceCollectionPath: function() { return 'participant'; },

          getServiceData: function( type, columnRestrictLists ) {
            var data = this.$$getServiceData( type, columnRestrictLists );
            if( angular.isUndefined( data.modifier.where ) ) data.modifier.where = [];
            data.modifier.where.push( { column: 'qnaire.type', operator: '=', value: self.type } );
            data.assignment = true;
            return data;
          }
        } );

        var self = this;
        angular.extend( this.participantModel.listModel, {
          // override the default column order for the participant list to rank
          order: { column: 'rank', reverse: false },

          // override the default order and set the heading
          heading: 'Participant Selection List',

          // override the onChoose function
          onSelect: async function( record ) {
            // attempt to assign the participant to the user
            var response = await CnModalConfirmFactory.instance( {
              title: 'Begin Assignment',
              message: 'Are you sure you wish to start a new assignment with participant ' + record.uid + '?'
            } ).show();

            if( response ) {
              self.isAssignmentLoading = true; // show loading screen right away
              await CnHttpFactory.instance( {
                path: 'assignment?operation=open',
                data: { participant_id: record.id },
                onError: async function( error ) {
                  self.isAssignmentLoading = false;
                  if( 409 == error.status ) {
                    // 409 means there is a conflict (the assignment can't be made)
                    await CnModalMessageFactory.instance( {
                      title: 'Unable to start assignment with ' + record.uid,
                      message: error.data,
                      error: true
                    } ).show();
                    await self.onLoad();
                  } else { CnModalMessageFactory.httpError( error ); }
                }
              } ).post();
              await self.onLoad();
            }
          }
        } );

        this.reset();

        async function init( object ){
          await CnSession.promise;
          object.application = CnSession.application.title;

          // add additional columns to the model
          object.participantModel.addColumn( 'rank', { title: 'Rank', column: 'queue.rank', type: 'rank' }, 0 );
          object.participantModel.addColumn( 'language', { title: 'Language', column: 'language.name' }, 1 );
          object.participantModel.addColumn( 'availability', { title: 'Availability', column: 'availability_type.name' } );
          if( 'home' == object.type ) {
            object.participantModel.addColumn( 'prev_event_user', { title: 'Previous Interviewer' } );
            object.participantModel.addColumn( 'address_summary', { title: 'Address' } );
          } else { // 'site' == object.type
            object.participantModel.addColumn( 'blood', { title: 'Blood', type: 'boolean' } );
            object.participantModel.addColumn( 'prev_event_site', { title: 'Previous Site' } );
            object.participantModel.addColumn( 'last_completed_datetime', { title: 'Home Completed', type: 'datetime' } );
          }
        }

        init( this );
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

} } );
