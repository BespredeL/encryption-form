<?php

declare(strict_types=1);

namespace Bespredel\EncryptionForm\Support;

use Illuminate\Support\Facades\File;

class EnvEditor
{
    /**
     * Upsert variables to the .env file.
     *
     * @param array $variables Variables to upsert
     *
     * @return bool
     */
    public function upsert(array $variables): bool
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return false;
        }

        $envContent = File::get($envPath);

        foreach ($variables as $key => $value) {
            $envContent = $this->replaceOrAppendVariable($envContent, $key, $this->quoteEnvValue($value));
        }

        return File::put($envPath, $envContent) !== false;
    }

    /**
     * Replace or append a variable to the .env file.
     *
     * @param string $envContent  Content of the .env file
     * @param string $key         Key to replace or append
     * @param string $quotedValue Quoted value to replace or append
     *
     * @return string
     */
    private function replaceOrAppendVariable(string $envContent, string $key, string $quotedValue): string
    {
        $line = $key . '=' . $quotedValue;
        $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

        if (preg_match($pattern, $envContent) === 1) {
            return (string)preg_replace($pattern, $line, $envContent, 1);
        }

        return rtrim($envContent) . PHP_EOL . $line . PHP_EOL;
    }

    /**
     * Quote the environment value.
     *
     * @param string $value Value to quote
     *
     * @return string
     */
    private function quoteEnvValue(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $value);
        $escaped = addcslashes($normalized, "\\\"\n");

        return '"' . $escaped . '"';
    }
}
