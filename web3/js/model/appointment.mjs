const { CN_action_add } = await import(`${CENOZO_URL}/js/action/add.mjs`);
const { CN_action_calendar } = await import(`${CENOZO_URL}/js/action/calendar.mjs`);
const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_modal_input } = await import(`${CENOZO_URL}/js/modal/input.mjs`);
const { CN_model_user } = await import(`${CENOZO_URL}/js/model/user.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_appointment extends CN_base_model {
  #calendar_model;

  constructor() {
    super({
      wording: {
        singular: "appointment",
        plural: "appointments",
        posessive: "appointment's",
      },
      columns: {
        site: {
          column: "effective_site.name",
          title: "Site",
          is_hidden: () => !CN_session.get("role", "all_sites") || this.get_parent_model(),
        },
        uid: { column: "participant.uid", title: "UID", is_hidden: () => null != this.get_parent_model() },
        datetime: { type: "datetime", title: "Date & Time" },
        formatted_user_id: {
          title: "Interviewer",
          table_prefix: false,
          is_hidden: () => "home" != this.get_action().get_qnaire_type(),
            //null == this.get_parent_model() ||
            //"home" != this.get_parent_model().get_action().get_property_value("qnaire_type"),
        },
        address_summary: {
          title: "Address",
          table_prefix: false,
          is_hidden: () => "home" != this.get_action().get_qnaire_type(),
            //null == this.get_parent_model() ||
            //"home" != this.get_parent_model().get_action().get_property_value("qnaire_type"),
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
          type: "enum",
          enum: {
            get_enums: async () => await this.get_user_enums(
              this.get_parent_model().get_action().get_property_value_for_record("effective_site_id")
            ),
          },
          is_hidden: () => "site" == this.get_action().get_qnaire_type(),
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
          is_hidden: () => "site" == this.get_action().get_qnaire_type(),
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
              // if there is no appointment type this property is hidden, but we still need to return a valid path
              const appointment_type_id = this.get_action().get_property_value("appointment_type_id");
              return (
                appointment_type_id ?
                `appointment_type/${appointment_type_id}/appointment_type_reason` :
                "appointment_type_reason"
              );
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
        mode: "month",
        select: {
          column: [
            "id", // appointment.id
            "interview_id",
            "datetime",
            {
              column: `CONCAT(
                uid,
                IF(postcode IS NOT NULL, CONCAT(" [", SUBSTR(postcode, 1, 3), "]"), ""),
                IF(appointment.user_id, CONCAT(" for ", user.name), "")
              )`,
              alias: "title",
              table_prefix: false,
            },
            {
              column: `CONCAT_WS(
                " ",
                IF(appointment_type_id IS NULL, "primary", "warning"),
                IF("cancelled" = outcome OR "rescheduled" = outcome, "text-decoration-line-through", "")
              )`,
              alias: "type",
              table_prefix: false,
            },
          ],
        },
        modifier: {
          order: ["appointment.datetime", "uid"],
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

  /**
   * Extend parent method
   */
  async configure(parent_el, action_name, identifier=null, parent_model=null, is_rendered=false) {
    await super.configure(parent_el, action_name, identifier, parent_model, is_rendered);

    // create a second appoinment model for the calendar that gets embedded after the add and view actions
    if (["add", "view"].includes(action_name)) {
      const appointment_module = CN_session.get_module("appointment");
      await appointment_module.load_classes();
      this.#calendar_model = appointment_module.create_model();
    }
  }

  /**
   * Extend parent method
   */
  async run() {
    await super.run();
    if (this.#calendar_model) await this.#calendar_model.run();
  }

  /**
   * ADD DOCS
   */
  async embed_calendar(parent_element, config = {}) {
    const parent_model = this.get_parent_model();
    await this.#calendar_model.configure(
      parent_element,
      "calendar",
      `site_id=${parent_model.get_action().get_property_value("effective_site_id")}`,
      parent_model,
      true
    );

    // change the calendar's events to act as a way to set the appointment's datetime
    const calendar_action = this.#calendar_model.get_action();
    for (const name in config) calendar_action.set_config(name, config[name]);
    parent_element.append(this.#calendar_model.get_element());
  }

  /**
   * ADD DOCS
   */
  async get_user_enums(site_id = null) {
    const where = [
      { column: "role.name", operator: "IN", value: ["interviewer", "interviewer+"] },
      { column: "user.active", operator: "=", value: true },
    ];
    if (site_id) where.push({ column: "access.site_id", operator: "=", value: site_id });

    const user_list = await CN_api.get( "user", {
      select: {
        distinct: true,
        column: ["id", "name", "first_name", "last_name"],
      },
      modifier: {
        join: [
          { table: "access", onleft: "user.id", onright: "access.user_id" },
          { table: "role", onleft: "access.role_id", onright: "role.id" },
        ],
        where: where,
        order: "user.first_name",
      },
    });
    return user_list.map(u => ({ key: u.id, value: `${u.first_name} ${u.last_name} (${u.name})` }));
  }
}

export class CN_add_appointment extends CN_action_add {
  #participant_id;
  #qnaire_type;

  get_qnaire_type() { return this.#qnaire_type; }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      return `Add Appoinment to ${CN_common.uc_words(this.#qnaire_type)} Interview`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();
    this.#participant_id = this.get_model().get_parent_model().get_action().get_property_value("participant_id");
    this.#qnaire_type = this.get_model().get_parent_model().get_action().get_property_value("qnaire_type");
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const notes_btn_el = this.get_footer_element().querySelector("button[name=notes]");
    const note_count = this.get_model().get_parent_model().get_action().get_property_value("note_count");
    notes_btn_el.innerHTML = `Notes (${note_count})`;
  }

  /**
   * Extend parent method
   */
  _create_element() {
    this.get_model().embed_calendar(this.get_parent_element(), {
      on_click_cell: async (element) => {
        await this.set_property_value("datetime", element.date);
      },
    });
    return super._create_element();
  }

  /**
   * Extends the parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();
    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]")

    // add the notes action
    const notes_btn_el = this.constructor.html(
      '<button name="notes" type="button" class="btn btn-light btn-outline-primary">Notes</button>'
    );
    notes_btn_el.addEventListener("click", () => {
      CN_session.navigate_to(`participant/notes/${this.get_property_value("participant_id")}`);
    });
    left_btn_group_el.append(notes_btn_el);

    return footer_el;
  }
}

export class CN_calendar_appointment extends CN_action_calendar {
  #user_calendar = false;
  #qnaire_type = null;
  #participant_id;
  #identifier = null;
  #allow_change_identifier = false;
  #item_list = [];

  constructor(parent_el, model) {
    super(parent_el, model);

    const identifier = this.get_model().get_identifier();
    const matches = identifier.match(/^(site_id|user_id)=([0-9]+)/);
    if (null != matches) {
      if ("user_id" == matches[1]) this.#user_calendar = true;
      this.#identifier = matches[2];
      this.#allow_change_identifier = (
        this.#user_calendar ?
        !CN_session.get("role", "name").match(/interviewer/) :
        CN_session.get("role", "all_sites")
      );
    }

    this.#qnaire_type = this.get_query_parameter("qnaire_type");
  }

  get_qnaire_type() { return this.#qnaire_type; }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      if (this.#identifier) {
        const [title, record] = await Promise.all([
          super.get_text(type),
          CN_api.get([this.#user_calendar ? "user" : "site", this.#identifier].join("/")),
        ]);
        const postfix = (
          this.#user_calendar ?
          `${record.first_name} ${record.last_name} (${record.name})` :
          record.name
        );
        return `${CN_common.uc_words(this.#qnaire_type)} ${title} for ${postfix}`;
      }
    }

    if ("view_parent" == type) {
      return `View ${CN_common.uc_words(this.#qnaire_type)} Appointment List`;
    }

    return await super.get_text(type);
  }

  /**
   * Replace parent method
   */
  async on_navigate_to_list() {
    const params = { qnaire_type: this.#qnaire_type };

    // restrict by user if looking at a user's calendar
    if (this.#user_calendar) {
      const response = await CN_api.get(`user/${this.#identifier}`);
      params.tables = JSON.stringify({
        appointment: {
          columns: {
            formatted_user_id: [{
              operator: "=",
              value: `${response.first_name} ${response.last_name} (${response.name})`,
              or: false
            }],
          },
        },
      });
    } else if (CN_session.get("role", "all_sites")) {
      // restrict by site if we have access to all sites
      const response = await CN_api.get(`site/${this.#identifier}`);
      params.tables = JSON.stringify({
        appointment: {
          columns: {
            site: [{ operator: "=", value: response.name, or: false }],
          },
        },
      });
    }

    await CN_session.navigate_to(this.get_model().get_list_url(), params);
  }

  /**
   * Override parent method
   */
  get_on_load_path() {
    // never include the parent's path
    return "appointment";
  }

  /**
   * Extend parent method
   */
  get_on_load_parameters() {
    const params = super.get_on_load_parameters();
    params.restricted_site_id = this.#identifier;
    params.qnaire_type = this.#qnaire_type;
    if (this.#user_calendar) {
      if (!CN_common.is_object(params.modifier)) params.modifier = {};
      if (!CN_common.is_array(params.modifier.where)) params.modifier.where = [];
      params.modifier.where.push({ column: "appointment.user_id", operator: "=", value: this.#identifier });
    }
    return params;
  }

  /**
   * Extend parent method
   */
  async on_load() {
    // When embedding the calendar in the add/view actions we need to get the qnaire_type from the parent model
    const parent_model = this.get_model().get_parent_model();
    if (parent_model) this.#qnaire_type = parent_model.get_action().get_property_value("qnaire_type");

    if (this.#allow_change_identifier) {
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
  update_element() {
    super.update_element();

    if (this.#allow_change_identifier) {
      const ul_el = this.get_header_element().querySelector("div[name=calendar-type] ul");
      ul_el.replaceChildren(this.constructor.html(
        '<li><div class="dropdown-header text-bg-secondary">Site Calendars</div></li>'
      ));

      this.#item_list.forEach(item => {
        const item_btn_el = this.constructor.html(`
          <button type="button" class="dropdown-item">${item.name}</button>
        `);
        item_btn_el.addEventListener("click", () => {
          const params = { qnaire_type: this.#qnaire_type };
          const calendar_params = this.get_query_parameter("calendar");
          if (calendar_params) params.calendar = calendar_params;
          CN_session.navigate_to(`appointment/calendar/site_id=${item.id}`, params);
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

    if (this.#allow_change_identifier) {
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

  /**
   * Extend parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();

    const type = "home" == this.#qnaire_type ? "site" : "home";
    const interviewer = CN_session.get("role", "name").match(/interviewer/);
    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]");

    // add a button to show the other calendar type
    const other_calendar_btn_el = this.constructor.html(`
      <button type="button" name="${type}-calendar" class="btn btn-light btn-outline-primary">
        ${CN_common.uc_words(type)} Calendar
      </button>
    `);
    left_btn_group_el.append(other_calendar_btn_el);
    other_calendar_btn_el.addEventListener("click", async () => {
      const calendar_params = this.get_query_parameter("calendar");
      const params = { qnaire_type: type };
      if (calendar_params) params.calendar = calendar_params;
      const site_id = this.#user_calendar ? CN_session.get("site", "id") : this.#identifier;
      CN_session.navigate_to(`appointment/calendar/site_id=${site_id}`, params);
    });

    // add the user calendar button
    if ("home" == this.#qnaire_type) {
      const calendar_btn_el = this.constructor.html(`
        <button type="button" name="user-calendar" class="btn btn-light btn-outline-primary">
          ${this.#user_calendar ? "" : interviewer ? "Personal" : "Interviewer"} Home Calendar
        </button>
      `);
      left_btn_group_el.append(calendar_btn_el);
      calendar_btn_el.addEventListener("click", async () => {
        const calendar_params = this.get_query_parameter("calendar");
        const params = { qnaire_type: "home" };
        if (calendar_params) params.calendar = calendar_params;
        let identifier = null;

        if (this.#user_calendar) {
          identifier = `site_id=${CN_session.get("site", "id")}`;
        } else {
          let user_id = CN_session.get("user", "id");
          if (!interviewer) {
            user_id = await CN_modal_input.create_and_open({
              title: "Select Interviewer",
              message: "Please select which user's calendar you wish to see.",
              input: {
                type: "enum",
                enum: {
                  get_enums: async () => await this.get_model().get_user_enums(this.#identifier),
                },
              }
            });

            if (undefined === user_id) return;
          }

          identifier = `user_id=${user_id}`;
        }

        CN_session.navigate_to(`appointment/calendar/${identifier}`, params);
      });
    }

    return footer_el;
  }
}

export class CN_list_appointment extends CN_action_list {
  #qnaire_type;

  get_qnaire_type() { return this.#qnaire_type; }

  constructor(parent_el, model) {
    super(parent_el, model);
    this.#qnaire_type = this.get_query_parameter("qnaire_type");
  }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      return `${CN_common.uc_words(this.#qnaire_type)} ${await super.get_text(type)}`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  get_on_load_parameters() {
    const parameters = super.get_on_load_parameters();
    //parameters.restricted_site_id = this.#identifier;
    parameters.qnaire_type = this.#qnaire_type;
    return parameters;
  }

  /**
   * Extend parent method
   */
  async on_load() {
    // When the appointment list has an interview parent we need to get the qnaire_type from the parent model
    const parent_model = this.get_model().get_parent_model();
    if (parent_model) this.#qnaire_type = parent_model.get_action().get_property_value("qnaire_type");

    await super.on_load();
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    // add the appointment calendar button when viewing the base appointment list
    const btn_group_el = this.get_footer_element().querySelector("div.btn-group");
    if (null == this.get_model().get_parent_model() && !btn_group_el.querySelector("button[name=calendar]")) {
      const calendar_btn_el = this.constructor.html(`
        <button type="button" name="calendar" class="btn btn-primary">
          ${CN_common.uc_words(this.#qnaire_type)} Appointment Calendar
        </button>
      `);
      btn_group_el.append(calendar_btn_el);
      calendar_btn_el.addEventListener("click", () => {
        CN_session.navigate_to(
          `appointment/calendar/site_id=${CN_session.get("site", "id")}`,
          { qnaire_type: this.#qnaire_type }
        );
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
  #qnaire_type;
  #participant_id;

  get_qnaire_type() { return this.#qnaire_type; }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      return `${CN_common.uc_words(this.#qnaire_type)} ${await super.get_text(type)}`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();
    this.#participant_id = this.get_model().get_parent_model().get_action().get_property_value("participant_id");
    this.#qnaire_type = this.get_model().get_parent_model().get_action().get_property_value("qnaire_type");
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const cancel_btn_el = this.get_footer_element().querySelector("button[name=cancel]");
    this.constructor.set_disabled(cancel_btn_el, "passed" != this.get_property_value("state"));

    const notes_btn_el = this.get_footer_element().querySelector("button[name=notes]");
    const note_count = this.get_model().get_parent_model().get_action().get_property_value("note_count");
    notes_btn_el.innerHTML = `Notes (${note_count})`;
  }

  /**
   * Extend parent method
   */
  _create_element() {
    this.get_model().embed_calendar(this.get_parent_element(), {
      on_click_cell: async (element) => {
        await this.constructor.wait_for(async () => {
          await this.set_property_value("datetime", element.date);
          await this.on_set_property("datetime");
          await this.get_model().run();
        }, 0);
      },
    });
    return super._create_element();
  }

  /**
   * Extends the parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();
    const right_btn_group_el = footer_el.querySelector("div[name=right-btn-group]");
    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]")

    // add a view-participant button
    const view_participant_btn_el = this.constructor.html(
      '<button name="view-participant" type="button" class="btn btn-primary">View Participant</button>'
    );
    right_btn_group_el.append(view_participant_btn_el);
    view_participant_btn_el.addEventListener("click", () => {
      CN_session.navigate_to(`participant/view/${this.#participant_id}`, { tab: "interview" });
    });

    // add the notes action
    const notes_btn_el = this.constructor.html(
      '<button name="notes" type="button" class="btn btn-light btn-outline-primary">Notes</button>'
    );
    notes_btn_el.addEventListener("click", () => {
      CN_session.navigate_to(`participant/notes/${this.get_property_value("participant_id")}`);
    });
    left_btn_group_el.append(notes_btn_el);

    // add the cancel action
    const cancel_btn_el = this.constructor.html(
      '<button name="cancel" type="button" class="btn btn-light btn-outline-primary">Cancel Appointment</button>'
    );
    cancel_btn_el.addEventListener("click", async () => {
      await this.constructor.wait_for(
        CN_api.patch(this.get_model().get_view_url(null, "api"), { outcome: "cancelled" }),
        0
      );
      await this.get_model().run();
    });
    left_btn_group_el.append(cancel_btn_el);

    return footer_el;
  }
}
