define(["jquery", "core/ajax", "core/templates", "core/notification"], function($, ajax, Templates, notification) {
    return {
        init: function() {
            $('#exportButton').click(function() {
                var selecteddate = $('#dated').text();
                var url = M.cfg.wwwroot + '/report/trainingenrolment/downloadpdf.php?d='+selecteddate;
                window.open(url,'_self' );
            });
        },
        mailTemp: function() {
            $("#add-tag-input").on({
                focusout : function() {
                    var txt = this.value.replace(/[^a-z0-9-@&\+\-\.\#]/ig,''); // allowed characters
                    if (txt) {
                        $("<span/>", {text:txt.toLowerCase(), appendTo:"#tag-container", class:"dashfolio-tag"});
                    }
                    this.value = "";
                },
                keyup : function(ev) {
                    // if: comma|enter (delimit more keyCodes with | pipe)
                    if (/(188|13)/.test(ev.which)) {
                        $(this).focusout();
                    }
                }
            });

            $('.tag-container').on('click', 'span', function() {
                    //if(confirm("Remove "+ $(this).text() +"?"))
                    $(this).remove();
            });

            $('#cancelBtn').click(function() {
                window.location.href = '{{{ main_url }}}';
                return false;
            });

            $('#submitBtn').click(function(e) {
                if( !$.trim( $('#tag-container').html() ).length ) {
                    e.preventDefault();
                    notification.alert('Notice', 'You must enter at least one email id!', 'Continue');
                    return;
                } else {
                    e.preventDefault();
                    var emailIds = $('.dashfolio-tag').map(function() {
                        return $(this).text();
                    }).get();

                    var checkedArray = [];
                    var checkboxes = document.querySelectorAll('input[name="inlineCheckbox[]"]:checked');
                    for (var i = 0; i < checkboxes.length; i++) {
                        checkedArray.push(checkboxes[i].value);
                    }
                    var promises = ajax.call([
                        {
                            methodname: 'report_te_mail_users',
                            args: {
                                attach : JSON.stringify(checkedArray),
                                subject : $('#subject').val(),
                                message : $('#message').val(),
                                users : JSON.stringify(emailIds)
                            }
                        }
                    ]);
                    promises[0].done(function(result) {
                        window.console.log(result);
                        notification.alert('Success', 'Users Processed Successfully!', 'Continue');
                    }).fail(function(result) {
                        window.console.log(result);
                        notification.alert('Warning', 'Failed Processing Users!', 'Continue');
                    });
                }
            });
        }
    };
});
