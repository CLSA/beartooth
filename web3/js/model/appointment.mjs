const { CN_action_add } = await import(`${CENOZO_URL}/js/action/add.mjs`);
const { CN_action_calendar } = await import(`${CENOZO_URL}/js/action/calendar.mjs`);
const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_model_user } = await import(`${CENOZO_URL}/js/model/user.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_appointment extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "appointment",
        plural: "appointments",
        posessive: "appointment's",
      },
      columns: {
        uid: { column: "participant.uid", title: "UID", is_hidden: () => null != this.get_parent_model() },
        datetime: { type: "datetime", title: "Date & Time" },
        formatted_user_id: {
          title: "Interviewer",
          table_prefix: false,
          is_hidden: () => "home" != this.get_parent_model().get_action().get_property_value("interview_type"),
        },
        address_summary: {
          title: "Address",
          table_prefix: false,
          is_hidden: () => "home" != this.get_parent_model().get_action().get_property_value("interview_type"),
        },
        appointment_type_id: {
          title: "Special Type",
          column: "appointment_type.name",
          help: `
            Identified whether this is a special appointment type.
            If blank then it is considered a "regular" appointment.
          `,
        },
        state: {
          title: "State",
          table_prefix: false,
          help: "Will either be completed, rescheduled, cancelled, upcoming or passed.",
        },
        interview_id: { is_hidden: () => true },
      },
      properties: {
        datetime: {
          title: "Date & Time",
          type: "datetime",
          required: true,
          help: "Cannot be changed once the appointment has passed.",
        },
        participant: {
          meta: { table: "participant", column: "uid" },
          title: "Participant",
          is_hidden: () => "add" == this.get_action_name(),
          is_constant: () => true,
        },
        qnaire: {
          meta: { table: "qnaire", column: "name" },
          title: "Questionnaire",
          is_hidden: () => "add" == this.get_action_name(),
          is_constant: () => true,
        },
        user_id: {
          title: "Interviewer",
          type: "typeahead",
          typeahead: CN_model_user.get_typeahead({
            modifier: {
              where: [
                { bracket: true, open: true },
                { column: "role_list", operator: "LIKE", value: "%interviewer%" },
                { column: "role_list", operator: "LIKE", value: "%coodinator%", or: true },
                { bracket: true, open: false },
              ],
            }
          }),
          is_hidden: () => "site" == this.get_action().get_interview_type(),
          help: "The interviewer the appointment is to be scheduled with.",
        },
        address_id: {
          title: "Address",
          type: "enum",
          enum: {
            path: () => {
              // get a list of the participant's addresses
              const participant_id = this.get_parent_model().get_action().get_property_value("participant_id");
              return `participant/${participant_id}/address`;
            },
            select: { column: [
              "id", {
                column: 'CONCAT(rank, ") ", CONCAT_WS(", ", address1, address2, city, region.name))',
                alias: "name",
                table_prefix: false
              }
            ] },
            modifier: { order: "rank" },
          },
          help: "The address of the home appointment.",
          is_hidden: () => "site" == this.get_action().get_interview_type(),
        },
        state: {
          meta: {}, // provided by the service
          title: "State",
          is_hidden: () => "add" == this.get_action_name(),
          is_constant: () => true,
          help: "One of upcoming, passed, completed or cancelled.",
        },
        appointment_type_id: {
          title: "Special Type",
          type: "enum",
          enum: {
            get_enums: async () => {
              const qnaire_id = this.get_parent_model().get_action().get_property_value("qnaire_id");
              return await CN_api.get("appointment_type", {
                select: { column: [
                  { table: "appointment_type", column: "id", alias: "key" },
                  { table: "appointment_type", column: "name", alias: "value" },
                ] },
                modifier: { where: { column: "qnaire_id", operator: "=", value: qnaire_id } },
              });
            },
          },
          is_constant: () =>
            "view" == this.get_action_name() &&
            (!this.allow_edit() || "" != this.get_action().get_property_value("state")),
          help: `
            Identified whether this is a special appointment type.
            If blank then it is considered a "regular" appointment.
          `,
        },
        appointment_type_reason_id: {
          title: "Reason for Special Type",
          type: "enum",
          enum: {
            path: () => {
              const appointment_type_id = this.get_action().get_property_value("appointment_type_id");
              path: `appointment_type/${appointment_type_id}/appointment_type_reason`;
            },
            select: { column: [
              "id",
              { column: 'CONCAT(rank, ") ", title)', alias: "name", table_prefix: false, }
            ] },
            modifier: { order: "rank" },
          },
          is_hidden: () =>
            "add" == this.get_action_name() ||
            !this.get_action().get_property_value("appointment_type_id"),
          is_constant: () =>
            "view" == this.get_action_name() &&
            (!this.allow_edit() || "" != this.get_action().get_property_value("state")),
          help: "Please indicate the main reason the participant requires the selected special appointment type.",
        },
        reason_extra: {
          title: "Additional Details",
          is_hidden: () =>
            "add" == this.get_action_name() ||
            !this.get_action().get_property_value("appointment_type_id"),
          is_constant: () =>
            "view" == this.get_action_name() &&
            (!this.allow_edit() || "" != this.get_action().get_property_value("state")),
        },
        disable_mail: {
          meta: {}, // provided by the service
          title: "Disable Email Reminder(s)",
          type: "boolean",
          get_default: () => false,
          is_hidden: () => "view" == this.get_action_name(),
          required: true,
          help: "If selected then no automatic email reminders will be created for this appointment.",
        },
        participant_id: {
          meta: { table: "interview", column: "participant_id" },
          is_hidden: () => true,
        },
      },
      calendar: {
        select: {
          column: [
            "id", // appointment.id
            "interview_id",
            {
              column: `CONCAT(
                uid,
                " (", language.code, ")",
                " (", qnaire.rank, ")",
                IF(user_id, CONCAT(" for ", user.name), "")
              )`,
              alias: "title",
              table_prefix: false,
            },
            {
              column: `IF(
                "cancelled" = outcome,
                "secondary text-decoration-line-through",
                "primary"
              )`,
              alias: "type",
              table_prefix: false,
            },
          ],
        },
        modifier: {
          order: ["datetime", "uid"],
        },
        on_click_event: async (event) => {
          await CN_session.navigate_to(`interview/view/${event.interview_id}/appointment/view/${event.id}`);
        },
      },
    });
  }

  /**
   * Extend parent method
   */
  allow_add() {
    const parent_model = this.get_parent_model();
    const parent_action = (
      null == parent_model || "interview" != parent_model.get_name() ?
      null :
      parent_model.get_action()
    );

    // Only allow an appointment to be added based on the parent interview's properties
    return (
      parent_action &&
      super.allow_add() && (
        // if there's no action then we're on the add appointment action
        null == this.get_action() ||
        // only allow the add button when the interview is open, has consent and has no future appointment
        (
          "(empty)" === parent_action.get_property_value("end_datetime") &&
          true === parent_action.get_property_value("last_participation_consent") &&
          false === parent_action.get_property_value("future_appointment")
        )
      )
    );
  }

  /**
   * Extend parent method
   */
  allow_delete() {
    const parent_model = this.get_parent_model();
    const parent_action = (
      null == parent_model || "interview" != parent_model.get_name() ?
      null :
      parent_model.get_action()
    );

    // only allow an appointment to be deleted when a future appointment exists
    return (
      parent_action &&
      super.allow_delete() &&
      true === parent_action.get_property_value("future_appointment")
    );
  }

  /**
   * Extend parent method
   */
  allow_edit() {
    // only allow editing future appointments
    let upcoming = false;
    const datetime = this.get_action().get_property_value("datetime");
    if (datetime && "(empty)" != datetime) {
      const date = new Date(datetime.replace(/ @ /, " "));
      const now = CN_common.get_date();
      now.setSeconds(0);
      upcoming = date >= now;
    }

    return super.allow_edit() && upcoming;
  }
}

export class CN_add_appointment extends CN_action_add {
  #participant_id;
  #interview_type;

  get_interview_type() { return this.#interview_type; }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      return `Add Appoinment to ${CN_common.uc_words(this.#interview_type)} Interview`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();
    this.#participant_id = this.get_model().get_parent_model().get_action().get_property_value("participant_id");
    this.#interview_type = this.get_model().get_parent_model().get_action().get_property_value("interview_type");
  }

  // TODO: add the site calendar
}

export class CN_calendar_appointment extends CN_action_calendar {
  #identifier = null;
  #change_type_allowed = false;
  #item_list = [];

  constructor(parent_el, model) {
    super(parent_el, model);

    const identifier = this.get_model().get_identifier();
    const matches = identifier.match(/^(site_id|user_id)=([0-9]+)/);
    if (null != matches) {
      this.#identifier = matches[2];
      this.#change_type_allowed = (
        "site_id" == matches[1] ?
        CN_session.get("role", "all_sites") :
        1 < CN_session.get("role", "tier")
      );
    }
  }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      const response = await CN_api.get(`site/${this.#identifier}`);
      const title = await super.get_text(type);
      return `${title} for ${response.name}`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  async on_load() {
    if (this.#change_type_allowed) {
      this.#item_list = await CN_api.get("site", { modifier: { order: "name" } });
    } else {
      // check permissions
      if (this.#identifier != CN_session.get("site", "id")) {
        const error = new URIError();
        error.title = "Permission Denied (403)";
        error.message = "You do not have access to the requested resource.";
        throw error;
      }
    }

    await super.on_load();
  }

  /**
   * Extend parent method
   */
  get_on_load_parameters() {
    const parameters = super.get_on_load_parameters();
    parameters.restricted_site_id = this.#identifier;
    return parameters;
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    if (this.#change_type_allowed) {
      const ul_el = this.get_header_element().querySelector("div[name=calendar-type] ul");
      ul_el.replaceChildren(this.constructor.html(
        '<li><div class="dropdown-header text-bg-secondary">Site Calendars</div></li>'
      ));

      this.#item_list.forEach(item => {
        const item_btn_el = this.constructor.html(`
          <button type="button" class="dropdown-item">${item.name}</button>
        `);
        item_btn_el.addEventListener("click", () => {
          const calendar_params = this.get_query_parameter("calendar");
          CN_session.navigate_to(
            `appointment/calendar/site_id=${item.id}`,
            calendar_params ? { calendar: calendar_params } : null,
          );
        });
        const item_li_el = this.constructor.html(
          `<li class="bg-${item.id == this.#identifier ? "warning" : "body"}"></li>`
        );
        item_li_el.append(item_btn_el);
        ul_el.append(item_li_el);
      });
    }
  }

  /**
   * Extend parent method
   */
  _create_header_element() {
    const header_el = super._create_header_element();

    if (this.#change_type_allowed) {
      const calendar_type_div_el = this.constructor.html(`
        <div class="dropdown" name="calendar-type">
          <button name="calendar-type" type="button" class="btn btn-primary px-2 py-0" data-bs-toggle="dropdown">
            <i class="bi bi-calendar fs-5"></i>
          </button>
          <ul class="dropdown-menu bg-secondary">
          </ul>
        </div>
      `);

      header_el.querySelector("div[name=report]").before(calendar_type_div_el);
    }

    return header_el;
  }
}

export class CN_list_appointment extends CN_action_list {
  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    // add the appointment calendar button when viewing the base appointment list
    const btn_group_el = this.get_footer_element().querySelector("div.btn-group");
    if (null == this.get_model().get_parent_model() && !btn_group_el.querySelector("button[name=calendar]")) {
      const calendar_btn_el = this.constructor.html(
        '<button type="button" name="calendar" class="btn btn-primary">Appointment Calendar</button>'
      );
      btn_group_el.append(calendar_btn_el);
      calendar_btn_el.addEventListener("click", () => {
        CN_session.navigate_to(`appointment/calendar/${this.get_model().get_identifier()}`)
      });
    }
  }

  /**
   * Replace parent method
   */
  async on_row_click(record) {
    // always include the interview as the parent model when selecting an appointment
    await CN_session.navigate_to(`interview/view/${record.interview_id}/appointment/view/${record.id}`);
  }
}

export class CN_view_appointment extends CN_action_view {
  #interview_type;
  #participant_id;

  get_interview_type() { return this.#interview_type; }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      return `${CN_common.uc_words(this.#interview_type)} ${await super.get_text(type)}`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();
    this.#participant_id = this.get_model().get_parent_model().get_action().get_property_value("participant_id");
    this.#interview_type = this.get_model().get_parent_model().get_action().get_property_value("interview_type");
  }

  /** 
   * Extends the parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();

    // add a view-participant button
    const right_btn_group_el = footer_el.querySelector("div[name=right-btn-group]");
    const view_participant_btn_el = this.constructor.html(
      '<button name="view-participant" type="button" class="btn btn-primary">View Participant</button>'
    );  
    right_btn_group_el.append(view_participant_btn_el);
    view_participant_btn_el.addEventListener("click", () => {
      CN_session.navigate_to(`participant/view/${this.#participant_id}`, { tab: "interview" });
    }); 

    return footer_el;
  }

  // TODO: add the site calendar
}
