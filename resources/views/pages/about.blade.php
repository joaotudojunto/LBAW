@extends('layouts.app')
@section('page-title', 'About us | ')
@section('content')
    @include('partials.navbar')
    @push('scripts')
    <script defer src = {{ asset('js/footer.js') }}></script>
    @endpush
    <section class="p-3 p-lg-5 my-4 col-lg-7 bg-white container rounded">
        <h2 class="h2 fw-bold ">About Us</h2>
        <hr class="rounded">

        <section>
            <h3 class="mb-3 mt-4">About ActiveNews</h3>
            <p>When 4 young guys met for the first time, they knew they wanted to change the world by creating the largest collaborative news website about technology!</p>
        </section>

        <hr class="admin_hr mt-4">

        <section>
            <h3 class="mb-3">Our team</h3>
            <section class="team-cards">
                <div class="row g-0 justify-content-around">
                    <div class="card col-12 col-md-5 p-2 about-card">
                        <img src="{{ asset('images/stark.jpg') }}" class="card-img-top rounded" alt="João Duarte">
                        <div class="card-body">
                            <h4 class="card-title">João Duarte</h4>
                            <p class="card-text">Student at LEIC</p>
                        </div>
                    </div>
                    <div class="card col-12 col-md-5 mt-3 mt-md-0 p-2 about-card">
                        <img src="{{asset('images/vision.jpg')}}" class="card-img-top rounded" alt="Henrique Vicente">
                        <div class="card-body">
                            <h4 class="card-title">Henrique Vicente</h4>
                            <p class="card-text">Student at LEIC</p>
                        </div>
                    </div>
                </div>
                <div class="row g-0 mt-3 justify-content-around">
                    <div class="card col-12 col-md-5 p-2 about-card">
                        <img src="{{asset('images/odinson.jpg')}}" class="card-img-top rounded" alt="Pedro Oliveira">
                        <div class="card-body">
                            <h4 class="card-title">Pedro Oliveira</h4>
                            <p class="card-text">Student at LEIC</p>
                        </div>
                    </div>

                    <div class="card col-12 col-md-5 mt-3 mt-md-0 p-2 about-card">
                        <img src="{{asset('images/strange.jpg')}}" class="card-img-top rounded" alt="João Ribeiro">
                        <div class="card-body">
                            <h4 class="card-title">João Ribeiro</h4>
                            <p class="card-text">Student at LEIC</p>
                        </div>
                    </div>
                </div>
            </section>
        </section>
    </section>
    @include('partials.footer')
@endsection
