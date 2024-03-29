cenozoApp.extendModule({
  name: "overview",
  create: (module) => {
    /* ############################################################################################## */
    cenozo.providers.decorator("CnOverviewViewFactory", [
      "$delegate",
      "$state",
      "CnHttpFactory",
      function ($delegate, $state, CnHttpFactory) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, root) {
          var object = instance(parentModel, root);

          // track which study ID to restrict to (for progress overview only)
          angular.extend(object, {
            study: object.parentModel.getQueryParameter("study") ?
              object.parentModel.getQueryParameter("study") : null,

            setStudy: async function() {
              object.parentModel.setQueryParameter("study", object.study);
              await object.parentModel.reloadState(true);
            },

            onView: async function(force) {
              await object.$$onView(force);

              if( "progress" == object.record.name ) {
                const response = await CnHttpFactory.instance({
                  path: "study",
                  data: {
                    select: { column: 'name' },
                    modifier: { order: 'name' },
                  }
                }).query();

                object.studyList = [{value:null, name: '(restrict to Study)'}];
                response.data.forEach((item) =>
                  object.studyList.push({ value: item.name, name: item.name })
                );
              } else {
                object.studyList = null;
              }
            },
          });

          return object;
        };
        return $delegate;
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.decorator("CnOverviewModelFactory", [
      "$delegate",
      "CnHttpFactory",
      function ($delegate, CnHttpFactory) {
        var instance = $delegate.instance;

        function extendObject(object) {
          angular.extend(object, {
            getServiceData: function(type, columnRestrictLists) {
              var data = object.$$getServiceData(type, columnRestrictLists);

              // add the study ID restriction (for progress overview only)
              if( object.viewModel.study ) {
                data.data_modifier = {
                  where: {
                    column: 'study.name',
                    operator: '=',
                    value: object.viewModel.study,
                  }
                };
              }

              return data;
            },
          });
        }

        extendObject($delegate.root);

        $delegate.instance = function () {
          var object = instance();
          extendObject(object);
          return object;
        };

        return $delegate;
      },
    ]);
  },
});
