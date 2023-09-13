cenozoApp.extendModule({
  name: "interview",
  create: (module) => {
    cenozo.insertPropertyAfter(module.columnList, "uid", "qnaire", {
      column: "qnaire.name",
      title: "Questionnaire",
    });
    cenozo.insertPropertyAfter(module.columnList, "qnaire", "interviewing_instance", {
      title: "Exporting Instance"
    });

    // add future_appointment as a hidden input (to be used below)
    module.addInput("", "future_appointment", { type: "hidden" });
    module.addInput("", "last_participation_consent", { type: "hidden" });
    module.addInput(
      "",
      "qnaire_id",
      {
        title: "Questionnaire",
        type: "enum",
        isConstant: true,
      },
      "participant"
    );
    module.addInput(
      "",
      "interviewing_instance",
      {
        title: "Exporting Instance",
        type: "string",
        isConstant: true,
      },
      "site_id"
    );

    // extend the list factory
    cenozo.providers.decorator("CnInterviewListFactory", [
      "$delegate",
      "CnHttpFactory",
      function ($delegate, CnHttpFactory) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel) {
          var object = instance(parentModel);

          // enable the add button if:
          //   1) the interview list's parent is a participant model
          //   2) all interviews are complete for this participant
          //   3) another qnaire is available for this participant
          object.afterList(async function () {
            object.parentModel.getAddEnabled = function () {
              return false;
            };

            var path = object.parentModel.getServiceCollectionPath();
            if (
              "participant" == object.parentModel.getSubjectFromState() &&
              null !== path.match(/participant\/[^\/]+\/interview/)
            ) {
              var queueRank = null;
              var qnaireRank = null;
              var lastInterview = null;

              // get the participant's last interview
              var response = await CnHttpFactory.instance({
                path: path,
                data: {
                  modifier: { order: { "qnaire.rank": true }, limit: 1 },
                  select: {
                    column: [
                      { table: "qnaire", column: "rank" },
                      "end_datetime",
                    ],
                  },
                },
                onError: function () {}, // ignore errors
              }).query();

              if (0 < response.data.length) lastInterview = response.data[0];

              // get the participant's current queue rank
              var response = await CnHttpFactory.instance({
                path: path.replace("/interview", ""),
                data: {
                  select: {
                    column: [
                      { table: "queue", column: "rank", alias: "queueRank" },
                      { table: "qnaire", column: "rank", alias: "qnaireRank" },
                    ],
                  },
                },
                onError: function () {}, // ignore errors
              }).query();

              queueRank = response.data.queueRank;
              qnaireRank = response.data.qnaireRank;

              object.parentModel.getAddEnabled = function () {
                return (
                  object.parentModel.$$getAddEnabled() &&
                  null != queueRank &&
                  null != qnaireRank &&
                  (null == lastInterview ||
                    (null != lastInterview.end_datetime &&
                      lastInterview.rank != qnaireRank))
                );
              };
            }
          });

          return object;
        };
        return $delegate;
      },
    ]);

    // extend the view factory
    cenozo.providers.decorator("CnInterviewViewFactory", [
      "$delegate",
      "$state",
      function ($delegate, $state) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, root) {
          var object = instance(parentModel, root);

          // force the default tab to be "appointment"
          object.defaultTab = "appointment";

          function getAppointmentEnabled(type) {
            var completed = null !== object.record.end_datetime;
            var participating =
              false !== object.record.last_participation_consent;
            var future = object.record.future_appointment;
            return "add" == type
              ? !completed && participating && !future
              : future;
          }

          function updateEnableFunctions() {
            object.appointmentModel.getAddEnabled = function () {
              return (
                angular.isDefined(object.appointmentModel.module.actions.add) &&
                getAppointmentEnabled("add")
              );
            };
            object.appointmentModel.getDeleteEnabled = function () {
              return (
                angular.isDefined(
                  object.appointmentModel.module.actions.delete
                ) && getAppointmentEnabled("delete")
              );
            };
          }

          // override onView
          object.onView = async function (force) {
            await object.$$onView(force);

            // check that the state type matches the interview's type
            if ($state.params.type != object.record.type) {
              await $state.go("error.404");
              throw "Interview type does not match state parameters, redirecting to 404.";
            }

            // set the correct type and refresh the list
            if (angular.isDefined(object.appointmentModel))
              updateEnableFunctions();
          };

          async function init() {
            // override appointment list's onDelete
            await object.deferred.promise;

            if (angular.isDefined(object.appointmentModel)) {
              object.appointmentModel.listModel.onDelete = async function (
                record
              ) {
                await object.appointmentModel.listModel.$$onDelete(record);
                await object.onView();
              };
            }
          }

          init();

          return object;
        };
        return $delegate;
      },
    ]);

    // extend the model factory
    cenozo.providers.decorator("CnInterviewModelFactory", [
      "$delegate",
      "$state",
      "CnHttpFactory",
      function ($delegate, $state, CnHttpFactory) {
        var instance = $delegate.instance;
        // extend getBreadcrumbTitle
        // (metadata's promise will have already returned so we don't have to wait for it)
        function extendObject(object) {
          object.type = $state.params.type;

          angular.extend(object, {
            getBreadcrumbTitle: function () {
              var qnaire =
                object.metadata.columnList.qnaire_id.enumList.findByProperty(
                  "value",
                  object.viewModel.record.qnaire_id
                );
              return qnaire ? qnaire.name : "unknown";
            },

            // pass type when transitioning to view state
            transitionToViewState: async function (record) {
              await $state.go(object.module.subject.snake + ".view", {
                type: record.type,
                identifier: record.getIdentifier(),
              });
            },

            // extend getMetadata
            getMetadata: async function () {
              await object.$$getMetadata();

              const [siteResponse, qnaireResponse] = await Promise.all([
                CnHttpFactory.instance({
                  path: "site",
                  data: {
                    select: { column: ["id", "name"] },
                    modifier: { order: "name", limit: 1000 },
                  },
                }).query(),

                CnHttpFactory.instance({
                  path: "qnaire",
                  data: {
                    select: { column: ["id", "name"] },
                    modifier: { order: "rank", limit: 1000 },
                  },
                }).query()
              ]);

              object.metadata.columnList.site_id.enumList = [];
              siteResponse.data.forEach(function (item) {
                object.metadata.columnList.site_id.enumList.push({
                  value: item.id,
                  name: item.name,
                });
              });

              object.metadata.columnList.qnaire_id.enumList = [];
              qnaireResponse.data.forEach(function (item) {
                object.metadata.columnList.qnaire_id.enumList.push({
                  value: item.id,
                  name: item.name,
                });
              });
            },
          });
        }

        extendObject($delegate.root);

        $delegate.instance = function () {
          var object = instance();
          extendObject(object);
          return object;
        };

        return $delegate;
      },
    ]);
  },
});
