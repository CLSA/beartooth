cenozoApp.defineModule({
  name: "appointment_type_reason",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "appointment_type",
          column: "appointment_type.name",
        },
      },
      name: {
        singular: "reason",
        plural: "reasons",
        possessive: "reason's",
      },
      columnList: {
        appointment_type: {
          column: "appointment_type.name",
          title: "Questionnaire",
        },
        rank: {
          title: "Rank",
          type: "rank",
        },
        title: {
          title: "Title",
        },
        extra: {
          title: "Extra Details",
          type: "boolean",
        },
      },
      defaultOrder: {
        column: "rank",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      rank: {
        column: "appointment_type_reason.rank",
        title: "Rank",
        type: "rank",
      },
      title: {
        title: "Title",
        type: "string",
      },
      extra: {
        title: "Extra Details",
        type: "boolean",
        help: "Whether to include a box for additional details."
      },
    });
  },
});
