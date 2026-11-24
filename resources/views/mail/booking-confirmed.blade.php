<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Turno confirmado</title>
</head>
<body style="margin:0;padding:0;background:#f9fafb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td align="center" style="padding:28px 24px 8px;">
                        @if ($location->logoUrl())
                            <img src="{{ $location->logoUrl() }}" alt="{{ $location->name }}" height="64" style="height:64px;max-width:200px;">
                        @else
                            <p style="margin:0;font-size:20px;font-weight:bold;color:#e11d48;">{{ $location->name }}</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px 8px;">
                        <h1 style="margin:0 0 8px;font-size:20px;">¡Hola {{ $booking->client->first_name }}! Tu turno está confirmado ✨</h1>
                        <p style="margin:0;color:#4b5563;font-size:14px;">Recibimos tu seña. Te esperamos:</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fff1f2;border-radius:12px;padding:16px;font-size:14px;">
                            <tr><td style="padding:4px 0;color:#6b7280;">Servicio</td><td align="right" style="padding:4px 0;font-weight:bold;">{{ $booking->service->name }}</td></tr>
                            <tr><td style="padding:4px 0;color:#6b7280;">Día</td><td align="right" style="padding:4px 0;font-weight:bold;">{{ ucfirst($booking->start_at->translatedFormat('l j \d\e F')) }}</td></tr>
                            <tr><td style="padding:4px 0;color:#6b7280;">Hora</td><td align="right" style="padding:4px 0;font-weight:bold;">{{ $booking->start_at->format('H:i') }} hs</td></tr>
                            <tr><td style="padding:4px 0;color:#6b7280;">Con</td><td align="right" style="padding:4px 0;font-weight:bold;">{{ $booking->professional->name }}</td></tr>
                            <tr><td style="padding:4px 0;color:#6b7280;">Precio</td><td align="right" style="padding:4px 0;">@ars($booking->price)</td></tr>
                            <tr><td style="padding:4px 0;color:#6b7280;">Seña abonada</td><td align="right" style="padding:4px 0;">@ars($paid)</td></tr>
                            <tr><td style="padding:4px 0;color:#6b7280;">Resta abonar</td><td align="right" style="padding:4px 0;font-weight:bold;">@ars(max(0, $booking->price - $paid))</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 28px;font-size:13px;color:#4b5563;line-height:1.5;">
                        @if ($location->address)
                            📍 {{ $location->address }}<br>
                        @endif
                        @if ($location->phone)
                            📞 {{ $location->phone }}<br>
                        @endif
                        <br>
                        {{ \App\Support\Settings::get('online.terms') }}
                    </td>
                </tr>
            </table>
            <p style="margin:16px 0 0;font-size:11px;color:#9ca3af;">{{ $location->name }}</p>
        </td>
    </tr>
</table>
</body>
</html>
