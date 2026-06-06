<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Export</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .text-center { text-align: center; }
        .header { text-align: center; margin-bottom: 30px; }
        .status-online { color: #2D60FF; }
        .status-offline { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Diagnostic Device List Export</h2>
        <p>Generated at: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Device ID / MAC</th>
                <th>Location (Building > Room)</th>
                <th>Status</th>
                <th>Last Seen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sensors as $sensor)
                <tr>
                    <td>
                        {{ $sensor->measurement_type }}<br>
                        <small>{{ $sensor->mac_address }}</small>
                    </td>
                    <td>{{ $sensor->room->client->name ?? 'N/A' }} > {{ $sensor->room->name ?? 'N/A' }}</td>
                    <td class="{{ $sensor->is_enabled ? 'status-online' : 'status-offline' }}">
                        {{ $sensor->is_enabled ? 'Online' : 'Offline' }}
                    </td>
                    <td>{{ $sensor->is_enabled ? 'Just now' : $sensor->updated_at->diffForHumans() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
