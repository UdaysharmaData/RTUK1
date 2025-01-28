<table cellspacing="0" cellpadding="6" border="1" width="100%" style="border-collapse:separate;color:#000000;border:1px solid #007bc3;font-family:Helvetica,Roboto,Arial,sans-serif">
    <thead >
        @if($headers)
            <tr>
                @foreach($headers as $header)
                    <th colspan="1" scope="col" class="{{ $header['className'] ?? 'text__left' }}" style="border:1px solid #e5e5e5;text-align:left;vertical-align:middle;padding:12px;font-size:14px;border-width:1px;border-style:solid;border-color:#007bc3;color:#000000">{{ $header['value'] }}</th>
                @endforeach
            </tr>
        @endif
    </thead>
    <tbody style="border-color:inherit">
        @if($body)
            @foreach($body as $body_items)
                <tr style="border-color:inherit">
                    @foreach($body_items as $body_item)
                        <td {{ $body_item['attr'] ?? '' }} class="{{ $body_item['className'] ?? 'text__left' }}"  colspan="1" scope="row" style="border:1px solid #e5e5e5;font-weight:normal;text-align:left;vertical-align:middle;padding:12px;font-size:14px;border-width:1px;border-style:solid;border-color:#007bc3;color:#000000">{!! $body_item['value'] !!}</td>
                    @endforeach
                </tr>
            @endforeach
        @endif
    </tbody>
</table>
