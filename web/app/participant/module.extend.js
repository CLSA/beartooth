cenozoApp.extendModule({
  name: "participant",
  create: (module) => {
    module.addInputGroup("Queue Details", {
      title: {
        title: "Current Questionnaire",
        column: "qnaire.title",
        type: "string",
        isConstant: true,
      },
      start_date: {
        title: "Delayed Until",
        column: "qnaire.start_date",
        type: "date",
        isConstant: true,
        help:
          "If not empty then the participant will not be permitted to begin this questionnaire until the " +
          "date shown is reached.",
      },
      queue: {
        title: "Current Queue",
        column: "queue.title",
        type: "string",
        isConstant: true,
      },
      override_stratum: {
        title: "Override Stratum",
        type: "boolean",
        isConstant: function ($state, model) {
          return !model.isRole("administrator");
        },
      },
    });

    module.addExtraOperation("view", {
      title: "Update Queue",
      isDisabled: function ($state, model) {
        return model.viewModel.isRepopulating;
      },
      operation: async function ($state, model) {
        await model.viewModel.onViewPromise;
        await model.viewModel.repopulate();
      },
    });

    angular.extend(module.historyCategoryList, {
      // appointments are added in the assignment's promise function below
      Appointment: { active: true },

      Assignment: {
        active: true,
        promise: async function (historyList, $state, CnHttpFactory) {
          var interviewResponse = await CnHttpFactory.instance({
            path: "participant/" + $state.params.identifier + "/interview",
            data: {
              modifier: { order: { "interview.start_datetime": true } },
              select: { column: ["id"] },
            },
          }).query();

          await Promise.all(
            interviewResponse.data.map(async function (item) {
              // appointments
              var response = await CnHttpFactory.instance({
                path: "interview/" + item.id + "/appointment",
                data: {
                  modifier: { order: { "interview.start_datetime": true } },
                  select: {
                    column: [
                      "datetime",
                      "address_id",
                      "outcome",
                      {
                        table: "user",
                        column: "first_name",
                        alias: "user_first",
                      },
                      {
                        table: "user",
                        column: "last_name",
                        alias: "user_last",
                      },
                      {
                        table: "appointment_type",
                        column: "name",
                        alias: "type",
                      },
                    ],
                  },
                },
              }).query();

              response.data.forEach(function (item) {
                var title =
                  "a " +
                  (null == item.type ? "regular" : item.type) +
                  " " +
                  (null == item.address_id ? "site" : "home") +
                  " appointment" +
                  (null == item.address_id
                    ? ""
                    : " with " + item.user_first + " " + item.user_last);
                var description =
                  "A " +
                  (null == item.address_id ? "site" : "home") +
                  " appointment scheduled for this time has ";
                if ("completed" == item.outcome) description += "been met.";
                else if ("cancelled" == item.outcome)
                  description += "been cancelled.";
                else description += "not been met.";

                historyList.push({
                  datetime: item.datetime,
                  category: "Appointment",
                  title: title,
                  description: description,
                });
              });

              // assignments
              var response = await CnHttpFactory.instance({
                path: "interview/" + item.id + "/assignment",
                data: {
                  modifier: { order: { "assignment.start_datetime": true } },
                  select: {
                    column: [
                      "start_datetime",
                      "end_datetime",
                      {
                        table: "user",
                        column: "first_name",
                        alias: "user_first",
                      },
                      {
                        table: "user",
                        column: "last_name",
                        alias: "user_last",
                      },
                      {
                        table: "site",
                        column: "name",
                        alias: "site",
                      },
                      {
                        table: "qnaire",
                        column: "name",
                        alias: "qnaire",
                      },
                      {
                        table: "queue",
                        column: "name",
                        alias: "queue",
                      },
                    ],
                  },
                },
              }).query();

              response.data.forEach(function (item) {
                if (null != item.start_datetime) {
                  historyList.push({
                    datetime: item.start_datetime,
                    category: "Assignment",
                    title:
                      "started by " + item.user_first + " " + item.user_last,
                    description:
                      'Started an assignment for the "' +
                      item.qnaire +
                      '" questionnaire.\n' +
                      "Assigned from the " +
                      item.site +
                      " site " +
                      'from the "' +
                      item.queue +
                      '" queue.',
                  });
                }
                if (null != item.end_datetime) {
                  historyList.push({
                    datetime: item.end_datetime,
                    category: "Assignment",
                    title:
                      "completed by " + item.user_first + " " + item.user_last,
                    description:
                      'Completed an assignment for the "' +
                      item.qnaire +
                      '" questionnaire.\n' +
                      "Assigned from the " +
                      item.site +
                      " site " +
                      'from the "' +
                      item.queue +
                      '" queue.',
                  });
                }
              });
            })
          );
        },
      },
    });

    // extend the list factory
    cenozo.providers.decorator("CnParticipantListFactory", [
      "$delegate",
      "CnSession",
      function ($delegate, CnSession) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel) {
          var object = instance(parentModel);
          if ("interviewer" == CnSession.role.name)
            object.heading = "My Participant List";
          return object;
        };
        return $delegate;
      },
    ]);

    // extend the view factory
    cenozo.providers.decorator("CnParticipantViewFactory", [
      "$delegate",
      "CnHttpFactory",
      function ($delegate, CnHttpFactory) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, root) {
          var object = instance(parentModel, root);

          // force the default tab to be "interview"
          object.defaultTab = "interview";

          object.isRepopulating = false;
          object.repopulate = async function () {
            try {
              object.isRepopulating = true;
              await CnHttpFactory.instance({
                path:
                  object.parentModel.getServiceResourcePath() + "?repopulate=1",
              }).patch();
              await object.onView();
            } finally {
              object.isRepopulating = false;
            }
          };
          return object;
        };
        return $delegate;
      },
    ]);

  },
});
