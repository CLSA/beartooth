cenozoApp.extendModule({
  name: "callback",
  create: (module) => {
    // extend the model factory so that callback events are coloured green when the next interview is at home
    cenozo.providers.decorator("CnCallbackCalendarFactory", [
      "$delegate",
      "CnHttpFactory",
      function ($delegate, CnHttpFactory) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, site) {
          var object = instance(parentModel, site);
          object.baseOnCalendarFn = object.onCalendar;

          object.onCalendar = async function (
            replace,
            minDate,
            maxDate,
            ignoreParent
          ) {
            await object.baseOnCalendarFn(
              replace,
              minDate,
              maxDate,
              ignoreParent
            );

            // get a list of all participants callbacks that aren't coloured yet
            var participantIdList = object.cache
              .filter((item) => angular.isUndefined(item.color))
              .map((item) => parseInt(item.getIdentifier()));

            if (0 < participantIdList.length) {
              var response = await CnHttpFactory.instance({
                path: "participant",
                data: {
                  select: { column: ["uid", "qnaire_type"] },
                  modifier: {
                    where: {
                      column: "participant.id",
                      operator: "IN",
                      value: participantIdList,
                    },
                  },
                },
              }).get();

              var participants = response.data.reduce(function (
                object,
                participant
              ) {
                object[participant.uid] = participant.qnaire_type;
                return object;
              },
              {});
              object.cache
                .filter((item) => angular.isUndefined(item.color))
                .forEach(function (item) {
                  item.color =
                    "site" == participants[item.title] ? "default" : "green";
                });
              angular.element("div.calendar").fullCalendar("refetchEvents");
            }
          };
          return object;
        };
        return $delegate;
      },
    ]);
  },
});
