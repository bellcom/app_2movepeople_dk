/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  Drupal.behaviors.totalChart = {
    attach: function (context, settings) {
      var wrappers = document.querySelectorAll('.progression-chart');

      for(var i = 0; i < wrappers.length; i += 1) {
        var currentElement = wrappers[i];

        $(this).graphTotalLoad('line', currentElement);
      }

      $('.line-graph-btn').change(function () {
        var element = this;
        var wrapper = element.closest('.progression-chart');
        $('#progression_total_chart').text('');

        $(this).graphTotalLoad('line', wrapper);
      });

      $('.bar-graph-btn').change(function () {
        var element = this;
        var wrapper = element.closest('.progression-chart');
        $('#progression_total_chart').text('');

        $(this).graphTotalLoad('bar', wrapper);
      });

      $('.radar-graph-btn').change(function () {
        var element = this;
        var wrapper = element.closest('.progression-chart');
        $('#progression_total_chart').text('');

        $(this).graphTotalLoad('radar', wrapper);
      });
    }
  };

  $.fn.graphTotalLoad = function (chart_type = 'line', wrapper) {
    var table = wrapper.querySelector('#progression_total_table');

    if (table) {
      var columns = GetColumnCount(table.querySelectorAll('table'));
      var arr = [];

      for (var i = 1; i <= columns; i++) {
        arr[i - 1] = [$(table).find('th[data-target=col_' + i + ']').text()];

        $(table).find('td[data-target=col_' + i + ']').each(function () {
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

      var type = (chart_type === 'radar') ? 'text' : 'date';
      var dataset = generateDataset(type, arr);

      // On load.
      charty.drawChart(wrapper.querySelector('.progression-total-chart'), dataset, chart_type);
    }
  };

  function generateDataset(type, dataset) {
    if (type === 'date') {
      return _datasetWithDateLabels(dataset);
    }

    return _datasetWithTextLabels(dataset);
  }

  function _datasetWithDateLabels(dataset) {
    var labels = dataset[0].slice();
    labels.shift();

    var mutatedDatasets = [];
    var datasets = dataset.slice();
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

    var data = {
      labels: labels,
      datasets: mutatedDatasets
    };

    return data;
  }

  function _datasetWithTextLabels(dataset) {
    var labels = dataset[0].slice();
    labels.shift();

    var mutatedDatasets = [];
    var datasets = dataset.slice();
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

    var data = {
      labels: labels,
      datasets: mutatedDatasets
    };

    return data;
  }

  function GetColumnCount(table) {
    var ColCount = 0;

    $(table).find('tr').eq(0).find('th,td').each(function () {
      ColCount += $(this).attr('colspan') ? parseInt($(this).attr('colspan')) : 1;
    });

    return ColCount;
  }

})(jQuery);


