angular.module('myApp').controller('UserCtrl', function($scope, $http, $location, authService, $modal, $rootScope, requestNotification, $window, $analytics) {
    'use strict';
    $scope.getRequestCount = requestNotification.getRequestCount;

    function userLoggedIn(user) {
        if ($window.ga) {
            $window.ga('set', 'dimension1', user.id + ': ' + user.name);
            $analytics.eventTrack('logged', {category: 'login', label: 'logged'});
        }

        $scope.user = user;
        $rootScope.user = $scope.user;
        authService.loginConfirmed();
        $rootScope.$emit('gims-loginConfirmed', user);
    }

    // Reload existing logged in user (eg: when refreshing page)
    var loadUserPromise = $http.get('/user/login');
    loadUserPromise.success(function(data) {
        if (data.status == 'logged') {
            userLoggedIn(data);
        }
    });

    // Intercept the event broadcasted by http-auth-interceptor when a request get a HTTP 401 response
    $scope.$on('event:auth-loginRequired', function() {

        // If we already checked with server, but are still not logged in, need to show prompt
        loadUserPromise.then(function() {
            if (!$rootScope.user) {
                $scope.promptLogin();
            }
        });
    });

    $scope.promptLogin = function() {

        var modalInstance = $modal.open({
            templateUrl: '/template/application/index/login',
            controller: 'LoginWindowCtrl'
        });

        modalInstance.result.then(userLoggedIn);
    };

    if ($location.search().token) {
        $scope.promptLogin();
    }
});

angular.module('myApp').controller('LoginWindowCtrl', function($scope, $http, $modalInstance, $log, $rootScope, $location) {
    'use strict';

    $scope.state = 'normal';
    var token = $location.search().token;

    if (token) {
        var action = $location.search().action;

        if (action == 'activate') {
            $scope.state = 'activating';
            $http.get('/api/user/activate', {params: $location.search()}).then(function(data) {
                $location.search('token', null);
                $scope.state = 'activated';
                $scope.login.identity = data.data.email;
            }, function() {
                $scope.state = 'invalidToken';
            });

        } else if (action == 'changePassword') {
            $scope.state = 'changingPassword';
            $http.get('/api/user/checkTokenValidity', {params: $location.search()}).then(function(data) {
                $scope.login.name = data.data.name;
                $scope.login.identity = data.data.email;
                $scope.state = 'changePassword';
            }, function() {
                $scope.state = 'invalidToken';
            });
        }
    }

    $scope.changePassword = function() {
        $http.put('/user/change-password', {
            password: $scope.register.password,
            token: token
        }).success(function(data) {
            $location.search('token', null);
            $scope.state = 'passwordChanged';
            $scope.login.identity = data.email;
        });
    };

    function resetErrors() {
        $scope.invalidUsernamePassword = false;
        $scope.userExisting = false;
    }
    resetErrors();

    $scope.cancelLogin = function()
    {
        $modalInstance.dismiss();
    };

    $scope.login = {};
    $scope.register = {};

    $scope.sendLogin = function() {
        $scope.state = 'signing';
        resetErrors();
        $http.post('/user/login', $scope.login).success(function(data) {
            $scope.state = 'normal';
            if (data.status == 'logged')
            {
                $scope.invalidUsernamePassword = false;
                $scope.user = data;
                $rootScope.user = $scope.user;
                $modalInstance.close(data);
            } else if (data.status == 'failed')
            {
                $scope.invalidUsernamePassword = true;
            }
        }).error(function(data) {
            $scope.state = 'normal';
            $log.error('Server error', data);
        });
    };

    $scope.sendRegister = function() {
        $scope.state = 'registering';
        resetErrors();
        $http.post('/user/register', $scope.register).success(function(data) {
            $scope.state = 'registered';
            $scope.registeredEmail = data.email;
        }).error(function(data) {
            $scope.state = 'normal';

            if (data.message.email.recordFound) {
                $scope.userExisting = true;
            }
        });
    };

    $scope.sendChangePassword = function() {
        $scope.state = 'reseting';

        $http.get('/user/change-password', {params: {email: $scope.login.identity}}).success(function(data) {
            $scope.state = 'resetSent';
            $scope.resetPasswordForEmail = data.email;
        }).error(function(data) {
            $scope.state = 'normal';

            if (data.message) {
                $scope.userNotFound = true;
            }
        });
    };

    $scope.toLowerCase = function() {
        if ($scope.login.identity) {
            $scope.login.identity = $scope.login.identity.toLowerCase();
        }

        if ($scope.register.email) {
            $scope.register.email = $scope.register.email.toLowerCase();
        }
    };
});
