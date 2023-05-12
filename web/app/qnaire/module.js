cenozoApp.defineModule({
  name: "qnaire",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: { column: "name" },
      name: {
        singular: "questionnaire",
        plural: "questionnaires",
        possessive: "questionnaire's",
      },
      columnList: {
        name: {
          title: "Name",
        },
        rank: {
          title: "Rank",
          type: "rank",
        },
        type: {
          title: "Type",
          type: "string",
        },
        delay_offset: {
          title: "Delay Offset",
          type: "number",
        },
        delay_unit: {
          title: "Delay Unit",
          type: "string",
        },
      },
      defaultOrder: {
        column: "rank",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      rank: {
        column: "qnaire.rank",
        title: "Rank",
        type: "rank",
        isConstant: "view",
      },
      name: {
        title: "Name",
        type: "string",
      },
      type: {
        title: "Type",
        type: "enum",
        isConstant: "view",
      },
      allow_missing_consent: {
        title: "Allow Missing Consent",
        type: "boolean",
        help: "This field determines whether or not a participant should be allowed to proceed with the questionnaire when they are missing the extra consent record specified by the study.",
      },
      delay_offset: {
        title: "Delay Offset",
        type: "string",
        format: "integer",
        minValue: 0,
      },
      delay_unit: {
        title: "Delay Unit",
        type: "enum",
      },
      completed_event_type_id: {
        title: "Completed Event Type",
        type: "enum",
        isConstant: true,
        isExcluded: "add",
        help: "The event type which is added to a participant's event list when this questionnaire is completed",
      },
      prev_event_type_id: {
        title: "Previous Event Type",
        type: "enum",
        help:
          "The event type which was added when the previous questionnaire of the same type (home or site) " +
          "was completed.",
      },
    });

    /* ############################################################################################## */
    cenozo.providers.factory("CnQnaireViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root, "script");

          this.getChildTitle = function (child) {
            let title = this.$$getChildTitle(child);

            // identify which children are items of interest
            if ("consent_type" == child.subject.snake) {
              title = "Consent of Interest";
            } else if ("event_type" == child.subject.snake) {
              title = "Event of Interest";
            } else if ("study" == child.subject.snake) {
              title = "Study of Interest";
            }

            return title;
          };

          async function init(object) {
            await object.deferred.promise;
            if (angular.isDefined(object.collectionModel))
              object.collectionModel.listModel.heading =
                "Disabled Collection List";
            if (angular.isDefined(object.holdTypeModel))
              object.holdTypeModel.listModel.heading =
                "Overridden Hold Type List";
            if (angular.isDefined(object.consentTypeModel))
              object.consentTypeModel.listModel.heading =
                "Consent Type of Interest List";
            if (angular.isDefined(object.scriptModel))
              object.scriptModel.listModel.heading = "Mandatory Script List";
            if (angular.isDefined(object.siteModel))
              object.siteModel.listModel.heading = "Disabled Site List";
            if (angular.isDefined(object.stratumModel))
              object.stratumModel.listModel.heading = "Disabled Stratum List";
          }

          init(this);
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnQnaireModelFactory", [
      "CnBaseModelFactory",
      "CnQnaireAddFactory",
      "CnQnaireListFactory",
      "CnQnaireViewFactory",
      "CnSession",
      "CnHttpFactory",
      function (
        CnBaseModelFactory,
        CnQnaireAddFactory,
        CnQnaireListFactory,
        CnQnaireViewFactory,
        CnSession,
        CnHttpFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);
          this.addModel = CnQnaireAddFactory.instance(this);
          this.listModel = CnQnaireListFactory.instance(this);
          this.viewModel = CnQnaireViewFactory.instance(this, root);

          // extend getMetadata
          this.getMetadata = async function () {
            await this.$$getMetadata();

            var response = await CnHttpFactory.instance({
              path: "event_type",
              data: {
                select: { column: ["id", "name"] },
                modifier: { order: "name", limit: 1000 },
              },
            }).query();

            this.metadata.columnList.completed_event_type_id.enumList =
              response.data.reduce((list, item) => {
                list.push({ value: item.id, name: item.name });
                return list;
              }, []);
            this.metadata.columnList.prev_event_type_id.enumList = angular.copy(
              this.metadata.columnList.completed_event_type_id.enumList
            );
          };
        };

        return {
          root: new object(true),
          instance: function () {
            return new object(false);
          },
        };
      },
    ]);
  },
});
