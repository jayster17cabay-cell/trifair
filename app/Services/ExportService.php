<?php

namespace App\Services;

use Illuminate\Http\Response;

class ExportService
{
    /* ────────────────────────── CSV ────────────────────────── */

    public function csv(string $filename, array $headers, array $rows): Response
    {
        $handle = fopen('php://temp', 'r+');
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /* ──────────────── Word (HTML with .doc ext) ─────────────── */

    public function word(string $title, array $headers, array $rows): Response
    {
        $html = $this->buildHtmlTable($title, $headers, $rows);

        return response($html, 200, [
            'Content-Type' => 'application/msword',
            'Content-Disposition' => 'attachment; filename="' . $this->stripExt($title) . '.doc"',
        ]);
    }

    /* ──────────────── PDF (print-ready HTML) ──────────────── */

    public function pdf(string $title, array $headers, array $rows): Response
    {
        $html = $this->buildPrintHtml($title, $headers, $rows);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /* ──────────────── Format dispatch helpers ──────────────── */

    public function operatorsFormat(\Illuminate\Support\Collection $operators, string $format): Response
    {
        $headers = ['ID', 'Name', 'Email', 'TODA', 'License No.', 'Plate No.', 'Body No.', 'Motorcycle Model', 'Contact', 'Address', 'Status', 'Archived', 'Registered'];
        $rows = $operators->map(function ($o) {
            return [
                $o->id,
                $o->user->name ?? '',
                $o->user->email ?? '',
                $o->toda->name ?? 'Unassigned',
                $o->license_number ?? '',
                $o->plate_number ?? '',
                $o->body_number ?? '',
                $o->motorcycle_model ?? '',
                $o->contact_number ?? '',
                $o->address ?? '',
                ucfirst($o->status),
                $o->isArchived() ? 'Yes' : '',
                $o->created_at?->format('Y-m-d H:i') ?? '',
            ];
        })->all();

        return $this->dispatch('operators', $headers, $rows, $format);
    }

    public function ratingsFormat(\Illuminate\Support\Collection $ratings, string $format): Response
    {
        $headers = ['ID', 'Operator', 'Rating', 'Comment', 'Date'];
        $rows = $ratings->all();

        return $this->dispatch('ratings', $headers, $rows, $format);
    }

    public function complaintsFormat(\Illuminate\Support\Collection $complaints, string $format): Response
    {
        $headers = ['ID', 'Operator', 'Rating', 'Complaint', 'Status', 'Date'];
        $rows = $complaints->all();

        return $this->dispatch('complaints', $headers, $rows, $format);
    }

    public function reportsFormat(\Illuminate\Support\Collection $reports, string $format): Response
    {
        $headers = ['Operator', 'TODA', 'Body No.', 'Plate No.', 'Total Trips', 'Average Rating', 'Status'];
        $rows = $reports->all();

        return $this->dispatch('reports', $headers, $rows, $format);
    }

    public function activityLogsFormat(\Illuminate\Support\Collection $logs, string $format): Response
    {
        $headers = ['Date', 'User', 'Action', 'Category', 'Description'];
        $rows = $logs->all();

        return $this->dispatch('activity-logs', $headers, $rows, $format);
    }

    /* ──────────────────── Legacy CSV shorthands ──────────────── */

    public function operatorsCsv(\Illuminate\Support\Collection $operators): Response
    {
        return $this->operatorsFormat($operators, 'csv');
    }

    public function ratingsCsv(\Illuminate\Support\Collection $ratings): Response
    {
        return $this->ratingsFormat($ratings, 'csv');
    }

    public function complaintsCsv(\Illuminate\Support\Collection $complaints): Response
    {
        return $this->complaintsFormat($complaints, 'csv');
    }

    public function reportsCsv(\Illuminate\Support\Collection $reports): Response
    {
        return $this->reportsFormat($reports, 'csv');
    }

    public function activityLogsCsv(\Illuminate\Support\Collection $logs): Response
    {
        return $this->activityLogsFormat($logs, 'csv');
    }

    /* ──────────────────── Internal helpers ──────────────────── */

    private function dispatch(string $name, array $headers, array $rows, string $format): Response
    {
        return match ($format) {
            'word' => $this->word($name, $headers, $rows),
            'pdf'  => $this->pdf($name, $headers, $rows),
            default => $this->csv($name . '.csv', $headers, $rows),
        };
    }

    private function stripExt(string $filename): string
    {
        return preg_replace('/\.[^.]+$/', '', $filename);
    }

    private function buildHtmlTable(string $title, array $headers, array $rows): string
    {
        $title = htmlspecialchars(ucfirst($title));
        $date  = now()->format('F j, Y');
        $site  = config('app.name', 'TriFair');

        $hCells = '';
        foreach ($headers as $h) {
            $hCells .= '<th style="background:#0f2a4a;color:#fff;padding:8px 12px;text-align:left;font-size:12px;border:1px solid #ccc;">' . htmlspecialchars($h) . '</th>';
        }

        $body = '';
        $i = 0;
        foreach ($rows as $row) {
            $bg = $i % 2 === 0 ? '#f8fafc' : '#ffffff';
            $cells = '';
            foreach ($row as $v) {
                $cells .= '<td style="padding:6px 12px;border:1px solid #e2e8f0;font-size:11px;background:' . $bg . ';">' . htmlspecialchars((string) ($v ?? '')) . '</td>';
            }
            $body .= '<tr>' . $cells . '</tr>';
            $i++;
        }

        return '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="UTF-8"><title>' . $title . '</title></head><body style="font-family:Arial,sans-serif;margin:30px;">'
            . '<h1 style="color:#0f2a4a;font-size:18px;margin:0 0 4px;">' . $site . ' — ' . $title . ' Report</h1>'
            . '<p style="color:#64748b;font-size:11px;margin:0 0 16px;">Generated on ' . $date . '</p>'
            . '<table style="border-collapse:collapse;width:100%;">'
            . '<thead><tr>' . $hCells . '</tr></thead>'
            . '<tbody>' . $body . '</tbody></table>'
            . '</body></html>';
    }

    private function buildPrintHtml(string $title, array $headers, array $rows): string
    {
        $title = htmlspecialchars(ucfirst($title));
        $count = count($rows);
        $date  = now()->format('F j, Y');
        $site  = config('app.name', 'TriFair');

        $hCells = '';
        foreach ($headers as $h) {
            $hCells .= '<th style="background:#0f2a4a;color:#fff;padding:8px 12px;text-align:left;font-size:12px;border:1px solid #ccc;white-space:nowrap;">' . htmlspecialchars($h) . '</th>';
        }

        $body = '';
        $i = 0;
        foreach ($rows as $row) {
            $bg = $i % 2 === 0 ? '#f8fafc' : '#ffffff';
            $cells = '';
            foreach ($row as $v) {
                $cells .= '<td style="padding:6px 12px;border:1px solid #e2e8f0;font-size:11px;background:' . $bg . ';">' . htmlspecialchars((string) ($v ?? '')) . '</td>';
            }
            $body .= '<tr>' . $cells . '</tr>';
            $i++;
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<title>' . $title . ' — ' . $site . '</title>'
            . '<style>@media print{body{margin:15mm;}}</style></head>'
            . '<body style="font-family:Arial,sans-serif;margin:30px;">'
            . '<div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:12px;">'
            . '<div><h1 style="color:#0f2a4a;font-size:18px;margin:0 0 4px;">' . $site . ' — ' . $title . ' Report</h1>'
            . '<p style="color:#64748b;font-size:11px;margin:0;">Generated on ' . $date . ' &middot; ' . $count . ' record(s)</p></div>'
            . '<button onclick="window.print()" style="background:#0f2a4a;color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;"><i class="bi bi-printer"></i> Print / Save as PDF</button>'
            . '</div>'
            . '<table style="border-collapse:collapse;width:100%;">'
            . '<thead><tr>' . $hCells . '</tr></thead>'
            . '<tbody>' . $body . '</tbody></table>'
            . '</body></html>';
    }
}
