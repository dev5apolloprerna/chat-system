@extends('layouts.app')

@section('title', 'User List')

@section('content')


    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                {{-- Alert Messages --}}
                @include('common.alert')

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header"
                                style="display: flex;
                            justify-content: space-between;">
                                <h5 class="card-title mb-0">User List</h5>
                                <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">
                                    <i data-feather="plus"></i> Add New
                                </a>
                            </div>
                            <div class="card-body">
                                <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Mobile</th>
                                            <th scope="col">Role</th>
                                            <th scope="col">Report To</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; ?>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>{{ $i + $users->perPage() * ($users->currentPage() - 1) }}</td>
                                                <td>{{ $user->full_name }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>{{ $user->mobile_number }}</td>
                                                <td>{{ $user->name }}</td>
                                                <td>{{ $user->strReportTo ?? ' - ' }}</td>
                                                <td>
                                                    @if ($user->status == 0)
                                                        <span class="badge badge-gradient-danger">Inactive</span>
                                                        {{-- <span class="badge badge-danger">Inactive</span> --}}
                                                    @elseif ($user->status == 1)
                                                        <span class="badge badge-gradient-primary">Active</span>
                                                        {{-- <span class="badge badge-success">Active</span> --}}
                                                    @endif
                                                </td>
                                                <td style="display: flex">
                                                    @if ($user->status == 0)
                                                        <a href="{{ route('users.status', ['user_id' => $user->id, 'status' => 1]) }}"
                                                            title="InActive" class="mx-1">
                                                            <i class="fa fa-lock" aria-hidden="true"></i>
                                                        </a>
                                                    @elseif ($user->status == 1)
                                                        <a href="{{ route('users.status', ['user_id' => $user->id, 'status' => 0]) }}"
                                                            title="Active" class="mx-1">
                                                            <i class="fa fa-unlock" aria-hidden="true"></i>
                                                        </a>
                                                    @endif
                                                    <a class="mx-1" title="Edit"
                                                        href="{{ route('users.edit', $user->id) }}">
                                                        <i class="far fa-edit"></i>

                                                    </a>
                                                    <a class="mx-1" href="#" data-bs-toggle="modal" title="Delete"
                                                        data-bs-target="#deleteRecordModal"
                                                        onclick="deleteData(<?= $user->id ?>);">
                                                        <i class="fa fa-trash" aria-hidden="true"></i>
                                                    </a>
                                                    <a class="mx-1" href="#" data-bs-toggle="modal"
                                                        title="Change Password" data-bs-target="#changepassword"
                                                        onclick="editpassword(<?= $user->id ?>);">
                                                        <i class="fa fa-key" aria-hidden="true"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php $i++; ?>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="d-flex justify-content-center mt-3">
                                    {{ $users->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changepassword" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light p-3">
                    <h5 class="modal-title" id="exampleModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="close-modal"></button>
                </div>
                <form method="post" action="{{ route('users.passwordupdate') }}" autocomplete="off">
                    @csrf
                    @method('post')

                    <input type="hidden" name="id" id="GetId" value="">

                    <div class="modal-body">
                        <div class="mb-3" id="modal-id" style="display: none;">
                            <label for="id-field" class="form-label">ID</label>
                            <input type="text" id="id-field" class="form-control" placeholder="ID" readonly />
                        </div>

                        <div class="mb-3">
                            <span style="color:red;">*</span>New Password
                            <input type="password" name="newpassword" id="newpassword" class="form-control"
                                placeholder="Enter New Password" required />
                            <div class="invalid-feedback">Please enter a customer name.</div>
                        </div>

                        <div class="mb-3">
                            <span style="color:red;">*</span>Confirm Password
                            <input type="password" name="confirmpassword" id="confirmpassword" class="form-control"
                                placeholder="Enter Confirm Password" required />
                            <div class="invalid-feedback">Please enter an email.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="hstack gap-2 justify-content-end">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success" id="add-btn">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        id="btn-close"></button>
                </div>
                <div class="modal-body">
                    <div class="mt-2 text-center">
                        <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop"
                            colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px"></lord-icon>
                        <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                            <h4>Are you Sure ?</h4>
                            <p class="text-muted mx-4 mb-0">Are you Sure You want to Remove this Record
                                ?</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                        <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                        <a class="btn btn-danger" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('user-delete-form').submit();">
                            Yes,
                            Delete It!
                        </a>
                        <form id="user-delete-form" method="POST"
                            action="{{ route('users.destroy', $user->id ?? '') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="id" id="deleteid" value="">

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end modal -->

@endsection

@section('scripts')
    <script>
        function editpassword(id) {
            $("#GetId").val(id);
        }

        function deleteData(id) {
            $("#deleteid").val(id);
        }
    </script>
@endsection
