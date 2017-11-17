/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  Drupal.behaviors.dashboardCharts = {
      attach: function (context, settings) {
          //google.charts.load('current', {packages: ['corechart', 'bar']});
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

              $(window).resize(function() {
                
                var panel = $('.panel-collapse.collapse.in').parent(); 
                var heading = $(panel).find('.panel-heading');

                if (heading.length > 0) {
                  var progression_id = $(heading).attr('data-progression-id');
                  
                  if (DashboardChart.data[progression_id].length > 0) {  
                    var element = $(panel).find('.div-chart').attr('id');
                    var chart_type = $("input[name='chart_type-" + progression_id + "']:checked").val();
                    drawChart(element, DashboardChart.data[progression_id], DashboardChart.header[progression_id], chart_type);
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

          $(".btn-update").click(function () {
              var progression_id = $(this).attr('data-progression-id');
              updateProgressionChart(progression_id);
          });

          $(".line-graph-btn").change(function () {
            var progression_id = $(this).attr('data-progression-id');
            var header = $("#accordion-progressions-heading-" + progression_id);
            var panel = $(header).parent().find('.panel-body');
            $("#div_chart_" + progression_id).text('');
            DashboardChart.load(header, panel, true, 'line');
          });
          $(".bar-graph-btn").change(function () {
            var progression_id = $(this).attr('data-progression-id');
            var header = $("#accordion-progressions-heading-" + progression_id);
            var panel = $(header).parent().find('.panel-body');
            $("#div_chart_" + progression_id).text('');
            DashboardChart.load(header, panel, true, 'bar');
          });
      }
  };

  Drupal.behaviors.totalChart = {
      attach: function (context, settings) {
          //google.charts.load('current', {packages: ['corechart', 'bar']});
          google.charts.load('current', {packages: ['corechart']});
          $(this).graphTotalLoad();

          $(".line-graph-btn").change(function () {
            $("#progression_total_chart").text('');
            $(this).graphTotalLoad('line');
          });
          $(".bar-graph-btn").change(function () {
            $('#progression_total_chart').text('');
            $(this).graphTotalLoad('bar');
          });
      }
  };

  DashboardChart = {
    data: {},
    header : {},
    
    load: function(header, panel, reload, chart_type = 'line') {
      if ($(panel).find('.div-chart div').length > 0 && reload == false)
          return;

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
                      DashboardChart.data[progression_id] = arr;
                      DashboardChart.header[progression_id] = data.chart_title;
                      drawChart(element, arr, data.chart_title, chart_type);
                  });
              } else {
                  $(panel).find('.div-form').hide();
              }
          }
      });
    }
  };

  function loadGraph(header, panel, reload, chart_type = 'line') {
      if ($(panel).find('.div-chart div').length > 0 && reload == false)
          return;

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

                      drawChart(element, arr, data.chart_title, chart_type);
                  });
              } else {
                  $(panel).find('.div-form').hide();
              }
          }
      });
  }

  function drawChart(element, data, title = "", chart_type = 'line') {
      var cdata = google.visualization.arrayToDataTable(data);

      var options = {
          title: title,
          bars: 'vertical',
          vAxis: {minValue: 0,
              ticks: [0, 1, 2, 3, 4, 5]
          },
          chartArea: {
              //height: "450px",
              top: '5%',
              bottom: '20%',
              width: "90%",
              is3D: true
          },
          legend: {position: "bottom"},
          pointSize: 10,
          is3D: true
      };

      var chart = (chart_type == 'line' ? 
              new google.visualization.LineChart(document.getElementById(element)) : 
              new google.visualization.ColumnChart(document.getElementById(element)));

      google.visualization.events.addOneTimeListener(chart, 'ready', function () {
        addChartGradient(chart);
      });
      chart.draw(cdata, options);
  }

  function addChartGradient(chart) {
    var chartDiv = chart.getContainer();
    var svg = chartDiv.getElementsByTagName('svg')[0];
    var properties = {
      id: "chartGradient",
      x1: "0%",
      y1: "0%",
      x2: "0%",
      y2: "100%",
      stops: [
        { offset: '5%', 'stop-color': '#f60' },
        { offset: '95%', 'stop-color': '#ff6' }
      ]
    };


    createGradient(svg, properties);
    var chartPath = svg.getElementsByTagName('path')[1];  //0 path corresponds to legend path
    chartPath.setAttribute('stroke', 'url(#chartGradient)');
    //chartPath.attr('fill', 'url(#chartGradient)');
  }

  function createGradient(svg, properties) {
    var svgNS = svg.namespaceURI;
    var grad = document.createElementNS(svgNS, 'linearGradient');
    grad.setAttribute('id', properties.id);
    ["x1","y1","x2","y2"].forEach(function(name) {
      if (properties.hasOwnProperty(name)) {
        grad.setAttribute(name, properties[name]);
      }
    });
    for (var i = 0; i < properties.stops.length; i++) {
      var attrs = properties.stops[i];
      var stop = document.createElementNS(svgNS, 'stop');
      for (var attr in attrs) {
        if (attrs.hasOwnProperty(attr)) stop.setAttribute(attr, attrs[attr]);
      }
      grad.appendChild(stop);
    }

    var defs = svg.querySelector('defs') ||
      svg.insertBefore(document.createElementNS(svgNS, 'defs'), svg.firstChild);
    return defs.appendChild(grad);
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
                        [''].concat(Object.values(data.series)),
                    ];
                    data.values.forEach(function (item) {
                        arr.push(item);
                    });
                    drawChart("div_chart_" + progression_id, arr, data.chart_title, chart_type)
                });
            } else {
              //  $("#dialog-message").dialog("open");
            }
        }
    });
  }
    
  $.fn.graphReload = function (element) {
      var panel = $(element).parent('.ui-accordion-content');
      var header = $("#" + panel.attr('aria-labelledby'));
      loadGraph(header, panel, true);
  };

  $.fn.graphTotalLoad = function (chart_type = 'line') {
      if ($('#progression_total_table').length > 0) {
          columns = GetColumnCount($("#progression_total_table table"));
          var arr = [];
          for (var i = 1; i <= columns; i++) {
              arr[i - 1] = [$("#progression_total_table table th[data-target=col_" + i + "]").text()];
              $("#progression_total_table table td[data-target=col_" + i + "]").each(function () {
                  if (!isNaN(parseFloat($(this).text())))
                      arr[i - 1].push(parseFloat($(this).text()));
                  else
                      arr[i - 1].push($(this).text());
              });

          }
          google.charts.setOnLoadCallback(function () {
              drawChart("progression_total_chart", arr, '', chart_type);
          });
          
          $(window).resize(function() {
            drawChart("progression_total_chart", arr, '', chart_type);
          });
      }
  };
  
  function GetColumnCount(table) {
      var ColCount = 0;
      $(table).find("tr").eq(0).find("th,td").each(function () {
          ColCount += $(this).attr("colspan") ? parseInt($(this).attr("colspan")) : 1;
      });

      return ColCount;
  }

//  $(window).resize(function() {
//    if ($('#progression_total_table').length > 0) {
//      alert(555);
//    }
//  });

})(jQuery);


