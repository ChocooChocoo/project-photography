@extends('layouts.studio-photographer.app')
@section('title', 'Procurement Requests')

@section('content')
    @include('procurement.request-list', [
        'createRoute' => 'studio-photographer.procurement.create',
        'indexRoute' => 'studio-photographer.procurement.index',
        'showRouteBase' => url('/studio-photographer/procurement'),
        'cancelRouteBase' => url('/studio-photographer/procurement'),
        'confirmReceiptRouteBase' => url('/studio-photographer/procurement'),
        'editRouteName' => 'studio-photographer.procurement.edit',
    ])
@endsection
