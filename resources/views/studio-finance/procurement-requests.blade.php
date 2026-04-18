@extends('layouts.studio-finance.app')
@section('title', 'Procurement Queue')

@section('content')
    @include('procurement.finance-index', [
        'showRouteBase' => url('/studio-finance/procurement'),
        'reviewRouteBase' => url('/studio-finance/procurement'),
        'purchaseOrderRouteBase' => url('/studio-finance/procurement'),
        'deliveryRouteBase' => url('/studio-finance/procurement'),
        'processReturnRouteBase' => url('/studio-finance/procurement'),
        'replacementDeliveryRouteBase' => url('/studio-finance/procurement'),
        'paymentRouteBase' => url('/studio-finance/procurement'),
        'completeRouteBase' => url('/studio-finance/procurement'),
    ])
@endsection
