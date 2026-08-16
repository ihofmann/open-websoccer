/******************************************************

  This file is part of OpenWebSoccer-Sim.

  OpenWebSoccer-Sim is free software: you can redistribute it
  and/or modify it under the terms of the
  GNU Lesser General Public License
  as published by the Free Software Foundation, either version 3 of
  the License, or any later version.

  OpenWebSoccer-Sim is distributed in the hope that it will be
  useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public
  License along with OpenWebSoccer-Sim.
  If not, see <http://www.gnu.org/licenses/>.

******************************************************/

import Chart from "chart.js/auto";

(function () {
  "use strict";

  function parseSeries(el) {
    var raw = el.dataset.series;
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function ensureCanvas(el) {
    var canvas = el.querySelector("canvas");
    if (!canvas) {
      canvas = document.createElement("canvas");
      el.innerHTML = "";
      el.appendChild(canvas);
    }
    return canvas;
  }

  /* ------------------------------------------------------------------ */
  /* Grades line chart                                                  */
  /* ------------------------------------------------------------------ */
  function initGradesChart() {
    var el = document.getElementById("grades");
    if (!el) return;
    var series = parseSeries(el);
    if (!series) return;

    var labels = series.map(function (v, i) {
      return i + 1;
    });

    new Chart(ensureCanvas(el), {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            data: series,
            borderColor: "#006699",
            backgroundColor: "rgba(0,102,153,0.1)",
            fill: false,
            tension: 0.1,
            pointRadius: 3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { ticks: { stepSize: 1, precision: 0 } },
          y: { min: 0, max: 6 },
        },
        plugins: { legend: { display: false } },
      },
    });
  }

  /* ------------------------------------------------------------------ */
  /* League history line chart                                          */
  /* ------------------------------------------------------------------ */
  function initLeagueHistoryChart() {
    var el = document.getElementById("leaguehistorychart");
    if (!el) return;
    var series = parseSeries(el);
    if (!series) return;
    var maxPos = parseInt(el.dataset.maxpos, 10) || 1;

    var labels = series.map(function (v, i) {
      return i + 1;
    });

    new Chart(ensureCanvas(el), {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            data: series,
            borderColor: "#006699",
            backgroundColor: "rgba(0,102,153,0.1)",
            fill: false,
            tension: 0.1,
            pointRadius: 3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { ticks: { stepSize: 1, precision: 0 } },
          y: { min: 1, max: maxPos, reverse: true, ticks: { precision: 0 } },
        },
        plugins: { legend: { display: false } },
      },
    });
  }

  /* ------------------------------------------------------------------ */
  /* Pie / doughnut charts                                              */
  /* ------------------------------------------------------------------ */
  function initPieChart(el) {
    var series = parseSeries(el);
    if (!series) return;

    var labels = series.map(function (s) {
      return s.label;
    });
    var data = series.map(function (s) {
      return parseFloat(s.data);
    });
    var colors = series.map(function (s) {
      return s.color || "#999999";
    });

    var chart = new Chart(ensureCanvas(el), {
      type: "doughnut",
      data: {
        labels: labels,
        datasets: [
          {
            data: data,
            backgroundColor: colors,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                var total = ctx.dataset.data.reduce(function (a, b) {
                  return a + b;
                }, 0);
                var pct = Math.round((ctx.parsed / total) * 100);
                return ctx.label + ": " + pct + "%";
              },
            },
          },
        },
      },
    });

    /* render a textual legend into the external label container */
    var labelContainer = el.parentElement.querySelector(".pieChartLabel");
    if (labelContainer) {
      labelContainer.innerHTML = "";
    }
  }

  function initAllPieCharts() {
    document.querySelectorAll(".pieChart").forEach(initPieChart);
  }

  /* ------------------------------------------------------------------ */
  /* Bootstrap                                                          */
  /* ------------------------------------------------------------------ */
  function initAll() {
    initGradesChart();
    initLeagueHistoryChart();
    initAllPieCharts();
  }

  document.addEventListener("DOMContentLoaded", initAll);

  /* re-render pie charts after AJAX content updates */
  document.addEventListener("ws:ajaxComplete", initAllPieCharts);
})();
