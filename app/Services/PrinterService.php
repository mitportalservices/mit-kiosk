<?php

namespace App\Services;

class PrinterService
{
    /**
     * Print the consultation ticket directly to a shared network printer.
     */
    public function printTicket(string $displayNumber, string $meta, string $pcName, string $printerName): array
    {
        // If running in a hosted / non-Windows container (e.g. Render, Linux Docker),
        // physical local printer access via UNC/winspool is not available.
        // Return immediately so there is ZERO delay for the kiosk visitor.
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return [
                'ok' => false,
                'method' => 'cloud_skip',
                'target' => null,
                'error' => 'Hosted environment (non-Windows) cannot access local printer directly.',
            ];
        }

        $text = $this->buildTicketBytes($displayNumber, $meta);

        // Build list of UNC shares to try.
        // Putting loopback / local addresses first ensures near-instant (<120ms) connection
        // and eliminates 15-second SMB network timeouts.
        $shares = [];
        $shares[] = "\\\\127.0.0.1\\" . $printerName;
        $shares[] = "\\\\localhost\\" . $printerName;
        if (!empty($pcName) && !in_array(strtolower($pcName), ['127.0.0.1', 'localhost'])) {
            $shares[] = "\\\\" . $pcName . "\\" . $printerName;
        }
        $shares = array_unique($shares);

        $lastError = null;
        foreach ($shares as $share) {
            $uncWrite = $this->tryUncWrite($share, $text);
            if ($uncWrite['ok']) {
                return $uncWrite;
            }
            $lastError = $uncWrite['error'];
        }

        // Fallback: PowerShell raw printer helper via Windows winspool.drv
        // Try local printer name first so it doesn't incur network timeouts
        $targets = array_unique(array_merge([$printerName], $shares));
        $psTry = $this->tryPowerShellRaw($targets, $text);
        if ($psTry['ok']) {
            return $psTry;
        }

        return [
            'ok' => false,
            'method' => 'none',
            'target' => null,
            'error' => $psTry['error'] ?? ($lastError ?? 'Unknown print failure.'),
        ];
    }

    private function buildTicketBytes(string $displayNumber, string $meta): string
    {
        $orientation = function_exists('config') ? (string) config('printer.orientation', 'portrait') : 'portrait';

        $init        = "\x1B\x40";          // ESC @ Initialize printer
        // Maximize thermal print darkness:
        // ESC 7 (heating dots=7 [64 dots], heating time=140 [1.4ms longer burn time = much darker text], cooling interval=2)
        $heatDark    = "\x1B\x37\x07\x8C\x02";
        // DC2 # (print density = 31, maximum density)
        $densityDark = "\x12\x23\x1F";
        $rotate90On  = "\x1B\x56\x01";      // ESC V 1 Rotate 90
        $rotate90Off = "\x1B\x56\x00";      // ESC V 0 Cancel rotate 90
        $leftAlign   = "\x1B\x61\x00";      // ESC a 0 Left alignment (we center in software for 57mm accuracy)
        $fontA       = "\x1B\x4D\x00";      // ESC M 0 Standard Font A (~32 chars/line hardware max)
        $normalMode  = "\x1B\x21\x00\x1D\x21\x00"; // ESC ! 0 + GS ! 0: Normal 1x1 size
        $headerMode  = "\x1B\x21\x08";      // ESC ! 8: Font A, 1x1 Normal size, Bold (neat label like on screen)
        $numMode     = "\x1B\x21\x38\x1D\x21\x11"; // ESC ! 56 + GS ! 0x11: Universal Double-width (2x) + Double-height (2x) + Bold
        $boldOn      = "\x1B\x45\x01";      // ESC E 1 Bold / Emphasized ON
        $boldOff     = "\x1B\x45\x00";      // ESC E 0 Bold OFF
        $strikeOn    = "\x1B\x47\x01";      // ESC G 1 Double-strike ON (richer/darker thermal print)
        $strikeOff   = "\x1B\x47\x00";      // ESC G 0 Double-strike OFF
        $feed        = "\x0A";

        // 57mm paper sits against the left guide of a 58mm (32-col) printhead, so hardware
        // ESC a 1 centering shifts everything slightly right. Instead, we use left alignment
        // and software-center every line within $cols (default 30 chars) with optional $leftPad.
        $cols    = function_exists('config') ? (int) config('printer.chars_per_line', 30) : 30;
        $cols    = max(20, min(32, $cols));
        $leftPad = function_exists('config') ? (int) config('printer.left_pad', 0) : 0;
        $leftPad = max(0, min(6, $leftPad));
        $wrap    = $cols - 2;

        $centerLine = static function (string $str, int $charWidth = 1) use ($cols, $leftPad): string {
            $str = trim($str);
            $effectiveCols = intdiv($cols, max(1, $charWidth));
            $len = strlen($str);
            $pad = $len < $effectiveCols ? intdiv($effectiveCols - $len, 2) : 0;
            $totalSpacePad = ($leftPad > 0 && $charWidth === 1 ? $leftPad : 0) + $pad;
            return str_repeat(' ', $totalSpacePad) . $str . "\r\n";
        };

        $divider = str_repeat(' ', $leftPad) . str_repeat('-', $cols) . "\r\n";

        $text = $init . $heatDark . $densityDark . $fontA;

        if (strtolower($orientation) === 'landscape') {
            $text .= $rotate90On;
        }

        $text .= $leftAlign;

        // Top margin
        $text .= $feed;

        // Header: Clean, normal-sized label matching screen's "text-xs Consultation Number"
        $text .= $divider;
        $text .= $normalMode . $headerMode . $centerLine('CONSULTATION NUMBER') . $normalMode;
        $text .= $divider;

        // Ticket Number: THE HERO — Big, bold, double-width, double-height, double-strike
        // Digits are spaced (e.g. "1 0 1") so the number commands the center of the paper
        $rawNum = trim($displayNumber);
        $spacedNum = (strlen($rawNum) <= 4) ? implode(' ', str_split($rawNum)) : $rawNum;

        $text .= $feed;
        $text .= $numMode . $strikeOn . $centerLine($spacedNum, 2);
        $text .= $normalMode . $strikeOff . $boldOff;
        $text .= $feed;

        // Divider
        $text .= $divider;

        // Meta details: Staff & Location
        $cleanMeta = str_replace(['•', '·', ' | '], ' - ', trim($meta));
        $parts = explode(' - ', $cleanMeta, 2);

        if (count($parts) >= 2) {
            $staff = strtoupper(trim($parts[0]));
            $location = trim($parts[1]);

            // Staff name in bold uppercase
            $text .= $boldOn . $strikeOn;
            $staffLines = explode("\n", wordwrap($staff, $wrap, "\n", true));
            foreach ($staffLines as $line) {
                $text .= $centerLine($line);
            }
            $text .= $strikeOff . $boldOff;

            // Location
            if ($location !== '') {
                $locLines = explode("\n", wordwrap($location, $wrap, "\n", true));
                foreach ($locLines as $line) {
                    $text .= $centerLine($line);
                }
            }
        } else {
            // Single meta string fallback
            $text .= $boldOn . $strikeOn;
            $metaLines = explode("\n", wordwrap($cleanMeta, $wrap, "\n", true));
            foreach ($metaLines as $line) {
                $text .= $centerLine($line);
            }
            $text .= $strikeOff . $boldOff;
        }

        // Bottom divider & footer matching on-screen message
        $text .= $divider;
        $text .= $centerLine('Please keep this number');
        $tzName = function_exists('config') ? (string) config('printer.timezone', 'Asia/Manila') : 'Asia/Manila';
        try {
            $now = new \DateTime('now', new \DateTimeZone($tzName));
        } catch (\Exception $e) {
            $now = new \DateTime('now');
        }
        $text .= $centerLine($now->format('m/d/Y  h:i A'));

        // Feed enough lines (5 lines) to push past the physical tear-off bar
        $text .= $feed . $feed . $feed . $feed . $feed;

        if (strtolower($orientation) === 'landscape') {
            $text .= $rotate90Off;
        }

        // Cut commands
        $text .= "\x1B\x69";      // ESC i
        $text .= "\x1D\x56\x01";  // GS V 1

        return $text;
    }
    private function tryUncWrite(string $sharePath, string $bytes): array
    {
        $h = @fopen($sharePath, 'wb');
        if ($h === false) {
            $e = error_get_last();
            return [
                'ok' => false,
                'method' => 'unc_write',
                'target' => $sharePath,
                'error' => $e && isset($e['message']) ? $e['message'] : 'Unable to open printer share path.',
            ];
        }

        $written = @fwrite($h, $bytes);
        @fclose($h);
        if ($written === false || $written <= 0) {
            $e = error_get_last();
            return [
                'ok' => false,
                'method' => 'unc_write',
                'target' => $sharePath,
                'error' => $e && isset($e['message']) ? $e['message'] : 'Unable to write to printer share path.',
            ];
        }

        return [
            'ok' => true,
            'method' => 'unc_write',
            'target' => $sharePath,
            'error' => null,
        ];
    }

    private function tryPowerShellRaw(array $targets, string $bytes): array
    {
        if (!$this->canExecuteShell()) {
            return [
                'ok' => false,
                'method' => 'powershell_raw',
                'target' => null,
                'error' => 'Shell execution is disabled in PHP (shell_exec/exec).',
            ];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'tkt');
        if ($tmp === false) {
            return [
                'ok' => false,
                'method' => 'powershell_raw',
                'target' => null,
                'error' => 'Unable to create temporary file for print bytes.',
            ];
        }

        $psFile = null;
        try {
            file_put_contents($tmp, $bytes);

            $tmpEsc = $this->escapePowerShellSingleQuoted($tmp);

            $targets = array_values(array_filter(array_map(static fn ($t) => trim((string) $t), $targets)));
            $targetsLiteralLines = array_map(static function (string $t): string {
                return "    '" . str_replace("'", "''", $t) . "'";
            }, $targets);
            $targetsLiteral = implode(",\n", $targetsLiteralLines);

            $psScript = <<<POWERSHELL

\$ErrorActionPreference = 'Stop'

\$code = @'
using System;
using System.Runtime.InteropServices;

public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static object SendBytesToPrinter(string szPrinterName, IntPtr pBytes, Int32 dwCount) {
        IntPtr hPrinter = new IntPtr(0);
        int written = 0;
        int lastError = 0;
        bool success = false;

        DOCINFOA di = new DOCINFOA();
        di.pDocName = "Consultation Ticket";
        di.pDataType = "RAW";

        if (!OpenPrinter(szPrinterName, out hPrinter, IntPtr.Zero)) {
            lastError = Marshal.GetLastWin32Error();
            return new { ok = false, target = szPrinterName, error = lastError, written = 0 };
        }

        try {
            if (!StartDocPrinter(hPrinter, 1, di)) {
                lastError = Marshal.GetLastWin32Error();
                return new { ok = false, target = szPrinterName, error = lastError, written = 0 };
            }

            try {
                if (!StartPagePrinter(hPrinter)) {
                    lastError = Marshal.GetLastWin32Error();
                    return new { ok = false, target = szPrinterName, error = lastError, written = 0 };
                }

                success = WritePrinter(hPrinter, pBytes, dwCount, out written);
                if (!success) {
                    lastError = Marshal.GetLastWin32Error();
                }
                EndPagePrinter(hPrinter);
            } finally {
                EndDocPrinter(hPrinter);
            }
        } finally {
            ClosePrinter(hPrinter);
        }

        return new { ok = success, target = szPrinterName, error = lastError, written = written };
    }
}
'@

try {
    Add-Type -TypeDefinition \$code -ErrorAction Stop
    \$bytes = [System.IO.File]::ReadAllBytes('$tmpEsc')
    \$ptr = [System.Runtime.InteropServices.Marshal]::AllocHGlobal(\$bytes.Length)
    [System.Runtime.InteropServices.Marshal]::Copy(\$bytes, 0, \$ptr, \$bytes.Length)

    \$targets = @(
$targetsLiteral
    )

    \$result = \$null
    foreach (\$t in \$targets) {
        if ([string]::IsNullOrWhiteSpace(\$t)) { continue }
        \$result = [RawPrinterHelper]::SendBytesToPrinter(\$t, \$ptr, \$bytes.Length)
        if (\$result.ok) { break }
    }

    [System.Runtime.InteropServices.Marshal]::FreeHGlobal(\$ptr)
    \$result | ConvertTo-Json -Compress
} catch {
    \$msg = (\$_ | Out-String)
    @{ ok = false; target = \$null; error = \$msg; written = 0 } | ConvertTo-Json -Compress
}

POWERSHELL;

            $psFile = tempnam(sys_get_temp_dir(), 'ps');
            if ($psFile === false) {
                return [
                    'ok' => false,
                    'method' => 'powershell_raw',
                    'target' => null,
                    'error' => 'Unable to create temporary PowerShell script file.',
                ];
            }
            $psFile .= '.ps1';
            file_put_contents($psFile, $psScript);

            $cmd = 'powershell.exe -NoLogo -NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' . escapeshellarg($psFile) . ' 2>&1';
            $outRaw = $this->runShellCommand($cmd);
            $out = is_string($outRaw) ? trim($outRaw) : '';
            $decoded = $out !== '' ? json_decode($out, true) : null;

            if (is_array($decoded) && isset($decoded['ok']) && $decoded['ok']) {
                return [
                    'ok' => true,
                    'method' => 'powershell_raw',
                    'target' => isset($decoded['target']) ? (string) $decoded['target'] : null,
                    'error' => null,
                ];
            }

            $errorCode = is_array($decoded) && isset($decoded['error']) ? (string) $decoded['error'] : null;
            $target = is_array($decoded) && isset($decoded['target']) ? (string) $decoded['target'] : null;

            return [
                'ok' => false,
                'method' => 'powershell_raw',
                'target' => $target,
                'error' => $errorCode
                    ? ('Win32 error: ' . $errorCode)
                    : ($out !== '' ? $out : ($outRaw === null ? 'Shell execution returned no output (disabled or blocked).' : 'PowerShell printing returned no output.')),
            ];
        } finally {
            if (is_string($tmp) && file_exists($tmp)) {
                @unlink($tmp);
            }
            if (is_string($psFile) && file_exists($psFile)) {
                @unlink($psFile);
            }
        }
    }

    private function canExecuteShell(): bool
    {
        $disabled = (string) ini_get('disable_functions');
        $disabled = array_filter(array_map('trim', explode(',', $disabled)));
        $disabledMap = array_fill_keys($disabled, true);

        $shellExecOk = function_exists('shell_exec') && !isset($disabledMap['shell_exec']);
        $execOk = function_exists('exec') && !isset($disabledMap['exec']);
        return $shellExecOk || $execOk;
    }

    private function runShellCommand(string $command): ?string
    {
        $disabled = (string) ini_get('disable_functions');
        $disabled = array_filter(array_map('trim', explode(',', $disabled)));
        $disabledMap = array_fill_keys($disabled, true);

        if (function_exists('shell_exec') && !isset($disabledMap['shell_exec'])) {
            return @shell_exec($command);
        }

        if (function_exists('exec') && !isset($disabledMap['exec'])) {
            $lines = [];
            $code = 0;
            @exec($command, $lines, $code);
            return implode("\n", $lines);
        }

        return null;
    }

    private function escapePowerShellSingleQuoted(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
