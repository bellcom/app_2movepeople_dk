/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
    Drupal.behaviors.dashboardCharts = {
        attach: function (context, settings) {
            google.charts.load('current', {packages: ['corechart', 'bar']});

            $('#accordion-progressions').on('show.bs.collapse', function (e) {
                var header = $(e.target).parent().find('.panel-heading');
                var panel = $(e.target).find('.panel-body');

                loadGraph(header, panel);
            });


            $('#accordion-progressions .panel.panel-default').each(function () {
                var header = $(this).find('.panel-heading');
                var panel = $(this).find('.panel-body');

                loadGraph(header, panel);
            });


//      $("#dialog-message").dialog({
//        modal: true,
//        autoOpen: false,
//        buttons: {
//          Ok: function () {
//            $(this).dialog("close");
//          }
//        }
//      });

            $(".btn-update").click(function () {
                var progression_id = $(this).attr('data-progression-id');
                var date_from = $('#date_from_' + progression_id).val();
                var date_to = $('#date_to_' + progression_id).val();
                $.ajax({
                    type: 'GET',
                    data: {from: date_from, to: date_to},
                    url: '/rates/' + progression_id + '/get',
                    dataType: 'json',
                    success: function (data) {
                        if (data.values.length) {
                            google.charts.setOnLoadCallback(function () {
                                var arr = [
                                    [''].concat(Object.values(data.series)),
                                ];
                                data.values.forEach(function (item) {
                                    arr.push(item);
                                });
                                drawChart("div_chart_" + progression_id, arr, data.chart_title)
                            });
                        } else {
                            $("#dialog-message").dialog("open");
                        }
                    }
                });
            })
        }
    }

    Drupal.behaviors.totalChart = {
        attach: function (context, settings) {
            google.charts.load('current', {packages: ['corechart', 'bar']});
            $(this).graphTotalLoad();
        }
    };

    function loadGraph(header, panel, reload) {
        if ($(panel).find('.div-chart div').length > 0 && reload == false)
            return;
        var progression_id = header.attr('data-progression-id');
        $.ajax({
            type: 'GET',
            url: '/rates/' + progression_id + '/get',
            dataType: 'json',
            success: function (data) {
                if (data.values.length) {
                    $(panel).find('.div-form').show();
                    google.charts.setOnLoadCallback(function () {
                        var element = $(panel).find('.div-chart').attr('id');

                        var arr = [
                            [''].concat(data.series),
                        ];

                        data.values.forEach(function (item) {
                            arr.push(item);
                        });

                        drawChart(element, arr, data.chart_title);
                    });
                } else {
                    $(panel).find('.div-form').hide();
                }
            }
        });
    }

    function drawChart(element, data, title = "") {
        var cdata = google.visualization.arrayToDataTable(data);

        var options = {
            title: title,
            bars: 'vertical',
            vAxis: {minValue: 0,
                ticks: [0, 1, 2, 3, 4, 5]
            },
            chartArea: {
                height: "450px",
                width: "90%"
            },
            legend: {position: "bottom"},
            pointSize: 10
        };

        var chart = new google.visualization.ColumnChart(document.getElementById(element));
        chart.draw(cdata, options);
    }

    $.fn.graphReload = function (element) {
        var panel = $(element).parent('.ui-accordion-content');
        var header = $("#" + panel.attr('aria-labelledby'));
        loadGraph(header, panel, true);
    }

    $.fn.graphTotalLoad = function () {
        if ($('#progression_total_table').length > 0) {
            columns = GetColumnCount($("#progression_total_table table"));
            var arr = [];
            for (var i = 1; i <= columns; i++) {
                arr[i - 1] = [$("#progression_total_table table th[data-target=col_" + i + "]").text()];
                $("#progression_total_table table td[data-target=col_" + i + "]").each(function () {
                    if (!isNaN(parseFloat($(this).text())))
                        arr[i - 1].push(parseFloat($(this).text()));
                    else
                        arr[i - 1].push($(this).text());
                })

            }
            google.charts.setOnLoadCallback(function () {
                drawChart("progression_total_chart", arr);
            });
        }
    }
    function GetColumnCount(table) {
        var ColCount = 0;
        $(table).find("tr").eq(0).find("th,td").each(function () {
            ColCount += $(this).attr("colspan") ? parseInt($(this).attr("colspan")) : 1;
        });

        return ColCount;
    }
})(jQuery);


