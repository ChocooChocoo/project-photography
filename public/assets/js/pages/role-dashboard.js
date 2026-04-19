(function ($) {
    "use strict";

    if (typeof $ === "undefined" || typeof ApexCharts === "undefined" || !window.dashboardPageConfig || !window.dashboardInitialData) {
        return;
    }

    const chartInstances = {};
    let currentDashboard = window.dashboardInitialData;

    function isCircularChart(chart) {
        return chart.type === "donut" || chart.type === "pie";
    }

    function getChartSeries(chart) {
        if (isCircularChart(chart)) {
            return chart.series && chart.series.length ? (chart.series[0].data || []) : [];
        }

        return chart.series || [];
    }

    function buildChartOptions(chart) {
        const baseOptions = {
            chart: {
                type: chart.type,
                height: chart.height || 320,
                toolbar: {
                    show: false
                }
            },
            series: getChartSeries(chart),
            labels: chart.categories,
            xaxis: {
                categories: chart.categories
            },
            stroke: {
                curve: "smooth",
                width: chart.type === "bar" ? 0 : 3
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                position: "bottom"
            },
            grid: {
                borderColor: "#e9ecef",
                strokeDashArray: 4
            },
            colors: ["#3475db", "#22c55e", "#f59e0b", "#0ea5e9", "#ef4444"],
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: "45%"
                },
                pie: {
                    donut: {
                        size: "68%"
                    }
                }
            },
            fill: {
                opacity: chart.type === "area" ? 0.25 : 1
            },
            tooltip: {
                shared: chart.type !== "donut",
                intersect: false
            }
        };

        if (isCircularChart(chart)) {
            delete baseOptions.xaxis;
        }

        return baseOptions;
    }

    function renderCharts(charts) {
        charts.forEach(function (chart) {
            const selector = "#chart-" + chart.key;

            if (!chartInstances[chart.key]) {
                chartInstances[chart.key] = new ApexCharts(document.querySelector(selector), buildChartOptions(chart));
                chartInstances[chart.key].render();
                return;
            }

            const nextOptions = {
                chart: {
                    type: chart.type,
                    height: chart.height || 320
                },
                labels: chart.categories
            };

            if (!isCircularChart(chart)) {
                nextOptions.xaxis = {
                    categories: chart.categories
                };
            }

            chartInstances[chart.key].updateOptions(nextOptions, false, true);
            chartInstances[chart.key].updateSeries(getChartSeries(chart), true);
        });
    }

    function updateKpis(kpis) {
        kpis.forEach(function (kpi) {
            $("[data-kpi-value='" + kpi.key + "']").text(kpi.display_value);
            $("[data-kpi-sub-label='" + kpi.key + "']").text(kpi.sub_label);
            $("[data-kpi-sub-value='" + kpi.key + "']").text(kpi.sub_value);
        });
    }

    function updateTables(tables) {
        tables.forEach(function (table) {
            const $tbody = $("table[data-table-key='" + table.key + "'] tbody");
            let rowsHtml = "";

            if (!table.rows.length) {
                rowsHtml += "<tr><td colspan='" + table.columns.length + "' class='text-center text-muted py-4'>" + table.empty_message + "</td></tr>";
            } else {
                table.rows.forEach(function (row) {
                    rowsHtml += "<tr>";

                    row.forEach(function (cell) {
                        rowsHtml += "<td>" + $("<div>").text(cell).html() + "</td>";
                    });

                    rowsHtml += "</tr>";
                });
            }

            $tbody.html(rowsHtml);
        });
    }

    function updateExportLink(filters) {
        const exportUrl = new URL(window.dashboardPageConfig.exportRoute, window.location.origin);
        exportUrl.searchParams.set("start_date", filters.start_date);
        exportUrl.searchParams.set("end_date", filters.end_date);
        $("#dashboard-export-link").attr("href", exportUrl.toString());
    }

    function setLoadingState(isLoading) {
        $("#dashboard-filter-form :input").prop("disabled", isLoading);
        $("#dashboard-kpis, #dashboard-charts, #dashboard-tables").toggleClass("opacity-50", isLoading);
    }

    function showDashboardError(message) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Dashboard Update Failed",
                text: message,
                confirmButtonColor: "#3475db"
            });
        }
    }

    function applyDashboardData(data) {
        currentDashboard = data;
        updateKpis(data.kpis || []);
        renderCharts(data.charts || []);
        updateTables(data.tables || []);
        updateExportLink(data.filters || {});
    }

    function loadDashboardData() {
        const payload = $("#dashboard-filter-form").serialize();

        setLoadingState(true);

        $.ajax({
            url: window.dashboardPageConfig.filterRoute,
            type: "GET",
            data: payload,
            dataType: "json"
        }).done(function (response) {
            if (response.status !== "success") {
                showDashboardError(response.message || "Unable to refresh dashboard data.");
                return;
            }

            applyDashboardData(response.data);
        }).fail(function (xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : "Unable to refresh dashboard data right now.";

            showDashboardError(message);
        }).always(function () {
            setLoadingState(false);
        });
    }

    $(function () {
        renderCharts(currentDashboard.charts || []);
        updateExportLink(currentDashboard.filters || {});

        $("#dashboard-filter-form").on("submit", function (event) {
            event.preventDefault();
            loadDashboardData();
        });
    });
})(jQuery);
