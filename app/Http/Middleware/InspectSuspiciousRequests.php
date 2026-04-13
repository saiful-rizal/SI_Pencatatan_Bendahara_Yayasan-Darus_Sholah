<?php

namespace App\Http\Middleware;

use App\Models\SecurityIncident;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InspectSuspiciousRequests
{
    /**
     * @var array<int, string>
     */
    private array $patterns = [
        '/<\s*script\b/i',
        '/<\s*iframe\b/i',
        '/<\s*object\b/i',
        '/<\s*embed\b/i',
        '/javascript\s*:/i',
        '/data\s*:\s*text\/html/i',
        '/on[a-z]+\s*=/i',
        '/\.\.[\\/]/',
        '/%00/i',
        '/\bunion\b\s+\bselect\b/i',
        '/\bdrop\b\s+\btable\b/i',
        '/\binsert\b\s+\binto\b/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isWriteMethod($request) && $this->containsSuspiciousContent($request->all())) {
            $reason = 'Suspicious request blocked by pattern inspection';
            $payloadExcerpt = $this->makePayloadExcerpt($request->all());

            SecurityIncident::create([
                'user_id' => optional($request->user())->id,
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
                'reason' => $reason,
                'payload_excerpt' => $payloadExcerpt,
            ]);

            Log::warning('Suspicious request blocked', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'path' => $request->path(),
                'user_id' => optional($request->user())->id,
                'user_agent' => $request->userAgent(),
            ]);

            abort(403, 'Permintaan diblokir oleh perlindungan keamanan aplikasi.');
        }

        return $next($request);
    }

    private function isWriteMethod(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function containsSuspiciousContent(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsSuspiciousContent($item)) {
                    return true;
                }
            }

            return false;
        }

        if ($value instanceof UploadedFile || is_object($value)) {
            return false;
        }

        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);

        if ($value === '') {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }

    private function makePayloadExcerpt(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return '[unserializable payload]';
        }

        return mb_substr($json, 0, 500);
    }
}
