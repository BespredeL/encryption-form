<?php

use Illuminate\Support\Str;

if (!function_exists('encryption_form_enabled')) {

    /**
     * Check if encryption form is enabled
     *
     * @return bool
     */
    function encryption_form_enabled(): bool
    {
        $enabled = config('encryption-form.enabled', true);

        if (!$enabled) {
            return false;
        }

        $ip = request()?->ip();
        $skip = config('encryption-form.skip_for_ips', []);

        foreach ($skip as $pattern) {
            if (Str::is($pattern, $ip)) {
                return false;
            }
        }

        return true;
    }
}