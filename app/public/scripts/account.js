$(document).ready(function () {
    "use strict";

    $('.dropdown a[href="#"]').on('click', function (event) {
        event.preventDefault();
    });

    function parseResponse(response, form) {
        if (response.status === 'success') {
            return true;
        } else if (response.status === 'fail') {
            if (response.data.auth) {
                window.location.replace('/');
            }
            if (form !== null) {
                var validator = $('#' + form).validate();

                validator.showErrors({
                    email: response.data.email,
                    phone: response.data.phone
                });
            }
        } else if (response.status === 'error') {
            window.alert('Erro: ' + response.message);
        } else {
            window.alert('Erro');
        }
        return false;
    }

    function getTokenValue() {
        return $('#token').text();
    }

    $(document).bind('ajaxSend', function (elm, xhr) {
        xhr.setRequestHeader('X-CSRF-Token', getTokenValue());
    });


    $('#submit').on('click', function () {
        $('#infoform').submit();
    });

    $.validator.addMethod("exactlength", function (value, element, param) {
        return this.optional(element) || value.length === param;
    });

    $('#infoform').validate({
        rules: {
            email: {
                required: true
            },
            phone: {
                required: true,
                digits: true,
                exactlength: 9
            }
        },
        messages: {
            email: {
                required: 'Coloque um email.'
            },
            phone: {
                required: 'Coloque um número de telemóvel.',
                digits: 'Coloque apenas dígitos',
                exactlength: 'Coloque {0} dígitos.'
            }
        },
        submitHandler: function (form, e) {
            e.preventDefault();

            $.ajax({
                url: '/student/update',
                type: 'POST',
                data: $(form).serialize(),
                async: false,
                cache: false,
                dataType: 'json',
                success: function (response) {
                    if (parseResponse(response, 'infoform')) {
                        window.location.replace('/student/dashboard');
                    }
                },
                error: function () {
                    window.alert('Erro');
                }
            });
        }
    });
});
