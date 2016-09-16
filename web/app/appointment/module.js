define( cenozoApp.module( 'site' ).getRequiredFiles(), function() {
  'use strict';

  try { var module = cenozoApp.module( 'appointment', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'interview',
        column: 'interview_id',
        friendly: 'qnaire'
      }
    },
    name: {
      singular: 'appointment',
      plural: 'appointments',
      possessive: 'appointment\'s',
      pluralPossessive: 'appointments\''
    },
    columnList: {
      datetime: {
        type: 'datetime',
        title: 'Date & Time'
      },
      formatted_user_id: {
        type: 'string',
        title: 'Interviewer',
        isIncluded: function( $state, model ) { return 'home' == $state.params.type || 'home' == model.type; }
      },
      address_summary: {
        type: 'string',
        title: 'Address',
        isIncluded: function( $state, model ) { return 'home' == $state.params.type || 'home' == model.type; }
      },
      appointment_type_id: {
        column: 'appointment_type.name',
        type: 'string',
        title: 'Special Type',
        help: 'Identified whether this is a special appointment type.  If blank then it is considered ' +
              'a "regular" appointment.'
      },
      state: {
        type: 'string',
        title: 'State',
        help: 'Will either be completed, cancelled, upcoming or passed'
      }
    },
    defaultOrder: {
      column: 'datetime',
      reverse: true
    }
  } );

  module.addInputGroup( '', {
    datetime: {
      title: 'Date & Time',
      type: 'datetime',
      min: 'now',
      help: 'Cannot be changed once the appointment has passed.'
    },
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      exclude: 'add',
      constant: true
    },
    qnaire: {
      column: 'qnaire.name',
      title: 'Questionnaire',
      type: 'string',
      exclude: 'add',
      constant: true
    },
    user_id: {
      column: 'appointment.user_id',
      title: 'Interviewer',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( first_name, " ", last_name, " (", name, ")" )',
        where: [ 'first_name', 'last_name', 'name' ]
      },
      help: 'The interviewer the appointment is to be scheduled with.'
    },
    address_id: {
      title: 'Address',
      type: 'enum',
      help: 'The address of the home appointment.'
    },
    state: {
      title: 'State',
      type: 'string',
      exclude: 'add',
      constant: true,
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    },
    appointment_type_id: {
      title: 'Special Type',
      type: 'enum',
      help: 'Identified whether this is a special appointment type.  If blank then it is considered ' +
            'a "regular" appointment.'
    },
    type: { column: 'qnaire.type', type: 'hidden' }
  } );

  // add an extra operation for home and site appointment types
  if( angular.isDefined( module.actions.calendar ) ) {
    module.addExtraOperation( 'calendar', {
      id: 'home-appointment-button',
      title: 'Home Appointment',
      operation: function( $state, model ) {
        $state.go( 'appointment.calendar', { type: 'home', identifier: model.site.getIdentifier() } );
      },
      classes: 'home-appointment-button'
    } );
  }

  if( angular.isDefined( module.actions.calendar ) ) {
    module.addExtraOperation( 'calendar', {
      id: 'site-appointment-button',
      title: 'Site Appointment',
      operation: function( $state, model ) {
        $state.go( 'appointment.calendar', { type: 'site', identifier: model.site.getIdentifier() } );
      },
      classes: 'site-appointment-button'
    } );
  }

  if( angular.isDefined( module.actions.calendar ) ) {
    module.addExtraOperation( 'view', {
      title: 'Appointment Calendar',
      operation: function( $state, model ) {
        $state.go( 'appointment.calendar', { type: model.type, identifier: model.site.getIdentifier() } );
      }
    } );
  }

  // converts appointments into events
  function getEventFromAppointment( appointment, timezone ) {
    if( angular.isDefined( appointment.start ) && angular.isDefined( appointment.end ) ) {
      return appointment;
    } else {
      var date = moment( appointment.datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() );

      // adjust the appointment for daylight savings time
      if( date.tz( timezone ).isDST() ) offset += -60;

      var event = {
        getIdentifier: function() { return appointment.getIdentifier() },
        title: ( appointment.uid ? appointment.uid : 'new appointment' ) +
               ( appointment.username ? ' (' + appointment.username + ')' : '' ),
        start: moment( appointment.datetime ).subtract( offset, 'minutes' ),
        end: moment( appointment.datetime ).subtract( offset, 'minutes' ).add( appointment.duration, 'minute' ),
        color: appointment.color
      };
      return event;
    }
  }

  // used by the add and view directives below
  function setupInputArray( CnHttpFactory, model, childScope ) {
    var inputArray = childScope.dataArray[0].inputArray;

    // show/hide user and address columns based on the type
    inputArray.findByProperty( 'key', 'user_id' ).type = 'home' == model.type ? 'lookup-typeahead' : 'hidden';
    inputArray.findByProperty( 'key', 'address_id' ).type = 'home' == model.type ? 'enum' : 'hidden';

    var identifier = model.getParentIdentifier();
    CnHttpFactory.instance( {
      path: identifier.subject + '/' + identifier.identifier,
      data: { select: { column: [ 'qnaire_id' ] } }
    } ).get().then( function( response ) {
      var appointmentTypeIndex = inputArray.findIndexByProperty( 'key', 'appointment_type_id' );

      // set the appointment type enum list based on the qnaire_id
      inputArray[appointmentTypeIndex].enumList = angular.copy(
        model.metadata.columnList.appointment_type_id.qnaireList[response.data.qnaire_id]
      );

      // we must also manually add the empty entry
      if( angular.isUndefined( inputArray[appointmentTypeIndex].enumList ) )
        inputArray[appointmentTypeIndex].enumList = [];
      if( null == inputArray[appointmentTypeIndex].enumList.findIndexByProperty( 'name', '(empty)' ) ) {
        inputArray[appointmentTypeIndex].enumList.unshift( {
          value: 'cnRecordAdd' == childScope.directive ? undefined : '',
          name: '(empty)'
        } );
      }
    } );
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', [
    'CnAppointmentModelFactory', 'CnSession', 'CnHttpFactory', 'CnModalConfirmFactory', '$q',
    function( CnAppointmentModelFactory, CnSession, CnHttpFactory, CnModalConfirmFactory, $q ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();

          // connect the calendar's day click callback to the appointment's datetime
          $scope.model.calendarModel.settings.dayClick = function( date ) {
            // make sure date is no earlier than today
            if( !date.isBefore( moment(), 'day' ) ) {
              var dateString = date.format( 'YYYY-MM-DD' ) + 'T12:00:00';
              var datetime = moment.tz( dateString, CnSession.user.timezone ).tz( 'UTC' );

              var cnRecordAddScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
              if( !cnRecordAddScope ) throw new Error( 'Unable to find appointment\'s cnRecordAdd scope.' );
              cnRecordAddScope.record.datetime = datetime.format();
              cnRecordAddScope.formattedRecord.datetime = CnSession.formatValue( datetime, 'datetime', true );
              $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
            }
          };

          $scope.model.addModel.afterNew( function() {
            // warn if old appointment will be cancelled
            var addDirective = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
            if( null == addDirective ) throw new Error( 'Unable to find appointment\'s cnRecordAdd scope.' );
            var saveFn = addDirective.save;
            addDirective.save = function() {
              CnHttpFactory.instance( {
                path: 'interview/' + $scope.model.getParentIdentifier().identifier,
                data: { select: { column: [ 'missed_appointment' ] } }
              } ).get().then( function( response ) {
                var proceed = false;
                var promise =
                  response.data.missed_appointment ?
                  CnModalConfirmFactory.instance( {
                    title: 'Cancel Missed Appointment?',
                    message: 'There already exists a passed appointment for this interview, ' +
                             'do you wish to cancel it and create a new one?'
                  } ).show().then( function( response ) { proceed = response; } ) :
                  $q.all().then( function() { proceed = true; } );

                // proceed with the usual save function if we are told to proceed
                promise.then( function() { if( proceed ) saveFn(); } );
              } );
            };
          } );

          $scope.model.addModel.afterNew( function() {
            // make sure the metadata has been created
            $scope.model.metadata.getPromise().then( function() {
              var cnRecordAddScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
              if( !cnRecordAddScope ) throw new Error( 'Cannot find cnRecordAdd scope' );

              // automatically fill in the current user as the interviewer (or null if this is a site appointment)
              cnRecordAddScope.record.user_id = 'home' == $scope.model.type ? CnSession.user.id : null;
              cnRecordAddScope.formattedRecord.user_id = 'home' == $scope.model.type ?
                CnSession.user.firstName + ' ' + CnSession.user.lastName + ' (' + CnSession.user.name + ')' : null;

              $scope.model.addModel.heading = $scope.model.type.ucWords() + ' Appointment Details';
              setupInputArray( CnHttpFactory, $scope.model, cnRecordAddScope );
            } );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentCalendar', [
    'CnAppointmentModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
          $scope.model.calendarModel.heading = $scope.model.site.name.ucWords() + ' - '
            + ( 'home' == $scope.model.type && 'interviewer' == CnSession.role.name ? 'Personal ' : '' )
            + $scope.model.type.ucWords() + ' Appointment Calendar';
        },
        link: function( scope, element ) {
          // highlight the calendar button that we're currently viewing
          var homeListener = scope.$watch(
            function() { return element.find( '#home-appointment-button' ).length; },
            function( length ) {
              if( 0 < length ) {
                var homeButton = element.find( '#home-appointment-button' );
                homeButton.addClass( 'home' == scope.model.type ? 'btn-warning' : 'btn-default' );
                homeButton.removeClass( 'home' == scope.model.type ? 'btn-default' : 'btn-warning' );
                homeListener(); // your watch has ended
              }
            }
          );

          var siteListener = scope.$watch(
            function() { return element.find( '#site-appointment-button' ).length; },
            function( length ) {
              if( 0 < length ) {
                var siteButton = element.find( '#site-appointment-button' );
                siteButton.addClass( 'site' == scope.model.type ? 'btn-warning' : 'btn-default' );
                siteButton.removeClass( 'site' == scope.model.type ? 'btn-default' : 'btn-warning' );
                siteListener(); // your watch has ended
              }
            }
          );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentList', [
    'CnAppointmentModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentView', [
    'CnAppointmentModelFactory', 'CnSession', 'CnHttpFactory',
    function( CnAppointmentModelFactory, CnSession, CnHttpFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope, $element ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();

          // connect the calendar's day click callback to the appointment's datetime
          if( $scope.model.getEditEnabled() ) {
            $scope.model.calendarModel.settings.dayClick = function( date ) {
              // make sure date is no earlier than today
              if( !date.isBefore( moment(), 'day' ) ) {
                var dateString = date.format( 'YYYY-MM-DD' ) + 'T12:00:00';
                var datetime = moment.tz( dateString, CnSession.user.timezone ).tz( 'UTC' );

                // if we clicked today then make sure it's after the current time
                if( !datetime.isAfter( moment() ) ) datetime.hour( moment().hour() + 1 );

                if( !datetime.isSame( moment( $scope.model.viewModel.record.datetime ) ) ) {
                  var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );
                  if( null == cnRecordViewScope )
                    throw new Error( 'Unable to find appointment\'s cnRecordView scope.' );

                  $scope.model.viewModel.record.datetime = datetime.format();
                  $scope.model.viewModel.formattedRecord.datetime =
                    CnSession.formatValue( datetime, 'datetime', true );
                  $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                  cnRecordViewScope.patch( 'datetime' );

                  // update the calendar
                  $element.find( 'div.calendar' ).fullCalendar( 'refetchEvents' );
                }
              }
            };
          }

          $scope.model.viewModel.afterView( function() {
            // make sure the metadata has been created
            $scope.model.metadata.getPromise().then( function() {
              var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );
              if( !cnRecordViewScope ) throw new Error( 'Cannot find cnRecordView scope' );

              $scope.model.viewModel.heading = $scope.model.type.ucWords() + ' Appointment Details';
              setupInputArray( CnHttpFactory, $scope.model, cnRecordViewScope );
            } );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentAddFactory', [
    'CnBaseAddFactory', 'CnSession', 'CnHttpFactory', '$injector',
    function( CnBaseAddFactory, CnSession, CnHttpFactory, $injector ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        this.onNew = function( record ) {
          // update the address list based on the parent interview
          var parent = parentModel.getParentIdentifier();
          return CnHttpFactory.instance( {
            path: [ parent.subject, parent.identifier ].join( '/' ),
            data: { select: { column: { column: 'participant_id' } } }
          } ).query().then( function( response ) {
            // get the participant's address list
            return CnHttpFactory.instance( {
              path: ['participant', response.data.participant_id, 'address' ].join( '/' ),
              data: {
                select: { column: [ 'id', 'rank', 'summary' ] },
                modifier: {
                  where: { column: 'address.active', operator: '=', value: true },
                  order: { rank: false }
                }
              }
            } ).query().then( function( response ) {
              return parentModel.metadata.getPromise().then( function() {
                parentModel.metadata.columnList.address_id.enumList = [];
                response.data.forEach( function( item ) {
                  parentModel.metadata.columnList.address_id.enumList.push( {
                    value: item.id,
                    name: item.summary
                  } );
                } );
                return self.$$onNew( record );
              } );
            } );
          } )
        };

        // add the new appointment's events to the calendar cache
        this.onAdd = function( record ) {
          // if the user_id is null then make the interview_id null as well
          // this is a cheat for knowing it's a site appointment
          if( null == record.user_id ) record.address_id = null;
          return this.$$onAdd( record );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentCalendarFactory', [
    'CnBaseCalendarFactory', 'CnSession',
    function( CnBaseCalendarFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseCalendarFactory.construct( this, parentModel );

        // default to month view
        this.currentView = 'month';

        // remove day click callback
        delete this.settings.dayClick;

        // extend onCalendar to transform templates into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // due to a design flaw (home vs site instances which cannot be determined in the base model's instance
          // method) we have to always replace events
          replace = true;

          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          return self.$$onCalendar( replace, minDate, maxDate, true ).then( function() {
            self.cache.forEach( function( item, index, array ) {
              array[index] = getEventFromAppointment( item, CnSession.user.timezone );
            } );
          } );
        };
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseListFactory.construct( this, parentModel );

        // override onDelete
        this.onDelete = function( record ) {
          return this.$$onDelete( record ).then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != record.getIdentifier();
            } );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentViewFactory', [
    'CnBaseViewFactory', 'CnSession', 'CnHttpFactory', '$injector',
    function( CnBaseViewFactory, CnSession, CnHttpFactory, $injector ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        this.onView = function() {
          return self.$$onView().then( function() {
            var upcoming = moment().isBefore( self.record.datetime, 'minute' );
            parentModel.getDeleteEnabled = function() { return parentModel.$$getDeleteEnabled() && upcoming; };
            parentModel.getEditEnabled = function() { return parentModel.$$getEditEnabled() && upcoming; };

            // update the address list based on the parent interview
            return CnHttpFactory.instance( {
              path: 'interview/' + self.record.interview_id,
              data: { select: { column: { column: 'participant_id' } } }
            } ).query().then( function( response ) {
              // get the participant's address list
              return CnHttpFactory.instance( {
                path: ['participant', response.data.participant_id, 'address' ].join( '/' ),
                data: {
                  select: { column: [ 'id', 'rank', 'summary' ] },
                  modifier: {
                    where: { column: 'address.active', operator: '=', value: true },
                    order: { rank: false }
                  }
                }
              } ).query().then( function( response ) {
                return parentModel.metadata.getPromise().then( function() {
                  parentModel.metadata.columnList.address_id.enumList = [];
                  response.data.forEach( function( item ) {
                    parentModel.metadata.columnList.address_id.enumList.push( {
                      value: item.id,
                      name: item.summary
                    } );
                  } );
                } );
              } );
            } );
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentModelFactory', [
    'CnBaseModelFactory',
    'CnAppointmentAddFactory', 'CnAppointmentCalendarFactory',
    'CnAppointmentListFactory', 'CnAppointmentViewFactory',
    'CnSession', 'CnHttpFactory', '$q', '$state', '$rootScope',
    function( CnBaseModelFactory,
              CnAppointmentAddFactory, CnAppointmentCalendarFactory,
              CnAppointmentListFactory, CnAppointmentViewFactory,
              CnSession, CnHttpFactory, $q, $state, $rootScope ) {
      var object = function( site ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnAppointmentModel without specifying the site.' );

        var self = this;

        CnBaseModelFactory.construct( this, module );
        this.addModel = CnAppointmentAddFactory.instance( this );
        this.calendarModel = CnAppointmentCalendarFactory.instance( this );
        this.listModel = CnAppointmentListFactory.instance( this );
        this.viewModel = CnAppointmentViewFactory.instance( this, site.id == CnSession.site.id );
        this.site = site;
        this.type = $state.params.type;

        // customize service data
        this.getServiceData = function( type, columnRestrictLists ) {
          this.type = $state.params.type;
          var data = this.$$getServiceData( type, columnRestrictLists );
          if( 'calendar' == type || 'list' == type ) {
            if( 'appointment' == self.getSubjectFromState() ) data.restricted_site_id = self.site.id;
            data.type = self.type;
            if( 'calendar' == type ) {
              data.select = { column: [ 'datetime', {
                table: 'appointment_type',
                column: 'color'
              } ] };
            }
          }
          return data;
        };

        // don't show add button when viewing full appointment list
        this.getAddEnabled = function() {
          return 'appointment' != this.getSubjectFromState() && this.$$getAddEnabled();
        };

        // pass type/site when transitioning to list state
        this.transitionToParentListState = function( subject ) {
          this.type = $state.params.type;
          if( angular.isUndefined( subject ) ) subject = '^';
          return $state.go(
            subject + '.list',
            { type: this.type, identifier: this.site.getIdentifier() }
          );
        };

        // pass type when transitioning to add state
        this.transitionToAddState = function() {
          this.type = $state.params.type;
          var params = { type: this.type, parentIdentifier: $state.params.identifier };

          // get the participant's primary site (assuming the current state is an interview)
          return CnHttpFactory.instance( {
            path: 'interview/' + $state.params.identifier,
            data: { select: { column: [ { table: 'effective_site', column: 'name' } ] } }
          } ).get().then( function( response ) {
            if( response.data.name ) params.site = 'name=' + response.data.name;
            return $state.go( '^.add_' + self.module.subject.snake, params );
          } );
        };

        // pass type/site when transitioning to list state
        this.transitionToListState = function( record ) {
          this.type = $state.params.type;
          return $state.go(
            this.module.subject.snake + '.list',
            { type: this.type, identifier: this.site.getIdentifier() }
          );
        };

        // pass type when transitioning to view state
        this.transitionToViewState = function( record ) {
          this.type = $state.params.type;
          var params = { type: this.type, identifier: record.getIdentifier() };

          // get the participant's primary site (assuming the current state is an interview)
          return CnHttpFactory.instance( {
            path: 'appointment/' + record.getIdentifier(),
            data: { select: { column: [ { table: 'effective_site', column: 'name' } ] } }
          } ).get().then( function( response ) {
            if( response.data.name ) params.site = 'name=' + response.data.name;
            return $state.go( self.module.subject.snake + '.view', params );
          } );
        };

        // pass type when transitioning to last state
        this.transitionToLastState = function() {
          this.type = $state.params.type;
          var parent = this.getParentIdentifier();
          return $state.go(
            parent.subject + '.view',
            { type: this.type, identifier: parent.identifier }
          );
        };

        this.transitionToParentViewState = function( subject, identifier ) {
          this.type = $state.params.type;
          var params = { identifier: identifier };
          if( 'interview' == subject ) params.type = this.type;
          return $state.go( subject + '.view', params );
        };

        // extend getBreadcrumbTitle
        this.setupBreadcrumbTrail = function() {
          this.type = $state.params.type;
          this.$$setupBreadcrumbTrail();
          // add the type to the "appointment" crumb
          if( this.type ) {
            var crumb = CnSession.breadcrumbTrail.findByProperty( 'title', 'Appointment' );
            if( !crumb ) var crumb = CnSession.breadcrumbTrail.findByProperty( 'title', 'Appointments' );
            if( crumb ) crumb.title = this.type[0].toUpperCase() + this.type.substring( 1 ) + ' ' + crumb.title;
          }
        };

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            // Force the user and address columns to be mandatory (this will only affect home appointments)
            self.metadata.columnList.user_id.required = true;
            self.metadata.columnList.address_id.required = true;

            return CnHttpFactory.instance( {
              path: 'appointment_type',
              data: {
                select: { column: [ 'id', 'name', 'qnaire_id' ] },
                modifier: { order: 'name' }
              }
            } ).query().then( function success( response ) {
              // store the appointment types in a special array with qnaire_id as indeces:
              var qnaireList = {};
              response.data.forEach( function( item ) {
                if( angular.isUndefined( qnaireList[item.qnaire_id] ) ) qnaireList[item.qnaire_id] = [];
                qnaireList[item.qnaire_id].push( { value: item.id, name: item.name, } );
              } );
              self.metadata.columnList.appointment_type_id.qnaireList = qnaireList;

              // and leave the enum list empty for now, it will be set by the view/add services
              self.metadata.columnList.appointment_type_id.enumList = [];
            } );
          } );
        };

        // extend getTypeaheadData
        this.getTypeaheadData = function( input, viewValue ) {
          var data = this.$$getTypeaheadData( input, viewValue );

          // only include active users
          if( 'user' == input.typeahead.table ) {
            data.modifier.where.unshift( { bracket: true, open: true } );
            data.modifier.where.push( { bracket: true, open: false } );
            data.modifier.where.push( { column: 'active', operator: '=', value: true } );

            // restrict to the current site
            if( this.site ) data.restricted_site_id = this.site.id;
          }

          return data;
        };
      };

      // get the siteColumn to be used by a site's identifier
      var siteModule = cenozoApp.module( 'site' );
      var siteColumn = angular.isDefined( siteModule.identifier.column ) ? siteModule.identifier.column : 'id';

      return {
        siteInstanceList: {},
        forSite: function( site ) {
          if( !angular.isObject( site ) ) {
            $state.go( 'error.404' );
            throw new Error( 'Cannot find site matching identifier "' + site + '", redirecting to 404.' );
          }
          if( angular.isUndefined( this.siteInstanceList[site.id] ) )
            this.siteInstanceList[site.id] = new object( site );
          if( $state.params.type ) this.siteInstanceList[site.id].type = $state.params.type;
          return this.siteInstanceList[site.id];
        },
        instance: function() {
          var site = null;
          var currentState = $state.current.name.split( '.' )[1];
          if( 'calendar' == currentState || 'list' == currentState ) {
            if( angular.isDefined( $state.params.identifier ) ) {
              var identifier = $state.params.identifier.split( '=' );
              if( 2 == identifier.length )
                site = CnSession.siteList.findByProperty( identifier[0], identifier[1] );
            }
          } else if( 'add_appointment' == currentState || 'view' == currentState ) {
            if( angular.isDefined( $state.params.site ) ) {
              var identifier = $state.params.site.split( '=' );
              if( 2 == identifier.length )
                site = CnSession.siteList.findByProperty( identifier[0], identifier[1] );
            }
          }

          if( null == site ) site = CnSession.site;
          if( angular.isUndefined( site.getIdentifier ) )
            site.getIdentifier = function() { return 'name=' + this.name; };
          return this.forSite( site );
        }
      };
    }
  ] );

} );
