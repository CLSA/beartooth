cenozoApp.defineModule({
  name: "queue",
  models: ["list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: { column: "name" },
      name: {
        singular: "queue",
        plural: "queues",
        possessive: "queue's",
      },
      columnList: {
        rank: {
          title: "Rank",
          type: "rank",
        },
        name: { title: "Name" },
        participant_count: {
          title: "Participants",
          type: "number",
        },
      },
      defaultOrder: {
        column: "rank",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      rank: {
        title: "Rank",
        type: "rank",
        isConstant: true,
      },
      name: {
        title: "Name",
        type: "string",
        isConstant: true,
      },
      title: {
        title: "Title",
        type: "string",
        isConstant: true,
      },
      description: {
        title: "Description",
        type: "text",
        isConstant: true,
      },
    });

    if (angular.isDefined(module.actions.tree)) {
      module.addExtraOperation("view", {
        title: "View Queue Tree",
        operation: async function ($state, model) {
          // if the queue's participant list has restrictions on qnaire, site or language then apply them
          var restrictList =
            model.viewModel.participantModel.listModel.columnRestrictLists;
          var params = {};
          if (angular.isDefined(restrictList.qnaire)) {
            var restrict = restrictList.qnaire.findByProperty("test", "<=>");
            params.qnaire = restrict.value;
          }
          if (angular.isDefined(restrictList.site)) {
            var restrict = restrictList.site.findByProperty("test", "<=>");
            params.site = restrict.value;
          }
          if (angular.isDefined(restrictList.language)) {
            var restrict = restrictList.language.findByProperty("test", "<=>");
            params.language = restrict.value;
          }

          await $state.go("queue.tree", params);
        },
      });
    }

    /* ############################################################################################## */
    cenozo.providers.directive("cnQueueTree", [
      "CnQueueTreeFactory",
      "CnSession",
      function (CnQueueTreeFactory, CnSession) {
        return {
          templateUrl: module.getFileUrl("tree.tpl.html"),
          restrict: "E",
          controller: async function ($scope) {
            $scope.model = CnQueueTreeFactory.instance();
            $scope.isLoading = true;

            try {
              await $scope.model.onView(true);
              CnSession.setBreadcrumbTrail([{ title: "Queue Tree" }]);
            } finally {
              $scope.isLoading = false;
            }
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnQueueViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          async function init(obj) {
            await obj.deferred.promise;

            if (angular.isDefined(obj.participantModel)) {
              // map queue-view query parameters to participant-list
              obj.participantModel.queryParameterSubject = "queue";

              // override model functions
              obj.participantModel.getServiceData = function (
                type,
                columnRestrictList
              ) {
                // note that here we mean to use "this" as it refers to the participant model, not the CnQueueViewFactory
                var data = this.$$getServiceData(type, columnRestrictList);
                if ("list" == type) data.repopulate = true;
                return data;
              };

              // add additional columns to the model
              obj.participantModel.addColumn(
                "qnaire",
                { title: "Questionnaire", column: "qnaire.name" },
                0
              );
              obj.participantModel.addColumn(
                "language",
                { title: "Language", column: "language.name" },
                1
              );

              // make sure users can't add/remove participants from queues
              obj.participantModel.getChooseEnabled = function () {
                return false;
              };
            }
          }

          init(this);
        };

        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnQueueTreeFactory", [
      "$q",
      "$state",
      "CnQueueModelFactory",
      "CnSession",
      "CnHttpFactory",
      function ($q, $state, CnQueueModelFactory, CnSession, CnHttpFactory) {
        var object = function (root) {
          this.queueList = []; // one-dimensional list for manipulation
          this.queueTree = []; // multi-dimensional tree for display
          this.queueModel = CnQueueModelFactory.root;

          this.form = {
            canRepopulate: 3 <= CnSession.role.tier,
            lastRepopulation: null,
            isRepopulating: false,
            qnaire_id: undefined,
            qnaireList: [],
            site_id: undefined,
            siteList: [],
            language_id: undefined,
            languageList: [],
          };

          this.repopulate = async function () {
            this.form.isRepopulating = true;

            // blank out the button title if the tree is already built
            if (0 < this.queueTree.length) {
              this.queueList.forEach((item, index, array) => {
                if (0 < index && angular.isDefined(item)) {
                  array[index].participant_count = 0;
                  array[index].childTotal = 0;
                  array[index].button.name = "\u2026";
                }
              });
            }

            // isRepopulating any queue repopulates them all
            try {
              await CnHttpFactory.instance({
                path: "queue/1?repopulate=full",
              }).get();
              this.onView();
            } finally {
              this.form.isRepopulating = false;
            }
          };

          this.refreshState = async function () {
            var qnaireName = undefined;
            if (angular.isDefined(this.form.qnaire_id)) {
              var qnaire = this.form.qnaireList.findByProperty(
                "value",
                this.form.qnaire_id
              );
              if (qnaire) qnaireName = qnaire.name;
            }
            this.queueModel.setQueryParameter("qnaire", qnaireName);

            var siteName = undefined;
            if (angular.isDefined(this.form.site_id)) {
              var site = this.form.siteList.findByProperty(
                "value",
                this.form.site_id
              );
              if (site) siteName = site.name;
            }
            this.queueModel.setQueryParameter("site", siteName);

            var languageName = undefined;
            if (angular.isDefined(this.form.language_id)) {
              var language = this.form.languageList.findByProperty(
                "value",
                this.form.language_id
              );
              if (language) languageName = language.name;
            }
            this.queueModel.setQueryParameter("language", languageName);

            await this.queueModel.reloadState(false, true);
            await this.onView(false);
          };

          this.onView = async function (updateQueue) {
            // blank out the button title if the tree is already built
            if (0 < this.queueTree.length) {
              this.queueList.forEach((item, index, array) => {
                if (0 < index && angular.isDefined(item)) {
                  array[index].participant_count = 0;
                  array[index].childTotal = 0;
                  array[index].button.name = "\u2026";
                }
              });
            }

            if (0 == this.form.qnaireList.length) {
              var response = await CnHttpFactory.instance({
                path: "qnaire",
                data: {
                  select: { column: ["id", "name"] },
                  modifier: { order: "rank" },
                },
              }).query();

              this.form.qnaireList = response.data.reduce(
                (list, item) => {
                  list.push({ value: item.id, name: item.name });
                  return list;
                },
                [{ value: undefined, name: "Any" }]
              );
            }

            if (0 == this.form.siteList.length && CnSession.role.allSites) {
              var response = await CnHttpFactory.instance({
                path: "site",
                data: {
                  select: { column: ["id", "name"] },
                  modifier: { order: "name" },
                },
              }).query();

              this.form.siteList = response.data.reduce(
                (list, item) => {
                  list.push({ value: item.id, name: item.name });
                  return list;
                },
                [{ value: undefined, name: "All" }]
              );
            }

            if (0 == this.form.languageList.length) {
              var response = await CnHttpFactory.instance({
                path: "language",
                data: {
                  select: { column: ["id", "name"] },
                  modifier: {
                    where: { column: "active", operator: "=", value: true },
                    order: "name",
                  },
                },
              }).query();

              this.form.languageList = response.data.reduce(
                (list, item) => {
                  list.push({ value: item.id, name: item.name });
                  return list;
                },
                [{ value: undefined, name: "Any" }]
              );
            }

            // determine the qnaire, site and language from the query parameters
            var qnaireName = this.queueModel.getQueryParameter("qnaire");
            if (angular.isDefined(qnaireName)) {
              var qnaire = this.form.qnaireList.findByProperty(
                "name",
                qnaireName
              );
              this.form.qnaire_id = qnaire ? qnaire.value : undefined;
            }

            var siteName = this.queueModel.getQueryParameter("site");
            if (angular.isDefined(siteName)) {
              var site = this.form.siteList.findByProperty("name", siteName);
              this.form.site_id = site ? site.value : undefined;
            }

            var languageName = this.queueModel.getQueryParameter("language");
            if (angular.isDefined(languageName)) {
              var language = this.form.languageList.findByProperty(
                "name",
                languageName
              );
              this.form.language_id = language ? language.value : undefined;
            }

            // build a where statement based on the qnaire, site and language parameters
            var whereList = [];
            if (angular.isDefined(this.form.qnaire_id))
              whereList.push({
                column: "qnaire_id",
                operator: "=",
                value: this.form.qnaire_id,
              });
            if (angular.isDefined(this.form.site_id))
              whereList.push({
                column: "site_id",
                operator: "=",
                value: this.form.site_id,
              });
            if (angular.isDefined(this.form.language_id))
              whereList.push({
                column: "language_id",
                operator: "=",
                value: this.form.language_id,
              });

            var response = await CnHttpFactory.instance({
              path: "queue?full=1" + (updateQueue ? "&repopulate=time" : ""),
              data: {
                modifier: {
                  order: "id",
                  where: whereList,
                },
                select: {
                  column: [
                    "id",
                    "parent_queue_id",
                    "rank",
                    "name",
                    "title",
                    "participant_count",
                  ],
                },
              },
            }).query();

            if (0 < this.queueTree.length) {
              // don't rebuild the queue, just update the participant totals
              response.data.forEach((item) => {
                var queue = this.queueList[item.id];
                queue.participant_count = item.participant_count;
                queue.button.name = item.participant_count;
                queue.last_repopulation = item.last_repopulation;
              });
            } else {
              // create an array containing all branches and add their child branches as we go
              var eligibleQueueId = null;
              var oldParticipantQueueId = null;
              response.data.forEach((item) => {
                // make note of certain queues
                if (null === eligibleQueueId && "eligible" == item.name)
                  eligibleQueueId = item.id;
                if (
                  null === oldParticipantQueueId &&
                  "old participant" == item.name
                )
                  oldParticipantQueueId = item.id;

                // add all branches to the root, for now
                item.branchList = []; // will be filled in if the branch has any children
                item.initialOpen =
                  null === oldParticipantQueueId ||
                  oldParticipantQueueId > item.id;
                var self = this;
                item.open = item.initialOpen;
                item.button = {
                  id: item.id,
                  name: item.participant_count,
                  go: async function () {
                    var restrict = {};
                    var qnaireName =
                      self.queueModel.getQueryParameter("qnaire");
                    if (qnaireName)
                      restrict.qnaire = [{ test: "<=>", value: qnaireName }];
                    var siteName = self.queueModel.getQueryParameter("site");
                    if (siteName)
                      restrict.site = [{ test: "<=>", value: siteName }];
                    var languageName =
                      self.queueModel.getQueryParameter("language");
                    if (languageName)
                      restrict.language = [
                        { test: "<=>", value: languageName },
                      ];

                    var params = { identifier: this.id };
                    if (0 < Object.keys(restrict).length)
                      params.restrict = angular.toJson(restrict);

                    await $state.go("queue.view", params);
                  },
                };

                if (null !== item.rank) {
                  item.title = "Q" + item.rank + ": " + item.title;
                  item.color = "success";
                }

                this.queueList[item.id] = item;
                if (null !== item.parent_queue_id && "qnaire" != item.name) {
                  if ("qnaire" == this.queueList[item.parent_queue_id].name)
                    item.parent_queue_id = eligibleQueueId;
                  this.queueList[item.parent_queue_id].branchList.push(item);
                }
              });

              // now put all root branches into the queue tree
              this.queueList.forEach((item) => {
                if (angular.isDefined(item) && null === item.parent_queue_id)
                  this.queueTree.push(item);
              });
            }

            // now check for count errors
            this.queueList.forEach((queue, index, array) => {
              if ("all" == queue.name)
                this.form.lastRepopulation = CnSession.formatValue(
                  queue.last_repopulation,
                  "datetimesecond",
                  false
                );

              if (angular.isDefined(queue) && 0 < queue.branchList.length) {
                var count = 0;
                queue.branchList.forEach((branch) => {
                  count += branch.participant_count;
                });
                array[index].childTotal = count;

                if (queue.childTotal != queue.participant_count)
                  console.error(
                    'Queue "' +
                      queue.title +
                      '" has ' +
                      queue.participant_count +
                      " participants but child queues add up to " +
                      queue.childTotal +
                      " (they should be equal)"
                  );
              }
            });
          };
        };

        return {
          instance: function () {
            return new object(false);
          },
        };
      },
    ]);
  },
});
