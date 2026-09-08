const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/interview.mjs`);

export class CN_model_interview extends classes.CN_model_interview {
  /**
   * Extend parent method
   */
  async clone_columns() {
    const columns = await super.clone_columns();

    CN_common.insert_property(columns, "after", "uid", "qnaire", {
      column: "qnaire.name",
      title: "Questionnaire",
    });
    CN_common.insert_property(columns, "after", "qnaire", "interviewing_instance", {
      title: "Exporting Instance",
      table_prefix: false,
    });

    return columns;
  }

  /**
   * Extend parent method
   */
  async clone_properties() {
    const properties = await super.clone_properties();

    CN_common.insert_property(properties, "after", "uid", "qnaire", {
      meta: { table: "qnaire", column: "name" },
      title: "Questionnaire",
      is_constant: () => true,
    });
    CN_common.insert_property(properties, "after", "site_id", "interviewing_instance", {
      meta: {},
      title: "Exporting Instance",
      is_constant: () => true,
    });

    // hidden properties used by the appointment model
    properties.qnaire_id = { is_hidden: () => true };
    properties.interview_type = { meta: { table: "qnaire", column: "type" }, is_hidden: () => true };

    // properties needed by the appointment model
    properties.last_participation_consent = { meta: {}, type: "boolean", is_hidden: () => true };
    properties.future_appointment = { meta: {}, type: "boolean", is_hidden: () => true };

    return properties;
  }

  /**
   * Extend parent method
   */
  allow_add() {
    const action = this.get_action();

    // only allow adding a new interview if one is available
    return (
      super.allow_add() &&
      "list" == action.get_type() &&
      action.is_new_interview_available()
    );
  }

  /**
   * Extend parent method
   */
  allow_edit() {
    return ["administrator", "helpline"].includes(CN_session.get("role", "name"));
  }
}

export class CN_list_interview extends classes.CN_list_interview {
  #current_queue_rank = null;
  #current_qnaire_rank = null;
  #open_interview_count = null;

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();

    this.#current_queue_rank = null;
    this.#current_qnaire_rank = null;
    this.#open_interview_count = null;

    // Make note of the number of open interviews so we know when an interview can be added.
    // Note that this has to happen before calling the parent class' run method so the value is up to date when
    // the update_element() method is called.
    const parent_model = this.get_model().get_parent_model();
    if (parent_model && "participant" == parent_model.get_name()) {
      const [participant_response, interview_count] = await Promise.all([
        CN_api.get(parent_model.get_view_url(null, "api"), {
          select: { column: [
            { table: "queue", column: "rank", alias: "queue_rank" },
            { table: "qnaire", column: "rank", alias: "qnaire_rank" },
          ]},
        }),

        CN_api.count(`${parent_model.get_view_url(null, "api")}/interview`, {
          modifier: { where: { column: "end_datetime", operator: "=", value: null } }
        }),
      ]);

      this.#current_queue_rank = participant_response.queue_rank;
      this.#current_qnaire_rank = participant_response.qnaire_rank;
      this.#open_interview_count = interview_count;
    }
  }

  /**
   * Determines if a new interview is available
   * (there are no open interviews and the participant is still in a queue and qnaire)
   */
  is_new_interview_available() {
    return (
      0 === this.#open_interview_count &&
      null != this.#current_queue_rank &&
      null != this.#current_qnaire_rank
    );
  }
}

export class CN_view_interview extends classes.CN_view_interview {
  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("crumb" == type) {
      return `${this.get_property_value("uid")}: ${this.get_property_value("qnaire")}`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const complete_btn_el = this.get_footer_element().querySelector("button[name=complete]");
    if (complete_btn_el) {
      this.constructor.set_disabled(complete_btn_el, "(empty)" != this.get_property_value("end_datetime"));
    }
  }
}
