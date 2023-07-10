<div class="container-fluid">

    <h1><?= __('Next ADI: Custom User Role Management') ?></h1>
    <p>You can map WordPress Roles to specific users, with that those users wont be effected by NADI Role Equivalent
        Mapping anymore if you activate "clean existing roles" for them. Users modified by this extention will be marked
        in the users list.
        <br>
        You can add multiple WordPress roles to a user by using <code>";"</code>. (Example: <code>Subscriber;Administrator;Editor</code>)
    </p>
    <div ng-app="nadiCustomUserRoleManagementApp">
        <div ng-controller="OptionsController">
            <?php wp_nonce_field('save', 'nadiext_custom_user_role_management_nonce') ?>
            <ul>
                <li>
                    <table ng-init="init()" class="table table-responsive">
                        <tr class="nadi-user-list-custom-column">
                            <th>Username</th>
                            <th>Custom Roles</th>
                            <th>Clean Existing Roles</th>
                            <th></th>
                        </tr>
                        <tr ng-repeat="(key, value) in data track by key">
                            <td><input type="text" ng-model="value.username" class="nadi-input-fullwidth"></td>
                            <td><input type="text" ng-model="value.roles" class="nadi-input-fullwidth"></td>
                            <td class="nadi-user-list-align-checkbox"><input type="checkbox" ng-model="value.cleanExistingRoles"
                                       ng-click="change(key, value)" style="align-self: center"></td>
                            <td>
                                <div class="text-center">
                                    <button class="button adi-btn-delete nadi-button-center"
                                            ng-click="removeMapping(key)">
                                        <span class="nsp_dashicons dashicons dashicons-no-alt nadi-icon-inside-button"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr class="nadi-input-fullwidth">
                            <td><input ng-model="newUserUsername" type="text" class="nadi-input-fullwidth"></td>
                            <td><input ng-model="newUserRoles" type="text" class="nadi-input-fullwidth"></td>
                            <td class="nadi-user-list-align-checkbox"><input type="checkbox" ng-model="newUsercleanExistingRoles"></td>
                            <td>
                                <div class="text-center">
                                    <button class="button button-primary " ng-click="addMapping()">
                                        <span class="nsp_dashicons dashicons dashicons-plus nadi-icon-inside-button"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <br>

                <li ng-repeat="(key, value) in errorCollector track by key">
                    <div class="alert alert-danger" role="alert">
                        <span class="sr-only">Error:</span>
                        ERROR: {{ value.msg }}
                    </div>
                </li>


                <button class="button button-primary" ng-click="saveMapping()">
                    <span>Save mapping</span>
                </button>
                </li>
            </ul>


        </div>
    </div>

</div>