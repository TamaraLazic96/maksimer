jQuery(document).ready(function ($) {

    if ($('div.max-main').length) {

        if ($.cookie('sort') === undefined) {
            $.cookie('sort', 'label')
        }
        if ($.cookie('pageNumber') === undefined) {
            $.cookie('pageNumber', 1)
        }
        if ($.cookie('dateFrom') && $.cookie('dateTo')) {
            let newDateFrom = moment($.cookie('dateFrom')).format('MM/DD/YYYY');
            let newDateTo = moment($.cookie('dateTo')).format('MM/DD/YYYY');
            $('#max-datepicker').val(newDateFrom + ' - ' + newDateTo);
        }
        maxEvents();

        $(function () {
            $('input[name="daterange"]').daterangepicker({
                opens: 'top'
            }, function (start, end) {
                $.cookie('dateFrom', start.format('YYYY-MM-DD') + ' 00:00:00');
                $.cookie('dateTo', end.format('YYYY-MM-DD') + ' 00:00:00');
                $.cookie('pageNumber', 1);
                maxEvents();
            });
        });

        const prevEvent = $('#prev-page-event');
        const nextEvent = $('#next-page-event');
        const firstEvent = $('#first-page-event');
        const label = $('#max-label');
        const datetime = $('#max-date');
        const dateReset = $('#max-reset-date');
        prevEvent.hide();

        dateReset.click(function () {
            $.removeCookie('dateFrom');
            $.removeCookie('dateTo');
            $.cookie('pageNumber', 1);
            $('#max-datepicker').val('12/03/2020 - 12/03/2020');
            maxEvents();
        });

        label.click(function () {
            $.cookie('sort', 'label');
            maxEvents();
        });

        datetime.click(function () {
            $.cookie('sort', '-datetime');
            maxEvents();
        });

        nextEvent.click(function (e) {
            e.preventDefault();
            $.cookie('pageNumber', nextEvent.data('next'));
            maxEvents();
        });

        prevEvent.click(function (e) {
            e.preventDefault();
            $.cookie('pageNumber', prevEvent.data('prev'));
            maxEvents();
        });

        firstEvent.click(function (e) {
            e.preventDefault();
            $.cookie('pageNumber', 1);
            maxEvents();
        });

        function maxEvents() {

            console.log($.cookie('pageNumber'));
            console.log($.cookie('sort'));
            console.log($.cookie('dateFrom'));
            console.log($.cookie('dateTo'));

            jQuery.ajax({
                type: 'POST',
                url: maxData.maxURL,
                data: {
                    "action": "event_page",
                    "page": $.cookie('pageNumber'),
                    "sort": $.cookie('sort'),
                    "dateFrom": $.cookie('dateFrom'),
                    "dateTo": $.cookie('dateTo')
                },
                success: function (data) {

                    let res = JSON.parse(data);

                    if (res.next === null) {
                        nextEvent.hide();
                    } else {
                        nextEvent.data('next', res.next);
                        nextEvent.show();
                    }

                    if (res.prev === null) {
                        prevEvent.hide();
                    } else {
                        prevEvent.data('prev', res.prev);
                        prevEvent.show();
                    }

                    firstEvent.data('first', 1);

                    let mainDiv = $('#events-max');
                    mainDiv.empty();
                    mainDiv.append(res.body);
                },
                beforeSend: function () {
                    $('.loader').show();
                    $('#events-max').hide();
                },
                complete: function () {
                    $('.loader').hide();
                    $('#events-max').show();
                }
            });
        }

        $(document).ajaxStop(function (event) {
            $(".save-user-event").click(function () {
                let saveBtn = $(this);
                jQuery.ajax({
                    type: 'POST',
                    url: maxData.maxURL,
                    data: {
                        "action": "event_save",
                        "event": saveBtn.attr('data-eventId'),
                    },
                    success: function (data) {
                        let res = JSON.parse(data);

                        if (res === "exists") {
                            saveBtn.text('Event is already saved.');
                        } else {
                            saveBtn.text('Successfully saved');
                        }
                        saveBtn.attr("disabled", true);
                    }
                })
            });
        })
    }
});