@extends('layouts.app')
@section('page-title', 'Edit '.$member->username.'\'s profile | ')
@section('content')
    @include('partials.navbar')
    @push('scripts')
    <script defer src = {{ asset('js/footer.js') }}></script>

    @endpush
    <script defer src="{{ asset('js/edit_profile.js') }}"></script>
    <section class="container g-0 mx-auto my-4 col-lg-7">
        <section class="profile-widget bg-white rounded mb-3">
            <form method="POST" action="{{ route('edit_profile', ['member' => $member->username]) }}" enctype="multipart/form-data"
                  id="edit_form">
                @csrf
                @method('PATCH')
            
                <div class="mt-2 edit_profile_username">
                    <h2 class="h2 fw-bold text-center " id="username">{{$member->username}}</h2>
                </div>

                <section class="container w-100 mt-2 form-group p-2">
                    <div class="mb-4">
                        <label for="new-post-title" class="form-label">Change username</label>
                        <input type="text" class="form-control" id="new-post-title" name="username"
                               value="{{$member->username}}">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="edit_submit_button">Save changes</button>
                    </div>
                </section>
            </form>
        </section>
    </section>
    @include('partials.footer')
@endsection
