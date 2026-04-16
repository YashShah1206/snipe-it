@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('general.license_report') }} 
@parent
@stop

{{-- Page content --}}
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-body">
                    <table
                            data-cookie-id-table="licensesReport"
                            data-id-table="licensesReport"
                            data-side-pagination="client"
                            data-sort-order="asc"
                            id="licensesReport"
                            data-advanced-search="false"
                            class="table table-striped snipe-table"
                            data-export-options='{
                        "fileName": "license-report-{{ date('Y-m-d') }}",
                        "ignoreColumn": ["actions","image","change","checkbox","checkincheckout","icon"]
                        }'>
                        <thead>
                            <tr role="row">
                                <th data-checkbox="true"></th>
                                <th data-field="id" data-visible="false">ID</th>
                                <th class="col-sm-1">{{ trans('admin/companies/table.title') }}</th>
                                <th class="col-sm-1">{{ trans('admin/licenses/table.title') }}</th>
                                <th class="col-sm-1">{{ trans('admin/licenses/form.license_key') }}</th>
                                <th class="col-sm-1">{{ trans('admin/licenses/form.seats') }}</th>
                                <th class="col-sm-1">{{ trans('admin/licenses/form.remaining_seats') }}</th>
                                <th class="col-sm-1">Assigned</th>
                                <th class="col-sm-1">{{ trans('admin/licenses/form.expiration') }}</th>
                                <th class="col-sm-1">{{ trans('admin/licenses/form.termination_date') }}</th>
                                <th class="col-sm-1">{{ trans('general.purchase_date') }}</th>
                                <th class="col-sm-1 text-right" class="col-sm-1">{{ trans('general.purchase_cost') }}</th>
                                <th class="col-sm-1">{{ trans('general.depreciation') }}</th>
                                <th class="col-sm-1 text-right">{{ trans('admin/hardware/table.book_value') }}</th>
                                <th class="col-sm-1 text-right">{{ trans('admin/hardware/table.diff') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($licenses as $license)
                            @php
                                $assignedBreakdown = $license->licenseseats
                                    ->filter(fn ($seat) => $seat->user || $seat->asset)
                                    ->groupBy(function ($seat) {
                                        if ($seat->user) {
                                            return 'user-'.$seat->user->id;
                                        }

                                        return 'asset-'.$seat->asset->id;
                                    })
                                    ->map(function ($seats, $key) {
                                        $firstSeat = $seats->first();

                                        if ($firstSeat->user) {
                                            return [
                                                'name' => $firstSeat->user->display_name,
                                                'type' => 'User',
                                                'count' => $seats->count(),
                                                'url' => route('users.show', $firstSeat->user->id),
                                            ];
                                        }

                                        return [
                                            'name' => $firstSeat->asset->display_name,
                                            'type' => 'Asset',
                                            'count' => $seats->count(),
                                            'url' => route('hardware.show', $firstSeat->asset->id),
                                        ];
                                    })
                                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                                    ->values();
                                $assignedCount = $assignedBreakdown->sum('count');
                                $assignedExport = $assignedBreakdown->map(fn ($entry) => $entry['name'].' ['.$entry['type'].'] '.$entry['count'])->implode(', ');
                            @endphp
                            <tr data-id="{{ $license->id }}">
                                <td></td>
                                <td>{{ $license->id }}</td>
                                <td>{{ is_null($license->company) ? '' : $license->company->name }}</td>
                                <td>{{ $license->name }}</td>
                                <td>
                                    @can('viewKeys', $license)
                                        {{ $license->serial }}
                                    @else
                                        ------------
                                    @endcan
                                </td>
                                <td>{{ $license->seats }}</td>
                                <td>{{ $license->remaincount() }}</td>
                                <td>
                                    <span class="export-content" style="display:none;" data-export-text="{{ e($assignedExport ?: '0') }}">{{ $assignedExport ?: '0' }}</span>
                                    @if ($assignedCount > 0)
                                        <a href="#"
                                           onclick="return showLicenseAssignmentsModal(this);"
                                           data-license-name="{{ e($license->name) }}"
                                           data-license-assignments='@json($assignedBreakdown)'>{{ $assignedCount }}</a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>{{ $license->expires_formatted_date }}</td>
                                <td>{{ $license->terminates_formatted_date }}
                                    @if ($license->isTerminated())
                                        <span class="text-danger">
                                        <x-icon type="warning" class="text-warning" />
                                        </span>
                                    @endif</td>
                                <td>{{ $license->purchase_date }}</td>
                                <td class="text-right">
                                    {{ $snipeSettings->default_currency }}{{ Helper::formatCurrencyOutput($license->purchase_cost) }}
                                </td>
                                <td>
                                    {{ ($license->depreciation) ? e($license->depreciation->name).' ('.$license->depreciation->months.' '.trans('general.months').')' : ''  }}
                                </td>
                                <td class="text-right">
                                    {{ $snipeSettings->default_currency }}{{ Helper::formatCurrencyOutput($license->getDepreciatedValue()) }}
                                </td>
                                <td class="text-right">
                                    -{{ $snipeSettings->default_currency }}{{ Helper::formatCurrencyOutput(($license->purchase_cost - $license->getDepreciatedValue())) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="licenseAssignmentsModal" tabindex="-1" role="dialog" aria-labelledby="licenseAssignmentsModalLabel">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('button.close') }}"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="licenseAssignmentsModalLabel">Assigned To</h4>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Count</th>
                    </tr>
                    </thead>
                    <tbody id="licenseAssignmentsModalBody"></tbody>
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
                    $exportDropdown.append('<li><a id="detailed-csv-link" href="{{ route('reports.licenses.export.csv') }}"><i class="fa fa-download"></i> Detailed CSV</a></li>');
                    $exportDropdown.append('<li><a id="detailed-pdf-link" href="{{ route('reports.licenses.export.pdf') }}"><i class="fa fa-file-pdf-o"></i> Detailed PDF</a></li>');
                }
            }

            // Run injection after table is initialized and on every page change/re-render
            $('#licensesReport').on('post-body.bs.table', function () {
                injectCustomExports();
            });

            // Also try once immediately in case post-body already fired
            setTimeout(injectCustomExports, 500);

            // Handle custom export click to include selected IDs
            $(document).on('click', '#detailed-csv-link, #detailed-pdf-link', function(e) {
                var selections = $('#licensesReport').bootstrapTable('getSelections');
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

        function showLicenseAssignmentsModal(link) {
            var assignments = $(link).data('license-assignments') || [];
            var licenseName = $(link).data('license-name') || 'License';
            var rows = '';

            if (!assignments.length) {
                rows = '<tr><td colspan="3">No assignments</td></tr>';
            } else {
                assignments.forEach(function (assignment) {
                    rows += '<tr>'
                        + '<td><a href="' + assignment.url + '">' + assignment.name + '</a></td>'
                        + '<td>' + assignment.type + '</td>'
                        + '<td>' + assignment.count + '</td>'
                        + '</tr>';
                });
            }

            $('#licenseAssignmentsModalLabel').text('Assigned To - ' + licenseName);
            $('#licenseAssignmentsModalBody').html(rows);
            $('#licenseAssignmentsModal').modal('show');

            return false;
        }
    </script>
@stop
