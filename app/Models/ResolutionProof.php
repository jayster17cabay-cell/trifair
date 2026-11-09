<?php

namespace App\Models;

use App\Helpers\SupabaseStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResolutionProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'rating_id',
        'file_path',
        'file_type',
        'original_name',
    ];

    public function rating()
    {
        return $this->belongsTo(Rating::class);
    }

    /**
     * Raw bytes for this proof so it can be attached to the passenger's
     * resolution email. Local storage is checked first (the fallback backend),
     * then Supabase Storage. Returns null when the file cannot be read.
     */
    public function contents(): ?string
    {
        try {
            if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
                return Storage::disk('public')->get($this->file_path);
            }
        } catch (\Throwable $e) {
            Log::warning('Resolution proof local read failed', [
                'proof_id' => $this->id,
                'path' => $this->file_path,
                'message' => $e->getMessage(),
            ]);
        }

        if (!SupabaseStorage::configured()) {
            return null;
        }

        try {
            $url = SupabaseStorage::getAccessUrl($this->file_path);
            if (!$url) {
                return null;
            }

            $response = Http::timeout(20)->get($url);
            if ($response->successful()) {
                return $response->body();
            }

            Log::warning('Resolution proof download failed', [
                'proof_id' => $this->id,
                'path' => $this->file_path,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Resolution proof download exception', [
                'proof_id' => $this->id,
                'path' => $this->file_path,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
