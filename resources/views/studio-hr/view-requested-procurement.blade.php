@extends('layouts.studio-hr.app')
@section('title', 'Procurement Requests')

@section('content')
    @include('procurement.request-list', [
        'createRoute' => 'studio-hr.procurement.create',
        'indexRoute' => 'studio-hr.procurement.index',
        'showRouteBase' => url('/studio-hr/procurement'),
        'cancelRouteBase' => url('/studio-hr/procurement'),
        'confirmReceiptRouteBase' => url('/studio-hr/procurement'),
        'editRouteName' => 'studio-hr.procurement.edit',
    ])
@endsection
