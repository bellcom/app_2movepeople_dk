/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  Drupal.behaviors.totalChart = {
    attach: function (context, settings) {
      $(this).graphTotalLoad();

      $('.line-graph-btn').change(function () {
        $('#progression_total_chart').text('');

        $(this).graphTotalLoad('line');
      });

      $('.bar-graph-btn').change(function () {
        $('#progression_total_chart').text('');

        $(this).graphTotalLoad('bar');
      });

      $('.radar-graph-btn').change(function () {
        $('#progression_total_chart').text('');

        $(this).graphTotalLoad('radar');
      });
    }
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
          else if (cellContent === '') {
            arr[i - 1].push(0);
          }
          else {
            arr[i - 1].push(cellContent);
          }
        });
      }

      // Mutate labels.
      var mutatedLabels = arr[0].slice();
      mutatedLabels.shift();

      // Mutate data.
      var mutatedDatasets = [];
      var datasets = arr.slice();
      datasets.shift();

      for (var dataset of datasets) {
        var label = dataset[0];
        var mutatedDataset = dataset.slice();
        mutatedDataset.shift();

        var mutatedDataset = {
          label: label,
          values: mutatedDataset
        };

        mutatedDatasets.push(mutatedDataset);
      }

      var mutatedData = {
        labels: mutatedLabels,
        datasets: mutatedDatasets
      };

      // On load.
      charty.drawChart('progression_total_chart', mutatedData, chart_type);

      // Resize.
      $(window).resize(function () {
        charty.drawChart('progression_total_chart', mutatedData, chart_type);
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


