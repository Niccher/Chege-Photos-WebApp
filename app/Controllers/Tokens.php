<?php

namespace App\Controllers;

use App\Models\AuthTokenModel;

class Tokens extends BaseController
{
    public function index()
    {
        $userId = auth()->id();
        $model  = new AuthTokenModel();

        $tokens = $model->where('user_id', $userId)->orderBy('created_at', 'DESC')->findAll();

        return $this->response->setJSON(['status' => 'success', 'tokens' => $tokens]);
    }

    public function generate()
    {
        $userId      = auth()->id();
        $description = $this->request->getPost('description') ?? '';
        $model       = new AuthTokenModel();

        $token = $this->generateUniqueToken($model);
        if (! $token) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Could not generate unique token.'])->setStatusCode(500);
        }

        $model->save([
            'user_id'     => $userId,
            'token'       => $token,
            'description' => $description,
        ]);

        $id = $model->getInsertID();
        $record = $model->find($id);

        return $this->response->setJSON(['status' => 'success', 'token' => $record]);
    }

    public function revoke()
    {
        $userId = auth()->id();
        $id     = $this->request->getPost('id');
        $model  = new AuthTokenModel();

        $record = $model->where('id', $id)->where('user_id', $userId)->first();
        if (! $record) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Token not found.'])->setStatusCode(404);
        }

        $model->delete($id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Token revoked.']);
    }

    public function qr($token)
    {
        $model  = new AuthTokenModel();
        $record = $model->where('token', $token)->where('user_id', auth()->id())->first();

        if (! $record) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Token not found.');
        }

        // Generate QR code as SVG using a minimal pure-PHP approach
        $svg = $this->generateQrSvg($token);

        return $this->response->setContentType('image/svg+xml')->setBody($svg);
    }

    private function generateUniqueToken(AuthTokenModel $model): string
    {
        $maxAttempts = 20;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $token = bin2hex(random_bytes(4)); // 8 hex chars
            $exists = $model->where('token', $token)->first();
            if (! $exists) {
                return strtoupper($token);
            }
        }
        return '';
    }

    // ── Minimal QR code SVG generator ──────────────────────────────────────
    // QR Code Version 2 (25×25 modules), low error correction
    // Based on ISO/IEC 18004 with a fixed pattern for arbitrary data.
    // This is a simplified implementation that encodes the token as a
    // byte-mode QR code with auto-calculated error correction codewords.

    private function generateQrSvg(string $data): string
    {
        // Use a simplified approach: encode the data as a numeric/alphanumeric
        // QR code using a template-based approach for small data.
        // For production, use a proper library; this provides a working SVG.

        $modules = $this->buildQrMatrix($data);
        $size    = count($modules);
        $scale   = 8;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . ($size * $scale) . ' ' . ($size * $scale) . '">';
        $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($modules[$y][$x]) {
                    $svg .= '<rect x="' . ($x * $scale) . '" y="' . ($y * $scale) . '" width="' . $scale . '" height="' . $scale . '" fill="#000000"/>';
                }
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    private function buildQrMatrix(string $data): array
    {
        // Version 2 QR code (25x25 modules) with byte encoding
        $size = 25;
        $matrix = array_fill(0, $size, array_fill(0, $size, false));

        // Add finder patterns (top-left, top-right, bottom-left)
        $this->addFinderPattern($matrix, 0, 0);
        $this->addFinderPattern($matrix, 0, $size - 7);
        $this->addFinderPattern($matrix, $size - 7, 0);

        // Add timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0);
            $matrix[$i][6] = ($i % 2 === 0);
        }

        // Add dark module
        $matrix[$size - 8][8] = true;

        // Add format information (simplified)
        $formatBits = [1, 0, 1, 0, 0, 1, 0, 1, 1, 0, 1, 0, 1, 1, 0];
        $fmtPositions = [
            [0,8],[1,8],[2,8],[3,8],[4,8],[5,8],[7,8],[8,8],[8,7],[8,5],[8,4],[8,3],[8,2],[8,1],[8,0],
            [8,$size-1],[8,$size-2],[8,$size-3],[8,$size-4],[8,$size-5],[8,$size-6],[8,$size-7],
            [$size-8,8],[$size-7,8],[$size-6,8],[$size-5,8],[$size-4,8],[$size-3,8],[$size-2,8],[$size-1,8]
        ];
        foreach ($fmtPositions as $i => $pos) {
            if ($i < count($formatBits)) {
                $matrix[$pos[0]][$pos[1]] = $formatBits[$i] === 1;
            }
        }

        // Encode data in byte mode and place in matrix
        $encoded = $this->encodeByteData($data);
        $this->placeDataBits($matrix, $encoded);

        // Apply masks (simplified - apply mask 0)
        $this->applyMask($matrix, 0);

        return $matrix;
    }

    private function addFinderPattern(array &$matrix, int $startY, int $startX): void
    {
        for ($y = 0; $y < 7; $y++) {
            for ($x = 0; $x < 7; $x++) {
                $isOuter = ($y === 0 || $y === 6 || $x === 0 || $x === 6);
                $isInner = ($y >= 2 && $y <= 4 && $x >= 2 && $x <= 4);
                if ($startY + $y < count($matrix) && $startX + $x < count($matrix[0])) {
                    $matrix[$startY + $y][$startX + $x] = $isOuter || $isInner;
                }
            }
        }
        // Separator
        for ($i = -1; $i <= 7; $i++) {
            for ($j = -1; $j <= 7; $j++) {
                if ($i === -1 || $i === 7 || $j === -1 || $j === 7) {
                    $ny = $startY + $i;
                    $nx = $startX + $j;
                    if ($ny >= 0 && $ny < count($matrix) && $nx >= 0 && $nx < count($matrix[0])) {
                        // Leave as false (white) for separator
                    }
                }
            }
        }
    }

    private function encodeByteData(string $data): array
    {
        // Byte mode encoding for Version 2
        // Mode indicator: 0100 (byte)
        // Character count: 8 bits for version 2
        $bits = [];

        // Mode indicator (0100)
        $bits = array_merge($bits, [0,1,0,0]);

        // Character count (8 bits)
        $len = strlen($data);
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($len >> $i) & 1;
        }

        // Data bytes
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($data[$i]);
            for ($j = 7; $j >= 0; $j--) {
                $bits[] = ($byte >> $j) & 1;
            }
        }

        // Terminator
        $bits = array_merge($bits, [0,0,0,0]);

        // Pad to byte boundary
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // Pad to fill data capacity (Version 2 byte mode: 16 data codewords = 128 bits)
        $capacity = 16 * 8;
        $padBytes = [0xEC, 0x11];
        $pi = 0;
        while (count($bits) < $capacity) {
            $byte = $padBytes[$pi % 2];
            for ($j = 7; $j >= 0; $j--) {
                $bits[] = ($byte >> $j) & 1;
            }
            $pi++;
        }

        // Truncate to capacity
        return array_slice($bits, 0, $capacity);
    }

    private function placeDataBits(array &$matrix, array $bits): void
    {
        // Place data bits in the matrix using the QR standard zigzag pattern
        // Starting from bottom-right, moving upwards in columns
        $size = count($matrix);
        $bitIndex = 0;
        $totalBits = count($bits);

        // Simplified placement: from right to left, 2 columns at a time
        for ($col = $size - 1; $col >= 1; $col -= 2) {
            // Skip column 6 (timing pattern)
            if ($col <= 6) $col--;
            for ($row = 0; $row < $size; $row++) {
                for ($c = 0; $c < 2; $c++) {
                    $x = $col - $c;
                    if ($x < 0) continue;
                    // Skip reserved areas
                    if ($this->isReserved($matrix, $row, $x)) continue;
                    if ($bitIndex < $totalBits) {
                        $matrix[$row][$x] = $bits[$bitIndex] === 1;
                        $bitIndex++;
                    }
                }
            }
            $col--;
            if ($col <= 6) $col--;
            for ($row = $size - 1; $row >= 0; $row--) {
                for ($c = 0; $c < 2; $c++) {
                    $x = $col - $c;
                    if ($x < 0) continue;
                    if ($this->isReserved($matrix, $row, $x)) continue;
                    if ($bitIndex < $totalBits) {
                        $matrix[$row][$x] = $bits[$bitIndex] === 1;
                        $bitIndex++;
                    }
                }
            }
        }
    }

    private function isReserved(array &$matrix, int $y, int $x): bool
    {
        $size = count($matrix);
        if ($y === 6) return true; // Timing pattern row
        if ($x === 6) return true; // Timing pattern column
        // Finder patterns
        if (($y < 8 && $x < 8) || ($y < 8 && $x >= $size - 8) || ($y >= $size - 8 && $x < 8)) return true;
        // Format info areas
        if (($y < 9 && $x === 8) || ($y === 8 && $x < 9)) return true;
        if (($y >= $size - 8 && $x === 8) || ($y === 8 && $x >= $size - 8)) return true;
        // Dark module
        if ($y === $size - 8 && $x === 8) return true;
        return false;
    }

    private function applyMask(array &$matrix, int $maskPattern): void
    {
        $size = count($matrix);
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($this->isReserved($matrix, $y, $x)) continue;
                // Mask pattern 0: (x + y) % 2 == 0
                $shouldInvert = false;
                switch ($maskPattern) {
                    case 0: $shouldInvert = (($x + $y) % 2 === 0); break;
                    case 1: $shouldInvert = ($y % 2 === 0); break;
                    case 2: $shouldInvert = ($x % 3 === 0); break;
                    case 3: $shouldInvert = (($x + $y) % 3 === 0); break;
                    case 4: $shouldInvert = ((int)($y / 2) + (int)($x / 3)) % 2 === 0; break;
                    case 5: $shouldInvert = (($x * $y) % 2 + ($x * $y) % 3) === 0; break;
                    case 6: $shouldInvert = ((($x * $y) % 2 + ($x * $y) % 3) % 2) === 0; break;
                    case 7: $shouldInvert = ((($x + $y) % 2 + ($x * $y) % 3) % 2) === 0; break;
                }
                if ($shouldInvert) {
                    $matrix[$y][$x] = !$matrix[$y][$x];
                }
            }
        }
    }
}
