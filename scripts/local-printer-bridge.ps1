# Standalone PowerShell Thermal Print Bridge for MIT Kiosk (Zero Dependencies!)
# Listens on http://127.0.0.1:18181 and prints directly to any POS-58 / 58mm thermal printer
# via Windows winspool.drv RAW printing (no PHP or XAMPP required on the Mini PC!).

$ErrorActionPreference = 'Stop'

# Compile RawPrinterHelper P/Invoke once at startup for instant (<50ms) printing
$pinvoke = @'
using System;
using System.Collections.Generic;
using System.Runtime.InteropServices;

public class KioskRawPrinter {
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

    public static bool SendBytes(string printerName, byte[] bytes, out int lastErr) {
        IntPtr hPrinter = IntPtr.Zero;
        lastErr = 0;
        if (!OpenPrinter(printerName, out hPrinter, IntPtr.Zero)) {
            lastErr = Marshal.GetLastWin32Error();
            return false;
        }
        IntPtr ptr = Marshal.AllocHGlobal(bytes.Length);
        try {
            Marshal.Copy(bytes, 0, ptr, bytes.Length);
            DOCINFOA di = new DOCINFOA { pDocName = "Consultation Ticket", pDataType = "RAW" };
            if (!StartDocPrinter(hPrinter, 1, di)) {
                lastErr = Marshal.GetLastWin32Error();
                return false;
            }
            try {
                if (!StartPagePrinter(hPrinter)) {
                    lastErr = Marshal.GetLastWin32Error();
                    return false;
                }
                int written = 0;
                bool ok = WritePrinter(hPrinter, ptr, bytes.Length, out written);
                if (!ok) lastErr = Marshal.GetLastWin32Error();
                EndPagePrinter(hPrinter);
                return ok && written > 0;
            } finally {
                EndDocPrinter(hPrinter);
            }
        } finally {
            Marshal.FreeHGlobal(ptr);
            ClosePrinter(hPrinter);
        }
    }
}
'@

Add-Type -TypeDefinition $pinvoke -ErrorAction SilentlyContinue

function Center-Line([string]$str, [int]$cols = 30, [int]$charWidth = 1, [int]$leftPad = 0) {
    $str = $str.Trim()
    $effectiveCols = [Math]::Floor($cols / [Math]::Max(1, $charWidth))
    $pad = if ($str.Length -lt $effectiveCols) { [Math]::Floor(($effectiveCols - $str.Length) / 2) } else { 0 }
    $totalPad = (if ($leftPad -gt 0 -and $charWidth -eq 1) { $leftPad } else { 0 }) + $pad
    return (' ' * $totalPad) + $str + "`r`n"
}

function Wrap-Text([string]$text, [int]$maxWidth) {
    $words = $text -split '\s+'
    $lines = New-Object System.Collections.Generic.List[string]
    $current = ''
    foreach ($w in $words) {
        if ($w -eq '') { continue }
        if ($current -eq '') {
            $current = $w
        } elseif (($current.Length + 1 + $w.Length) -le $maxWidth) {
            $current += ' ' + $w
        } else {
            $lines.Add($current)
            $current = $w
        }
    }
    if ($current -ne '') { $lines.Add($current) }
    if ($lines.Count -eq 0) { $lines.Add('') }
    return $lines
}

function Build-TicketBytes([string]$displayNumber, [string]$meta) {
    $cols = 30
    $leftPad = 0
    $wrap = $cols - 2

    $enc = [System.Text.Encoding]::GetEncoding(437)
    $ms = New-Object System.IO.MemoryStream
    $writeBytes = { param([byte[]]$b) $ms.Write($b, 0, $b.Length) }
    $writeStr   = { param([string]$s) $b = $enc.GetBytes($s); $ms.Write($b, 0, $b.Length) }

    # ESC @ Initialize, ESC 7 dark heat, DC2 # max density, ESC M 0 Font A, ESC a 0 Left align
    & $writeBytes @(0x1B,0x40, 0x1B,0x37,0x07,0x8C,0x02, 0x12,0x23,0x1F, 0x1B,0x4D,0x00, 0x1B,0x61,0x00, 0x0A)

    $divider = (' ' * $leftPad) + ('-' * $cols) + "`r`n"
    & $writeStr $divider
    & $writeBytes @(0x1B,0x21,0x08) # Bold header
    & $writeStr (Center-Line 'CONSULTATION NUMBER' $cols 1 $leftPad)
    & $writeBytes @(0x1B,0x21,0x00, 0x1D,0x21,0x00)
    & $writeStr $divider

    $rawNum = $displayNumber.Trim()
    $spacedNum = if ($rawNum.Length -le 4) { ($rawNum.ToCharArray() -join ' ') } else { $rawNum }

    & $writeBytes @(0x0A, 0x1B,0x21,0x38, 0x1D,0x21,0x11, 0x1B,0x47,0x01) # 2x2 + Bold + Double-strike
    & $writeStr (Center-Line $spacedNum $cols 2 $leftPad)
    & $writeBytes @(0x1B,0x21,0x00, 0x1D,0x21,0x00, 0x1B,0x47,0x00, 0x1B,0x45,0x00, 0x0A)
    & $writeStr $divider

    $cleanMeta = ($meta -replace '[•·|]', ' - ').Trim()
    $parts = $cleanMeta -split '\s+-\s+', 2
    if ($parts.Count -ge 2) {
        $staff = $parts[0].Trim().ToUpperInvariant()
        $loc = $parts[1].Trim()
        & $writeBytes @(0x1B,0x45,0x01, 0x1B,0x47,0x01)
        foreach ($line in (Wrap-Text $staff $wrap)) {
            & $writeStr (Center-Line $line $cols 1 $leftPad)
        }
        & $writeBytes @(0x1B,0x47,0x00, 0x1B,0x45,0x00)
        if ($loc -ne '') {
            foreach ($line in (Wrap-Text $loc $wrap)) {
                & $writeStr (Center-Line $line $cols 1 $leftPad)
            }
        }
    } else {
        & $writeBytes @(0x1B,0x45,0x01, 0x1B,0x47,0x01)
        foreach ($line in (Wrap-Text $cleanMeta.ToUpperInvariant() $wrap)) {
            & $writeStr (Center-Line $line $cols 1 $leftPad)
        }
        & $writeBytes @(0x1B,0x47,0x00, 0x1B,0x45,0x00)
    }

    & $writeStr $divider
    & $writeStr (Center-Line 'Please keep this number' $cols 1 $leftPad)
    $nowStr = (Get-Date).ToString('MM/dd/yyyy  hh:mm tt', [System.Globalization.CultureInfo]::InvariantCulture)
    & $writeStr (Center-Line $nowStr $cols 1 $leftPad)

    # Feed 5 lines + cut
    & $writeBytes @(0x0A,0x0A,0x0A,0x0A,0x0A, 0x1B,0x69, 0x1D,0x56,0x01)
    return $ms.ToArray()
}

function Resolve-PrinterCandidates {
    $list = New-Object System.Collections.Generic.List[string]
    $list.Add('POS-58')
    try {
        $printers = Get-CimInstance Win32_Printer -ErrorAction SilentlyContinue
        foreach ($p in $printers) {
            if ($p.Name -match 'POS|58|57|Thermal|Receipt|XP-|RONGTA|Xprinter') {
                if (-not $list.Contains($p.Name)) { $list.Add($p.Name) }
            }
        }
        $def = $printers | Where-Object { $_.Default -eq $true } | Select-Object -First 1
        if ($def -and -not $list.Contains($def.Name)) { $list.Add($def.Name) }
    } catch {}
    return $list
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add('http://127.0.0.1:18181/')
try {
    $listener.Start()
} catch {
    # Already listening on 18181
    exit 0
}

while ($listener.IsListening) {
    try {
        $ctx = $listener.GetContext()
        $req = $ctx.Request
        $res = $ctx.Response

        $res.Headers.Add('Access-Control-Allow-Origin', '*')
        $res.Headers.Add('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        $res.Headers.Add('Access-Control-Allow-Headers', 'Content-Type, Accept')
        $res.Headers.Add('Access-Control-Allow-Private-Network', 'true')
        $res.ContentType = 'application/json'

        if ($req.HttpMethod -eq 'OPTIONS') {
            $res.StatusCode = 200
            $res.Close()
            continue
        }

        if ($req.HttpMethod -eq 'GET') {
            $buf = [System.Text.Encoding]::UTF8.GetBytes('{"status":"online","printer":"POS-58","port":18181}')
            $res.StatusCode = 200
            $res.OutputStream.Write($buf, 0, $buf.Length)
            $res.Close()
            continue
        }

        $reader = New-Object System.IO.StreamReader($req.InputStream, [System.Text.Encoding]::UTF8)
        $body = $reader.ReadToEnd()
        $reader.Close()

        $data = $body | ConvertFrom-Json
        $displayNumber = [string]$data.display_number
        $meta = [string]$data.meta

        if ([string]::IsNullOrWhiteSpace($displayNumber)) {
            $buf = [System.Text.Encoding]::UTF8.GetBytes('{"ok":false,"error":"Missing display_number"}')
            $res.StatusCode = 400
            $res.OutputStream.Write($buf, 0, $buf.Length)
            $res.Close()
            continue
        }

        $bytes = Build-TicketBytes $displayNumber $meta
        $candidates = Resolve-PrinterCandidates
        $printed = $false
        $usedTarget = $null
        $lastErr = 0

        foreach ($cand in $candidates) {
            if ([KioskRawPrinter]::SendBytes($cand, $bytes, [ref]$lastErr)) {
                $printed = $true
                $usedTarget = $cand
                break
            }
        }

        $payload = @{
            ok     = $printed
            method = 'winspool_raw'
            target = $usedTarget
            error  = if ($printed) { $null } else { "Win32 error $lastErr" }
        } | ConvertTo-Json -Compress

        $buf = [System.Text.Encoding]::UTF8.GetBytes($payload)
        $res.StatusCode = if ($printed) { 200 } else { 500 }
        $res.OutputStream.Write($buf, 0, $buf.Length)
        $res.Close()
    } catch {
        try { $ctx.Response.Close() } catch {}
    }
}

