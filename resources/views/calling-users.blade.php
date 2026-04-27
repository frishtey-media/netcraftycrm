@extends('layouts.admin')
@section('content')
    <div class="container">

        <h3>Calling Staff Management</h3>

        <!-- Add Staff -->
        <div class="card mb-4 p-3">
            <form method="POST" action="{{ route('calling.users.store') }}">
                @csrf

                <div class="row">
                    <div class="col">
                        <input name="name" class="form-control" placeholder="Name" required>
                    </div>

                    <div class="col">
                        <input name="email" class="form-control" placeholder="Email" required>
                    </div>

                    <div class="col">
                        <input name="password" type="password" class="form-control" placeholder="Password" required>
                    </div>

                    <div class="col">
                        <button class="btn btn-primary w-100">Add Staff</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Staff List -->
        <div class="card p-3">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>

                            <td>
                                @if ($user->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Blocked</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('calling.users.toggle', $user->id) }}"
                                    class="btn btn-sm {{ $user->status ? 'btn-danger' : 'btn-success' }}">

                                    {{ $user->status ? 'Block' : 'Activate' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div>
@endsection
