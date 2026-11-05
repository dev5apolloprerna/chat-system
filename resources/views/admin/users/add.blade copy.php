@extends('layouts.app')

@section('title', 'Add Users')

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
                                                    <input type="text" class="form-control" id="placeholderInput"
                                                        placeholder="Enter User Name" name="username" maxlength="100"
                                                        value="{{ old('username') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>First Name
                                                    <input type="text" class="form-control" maxlength="100"
                                                        id="placeholderInput" placeholder="Enter First Name"
                                                        name="first_name" value="{{ old('first_name') }}" required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Last Name
                                                    <input type="text" class="form-control" maxlength="100"
                                                        name="last_name" value="{{ old('last_name') }}"
                                                        id="placeholderInput" placeholder="Enter Last Name" required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Email
                                                    <input type="email" class="form-control" id="placeholderInput"
                                                        placeholder="Enter Email" name="email" maxlength="100"
                                                        value="{{ old('email') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Address 1
                                                    <input type="text" class="form-control" id="placeholderInput"
                                                        placeholder="Enter Address 1" name="address1" maxlength="500"
                                                        value="{{ old('address1') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Address 2
                                                    <input type="text" class="form-control" maxlength="500"
                                                        id="placeholderInput" placeholder="Enter Address 2" name="address2"
                                                        value="{{ old('address2') }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Landmark
                                                    <input type="text" class="form-control" maxlength="500"
                                                        id="placeholderInput" placeholder="Enter Landmark" name="landmark"
                                                        value="{{ old('landmark') }}">
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>City
                                                    <input type="text" class="form-control" id="placeholderInput"
                                                        placeholder="Enter City" name="city" maxlength="100"
                                                        value="{{ old('city') }}" required>
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
                                                    <input type="text" class="form-control" id="placeholderInput"
                                                        placeholder="Enter Pincode" name="pincode"
                                                        value="{{ old('pincode') }}" required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Password
                                                    <input type="password" class="form-control" name="password"
                                                        value="{{ old('password') }}" id="placeholderInput"
                                                        placeholder="Enter Password" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Phone
                                                    <input type="text" class="form-control"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');"
                                                        name="mobile_number" value="{{ old('mobile_number') }}"
                                                        maxlength="10" id="mobilevalidation" placeholder="Enter Phone"
                                                        required>
                                                </div>
                                            </div>
                                            <!--end col-->
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Secondary
                                                    Phone
                                                    <input type="text" class="form-control"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');"
                                                        id="secondmobilevalidation" placeholder="Enter Secondary Phone"
                                                        name="secondarymobile" value="{{ old('secondarymobile') }}">
                                                </div>
                                            </div>
                                            <!--end col-->


                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>User Type
                                                    <select class="form-control" name="role_id" required>
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
                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;"></span>Is Insurance
                                                    Agent
                                                    <div>
                                                        <input type="checkbox" name="isInsurance" id="isInsurance"
                                                            value="1" />
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
    <script>
        $(document).ready(function() {

            $("#mobilevalidation").on("blur", function() {
                var mobNum = $(this).val();
                var filter = /^\d*(?:\.\d{1,2})?$/;

                if (filter.test(mobNum)) {
                    if (mobNum.length == 10) {

                    } else {
                        alert('Please Enter 10  digit mobile number');
                        $("#mobilevalidation").val('');
                        return false;
                    }
                } else {
                    alert('Not a valid number');
                    return false;
                }

            });

        });
    </script>
    <script>
        $(document).ready(function() {

            $("#secondmobilevalidation").on("blur", function() {
                var mobNum = $(this).val();
                var filter = /^\d*(?:\.\d{1,2})?$/;

                if (filter.test(mobNum)) {
                    if (mobNum.length == 10) {

                    } else {
                        alert('Please Enter 10  digit mobile number');
                        $("#secondmobilevalidation").val('');
                        return false;
                    }
                } else {
                    alert('Not a valid number');
                    return false;
                }

            });

        });
    </script>

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
        $(document).ready(function() {
            $("#regForm").validate({
                rules: {
                    firstName: {
                        required: true,
                        maxlength: 20,
                    },
                    lastName: {
                        required: true,
                        maxlength: 20,
                    },
                    email: {
                        required: true,
                        email: true,
                        maxlength: 50
                    },
                    phone: {
                        required: true,
                        minlength: 10,
                        maxlength: 10,
                        number: true
                    },
                    password: {
                        required: true,
                        minlength: 5
                    },
                    confirmPassword: {
                        required: true,
                        equalTo: "#password"
                    },
                    gender: {
                        required: true,
                    },
                    dateOfBirth: {
                        required: true,
                        date: true
                    },
                    address: {
                        required: true,
                        maxlength: 50
                    },
                    city: {
                        required: true,
                        maxlength: 40
                    },
                    state: {
                        required: true,
                        maxlength: 40
                    },
                    zipcode: {
                        required: true,
                        minlength: 6,
                        maxlength: 6
                    }
                },
                messages: {
                    firstName: {
                        required: "First name is required",
                        maxlength: "First name cannot be more than 20 characters"
                    },
                    lastName: {
                        required: "Last name is required",
                        maxlength: "Last name cannot be more than 20 characters"
                    },
                    email: {
                        required: "Email is required",
                        email: "Email must be a valid email address",
                        maxlength: "Email cannot be more than 50 characters",
                    },
                    phone: {
                        required: "Phone number is required",
                        minlength: "Phone number must be of 10 digits"
                    },
                    password: {
                        required: "Password is required",
                        minlength: "Password must be at least 5 characters"
                    },
                    confirmPassword: {
                        required: "Confirm password is required",
                        equalTo: "Password and confirm password should same"
                    },
                    gender: {
                        required: "Please select the gender",
                    },
                    dateOfBirth: {
                        required: "Date of birth is required",
                        date: "Date of birth should be a valid date"
                    },
                    address: {
                        required: "Address is required",
                        maxlength: "Address cannot not be more than 50 characters"
                    },
                    city: {
                        required: "City is required",
                        maxlength: "City cannot be more than 40 characters"
                    },
                    state: {
                        required: "State is required",
                        maxlength: "City cannot be more than 40 characters"
                    },
                    zipcode: {
                        required: "Zipcode is required",
                        minlength: "Zipcode must be of 6 digits",
                        maxlength: "Zipcode cannot be more than 6 digits"
                    }
                }
            });
        });
    </script>
@endsection
