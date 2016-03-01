define( {
  subject: 'onyx_instance',
  name: {
    singular: 'onyx instance',
    plural: 'onyx instances',
    possessive: 'onyx instance\'s',
    pluralPossessive: 'onyx instances\''
  },
  inputList: {
    // TODO: fill out
  },
  columnList: {
    name: {
      column: 'user.name',
      title: 'Name'
    },
    site: {
      // TODO
    },
    owner: {
      // TODO
    },
    active: {
      column: 'user.active',
      title: 'Active',
      filter: 'cnYesNo'
    },
    last_datetime: {
      title: 'Last Activity',
      filter: 'date:"MMM d, y HH:mm"'
    }
  },
  defaultOrder: {
    column: 'name',
    reverse: false
  }
} );
