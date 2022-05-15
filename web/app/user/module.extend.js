cenozoApp.extendModule({
  name: "user",
  create: (module) => {
    // extend the view factory
    cenozo.providers.decorator("CnUserViewFactory", [
      "$delegate",
      "CnHttpFactory",
      function ($delegate, CnHttpFactory) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, root) {
          var object = instance(parentModel, root);
          object.afterView(async function () {
            var response = await CnHttpFactory.instance({
              path: "user/" + object.record.id + "/assignment",
              data: {
                modifier: {
                  where: {
                    column: "assignment.end_datetime",
                    operator: "=",
                    value: null,
                  },
                },
                select: { column: ["id"] },
              },
            }).get();

            if (0 < response.data.length) {
              // add the view assignment button
              module.addExtraOperation("view", {
                title: "View Active Assignment",
                operation: async function ($state, model) {
                  await $state.go("assignment.view", {
                    identifier: response.data[0].id,
                  });
                },
              });
            } else {
              // remove the view assignment button, if found
              module.removeExtraOperation("view", "View Active Assignment");
            }
          });

          return object;
        };
        return $delegate;
      },
    ]);
  },
});
