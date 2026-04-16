@extends('layouts/default')

{{-- Page title --}}
@section('title')
    {{ trans('general.accessory_report') }}
    @parent
@stop


{{-- Page content --}}
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-body">
                        <table
                                data-cookie-id-table="accessoriesReport"
                                data-id-table="accessoriesReport"
                                data-side-pagination="server"
                                data-sort-order="asc"
                                id="accessoriesReport"
                                data-advanced-search="false"
                                class="table table-striped snipe-table"
                                data-url="{{ route('api.accessories.index') }}"
                                data-export-options='{
                        "fileName": "accessory-report-{{ date('Y-m-d') }}",
                        "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>

                            <thead>
                            <tr>
                                <th data-checkbox="true"></th>
                                <th data-field="id" data-visible="false">ID</th>
                                <th class="col-sm-1" data-field="company.name">{{ trans('admin/companies/table.title') }}</th>
                                <th class="col-sm-1" data-field="name">{{ trans('admin/accessories/table.title') }}</th>
                                <th class="col-sm-1" data-field="model_number">{{ trans('general.model_no') }}</th>
                                <th class="col-sm-1" data-field="qty">{{ trans('admin/accessories/general.total') }}</th>
                                <th class="col-sm-1" data-field="remaining_qty">{{ trans('admin/accessories/general.remaining') }}</th>
                                <th class="col-sm-2" data-field="assigned_users" data-formatter="assignedUsersFormatter">Assigned Users</th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="assignedUsersModal" tabindex="-1" role="dialog" aria-labelledby="assignedUsersModalLabel">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('button.close') }}"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="assignedUsersModalLabel">Assigned Users</h4>
                    </div>
                    <div class="modal-body">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>User</th>
                                <th>Count</th>
                            </tr>
                            </thead>
                            <tbody id="assignedUsersModalBody"></tbody>
                        </table>
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
                            $exportDropdown.append('<li><a id="detailed-csv-link" href="{{ route('reports.accessories.export.csv') }}"><i class="fa fa-download"></i> Detailed CSV</a></li>');
                            $exportDropdown.append('<li><a id="detailed-pdf-link" href="{{ route('reports.accessories.export.pdf') }}"><i class="fa fa-file-pdf-o"></i> Detailed PDF</a></li>');
                        }
                    }

                    // Run injection after table is initialized and on every page change/re-render
                    $('#accessoriesReport').on('post-body.bs.table', function () {
                        injectCustomExports();
                    });

                    // Also try once immediately in case post-body already fired
                    setTimeout(injectCustomExports, 500);

                    // Handle custom export click to include selected IDs
                    $(document).on('click', '#detailed-csv-link, #detailed-pdf-link', function(e) {
                        var selections = $('#accessoriesReport').bootstrapTable('getSelections');
                        if (selections.length > 0) {
                            e.preventDefault();
                            var baseUrl = $(this).attr('href').split('?')[0];
                            var ids = selections.map(function(row) {
                                return row.id;
                            });
                            
                            var queryParams = $.param({ ids: ids });
                            window.location.href = baseUrl + '?' + queryParams;
                        }
                    });
                });

                window.assignedUsersLookup = {};
                window.assignedUsersAccessoryNames = {};

                function escapeHtml(value) {
                    return String(value || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function assignedUsersFormatter(value, row) {
                    var assignedCount = row.assigned_users_count || 0;
                    var users = row.assigned_users_breakdown || [];
                    var exportContent = users.map(function (user) {
                        return user.name + ' ' + user.count;
                    }).join(', ');
                    window.assignedUsersLookup[row.id] = users;
                    window.assignedUsersAccessoryNames[row.id] = row.name || 'Accessory';

                    if (!assignedCount) {
                        return '<span class="export-content" style="display:none;" data-export-html="true" data-export-text="0">0</span><span>0</span>';
                    }

                    var exportHtmlRows = '<div>' + escapeHtml(exportContent) + '</div>';

                    return '<span class="export-content" style="display:none;" data-export-html="true" data-export-text="' + escapeHtml(exportContent) + '">' + exportHtmlRows + '</span>'
                        + '<a href="#" onclick="return showAssignedUsersModal(' + row.id + ');">' + assignedCount + '</a>';
                }

                function showAssignedUsersModal(accessoryId) {
                    var users = window.assignedUsersLookup[accessoryId] || [];
                    var accessoryName = window.assignedUsersAccessoryNames[accessoryId] || 'Accessory';
                    var rows = '';

                    if (!users.length) {
                        rows = '<tr><td colspan="2">No assigned users</td></tr>';
                    } else {
                        users.forEach(function (user) {
                            rows += '<tr><td><a href="' + user.url + '">' + user.name + '</a></td><td>' + user.count + '</td></tr>';
                        });
                    }

                    $('#assignedUsersModalLabel').text('Assigned Users - ' + accessoryName);
                    $('#assignedUsersModalBody').html(rows);
                    $('#assignedUsersModal').modal('show');

                    return false;
                }
            </script>

        @stop
