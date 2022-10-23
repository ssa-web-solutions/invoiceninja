@extends('portal.ninja2020.layout.app')
@section('meta_title', ctrans('texts.view_credit'))

@push('head')
    <meta name="pdf-url" content="{{ $credit->pdf_file_path(null, 'url', true) }}">
    @include('portal.ninja2020.components.no-cache')
    
    <script src="{{ asset('js/vendor/pdf.js/pdf.min.js') }}"></script>
@endpush

@section('body')
    <div class="bg-white shadow sm:rounded-lg mb-4" translate>
        <div class="px-4 py-5 sm:p-6">
            <div class="sm:flex sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        {{ ctrans('texts.entity_number_placeholder', ['entity' => ctrans('texts.credit'), 'entity_number' => $credit->number]) }}
                    </h3>

                    @if($key)
                    <div class="btn hidden md:block" data-clipboard-text="{{url("client/credit/{$key}")}}" aria-label="Copied!">
                        <div class="flex text-sm leading-6 font-medium text-gray-500">
                            <p class="mr-2">{{url("client/credit/{$key}")}}</p>
                            <p><img class="h-5 w-5" src="{{ asset('assets/clippy.svg') }}" alt="Copy to clipboard"></p>
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    @include('portal.ninja2020.components.entity-documents', ['entity' => $credit])

    @include('portal.ninja2020.components.pdf-viewer', ['entity' => $credit, 'invitation' => $invitation])

    
@endsection

@section('footer')
    <script src="{{ asset('vendor/clipboard.min.js') }}"></script>

    <script type="text/javascript">

        var clipboard = new ClipboardJS('.btn');

            // clipboard.on('success', function(e) {
            //     console.info('Action:', e.action);
            //     console.info('Text:', e.text);
            //     console.info('Trigger:', e.trigger);

            //     e.clearSelection();
            // });

            // clipboard.on('error', function(e) {
            //     console.error('Action:', e.action);
            //     console.error('Trigger:', e.trigger);
            // });

    </script>
@endsection
