@php
    /*
     * The signed prospectus as a PDF (BRS v3.0 PRO-R04): the same parts, in the same order, as the
     * investor reader, laid out from the InvestorProspectusResource payload. Every value is escaped;
     * remote assets and PHP are off in dompdf, so business text can only ever print as text.
     */
    $rwf = fn (string $amount): string => 'RWF '.number_format((float) $amount, 0, '.', ',');
    $day = fn (string $instant): string => \Carbon\CarbonImmutable::parse($instant)->setTimezone('Africa/Kigali')->format('j M Y');
    $roles = ['representative' => 'Official representative', 'director' => 'Director', 'owner' => 'Owner'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $deal['name'] }} prospectus</title>
    <style>
        @page { margin: 48px 52px 60px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1a1f2b; line-height: 1.5; }
        .brand { font-size: 11px; font-weight: bold; color: #1d9e75; letter-spacing: .04em; }
        h1 { font-size: 20px; margin: 4px 0 2px; }
        .muted { color: #5b6475; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.5px; word-wrap: break-word; }
        h2 { font-size: 13px; margin: 20px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #d9dde5; }
        h3 { font-size: 11px; margin: 10px 0 4px; }
        p { margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 5px 0; border-bottom: 1px solid #eceef3; vertical-align: top; text-align: left; }
        th { color: #5b6475; font-weight: bold; }
        .num { text-align: right; white-space: nowrap; }
        .tag { font-size: 8.5px; font-weight: bold; color: #1d9e75; }
        .warn { font-size: 8.5px; font-weight: bold; color: #a15c00; }
        .pre { white-space: pre-line; }
        footer { position: fixed; bottom: -40px; left: 0; right: 0; font-size: 8px; color: #5b6475; }
    </style>
</head>
<body>
    <footer>Rozine · {{ $deal['name'] }} prospectus, version {{ $prospectus['version'] }} · Fingerprint {{ $prospectus['fingerprint'] }}</footer>

    <div class="brand">ROZINE</div>
    <h1>{{ $deal['name'] }} prospectus</h1>
    <p class="muted">Version {{ $prospectus['version'] }}, signed by {{ $prospectus['signed_by'] }} on {{ $day($prospectus['signed_at']) }}</p>
    <p class="mono muted">Fingerprint {{ $prospectus['fingerprint'] }}</p>

    <h2>Purpose</h2>
    <p class="pre">{{ $inputs['purpose'] }}</p>

    <h2>Terms and fees</h2>
    <table>
        <tr><td>Amount raised</td><td class="num">{{ $rwf($facts['principal']) }}</td></tr>
        <tr><td>Rozine platform fee (3%)</td><td class="num">{{ $rwf($facts['platform_fee']) }}</td></tr>
        <tr><td>Left for the business to spend</td><td class="num">{{ $rwf($facts['net_of_fee']) }}</td></tr>
        <tr><td>Term</td><td class="num">{{ $facts['term_months'] }} {{ $facts['term_months'] === 1 ? 'month' : 'months' }}</td></tr>
        <tr><td>Monthly instalment</td><td class="num">{{ $rwf($facts['instalment']) }}</td></tr>
    </table>

    <h2>Use of funds</h2>
    <table>
        @foreach ($inputs['use_of_funds'] as $index => $line)
            <tr>
                <td>{{ $line['label'] }}@if (in_array($index, $quoted_lines, true)) <span class="tag">Quote on file</span>@endif</td>
                <td class="num">{{ $rwf($line['amount']) }}</td>
            </tr>
        @endforeach
    </table>

    <h2>CPA-verified financials and forecast</h2>
    <table>
        <tr><td>Verified average monthly revenue</td><td class="num">{{ $rwf($facts['verified_monthly_revenue']) }}</td></tr>
        <tr><td>Latest sealed CPA report</td><td class="num">{{ $facts['latest_sealed_report_at'] === null ? '—' : $day($facts['latest_sealed_report_at']) }}</td></tr>
    </table>
    <h3>The business’s forecast</h3>
    <table>
        <tr><th>Month</th><th class="num">Revenue</th><th class="num">Costs</th></tr>
        @foreach ($inputs['forecast'] as $month)
            <tr>
                <td>{{ $month['month'] }}@if (in_array($month['month'], $contracted_months, true)) <span class="tag">Signed contract on file</span>@endif</td>
                <td class="num">{{ $rwf($month['revenue']) }}</td>
                <td class="num">{{ $rwf($month['costs']) }}</td>
            </tr>
        @endforeach
    </table>
    <h3>Assumptions</h3>
    <p class="pre">{{ $inputs['assumptions'] }}</p>
    <h3>If sales fall short</h3>
    <p class="pre">{{ $inputs['downside'] }}</p>

    <h2>Management</h2>
    @foreach ($inputs['profiles'] as $profile)
        <p><strong>{{ $profile['name'] }}</strong> <span class="muted">· {{ $roles[$profile['role']] ?? '' }}</span></p>
        <p class="pre muted">{{ $profile['text'] }}</p>
    @endforeach

    <h2>Risks</h2>
    @if ($inputs['largest_customer_share_percent'] !== null)
        <p class="muted">The largest customer brings {{ $inputs['largest_customer_share_percent'] }}% of revenue.</p>
    @endif
    <ul>
        @foreach ($inputs['risks'] as $risk)
            <li>{{ $risk['text'] }}@if ($risk['concentration']) <span class="warn">Customer concentration</span>@endif</li>
        @endforeach
    </ul>
</body>
</html>
