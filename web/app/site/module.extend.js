cenozoApp.extendModule({
  name: "site",
  create: (module) => {
    // extend the view factory
    cenozo.providers.decorator("CnSiteViewFactory", [
      "$delegate",
      function ($delegate) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, root) {
          var object = instance(parentModel, root);

          async function init() {
            await object.deferred.promise;
            if (angular.isDefined(object.qnaireModel))
              object.qnaireModel.listModel.heading =
                "Disabled Questionnaire List";
          }

          init();

          return object;
        };
        return $delegate;
      },
    ]);
  },
});
