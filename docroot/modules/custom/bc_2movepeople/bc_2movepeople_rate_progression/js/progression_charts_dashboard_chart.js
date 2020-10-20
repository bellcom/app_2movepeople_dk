(function ($) {
  Drupal.behaviors.dashboardCharts = {
    attach: function (context, settings) {

      $('#accordion-progressions').on('show.bs.collapse', function (e) {
        var header = $(e.target).parent().find('.panel-heading');
        var panel = $(e.target).find('.panel-body');

        DashboardChart.load(header, panel);
      });

      $('#accordion-progressions .panel.panel-default').each(function () {
        var header = $(this).find('.panel-heading');
        var panel = $(this).find('.panel-body');

        DashboardChart.load(header, panel);
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

  DashboardChart = {
    data: {},
    header: {},

    load: function (header, panel, reload, chart_type = 'line') {
      if ($(panel).find('.div-chart div').length > 0 && reload === false) {
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
            var element = $(panel).find('.div-chart').attr('id');
            var mutatedData = charty.convertDataToDatasets(data);

            charty.drawChart(element, mutatedData, chart_type);
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
          var element = $(panel).find('.div-chart').attr('id');
          var mutatedData = charty.convertDataToDatasets(data);

          charty.drawChart(element, mutatedData, chart_type);
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

    $.ajax({
      type: 'GET',
      data: {
        from: date_from,
        to: date_to
      },
      url: '/rates/' + progression_id + '/get',
      dataType: 'json',
      success: function (data) {
        if (data.values.length) {
          var mutatedData = charty.convertDataToDatasets(data);

          charty.drawChart('#div_chart_' + progression_id, mutatedData, chart_type);
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
