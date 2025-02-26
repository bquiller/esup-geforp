/**
 * BatchMailingController
 */
sygeforApp.controller('InscriptionStatusChange', ['$scope', '$http', '$window', '$modalInstance', '$dialogParams', '$dialog', 'config', function ($scope, $http, $window, $modalInstance, $dialogParams, $dialog, config)
{
    $scope.service = 'sygefor_inscription.batch.inscription_status_change';
    $scope.dialog = $modalInstance;
    $scope.items = $dialogParams.items;
    $scope.inscriptionstatus = $dialogParams.inscriptionstatus;
    $scope.presencestatus = $dialogParams.presencestatus;
    $scope.send = {Mail: !!config.templates.length};
    $scope.attachmentTemplates = config.attachmentTemplates;
    $scope.attachments = [];
    $scope.attCheckList = $scope.attachmentTemplates;
    $scope.formError = '';

    //building templates contents
    $scope.templates = [];
    for (var i in config.templates) {
        $scope.templates[i] = {
            'key': i,
            'label': config.templates[i]['name'],
            'subject': config.templates[i]['subject'],
            'body': config.templates[i]['body'],
            'ical': config.templates[i]['private'],
            'format': config.templates[i]['position'],
            'attachmentTemplates': config.templates[i]['attachment_templates']
        };
    }

    $scope.message = {};

    if ($scope.templates.length) {
        $scope.message.template = $scope.templates[0];
        $scope.message.subject = $scope.templates[0].subject;
        $scope.message.body = $scope.templates[0].body;
        $scope.message.attachmentTemplates = $scope.templates[0].attachmentTemplates;
        $scope.message.ical = $scope.templates[0].ical;
        $scope.message.format = $scope.templates[0].format;
    }
    $scope.message.attachments = [];

    /**
     * ensures the form was correctly filed (sets an error message otherwise), then asks for server-side message sending
     * if mail sending is performed without errors, the file is asked for download
     */
    $scope.ok = function () {

        if ($scope.send.Mail && !($scope.message.subject || $scope.message.message)) {
            $scope.formError = 'Pas de corps de message';
            return;
        }

        $scope.formError = '';
        var url = Routing.generate('sygefor_core.batch_operation.execute', {id: $scope.service});

        var attTemplates = [];
        if (typeof $scope.attCheckList != 'undefined') {
            angular.forEach($scope.attCheckList, function (tpl) {
                if (( typeof tpl.selected != 'undefined' ) && tpl.selected) {
                    attTemplates.push(tpl.id);
                }
            });
        }

        var data = {
            options: {
                //inscriptionStatus: $scope.inscriptionStatus.id,
                targetClass: 'App\\Entity\\Core\\AbstractInscription',
                sendMail: $scope.send.Mail,
                subject: $scope.message.subject,
                message: $scope.message.body,
                ical: $scope.message.ical,
                format: $scope.message.format,
                attachmentTemplates: attTemplates,
                objects: {'App\\Entity\\Session': ($dialogParams.session) ? $dialogParams.session.id : 0}
            },
            attachments: $scope.message.attachments,
            ids: $scope.items.join(",")
        };

        if (typeof $scope.inscriptionstatus != 'undefined') {
            data['options']['inscriptionstatus'] = $scope.inscriptionstatus.id
        }

        if (typeof $scope.presencestatus != 'undefined') {
            data['options']['presencestatus'] = $scope.presencestatus.id
        }

        $http({
            method: 'POST',
            url: url,
            transformRequest: function (data) {
                var formData = new FormData();
                //need to convert our json object to a string version of json otherwise
                // the browser will do a 'toString()' on the object which will result
                // in the value '[Object object]' on the server.
                formData.append("options", angular.toJson(data.options));
                //now add all of the assigned files
                formData.append("ids", angular.toJson(data.ids));
                //add each file to the form data and iteratively name them
                for (var key in data.attachments) {
                    formData.append("attachment_" + key, data.attachments[key]);
                }
                return formData;
            },
            headers: {'Content-Type': undefined},
            data: data
        }).success(function (data) {
            $scope.dialog.close();
        });
/*
        $http.post(url, data).success(function() {
            $scope.dialog.close();
        });*/
    };

    $scope.preview = function () {
        $dialog.open('batch.emailPreview', {
            ids: $scope.items[0],
            options: {
                targetClass: 'App\\Entity\\Core\\AbstractInscription',
                subject: $scope.message.subject,
                message: $scope.message.body
            }
        });
    };

    $scope.previewAttachment = function (attachmentTemplate) {

        var url = Routing.generate('sygefor_core.batch_operation.execute', {id: 'sygefor_core.batch.publipost.inscription'});

        var data = {
            options: {
                template: attachmentTemplate.id
            },
            ids: $scope.items[0]
        };

        $http(
            {
                method: 'POST',
                url: url,
                transformRequest: function (data) {
                    var formData = new FormData();
                    //need to convert our json object to a string version of json otherwise
                    // the browser will do a 'toString()' on the object which will result
                    // in the value '[Object object]' on the server.
                    formData.append("options", angular.toJson(data.options));
                    //now add all of the assigned files
                    formData.append("ids", angular.toJson(data.ids));

                    return formData;
                },
                headers: {'Content-Type': undefined},
                data: data
            }).success(
            function (data) { //response should contain the file url
                if (data.fileUrl) {
                    var url = Routing.generate('sygefor_core.batch_operation.get_file', {
                        service: 'sygefor_core.batch.publipost.inscription',
                        file: data.fileUrl,
                        filename: attachmentTemplate.fileName,
                        pdf: true
                    });
                    // changin location :
                    $window.location = url;
                }
            });

    };

    $scope.cancel = function () {
        $modalInstance.dismiss();
    };

    /**
     * watches file upload model, and updates the form accordingly
     */
    $scope.fileChanged = function (element, $scope) {
        $scope.$apply(function (scope) {
            $scope.options.templateFile = element.files[0];
        });
    };

    /**
     * watches file upload, and updates the form accordingly
     */
    $scope.fileChangedAtt = function (element, $scope) {
        $scope.$apply(function (scope) {
            for (var key in element.files) {
                if (typeof element.files[key] === "object") {
                    $scope.message.attachments.push(element.files[key]);
                }
            }
        });
        angular.element($('#inputAttachment')).val(null);
    };

    /**
     * Remove file attachment
     * @param key
     */
    $scope.removeAttachment = function(key) {
        $scope.message.attachments.splice(key, 1);
        angular.element($('#inputAttachment')).val(null);
    };

    $scope.isAObject = function(mixed) {
        return typeof mixed === "object";
    };

    
    /**
     * Watches selected template. When changed, current field contents are stored,
     * then replaced byselected template values
     */
    $scope.$watch('message.template', function (newValue, oldValue) {
        if (newValue) {
            //storing changes
            if (typeof oldValue != 'undefined') {
                oldValue.subject = $scope.message.subject;
                oldValue.body = $scope.message.body;
                oldValue.ical = $scope.message.ical;
                oldValue.format = $scope.message.format;
            }
            //replacing values
            $scope.message.subject = newValue.subject;
            $scope.message.body = newValue.body;
            $scope.message.ical = newValue.ical;
            $scope.message.format = newValue.format;
            $scope.attCheckList = newValue.attachmentTemplates;
        }
    });
}]);
