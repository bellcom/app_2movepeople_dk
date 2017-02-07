/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function($) {
    Drupal.behaviors.dashboardCharts = {
        attach: function(context, settings) {
            google.charts.load('current', {packages:['corechart','bar']});


         $("#accordion").accordion({
           activate: function(event, ui) {
             loadGraph(ui.newHeader, ui.newPanel);
            },
            create: function(event, ui) {
             loadGraph(ui.header, ui.panel);
            }
          });


      }
    };

     function loadGraph(header, panel, reload=false) {
        if (panel.find('.div-chart div').length > 0 && reload==false) return;
          var progression_id = header.attr('data-progression-id');
           $.ajax({
                type: 'GET',
                url: 'rates/' + progression_id + '/get',
                dataType: 'json',
                success: function (data) {
                  console.log(data.values.length);
                  if (data.values.length) {
                  google.charts.setOnLoadCallback( function(){ drawChart(panel.find('.div-chart').attr('id'), data) });
                }
                }
              });
          }
          function drawChart(element, data) {
            var arr = [
          [''].concat(data.series),

        ];

       data.values.forEach(function(item){
          arr.push(item);
         // console.log(item);
        });

            var cdata = google.visualization.arrayToDataTable(arr);

        var options = {
          //chart: {
           title:  data.chart_title ,
          //},
          bars: 'vertical',
          vAxis: { minValue: 0,
            ticks: [0, 1, 2, 3, 4, 5]
          },
          height: 350,
          legend: {position: "bottom"},
          pointSize: 10,

        };

        var chart = new google.visualization.LineChart(document.getElementById(element));
        chart.draw(cdata, options);
        $('#' + element).parent('.ui-accordion-content').height($('#' + element).parent('.ui-accordion-content').children('.div-goals').height() + $('#' + element).height());
        }
         $.fn.graphReload = function(element) {
           var panel = $(element).parent('.ui-accordion-content');
           var header = $("#" + panel.attr('aria-labelledby'));
           console.log(panel);
           console.log(header);
           console.log(element);
           loadGraph(header, panel, true);

    }
})(jQuery);


