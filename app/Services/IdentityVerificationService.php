<?php

namespace App\Services;

use App\Models\User;
use App\Models\QrAbsen; // Import if needed for QR validation
use Carbon\Carbon; // Import if needed for date/time operations
use Illuminate\Support\Facades\Log; // Import for logging

class IdentityVerificationService
{
    /**
     * Verify user identity based on attendance type and provided data.
     *
     * @param string $attendanceType Type of attendance (e.g., 'face_recognition', 'qr_code').
     * @param string $identityData Data provided for verification (e.g., face embedding string, QR code string).
     * @param User $user The authenticated user.
     * @param string $absensiContext Context of the verification (e.g., 'clock_in', 'clock_out').
     * @return bool True if verification succeeds, false otherwise.
     */
    public function verify(string $attendanceType, string $identityData, User $user, string $absensiContext): bool
    {
        Log::info("IdentityVerificationService: Attempting verification for user {$user->id} ({$user->name}), type: {$attendanceType}, context: {$absensiContext}");

        // --- Mulai Penambahan Logika untuk Pengguna WFA ---
        // Jika pengguna adalah WFA dan tipe absensi adalah ON_SITE atau face_recognition,
        // lewati verifikasi identitas spesifik dan anggap berhasil.
        if ($user->is_wfa && ($attendanceType === 'ON_SITE' || $attendanceType === 'face_recognition')) {
            Log::info("IdentityVerificationService: WFA user ({$user->id}) with type {$attendanceType}. Skipping specific identity verification.");
            return true; // Verifikasi dianggap berhasil untuk WFA pada tipe ini
        }
        // --- Akhir Penambahan Logika untuk Pengguna WFA ---

        switch ($attendanceType) {
            case 'face_recognition':
                Log::info("IdentityVerificationService: Processing Face Recognition for user {$user->id}.");

                // --- Mulai Logika Verifikasi untuk Non-WFA (Sesuai Saran Pengguna) ---
                // Untuk pengguna NON-WFA dengan tipe face_recognition, backend hanya memeriksa
                // apakah data embedding diterima dari frontend. Asumsinya frontend
                // sudah melakukan perbandingan dengan embedding tersimpan.
                if (!$user->is_wfa) {
                     if (!empty($identityData)) {
                         Log::info("IdentityVerificationService: Non-WFA user ({$user->id}) with type face_recognition. Received non-empty identityData. Assuming frontend verification successful.");
                         return true; // Anggap verifikasi berhasil jika data embedding diterima
                     } else {
                         Log::warning("IdentityVerificationService: Non-WFA user ({$user->id}) with type face_recognition. Received empty identityData. Verification failed.");
                         return false; // Verifikasi gagal jika data embedding kosong
                     }
                 }
                 // --- Akhir Logika Verifikasi untuk Non-WFA (Sesuai Saran Pengguna) ---

                // 1. Retrieve stored face embedding for the user
                $storedEmbeddingJson = $user->face_embedding; // Asumsi nama kolom adalah face_embedding

                if (empty($storedEmbeddingJson)) {
                    Log::warning("IdentityVerificationService: Face Recognition failed: No stored embedding for user {$user->id}.");
                    return false; // Tidak ada embedding tersimpan
                }

                $storedEmbedding = array_map('floatval', explode(',', $storedEmbeddingJson));

                if (!is_array($storedEmbedding) || empty($storedEmbedding)) {
                    Log::warning("IdentityVerificationService: Face Recognition failed: No valid stored embedding for user {$user->id}.");
                    return false; // Tidak ada embedding tersimpan yang valid
                }

                // Add logging for stored embedding
                Log::info("IdentityVerificationService: Stored embedding for user {$user->id}: [" . implode(", ", $storedEmbedding) . "]");

                // 2. Parse identityData string into an array of floats
                $receivedEmbedding = array_map('floatval', explode(',', $identityData));

                if (!is_array($receivedEmbedding) || empty($receivedEmbedding)) {
                    Log::warning("IdentityVerificationService: Face Recognition failed: Failed to decode received embedding for user {$user->id}. Error: Invalid format.");
                    return false; // Gagal decode JSON dari Flutter
                }

                // Add logging for received embedding
                Log::info("IdentityVerificationService: Received embedding from frontend for user {$user->id}: [" . implode(", ", $receivedEmbedding) . "]");

                // Optional: Check if embeddings have the same dimension/length
                // You should know the expected size based on your ML model.
                // Example check (assuming embedding size is 128 or something similar):
                // if (count($storedEmbedding) !== 128 || count($receivedEmbedding) !== 128) {
                //      Log::warning("IdentityVerificationService: Embedding size mismatch for user {$user->id}.");
                //      return false;
                // }

                // 3. Compare embeddings (using Cosine Similarity or similar metric)
                // TODO: Implement calculateCosineSimilarity function
                $similarityThreshold = 0.8; // TODO: Tentukan threshold yang tepat melalui eksperimen

                $similarity = $this->calculateCosineSimilarity($storedEmbedding, $receivedEmbedding);

                // Add logging for similarity score and threshold
                Log::info("IdentityVerificationService: Cosine Similarity for user {$user->id}: " . $similarity . ", Threshold: " . $similarityThreshold);

                $identityVerified = $similarity >= $similarityThreshold;

                if (!$identityVerified) {
                    Log::warning("IdentityVerificationService: Face Recognition failed: Similarity ($similarity) below threshold ($similarityThreshold) for user {$user->id}.");
                }

                return $identityVerified; // <<< Logika sebenarnya

            case 'qr_code':
                // TODO: Implement QR Code validation logic.
                // - Validate $identityData (the scanned QR string).
                // - Retrieve the correct QR code for today's date and the given context ('clock_in' or 'clock_out') from the QrAbsen model.
                // - Compare $identityData with the retrieved QR code string.
                // - Return true if they match, false otherwise.

                 $today = Carbon::now()->toDateString();
                 $qrRecord = QrAbsen::where('date', $today)->first();

                 if (!$qrRecord) {
                     Log::warning("IdentityVerificationService: QR validation failed: No QR record found for today {$today}.");
                     return false;
                 }

                 $validQrCode = null;
                 if ($absensiContext === 'clock_in') {
                     $validQrCode = $qrRecord->qr_checkin;
                 } elseif ($absensiContext === 'clock_out') {
                     $validQrCode = $qrRecord->qr_checkout;
                 }

                 if (is_null($validQrCode)) {
                      Log::warning("IdentityVerificationService: QR validation failed: Invalid absensi context {$absensiContext} or QR field is null.");
                      return false;
                 }

                 $isValid = ($identityData === $validQrCode);
                 Log::info("IdentityVerificationService: QR validation result: " . ($isValid ? "Valid" : "Invalid") . ", received: {$identityData}, expected: {$validQrCode}");

                 return $isValid; // <<< Ini sudah logika sebenarnya untuk QR

            case 'ON_SITE': // Tambahkan penanganan untuk tipe ON_SITE
                Log::info("IdentityVerificationService: Processing ON_SITE type for user {$user->id}.");
                // Untuk tipe ON_SITE (yang memerlukan validasi lokasi dan wajah di frontend)
                // dan pengguna NON-WFA, kita terapkan logika yang sama dengan face_recognition:
                // Cek apakah data embedding wajah diterima dari frontend.
                if (!$user->is_wfa) {
                    if (!empty($identityData)) {
                        Log::info("IdentityVerificationService: Non-WFA user ({$user->id}) with type ON_SITE. Received non-empty identityData. Assuming frontend verification successful.");
                        return true; // Anggap verifikasi berhasil jika data embedding diterima
                    } else {
                        Log::warning("IdentityVerificationService: Non-WFA user ({$user->id}) with type ON_SITE. Received empty identityData. Verification failed.");
                        return false; // Verifikasi gagal jika data embedding kosong
                    }
                }
                // Jika ON_SITE tapi WFA (harusnya sudah ditangani di blok if pertama),
                // atau ada logika ON_SITE lain, akan fallthrough ke default (jika tidak ada break/return di atas).
                // Berdasarkan alur saat ini, blok if di atas cukup untuk kasus ON_SITE non-WFA.

                // Jika ada logika ON_SITE lain untuk non-WFA yang TIDAK terkait wajah/lokasi,
                // tambahkan di sini. Saat ini, kita asumsikan ON_SITE non-WFA selalu memerlukan verifikasi wajah.

                // Fallthrough to default or add specific handling if needed.
                // Karena kita sudah menangani kasus ON_SITE non-WFA di atas,
                // kasus ON_SITE WFA sudah ditangani di awal function.
                // Jadi, seharusnya tidak ada kasus ON_SITE lain yang sampai sini.
                // Namun, sebagai penanganan default, kita bisa return false.
                 return false; // Default untuk kasus ON_SITE yang tidak ditangani di atas (walau seharusnya tidak tercapai)

            default:
                Log::warning("IdentityVerificationService: Unsupported attendance type for verification: {$attendanceType}.");
                return false;
        }
    }

    /**
     * Calculates the Cosine Similarity between two embedding vectors.
     *
     * @param array<float> $embedding1 The first embedding vector.
     * @param array<float> $embedding2 The second embedding vector.
     * @return float The cosine similarity score (between -1 and 1).
     */
    private function calculateCosineSimilarity(array $embedding1, array $embedding2): float
    {
        // Ensure the vectors have the same length
        if (count($embedding1) !== count($embedding2)) {
            Log::error("Cosine Similarity Error: Embedding vector lengths mismatch.");
            return 0.0; // Or throw an exception, depending on desired behavior
        }

        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        $dimension = count($embedding1);

        for ($i = 0; $i < $dimension; $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += pow($embedding1[$i], 2);
            $magnitude2 += pow($embedding2[$i], 2);
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
             // Avoid division by zero if one of the embeddings is all zeros
             Log::warning("Cosine Similarity Warning: One or both embedding magnitudes are zero.");
             return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }
} 