@extends('public.v2.public_master')

@section('title', 'Home')
@section('content')
<!-- ======= Hero Section ======= -->
@include('public.v2.body.hero')
<!-- End Hero -->

<!-- ======= About Us Section ======= -->
@include('public.v2.body.about_us')
<!-- End About Us Section -->

<!-- ======= Services Section ======= -->
@include('public.v2.body.services')
<!-- End Services Section -->


<!-- ======= Our Clients Section ======= -->
{{-- <section id="clients" class="clients">
    <div class="container" data-aos="fade-up">

    <div class="section-title">
        <h2>Clients</h2>
    </div>

    <div class="row no-gutters clients-wrap clearfix" data-aos="fade-up">

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-1.png') }}" class="img-fluid" alt="">
        </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-2.png') }}" class="img-fluid" alt="">
        </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-3.png') }}" class="img-fluid" alt="">
        </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-4.png') }}" class="img-fluid" alt="">
        </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-5.png') }}" class="img-fluid" alt="">
        </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-6.png') }}" class="img-fluid" alt="">
        </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-7.png') }}" class="img-fluid" alt="">
        </div>
        </div>

        <div class="col-lg-3 col-md-4 col-6">
        <div class="client-logo">
            <img src="{{asset('frontend/assets/img/clients/client-8.png') }}" class="img-fluid" alt="">
        </div>
        </div>

    </div>

    </div>
</section> --}}
<!-- End Our Clients Section -->

@endsection