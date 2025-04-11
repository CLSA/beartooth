cenozoApp.defineModule({
  name: "appointment_type",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "qnaire",
          column: "qnaire.name",
        },
      },
      name: {
        singular: "appointment type",
        plural: "appointment types",
        possessive: "appointment type's",
      },
      columnList: {
        name: {
          title: "Name",
        },
        use_participant_timezone: {
          title: "Use Participant's Timezone",
          type: "boolean",
        },
        color: {
          title: "Colour",
        },
        qnaire: {
          column: "qnaire.name",
          title: "Questionnaire",
        },
      },
      defaultOrder: {
        column: "name",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      name: {
        title: "Name",
        type: "string",
        format: "identifier",
      },
      use_participant_timezone: {
        title: "Use Participant's Timezone",
        type: "boolean",
        help: "Whether to send appointment reminders in the participant's timezone or the site's timezone.",
      },
      color: {
        title: "Colour",
        type: "color",
      },
      description: {
        title: "Description",
        type: "text",
      },
    });

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentTypeModelFactory", [
      "CnBaseModelFactory",
      "CnAppointmentTypeListFactory",
      "CnAppointmentTypeAddFactory",
      "CnAppointmentTypeViewFactory",
      "CnSession",
      "CnHttpFactory",
      function (
        CnBaseModelFactory,
        CnAppointmentTypeListFactory,
        CnAppointmentTypeAddFactory,
        CnAppointmentTypeViewFactory,
        CnSession,
        CnHttpFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);
          this.addModel = CnAppointmentTypeAddFactory.instance(this);
          this.listModel = CnAppointmentTypeListFactory.instance(this);
          this.viewModel = CnAppointmentTypeViewFactory.instance(this, root);

          // extend getMetadata
          this.getMetadata = async function () {
            await this.$$getMetadata();

            var response = await CnHttpFactory.instance({
              path: "qnaire",
              data: {
                select: { column: ["id", "name"] },
                modifier: { order: "rank", limit: 1000 },
              },
            }).query();

            this.metadata.columnList.qnaire_id.enumList = [];
            var self = this;
            response.data.forEach(function (item) {
              self.metadata.columnList.qnaire_id.enumList.push({
                value: item.id,
                name: item.name,
              });
            });
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
