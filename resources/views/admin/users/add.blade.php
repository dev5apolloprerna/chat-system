@extends('layouts.app')

@section('title', 'Add User')

@section('content')

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                {{-- Alert Messages --}}
                @include('common.alert')

                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Add User</h4>
                            <div class="page-title-right">
                                <a href="{{ route('users.index') }}"
                                    class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="live-preview">
                                    <form method="POST" action="{{ route('users.store') }}" id="regForm">
                                        @csrf
                                        <div class="row gy-4">
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>User Name
                                                    <input type="text" class="form-control" id=""
                                                        placeholder="Enter User Name" name="username" maxlength="100"
                                                        autocomplete="off" value="{{ old('username') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>First Name
                                                    <input type="text" class="form-control" maxlength="100"
                                                        id="" placeholder="Enter First Name" name="first_name"
                                                        autocomplete="off" value="{{ old('first_name') }}" required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Last Name
                                                    <input type="text" class="form-control" maxlength="100"
                                                        name="last_name" value="{{ old('last_name') }}" id=""
                                                        autocomplete="off" placeholder="Enter Last Name" required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Email
                                                    <input type="email"
                                                        class="form-control @error('email') is-invalid @enderror"
                                                        id="" placeholder="Enter Email" name="email"
                                                        maxlength="100" autocomplete="off" value="{{ old('email') }}"
                                                        required>
                                                    @error('email')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Address 1
                                                    <input type="text" class="form-control" id=""
                                                        placeholder="Enter Address 1" name="address1" maxlength="500"
                                                        autocomplete="off" value="{{ old('address1') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Address 2
                                                    <input type="text" class="form-control" maxlength="500"
                                                        id="" placeholder="Enter Address 2" name="address2"
                                                        autocomplete="off" value="{{ old('address2') }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Landmark
                                                    <input type="text" class="form-control" maxlength="500"
                                                        id="" placeholder="Enter Landmark" name="landmark"
                                                        autocomplete="off" value="{{ old('landmark') }}">
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>City
                                                    <input type="text" class="form-control" id=""
                                                        placeholder="Enter City" name="city" maxlength="100"
                                                        autocomplete="off" value="{{ old('city') }}" required>
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
                                                                {{ old('state') == $state->id ? 'selected' : '' }}>
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
                                                    <input type="text" class="form-control" id="pincode"
                                                        onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')"
                                                        autocomplete="off" placeholder="Enter Pincode" name="pincode"
                                                        minlength="6" maxlength="6" value="{{ old('pincode') }}"
                                                        required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Password
                                                    <input type="password" class="form-control" name="password"
                                                        autocomplete="off" value="{{ old('password') }}" id=""
                                                        placeholder="Enter Password" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Phone
                                                    <input type="text" class="form-control"
                                                        onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')"
                                                        autocomplete="off" name="mobile_number"
                                                        value="{{ old('mobile_number') }}" minlength="10" maxlength="10"
                                                        placeholder="Enter Phone" required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Secondary
                                                    Phone
                                                    <input type="text" class="form-control"
                                                        onkeyup="if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,'')"
                                                        autocomplete="off" id="secondmobilevalidation"
                                                        placeholder="Enter Secondary Phone" minlength="10" maxlength="10"
                                                        name="secondarymobile" value="{{ old('secondarymobile') }}">
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
                                                                {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                                                                {{ old('ireportTo') == $Managers->id ? 'selected' : '' }}>
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
                                                                <?= isset($Tickettype) && $tickettype->tickettypeMasterId == $Tickettype ? 'Selected' : '' ?>>
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
                                                            autocomplete="off" value="1" />
                                                    </div>
                                                </div>
                                            </div>
                                            <!--end col-->

                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Status
                                                    <select class="form-control" name="status" required>
                                                        <option selected disabled value="">Select Status</option>
                                                        <option value="1" selected>Active</option>
                                                        <option value="0">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <!--end col-->

                                        </div>
                                        <div class="card-footer mt-5" style="float: right;">
                                            <button type="submit"
                                                class="btn btn-success btn-user float-right mb-3">Save</button>
                                            <a class="btn btn-primary float-right mr-3 mb-3"
                                                href="{{ route('users.index') }}">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

@section('scripts')


    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
        integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous">
    </script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/jquery.validate.min.js"></script>

    <script>
        function managerbind(id) {
            var manager = $('#managersssss').val();
            {{--  alert(manager);  --}}

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
    </script>

@endsection
