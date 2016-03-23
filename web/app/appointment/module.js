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
        title: 'Interviewer'
      },
      address_summary: {
        type: 'string',
        title: 'Address'
      },
      appointment_type_id: {
        type: 'enum',
        title: 'Special Type',
        help: 'Identified whether this is a special appointment type.  If blank then it is considered ' +
              'a "regular" appointment.'
      },
      state: {
        type: 'string',
        title: 'State',
        help: 'One of completed, upcoming or passed'
      }
    },
    defaultOrder: {
      column: 'datetime',
      reverse: true
    }
  } );

  module.addInputGroup( null, {
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
    }
  } );

  // add an extra operation for home and site appointment types
  module.addExtraOperation( 'calendar', {
    id: 'home-appointment-button',
    title: 'Home Appointment',
    operation: function( $state, model ) {
      $state.go( 'appointment.calendar', { identifier: model.site.getIdentifier() + ';type=home' } );
    },
    classes: 'home-appointment-button'
  } );

  module.addExtraOperation( 'calendar', {
    id: 'site-appointment-button',
    title: 'Site Appointment',
    operation: function( $state, model ) {
      $state.go( 'appointment.calendar', { identifier: model.site.getIdentifier() + ';type=site' } );
    },
    classes: 'site-appointment-button'
  } );

  module.addExtraOperation( 'view', {
    title: 'Appointment Calendar',
    operation: function( $state, model ) {
      $state.go( 'appointment.calendar', { identifier: model.site.getIdentifier() + ';type=' + model.type } );
    }
  } );

  // converts appointments into events
  function getEventFromAppointment( appointment, timezone, duration ) {
    if( angular.isDefined( appointment.start ) && angular.isDefined( appointment.end ) ) {
      return appointment;
    } else {
      var date = moment( appointment.datetime );
      var offset = moment.tz.zone( timezone ).offset( date.unix() );

      // adjust to/from daylight saving time
      var isNowDST = moment().tz( timezone ).isDST();
      var isDST = date.tz( timezone ).isDST();
      if( isNowDST != isDST ) offset += ( isNowDST ? 1 : -1 ) * 60;
      
      var event = {
        getIdentifier: function() { return appointment.getIdentifier() },
        title: ( appointment.uid ? appointment.uid : 'new appointment' ) +
               ( appointment.username ? ' (' + appointment.username + ')' : '' ),
        start: moment( appointment.datetime ).subtract( offset, 'minutes' ),
        end: moment( appointment.datetime ).subtract( offset, 'minutes' ).add( duration, 'minute' ),
        color: appointment.color
      };
      return event;
    }
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', [
    'CnAppointmentModelFactory', 'CnSession', 'CnHttpFactory',
    function( CnAppointmentModelFactory, CnSession, CnHttpFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          $scope.loading = true;
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
          $scope.model.addModel.afterNew( function() {
            // get a home or site calendar, based on the appointment's parent
            var identifier = $scope.model.getParentIdentifier();
            CnHttpFactory.instance( {
              path: identifier.subject + '/' + identifier.identifier,
              data: { select: { column: [ 'qnaire_id', { table: 'qnaire', column: 'type' } ] } }
            } ).get().then( function( response ) {
              var type = response.data.type;
              var cnRecordAddScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordAdd' );
              if( !cnRecordAddScope ) throw new Exception( 'Cannot find cnRecordAdd scope' );

              cnRecordAddScope.heading =
                cnRecordAddScope.heading.replace( 'Appointment', type.ucWords() + ' Appointment' );
              // Create a reserved data array object to temporarily store the address and user columns
              // for when we're adding a site-qnaire appointment
              if( angular.isUndefined( cnRecordAddScope.reservedDataArray ) )
                cnRecordAddScope.reservedDataArray = {};
              var reservedDataArray = cnRecordAddScope;
              var dataArray = cnRecordAddScope.dataArray;
              var record = cnRecordAddScope.record;
              var formattedRecord = cnRecordAddScope.formattedRecord;

              // make sure the metadata has been created
              $scope.model.metadata.getPromise().then( function() {
                var datetimeIndex = dataArray.findIndexByProperty( 'key', 'datetime' );
                if( 'home' == type ) {
                  var userIndex = dataArray.findIndexByProperty( 'key', 'user_id' );
                  if( null == userIndex )
                    dataArray.splice( datetimeIndex + 1, 0, reservedDataArray.user_id );
                  var addressIndex = dataArray.findIndexByProperty( 'key', 'address_id' );
                  if( null == addressIndex )
                    dataArray.splice( datetimeIndex + 1, 0, reservedDataArray.address_id );
                } else { // 'site' == type
                  var userIndex = dataArray.findIndexByProperty( 'key', 'user_id' );
                  if( null != userIndex ) {
                    reservedDataArray.user_id = dataArray[userIndex];
                    dataArray.splice( userIndex, 1 );
                  }
                  var addressIndex = dataArray.findIndexByProperty( 'key', 'address_id' );
                  if( null != addressIndex ) {
                    reservedDataArray.address_id = dataArray[addressIndex];
                    dataArray.splice( addressIndex, 1 );
                  }
                }

                // set the appointment type enum list based on the qnaire_id
                var appointmentTypeIndex = dataArray.findIndexByProperty( 'key', 'appointment_type_id' );
                dataArray[appointmentTypeIndex].enumList = 
                  $scope.model.metadata.columnList.appointment_type_id.qnaireList[response.data.qnaire_id];
              } );

              // automatically fill in the current user as the interviewer (or null if this is a site appointment)
              formattedRecord.user_id = 'home' == type ?
                CnSession.user.firstName + ' ' + CnSession.user.lastName + ' (' + CnSession.user.name + ')' : null;
              record.user_id = 'home' == type ? CnSession.user.id : null;

              // connect the calendar's day click callback to the appointment's datetime
              $scope.appointmentModel = CnAppointmentModelFactory.forSite( $scope.model.site, type );
              $scope.appointmentModel.calendarModel.settings.dayClick = function( date, event, view ) {
                // make sure date is no earlier than today
                var today = moment().hour( moment().hour() + 1 ).minute( 0 ).second( 0 );

                // set the timezone
                today.tz( CnSession.user.timezone );
                date.tz( CnSession.user.timezone );

                // full-calendar has a bug where it picks one day behind the actual day, so we adjust for it here
                if( !date.isBefore( today, 'day' ) ) {
                  var datetime = date.isAfter( today )
                               ? moment( date ).hour( 12 ).minute( 0 ).second( 0 )
                               : today;
                  if( 'month' == view.type ) datetime.add( 1, 'days' );

                  record.datetime = datetime.format();
                  formattedRecord.datetime = CnSession.formatValue( datetime, 'datetime', true );
                  $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                }
              };

              $scope.loading = false;
            } );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentCalendar', [
    'CnAppointmentModelFactory', 'CnSession', '$timeout',
    function( CnAppointmentModelFactory, CnSession, $timeout ) {
      return {
        templateUrl: module.getFileUrl( 'calendar.tpl.html' ),
        restrict: 'E',
        scope: {
          model: '=?',
          preventSiteChange: '@'
        },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
          console.log( $scope.model.type );
          $scope.heading = $scope.model.site.name.ucWords() + ' - '
                         + ( 'home' == $scope.model.type && 1 == CnSession.role.tier ? 'Personal ' : '' )
                         + $scope.model.type.ucWords() + ' Appointment Calendar';
        },
        link: function( scope, element ) {
          // synchronize appointment-based calendars
          var homeCalendarModel =
            CnAppointmentModelFactory.forSite( scope.model.site, 'home' ).calendarModel;
          var siteCalendarModel =
            CnAppointmentModelFactory.forSite( scope.model.site, 'site' ).calendarModel;

          scope.$watch( 'model.calendarModel.currentDate', function( date ) {
            if( !homeCalendarModel.currentDate.isSame( date, 'day' ) ) homeCalendarModel.currentDate = date;
            if( !siteCalendarModel.currentDate.isSame( date, 'day' ) ) siteCalendarModel.currentDate = date;
          } );
          scope.$watch( 'model.calendarModel.currentView', function( view ) {
            if( homeCalendarModel.currentView != view ) homeCalendarModel.currentView = view;
            if( siteCalendarModel.currentView != view ) siteCalendarModel.currentView = view;
          } );

          // highlight the calendar button that we're currently viewing
          $timeout( function() {
            var homeButton = element.find( '#home-appointment-button' );
            homeButton.addClass( 'home' == scope.model.type ? 'btn-warning' : 'btn-default' );
            homeButton.removeClass( 'home' == scope.model.type ? 'btn-default' : 'btn-warning' );

            var siteButton = element.find( '#site-appointment-button' );
            siteButton.addClass( 'site' == scope.model.type ? 'btn-warning' : 'btn-default' );
            siteButton.removeClass( 'site' == scope.model.type ? 'btn-default' : 'btn-warning' );
          }, 200 );
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
        controller: function( $scope ) {
          $scope.loading = true;
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
          $scope.model.viewModel.afterView( function() {
            // get a home or site calendar, based on the appointment's parent
            var identifier = $scope.model.getParentIdentifier();
            CnHttpFactory.instance( {
              path: identifier.subject + '/' + identifier.identifier,
              data: { select: { column: [ 'qnaire_id', { table: 'qnaire', column: 'type' } ] } }
            } ).get().then( function( response ) {
              var type = response.data.type;
              var cnRecordViewScope = cenozo.findChildDirectiveScope( $scope, 'cnRecordView' );
              if( !cnRecordViewScope ) throw new Exception( 'Cannot find cnRecordView scope' );

              cnRecordViewScope.heading =
                cnRecordViewScope.heading.replace( 'Appointment', type.ucWords() + ' Appointment' );
              // Create a reserved data array object to temporarily store the address and user columns
              // for when we're viewing a site-qnaire appointment
              if( angular.isUndefined( cnRecordViewScope.reservedDataArray ) )
                cnRecordViewScope.reservedDataArray = {};
              var reservedDataArray = cnRecordViewScope;
              var dataArray = cnRecordViewScope.dataArray[0].inputList;
              var record = $scope.model.viewModel.record;
              var formattedRecord = $scope.model.viewModel.formattedRecord;

              // make sure the metadata has been created
              $scope.model.metadata.getPromise().then( function() {
                var datetimeIndex = dataArray.findIndexByProperty( 'key', 'datetime' );
                if( 'home' == type ) {
                  var userIndex = dataArray.findIndexByProperty( 'key', 'user_id' );
                  if( null == userIndex )
                    dataArray.splice( datetimeIndex + 1, 0, reservedDataArray.user_id );
                  var addressIndex = dataArray.findIndexByProperty( 'key', 'address_id' );
                  if( null == addressIndex )
                    dataArray.splice( datetimeIndex + 1, 0, reservedDataArray.address_id );
                } else { // 'site' == type
                  var userIndex = dataArray.findIndexByProperty( 'key', 'user_id' );
                  if( null != userIndex ) {
                    reservedDataArray.user_id = dataArray[userIndex];
                    dataArray.splice( userIndex, 1 );
                  }
                  var addressIndex = dataArray.findIndexByProperty( 'key', 'address_id' );
                  if( null != addressIndex ) {
                    reservedDataArray.address_id = dataArray[addressIndex];
                    dataArray.splice( addressIndex, 1 );
                  }
                }

                // set the appointment type enum list based on the qnaire_id
                var appointmentTypeIndex = dataArray.findIndexByProperty( 'key', 'appointment_type_id' );
                dataArray[appointmentTypeIndex].enumList = 
                  $scope.model.metadata.columnList.appointment_type_id.qnaireList[response.data.qnaire_id];
              } );

              // automatically fill in the current user as the interviewer (or null if this is a site appointment)
              formattedRecord.user_id = 'home' == type ?
                CnSession.user.firstName + ' ' + CnSession.user.lastName + ' (' + CnSession.user.name + ')' : null;
              record.user_id = 'home' == type ? CnSession.user.id : null;

              // connect the calendar's day click callback to the appointment's datetime
              $scope.appointmentModel = CnAppointmentModelFactory.forSite( $scope.model.site, type );
              $scope.appointmentModel.calendarModel.settings.dayClick = function( date, event, view ) {
                // make sure date is no earlier than today
                var today = moment().hour( moment().hour() + 1 ).minute( 0 ).second( 0 );

                // set the timezone
                today.tz( CnSession.user.timezone );
                date.tz( CnSession.user.timezone );

                // full-calendar has a bug where it picks one day behind the actual day, so we adjust for it here
                if( !date.isBefore( today, 'day' ) ) {
                  var datetime = date.isAfter( today )
                               ? moment( date ).hour( 12 ).minute( 0 ).second( 0 )
                               : today;
                  if( 'month' == view.type ) datetime.add( 1, 'days' );

                  record.datetime = datetime.format();
                  formattedRecord.datetime = CnSession.formatValue( datetime, 'datetime', true );
                  $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
                }
              };

              $scope.loading = false;
            } );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentAddFactory', [
    'CnBaseAddFactory', 'CnSession',
    function( CnBaseAddFactory, CnSession ) {
      var object = function( parentModel ) {
        var self = this;

        CnBaseAddFactory.construct( this, parentModel );

        // add the new appointment's events to the calendar cache
        this.onAdd = function( record ) {
          return this.$$onAdd( record ).then( function() {
            var duration = 'long' == record.type
                         ? CnSession.setting.longAppointment
                         : CnSession.setting.shortAppointment;
            record.getIdentifier = function() { return parentModel.getIdentifierFromRecord( record ); };
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache.push(
              getEventFromAppointment( record, CnSession.user.timezone, duration )
            );
          } );
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

        // remove day click callback
        delete this.settings.dayClick;

        // extend onCalendar to transform templates into events
        this.onCalendar = function( replace, minDate, maxDate, ignoreParent ) {
          // we must get the load dates before calling $$onCalendar
          var loadMinDate = self.getLoadMinDate( replace, minDate );
          var loadMaxDate = self.getLoadMaxDate( replace, maxDate );
          return self.$$onCalendar( replace, minDate, maxDate, true ).then( function() {
            self.cache.forEach( function( item, index, array ) {
              var duration = 'long' == item.type
                           ? CnSession.setting.longAppointment
                           : CnSession.setting.shortAppointment;
              array[index] = getEventFromAppointment( item, CnSession.user.timezone, duration );
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
            self.parentModel.enableAdd( 0 == self.total );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentViewFactory', [
    'CnBaseViewFactory', 'CnSession',
    function( CnBaseViewFactory, CnSession ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // remove the deleted appointment's events from the calendar cache
        this.onDelete = function() {
          return this.$$onDelete().then( function() {
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
          } );
        };

        // remove and re-add the appointment's events from the calendar cache
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            var minDate = parentModel.calendarModel.cacheMinDate;
            var maxDate = parentModel.calendarModel.cacheMaxDate;
            parentModel.calendarModel.cache = parentModel.calendarModel.cache.filter( function( e ) {
              return e.getIdentifier() != self.record.getIdentifier();
            } );
            parentModel.calendarModel.cache.push(
              getEventFromAppointment( self.record, CnSession.user.timezone )
            );
          } );
        };

        this.onView = function() {
          return this.$$onView().then( function() {
            var upcoming = moment().isBefore( self.record.datetime, 'minute' );
            parentModel.enableDelete( upcoming );
            parentModel.enableEdit( upcoming );
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
    'CnSession', 'CnHttpFactory', '$q', '$state',
    function( CnBaseModelFactory,
              CnAppointmentAddFactory, CnAppointmentCalendarFactory,
              CnAppointmentListFactory, CnAppointmentViewFactory,
              CnSession, CnHttpFactory, $q, $state ) {
      var object = function( site, type ) {
        if( !angular.isObject( site ) || angular.isUndefined( site.id ) )
          throw new Error( 'Tried to create CnAppointmentModel without specifying the site.' );
        if( !angular.isString( type ) || 0 == type.length )
          throw new Error( 'Tried to create CnAppointmentModel without specifying the type.' );

        var self = this;

        CnBaseModelFactory.construct( this, module );
        this.addModel = CnAppointmentAddFactory.instance( this );
        this.calendarModel = CnAppointmentCalendarFactory.instance( this );
        this.listModel = CnAppointmentListFactory.instance( this );
        this.viewModel = CnAppointmentViewFactory.instance( this, site.id == CnSession.site.id );
        this.site = site;
        this.type = type;

        // customize service data
        this.getServiceData = function( type, columnRestrictLists ) {
          var data = this.$$getServiceData( type, columnRestrictLists );
          if( 'calendar' == type ) {
            data.restricted_site_id = self.site.id;
            data.type = self.type;
            data.select = { column: [ 'datetime', {
              table: 'appointment_type',
              column: 'color'
            } ] };
          }
          return data;
        };

        // extend getMetadata
        this.getMetadata = function() {
          var promiseList = [
            this.$$getMetadata(),
            CnHttpFactory.instance( {
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
            } )
          ];

          var parent = this.getParentIdentifier();
          if( angular.isDefined( parent.subject ) && angular.isDefined( parent.identifier ) ) {
            promiseList.push(
              CnHttpFactory.instance( {
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
                  self.metadata.columnList.address_id.enumList = [];
                  response.data.forEach( function( item ) {
                    self.metadata.columnList.address_id.enumList.push( {
                      value: item.id,
                      name: item.summary
                    } );
                  } );
                } );
              } )
            );
          }

          return $q.all( promiseList );
        };
      };

      // get the siteColumn to be used by a site's identifier
      var siteModule = cenozoApp.module( 'site' );
      var siteColumn = angular.isDefined( siteModule.identifier.column ) ? siteModule.identifier.column : 'id';

      return {
        siteInstanceList: {},
        forSite: function( site, type ) {
          if( !angular.isObject( site ) ) {
            $state.go( 'error.404' );
            throw new Error( 'Cannot find site matching identifier "' + site + '", redirecting to 404.' );
          }
          if( angular.isUndefined( this.siteInstanceList[site.id] ) ) this.siteInstanceList[site.id] = {};
          if( angular.isUndefined( this.siteInstanceList[site.id][type] ) ) {
            if( angular.isUndefined( site.getIdentifier ) )
              site.getIdentifier = function() { return siteColumn + '=' + this[siteColumn]; };
            this.siteInstanceList[site.id][type] = new object( site, type );
          }
          return this.siteInstanceList[site.id][type];
        },
        instance: function() {
          var site = null;
          var type = null;
          if( 'calendar' == $state.current.name.split( '.' )[1] ) {
            $state.params.identifier.split( ';' ).forEach( function( identifier ) {
              var parts = identifier.split( '=' );
              if( 1 == parts.length && parseInt( parts[0] ) == parts[0] ) {
                // int site identifier
                site = CnSession.siteList.findByProperty( 'id', parseInt( parts[0] ) );
              } else if( 2 == parts.length ) {
                // key=val identifier
                if( 'name' == parts[0] ) {
                  site = CnSession.siteList.findByProperty( parts[0], parts[1] );
                } else if( 'type' == parts[0] ) {
                  type = parts[1];
                }
              }
            } );
          } else {
            site = CnSession.site;
            type = 'site';
          }
          return this.forSite( site, type );
        }
      };
    }
  ] );

} );
