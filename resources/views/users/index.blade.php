@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">

                <div>
                    <h3 class="mb-0">Client Users Management</h3>
                    <small class="text-muted">
                        Create & Manage Client Login Accounts
                    </small>
                </div>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i>
                    Add User
                </button>

            </div>
        </div>

        {{-- Users Table --}}
        <div class="card shadow-sm">

            <div class="card-body">

                <table id="usersTable" class="table table-bordered table-hover">

                    <thead class="table-dark">

                        <tr>
                            <th width="70">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Client</th>
                            <th width="120">Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($users as $key => $user)
                            <tr>

                                <td>{{ $key + 1 }}</td>

                                <td>{{ $user->name }}</td>

                                <td>{{ $user->email }}</td>

                                <td>
                                    {{ $user->client->client_name ?? 'N/A' }}
                                </td>

                                <td>

                                    <button class="btn btn-warning btn-sm editBtn" data-id="{{ $user->id }}"
                                        data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                        data-client="{{ $user->client_id }}">

                                        Edit

                                    </button>

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- Add User Modal --}}
    <div class="modal fade" id="addUserModal" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <form action="{{ route('users.store') }}" method="POST">

                    @csrf

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Create Client User
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="mb-3">

                            <label>Name</label>

                            <input type="text" name="name" class="form-control" required>

                        </div>

                        <div class="mb-3">

                            <label>Email</label>

                            <input type="email" name="email" class="form-control" required>

                        </div>

                        <div class="mb-3">

                            <label>Password</label>

                            <input type="password" name="password" class="form-control" required>

                        </div>

                        <div class="mb-3">

                            <label>Client</label>

                            <select name="client_id" class="form-control" required>
                                <option value="">Select Client</option>

                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="submit" class="btn btn-primary">

                            Create User

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    {{-- Edit Modal --}}
    <div class="modal fade" id="editUserModal" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <form id="editForm" method="POST">

                    @csrf
                    @method('PUT')

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Edit User
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        <div class="mb-3">

                            <label>Name</label>

                            <input type="text" id="edit_name" name="name" class="form-control" required>

                        </div>

                        <div class="mb-3">

                            <label>Email</label>

                            <input type="email" id="edit_email" name="email" class="form-control" required>

                        </div>

                        <div class="mb-3">

                            <label>New Password</label>

                            <input type="password" name="password" class="form-control">

                            <small class="text-muted">
                                Leave blank if no change
                            </small>

                        </div>

                        <div class="mb-3">

                            <label>Client</label>

                            <select name="client_id" id="edit_client" class="form-control">

                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button class="btn btn-success">

                            Update User

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    {{-- DataTable --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <script>
        $(function() {

            $('#usersTable').DataTable();

            $('.editBtn').click(function() {

                let id = $(this).data('id');
                let name = $(this).data('name');
                let email = $(this).data('email');
                let client = $(this).data('client');

                $('#edit_name').val(name);
                $('#edit_email').val(email);
                $('#edit_client').val(client);

                $('#editForm').attr(
                    'action',
                    '/users/' + id
                );

                $('#editUserModal').modal('show');
            });

        });
    </script>
@endsection
