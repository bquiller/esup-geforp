sygeforApp.controller('TraineeDetailViewController', ['$scope', '$taxonomy', '$dialog', '$http', '$window', '$user', '$state', 'search', 'data', function($scope, $taxonomy, $dialog, $http, $window, $user, $state, search, data) {
    $scope.trainee = data.trainee;
    $scope.form = data.form ? data.form : false;
    $scope.$moment = moment;
    $scope.$user = $user;

    $scope.onSuccess = function(data) {
	    $scope.trainee = data.trainee;
	    $scope.updateActiveItem($scope.trainee);
    };

    /**
     * Unset a form children
     * @param key
     */
    $scope.unset = function (key) {
        delete $scope.form.children[key];
    };

    /**
     * Get nbr of email from entityEmails controller
     */
    $scope.$on('nbrEmails', function(event, value) {
       $scope.trainee.messages = { length: value };
    });

    /**
     * Delete the institution
     */
    $scope.toggleActivation = function () {
        $dialog.open('trainee.toggleActivation', {trainee: $scope.trainee}).then(function (data) {
            $scope.trainee = data.trainee;

            angular.forEach($scope.search.result.items, function(result) {
                if ($scope.trainee.id == result.id) {
                    result.isactive = $scope.trainee.isactive;
                    result.class = $scope.trainee.isactive ? '' : 'alert-danger';
                }
            });
        });
    };

    /**
     * Delete the trainee
     */
    $scope.delete = function () {
        $dialog.open('trainee.delete', {trainee: $scope.trainee}).then(function (){
            $state.go('trainee.table', null, { reload:true });
        });
    };

}]);
