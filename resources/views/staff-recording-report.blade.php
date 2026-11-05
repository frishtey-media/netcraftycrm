@extends('layouts.admin')

@section('content')

    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <div>
                <h4 class="mb-1 font-weight-bold">Staff Recording Report</h4>
                <p class="text-muted mb-0">
                    Staff-wise call recording report
                </p>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card shadow-sm mb-4">

            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-filter mr-1"></i> Filter Recordings
                </h6>
            </div>

            <div class="card-body">

                <form method="GET" action="{{ route('admin.staff-recording-report') }}">

                    <div class="row align-items-end">

                        {{-- Staff Filter --}}
                        <div class="col-md-4 mb-3">
                            <label class="font-weight-bold">Select Staff</label>

                            <select name="staff_id" class="form-control">
                                <option value="">All Staff</option>

                                @foreach ($staffList as $staff)
                                    <option value="{{ $staff->id }}"
                                        {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach

                            </select>
                        </div>

                        {{-- From Date --}}
                        <div class="col-md-3 mb-3">
                            <label class="font-weight-bold">From Date</label>

                            <input type="date" name="from" class="form-control" value="{{ request('from') }}"
                                required>
                        </div>

                        {{-- To Date --}}
                        <div class="col-md-3 mb-3">
                            <label class="font-weight-bold">To Date</label>

                            <input type="date" name="to" class="form-control" value="{{ request('to') }}"
                                min="{{ request('from') }}" required>
                        </div>

                        {{-- Buttons --}}
                        <div class="col-md-2 mb-3">

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> Filter
                            </button>

                        </div>

                        <div class="col-12">

                            <a href="{{ route('admin.staff-recording-report') }}" class="btn btn-sm btn-outline-secondary">

                                <i class="fas fa-redo mr-1"></i> Reset

                            </a>

                        </div>

                    </div>

                </form>

            </div>
        </div>


        {{-- Recording Report --}}
        @if (request()->filled('from') && request()->filled('to'))
            <div class="card shadow-sm mb-4">

                <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">

                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-microphone-alt mr-1"></i>
                        Recording Details
                    </h6>

                    <small class="text-muted">
                        {{ \Carbon\Carbon::parse(request('from'))->format('d-m-Y') }}
                        to
                        {{ \Carbon\Carbon::parse(request('to'))->format('d-m-Y') }}
                    </small>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">

                            <thead class="thead-light">
                                <tr>
                                    <th width="60">#</th>
                                    <th>Date & Time</th>
                                    <th>Staff Name</th>
                                    <th>Customer Phone</th>
                                    <th>Recording</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($recordings as $index => $recording)
                                    <tr>
                                        <td>{{ $recordings->firstItem() + $index }}</td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($recording->created_at)->format('d-m-Y h:i A') }}
                                        </td>

                                        <td>
                                            <strong>{{ $recording->staff_name ?? 'N/A' }}</strong>
                                        </td>

                                        <td>{{ $recording->customer_phone ?? '-' }}</td>

                                        <td>
                                            @if ($recording->upload_status == 'uploaded' && !empty($recording->recording_path))
                                                <audio controls preload="none" style="width:250px;">
                                                    <source
                                                        src="{{ route('admin.staff-recordings.play', $recording->id) }}"
                                                        type="audio/mpeg">
                                                </audio>
                                            @else
                                                <span class="text-muted">Recording Not Available</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            No recordings found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>

                    </div>

                    {{-- Pagination --}}
                    @if ($recordings->hasPages())
                        <div class="d-flex justify-content-end mt-3">

                            {{ $recordings->appends(request()->query())->links() }}

                        </div>
                    @endif

                </div>

            </div>
        @else
            {{-- Default Empty State --}}
            <div class="card shadow-sm">

                <div class="card-body text-center py-5">

                    <i class="fas fa-microphone-alt fa-3x text-muted mb-3"></i>

                    <h5 class="font-weight-bold">
                        Staff Recording Report
                    </h5>

                    <p class="text-muted mb-0">
                        Please select a staff member and date range,
                        then click Filter to view recordings.
                    </p>

                </div>

            </div>
        @endif

    </div>

@endsection
