@extends($layout)
@section('title', 'Booking Terms and Conditions')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="mt-3" style="max-width: 820px; margin: 0 auto;">
                <h4 class="mb-3">Booking Terms and Conditions</h4>
                <div class="card">
                    <div class="card-body" style="line-height: 1.7;">
                        {!! $html !!}
                        <hr>
                        <small class="text-muted">
                            Bookings of two or more days require a contract that you and the provider both sign before the booking is confirmed.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
