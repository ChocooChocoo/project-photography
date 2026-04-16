@extends('layouts.owner.app')
@section('title', 'Procurement Oversight')

@section('content')
    @include('procurement.owner-index', [
        'showRouteBase' => url('/owner/procurement'),
        'processRouteBase' => url('/owner/procurement'),
    ])
@endsection
