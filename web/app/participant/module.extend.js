// extend the framework's module
define( [ cenozoApp.module( 'participant' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'participant' );
  module.addInputGroup( 'Next of Kin', {
    next_of_kin_first_name: {
      column: 'next_of_kin.first_name',
      title: 'First Name',
      type: 'string'
    },
    next_of_kin_last_name: {
      column: 'next_of_kin.last_name',
      title: 'Last Name',
      type: 'string'
    },
    next_of_kin_gender: {
      column: 'next_of_kin.gender',
      title: 'Sex',
      type: 'string'
    },
    next_of_kin_phone: {
      column: 'next_of_kin.phone',
      title: 'Phone',
      type: 'string'
    },
    next_of_kin_street: {
      column: 'next_of_kin.street',
      title: 'Address',
      type: 'string'
    },
    next_of_kin_city: {
      column: 'next_of_kin.city',
      title: 'City',
      type: 'string'
    },
    next_of_kin_province: {
      column: 'next_of_kin.province',
      title: 'Region',
      type: 'string'
    },
    next_of_kin_postal_code: {
      column: 'next_of_kin.postal_code',
      title: 'Postcode',
      type: 'string'
    }
  } );

  module.addInputGroup( 'Queue Details', {
    title: {
      title: 'Current Questionnaire',
      column: 'qnaire.title',
      type: 'string',
      constant: true
    },
    start_date: {
      title: 'Delayed Until',
      column: 'qnaire.start_date',
      type: 'date',
      constant: true,
      help: 'If not empty then the participant will not be permitted to begin this questionnaire until the ' +
            'date shown is reached.'
    },
    queue: {
      title: 'Current Queue',
      column: 'queue.name',
      type: 'string',
      constant: true
    },
    override_quota: {
      title: 'Override Quota',
      type: 'boolean'
    }
  } );

  angular.extend( module.historyCategoryList, {

    // appointments are added in the assignment's promise function below
    Appointment: { active: true },

    Assignment: {
      active: true,
      promise: function( historyList, $state, CnHttpFactory, $q ) {
        return CnHttpFactory.instance( {
          path: 'participant/' + $state.params.identifier + '/interview',
          data: {
            modifier: { order: { start_datetime: true } },
            select: { column: [ 'id' ] }
          }
        } ).query().then( function( response ) {
          var promiseArray = [];
          response.data.forEach( function( item ) {
            // appointments
            promiseArray.push(
              CnHttpFactory.instance( {
                path: 'interview/' + item.id + '/appointment',
                data: {
                  modifier: { order: { start_datetime: true } },
                  select: {
                    column: [ 'datetime', 'address_id', 'outcome', {
                      table: 'user',
                      column: 'first_name',
                      alias: 'user_first'
                    }, {
                      table: 'user',
                      column: 'last_name',
                      alias: 'user_last'
                    }, {
                      table: 'appointment_type',
                      column: 'name',
                      alias: 'type'
                    } ]
                  }
                }
              } ).query().then( function( response ) {
                response.data.forEach( function( item ) {
                  var title = 'a ' + ( null == item.type ? 'regular' : item.type ) + ' '
                            + ( null == item.address_id ? 'site' : 'home' ) + ' appointment'
                            + ( null == item.address_id ? '' : ' with ' + item.user_first + ' ' + item.user_last );
                  var description = 'A ' + ( null == item.address_id ? 'site' : 'home' )
                                  + ' appointment scheduled for this time has ';
                  if( 'completed' == item.outcome ) description += 'been met.';
                  else if( 'cancelled' == item.outcome ) description += 'been cancelled.';
                  else description += 'not been met.';

                  historyList.push( {
                    datetime: item.datetime,
                    category: 'Appointment',
                    title: title,
                    description: description
                  } );
                } );
              } )
            );

            // assignments
            promiseArray.push(
              CnHttpFactory.instance( {
                path: 'interview/' + item.id + '/assignment',
                data: {
                  modifier: { order: { start_datetime: true } },
                  select: {
                    column: [ 'start_datetime', 'end_datetime', {
                      table: 'user',
                      column: 'first_name',
                      alias: 'user_first'
                    }, {
                      table: 'user',
                      column: 'last_name',
                      alias: 'user_last'
                    }, {
                      table: 'site',
                      column: 'name',
                      alias: 'site'
                    }, {
                      table: 'qnaire',
                      column: 'name',
                      alias: 'qnaire'
                    }, {
                      table: 'queue',
                      column: 'name',
                      alias: 'queue'
                    } ]
                  }
                }
              } ).query().then( function( response ) {
                response.data.forEach( function( item ) {
                  if( null != item.start_datetime ) {
                    historyList.push( {
                      datetime: item.start_datetime,
                      category: 'Assignment',
                      title: 'started by ' + item.user_first + ' ' + item.user_last,
                      description: 'Started an assignment for the "' + item.qnaire + '" questionnaire.\n' +
                                   'Assigned from the ' + item.site + ' site ' +
                                   'from the "' + item.queue + '" queue.'
                    } );
                  }
                  if( null != item.end_datetime ) {
                    historyList.push( {
                      datetime: item.end_datetime,
                      category: 'Assignment',
                      title: 'completed by ' + item.user_first + ' ' + item.user_last,
                      description: 'Completed an assignment for the "' + item.qnaire + '" questionnaire.\n' +
                                   'Assigned from the ' + item.site + ' site ' +
                                   'from the "' + item.queue + '" queue.'
                    } );
                  }
                } );
              } )
            );

          } );
          return $q.all( promiseArray );
        } );
      }
    },

  } );


  // extend the list factory
  cenozo.providers.decorator( 'CnParticipantListFactory', [
    '$delegate', 'CnSession',
    function( $delegate, CnSession ) {
      var instance = $delegate.instance;
      $delegate.instance = function( parentModel ) {
        var object = instance( parentModel );
        if( 'interviewer' == CnSession.role.name ) object.heading = 'My Participant List';
        return object;
      };
      return $delegate;
    }
  ] );

  // extend the list factory
  cenozo.providers.decorator( 'CnParticipantModelFactory', [
    '$delegate', 'CnSession',
    function( $delegate, CnSession ) {
      // only allow tier-3 roles to override the quota
      $delegate.root.module.getInput( 'override_quota' ).constant = 3 > CnSession.role.tier;
      return $delegate;
    }
  ] );

} );
