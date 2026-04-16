@extends('layouts.studio-photographer.app')
@section('title', $existingRequest ? 'Edit Procurement Request' : 'Request Procurement')

@section('content')
    @include('procurement.request-form', [
        'portalHomeRoute' => 'studio-photographer.dashboard',
        'indexRoute' => 'studio-photographer.procurement.index',
        'storeRoute' => 'studio-photographer.procurement.store',
        'updateRoute' => 'studio-photographer.procurement.update',
    ])
@endsection
