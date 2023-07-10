// Define the `nadiCustomUserRoleManagementApp` module
var nadiCustomUserRoleManagementApp = angular.module('nadiCustomUserRoleManagementApp', ['ngNotify']);

// Define the `OptionsController` controller on the `nadiCustomUserRoleManagementApp` module
nadiCustomUserRoleManagementApp.controller('OptionsController', function OptionsController($scope, $http, ngNotify) {
    "use strict";
    $scope.newUserUsername = '';
    $scope.newUserRoles = '';
    $scope.newUsercleanExistingRoles  = '';

    // User removed from the list.
    $scope.deletedUsers = [];
    
    $scope.removeMapping = function (key) {
        $scope.deletedUsers.push($scope.data[key]);
        $scope.data.splice(key, 1);
    };

    $scope.addMapping = function () {
        $scope.data.push({
            username: $scope.newUserUsername,
            roles: $scope.newUserRoles,
            cleanExistingRoles: $scope.newUsercleanExistingRoles
        });

        $scope.newUserUsername = '';
        $scope.newUserRoles = '';
        $scope.newUsercleanExistingRoles = '';
    };


    $scope.saveMapping = function () {
        console.log("Persisting Mapping...");

        var saveUsers = [];
        $scope.errorCollector = [];

        angular.copy($scope.data, saveUsers);

        // Converting Frontend Booleans to String values before persisting it.
        for (var i = 0; i < saveUsers.length; i++) {
            if (saveUsers[i].cleanExistingRoles == true) {
                saveUsers[i].cleanExistingRoles = "1";
            } else {
                saveUsers[i].cleanExistingRoles = "0";
            }
        }

        $scope.persistData = {
            saveUsers: saveUsers,
            deleteUsers: $scope.deletedUsers,
        };

        $scope.buffer = JSON.stringify($scope.persistData);


        $http.post('admin-ajax.php', {
            action: 'next_ad_int_custom_user_role_management_save_settings',
            security: 'nadiext_custom_user_role_management_nonce',
            data: $scope.persistData
        }).then(function (response) {

            var data = response.data.slice(0, -1);
            console.log(data);
            var dataObject = JSON.parse(data);
            console.log(dataObject);
            if (dataObject.status == "success") {
                ngNotify.set('Mapping saved successfully!', 'success');
            } else {
                ngNotify.set('Something went wrong!', 'error');
                $scope.errorCollector = dataObject.errorCollector;
                console.log("Scope Errorlog", $scope.errorCollector);
            }

        });
    };

    $scope.init = function () {
        return $http.post('admin-ajax.php', {
            action: 'next_ad_int_custom_user_role_management_load_settings',
            security: 'nadiext_custom_user_role_management_nonce'
        }).then(function (response) {
                if (typeof response !== 'undefined')
                {

                    var data = response.data.slice(0, -1);
                    var dataObject = JSON.parse(data);
                    var userData = dataObject.userData;

                    $scope.data = [];

                    for (var i = 0; i < userData.length; i++) {
                        console.log(userData[i]);

                        // Converting Backend String to Booleans values before loading them into scope
                        if (userData[i]["cleanExistingRoles"] == "1") {
                            userData[i]["cleanExistingRoles"] = true;
                        } else {
                            userData[i]["cleanExistingRoles"] = false;
                        }

                        $scope.data.push({
                            username: userData[i]["username"],
                            roles: userData[i]["roles"],
                            cleanExistingRoles: userData[i]["cleanExistingRoles"]
                        })
                    }

                    console.log($scope.data);

                    return data;
                } else {
                    console.log("No data from backend!")
                }
        });
    };

    $scope.change = function (key, value) {
        $scope.data[key].cleanExistingRoles = value.cleanExistingRoles;
    };
});