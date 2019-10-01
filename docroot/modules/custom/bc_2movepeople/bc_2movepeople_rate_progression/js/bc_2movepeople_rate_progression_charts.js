(function ($) {
  Drupal.behaviors.dashboardCharts = {
    attach: function (context, settings) {

      $('#accordion-progressions').on('show.bs.collapse', function (e) {
        var header = $(e.target).parent().find('.panel-heading');
        var panel = $(e.target).find('.panel-body');

        console.log('Load 0');

        DashboardChart.load(header, panel);
      });

      $('#accordion-progressions .panel.panel-default').each(function () {
        var header = $(this).find('.panel-heading');
        var panel = $(this).find('.panel-body');

        console.log('Load 2');

        DashboardChart.load(header, panel);

        $(window).resize(function () {

          var panel = $('.panel-collapse.collapse.in').parent();
          var heading = $(panel).find('.panel-heading');

          if (heading.length > 0) {
            var progression_id = $(heading).attr('data-progression-id');

            if (DashboardChart.data[progression_id].length > 0) {
              var element = $(panel).find('.div-chart').attr('id');
              var chart_type = $("input[name='chart_type-" + progression_id + "']:checked").val();

              console.log('Load 22');
              drawChart(element, DashboardChart.data[progression_id], chart_type);
            }
          }
        });
      });

      // Update action.
      $('.btn-update').click(function () {
        var progression_id = $(this).attr('data-progression-id');

        updateProgressionChart(progression_id);
      });

      $('.line-graph-btn, .bar-graph-btn, .radar-graph-btn').change(function (e) {
        var progression_id = $(this).attr('data-progression-id');
        var header = $('#accordion-progressions-heading-' + progression_id);
        var panel = $(header).parent().find('.panel-body');
        var type;
        var $target = $(e.target);

        if ($target.hasClass('bar-graph-btn')) {
          type = 'bar';
        }
        else if ($target.hasClass('radar-graph-btn')) {
          type = 'radar';
        }
        else if ($target.hasClass('line-graph-btn')) {
          type = 'line';
        }

        $('#div_chart_' + progression_id).text('');

        DashboardChart.load(header, panel, true, type);
      });
    }
  };

  Drupal.behaviors.totalChart = {
    attach: function (context, settings) {
      $(this).graphTotalLoad();

      $('.line-graph-btn, .bar-graph-btn, .radar-graph-btn').change(function (e) {
        var type;
        var $target = $(e.target);

        $('#progression_total_chart').text('');

        if ($target.hasClass('bar-graph-btn')) {
          type = 'bar';
        }
        else if ($target.hasClass('radar-graph-btn')) {
          type = 'radar';
        }
        else if ($target.hasClass('line-graph-btn')) {
          type = 'line';
        }

        $(this).graphTotalLoad(type);
      });
    }
  };

  DashboardChart = {
    data: {},
    header: {},

    load: function (header, panel, reload, chart_type = 'line') {
      if ($(panel).find('.div-chart div').length > 0 && reload === false) {
        return;
      }

      console.log('Load 1');

      var progression_id = header.attr('data-progression-id');

      $.ajax({
        type: 'GET',
        url: '/rates/' + progression_id + '/get',
        dataType: 'json',
        success: function (data) {
          if (data.values.length) {
            $(panel).find('.div-form').show();
            var element = $(panel).find('.div-chart').attr('id');

            var labels = [
              "Har du overskud i hverdagen til, at fokusere på både dit arbejde og privatliv?",
              "Hvorledes vil du vurdere din nuværende helbredstilstand i almindelighed både fysisk og psykisk?",
              "Hvor tit har du sovet dårligt: fx uroligt, haft svært ved at falde i søvn, vågnet for tidligt?"
            ];
            var datasets = [
              {
                date: '010202022129',
                values: [5, 2, 3]
              },
              {
                date: '010202022129',
                values: [4, 3, 1]
              },
              {
                date: '010202022129',
                values: [2, 2, 5]
              },
            ];

            var json = {
              labels: labels,
              datasets: [
                {
                  date: '010202022129',
                  values: [5, 2, 3]
                },
                {
                  date: '010202022129',
                  values: [4, 3, 1]
                },
                {
                  date: '010202022129',
                  values: [2, 2, 5]
                },
              ]
            };

            drawChart(element, json, chart_type);
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

    console.log('Load 5');

    $.ajax({
      type: 'GET',
      url: '/rates/' + progression_id + '/get',
      dataType: 'json',
      success: function (data) {
        if (data.values.length) {
          $(panel).find('.div-form').show();
          // google.charts.setOnLoadCallback(function () {
          //   var element = $(panel).find('.div-chart').attr('id');
          //
          //   var arr = [
          //     [''].concat(data.series)
          //   ];
          //
          //   data.values.forEach(function (item) {
          //     arr.push(item);
          //   });
          //
          //   drawChart(element, arr, data.chart_title, chart_type);
          // });
        }
        else {
          $(panel).find('.div-form').hide();
        }
      }
    });
  }

  function drawChart(element, data, chart_type = 'line') {
    var iteration = 1;
    var colors = ['#ebbab2', '#4782a6', '#60d5d5', '#f2188e', '#980299', '#dc3913'];
    var ctx = document.getElementById(element).getContext('2d');
    var labels = [];
    var datasets = [];
    var options = {
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true
          }
        }]
      }
    };

    // Labels.
    for (var label of data.labels) {
      labels.push(truncateString(label, 25));
    }

    // Datasets.
    for (var dataset of data.datasets) {
      datasets.push({
        label: dataset.date,
        data: dataset.values,
        backgroundColor: colors[iteration],
        borderColor: colors[iteration],
        fill: false,
        borderWidth: 2
      });

      iteration++;
    }

    switch (chart_type) {
      case 'line': {
        options = {};

        break;

      }
      case 'bar': {
        options = {};

        break;

      }
      case 'radar': {
        options = {};

        break;

      }
    }

    // Remove class.
    document
      .getElementById(element)
      .classList
      .remove('hidden');

    var chart = new Chart(ctx, {
      type: chart_type,
      data: {
        labels: labels,
        datasets: datasets
      },
      options: options,
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
          // google.charts.setOnLoadCallback(function () {
          //   var arr = [
          //     [''].concat(Object.values(data.series))
          //   ];
          //   data.values.forEach(function (item) {
          //     arr.push(item);
          //   });
          //   drawChart('div_chart_' + progression_id, arr, data.chart_title, chart_type);
          // });
        }
        else {
          //  $("#dialog-message").dialog("open");
        }
      }
    });
  }

  function truncateString (string, length = 25) {
    var ellipsis = '...';

    console.log('string', string);

    if (string.length > length) {
      return string.substring(0, length - ellipsis.length) + ellipsis;
    }

    return string;
  };

  $.fn.graphReload = function (element) {
    var panel = $(element).parent('.ui-accordion-content');
    var header = $('#' + panel.attr('aria-labelledby'));
    loadGraph(header, panel, true);
  };

  $.fn.graphTotalLoad = function (chart_type = 'line') {
    if ($('#progression_total_table').length > 0) {
      columns = GetColumnCount($('#progression_total_table table'));
      var arr = [];
      for (var i = 1; i <= columns; i++) {
        arr[i - 1] = [$('#progression_total_table table th[data-target=col_' + i + ']').text()];
        $('#progression_total_table table td[data-target=col_' + i + ']').each(function () {
          var cellContent = $(this).text().trim();
          if (!isNaN(parseFloat(cellContent))) {
            arr[i - 1].push(parseFloat(cellContent));
          }
          else if (cellContent == '') {
            arr[i - 1].push(null);
          }
          else {
            arr[i - 1].push(cellContent);
          }
        });

      }
      // google.charts.setOnLoadCallback(function () {
      //   drawChart('progression_total_chart', arr, chart_type);
      // });

      $(window).resize(function () {
        drawChart('progression_total_chart', arr, chart_type);
      });
    }
  };

  function GetColumnCount(table) {
    var ColCount = 0;
    $(table).find('tr').eq(0).find('th,td').each(function () {
      ColCount += $(this).attr('colspan') ? parseInt($(this).attr('colspan')) : 1;
    });

    return ColCount;
  }

})(jQuery);
