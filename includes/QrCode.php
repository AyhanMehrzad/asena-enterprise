<?php
/**
 * Asena Enterprise - Pure PHP Standalone QR Code Generator
 * 
 * Generates standards-compliant QR Code Matrix and outputs vector SVG or Data URI.
 * Works 100% offline with zero external network calls or third-party dependencies.
 */
class QrCode {
    /**
     * Generate an SVG string for the given text.
     *
     * @param string $text Content to encode
     * @param int $size Width/height in pixels
     * @param string $fgColor Foreground color (hex)
     * @param string $bgColor Background color (hex or 'transparent')
     * @param int $margin Quiet zone in modules
     * @return string SVG markup
     */
    public static function svg(string $text, int $size = 180, string $fgColor = '#001a48', string $bgColor = '#ffffff', int $margin = 2): string {
        $matrix = self::encodeText($text);
        $moduleCount = count($matrix);
        $totalModules = $moduleCount + ($margin * 2);
        $moduleSize = $size / $totalModules;

        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="%d" height="%d" viewBox="0 0 %d %d">',
            $size, $size, $size, $size
        );

        if ($bgColor !== 'transparent') {
            $svg[] = sprintf('<rect width="%d" height="%d" fill="%s"/>', $size, $size, htmlspecialchars($bgColor));
        }

        $path = '';
        for ($r = 0; $r < $moduleCount; $r++) {
            for ($c = 0; $c < $moduleCount; $c++) {
                if ($matrix[$r][$c]) {
                    $x = ($c + $margin) * $moduleSize;
                    $y = ($r + $margin) * $moduleSize;
                    $path .= sprintf('M%.2f,%.2fh%.2fv%.2fh-%.2fz ', $x, $y, $moduleSize, $moduleSize, $moduleSize);
                }
            }
        }

        $svg[] = sprintf('<path d="%s" fill="%s" shape-rendering="crispEdges"/>', trim($path), htmlspecialchars($fgColor));
        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * Generate Base64 Data URI of SVG
     */
    public static function dataUri(string $text, int $size = 180, string $fgColor = '#001a48', string $bgColor = '#ffffff'): string {
        $svg = self::svg($text, $size, $fgColor, $bgColor);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * QR Code Matrix Encoder
     * Implements QR Code Model 2 with Reed-Solomon Error Correction Level M
     */
    private static function encodeText(string $text): array {
        $data = unpack('C*', $text);
        $len = count($data);

        // Determine smallest QR Version (1 to 10) that can hold this byte length with Level M error correction
        // Capacities for Byte Mode Level M:
        $capacities = [
            1 => 14, 2 => 26, 3 => 42, 4 => 62, 5 => 84,
            6 => 106, 7 => 122, 8 => 152, 9 => 180, 10 => 213,
            11 => 251, 12 => 287, 13 => 331, 14 => 362
        ];

        $version = 1;
        foreach ($capacities as $v => $cap) {
            if ($len <= $cap) {
                $version = $v;
                break;
            }
            $version = $v;
        }

        $size = 17 + (4 * $version);
        $matrix = array_fill(0, $size, array_fill(0, $size, null));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // 1. Finder patterns (Top-Left, Top-Right, Bottom-Left)
        self::addFinderPattern($matrix, $reserved, 0, 0);
        self::addFinderPattern($matrix, $reserved, $size - 7, 0);
        self::addFinderPattern($matrix, $reserved, 0, $size - 7);

        // 2. Alignment patterns for Version >= 2
        if ($version >= 2) {
            $alignCoords = self::getAlignmentCoordinates($version);
            foreach ($alignCoords as $ar) {
                foreach ($alignCoords as $ac) {
                    // Skip if overlaps with finder patterns
                    if (($ar <= 8 && $ac <= 8) || ($ar <= 8 && $ac >= $size - 8) || ($ar >= $size - 8 && $ac <= 8)) {
                        continue;
                    }
                    self::addAlignmentPattern($matrix, $reserved, $ac - 2, $ar - 2);
                }
            }
        }

        // 3. Timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            if (!$reserved[6][$i]) {
                $matrix[6][$i] = $val;
                $reserved[6][$i] = true;
            }
            if (!$reserved[$i][6]) {
                $matrix[$i][6] = $val;
                $reserved[$i][6] = true;
            }
        }

        // 4. Dark module
        $matrix[4 * $version + 9][8] = 1;
        $reserved[4 * $version + 9][8] = true;

        // 5. Reserve format info areas
        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }

        // 6. Encode bit stream (Mode indicator: 0100 for Byte mode, Character count, Data, Terminator)
        $bits = '';
        $bits .= '0100'; // Byte mode indicator
        $charCountBits = ($version <= 9) ? 8 : 16;
        $bits .= str_pad(decbin($len), $charCountBits, '0', STR_PAD_LEFT);
        foreach ($data as $byte) {
            $bits .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        }

        // Total data codewords capacity for version & Level M
        $totalDataCodewords = self::getDataCodewordCount($version);
        $totalDataBits = $totalDataCodewords * 8;

        // Terminator
        $bits .= str_repeat('0', min(4, max(0, $totalDataBits - strlen($bits))));
        // Pad to byte multiple
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }
        // Pad with alternating 0xEC (11101100) and 0x11 (00010001)
        $padBytes = ['11101100', '00010001'];
        $padIdx = 0;
        while (strlen($bits) < $totalDataBits) {
            $bits .= $padBytes[$padIdx % 2];
            $padIdx++;
        }

        // 7. Error Correction with Reed-Solomon
        $dataCodewords = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $dataCodewords[] = bindec(substr($bits, $i, 8));
        }

        $ecCodewords = self::generateErrorCorrection($dataCodewords, $version);
        $finalCodewords = array_merge($dataCodewords, $ecCodewords);

        $finalBits = '';
        foreach ($finalCodewords as $cw) {
            $finalBits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }
        // Remainder bits
        $finalBits .= str_repeat('0', 7);

        // 8. Place data bits in matrix (Zig-zag right-to-left, bottom-to-top)
        $bitIdx = 0;
        $numBits = strlen($finalBits);
        $direction = -1; // -1 = up, 1 = down
        $row = $size - 1;

        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) $col--; // Skip vertical timing column

            while (true) {
                for ($c = 0; $c < 2; $c++) {
                    $currCol = $col - $c;
                    if (!$reserved[$row][$currCol]) {
                        $bit = ($bitIdx < $numBits) ? ($finalBits[$bitIdx] === '1' ? 1 : 0) : 0;
                        // Apply Mask Pattern 0: (row + col) % 2 == 0
                        $mask = (($row + $currCol) % 2 === 0) ? 1 : 0;
                        $matrix[$row][$currCol] = $bit ^ $mask;
                        $bitIdx++;
                    }
                }
                $row += $direction;
                if ($row < 0 || $row >= $size) {
                    $direction = -$direction;
                    $row += $direction;
                    break;
                }
            }
        }

        // 9. Format information: Mask 0 with Level M -> bits 101010000010010
        $formatBits = '101010000010010';
        self::placeFormatBits($matrix, $formatBits, $size);

        // Clean up remaining nulls
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if ($matrix[$r][$c] === null) {
                    $matrix[$r][$c] = 0;
                }
            }
        }

        return $matrix;
    }

    private static function addFinderPattern(array &$matrix, array &$reserved, int $startX, int $startY): void {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $matrix[$startY + $r][$startX + $c] = ($r === 0 || $r === 6 || $c === 0 || $c === 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) ? 1 : 0;
                $reserved[$startY + $r][$startX + $c] = true;
            }
        }
        // Separator
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $y = $startY + $r;
                $x = $startX + $c;
                if ($y >= 0 && $y < count($matrix) && $x >= 0 && $x < count($matrix)) {
                    if (!$reserved[$y][$x]) {
                        $matrix[$y][$x] = 0;
                        $reserved[$y][$x] = true;
                    }
                }
            }
        }
    }

    private static function addAlignmentPattern(array &$matrix, array &$reserved, int $startX, int $startY): void {
        for ($r = 0; $r < 5; $r++) {
            for ($c = 0; $c < 5; $c++) {
                $matrix[$startY + $r][$startX + $c] = ($r === 0 || $r === 4 || $c === 0 || $c === 4 || ($r === 2 && $c === 2)) ? 1 : 0;
                $reserved[$startY + $r][$startX + $c] = true;
            }
        }
    }

    private static function getAlignmentCoordinates(int $version): array {
        $table = [
            2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
            6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46],
            10 => [6, 28, 50], 11 => [6, 30, 54], 12 => [6, 32, 58], 13 => [6, 34, 62], 14 => [6, 26, 46, 66]
        ];
        return $table[$version] ?? [6, 18];
    }

    private static function getDataCodewordCount(int $version): int {
        $table = [
            1 => 16, 2 => 28, 3 => 44, 4 => 64, 5 => 86,
            6 => 108, 7 => 124, 8 => 154, 9 => 182, 10 => 216,
            11 => 254, 12 => 290, 13 => 334, 14 => 365
        ];
        return $table[$version] ?? 16;
    }

    private static function getECCodewordCount(int $version): int {
        $table = [
            1 => 10, 2 => 16, 3 => 26, 4 => 36, 5 => 48,
            6 => 64, 7 => 72, 8 => 88, 9 => 110, 10 => 130,
            11 => 150, 12 => 176, 13 => 198, 14 => 216
        ];
        return $table[$version] ?? 10;
    }

    private static function generateErrorCorrection(array $data, int $version): array {
        $ecCount = self::getECCodewordCount($version);
        // Reed-Solomon generator polynomial over GF(256)
        $genPoly = self::getGeneratorPolynomial($ecCount);
        $msg = array_pad($data, count($data) + $ecCount, 0);

        for ($i = 0; $i < count($data); $i++) {
            $coef = $msg[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < count($genPoly); $j++) {
                    $msg[$i + $j] ^= self::gfMul($genPoly[$j], $coef);
                }
            }
        }

        return array_slice($msg, count($data));
    }

    // Galois Field GF(256) Log and Antilog Tables (Primitive Polynomial 0x11D)
    private static array $gfExp = [];
    private static array $gfLog = [];

    private static function initGF(): void {
        if (!empty(self::$gfExp)) return;
        self::$gfExp = array_fill(0, 512, 0);
        self::$gfLog = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$gfExp[$i] = $x;
            self::$gfLog[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$gfExp[$i] = self::$gfExp[$i - 255];
        }
    }

    private static function gfMul(int $x, int $y): int {
        if ($x === 0 || $y === 0) return 0;
        self::initGF();
        return self::$gfExp[self::$gfLog[$x] + self::$gfLog[$y]];
    }

    private static function getGeneratorPolynomial(int $degree): array {
        self::initGF();
        $poly = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = [1, self::$gfExp[$i]];
            $res = array_fill(0, count($poly) + 1, 0);
            for ($p = 0; $p < count($poly); $p++) {
                for ($q = 0; $q < 2; $q++) {
                    $res[$p + $q] ^= self::gfMul($poly[$p], $next[$q]);
                }
            }
            $poly = $res;
        }
        return $poly;
    }

    private static function placeFormatBits(array &$matrix, string $formatBits, int $size): void {
        // Format info around Top-Left
        $formatIndicesTL = [
            [8, 0], [8, 1], [8, 2], [8, 3], [8, 4], [8, 5],
            [8, 7], [8, 8], [7, 8], [5, 8], [4, 8], [3, 8],
            [2, 8], [1, 8], [0, 8]
        ];
        // Format info around Top-Right and Bottom-Left
        $formatIndicesOther = [
            [$size - 1, 8], [$size - 2, 8], [$size - 3, 8], [$size - 4, 8],
            [$size - 5, 8], [$size - 6, 8], [$size - 7, 8],
            [8, $size - 8], [8, $size - 7], [8, $size - 6], [8, $size - 5],
            [8, $size - 4], [8, $size - 3], [8, $size - 2], [8, $size - 1]
        ];

        for ($i = 0; $i < 15; $i++) {
            $bit = (int)$formatBits[$i];
            $matrix[$formatIndicesTL[$i][0]][$formatIndicesTL[$i][1]] = $bit;
            $matrix[$formatIndicesOther[$i][0]][$formatIndicesOther[$i][1]] = $bit;
        }
    }
}
