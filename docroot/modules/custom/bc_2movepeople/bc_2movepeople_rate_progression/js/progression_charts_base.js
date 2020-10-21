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
    convertDataToDatasets : function (data) {
      var mutatedDatasets = [];

      for (var dataset of data.values) {
        var shiftedDataset = dataset.slice();
        shiftedDataset.shift();

        var mutatedDataset = {
          label: false,
          values: shiftedDataset
        };

        mutatedDatasets.push(mutatedDataset);
      }

      var mutatedData = {
        labels: (typeof data.series === 'string') ? [data.series] : data.series, // Cast to array.
        datasets: mutatedDatasets,
      };

      return mutatedData;
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
      container.appendChild(helpNode);

      var ctx = canvas.getContext('2d');
      var labels = [];
      var datasets = [];
      var options = {
        maintainAspectRatio: false,
        legend: {
          position: 'bottom',
          display: true
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
