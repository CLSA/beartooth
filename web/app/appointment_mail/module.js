define( [ 'trace' ].reduce( function( list, name ) {
  return list.concat( cenozoApp.module( name ).getRequiredFiles() );
}, [] ), function() {
  'use strict';

  try { var module = cenozoApp.module( 'appointment_mail', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'site',
        column: 'site.name'
      }
    },
    name: {
      singular: 'appointment mail template',
      plural: 'appointment mail templates',
      possessive: 'appointment mail template\'s'
    },
    columnList: {
      site: {
        column: 'site.name',
        title: 'Site'
      },
      qnaire: {
        column: 'qnaire.type',
        title: 'Questionnaire'
      },
      appointment_type: {
        column: 'appointment_type.name',
        title: 'Special Type'
      },
      language: {
        column: 'language.name',
        title: 'Language'
      },
      delay: {
        title: 'Delay (days)'
      },
      subject: {
        title: 'Subject'
      }
    },
    defaultOrder: {
      column: 'delay',
      reverse: false
    }
  } );

  module.addInputGroup( '', {
    site_id: {
      title: 'Site',
      type: 'enum',
      isExcluded: function( $state, model ) { return model.hasAllSites() ? 'add' : true; },
      isConstant: 'view'
    },
    qnaire_id: {
      title: 'Questionnaire',
      type: 'enum',
      isConstant: 'view'
    },
    appointment_type_id: {
      title: 'Special Type',
      type: 'enum',
      isConstant: 'view',
      isExcluded: function( $state, model ) {
        return angular.isUndefined( model.metadata.columnList ) ||
               angular.isUndefined( model.metadata.columnList.appointment_type_id ) ||
               angular.isUndefined( model.metadata.columnList.appointment_type_id.qnaireList ) ||
               0 == Object.keys( model.metadata.columnList.appointment_type_id.qnaireList ).length;
      }
    },
    language_id: {
      title: 'Language',
      type: 'enum',
      isConstant: 'view'
    },
    from_name: {
      title: 'From Name',
      type: 'string'
    },
    from_address: {
      title: 'From Address',
      type: 'string',
      format: 'appointment_mail',
      help: 'Must be in the format "account@domain.name".'
    },
    cc_address: {
      title: 'Carbon Copy (CC)',
      type: 'string',
      help: 'May be a comma-delimited list of appointment_mail addresses in the format "account@domain.name".'
    },
    bcc_address: {
      title: 'Blind Carbon Copy (BCC)',
      type: 'string',
      help: 'May be a comma-delimited list of appointment_mail addresses in the format "account@domain.name".'
    },
    delay: {
      title: 'Delay (days)',
      type: 'string',
      format: 'integer'
    },
    subject: {
      title: 'Subject',
      type: 'string'
    },
    body: {
      title: 'Body',
      type: 'text'
    }
  } );

  module.addExtraOperation( 'view', {
    title: 'Preview',
    operation: function( $state, model ) { model.viewModel.preview(); }
  } );

  module.addExtraOperation( 'view', {
    title: 'Validate',
    operation: function( $state, model ) { model.viewModel.validate(); }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentMailAdd', [
    'CnAppointmentMailModelFactory',
    function( CnAppointmentMailModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'add.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentMailModelFactory.root;

          // get the child cn-record-add's scope
          $scope.$on( 'cnRecordAdd ready', function( event, data ) {
            $scope.model.metadata.getPromise().then( function() {
              var cnRecordAddScope = data;
              var inputArray = cnRecordAddScope.dataArray[0].inputArray;
              var appointmentTypeIndex = inputArray.findIndexByProperty( 'key', 'appointment_type_id' );

              // always start with an empty appointment type list
              $scope.record = {};
              $scope.model.addModel.onNew( $scope.record ).then( function() {
                inputArray[appointmentTypeIndex].enumList = [ { value: undefined, name: '(empty)' } ];
              } );

              // Override the check function so that the appointment type list can be updated based on which qnaire
              // has been selected
              var checkFunction = cnRecordAddScope.check;
              cnRecordAddScope.check = function( property ) {
                // run the original check function first
                checkFunction( property );

                if( 'qnaire_id' == property ) {
                  // reset the selected appointment type
                  cnRecordAddScope.record.appointment_type_id = undefined;

                  if( 0 < Object.keys( $scope.model.metadata.columnList.appointment_type_id.qnaireList ).length ) {
                    // set the appointment type enum list based on the qnaire_id
                    inputArray[appointmentTypeIndex].enumList = [];
                    if( cnRecordAddScope.record.qnaire_id ) {
                      inputArray[appointmentTypeIndex].enumList = angular.copy(
                        $scope.model.metadata.columnList.appointment_type_id.qnaireList[cnRecordAddScope.record.qnaire_id]
                      );
                    }
                    inputArray[appointmentTypeIndex].enumList.unshift( { value: undefined, name: '(empty)' } );
                  }
                }
              };
            } );
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentMailList', [
    'CnAppointmentMailModelFactory',
    function( CnAppointmentMailModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentMailModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentMailView', [
    'CnAppointmentMailModelFactory',
    function( CnAppointmentMailModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnAppointmentMailModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailAddFactory', [
    'CnBaseAddFactory', 'CnHttpFactory',
    function( CnBaseAddFactory, CnHttpFactory ) {
      var object = function( parentModel ) {
        var self = this;
        CnBaseAddFactory.construct( this, parentModel );

        this.onNew = function( record ) {
          return this.$$onNew( record ).then( function() {
            var parent = self.parentModel.getParentIdentifier();
            return CnHttpFactory.instance( {
              path: 'application/0',
              data: { select: { column: [ 'mail_name', 'mail_address' ] } }
            } ).get().then( function( response ) {
              record.from_name = response.data.mail_name;
              record.from_address = response.data.mail_address;
            } );
          } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailViewFactory', [
    'CnBaseViewFactory', 'CnSession', 'CnHttpFactory', 'CnModalMessageFactory',
    function( CnBaseViewFactory, CnSession, CnHttpFactory, CnModalMessageFactory ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        this.preview = function() {
          return CnHttpFactory.instance( {
            path: 'application/' + CnSession.application.id,
            data: { select: { column: [ 'mail_header', 'mail_footer' ] } }
          } ).get().then( function( response ) {
            var body = self.record.body;
            if( null != response.data.mail_header ) body = response.data.mail_header + "\n" + body;
            if( null != response.data.mail_footer ) body = body + "\n" + response.data.mail_footer;
            return CnModalMessageFactory.instance( {
              title: 'Mail Preview',
              message: body,
              html: true
            } ).show();
          } );
        };

        this.validate = function() {
          return CnHttpFactory.instance( {
            path: this.parentModel.getServiceResourcePath(),
            data: { select: { column: 'validate' } }
          } ).get().then( function( response ) {
            var result = JSON.parse( response.data.validate );

            var message = 'The subject contains ';
            message += null == result || angular.isUndefined( result.subject )
                     ? 'no errors.\n'
                     : 'the invalid variable $' + result.subject + '$.';

            message += 'The body contains ';
            message += null == result || angular.isUndefined( result.body )
                     ? 'no errors.\n'
                     : 'the invalid variable $' + result.body + '$.';

            return CnModalMessageFactory.instance( { title: 'Validation Result', message: message } ).show();
          } );
        };
      };
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentMailModelFactory', [
    'CnBaseModelFactory', 'CnAppointmentMailListFactory', 'CnAppointmentMailAddFactory', 'CnAppointmentMailViewFactory',
    'CnSession', 'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnAppointmentMailListFactory, CnAppointmentMailAddFactory, CnAppointmentMailViewFactory,
              CnSession, CnHttpFactory, $q ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnAppointmentMailAddFactory.instance( this );
        this.listModel = CnAppointmentMailListFactory.instance( this );
        this.viewModel = CnAppointmentMailViewFactory.instance( this, root );

        this.hasAllSites = function() { return CnSession.role.allSites; };

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
            return $q.all( [

              CnHttpFactory.instance( {
                path: 'site',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: 'name', limit: 1000 }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.site_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.site_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: [ 'id', 'type' ] },
                  modifier: { order: 'rank', limit: 1000 }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.qnaire_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.qnaire_id.enumList.push( { value: item.id, name: item.type } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'appointment_type',
                data: {
                  select: { column: [ 'id', 'name', 'qnaire_id' ] },
                  modifier: { order: 'name', limit: 1000 }
                }
              } ).query().then( function success( response ) {
                // store the appointment types in a special array with qnaire_id as indices
                self.metadata.columnList.appointment_type_id.enumList = [ { value: '', name: '(empty)' } ];
                var qnaireList = {};
                response.data.forEach( function( item ) {
                  self.metadata.columnList.appointment_type_id.enumList.push( { value: item.id, name: item.name } );

                  if( angular.isUndefined( qnaireList[item.qnaire_id] ) ) qnaireList[item.qnaire_id] = [];
                  qnaireList[item.qnaire_id].push( { value: item.id, name: item.name } );
                } );
                self.metadata.columnList.appointment_type_id.qnaireList = qnaireList;
              } ),

              CnHttpFactory.instance( {
                path: 'language',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: {
                    where: { column: 'active', operator: '=', value: true },
                    order: 'name',
                    limit: 1000
                  }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.language_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.language_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } )

            ] );
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
