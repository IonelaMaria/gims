angular.module('myApp.directives').directive('gimsNumQuestion', function (QuestionAssistant) {
    return {
        restrict: 'E',
        template:   "<ng-form name='innerQuestionForm'> " +
                        "<div class='span12'>"+
                            "<div ng-form name='innerQuestionForm' class='span2' ng-repeat='part in question.parts'>"+
                            "     <div ng-switch='part.name'>" +
                            "           <div ng-switch-when='Total'>National</div>"+
                            "           <div ng-switch-when='Urban'>Urban</div>"+
                            "           <div ng-switch-when='Rural'>Rural</div>"+
                            "     </div>"+
                            "     <input class='span12' ng-disabled='saving' type='number' ng-required='question.isCompulsory' ng-model='index[question.id+\"-\"+part.id].valueAbsolute' ng-blur='save(question,part)' name='numerical-{{question.id}}-{{part.id}}' id='numerical-{{question.id}}-{{part.id}}'/>"+
                            "</div>"+
                        "</div>"+
                        "<span ng-show='question.isCompulsory' class='badge' ng-class=\"{'badge-important':question.statusCode==1, 'badge-success':question.statusCode==3}\">Required</span>"+
                        "<span ng-show='!question.isCompulsory' class='badge' ng-class=\"{'badge-warning':question.statusCode==2, 'badge-success':question.statusCode==3}\">Optional</span>"+
                    "</ng-form>",

        scope:{
            index:'=',
            question:'='
        },

        controller: function ($scope, $location, $resource, $routeParams, Restangular, Modal)
        {
            $scope.saving=false;
            $scope.save = function (question, part)
            {
                $scope.saving=true;
                var newAnswer = $scope.index[question.id+"-"+part.id];

                // if exists but value not empty -> update
                if (newAnswer.id && newAnswer.valueAbsolute) {
                    newAnswer.put().then(function(){
                        $scope.saving=false;
                        QuestionAssistant.updateQuestion(question, $scope.index, false, true);
                    });

                // if dont exists -> create
                } else if (!newAnswer.id && newAnswer.valueAbsolute) {
                    Restangular.all('answer').post(newAnswer).then(function(answer){
                        $scope.index[question.id+"-"+part.id] = answer;
                        $scope.saving=false;
                        QuestionAssistant.updateQuestion(question, $scope.index, false, true);
                    });

                // if exists and empty -> remove
                } else if (newAnswer.id && (newAnswer.valueAbsolute===undefined || newAnswer.valueAbsolute===null)) {
                    newAnswer.remove().then(function(){
                        $scope.index[question.id+"-"+part.id] = QuestionAssistant.getEmptyTextAnswer(question, part.id);
                        $scope.saving=false;
                        QuestionAssistant.updateQuestion(question, $scope.index, false, true);
                    });
                } else {
                    $scope.saving=false;
                }
            };

        }
    }
});

