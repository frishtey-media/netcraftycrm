@extends('layouts.admin')

@section('content')
    <div class="container">

        <div class="card p-4 shadow-sm">

            <h4 class="mb-3">🔗 Client ↔ Staff Mapping</h4>

            {{-- SUCCESS --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('client_staff_save') }}">
                @csrf

                {{-- CLIENT SELECT --}}
                <div class="mb-3">
                    <label>Select Client</label>
                    <select name="client_id" class="form-control" required>
                        <option value="">-- Select Client --</option>

                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->client_name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- STAFF CHECKBOX --}}
                <div class="mb-3">
                    <label>Select Staff</label>

                    <div class="row">

                        @foreach ($staffs as $staff)
                            <div class="col-md-4 mb-2">

                                <div class="form-check">
                                    <input type="checkbox" name="staff_ids[]" value="{{ $staff->id }}"
                                        class="form-check-input" id="staff{{ $staff->id }}">

                                    <label class="form-check-label" for="staff{{ $staff->id }}">
                                        {{ $staff->name }}
                                    </label>
                                </div>

                            </div>
                        @endforeach

                    </div>
                </div>

                <button class="btn btn-primary w-100">
                    Save Mapping
                </button>

            </form>

        </div>

    </div>
@endsection
