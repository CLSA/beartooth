define( function() {
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
      phone: {
        column: 'phone.name',
        type: 'string',
        title: 'Number'
      },
      user: {
        column: 'user.name',
        type: 'string',
        title: 'Reserved For'
      },
      address: {
        column: 'address.rank',
        type: 'string',
        title: 'Address (TODO)'
      },
      appointment_type_id: {
        type: 'enum',
        title: 'Special Type'
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
      column: 'script.name',
      title: 'Questionnaire',
      type: 'string',
      exclude: 'add',
      constant: true
    },
    phone_id: {
      title: 'Phone Number',
      type: 'enum',
      help: 'Which number should be called for the appointment, or leave this field blank if any of the ' +
            'participant\'s phone numbers can be called.'
    },
    user_id: {
      column: 'appointment.user_id',
      title: 'Reserved for',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( first_name, " ", last_name, " (", name, ")" )',
        where: [ 'first_name', 'last_name', 'name' ]
      },
      help: 'The user the appointment is specifically reserved for. ' +
            'Cannot be changed once the appointment has passed.'
    },
    state: {
      title: 'State',
      type: 'string',
      exclude: 'add',
      constant: true,
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    },
    type: {
      title: 'Type',
      type: 'enum'
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
        title: ( angular.isDefined( appointment.uid ) ? appointment.uid : 'new appointment' ) +
               ( angular.isDefined( appointment.qnaire_rank ) ? ' (' + appointment.qnaire_rank + ')' : '' ),
        start: moment( appointment.datetime ).subtract( offset, 'minutes' ),
        end: moment( appointment.datetime ).subtract( offset, 'minutes' ).add( duration, 'minute' )
      };
      return event;
    }
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', [
    'CnAppointmentModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
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
          $scope.heading = $scope.model.site.name.ucWords() + ' Appointment Calendar';
        },
        link: function( scope, element ) {
          // add the calendar type to the heading
          scope.heading = scope.heading.replace(
            'Appointment', '- ' + scope.model.type.ucWords() + ' Appointment' );

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
    'CnAppointmentModelFactory', 'CnSession',
    function( CnAppointmentModelFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentModelFactory.instance();
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
          return self.$$onCalendar( replace, minDate, maxDate, ignoreParent ).then( function() {
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
          }
          return data;
        };

        // extend getMetadata
        this.getMetadata = function() {
          var promiseList = [ this.$$getMetadata() ];

          var parent = this.getParentIdentifier();
          if( angular.isDefined( parent.subject ) && angular.isDefined( parent.identifier ) ) {
            promiseList.push(
              CnHttpFactory.instance( {
                path: [ parent.subject, parent.identifier ].join( '/' ),
                data: { select: { column: { column: 'participant_id' } } }
              } ).query().then( function( response ) {
                // get the participant's effective site and list of phone numbers
                return CnHttpFactory.instance( {
                  path: ['participant', response.data.participant_id, 'phone' ].join( '/' ),
                  data: {
                    select: { column: [ 'id', 'rank', 'type', 'number' ] },
                    modifier: { order: { rank: false } }
                  }
                } ).query().then( function( response ) {
                  self.metadata.columnList.phone_id.enumList = [];
                  response.data.forEach( function( item ) {
                    self.metadata.columnList.phone_id.enumList.push( {
                      value: item.id,
                      name: '(' + item.rank + ') ' + item.type + ': ' + item.number
                    } );
                  } );
                } );
              } )
            );
          }

          return $q.all( promiseList );
        };
      };

      return {
        siteInstanceList: {},
        forSite: function( site, type ) {
          if( !angular.isObject( site ) ) {
            $state.go( 'error.404' );
            throw new Error( 'Cannot find site matching identifier "' + site + '", redirecting to 404.' );
          }
          if( angular.isUndefined( this.siteInstanceList[site.id] ) ) this.siteInstanceList[site.id] = {};
          if( angular.isUndefined( this.siteInstanceList[site.id][type] ) )
            this.siteInstanceList[site.id][type] = new object( site, type );
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
          }
          return this.forSite( site, type );
        }
      };
    }
  ] );

} );
