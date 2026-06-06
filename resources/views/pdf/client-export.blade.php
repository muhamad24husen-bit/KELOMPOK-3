<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Clients Export</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .text-center { text-align: center; }
        .status-active { color: green; font-weight: bold; }
        .status-expired { color: red; font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Building Directory Export</h2>
        <p>Generated at: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Building Name</th>
                <th>Code / Zone</th>
                <th>Address / Gedung</th>
                <th>Expiry Date</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->code }} {{ $client->kelas ? '('.$client->kelas.')' : '' }}</td>
                    <td>{{ $client->gedung ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($client->expirity)->format('Y-m-d') }}</td>
                    <td class="text-center">
                        @if(\Carbon\Carbon::parse($client->expirity)->isPast())
                            <span class="status-expired">Expired</span>
                        @else
                            <span class="status-active">Active</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
