<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Traits\HelperTrait;
use Http;

class RecaptchaService
{
    use HelperTrait;

    public function validate(string $token): bool
    {
        $response = Http::asForm()->post("https://www.google.com/recaptcha/api/siteverify", [
            'secret' => config('recaptcha.secret_key'),
            'response' => $token,
            'ip' => request()->ip(),
        ]);

        if (
            $response->successful()
            && $response->json('success')
            && $response->json('score') >= 0.7
        ) {
            return true;
        }

        return false;
    }
}
