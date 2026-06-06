<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rooms Export</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .text-center { text-align: center; }
        .header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rooms Inventory Export</h2>
        <p>Generated at: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Room Name</th>
                <th>Building</th>
                <th>Floor</th>
                <th class="text-center">Total Nodes</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rooms as $room)
                <tr>
                    <td>{{ $room->name }}</td>
                    <td>{{ $room->client->name ?? 'N/A' }}</td>
                    <td>{{ $room->floor }}</td>
                    <td class="text-center">{{ $room->total_nodes }}</td>
                    <td>{{ $room->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
