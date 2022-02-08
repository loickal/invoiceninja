@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.view_invoice'))

@push('head')
    <meta name="show-invoice-terms" content="{{ $settings->show_accept_invoice_terms ? true : false }}">
    <meta name="require-invoice-signature" content="{{ $client->user->account->hasFeature(\App\Models\Account::FEATURE_INVOICE_SETTINGS) && $settings->require_invoice_signature }}">
    @include('portal.ninja2020.components.no-cache')
    
    <script src="{{ asset('vendor/signature_pad@2.3.2/signature_pad.min.js') }}"></script>
@endpush

@section('body')

    @if(!$invoice->isPayable() && $client->getSetting('custom_message_paid_invoice'))
        @component('portal.ninja2020.components.message')
            {{ $client->getSetting('custom_message_paid_invoice') }}
        @endcomponent
    @endif

    @if($invoice->isPayable())
        <form action="{{ ($settings->client_portal_allow_under_payment || $settings->client_portal_allow_over_payment) ? route('client.invoices.bulk') : route('client.payments.process') }}" method="post" id="payment-form">
            @csrf
            <input type="hidden" name="invoices[]" value="{{ $invoice->hashed_id }}">
            <input type="hidden" name="action" value="payment">

            <input type="hidden" name="company_gateway_id" id="company_gateway_id">
            <input type="hidden" name="payment_method_id" id="payment_method_id">
            <input type="hidden" name="signature">

            <input type="hidden" name="payable_invoices[0][amount]" value="{{ $invoice->partial > 0 ?  \App\Utils\Number::formatValue($invoice->partial, $invoice->client->currency()) : \App\Utils\Number::formatValue($invoice->balance, $invoice->client->currency()) }}">
            <input type="hidden" name="payable_invoices[0][invoice_id]" value="{{ $invoice->hashed_id }}">

            <div class="bg-white shadow sm:rounded-lg mb-4" translate>
                <div class="px-4 py-5 sm:p-6">
                    <div class="sm:flex sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                {{ ctrans('texts.invoice_number_placeholder', ['invoice' => $invoice->number])}}
                                - {{ ctrans('texts.unpaid') }}
                            </h3>
                        </div>
                        <div class="mt-5 sm:mt-0 sm:ml-6 flex justify-end">
                            <div class="inline-flex rounded-md shadow-sm">
                                <input type="hidden" name="invoices[]" value="{{ $invoice->hashed_id }}">
                                <input type="hidden" name="action" value="payment">

                                @if($settings->client_portal_allow_under_payment || $settings->client_portal_allow_over_payment)
                                    <button class="button button-primary bg-primary">{{ ctrans('texts.pay_now') }}</button>
                                @else
                                    @livewire('pay-now-dropdown', ['total' => $invoice->partial > 0 ? $invoice->partial : $invoice->balance, 'company' => $company])
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="bg-white shadow sm:rounded-lg mb-4">
            <div class="px-4 py-5 sm:p-6">
                <div class="sm:flex sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            {{ ctrans('texts.invoice_number_placeholder', ['invoice' => $invoice->number])}}
                            - {{ \App\Models\Invoice::stringStatus($invoice->status_id) }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('portal.ninja2020.components.entity-documents', ['entity' => $invoice])
    @include('portal.ninja2020.components.pdf-viewer', ['entity' => $invoice])
    @include('portal.ninja2020.invoices.includes.terms', ['entities' => [$invoice], 'entity_type' => ctrans('texts.invoice')])
    @include('portal.ninja2020.invoices.includes.signature')
@endsection

@section('footer')
    <script src="{{ asset('js/clients/invoices/payment.js') }}"></script>
@endsection