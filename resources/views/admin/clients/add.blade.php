@extends('layouts.app')

@section('title', 'Add Client')

@section('content')

    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                {{-- Alert Messages --}}
                @include('common.alert')

                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Add Client</h4>
                            <div class="page-title-right">
                                <a href="{{ route('admin.clients.index') }}"
                                    class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                                    Back
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
                                    <form id="regForm" method="POST" action="{{ route('admin.clients.store') }}">
                                        @csrf
                                        <div class="row gy-4">

                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Name
                                                    <input type="text"
                                                        class="form-control @error('name') is-invalid @enderror"
                                                        placeholder="Enter Name" name="name" id="text"
                                                        autocomplete="off" value="{{ old('name') }}" required autofocus>
                                                    @error('name')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Email
                                                    <input type="text"
                                                        class="form-control @error('email') is-invalid @enderror"
                                                        placeholder="Enter Email" name="email" id="email"
                                                        autocomplete="off" value="{{ old('email') }}" required>
                                                    @error('email')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Password
                                                    <input type="password"
                                                        class="form-control @error('password') is-invalid @enderror"
                                                        placeholder="Enter Password" name="password" id="password"
                                                        autocomplete="off" value="{{ old('password') }}" required>
                                                    @error('password')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Mobile
                                                    <input type="text"
                                                        class="form-control @error('mobile') is-invalid @enderror"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');"
                                                        maxlength="10" minlength="10" placeholder="Enter Mobile"
                                                        name="mobile" id="mobile" autocomplete="off"
                                                        value="{{ old('mobile') }}" required>
                                                    @error('mobile')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Whatsapp Meta No
                                                    <input type="text"
                                                        class="form-control @error('whatsapp_meta_no') is-invalid @enderror"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');"
                                                        maxlength="10" minlength="10" placeholder="Enter Whatsapp Meta No"
                                                        name="whatsapp_meta_no" id="whatsapp_meta_no" autocomplete="off"
                                                        value="{{ old('whatsapp_meta_no') }}" required>
                                                    @error('whatsapp_meta_no')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>




                                            <div class="col-lg-4 col-md-6">
                                                <div>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Address
                                                    <textarea class="form-control @error('address') is-invalid @enderror" name="address" id="address" cols="5"
                                                        rows="5" required>{{ old('address') }}</textarea>
                                                    @error('address')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>State
                                                    <input type="text"
                                                        class="form-control @error('state') is-invalid @enderror"
                                                        placeholder="Enter State" name="state" id="state"
                                                        autocomplete="off" value="{{ old('state') }}" required>
                                                    @error('state')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>City
                                                    <input type="text"
                                                        class="form-control @error('city') is-invalid @enderror"
                                                        placeholder="Enter City" name="city" id="city"
                                                        autocomplete="off" value="{{ old('city') }}" required>
                                                    @error('city')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Pincode
                                                    <input type="text"
                                                        class="form-control @error('pincode') is-invalid @enderror"
                                                        placeholder="Enter Pincode" name="pincode" id="pincode"
                                                        autocomplete="off" value="{{ old('pincode') }}" required>
                                                    @error('pincode')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Phone Number Id
                                                    <input type="text"
                                                        class="form-control @error('phone_number_id') is-invalid @enderror"
                                                        maxlength="25"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');"
                                                        placeholder="Enter Phone Number Id" name="phone_number_id"
                                                        id="phone_number_id" autocomplete="off"
                                                        value="{{ old('phone_number_id') }}" required>
                                                    @error('phone_number_id')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-md-6">
                                                <div>
                                                    <span style="color:red;">*</span>Business Account Id
                                                    <input type="text"
                                                        class="form-control @error('business_account_id') is-invalid @enderror"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');"
                                                        maxlength="25" placeholder="Enter Business Account Id"
                                                        name="business_account_id" id="business_account_id"
                                                        autocomplete="off" value="{{ old('business_account_id') }}"
                                                        required>
                                                    @error('business_account_id')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                        </div>

                                        <div class="card-footer mt-5" style="float: right;">
                                            <button type="submit"
                                                class="btn btn-primary btn-user float-right mb-3 mx-2">Save
                                            </button>
                                            <a class="btn btn-primary float-right mr-3 mb-3 mx-2"
                                                href="{{ route('admin.clients.index') }}">Cancel
                                            </a>
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

    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

    <script>
        $(function() {
            // Initialize Start Datepicker
            $("#startdatepicker").datepicker({
                dateFormat: 'dd-mm-yy', // Use dd-mm-yy format (ensure it's consistent with the date format you're using)
                onSelect: function(selectedDate) {
                    // Set the minimum date for End Datepicker (it will be the date selected in Start Datepicker)
                    $("#enddatepicker").datepicker("option", "minDate", selectedDate);
                }
            });

            // Initialize End Datepicker
            $("#enddatepicker").datepicker({
                dateFormat: 'dd-mm-yy', // Use dd-mm-yy format
            });
        });
    </script>

@endsection
