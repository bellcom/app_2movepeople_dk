/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  var charty;

  charty = {
    truncateString: function (string, length = 25) {
      var ellipsis = '...';

      if (string.length > length) {
        return string.substring(0, length - ellipsis.length) + ellipsis;
      }

      return string;
    },
    generateDataset: function(dataset, chart_type) {
      var type = (chart_type === 'radar') ? 'text' : 'date';

      if (type === 'date') {
        return this._datasetWithDateLabels(dataset);
      }

      return this._datasetWithTextLabels(dataset);
    },
    _datasetWithDateLabels: function(dataset) {
      return {
        labels: dataset.dates,
        datasets: dataset.goals,
      }
    },
    _datasetWithTextLabels: function(dataset) {

      // Generate labels.
      var labels = [];

      for (var i = 0; i < dataset.goals.length; i += 1) {
        var goal = dataset.goals[i];

        labels.push(goal.label);
      }

      // Generate datasets.
      var datasets = [];

      for (var dateIndex = 0; dateIndex < dataset.dates.length; dateIndex += 1) {
        var dateData = [];

        // Run through all goals.
        for (var goalIndex = 0; goalIndex < dataset.goals.length; goalIndex += 1) {
          dateData.push(dataset.goals[goalIndex].values[dateIndex]);
        }

        datasets.push({
          label: dataset.dates[dateIndex],
          values: dateData,
        });
      }

      return {
        labels: labels,
        datasets: datasets,
      }
    },
    isIterable: function (obj) {
      if (obj == null) {
        return false;
      }

      return typeof obj[Symbol.iterator] === 'function';
    },
    drawChart: function (element, data, chart_type = 'line') {
      var iteration = 1;
      var colors = ['#ebbab2', '#4782a6', '#60d5d5', '#f2188e', '#980299', '#dc3913'];
      var canvas = document.createElement('CANVAS');
      var container = (typeof element === 'string') ? document.querySelector(element) : element;

      // Help text.
      var helpText = (chart_type === 'radar') ? 'Klik på datoerne få at skjule/vise linje' : 'Klik på kategorien få at skjule/vise linje';
      var helpNode = document.createElement('DIV');
      helpNode.classList.add('text-center');
      helpNode.innerText = helpText;

      canvas.style.height = '30vh';
      container.innerHTML = '';
      container.appendChild(canvas);
      container.appendChild(document.createElement('BR'));
      container.appendChild(helpNode);
      container.appendChild(document.createElement('BR'));

      var ctx = canvas.getContext('2d');
      var labels = [];
      var datasets = [];
      var options = {
        maintainAspectRatio: false,
        legend: {
          position: 'bottom',
          display: true,
        },
        animation: {
          duration: 0
        },
        hover: {
          animationDuration: 0
        },
        responsiveAnimationDuration: 0,
        scales: {
          yAxes: [{
            ticks: {
              suggestedMax: 10,
              beginAtZero: true
            }
          }]
        }
      };

      // Labels.
      if (charty.isIterable(data.labels)) {
        for (var label of data.labels) {
          labels.push(charty.truncateString(label, 25));
        }
      }
      else {
        labels.push(charty.truncateString(data.labels[1], 25));
      }

      // Datasets.
      for (var dataset of data.datasets) {
        datasets.push({
          label: dataset.label,
          data: dataset.values,
          backgroundColor: colors[iteration],
          borderColor: colors[iteration],
          fill: false,
          borderWidth: 2
        });

        iteration++;
      }

      new Chart(ctx, {
        type: chart_type,
        data: {
          labels: labels,
          datasets: datasets
        },
        options: options,
      });
    }
  };

  window.charty = charty;
})(jQuery);
