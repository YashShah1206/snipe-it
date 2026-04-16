@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general.user_report') }}
@parent
@stop

{{-- Page content --}}
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                <div id="toolbar">
                </div>
                <div class="table-responsive">
                    <table
                        data-cookie-id-table="usersReport"
                        data-id-table="usersReport"
                        data-toolbar="#toolbar"
                        data-side-pagination="client"
                        data-sort-order="asc"
                        data-search="true"
                        data-show-columns="true"
                        data-show-export="true"
                        data-show-print="true"
                        data-show-fullscreen="true"
                        id="usersReport"
                        class="table table-striped snipe-table"
                        data-export-options='{
                            "fileName": "user-report-{{ date('Y-m-d') }}",
                            "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                        <thead>
                            <tr role="row">
                                <th data-checkbox="true"></th>
                                <th data-field="id" data-visible="false">ID</th>
                                <th data-field="name" data-sortable="true">{{ trans('admin/users/table.name') }}</th>
                                <th data-field="assets" data-sortable="true">{{ trans('general.assets') }}</th>
                                <th data-field="licenses" data-sortable="true">{{ trans('general.licenses') }}</th>
                                <th data-field="accessories" data-sortable="true">{{ trans('general.accessories') }}</th>
                                <th data-field="consumables" data-sortable="true">{{ trans('general.consumables') }}</th>
                            </tr>
                        </thead>
                         <tbody>
                            @foreach ($users as $user)
                            <tr data-id="{{ $user->id }}">
                                <td></td>
                                <td>{{ $user->id }}</td>
                                <td><a href="{{ route('users.show', $user->id) }}">{{ $user->display_name }}</a></td>
                                <td>
                                    @if ($user->assets_count > 0)
                                        <a href="#" class="view-details" data-type="assets" data-id="{{ $user->id }}" data-name="{{ e($user->display_name) }}">{{ $user->assets_count }}</a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    @if ($user->licenses_count > 0)
                                        <a href="#" class="view-details" data-type="licenses" data-id="{{ $user->id }}" data-name="{{ e($user->display_name) }}">{{ $user->licenses_count }}</a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    @if ($user->accessories_count > 0)
                                        <a href="#" class="view-details" data-type="accessories" data-id="{{ $user->id }}" data-name="{{ e($user->display_name) }}">{{ $user->accessories_count }}</a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    @if ($user->consumables_count > 0)
                                        <a href="#" class="view-details" data-type="consumables" data-id="{{ $user->id }}" data-name="{{ e($user->display_name) }}">{{ $user->consumables_count }}</a>
                                    @else
                                        0
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="detailModalTitle">Details</h4>
            </div>
            <div class="modal-body">
                <div id="detailTableContainer">
                    {{-- Table will be injected here --}}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('general.close') }}</button>
            </div>
        </div>
    </div>
</div>

@stop

@section('moar_scripts')
@include ('partials.bootstrap-table')
<script nonce="{{ csrf_token() }}">
    $(function() {
        // Function to inject custom export items into the bootstrap-table export dropdown
        function injectCustomExports() {
            var $exportDropdown = $('.export .dropdown-menu');
            if ($exportDropdown.length && !$('#detailed-csv-link').length) {
                $exportDropdown.append('<li class="divider"></li>');
                $exportDropdown.append('<li><a id="detailed-csv-link" href="{{ route('reports.users.export.csv') }}"><i class="fa fa-download"></i> Detailed CSV</a></li>');
                $exportDropdown.append('<li><a id="detailed-pdf-link" href="{{ route('reports.users.export.pdf') }}"><i class="fa fa-file-pdf-o"></i> Detailed PDF</a></li>');
            }
        }

        // Run injection after table is initialized and on every page change/re-render
        $('#usersReport').on('post-body.bs.table', function () {
            injectCustomExports();
        });

        // Also try once immediately in case post-body already fired
        setTimeout(injectCustomExports, 500);

        // Handle custom export click to include selected IDs
        $(document).on('click', '#detailed-csv-link, #detailed-pdf-link', function(e) {
            var selections = $('#usersReport').bootstrapTable('getSelections');
            if (selections.length > 0) {
                e.preventDefault();
                var baseUrl = $(this).attr('href').split('?')[0];
                var ids = selections.map(function(row) {
                    // row.id works if data-field="id" is used
                    return row.id;
                });
                
                var queryParams = $.param({ ids: ids });
                window.location.href = baseUrl + '?' + queryParams;
            }
            // If no selections, let the default link behavior (all users) happen
        });

        $(document).on('click', '.view-details', function(e) {
            e.preventDefault();
            var type = $(this).data('type');
            var id = $(this).data('id');
            var name = $(this).data('name');
            
            $('#detailModalTitle').text(name + ' - ' + type.charAt(0).toUpperCase() + type.slice(1));
            $('#detailTableContainer').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
            $('#detailModal').modal('show');

            // We'll use Snipe-IT's standard API endpoints to fill the table
            var apiUrl = '';
            var columns = [];

            apiUrl = '{{ url('reports/users') }}' + '/' + id + '/' + type;

            switch(type) {
                case 'assets':
                    columns = [
                        { field: 'asset_tag', title: '{{ trans('admin/hardware/table.asset_tag') }}', sortable: true },
                        { field: 'name', title: '{{ trans('admin/hardware/form.name') }}', sortable: true },
                        { field: 'model', title: '{{ trans('admin/hardware/form.model') }}', sortable: true },
                        { field: 'serial', title: '{{ trans('admin/hardware/table.serial') }}', sortable: true },
                        { field: 'status', title: '{{ trans('general.status') }}', sortable: true },
                        { field: 'checkout_date', title: '{{ trans('admin/hardware/table.checkout_date') }}', sortable: true }
                    ];
                    break;
                case 'licenses':
                    columns = [
                        { field: 'name', title: '{{ trans('admin/licenses/table.title') }}', sortable: true },
                        { field: 'serial', title: '{{ trans('admin/licenses/form.license_key') }}', sortable: true },
                        { field: 'category', title: '{{ trans('general.category') }}', sortable: true },
                        { field: 'checkout_date', title: '{{ trans('general.date') }}', sortable: true }
                    ];
                    break;
                case 'accessories':
                case 'consumables':
                    columns = [
                        { field: 'name', title: '{{ trans('general.name') }}', sortable: true },
                        { field: 'category', title: '{{ trans('general.category') }}', sortable: true },
                        { field: 'checkout_date', title: '{{ trans('general.date') }}', sortable: true }
                    ];
                    break;
            }

            // Simple table skeleton
            $('#detailTableContainer').html('<table id="detailTable" class="table table-striped"></table>');
            
            $('#detailTable').bootstrapTable({
                url: apiUrl,
                columns: columns,
                search: false,
                showExport: false,
                showColumns: false,
                pagination: false,
                pageSize: 10,
                responseHandler: function (res) {
                    return res.rows;
                }
            });
            
            // Note: Since we are using the internal session for authentication in a report, 
            // the API might need the X-CSRF-TOKEN or just work with the session cookie.
            // Snipe-IT's API usually requires a Bearer token or a valid session.
        });
    });
</script>
@stop
