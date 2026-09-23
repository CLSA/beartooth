const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);

export class CN_model_appointment_type extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "appointment type",
        plural: "appointment types",
        posessive: "appointment type's",
      },
      columns: {
        name: { title: "Name" },
        use_participant_timezone: { title: "Use Participant's Timezone", type: "boolean" },
        color: { title: "Colour" },
        qnaire: { column: "qnaire.name", title: "Questionnaire" },
      },
      properties: {
        name: { title: "Name", format: "identifier" },
        use_participant_timezone: {
          title: "Use Participant's Timezone",
          type: "boolean",
          help: "Whether to send appointment reminders in the participant's timezone or the site's timezone.",
        },
        color: { title: "Colour", type: "color" },
        description: { title: "Description", type: "text" },
      },
    });
  }
}
