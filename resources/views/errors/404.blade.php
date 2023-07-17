@extends('layouts.app')
@section('page-title', '404 | ')
@section('content')
<div class="col d-flex align-items-center">
    <section class="p-3 p-lg-5 my-4 col-lg-7 container bg-white rounded">
        <h1 class="text-center h1 fw-bold color-red">Page not found</h1>
        <h6 class="text-center text-muted">Our monkey based team are working on this issue!</h6>
        
        <div class="row g-0 text-center">
            <img class="error-picture" src="{{asset('/images/error.png')}}" alt="error" >
        </div>
        <h5 class="text-center"><a href="{{ route('home') }}" class="color-red">Main Page</a></h5>
    </section>
</div>
@endsection
