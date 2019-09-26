/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  var moveCharts;

  moveCharts = {
    drawChart : function drawChart(element, data, title = '', chart_type = 'line') {
      var ctx = document.getElementById(element);
      var radarCtx = document.getElementById('radarChart');
      var chart;

      var header = data[0];
      for (i = 0; i < header.length; i++) {
        if (typeof header[i] !== 'string') {
          break;
        }
        var type = (i == 0) ? 'string' : 'number';
        data[0][i] = {
          label: header[i],
          type: type
        };
      }
      var googleChartData = new google.visualization.arrayToDataTable(data);
      progression_type = $('#' + element).attr('progression');

      if (progression_type == 'feedback') {
        var options = {
          title: title,
          bars: 'vertical',
          curveType: 'function',
          vAxis: {
            minValue: 0,
            ticks: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
          },
          chartArea: {
            // height: "450px",
            top: '5%',
            bottom: '20%',
            width: '90%'
          },
          legend: {position: 'bottom'},
          pointSize: 5,
          colors: ['#ebbab2', '#4782a6', '#60d5d5', '#f2188e', '#980299', '#dc3913']
        };
      }
      else {
        var options = {
          title: title,
          bars: 'vertical',
          curveType: 'function',
          vAxis: {
            minValue: 0,
            ticks: [0, 1, 2, 3, 4, 5]
          },
          chartArea: {
            // height: "450px",
            top: '5%',
            bottom: '20%',
            width: '90%'
          },
          legend: {position: 'bottom'},
          pointSize: 5,
          colors: ['#ebbab2', '#4782a6', '#60d5d5', '#f2188e', '#980299', '#dc3913']
        };
      }

      switch (chart_type) {
        case 'line': {
          // radarCtx.classList.add('hidden');
          ctx.classList.remove('hidden');

          chart = new google.visualization.LineChart(ctx);

          chart.draw(googleChartData, options);
          break;
        }
        case 'bar': {
          // radarCtx.classList.add('hidden');
          ctx.classList.remove('hidden');

          chart = new google.visualization.ColumnChart(ctx);

          chart.draw(googleChartData, options);
          break;
        }
        case 'radar': {
          var color = Chart.helpers.color;
          var colors = ['#ebbab2', '#4782a6', '#60d5d5', '#f2188e', '#980299', '#dc3913'];
          ctx.classList.add('hidden');
          radarCtx.classList.remove('hidden');

          // Get labels.
          var labels = data[0].map(function (label) {
            return label.label;
          });
          labels.shift(); // Remove the first element in the array.

          // Get datasets.
          var datasets = data;
          datasets.shift(); // Remove the labels.
          var transformedDataset = datasets.map(function (dataset) {
            var label = dataset[0];
            var data = dataset;
            data.shift(); // Remove the label.

            var transformedData = data.map(function (item) {
              if (item === null) {
                return 0;
              }

              return item;
            });

            return {
              label: label,
              data: transformedData
            };
          });

          // Add colors to the dataset items.
          for (var i = 0; i < transformedDataset.length; i++) {
            transformedDataset[i].borderColor = color(colors[i]).alpha(0.3).rgbString();
            transformedDataset[i].backgroundColor = color(colors[i]).alpha(0.3).rgbString();
          }

          chart = new Chart(radarCtx, {
            type: 'radar',
            data: {
              labels: labels,
              datasets: transformedDataset
            },
            options: {}
          });
          break;
        }
      }
    }
  };

  window.moveCharts = moveCharts;
})(jQuery);


