cenozoApp.defineModule({
  name: "setting",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "site",
          column: "site_id",
          friendly: "site",
        },
      },
      name: {
        singular: "setting",
        plural: "settings",
        possessive: "setting's",
      },
      columnList: {
        site: {
          column: "site.name",
          title: "Site",
        },
        mail_name: {
          title: "Default Email Name",
          help: "The default sender's name that emails will be sent from",
        },
        mail_address: {
          title: "Default Email Address",
          help: "The default email address that emails will be sent from",
        },
        call_without_webphone: {
          title: "No-Webphone",
          type: "boolean",
          help: "Allow users to make calls without being connected to the webphone",
        },
        calling_start_time: {
          title: "Start Call",
          type: "time_notz",
          help: "The earliest time to assign participants (in their local time)",
        },
        calling_end_time: {
          title: "End Call",
          type: "time_notz",
          help: "The latest time to assign participants (in their local time)",
        },
        appointment_update_span: {
          title: "Update Span",
          type: "number",
          help: "How many days into the future to include appointments when fetching the appointment list",
        },
        appointment_home_duration: {
          title: "Home Ap.",
          type: "number",
          help: "The length of time, in minutes, that a home appointment is estimated to take",
        },
        appointment_site_duration: {
          title: "Site Ap.",
          type: "number",
          help: "The length of time, in minutes, that a site appointment is estimated to take",
        },
      },
      defaultOrder: {
        column: "site",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      site: {
        column: "site.name",
        title: "Site",
        type: "string",
        isConstant: true,
      },
      mail_name: {
        title: "Default Email Name",
        type: "string",
        help: "The default sender's name that emails will be sent from",
      },
      mail_address: {
        title: "Default Email Address",
        type: "string",
        help: "The default email address that emails will be sent from",
      },
      call_without_webphone: {
        title: "Allow calls without a webphone",
        type: "boolean",
        help: "Allow users to make calls without being connected to the webphone",
      },
      calling_start_time: {
        title: "Earliest Call Time",
        type: "time_notz",
        help: "The earliest time to assign participants (in their local time)",
      },
      calling_end_time: {
        title: "Latest Call Time",
        type: "time_notz",
        help: "The latest time to assign participants (in their local time)",
      },
      appointment_update_span: {
        title: "Appointment Update Span",
        type: "string",
        format: "integer",
        minValue: 0,
        help: "How many days into the future to include appointments when fetching the appointment list",
      },
      appointment_home_duration: {
        title: "Home Appointment Duration",
        type: "string",
        format: "integer",
        minValue: 0,
        help: "The length of time, in minutes, that a home appointment is estimated to take",
      },
      appointment_site_duration: {
        title: "Site Appointment Duration",
        type: "string",
        format: "integer",
        minValue: 0,
        help: "The length of time, in minutes, that a site appointment is estimated to take",
      },
    });

    module.addInputGroup("Last Call Wait Times", {
      contacted_wait: {
        title: "Contacted Wait",
        type: "string",
        format: "integer",
        minValue: 0,
        help: 'How many minutes after a "contacted" call result to allow a participant to be called',
      },
      busy_wait: {
        title: "Busy Wait",
        type: "string",
        format: "integer",
        minValue: 0,
        help: 'How many minutes after a "busy" call result to allow a participant to be called',
      },
      fax_wait: {
        title: "Fax Wait",
        type: "string",
        format: "integer",
        minValue: 0,
        help: 'How many minutes after a "fax" call result to allow a participant to be called',
      },
      no_answer_wait: {
        title: "No Answer Wait",
        type: "string",
        format: "integer",
        minValue: 0,
        help: 'How many minutes after a "no answer" call result to allow a participant to be called',
      },
      not_reached_wait: {
        title: "Not Reached Wait",
        type: "string",
        format: "integer",
        minValue: 0,
        help: 'How many minutes after a "not reached" call result to allow a participant to be called',
      },
      hang_up_wait: {
        title: "Hang Up Wait",
        type: "string",
        format: "integer",
        minValue: 0,
        help: 'How many minutes after a "hang up" call result to allow a participant to be called',
      },
      soft_refusal_wait: {
        title: "Soft Refusal Wait",
        type: "string",
        format: "integer",
        minValue: 0,
        help: 'How many minutes after a "soft refusal" call result to allow a participant to be called',
      },
    });
  },
});
