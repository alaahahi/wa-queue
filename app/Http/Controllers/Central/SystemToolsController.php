<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SystemToolsController extends Controller
{
    public function logs(Request $request): JsonResponse
    {
        $path = storage_path('logs/laravel.log');
        $lines = min(1000, max(50, (int) $request->get('lines', 300)));

        if (! file_exists($path)) {
            return response()->json([
                'content' => '',
                'path' => $path,
                'exists' => false,
                'size' => 0,
                'updated_at' => null,
            ]);
        }

        return response()->json([
            'content' => $this->tailFile($path, $lines),
            'path' => $path,
            'exists' => true,
            'size' => filesize($path),
            'updated_at' => date('c', filemtime($path)),
        ]);
    }

    public function migrateCentral(): JsonResponse
    {
        return $this->runArtisan('migrate', ['--force' => true]);
    }

    public function migrateTenants(Request $request): JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        $params = ['--force' => true];

        if ($tenantId) {
            $params['--tenants'] = [$tenantId];
        }

        return $this->runArtisan('tenants:migrate', $params);
    }

    public function status(): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');

        return response()->json([
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'tenants_count' => Tenant::query()->count(),
            'log_exists' => file_exists($logPath),
            'log_size' => file_exists($logPath) ? filesize($logPath) : 0,
            'php_version' => PHP_VERSION,
        ]);
    }

    private function runArtisan(string $command, array $parameters = []): JsonResponse
    {
        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = trim(Artisan::output());

            return response()->json([
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'command' => $command,
                'output' => $output !== '' ? $output : 'تم التنفيذ بنجاح',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'exit_code' => 1,
                'command' => $command,
                'output' => $e->getMessage(),
            ], 500);
        }
    }

    private function tailFile(string $path, int $lines): string
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $start = max(0, $lastLine - $lines);
        $buffer = [];

        $file->seek($start);
        while (! $file->eof()) {
            $line = $file->current();
            if ($line !== false && $line !== '') {
                $buffer[] = rtrim($line, "\r\n");
            }
            $file->next();
        }

        return implode("\n", $buffer);
    }
}
