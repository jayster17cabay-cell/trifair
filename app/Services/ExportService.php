<?php

namespace App\Services;

use Illuminate\Http\Response;

/**
 * Builds UTF-8 CSV downloads (with BOM so Excel renders accented names
 * correctly) from a set of headers and rows.
 */
class ExportService
{
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

    public function operatorsCsv(\Illuminate\Support\Collection $operators): Response
    {
        $headers = ['ID', 'Name', 'Email', 'TODA', 'License No.', 'Plate No.', 'Body No.', 'Tricycle Color', 'Contact', 'Address', 'Status', 'Archived', 'Registered'];
        $rows = $operators->map(function ($o) {
            return [
                $o->id,
                $o->user->name ?? '',
                $o->user->email ?? '',
                $o->toda->name ?? 'Unassigned',
                $o->license_number ?? '',
                $o->plate_number ?? '',
                $o->body_number ?? '',
                $o->tricycle_color ?? '',
                $o->contact_number ?? '',
                $o->address ?? '',
                ucfirst($o->status),
                $o->isArchived() ? 'Yes' : '',
                $o->created_at?->format('Y-m-d H:i') ?? '',
            ];
        });

        return $this->csv('operators.csv', $headers, $rows->all());
    }

    public function ratingsCsv(\Illuminate\Support\Collection $ratings): Response
    {
        $headers = ['ID', 'Trip ID', 'Operator', 'Rating', 'Comment', 'Date'];
        return $this->csv('ratings.csv', $headers, $ratings->all());
    }

    public function complaintsCsv(\Illuminate\Support\Collection $complaints): Response
    {
        $headers = ['ID', 'Trip ID', 'Operator', 'Rating', 'Complaint', 'Status', 'Date'];
        return $this->csv('complaints.csv', $headers, $complaints->all());
    }

    public function reportsCsv(\Illuminate\Support\Collection $reports): Response
    {
        $headers = ['Operator', 'TODA', 'Body No.', 'Plate No.', 'Total Trips', 'Average Rating', 'Status'];
        return $this->csv('reports.csv', $headers, $reports->all());
    }

    public function activityLogsCsv(\Illuminate\Support\Collection $logs): Response
    {
        $headers = ['Date', 'User', 'Action', 'Category', 'Description'];
        return $this->csv('activity-logs.csv', $headers, $logs->all());
    }
}
