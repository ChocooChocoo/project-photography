@extends('layouts.studio-hr.app')
@section('title', $existingRequest ? 'Edit Procurement Request' : 'Request Procurement')

@section('content')
    @include('procurement.request-form', [
        'portalHomeRoute' => 'studio-hr.dashboard',
        'indexRoute' => 'studio-hr.procurement.index',
        'storeRoute' => 'studio-hr.procurement.store',
        'updateRoute' => 'studio-hr.procurement.update',
    ])
@endsection
