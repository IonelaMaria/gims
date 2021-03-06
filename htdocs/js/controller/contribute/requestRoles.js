angular.module('myApp').controller('Contribute/RequestRolesCtrl', function($scope, $http, $location, $routeParams) {
    'use strict';
    $scope.types = {
        selected: []
    };

    $scope.surveyTypesList = [
        {
            value: 'jmp',
            display: 'JMP'
        }, {
            value: 'glaas',
            display: 'Glaas'
        }, {
            value: 'nsa',
            display: 'Nsa'
        }
    ];

    $scope.$watch('types.selected', function() {
        if ($scope.types.selected && $scope.types.selected.length) {
            $location.search('types', $scope.types.selected.join(','));
        } else {
            $location.search('types', null);
        }
    });

    if ($routeParams.types) {
        $scope.types.selected = $routeParams.types.split(',');
    }
    $scope.sending = false;
    $scope.alerts = [];
    $scope.sendRequest = function() {
        $scope.sending = true;
        $http.get('/api/roles-request/requestRoles', {
            params: {
                geonames: _.pluck($scope.geonames, 'id').join(','),
                roles: _.pluck($scope.roles, 'id').join(','),
                types: $scope.types.selected.join(',')
            }
        }).success(function() {
            $scope.geonames = [];
            $scope.roles = [];
            $scope.alerts.push({
                type: 'success',
                msg: 'The request was successfully sent. Come back later when your request has been approved, or request additional roles now.'
            });
        }).finally(function() {
            $scope.sending = false;
        });
    };

    $scope.closeAlert = function(index) {
        $scope.alerts.splice(index, 1);
    };

});
