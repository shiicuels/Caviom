@extends('charity.charity_master')
@section('title', 'Add Beneficiary')
@section('charity')

<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="p-2">
                    <h1 class="mb-0" style="color: #62896d"><strong>BENEFICIARIES</strong></h1>
                    <ol class="breadcrumb m-0 p-0">
                        <li class="breadcrumb-item">Our Charitable Organization</li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('charity.beneficiaries.all') }}">Beneficiaries</a>
                        </li>
                        <li class="breadcrumb-item active">Add</li>
                    </ol>

                    @include('charity.modals.beneficiaries')
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-md-12">
                <div class="card p-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <h1><strong>Add New Beneficiary</strong></h1>
                            </div>
                            <div class="col-lg-4 mt-4">
                                <a href="{{route('charity.beneficiaries.all')}}" class="text-link float-end">
                                    <i class="ri-arrow-left-line"></i> Go Back
                                </a>
                            </div>
                        </div>

                        <hr class="my-3">

                        <form method="POST" action="{{ route('charity.beneficiaries3.storePart3', $beneficiary->code) }}" enctype="multipart/form-data">
                            @csrf

                            <!-- Background Info -->
                            @include('charity.main.beneficiaries.add_components.bg')

                            <!-- Last Part: Label, Category, Prepared By, Noted By-->
                            @include('charity.main.beneficiaries.add_components.lastpart')


                            <div class="row p-3">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="text-center mb-0 small col-2">Part 3/3</p>
                                        <div class="progress col-2">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="99" aria-valuemin="0" aria-valuemax="99" style="width: 99%"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-dark btn-rounded w-md waves-effect waves-light float-end">
                                            FINISH
                                        </button>
                                        <a class="btn list-inline-item float-end mx-4" href="{{route('charity.beneficiaries.all' )}}">Beneficiary List</a>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div><!-- end cardbody -->
                </div><!-- end card -->
            </div><!-- end col -->
        </div>
        <!-- end row -->
    </div>
</div>
@endsection
