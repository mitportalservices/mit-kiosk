<?php

return [
    'pc_name' => env('PRINTER_PC_NAME', '127.0.0.1'),
    'share_name' => env('PRINTER_SHARE_NAME', 'POS-58'),
    'enabled' => filter_var(env('PRINTER_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'orientation' => env('PRINTER_ORIENTATION', 'portrait'),
    // Characters per line for ESC/POS tickets. 57mm paper ~ 30 (max 32 on 58mm).
    'chars_per_line' => (int) env('PRINTER_CHARS_PER_LINE', 30),
    // Extra space characters added on the left of every line for fine-tuning centering on 57mm paper.
    'left_pad' => (int) env('PRINTER_LEFT_PAD', 0),
    // Timezone used for the date/time printed on tickets.
    'timezone' => env('PRINTER_TIMEZONE', 'Asia/Manila'),
    'browser_print_fallback' => filter_var(env('KIOSK_BROWSER_PRINT_FALLBACK', false), FILTER_VALIDATE_BOOLEAN),
];
