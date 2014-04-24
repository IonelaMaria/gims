angular.module('myApp').controller('Browse/FilterCtrl', function($scope, $routeParams, $location, $http, $timeout, Restangular, $q, authService, $route, $rootScope) {
    'use strict';

    /**************************************************************************/
    /***************************************** First execution initialisation */
    /**************************************************************************/

    if ($location.$$path.indexOf('/contribute') >= 0) {
        $scope.mode = 'Contribute';
    } else if ($location.$$path.indexOf('/browse') >= 0) {
        $scope.mode = 'Browse';
    }

    if ($route.current.params.sectorChildren) {
        $scope.sector = true;
    } else {
        $scope.sector = false;
    }

    /**************************************************************************/
    /*********************************************** Variables initialisation */
    /**************************************************************************/

        // params for ajax requests
    $scope.filterParams = {fields: 'paths,color,genericColor', itemOnce: 'true'};
    $scope.filterSetFields = {fields: 'color,paths'};
    $scope.filterFields = {fields: 'color,paths'};
    $scope.countryParams = {fields: 'geoname'};
    $scope.countryFields = {fields: 'geoname.questionnaires,geoname.questionnaires.survey,geoname.questionnaires.survey.questions,geoname.questionnaires.survey.questions.type,geoname.questionnaires.survey.questions.filter'};
    $scope.questionnaireWithQTypeFields = {fields: 'survey.questions,survey.questions.type'};
    $scope.questionnaireWithAnswersFields = {fields: 'permissions,geoname.country,survey.questions,survey.questions.filter,survey.questions.answers,survey.questions.answers.questionnaire,survey.questions.answers.part'};
    $scope.surveyFields = {fields: 'questionnaires.survey,questionnaires.survey.questions,questionnaires.survey.questions.type,questionnaires.survey.questions.filter'};


    // Variables initialisations
    $scope.isLoading = false;
    $scope.expandSelection = true;
    $scope.lastFilterId = 1;
    $scope.firstQuestionnairesRetrieve = false; // avoid to compute filters before questionnaires have been retrieved, getComputedFilters() need ready base to complete data
    $scope.tabs = {};
    $scope.parts = Restangular.all('part').getList().$object;
    $scope.modes = ['Browse', 'Contribute'];
    $scope.surveysTemplate = "[[item.code]] - [[item.name]]";
    $scope.filtersTemplate = "" +
        "<div>" +
            "<div class='col-sm-4 col-md-4 select-label select-label-with-icon'>" +
            "    <i class='fa fa-gims-filter' style='color:[[item.color]];' ></i> [[item.name]]" +
            "</div>" +
            "<div class='col-sm-7 col-md-7'>" +
            "    <small>" +
            "       [[_.map(item.paths, function(path){return \"<div class='select-label select-label-with-icon'><i class='fa fa-gims-filter'></i> \"+path+\"</div>\";}).join('')]]" +
            "    </small>" +
            "</div>" +
            "<div class='clearfix'></div>" +
        "</div>";

    /**************************************************************************/
    /*************************************************************** Watchers */
    /**************************************************************************/

    $scope.$watch(function() {
        return $location.url();
    }, function() {
        $scope.returnUrl = $location.search().returnUrl;
        $scope.currentUrl = encodeURIComponent($location.url());
    });

    $scope.$watch('sector', function() {
        if ($scope.sector) {
            $scope.max = 10000000000;
        } else {
            $scope.max = 1;
        }
    });

    $scope.$watch('mode', function(mode) {
        if (!_.isUndefined(mode) && mode != 'Browse') {
            // Make a call that require to be authenticated, then UserCtrl catch 401 and fire event gims-loginConfirmed when it's done
            Restangular.all('user').getList();
            // listen to event gims-loginConfirmed to refresh questionnaires permissions, considering logged in user
            $rootScope.$on('gims-loginConfirmed', function() {
                $scope.refresh(true, false);
            });

        }
    });

    $scope.$watch('tabs.filterSet', function() {
        if ($scope.tabs.filterSet) {
            $scope.isLoading = true;
            Restangular.one('filterSet', $scope.tabs.filterSet.id).getList('filters', _.merge($scope.filterSetFields, {perPage: 1000})).then(function(filters) {
                if (filters) {
                    $scope.tabs.filters = filters;
                    $scope.tabs.filter = null;
                }
                $scope.isLoading = false;
                checkSelectionExpand();
            });
        }
    });

    $scope.$watch('tabs.filter', function() {
        if ($scope.tabs.filter) {
            $scope.isLoading = true;
            Restangular.one('filter', $scope.tabs.filter.id).getList('children', _.merge($scope.filterFields, {perPage: 1000})).then(function(filters) {
                if (filters) {
                    $scope.tabs.filters = filters;
                    $scope.tabs.filterSet = null;
                }
                $scope.isLoading = false;
                checkSelectionExpand();
            });
        }
    });

    $scope.$watch('tabs.country', function() {
        if ($scope.tabs.country) {
            $scope.isLoading = true;
            Restangular.one('country', $scope.tabs.country.id).get(_.merge($scope.countryFields, {perPage: 1000})).then(function(country) {
                $scope.tabs.questionnaires = country.geoname.questionnaires;
                $scope.tabs.survey = null;
                $scope.isLoading = false;
                checkSelectionExpand();
            });
        }
    });

    $scope.$watch('tabs.survey', function() {
        if ($scope.tabs.survey) {
            $scope.isLoading = true;
            Restangular.one('survey', $scope.tabs.survey.id).get(_.merge($scope.surveyFields, {perPage: 1000})).then(function(survey) {
                $scope.isLoading = false;
                $scope.tabs.questionnaires = survey.questionnaires;
                $scope.tabs.country = null;
                checkSelectionExpand();
            });
        }
    });

    var firstLoading = true;
    $scope.$watch('tabs.filters', function(newFilters, oldFilters) {
        removeUnUsedQuestions(newFilters, oldFilters);
        fillMissingElements();
        getComputedFilters();
        if (firstLoading === true && $scope.tabs.filters && $scope.tabs.questionnaires) {
            checkSelectionExpand();
        }
    });

    $scope.$watchCollection('tabs.filters', function() {
        prepareSectorFilters();
    });

    $scope.$watch('tabs.questionnaires', function(newQuests, oldQuests) {
        var newQuestionnaires = _.difference(_.pluck(newQuests, 'id'), _.pluck(oldQuests, 'id'));
        newQuestionnaires = newQuestionnaires ? newQuestionnaires : [];

        if (!_.isEmpty(newQuestionnaires)) {
            getQuestionnaires(newQuestionnaires, $scope.questionnaireWithQTypeFields).then(function(questionnaires) {
                checkGlassQuestionnaires(questionnaires);
            });
        }

        if (firstLoading === true && $scope.tabs.filters && $scope.tabs.questionnaires) {
            checkSelectionExpand();
        }
    });

    /**************************************************************************/
    /******************************************************** Scope functions */
    /**************************************************************************/

    /**
     * Refreshing page means :
     *  - Recover all questionnaires permissions (in case user swith from browse to contribute/full view and need to be logged in)
     *  - Recompute filters, after some changes on answers. Can be done automatically after each answer change, but is heavy.
     * @param questionnaires
     */
    $scope.refresh = function(questionnairesPermissions, filtersComputing) {

        if (questionnairesPermissions && !_.isUndefined($scope.tabs) && !_.isUndefined($scope.tabs.questionnaires)) {
            getQuestionnaires($scope.tabs.questionnaires, {fields: 'permissions'}).then(function(questionnaires) {
                updateQuestionnairePermissions(questionnaires);
            });
        }

        if (filtersComputing) {
            getComputedFilters();
        }
    };

    /**
     * Call api to get answer permissions
     * @param answer
     * @param callback
     */
    $scope.getPermissions = function(answer, callback) {

        if (answer.id && _.isUndefined(answer.permissions)) {
            Restangular.one('answer', answer.id).get({fields: 'permissions'}).then(function(newAnswer) {
                answer.permissions = newAnswer.permissions;

                // if value has been updated between permissions check, restore value
                if (!answer.permissions.update) {
                    answer.valuePercent = newAnswer.valuePercent;
                }
                if (callback) {
                    callback(answer);
                }

            }, function() {
                answer.isLoading = false;
                answer.permissions = {
                    create: false,
                    read: false,
                    update: false,
                    delete: false
                };
            });
        }
    };

    /**
     * Detect if there are empty questionnaires to display button "generate"
     * @returns {boolean}
     */
    $scope.isEmptyQuestionnaires = function() {
        var isEmptyQuestionnaires = false;
        _.forEachRight($scope.tabs.questionnaires, function(questionnaire) {
            if (_.isUndefined(questionnaire.id)) {
                isEmptyQuestionnaires = true;
                return false;
            }
        });
        return isEmptyQuestionnaires;
    };

    /**
     * Detect if there are empty filters to display button "generate"
     * @returns {boolean}
     */
    $scope.isEmptyFilters = function() {
        var filters = _.filter($scope.tabs.filters, function(f) {
            if (/^_.*/.test(f.id)) {
                return f;
            }
        });
        if (filters.length) {
            return true;
        } else {
            return false;
        }
    };

    /**
     * Update an answer
     * Create an answer and if needed the question related
     * @param answer
     * @param question
     * @param filter
     * @param questionnairePermissions
     */
    $scope.saveAnswer = function(answer, question, filter, questionnaire, part) {

        // complete question in all situations with filtername if there is no name specified
        if (_.isUndefined(question.name)) {
            question.name = filter.name;
        }

        // avoid to do some job if the value is not changed or if it's invalid (undefined)
        if (answer.initialValue === answer.valuePercent || (_.isUndefined(answer.valuePercent) && !_.isUndefined(answer.initialValue))) {
            answer.valuePercent = answer.initialValue;
            return;
        }

        // avoid to save questions when its a new questionnaire / survey
        // the save is provided by generate button for all new questionnaires, surveys, questions and answers.
        if (_.isUndefined(questionnaire.id)) {
            return;
        }

        Restangular.restangularizeElement(null, answer, 'answer');
        Restangular.restangularizeElement(null, question, 'question');

        // delete answer if no value
        if (answer.id && !$scope.toBoolNum(answer.valuePercent)) {
            $scope.removeAnswer(answer);

            // update
        } else if (answer.id) {

            if (answer.permissions) {
                updateAnswer(answer);
            } else {
                $scope.getPermissions(answer, updateAnswer);
            }

            // create answer, if allowed by questionnaire
        } else if (_.isUndefined(answer.id) && !_.isUndefined(answer.valuePercent) && questionnaire.permissions.create) {
            answer.isLoading = true;
            answer.questionnaire = questionnaire.id;
            question.survey = questionnaire.survey.id;
            // if question is not created, create it before creating the answer
            getOrSaveQuestion(question).then(function(question) {
                answer.question = question.id;
                answer.part = part.id;
                createAnswer(answer).then(function() {
                    $scope.refresh(false, true);
                });

            });
        }
    };

    /**
     * Save one questionnaire if index is specified or all if it's not.
     * @param index
     */
    $scope.saveQuestionnaires = function(index) {

        $scope.checkQuestionnairesIntegrity().then(function() {
            saveFilters().then(function() {
                if (_.isUndefined(index)) {
                    var questionnairesToSave = _.filter($scope.tabs.questionnaires, $scope.checkIfSavableQuestionnaire);
                    saveAllQuestionnairesWhenQuestionsAreSaved(questionnairesToSave, 0);
                } else {
                    if ($scope.checkIfSavableQuestionnaire($scope.tabs.questionnaires[index])) {
                        saveCompleteQuestionnaire($scope.tabs.questionnaires[index]);
                    }
                }
            });
        });
    };

    /**
     * Check if a questionnaire is ready for save (has code, year, geoname and no errors
     * @param questionnaire
     * @returns {*}
     */
    $scope.checkIfSavableQuestionnaire = function(questionnaire) {
        if (_.isUndefined(questionnaire.id) && !_.isUndefined(questionnaire.geoname) && !_.isUndefined(questionnaire.geoname.country) && !_.isUndefined(questionnaire.survey) && !_.isEmpty(questionnaire.survey.code) && !_.isUndefined(questionnaire.survey.year) && !hasErrors(questionnaire)) {
            return questionnaire;
        }
    };

    /**
     * Avoid new questionnaires to have the same country for a same survey and avoid a same survey code to have two different years
     */
    $scope.checkQuestionnairesIntegrity = function() {
        var deferred = $q.defer();

        // check for countries
        $timeout(function() {
            if (_.isEmpty($scope.tabs.questionnaires)) {
                deferred.resolve();
            } else {
                _.forEach($scope.tabs.questionnaires, function(q1, i) {
                    if (_.isUndefined(q1.id)) {
                        q1.errors = {
                            duplicateCountryCode: false,
                            codeAndYearDifferent: false,
                            countryAlreadyUsedForExistingSurvey: false
                        };

                        _.forEach($scope.tabs.questionnaires, function(q2, j) {
                            if (_.isUndefined(q2.id) && i != j) {
                                if (q1.geoname && q2.geoname && q1.geoname.country.id == q2.geoname.country.id) {
                                    if (q1.survey.code && q2.survey.code && q1.survey.code == q2.survey.code) {
                                        q1.errors.duplicateCountryCode = true;
                                    }
                                } else {

                                    if (q1.survey.year && q2.survey.year && q1.survey.year != q2.survey.year && q1.survey.code == q2.survey.code) {
                                        q1.errors.codeAndYearDifferent = true;
                                    }
                                }
                            }
                        });
                    }
                    deferred.resolve();
                });
            }
        }, 0);

        return deferred.promise;
    };

    /**
     * Select next view mode
     */
    $scope.nextMode = function() {
        _.forEach($scope.modes, function(mode, index) {
            if (mode === $scope.mode) {
                if (index === $scope.modes.length - 1) {
                    $scope.mode = $scope.modes[0];
                } else {
                    $scope.mode = $scope.modes[index + 1];
                }

                return false;
            }
        });
    };

    /**
     * Set the value of a input (ng-model) before the value is changed
     * Used in function saveAnswer().
     * Avoid to do some ajax requests when we just blur field without changing value.
     * @param answer
     */
    $scope.setInitialValue = function(answer) {
        answer.initialValue = answer.valuePercent;
    };

    /**
     * Check if given value is positive number including 0, to avoid 0 to be interpreted as null in the template side
     * @param val
     * @returns {boolean}
     */
    $scope.toBoolNum = function(val) {
        if (_.isNumber(val) && val >= 0) {
            return true;
        }
        return false;
    };
    /**
     * Save question if it has a name
     * @param question
     * @param survey
     */
    $scope.saveQuestion = function(question, survey) {
        if (question.id && !_.isEmpty(question.name) && question.name != question.initialName) {

            var questionnairesWithSameLabel = _.filter($scope.tabs.questionnaires, function(q) {
                if (q.survey.code.toUpperCase() == survey.code.toUpperCase()) {
                    return q;
                }
            });

            Restangular.restangularizeElement(null, question, 'question');
            $scope.isLoading = true;
            question.put().then(function() {
                $scope.isLoading = false;
            });

            _.forEach(questionnairesWithSameLabel, function(questionnaire) {
                questionnaire.survey.questions[question.filter.id].name = question.name;
            });
        }
    };

    /**
     * Recovers existing data from DB when and existing code/year/country are setted in empty questionnaire
     * @param questionnaire
     */
    $scope.completeQuestionnaire = function(questionnaire) {
        if (_.isUndefined(questionnaire.id)) {
            getSurvey(questionnaire).then(function(data) {
                if (data.survey && data.survey.year) {
                    questionnaire.survey.year = data.survey.year;
                    propagateQuestionLabels(questionnaire, data.survey.questions);
                    updateUrl('questionnaires');
                }

                if (data.questionnaire && data.questionnaire.id) {
                    questionnaire.id = data.questionnaire.id;
                    questionnaire.survey.id = data.survey.id;
                    getQuestionnaires([data.questionnaire.id
                    ], $scope.questionnaireWithAnswersFields).then(function(questionnaires) {
                        $scope.firstQuestionnairesRetrieve = true;
                        prepareDataQuestionnaires(questionnaires);
                        updateUrl('questionnaires');
                    });
                }

            }, function(error) {
                if (_.isUndefined(questionnaire.errors)) {
                    questionnaire.errors = {};
                }
                if (error.code == 1) {
                    questionnaire.errors.surveyExistWithDifferentYear = true;
                    questionnaire.survey.existingYear = error.year;
                }
            });
        }
    };

    /**
     * Remove question after retrieving permissions from server if not yet done
     * @param answer
     */
    $scope.removeAnswer = function(answer) {
        Restangular.restangularizeElement(null, answer, 'answer');
        if (_.isUndefined(answer.permissions)) {
            $scope.getPermissions(answer, deleteAnswer);
        } else {
            deleteAnswer(answer);
        }
    };

    /**
     * Add column (questionnaire)
     */
    $scope.addQuestionnaire = function() {
        if (_.isUndefined($scope.tabs.questionnaires)) {
            $scope.tabs.questionnaires = [];
        }
        $scope.tabs.questionnaires.push({});
        fillMissingElements();
        updateUrl('questionnaires');
    };

    /**
     * Remove column (questionnaire)
     * @param index
     */
    $scope.removeQuestionnaire = function(index) {
        $scope.tabs.questionnaires.splice(index, 1);
        updateUrl('questionnaires');
    };

    /**
     * Remove row (filter)
     * @param index
     */
    $scope.removeFilter = function(index) {

        var id = $scope.tabs.filters[index].id;
        $scope.tabs.filters.splice(index, 1);
        removeQuestionsForFilter(id, false);

        if ($scope.sector) {
            var temporaryFilterIdRegex = new RegExp('_' + id + "_");
            // remove temporary children sector filters
            $scope.tabs.filters = _.filter($scope.tabs.filters, function(filter) {
                if (!temporaryFilterIdRegex.test(filter.id)) {
                    return filter;
                }
            });
            if (!_.isEmpty($scope.tabs.questionnaires)) {
                removeQuestionsForFilter(temporaryFilterIdRegex, true);
            }
        }
        updateUrl('filters');
    };

    /**
     * Add a filter to list
     * Filters that have been added must have and ID because the questions are indexed on their filter id,
     * This function assigns an arbitrary ID starting with and anderscore that is replaced on save.
     * This underscored id is used for children filters that need a reference to parents.
     */
    $scope.addFilter = function() {
        if (_.isUndefined($scope.tabs.filters)) {
            $scope.tabs.filters = [];
        }
        $scope.tabs.filters.push({
            id: "_" + $scope.lastFilterId++,
            level: 0,
            parents: [$scope.tabs.filter.id],
            sector: true
        });
    };

    /**
     * Create a filterset with a filter using the same name. Is used on sector mode.
     */
    $scope.createFilterSet = function() {
        $scope.isLoading = true;
        Restangular.all('filterSet').post($scope.tabs.newFilterSet).then(function(filterSet) {
            var filter = {
                name: $scope.tabs.newFilterSet.name,
                filterSets: [filterSet.id],
                color: '#FF0000'
            };
            saveFilter(filter).then(function(filter) {
                $scope.tabs.filter = filter;
                $scope.tabs.view = true;
                $scope.tabs.create = false;
                $scope.tabs.createDisabled = true;
                $scope.isLoading = true;
            });
        });
    };

    /**
     * Detects if a ID is temporary (with underscore) or not. Used to detect unsaved filters.
     * @param filter
     * @returns {boolean}
     */
    $scope.isValidId = function(filter) {
        if (_.isUndefined(filter.id) || /_\d+/.test(filter.id)) {
            return false;
        } else {
            return true;
        }
    };

    /**************************************************************************/
    /****************************************************** Private functions */
    /**************************************************************************/

    /**
     * Multiple questionnaires may have the same survey, but the questions are linked to questionnaires with the right answers.
     * So questions may be in multiple questionnaires but they have bo be synced for labels and id. A filter is supposed to have only one question.
     * This function propagates modifications on other questionnaires that have the same code.
     * @param questionnaire
     * @param questions
     */
    var propagateQuestionLabels = function(questionnaire, questions) {
        _.forEach(questions, function(question) {
            if (questionnaire.survey.questions[question.filter.id]) {
                questionnaire.survey.questions[question.filter.id].name = question.name;
            }
        });

    };

    /**
     * Call questionnaires asking for passed fields and executing callback function passing received questionnaires
     * @param questionnaires
     * @param fields
     * @param callback
     */
    var getQuestionnaires = function(questionnaires, fields) {
        var deferred = new $q.defer();

        if (questionnaires.length === 1 && !_.isUndefined(questionnaires[0])) {
            $scope.isLoading = true;
            Restangular.one('questionnaire', questionnaires[0]).get(fields).then(function(questionnaire) {
                $scope.isLoading = false;
                deferred.resolve([questionnaire]);
            });
        } else if (questionnaires.length > 1) {
            $scope.isLoading = true;
            Restangular.all('questionnaire').getList(_.merge({id: questionnaires.join(',')}, fields)).then(function(questionnaires) {
                $scope.isLoading = false;
                deferred.resolve(questionnaires);

            });
        }

        return deferred.promise;
    };

    /**
     * Check glass questiionnaires and add them to a specific array that add a tab
     * If there is only one Glass questionnaire and no JMP, a redirection display the questionnaire
     * @param questionnaires
     */
    var checkGlassQuestionnaires = function(questionnaires) {
        var glass = [];
        var jmp = [];
        angular.forEach(questionnaires, function(questionnaire) {
            if (_.find(questionnaire.survey.questions, function(question) {
                return question.type != 'Numeric';
            })) {
                glass.push(questionnaire);
            } else {
                jmp.push(questionnaire);
            }
        });

        // if there is only 1 glass in all selected questionnaires, consider that user want to edit this one, and redirect to glass template
        // no action is possible on multiple glass questionnaires (neigther browse or contribute)
        if (glass.length === 1 && $scope.tabs.questionnaires.length === 1) {
            $location.url('/contribute/questionnaire/glass/' + glass[0].id + "?returnUrl=" + $location.path());

            // else list glass questionnaires apart
        } else {
            $scope.tabs.glass = glass;

            // remove glass questionnaires from selected questionnaires
            $scope.tabs.questionnaires = _.filter($scope.tabs.questionnaires, function(q) {
                if (!_.find(glass, {id: q.id})) {
                    return true;
                }
                return false;
            });

            // get data for new jmp questionnaires
            getQuestionnaires(_.pluck(jmp, 'id'), $scope.questionnaireWithAnswersFields).then(function(questionnaires) {
                $scope.firstQuestionnairesRetrieve = true;
                prepareDataQuestionnaires(questionnaires);
            });
        }
    };

    /**
     * Index answers by part and questions by filters on questionnaire that have data from DB
     * @param questionnaires
     */
    var prepareDataQuestionnaires = function(questionnaires) {
        angular.forEach(questionnaires, function(questionnaire) {

            _.forEach(questionnaire.survey.questions, function(question) {
                if (question.answers) {
                    // class answers by part id
                    var answers = {};
                    _.forEach(question.answers, function(answer) {
                        if (!_.isUndefined(answer.questionnaire) && answer.questionnaire.id == questionnaire.id) {
                            delete(answer.questionnaire);
                            answers[answer.part.id] = answer;
                        }
                    });
                    question.answers = answers;
                }
            });

            questionnaire.survey.questions = _.indexBy(questionnaire.survey.questions, function(q) {
                return q.filter.id;
            });

            // update $scope with modified questionnaire
            $scope.tabs.questionnaires[_.findIndex($scope.tabs.questionnaires, {id: questionnaire.id})] = questionnaire;
        });

        fillMissingElements();
        getComputedFilters();
    };

    /**
     * Called when a new questionnaire is added or filters are changed.
     * Ensure there is empty objects to grand app to work fine (e.g emptyanswers have to exist before ng-model assigns a value)
     */
    var fillMissingElements = function() {
        if ($scope.tabs.questionnaires && $scope.tabs.filters) {
            _.forEach($scope.tabs.questionnaires, function(questionnaire) {

                if (_.isUndefined(questionnaire.survey)) {
                    questionnaire.survey = {};
                }

                if (_.isUndefined(questionnaire.permissions)) {
                    questionnaire.permissions = {create: true};
                }

                if (_.isUndefined(questionnaire.survey.questions)) {
                    questionnaire.survey.questions = {};
                }

                _.forEach($scope.tabs.filters, function(filter) {
                    if (_.isUndefined(questionnaire.survey.questions[filter.id])) {
                        questionnaire.survey.questions[filter.id] = {
                            filter: {id: filter.id},
                            parts: [1, 2, 3],
                            type: 'Numeric'
                        };
                    }
                    if (_.isUndefined(questionnaire.survey.questions[filter.id].answers)) {
                        questionnaire.survey.questions[filter.id].answers = {};
                    }

                    _.forEach($scope.parts, function(part) {
                        questionnaire.survey.questions[filter.id].answers[part.id] = getEmptyAnswer(questionnaire.survey.questions[filter.id].answers[part.id], questionnaire.id, questionnaire.survey.questions[filter.id].id, part.id);
                    });
                });

            });
        }
    };

    /**
     * Update questionnaires permissions
     * @type Function
     * @param questionnaires
     */
    var updateQuestionnairePermissions = function(questionnaires) {
        _.forEach($scope.tabs.questionnaires, function(questionnaire) {
            questionnaire.permissions = _.find(questionnaires, {id: questionnaire.id}).permissions;
        });
    };

    /**
     * Init computed filters
     * @type {null}
     */
    var getComputedFiltersCanceller = null;
    var getComputedFilters = function() {
        if (!$scope.firstQuestionnairesRetrieve || $scope.sector) {
            return;
        }

        $timeout(function() {
            var filtersIds = _.map($scope.tabs.filters, function(el) {
                return el.id;
            });
            var questionnairesIds = _.map($scope.tabs.questionnaires, function(el) {
                return el.id;
            });

            if (filtersIds.length > 0 && questionnairesIds.length > 0) {
                $scope.isLoading = true;
                $scope.isComputing = true;

                if (getComputedFiltersCanceller) {
                    getComputedFiltersCanceller.resolve();
                }
                getComputedFiltersCanceller = $q.defer();

                $http.get('/api/filter/getComputedFilters', {
                    timeout: getComputedFiltersCanceller.promise,
                    params: {
                        filters: filtersIds.join(','),
                        questionnaires: _.filter(questionnairesIds,function(el) {
                            if (el) {
                                return el;
                            }
                        }).join(',')
                    }
                }).success(function(questionnaires) {

                        _.forEach($scope.tabs.questionnaires, function(scopeQuestionnaire) {
                            if (!_.isUndefined(questionnaires[scopeQuestionnaire.id])) {
                                _.forEach(questionnaires[scopeQuestionnaire.id], function(values, filterId) {
                                    scopeQuestionnaire.survey.questions[filterId].filter.values = values;
                                });
                            }
                        });

                        $scope.isLoading = false;
                        $scope.isComputing = false;
                    });
            }
        }, 0);
    };

    /**
     * Create a questionnaire, recovering or creating related survey and questions
     * @param questionnaire
     */
    var saveCompleteQuestionnaire = function(questionnaire) {
        var deferred = $q.defer();

        questionnaire.isLoading = true;

        // get survey if exists or create
        getOrSaveSurvey(questionnaire).then(function(survey) {
            questionnaire.survey = survey;

            // create questionnaire
            saveUnitQuestionnaire(questionnaire).then(function(newQuestionnaire) {
                questionnaire.id = newQuestionnaire.id;
                questionnaire.isLoading = true;
                updateSurveysWithSameCode(survey);
                updateUrl('questionnaires');

                // create questions
                var nbQuestionsSaved = 0;
                var questionsForSave = _.filter(questionnaire.survey.questions, function(q) {
                    if (q.name) {
                        return q;
                    }
                });
                if (questionsForSave.length === 0) {
                    deferred.notify();
                } else {
                    _.forEach(questionsForSave, function(question) {
                        getOrSaveQuestion(question).then(function(newQuestion) {
                            question = newQuestion;
                            updateSurveysWithSameCode(survey);
                            nbQuestionsSaved++;

                            if (nbQuestionsSaved == questionsForSave.length) {
                                updateSurveysWithSameCode(survey);
                                deferred.notify('Questions recovered'); // says to promise listener he can save next questionnaire
                            }

                            var nbAnswersSaved = 0;
                            _.forEach(question.answers, function(answer, partId) {
                                answer.questionnaire = questionnaire.id;
                                createAnswer(answer, partId).then(function() {
                                    nbAnswersSaved++;
                                    if (nbAnswersSaved == question.answers.length) {
                                        deferred.resolve(questionnaire);
                                    }
                                });
                            });
                        });
                    });
                }
            });

            // reject for survey creation
        }, function() {
            questionnaire.isLoading = false;
        });

        return deferred.promise;
    };

    /**
     * Recursive function that save all questionnaires
     * Instaed of saving all questionnaires at the same time, wait for the questions to be saved in case a future questionnaire use the same survey and the same questions.
     * This avoid try to create questions twice and cause conflict.
     * @param questionnaires
     * @param index
     */
    var saveAllQuestionnairesWhenQuestionsAreSaved = function(questionnaires, index) {
        if (questionnaires[index]) {
            saveCompleteQuestionnaire(questionnaires[index]).then(function() {
            }, function() {
            }, function() {
                saveAllQuestionnairesWhenQuestionsAreSaved(questionnaires, index + 1);
            });
        }
    };

    /**
     * When recovering data from BD, propagates this data on survey that have the same code.
     * @param survey
     */
    var updateSurveysWithSameCode = function(survey) {

        _.forEach($scope.tabs.questionnaires, function(questionnaire) {
            if (questionnaire.survey.code.toUpperCase() == survey.code.toUpperCase()) {
                questionnaire.survey.id = survey.id;

                // init current questions id and names to match with those in the existing survey
                _.forEach(survey.questions, function(question) {
                    if (question.id) {
                        _.forEach(questionnaire.survey.questions[question.filter.id].answers, function(answer) {
                            answer.question = question.id;
                        });
                    }
                    questionnaire.survey.questions[question.filter.id].name = question.name;
                    questionnaire.survey.questions[question.filter.id].survey = survey.id;
                });

            }
        });
    };

    /**
     * Create a questionnaire object in database.
     * @param questionnaire
     * @returns promise
     */
    var saveUnitQuestionnaire = function(questionnaire) {
        var deferred = $q.defer();

        if (_.isUndefined(questionnaire.id)) {

            // create a mini questionnaire object, to avoid big amounts of data to be sent to server
            var miniQuestionnaire = {
                dateObservationStart: questionnaire.survey.year + '-01-01',
                dateObservationEnd: questionnaire.survey.year + '-12-31',
                geoname: questionnaire.geoname.country.geoname.id,
                survey: questionnaire.survey.id
            };

            Restangular.all('questionnaire').post(miniQuestionnaire, {fields: 'permissions'}).then(function(newQuestionnaire) {
                questionnaire.id = newQuestionnaire.id;
                deferred.resolve(questionnaire);
            });
        }

        return deferred.promise;
    };

    /**
     * Get survey, or create it if dont exist
     * @param survey
     */
    var getOrSaveSurvey = function(questionnaire) {
        var deferred = $q.defer();
        var survey = questionnaire.survey;

        getSurvey(questionnaire).then(function(data) {

            // same survey exists
            if (data.survey) {
                survey.id = data.survey.id;

                // init current questions id and names to match with those existing in the db
                _.forEach(data.survey.questions, function(question) {
                    if (!_.isUndefined(survey.questions[question.filter.id])) {
                        survey.questions[question.filter.id].id = question.id;
                        survey.questions[question.filter.id].name = question.name;
                    }
                });
                deferred.resolve(survey);

                // no survey exists, create it
            } else {
                survey.name = survey.code + " - " + survey.year;
                Restangular.all('survey').post(survey).then(function(newSurvey) {
                    survey.id = newSurvey.id;
                    deferred.resolve(survey);
                });
            }

            // catch reject result
        }, function(error) {
            if (_.isUndefined(questionnaire.errors)) {
                questionnaire.errors = {};
            }
            if (error.code == 1) {
                questionnaire.errors.surveyExistWithDifferentYear = true;
                questionnaire.survey.existingYear = error.year;
            }
            deferred.reject();
        });

        return deferred.promise;
    };

    /**
     * This function recovers surveys by searching with Q params
     * If there is similar code, search if the country is already used
     * @param questionnaire
     * @returns null|survey Return null if no survey exists, returns the survey if exist or reject promise if country already used
     */
    var getSurvey = function(questionnaire) {
        var deferred = $q.defer();

        if (_.isUndefined(questionnaire.survey.id) && !_.isEmpty(questionnaire.survey.code)) {

            Restangular.all('survey').getList({q: questionnaire.survey.code, perPage: 1000, fields: 'questions,questions.filter,questionnaires,questionnaires.geoname,questionnaires.geoname.country'}).then(function(surveys) {
                if (surveys.length === 0) {
                    deferred.resolve({survey: null, questionnaire: null});
                } else {
                    var existingSurvey = _.find(surveys, function(s) {
                        if (s.code.toUpperCase() == questionnaire.survey.code.toUpperCase()) {
                            return true;
                        }
                    });

                    if (existingSurvey) {
                        // if wanted survey has no year, return found survey and questionnaire if found
                        if (_.isUndefined(questionnaire.survey.year) || !_.isUndefined(questionnaire.survey.year) && existingSurvey.year == questionnaire.survey.year) {

                            var existingQuestionnaire = null;
                            if (!_.isUndefined(questionnaire.geoname)) {
                                existingQuestionnaire = _.find(existingSurvey.questionnaires, function(q) {
                                    if (questionnaire.geoname.country.id == q.geoname.country.id) {
                                        return q;
                                    }
                                });
                            }

                            deferred.resolve({
                                survey: existingSurvey,
                                questionnaire: existingQuestionnaire
                            });

                            // if wanted survey has a different year, return an error
                        } else if (existingSurvey.year != questionnaire.survey.year) {
                            deferred.reject({code: 1, year: existingSurvey.year});
                        }

                        // else, there is not recoverable survey
                    } else {
                        deferred.resolve({survey: null, questionnaire: null});
                    }
                }
            });

        } else {
            deferred.resolve({survey: questionnaire.survey, questionnaire: questionnaire});
        }

        return deferred.promise;
    };

    /**
     * Get and empty answer ready to be used as model and with right attributs setted
     */
    var getEmptyAnswer = function(answer, questionnaire, question, part) {
        answer = answer ? answer : {};

        if (questionnaire) {
            answer.questionnaire = questionnaire;
        }
        if (question) {
            answer.question = question;
        }
        if (part) {
            answer.part = part;
        }

        return answer;
    };

    /**
     * Update answers considering answer permissions
     * @param answer
     */
    var updateAnswer = function(answer) {
        if (answer.id && answer.permissions.update) {
            answer.isLoading = true;
            answer.put().then(function() {
                answer.isLoading = false;
                $scope.refresh(false, true);
            });
        }
    };

    /**
     * Delete answer considering answer permissions
     * @param answer
     */
    var deleteAnswer = function(answer) {
        if (answer.id && answer.permissions.delete) {
            answer.remove().then(function() {
                delete(answer.id);
                delete(answer.valuePercent);
                delete(answer.edit);
                $scope.refresh(false, true);
            });
        }
    };

    /**
     * Create answer considering *questionnaire* permissions
     * @param answer
     */
    var createAnswer = function(answer, partId) {
        var deferred = $q.defer();

        if (!_.isUndefined(answer.valuePercent) && !_.isNull(answer.valuePercent)) {
            if (!_.isUndefined(partId)) {
                answer.part = partId;
            }

            answer.isLoading = true;
            Restangular.all('answer').post(answer, {fields: 'permissions'}).then(function(newAnswer) {
                answer.id = newAnswer.id;
                answer.isLoading = false;
                deferred.resolve(answer);
            });
        } else {
            deferred.reject('no value');
        }

        return deferred.promise;
    };

    /**
     * use question or save if necessary and return result
     * @param question
     */
    var getOrSaveQuestion = function(question) {
        var deferred = $q.defer();

        // if no id, create
        if (_.isUndefined(question.id)) {
            var miniQuestion = {};
            miniQuestion.name = question.name;
            miniQuestion.survey = question.survey;
            miniQuestion.filter = question.filter.id;
            miniQuestion.type = 'Numeric';

            Restangular.all('question').post(miniQuestion).then(function(newQuestion) {
                question.id = newQuestion.id;
                deferred.resolve(question);
            });

            // else, do nothing
        } else {
            deferred.resolve(question);
        }

        return deferred.promise;
    };

    /**
     * Hide selection panels on :
     *  - survey selection
     *  - country selection
     *  - filter set selection
     *  - filter's children selection
     *  - page loading
     *
     *  If there are filter and questionnaires selected after this manipulation
     *  Don't hide selection panes if select with free selection tool on "Selected" tab.
     *  The button "Expand/Compress Selection" reflects this status and allow to change is again.
     */
    var checkSelectionExpand = function() {
        firstLoading = false;
        if ($scope.tabs.filters && $scope.tabs.filters.length && $scope.tabs.questionnaires && $scope.tabs.questionnaires.length) {
            $scope.expandSelection = false;
        } else {
            $scope.expandSelection = true;
        }
    };

    /**
     * In case sector parameter is detected in url, this function ensures each filter has sub filters dedicated to filter data (Usualy people and equipement but may be anything)
     */
    var prepareSectorFilters = function() {
        if ($scope.tabs.filters && $scope.sector) {

            var sectorFilters = _.filter($scope.tabs.filters, function(f) {
                if (!f.sectorChild) {
                    return f;
                }
            });
            var sectorChildrenNames = $route.current.params.sectorChildren.split(',');

            _.forEachRight(sectorFilters, function(filter) {
                var childSectorFilters = _.filter($scope.tabs.filters, function(f) {
                    if (f.parents && f.parents[0] && f.parents[0] == filter.id) {
                        return f;
                    }
                });
                if (childSectorFilters.length === 0) {
                    var sectorChildFilters = _.map(sectorChildrenNames, function(childSectorName, sectorIndex) {
                        return {
                            id: '_' + filter.id + "_" + sectorIndex,
                            name: childSectorName,
                            parents: [filter.id],
                            level: filter.level + 1,
                            sectorChild: true
                        };
                    });

                    var index = _.findIndex($scope.tabs.filters, {id: filter.id});
                    _.forEach(sectorChildFilters, function(filter, sectorIndex) {
                        $scope.tabs.filters.splice(index + sectorIndex + 1, 0, filter);
                    });
                }
            });
            fillMissingElements();
        }
    };

    /**
     * When removing a filter, this function remove related questions on questionnaires to ensure no unwanted operation on DB is made
     * @param newFilters
     * @param oldFilters
     */
    var removeUnUsedQuestions = function(newFilters, oldFilters) {
        var removedFilters = _.difference(_.pluck(oldFilters, 'id'), _.pluck(newFilters, 'id'));
        _.forEach(removedFilters, function(filterId) {
            removeQuestionsForFilter(filterId, false);
        });
    };

    var removeQuestionsForFilter = function(id, isRegex) {
        _.forEach($scope.tabs.questionnaires, function(questionnaire) {
            // only remove questions on new questionnaires, others have received data from DB and shouldn't be removed
            if (_.isUndefined(questionnaire.id)) {
                _.forEach(questionnaire.survey.questions, function(question, filterId) {
                    if (isRegex && id.test(filterId) || !isRegex && id == filterId) {
                        delete(questionnaire.survey.questions[filterId]);
                    }
                });
            }
        });
    };

    /**
     * Save all filters, first root ones, then children ones
     * @returns promise
     */
    var saveFilters = function() {
        var deferred = $q.defer();

        // get all filters with starting by _1
        var parentFilters = _.filter($scope.tabs.filters, function(filter) {
            if (/^_\d+/.test(filter.id)) {
                return filter;
            }
        });

        if (!_.isEmpty(parentFilters)) {
            $scope.isLoading = true;
            saveFiltersCollection(parentFilters).then(function() {
                $scope.isLoading = true;

                // get all filters with starting by __1
                var childrenFilters = _.filter($scope.tabs.filters, function(filter) {
                    if (/^__\d+/.test(filter.id)) {
                        return filter;
                    }
                });
                if (!_.isEmpty(parentFilters)) {
                    $scope.isLoading = true;
                    saveFiltersCollection(childrenFilters).then(function() {
                        $scope.isLoading = false;
                        deferred.resolve();
                    });
                }
            });
        }

        return deferred.promise;
    };

    /**
     * Save given collection of filters
     * @param filtersToSave
     * @returns promise
     */
    var saveFiltersCollection = function(filtersToSave) {
        var deferred = $q.defer();

        // get all filters with starting by _1 or __1
        if (filtersToSave.length === 0) {
            deferred.resolve();
        } else {
            var filterPromises = [];
            _.forEach(filtersToSave, function(filter) {
                filterPromises.push(saveFilter(filter));
            });
            $q.all(filterPromises).then(function() {
                $location.search('sectorChildren', null);
                $scope.sector = false;
                $scope.expandHierarchy = true;
                updateUrl('filters');
                deferred.resolve();
            });
        }

        return deferred.promise;
    };

    /**
     * Save a single filter
     * @param filter
     * @returns promise
     */
    var saveFilter = function(filter) {
        var deferred = $q.defer();

        if (filter.id) {
            filter.oldId = filter.id;
        }
        filter.isLoading = true;
        Restangular.all('filter').post({name: filter.name, parents: filter.parents}).then(function(newFilter) {
            filter.id = newFilter.id;
            filter.isLoading = false;
            delete(filter.sector);
            delete(filter.sectorChild);
            replaceQuestionsIds(filter.id, filter.oldId);
            replaceIdReferenceOnChildFilters(filter);
            deferred.resolve(filter);
        });

        return deferred.promise;
    };

    /**
     * When saving a filter with temporary url, we need to update question filter and index with new filter id.
     * @param id
     * @param oldId
     */
    var replaceQuestionsIds = function(id, oldId) {
        _.forEach($scope.tabs.questionnaires, function(questionnaire) {
            if (questionnaire.survey && questionnaire.survey.questions && questionnaire.survey.questions[oldId]) {
                questionnaire.survey.questions[id] = questionnaire.survey.questions[oldId];
                questionnaire.survey.questions[id].filter.id = id;
                delete(questionnaire.survey.questions[oldId]);
            }
        });
    };

    /**
     * Remplace id related to filters that have temporary id by the new ID returned by DB.
     * @param filter
     */
    var replaceIdReferenceOnChildFilters = function(filter) {
        var children = _.filter($scope.tabs.filters, function(f) {
            if (f.parents && f.parents[0] && f.parents[0] == filter.oldId) {
                return f;
            }
        });
        _.forEach(children, function(child) {
            child.parents[0] = filter.id;
        });
    };

    /**
     * Update parameters on url exlucding empty ids to avoid multiple consecutive commas that cause problems on server side.
     * @param element
     */
    var updateUrl = function(element) {
        $location.search(element, _.filter(_.pluck($scope.tabs[element], 'id'),function(el) {
            if (el) {
                return el;
            }
        }).join(','));
    };

    /**
     * Check if questionnaire has one or multiple errors
     * @param questionnaire
     * @returns {boolean}
     */
    var hasErrors = function(questionnaire) {
        var hasErrors = false;
        _.forEach(questionnaire.errors, function(potentialError) {
            if (potentialError) {
                hasErrors = true;
                return false;
            }
        });
        return hasErrors;
    };

    /* Redirect functions */
    var redirect = function() {
        $location.url($location.search().returnUrl);
    };

    $scope.cancel = function() {
        redirect();
    };

});
