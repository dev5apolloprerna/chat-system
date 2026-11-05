@extends('layouts.app')

@section('title', 'Edit User')

@section('content')

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                {{-- Alert Messages --}}
                @include('common.alert')

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Edit User</h4>
                            <div class="page-title-right">
                                <a href="{{ route('users.index') }}"
                                    class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end page title -->

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="live-preview">
                                    <form method="POST" action="{{ route('users.update', ['user' => $user->id]) }}">
                                        @csrf
                                        @method('post')
                                        <div class="row gy-4">
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>User Name
                                                    <input type="text" class="form-control" name="username"
                                                        value="{{ old('username') ? old('username') : $user->username }}"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>First Name
                                                    <input type="text" class="form-control" name="first_name"
                                                        value="{{ old('first_name') ? old('first_name') : $user->first_name }}"
                                                        required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Last Name
                                                    <input type="text" class="form-control" name="last_name"
                                                        value="{{ old('last_name') ? old('last_name') : $user->last_name }}"
                                                        required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Email
                                                    <input type="email"
                                                        class="form-control @error('email') is-invalid @enderror"
                                                        name="email"
                                                        value="{{ old('email') ? old('email') : $user->email }}" required>
                                                    @error('email')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Address 1
                                                    <input type="text" class="form-control" name="address1"
                                                        value="{{ old('address1') ? old('address1') : $user->address1 }}"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Address 2
                                                    <input type="text" class="form-control" placeholder="Enter Address 2"
                                                        name="address2"
                                                        value="{{ old('address2') ? old('address2') : $user->address2 }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Landmark
                                                    <input type="text" class="form-control" placeholder="Enter Landmark"
                                                        name="landmark"
                                                        value="{{ old('landmark') ? old('landmark') : $user->landmark }}">
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>City
                                                    <input type="text" class="form-control" name="city"
                                                        value="{{ old('city') ? old('city') : $user->city }}" required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>State
                                                    <select class="form-control" name="state" required>
                                                        <option selected disabled value="">Select state</option>
                                                        @foreach ($states as $state)
                                                            <option value="{{ $state->id }}"
                                                                {{ old('state') ? (old('state') == $state->id ? 'selected' : '') : ($user->state == $state->id ? 'selected' : '') }}>
                                                                {{ $state->statename }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Pincode
                                                    <input type="text" class="form-control" name="pincode"
                                                        onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')"
                                                        minlength="6" maxlength="6"
                                                        value="{{ old('pincode') ? old('pincode') : $user->pincode }}"
                                                        required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Phone
                                                    <input type="text" class="form-control"
                                                        onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')"
                                                        name="mobile_number" minlength="10" maxlength="10"
                                                        value="{{ old('mobile_number') ? old('mobile_number') : $user->mobile_number }}"
                                                        required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Secondary
                                                    Phone
                                                    <input type="text" class="form-control"
                                                        onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')"
                                                        name="secondarymobile" minlength="10" maxlength="10"
                                                        placeholder="Enter Secondary Phone"
                                                        value="{{ old('secondarymobile') ? old('secondarymobile') : $user->secondarymobile }}">
                                                </div>
                                            </div>
                                            <!--end col-->


                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>User Type
                                                    <select class="form-control" name="role_id" onchange="managerbind();"
                                                        id="managersssss" required>
                                                        <option selected disabled value="">Select User Type</option>
                                                        @foreach ($roles as $role)
                                                            <option value="{{ $role->id }}"
                                                                {{ old('role_id') ? (old('role_id') == $role->id ? 'selected' : '') : ($user->role_id == $role->id ? 'selected' : '') }}>
                                                                {{ $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <!--end col-->

                                            <div class="col-lg-4 col-md-6" id="reporttoDiv" style="display: none;">
                                                <div>
                                                    <span style="color:red;">*</span>Report To
                                                    <select class="form-control" name="ireportTo" id="ireportToo">
                                                        <option selected disabled value="">Select Report To</option>
                                                        @foreach ($Manager as $Managers)
                                                            <option value="{{ $Managers->id }}"
                                                                {{ old('ireportTo') ? (old('ireportTo') == $Managers->id ? 'selected' : '') : ($user->ireportTo == $Managers->id ? 'selected' : '') }}>
                                                                {{ $Managers->first_name . ' ' . $Managers->last_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6" id="TicketTypeDiv" style="display: none;">
                                                <div>
                                                    <span style="color:red;">*</span>Ticket Type
                                                    <select class="form-control" name="itickettype" id="itickettype">
                                                        <option selected disabled value="">Select Ticket Type
                                                        </option>
                                                        @foreach ($TicketType as $tickettype)
                                                            <option value="{{ $tickettype->tickettypeMasterId }}"
                                                                {{ old('itickettype') ? (old('itickettype') == $tickettype->tickettypeMasterId ? 'selected' : '') : ($user->itickettype == $tickettype->tickettypeMasterId ? 'selected' : '') }}>
                                                                {{ $tickettype->tickettypename }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Is Insurance
                                                    Agent
                                                    <div>
                                                        <input type="checkbox" name="isInsurance" id="isInsurance"
                                                            value="1" {{ $user->isInsurance ? 'checked' : '' }} />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Status
                                                    <select class="form-control" name="status" required>
                                                        <option selected disabled value="">Select Status</option>
                                                        <option value="1"
                                                            {{ old('role_id') ? (old('role_id') == 1 ? 'selected' : '') : ($user->status == 1 ? 'selected' : '') }}>
                                                            Active</option>
                                                        <option value="0"
                                                            {{ old('role_id') ? (old('role_id') == 0 ? 'selected' : '') : ($user->status == 0 ? 'selected' : '') }}>
                                                            Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <!--end col-->

                                        </div>
                                        <div class="card-footer mt-5" style="float: right;">
                                            <button type="submit"
                                                class="btn btn-success btn-user float-right mb-3">Update</button>
                                            <a class="btn btn-primary float-right mr-3 mb-3"
                                                href="{{ route('users.index') }}">Cancel</a>
                                        </div>
                                        <!--end row-->
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end col-->
                </div>
                <!--end row-->

            </div>
        </div>
    </div>


@endsection


@section('scripts')

    <script>
        function managerbind() {
            var manager = $('#managersssss').val();
            if (manager == 3) {
                $("#reporttoDiv").show();
                $("#ireportToo").attr("required", true);
                $("#TicketTypeDiv").show();
                $("#itickettype").attr("required", true);
            } else {
                $("#reporttoDiv").hide();
                $("#ireportToo").attr("required", false);
                $("#TicketTypeDiv").hide();
                $("#itickettype").attr("required", false);
            }
        }

        $(document).ready(function() {
            managerbind();
        });
    </script>

@endsection
