const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);

export class CN_model_qnaire extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "questionnaire",
        plural: "questionnaires",
        posessive: "questionnaire's",
      },
      columns: {
        name: { title: "Name" },
        rank: { title: "Rank", type: "rank" },
        type: { title: "Type" },
        allow_missing_consent: { title: "Missing Consent", type: "boolean" },
        delay_offset: { title: "Delay Offset", type: "integer" },
        delay_unit: { title: "Delay Unit" },
      },
      properties: {
        rank: { meta: { table: "qnaire", column: "rank" }, title: "Rank", type: "rank" },
        name: { title: "Name", format: "identifier" },
        type: { title: "Type", type: "enum", is_constant: () => "view" == this.get_action_name() },
        allow_missing_consent: {
          title: "Allow Missing Consent",
          type: "boolean",
          help: `
            This field determines whether or not a participant should be allowed to proceed with the
            questionnaire when they are missing the extra consent record specified by the study.
          `,
        },
        delay_offset: { title: "Delay Offset", type: "integer", get_min: () => 0 },
        delay_unit: { title: "Delay Unit", type: "enum" },
        completed_event_type: {
          title: "Completed Event Type",
          meta: { table: "completed_event_type", column: "name" },
          is_constant: () => true,
          is_hidden: () => "add" == this.get_action_name(),
          help: "The event type which is added to a participant's event list when this questionnaire is completed",
        },
        prev_event_type_id: {
          title: "Previous Event Type",
          type: "enum",
          enum: { path: "event_type" },
          help: `
            The event type which was added when the previous questionnaire of the
            same type (home or site) was completed.
          `,
        },
      },
    });
  }
}
