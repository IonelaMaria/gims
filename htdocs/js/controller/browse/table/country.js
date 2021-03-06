angular.module('myApp').controller('Browse/Table/CountryCtrl', function($scope, $http, $location, Restangular, Utility, TableAssistant) {
    'use strict';

    // Init to empty
    $scope.columnDefs = [];
    $scope.tabs = {};
    $scope.tabs.years = $location.search().years;

    // Configure ui-grid.
    $scope.gridOptions = {
        data: 'table'
    };

    $scope.$watch('tabs.years', function() {
        if (_.isEmpty($scope.tabs.years)) {
            $location.search('years', null);
        } else {
            $location.search('years', $scope.tabs.years);
            slowRefresh();
        }
    });

    $scope.$watch('tabs.filterSets', function() {
        if ($scope.tabs.filterSets && $scope.tabs.filterSets.length) {
            $scope.tabs.filters = [];
            Restangular.one('filterSet').get({id: _.pluck($scope.tabs.filterSets, 'id').join(','), fields: 'filters.color', perPage: 1000}).then(function(data) {
                $scope.tabs.filters = Utility.getAttribute(data, 'filters', null);
                $scope.tabs.filter = null;
            });
        }
    });

    $scope.$watch('tabs.filter', function() {
        if ($scope.tabs.filter) {
            Restangular.one('filter', $scope.tabs.filter.id).getList('children.color', {perPage: 1000}).then(function(filters) {
                $scope.tabs.filters = filters;
                $scope.tabs.filterSets = null;
            });
        }
    });

    $scope.$watch('tabs.regions', function() {
        if ($scope.tabs.regions && $scope.tabs.regions.length) {
            $scope.tabs.geonames = [];
            Restangular.one('geoname').get({id: _.pluck($scope.tabs.regions, 'id').join(','), fields: 'children', perPage: 1000}).then(function(data) {
                $scope.tabs.geonames = $scope.tabs.regions;
                $scope.tabs.geonames = $scope.tabs.geonames.concat(_.uniq(Utility.getAttribute(data, 'children', 'read'), 'id'));
            });
        }
    });

    $scope.$watch('tabs.filters', function() {
        $scope.filtersIds = _.pluck($scope.tabs.filters, 'id').join(',');
        if ($scope.tabs.filters) {
            refresh();
        }
    });

    $scope.$watch('tabs.geonames', function() {
        $scope.geonamesIds = _.pluck($scope.tabs.geonames, 'id').join(',');
        if ($scope.tabs.geonames) {
            refresh();
        }
    });

    var slowRefresh = _.debounce(function() {
        refresh();
    }, 500);

    var refresh = _.debounce(function() {

        if ($scope.tabs.geonames && $scope.tabs.filters && $scope.tabs.years) {
            $scope.ready = false;

            // ... then, get table data via Ajax, but only once per 200 milliseconds
            // (this avoid sending several request on page loading)
            $http.get('/api/table/country', {
                params: {
                    filters: $scope.filtersIds,
                    geonames: $scope.geonamesIds,
                    years: $scope.tabs.years
                }

            }).success(function(data) {
                $scope.table = data.data;

                _.forEach(data.columns, function(column) {
                    if (!_.isUndefined(column.thematic)) {
                        column.cellTemplate = "<div class='text-right ui-grid-cell-contents'>{{row.entity[col.field]}}</div>";
                    }
                });

                $scope.gridOptions.columnDefs = data.columns;
                $scope.gridOptions.headerTemplate = TableAssistant.getHeaderTemplate(data.columns);
                $scope.ready = true;
            });
        }
    }, 1000);

});
