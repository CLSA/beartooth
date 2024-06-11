cenozoApp.defineModule({
  name: "appointment",
  dependencies: "site",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: [
          {
            subject: "interview",
            column: "interview_id",
            friendly: "qnaire",
          },
          {
            subject: "participant",
            column: "participant.uid",
          },
        ],
      },
      name: {
        singular: "appointment",
        plural: "appointments",
        possessive: "appointment's",
      },
      columnList: {
        datetime: {
          type: "datetime",
          title: "Date & Time",
        },
        formatted_user_id: {
          type: "string",
          title: "Interviewer",
          isIncluded: function ($state, model) {
            return "home" == $state.params.type || "home" == model.type;
          },
        },
        address_summary: {
          type: "string",
          title: "Address",
          isIncluded: function ($state, model) {
            return "home" == $state.params.type || "home" == model.type;
          },
        },
        appointment_type_id: {
          column: "appointment_type.name",
          type: "string",
          title: "Special Type",
          help:
            "Identified whether this is a special appointment type.  If blank then it is considered " +
            'a "regular" appointment.',
        },
        state: {
          type: "string",
          title: "State",
          help: "Will either be completed, rescheduled, cancelled, upcoming or passed",
        },
      },
      defaultOrder: {
        column: "datetime",
        reverse: true,
      },
    });

    module.addInputGroup("", {
      datetime: {
        title: "Date & Time",
        type: "datetime",
        min: "after now",
        help: "Cannot be changed once the appointment has passed.",
      },
      participant: {
        column: "participant.uid",
        title: "Participant",
        type: "string",
        isExcluded: "add",
        isConstant: true,
      },
      qnaire: {
        column: "qnaire.name",
        title: "Questionnaire",
        type: "string",
        isExcluded: "add",
        isConstant: true,
      },
      user_id: {
        column: "appointment.user_id",
        title: "Interviewer",
        type: "lookup-typeahead",
        typeahead: {
          table: "user",
          select: 'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
          where: ["user.first_name", "user.last_name", "user.name"],
        },
        help: "The interviewer the appointment is to be scheduled with.",
      },
      address_id: {
        title: "Address",
        type: "hidden",
        help: "The address of the home appointment.",
      },
      state: {
        title: "State",
        type: "string",
        isExcluded: "add",
        isConstant: true,
        help: "One of upcoming, passed, completed or cancelled",
      },
      appointment_type_id: {
        title: "Special Type",
        type: "enum",
        isConstant: function($state, model) {
          if ("view" != model.getActionFromState()) return false;

          // constant if we can't edit or it isn't upcoming
          return !model.getEditEnabled() || "upcoming" != model.viewModel.record.state;
        },
        help:
          "Identified whether this is a special appointment type.  If blank then it is considered " +
          'a "regular" appointment.',
      },
      disable_mail: {
        title: "Disable Email Reminder(s)",
        type: "boolean",
        isExcluded: "view",
        help: "If selected then no automatic email reminders will be created for this appointment.",
      },
      qnaire_id: { column: "qnaire.id", type: "hidden" },
      language_id: { column: "participant.language_id", type: "hidden" },
      type: { column: "qnaire.type", type: "hidden" },
    });

    if (angular.isDefined(cenozoApp.module("participant").actions.notes)) {
      module.addExtraOperation("view", {
        title: "Notes",
        operation: async function ($state, model) {
          await $state.go("participant.notes", {
            identifier: "uid=" + model.viewModel.record.participant,
          });
        },
      });
    }

    // add an extra operation for home and site appointment types
    if (angular.isDefined(module.actions.calendar)) {
      module.addExtraOperation("calendar", {
        id: "home-appointment-button",
        title: "Home Appointment",
        operation: async function ($state, model) {
          await $state.go("appointment.calendar", {
            type: "home",
            identifier: model.site.getIdentifier(),
          });
        },
        classes: "home-appointment-button",
      });
    }

    if (angular.isDefined(module.actions.calendar)) {
      module.addExtraOperation("calendar", {
        id: "site-appointment-button",
        title: "Site Appointment",
        operation: async function ($state, model) {
          await $state.go("appointment.calendar", {
            type: "site",
            identifier: model.site.getIdentifier(),
          });
        },
        classes: "site-appointment-button",
      });
    }

    if (angular.isDefined(module.actions.calendar)) {
      module.addExtraOperation("view", {
        title: "Appointment Calendar",
        operation: async function ($state, model) {
          await $state.go("appointment.calendar", {
            type: model.type,
            identifier: model.site.getIdentifier(),
          });
        },
      });
    }

    module.addExtraOperation("view", {
      title: "Cancel Appointment",
      isDisabled: function ($state, model) {
        return "passed" != model.viewModel.record.state;
      },
      operation: async function ($state, model) {
        await model.viewModel.cancelAppointment();
        await model.reloadState(true);
      },
    });

    // converts appointments into events
    function getEventFromAppointment(appointment, timezone) {
      if (angular.isDefined(appointment.start) && angular.isDefined(appointment.end)) {
        return appointment;
      } else {
        var date = moment(appointment.datetime);
        var offset = moment.tz.zone(timezone).utcOffset(date.unix());

        // adjust the appointment for daylight savings time
        if (date.tz(timezone).isDST()) offset += -60;

        // get the identifier now and not in the getIdentifier() function below
        var identifier = appointment.getIdentifier();
        var event = {
          getIdentifier: function () {
            return identifier;
          },
          title:
            (appointment.uid ? appointment.uid : "new appointment") +
            (appointment.postcode ? " [" + appointment.postcode.substr(0, 3) + "]" : "") +
            (appointment.username ? " (" + appointment.username + ")" : ""),
          start: moment(appointment.datetime).subtract(offset, "minutes"),
          end: moment(appointment.datetime).subtract(offset, "minutes").add(appointment.duration, "minute"),
          color: appointment.color,
          help: appointment.help,
        };

        if (null != appointment.outcome) {
          if (["rescheduled", "cancelled"].includes(appointment.outcome)) {
            event.className = "calendar-event-cancelled";
          }
          event.textColor = "lightgray";
        }

        return event;
      }
    }

    // private function used to update the appointment type enum list (for both add and view directive)
    function updateAppointmentTypeEnumList (directive, input, qnaireList, qnaireId) {
      // set the appointment type enum list based on the qnaire_id
      input.enumList = angular.copy(qnaireList[qnaireId]);

      // we must also manually add the empty entry
      if (angular.isUndefined(input.enumList)) input.enumList = [];
      var emptyIndex = input.enumList.findIndexByProperty("name", "(empty)");
      if (null == emptyIndex) {
        input.enumList.unshift({ value: "add" == directive ? undefined : "", name: "(empty)" });
      }
    }

    /* ############################################################################################## */
    cenozo.providers.directive("cnAppointmentAdd", [
      "CnAppointmentModelFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalConfirmFactory",
      function (
        CnAppointmentModelFactory,
        CnSession,
        CnHttpFactory,
        CnModalConfirmFactory
      ) {
        return {
          templateUrl: module.getFileUrl("add.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: function ($scope) {
            if (angular.isUndefined($scope.model))
              $scope.model = CnAppointmentModelFactory.instance();

            // get the child cn-record-add's scope
            var cnRecordAddScope = null;
            $scope.$on("cnRecordAdd ready", function (event, data) {
              cnRecordAddScope = data;
            });

            // connect the calendar's day click callback to the appointment's datetime
            $scope.model.calendarModel.settings.dayClick = function (date) {
              // make sure date is no earlier than today
              if (!date.isBefore(moment(), "day")) {
                var dateString = date.format("YYYY-MM-DD") + "T12:00:00";
                var datetime = moment.tz(dateString, CnSession.user.timezone).tz("UTC");
                cnRecordAddScope.record.datetime = datetime.format();
                cnRecordAddScope.formattedRecord.datetime = CnSession.formatValue(datetime, "datetime", true);
                $scope.$apply(); // needed otherwise the new datetime takes seconds before it appears
              }
            };

            $scope.model.addModel.afterNew(async function () {
              // warn if old appointment will be cancelled
              cnRecordAddScope.baseSaveFn = cnRecordAddScope.save;
              cnRecordAddScope.save = async function () {
                var response = await CnHttpFactory.instance({
                  path: "interview/" + $scope.model.getParentIdentifier().identifier,
                  data: { select: { column: ["missed_appointment"] } },
                }).get();

                var proceed = true;
                if (response.data.missed_appointment) {
                  var proceed = await CnModalConfirmFactory.instance({
                    title: "Cancel Missed Appointment?",
                    message:
                      "There already exists a passed appointment for this interview, " +
                      "do you wish to cancel it and create a new one?",
                  }).show();
                }

                if (proceed) await cnRecordAddScope.baseSaveFn();
              };

              // make sure the metadata has been created
              await $scope.model.metadata.getPromise();

              $scope.model.addModel.heading = $scope.model.type.ucWords() + " Appointment Details";
              var inputArray = cnRecordAddScope.dataArray[0].inputArray;

              // show/hide user and address columns based on the type
              inputArray.findByProperty("key", "user_id").type =
                "home" == $scope.model.type ? "lookup-typeahead" : "hidden";
              inputArray.findByProperty("key", "address_id").type =
                "home" == $scope.model.type ? "enum" : "hidden";

              var identifier = $scope.model.getParentIdentifier();
              if (angular.isDefined(identifier.subject) && angular.isDefined(identifier.identifier)) {
                var response = await CnHttpFactory.instance({
                  path: identifier.subject + "/" + identifier.identifier,
                  data: { select: { column: ["qnaire_id"] } },
                }).get();

                // only show appointment types based on the qnaire
                updateAppointmentTypeEnumList(
                  "add",
                  inputArray.findByProperty("key", "appointment_type_id"),
                  $scope.model.metadata.columnList.appointment_type_id.qnaireList,
                  response.data.qnaire_id
                );
              }
            });
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.directive("cnAppointmentCalendar", [
      "CnAppointmentModelFactory",
      "CnSession",
      function (CnAppointmentModelFactory, CnSession) {
        return {
          templateUrl: module.getFileUrl("calendar.tpl.html"),
          restrict: "E",
          scope: {
            model: "=?",
            preventSiteChange: "@",
          },
          controller: function ($scope) {
            if (angular.isUndefined($scope.model)) $scope.model = CnAppointmentModelFactory.instance();
            $scope.model.calendarModel.heading =
              $scope.model.site.name.ucWords() +
              " - " +
              ("home" == $scope.model.type && "interviewer" == CnSession.role.name ? "Personal " : "") +
              $scope.model.type.ucWords() +
              " Appointment Calendar";
          },
          link: function (scope, element) {
            // highlight the calendar button that we're currently viewing
            var homeListener = scope.$watch(
              function () {
                return element.find("#home-appointment-button").length;
              },
              function (length) {
                if (0 < length) {
                  var homeButton = element.find("#home-appointment-button");
                  homeButton.addClass("home" == scope.model.type ? "btn-warning" : "btn-default");
                  homeButton.removeClass("home" == scope.model.type ? "btn-default" : "btn-warning");
                  homeListener(); // your watch has ended
                }
              }
            );

            var siteListener = scope.$watch(
              function () {
                return element.find("#site-appointment-button").length;
              },
              function (length) {
                if (0 < length) {
                  var siteButton = element.find("#site-appointment-button");
                  siteButton.addClass("site" == scope.model.type ? "btn-warning" : "btn-default");
                  siteButton.removeClass("site" == scope.model.type ? "btn-default" : "btn-warning");
                  siteListener(); // your watch has ended
                }
              }
            );
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.directive("cnAppointmentList", [
      "CnAppointmentModelFactory",
      function (CnAppointmentModelFactory) {
        return {
          templateUrl: module.getFileUrl("list.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: function ($scope) {
            // note that instead of attaching the model to the root we need to reference an instance instead
            if (angular.isUndefined($scope.model)) $scope.model = CnAppointmentModelFactory.instance();
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.directive("cnAppointmentView", [
      "CnAppointmentModelFactory",
      "CnModalMessageFactory",
      function (CnAppointmentModelFactory, CnModalMessageFactory) {
        return {
          templateUrl: module.getFileUrl("view.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: function ($scope, $element) {
            if (angular.isUndefined($scope.model)) $scope.model = CnAppointmentModelFactory.instance();

            // get the child cn-record-view's scope
            var cnRecordViewScope = null;
            $scope.$on("cnRecordView ready", function (event, data) {
              cnRecordViewScope = data;
            });

            $scope.model.calendarModel.settings.dayClick = async function (date) {
              if ($scope.model.getEditEnabled()) {
                await CnModalMessageFactory.instance({
                  title: "Notice",
                  message: 'Please use the "Date & Time" field above to change the appointment date.',
                }).show();
              }
            };

            $scope.model.viewModel.afterView(async function () {
              // show/hide user and address columns based on the type
              var inputArray = cnRecordViewScope.dataArray[0].inputArray;
              inputArray.findByProperty("key", "user_id").type =
                "home" == $scope.model.type ? "lookup-typeahead" : "hidden";
              inputArray.findByProperty("key", "address_id").type =
                "home" == $scope.model.type ? "enum" : "hidden";

              // make sure the metadata has been created
              await $scope.model.metadata.getPromise();
              $scope.model.viewModel.heading = $scope.model.type.ucWords() + " Appointment Details";
            });

            $scope.$on("cnRecordView complete", function (event, data) {
              var inputArray = cnRecordViewScope.dataArray[0].inputArray;

              // only show appointment types based on the qnaire
              updateAppointmentTypeEnumList(
                "view",
                inputArray.findByProperty("key", "appointment_type_id"),
                $scope.model.metadata.columnList.appointment_type_id.qnaireList,
                $scope.model.viewModel.record.qnaire_id
              );
            });
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentAddFactory", [
      "CnBaseAddFactory",
      "CnHttpFactory",
      function (CnBaseAddFactory, CnHttpFactory) {
        var object = function (parentModel) {
          CnBaseAddFactory.construct(this, parentModel);

          this.onNew = async function (record) {
            // update the address list based on the parent interview
            // Note that we only do this if there is a parent, otherwise we're not in the right state and we don't
            // need to update the address list as it will be done the next time we ARE in the right state (ie:
            // looking to create a new appointment)
            var parent = parentModel.getParentIdentifier();
            if (angular.isDefined(parent.subject) && angular.isDefined(parent.identifier)) {
              var response = await CnHttpFactory.instance({
                path: [parent.subject, parent.identifier].join("/"),
                data: { select: { column: { column: "participant_id" } } },
              }).query();
              var participant_id = response.data.participant_id;

              // get the participant's address list
              var response = await CnHttpFactory.instance({
                path: ["participant", participant_id, "address"].join("/"),
                data: {
                  select: { column: ["id", "rank", "summary"] },
                  modifier: {
                    where: {
                      column: "address.active",
                      operator: "=",
                      value: true,
                    },
                    order: { rank: false },
                  },
                },
              }).query();

              await parentModel.metadata.getPromise();
              parentModel.metadata.columnList.address_id.enumList =
                response.data.reduce((list, item) => {
                  list.push({ value: item.id, name: item.summary });
                  return list;
                }, []);
            }

            await this.$$onNew(record);
          };

          // add the new appointment's events to the calendar cache
          this.onAdd = function (record) {
            // if the user_id is null then make the interview_id null as well
            // this is a cheat for knowing it's a site appointment
            if (null == record.user_id) record.address_id = null;
            return this.$$onAdd(record);
          };
        };
        return {
          instance: function (parentModel) {
            return new object(parentModel);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentCalendarFactory", [
      "CnBaseCalendarFactory",
      "CnSession",
      function (CnBaseCalendarFactory, CnSession) {
        var object = function (parentModel) {
          CnBaseCalendarFactory.construct(this, parentModel);

          // default to month view
          this.currentView = "month";

          // remove day click callback
          delete this.settings.dayClick;

          // extend onCalendar to transform templates into events
          this.onCalendar = async function (replace, minDate, maxDate, ignoreParent) {
            // due to a design flaw (home vs site instances which cannot be determined in the base model's instance
            // method) we have to always replace events
            replace = true;

            // we must get the load dates before calling $$onCalendar
            var loadMinDate = this.getLoadMinDate(replace, minDate);
            var loadMaxDate = this.getLoadMaxDate(replace, maxDate);
            await this.$$onCalendar(replace, minDate, maxDate, true);
            this.cache.forEach((item, index, array) => {
              array[index] = getEventFromAppointment(item, CnSession.user.timezone);
            });
          };
        };

        return {
          instance: function (parentModel) {
            return new object(parentModel);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentViewFactory", [
      "CnBaseViewFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      "CnModalConfirmFactory",
      function (CnBaseViewFactory, CnSession, CnHttpFactory, CnModalMessageFactory, CnModalConfirmFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          angular.extend(this, {
            cancelAppointment: async function () {
              var modal = CnModalMessageFactory.instance({
                title: "Please Wait",
                message: "The appointment is being cancelled, please wait.",
                block: true,
              });
              modal.show();

              try {
                await CnHttpFactory.instance({
                  path: this.parentModel.getServiceResourcePath(this.record.getIdentifier()),
                  data: { outcome: "cancelled" },
                }).patch();
              } finally {
                modal.close();
              }
            },

            onView: async function (force) {
              await this.$$onView(force);

              // convert null appointment types to something more user-friendly
              if (null == this.record.appointment_type) this.record.appointment_type = "(none)";

              parentModel.getEditEnabled = function () {
                const record = parentModel.viewModel.record;
                return (
                  parentModel.$$getEditEnabled() && (
                    (moment().isBefore(record.datetime, "minute") && "upcoming" == record.state) ||
                    "passed" == record.state
                  )
                );
              };

              if ("home" == this.record.type) {
                // update the address list based on the parent interview
                var response = await CnHttpFactory.instance({
                  path: "interview/" + this.record.interview_id,
                  data: { select: { column: { column: "participant_id" } } },
                }).query();

                // get the participant's address list
                var response = await CnHttpFactory.instance({
                  path: ["participant", response.data.participant_id, "address", ].join("/"),
                  data: {
                    select: { column: ["id", "rank", "summary"] },
                    modifier: {
                      where: {
                        column: "address.active",
                        operator: "=",
                        value: true,
                      },
                      order: { rank: false },
                    },
                  },
                }).query();

                await parentModel.metadata.getPromise();
                parentModel.metadata.columnList.address_id.enumList =
                  response.data.reduce((list, item) => {
                    list.push({ value: item.id, name: item.summary });
                    return list;
                  }, []);
              }
            },

            onPatch: async function (data) {
              // changing the datetime must be handled differently than normal
              if (angular.isDefined(data.datetime)) {
                var formattedDatetime = CnSession.formatValue(data.datetime, "datetime", true);
                const passed = "passed" == this.record.state;

                if (!passed) {
                  // no need to reschedule when the change is on the same day
                  let oldDatetime = moment.tz(this.backupRecord.datetime, CnSession.user.timezone);
                  let newDatetime = moment.tz(data.datetime, CnSession.user.timezone);

                  if (oldDatetime.isSame(newDatetime, "day")) {
                    await this.$$onPatch(data);
                    await this.parentModel.reloadState(true);
                    return;
                  }
                }

                // the appointment must be cancelled or rescheduled
                const message = passed ? "Cancel and Reschedule" : "Reschedule";

                var response = await CnModalConfirmFactory.instance({
                  title: message + " Appointment",
                  message:
                    "Are you sure you wish to " + message.toLowerCase() +
                    " the appointment to " + formattedDatetime + "?",
                }).show();

                if (response) {
                  // check if there are appointment reminders setup for this appointment
                  const mailCountResponse = await CnHttpFactory.instance({
                    path: "appointment_mail",
                    data: {
                      modifier: {
                        where: [{
                          column: "appointment_mail.site_id",
                          operator: "=",
                          value: CnSession.site.id,
                        }, {
                          column: "appointment_mail.qnaire_id",
                          operator: "=",
                          value: this.record.qnaire_id,
                        }, {
                          column: "appointment_mail.appointment_type_id",
                          operator: "=",
                          value: !this.record.appointment_type_id ? null : this.record.appointment_type_id,
                        }, {
                          column: "appointment_mail.language_id",
                          operator: "=",
                          value: this.record.language_id,
                        }]
                      }
                    }
                  }).count();

                  let addMail = false;
                  const mailCount = parseInt(mailCountResponse.headers("Total"));
                  if (0 < mailCount) {
                    addMail = await CnModalConfirmFactory.instance({
                      title: "Email Reminders",
                      message:
                        "All unsent appointment reminders for the previous appointment will be removed.\n\n" +
                        "Do you wish to schedule appointment reminders for the new appointment time?"
                    }).show();
                  }

                  // rescheduling only requires updating the current record's datetime (server does the rest)
                  this.record.datetime = data.datetime;
                  this.formattedRecord.datetime = formattedDatetime;
                  const response = await this.$$onPatch(data);
                  const newAppointmentId = response.data;

                  // add mail if requested to
                  if (addMail) {
                    await CnHttpFactory.instance({
                      path: this.parentModel.getServiceResourcePath(newAppointmentId) + "?add_mail=1",
                      data: {},
                    }).patch();
                  }

                  this.parentModel.transitionToViewState({ getIdentifier: () => response.data });
                }
              } else {
                // otherwise just patch the data and reload the page so the calendar is updated
                await this.$$onPatch(data);
                await this.parentModel.reloadState(true);
              }
            },
          });
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentModelFactory", [
      "CnBaseModelFactory",
      "CnAppointmentAddFactory",
      "CnAppointmentCalendarFactory",
      "CnAppointmentListFactory",
      "CnAppointmentViewFactory",
      "CnSession",
      "CnHttpFactory",
      "$state",
      function (
        CnBaseModelFactory,
        CnAppointmentAddFactory,
        CnAppointmentCalendarFactory,
        CnAppointmentListFactory,
        CnAppointmentViewFactory,
        CnSession,
        CnHttpFactory,
        $state
      ) {
        var object = function (site) {
          if (!angular.isObject(site) || angular.isUndefined(site.id))
            throw new Error(
              "Tried to create CnAppointmentModel without specifying the site."
            );

          CnBaseModelFactory.construct(this, module);
          this.addModel = CnAppointmentAddFactory.instance(this);
          this.calendarModel = CnAppointmentCalendarFactory.instance(this);
          this.listModel = CnAppointmentListFactory.instance(this);
          this.viewModel = CnAppointmentViewFactory.instance(this, site.id == CnSession.site.id);
          this.site = site;
          this.type = $state.params.type;

          // set the default value of the user_id to the current user
          module.getInput("user_id").default = {
            id: CnSession.user.id,
            formatted: CnSession.user.firstName + " " + CnSession.user.lastName + " (" + CnSession.user.name + ")"
          };

          // customize service data
          this.getServiceData = function (type, columnRestrictLists) {
            this.type = $state.params.type;
            var data = this.$$getServiceData(type, columnRestrictLists);
            if ("calendar" == type || "list" == type) {
              if ("appointment" == this.getSubjectFromState())
                data.restricted_site_id = this.site.id;
              data.qnaire_type = this.type;
              if ("calendar" == type) {
                data.select = { column: ["datetime", "outcome", { table: "appointment_type", column: "color" }] };
                if ("home" == this.type) {
                  data.select.column.push({ table: "address", column: "postcode", });
                }
              }
            }
            return data;
          };

          // don't show add button when viewing full appointment list
          this.getAddEnabled = function () {
            return ("appointment" != this.getSubjectFromState() && this.$$getAddEnabled());
          };

          // pass type/site when transitioning to list state
          this.transitionToParentListState = async function (subject) {
            this.type = $state.params.type;
            if (angular.isUndefined(subject)) subject = "^";
            await $state.go(subject + ".list", {
              type: this.type,
              identifier: this.site.getIdentifier(),
            });
          };

          // pass type when transitioning to add state
          this.transitionToAddState = async function () {
            this.type = $state.params.type;
            var params = {
              type: this.type,
              parentIdentifier: $state.params.identifier,
            };

            // get the participant's primary site (assuming the current state is an interview)
            var response = await CnHttpFactory.instance({
              path: "interview/" + $state.params.identifier,
              data: { select: { column: [{ table: "effective_site", column: "name" }] } },
            }).get();

            if (response.data.name) params.site = "name=" + response.data.name;
            await $state.go("^.add_" + this.module.subject.snake, params);
          };

          // pass type/site when transitioning to list state
          this.transitionToListState = async function (record) {
            this.type = $state.params.type;
            await $state.go(this.module.subject.snake + ".list", {
              type: this.type,
              identifier: this.site.getIdentifier(),
            });
          };

          // pass type when transitioning to view state
          this.transitionToViewState = async function (record) {
            this.type = $state.params.type;
            var params = {
              type: this.type,
              identifier: record.getIdentifier(),
            };

            // get the participant's primary site (assuming the current state is an interview)
            var response = await CnHttpFactory.instance({
              path: "appointment/" + record.getIdentifier(),
              data: { select: { column: [{ table: "effective_site", column: "name" }] } },
            }).get();

            if (response.data.name) params.site = "name=" + response.data.name;
            await $state.go(this.module.subject.snake + ".view", params);
          };

          // pass type when transitioning to last state
          this.transitionToLastState = async function () {
            this.type = $state.params.type;
            var parent = this.getParentIdentifier();
            await $state.go(parent.subject + ".view", {
              type: this.type,
              identifier: parent.identifier,
            });
          };

          this.transitionToParentViewState = async function (subject, identifier) {
            this.type = $state.params.type;
            var params = { identifier: identifier };
            if ("interview" == subject) params.type = this.type;
            await $state.go(subject + ".view", params);
          };

          // extend getBreadcrumbTitle
          this.setupBreadcrumbTrail = function () {
            this.type = $state.params.type;
            this.$$setupBreadcrumbTrail();
            // add the type to the "appointment" crumb
            if (this.type) {
              var crumb = CnSession.breadcrumbTrail.findByProperty("title", "Appointment");
              if (!crumb) var crumb = CnSession.breadcrumbTrail.findByProperty("title", "Appointments");
              if (crumb) crumb.title = this.type[0].toUpperCase() + this.type.substring(1) + " " + crumb.title;
            }
          };

          // extend getMetadata
          this.getMetadata = async function () {
            await this.$$getMetadata();

            // Force the user and address columns to be mandatory (this will only affect home appointments)
            this.metadata.columnList.user_id.required = true;
            this.metadata.columnList.address_id.required = true;

            var response = await CnHttpFactory.instance({
              path: "appointment_type",
              data: {
                select: { column: ["id", "name", "qnaire_id"] },
                modifier: { order: "name", limit: 1000 },
              },
            }).query();

            // store the appointment types in a special array with qnaire_id as indeces:
            this.metadata.columnList.appointment_type_id.qnaireList =
              response.data.reduce((list, item) => {
                if (angular.isUndefined(list[item.qnaire_id])) list[item.qnaire_id] = [];
                list[item.qnaire_id].push({ value: item.id, name: item.name });
                return list;
              }, {});

            // and leave the enum list empty for now, it will be set by the view/add services
            this.metadata.columnList.appointment_type_id.enumList = [];
          };

          // extend getTypeaheadData
          this.getTypeaheadData = function (input, viewValue) {
            var data = this.$$getTypeaheadData(input, viewValue);

            // only include active users
            if ("user" == input.typeahead.table) {
              data.modifier.where.unshift({ bracket: true, open: true });
              data.modifier.where.push({ bracket: true, open: false });
              data.modifier.where.push({
                column: "user.active",
                operator: "=",
                value: true,
              });

              // restrict to the current site
              if (this.site) data.restricted_site_id = this.site.id;
            }

            return data;
          };
        };

        return {
          siteInstanceList: {},
          forSite: function (site) {
            if (!angular.isObject(site)) {
              $state.go("error.404");
              throw ('Cannot find site matching identifier "' + site + '", redirecting to 404.');
            }
            if (angular.isUndefined(this.siteInstanceList[site.id])) {
              this.siteInstanceList[site.id] = new object(site);
            }
            if ($state.params.type) this.siteInstanceList[site.id].type = $state.params.type;
            return this.siteInstanceList[site.id];
          },
          instance: function () {
            var site = null;
            var currentState = $state.current.name.split(".")[1];
            if ("calendar" == currentState || "list" == currentState) {
              if (angular.isDefined($state.params.identifier)) {
                var identifier = $state.params.identifier.split("=");
                if (2 == identifier.length) site = CnSession.siteList.findByProperty(identifier[0], identifier[1]);
              }
            } else if ("add_appointment" == currentState || "view" == currentState) {
              if (angular.isDefined($state.params.site)) {
                var identifier = $state.params.site.split("=");
                if (2 == identifier.length) site = CnSession.siteList.findByProperty(identifier[0], identifier[1]);
              }
            }

            if (null == site) site = CnSession.site;
            if (angular.isUndefined(site.getIdentifier))
              site.getIdentifier = function () { return "name=" + this.name; };
            return this.forSite(site);
          },
        };
      },
    ]);
  },
});
