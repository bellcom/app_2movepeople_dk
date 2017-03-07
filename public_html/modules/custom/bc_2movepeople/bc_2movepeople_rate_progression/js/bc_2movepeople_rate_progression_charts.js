/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  Drupal.behaviors.dashboardCharts = {
    attach: function (context, settings) {
      google.charts.load('current', {packages: ['corechart', 'bar']});

      if (!$.isFunction($("#accordion", context).accordion))
        return false;
      $("#accordion", context).accordion({
        activate: function (event, ui) {
          loadGraph(ui.newHeader, ui.newPanel);
        },
        create: function (event, ui) {
          loadGraph(ui.header, ui.panel);
        }
      });
      $("#dialog-message").dialog({
        modal: true,
        autoOpen: false,
        buttons: {
          Ok: function () {
            $(this).dialog("close");
          }
        }
      });

      $(".btn-update").click(function () {
        var progression_id = $(this).attr('data-progression-id');
        var date_from = $('#date_from_' + progression_id).val();
        var date_to = $('#date_to_' + progression_id).val();
        $.ajax({
          type: 'GET',
          data: {from: date_from, to: date_to},
          url: 'rates/' + progression_id + '/get',
          dataType: 'json',
          success: function (data) {
            if (data.values.length) {
              google.charts.setOnLoadCallback(function () {
                drawChart("div_chart_" + progression_id, data)
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
      if ($("#progression_total_table").length > 0) {
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
  };

  function loadGraph(header, panel, reload = false) {
    if (panel.find('.div-chart div').length > 0 && reload == false)
      return;
    var progression_id = header.attr('data-progression-id');
    $.ajax({
      type: 'GET',
      url: '/rates/' + progression_id + '/get',
      dataType: 'json',
      success: function (data) {
        if (data.values.length) {
          panel.find('.div-form').show();
          google.charts.setOnLoadCallback(function () {
            var element = panel.find('.div-chart').attr('id');
            var arr = [
              [''].concat(data.series),
            ];

            data.values.forEach(function (item) {
              arr.push(item);
            });
            drawChart(panel.find('.div-chart').attr('id'), arr, data.chart_title)
            $('#' + element).parent('.ui-accordion-content').height($('#' + element).parent('.ui-accordion-content').children('.div-goals').height()
                    + $('#' + element).parent('.ui-accordion-content').children('.div-form').height()
                    + $('#' + element).height());
          });
        } else {
          panel.find('.div-form').hide();
        }
      }
    });
  }
  function drawChart(element, data, title = "") {
    var cdata = google.visualization.arrayToDataTable(data);

    var options = {
      //chart: {
      title: title,
      //},
      bars: 'vertical',
      vAxis: {minValue: 0,
        ticks: [0, 1, 2, 3, 4, 5]
      },
      height: 350,
      legend: {position: "bottom"},
      pointSize: 10,

    };
    var chart = new google.visualization.LineChart(document.getElementById(element));
    chart.draw(cdata, options);
  }
  $.fn.graphReload = function (element) {
    var panel = $(element).parent('.ui-accordion-content');
    var header = $("#" + panel.attr('aria-labelledby'));
    loadGraph(header, panel, true);

  }
  function GetColumnCount(table)
  {
    var ColCount = 0;
    $(table).find("tr").eq(0).find("th,td").each(function ()
    {
      ColCount += $(this).attr("colspan") ? parseInt($(this).attr("colspan")) : 1;
    });

    return ColCount;
  }
})(jQuery);


