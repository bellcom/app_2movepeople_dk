/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  Drupal.behaviors.dashboardCharts = {
    attach: function (context, settings) {
      // google.charts.load('current', {packages: ['corechart', 'bar']});
      google.charts.load('current', {packages: ['corechart']});

      $('#accordion-progressions').on('show.bs.collapse', function (e) {
        var header = $(e.target).parent().find('.panel-heading');
        var panel = $(e.target).find('.panel-body');

        DashboardChart.load(header, panel);
      });

      $('#accordion-progressions .panel.panel-default').each(function () {
        var header = $(this).find('.panel-heading');
        var panel = $(this).find('.panel-body');

        DashboardChart.load(header, panel);

        $(window).resize(function () {

          var panel = $('.panel-collapse.collapse.in').parent();
          var heading = $(panel).find('.panel-heading');

          if (heading.length > 0) {
            var progression_id = $(heading).attr('data-progression-id');

            if (DashboardChart.data[progression_id].length > 0) {
              var element = $(panel).find('.div-chart').attr('id');
              var chart_type = $("input[name='chart_type-" + progression_id + "']:checked").val();
              moveCharts.drawChart(element, DashboardChart.data[progression_id], DashboardChart.header[progression_id], chart_type);
            }
          }
        });
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

      $('.btn-update').click(function () {
        var progression_id = $(this).attr('data-progression-id');
        updateProgressionChart(progression_id);
      });

      $('.line-graph-btn').change(function () {
        var progression_id = $(this).attr('data-progression-id');
        var header = $('#accordion-progressions-heading-' + progression_id);
        var panel = $(header).parent().find('.panel-body');
        $('#div_chart_' + progression_id).text('');
        DashboardChart.load(header, panel, true, 'line');
      });

      $('.bar-graph-btn').change(function () {
        var progression_id = $(this).attr('data-progression-id');
        var header = $('#accordion-progressions-heading-' + progression_id);
        var panel = $(header).parent().find('.panel-body');
        $('#div_chart_' + progression_id).text('');
        DashboardChart.load(header, panel, true, 'bar');
      });
    }
  };

  DashboardChart = {
    data: {},
    header: {},

    load: function (header, panel, reload, chart_type = 'line') {
      if ($(panel).find('.div-chart div').length > 0 && reload == false) {
        return;
      }

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
              var arr = [['']];
              for (var key in data.series) {
                var label = '';
                if (data.series.hasOwnProperty(key)) {
                  label = data.series[key];
                }
                arr[0].push(label);
              }
              data.values.forEach(function (item) {
                arr.push(item);
              });
              DashboardChart.data[progression_id] = arr;
              DashboardChart.header[progression_id] = data.chart_title;
              moveCharts.drawChart(element, arr, data.chart_title, chart_type);
            });
          }
          else {
            $(panel).find('.div-form').hide();
          }
        }
      });
    }
  };

  function loadGraph(header, panel, reload, chart_type = 'line') {
    if ($(panel).find('.div-chart div').length > 0 && reload == false) {
      return;
    }

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
              [''].concat(data.series)
            ];

            data.values.forEach(function (item) {
              arr.push(item);
            });

            moveCharts.drawChart(element, arr, data.chart_title, chart_type);
          });
        }
        else {
          $(panel).find('.div-form').hide();
        }
      }
    });
  }

  function updateProgressionChart(progression_id, chart_type = 'line') {
    var date_from = $('#date_from_' + progression_id).val();
    var date_to = $('#date_to_' + progression_id).val();
    var chart_type = $("input[name='chart_type-" + progression_id + "']:checked").val();

    $.ajax({
      type: 'GET',
      data: {from: date_from, to: date_to},
      url: '/rates/' + progression_id + '/get',
      dataType: 'json',
      success: function (data) {
        if (data.values.length) {
          google.charts.setOnLoadCallback(function () {
            var arr = [
              [''].concat(Object.values(data.series))
            ];
            data.values.forEach(function (item) {
              arr.push(item);
            });
            moveCharts.drawChart('div_chart_' + progression_id, arr, data.chart_title, chart_type);
          });
        }
        else {
          //  $("#dialog-message").dialog("open");
        }
      }
    });
  }

  $.fn.graphReload = function (element) {
    var panel = $(element).parent('.ui-accordion-content');
    var header = $('#' + panel.attr('aria-labelledby'));
    loadGraph(header, panel, true);
  };


})(jQuery);


