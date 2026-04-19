<div class="content-page">
    <div class="container-fluid">
        <div class="page-title-head d-flex flex-wrap align-items-center gap-3 mb-4">
            <div class="flex-grow-1">
                <h4 class="fs-xl fw-bold m-0">{{ $dashboardConfig['title'] }}</h4>
                <p class="text-muted mt-1 mb-0">{{ $dashboard['meta']['subtitle'] ?? $dashboardConfig['subtitle'] }}</p>
            </div>

            <div class="ms-auto">
                <form
                    id="dashboard-filter-form"
                    class="row g-2 align-items-end"
                    data-filter-url="{{ $dashboardConfig['filterRoute'] }}"
                    data-export-url="{{ $dashboardConfig['exportRoute'] }}"
                >
                    <div class="col-auto">
                        <label for="dashboard-start-date" class="form-label mb-1">Start Date</label>
                        <input
                            type="date"
                            id="dashboard-start-date"
                            name="start_date"
                            class="form-control"
                            value="{{ $dashboard['filters']['start_date'] }}"
                        >
                    </div>
                    <div class="col-auto">
                        <label for="dashboard-end-date" class="form-label mb-1">End Date</label>
                        <input
                            type="date"
                            id="dashboard-end-date"
                            name="end_date"
                            class="form-control"
                            value="{{ $dashboard['filters']['end_date'] }}"
                        >
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-filter me-1"></i>Apply
                        </button>
                    </div>
                    <div class="col-auto">
                        <a id="dashboard-export-link" href="{{ $dashboardConfig['exportRoute'] }}?start_date={{ $dashboard['filters']['start_date'] }}&end_date={{ $dashboard['filters']['end_date'] }}" class="btn btn-outline-secondary">
                            <i class="ti ti-download me-1"></i>Export CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 align-items-center mb-4" id="dashboard-kpis">
            @foreach($dashboard['kpis'] as $kpi)
                <div class="col">
                    <div class="card h-100" data-kpi-card="{{ $kpi['key'] }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h5 class="mb-0">{{ $kpi['label'] }}</h5>
                                <p class="mb-0 fs-lg">
                                    <i class="{{ $kpi['icon'] }} text-{{ $kpi['color'] }}"></i>
                                </p>
                            </div>
                            <div class="d-flex align-items-center gap-2 my-3">
                                <div class="avatar-md flex-shrink-0">
                                    <span class="avatar-title text-bg-{{ $kpi['color'] }} bg-opacity-75 rounded-circle fs-22">
                                        <i class="{{ $kpi['icon'] }}"></i>
                                    </span>
                                </div>
                                <h3 class="mb-0" data-kpi-value="{{ $kpi['key'] }}">{{ $kpi['display_value'] }}</h3>
                            </div>
                            <p class="mb-0">
                                <span class="text-{{ $kpi['color'] }}"><i class="ti ti-point-filled"></i></span>
                                <span class="text-nowrap text-muted" data-kpi-sub-label="{{ $kpi['key'] }}">{{ $kpi['sub_label'] }}</span>
                                <span class="float-end fw-semibold" data-kpi-sub-value="{{ $kpi['key'] }}">{{ $kpi['sub_value'] }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3" id="dashboard-charts">
            @foreach($dashboard['charts'] as $chart)
                <div class="col-12 col-xl-6">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4 class="card-title mb-0">{{ $chart['title'] }}</h4>
                            <span class="badge badge-soft-primary">{{ $dashboard['filters']['label'] }}</span>
                        </div>
                        <div class="card-body">
                            <div id="chart-{{ $chart['key'] }}" data-chart-key="{{ $chart['key'] }}" style="height: {{ $chart['height'] ?? 320 }}px;"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-3 mt-1" id="dashboard-tables">
            @foreach($dashboard['tables'] as $table)
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">{{ $table['title'] }}</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" data-table-key="{{ $table['key'] }}">
                                    <thead>
                                    <tr>
                                        @foreach($table['columns'] as $column)
                                            <th>{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($table['rows'] as $row)
                                        <tr>
                                            @foreach($row as $cell)
                                                <td>{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($table['columns']) }}" class="text-center text-muted py-4" data-empty-message="{{ $table['key'] }}">
                                                {{ $table['empty_message'] }}
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
