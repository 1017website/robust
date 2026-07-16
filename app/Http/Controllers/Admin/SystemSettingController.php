<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\Logger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = [
            'company_name' => SystemSetting::value('company_name', 'ROBUST'),
            'company_tagline' => SystemSetting::value('company_tagline', 'Laboratory Furniture & Equipment'),
            'company_logo' => SystemSetting::assetUrl('company_logo'),
            'company_favicon' => SystemSetting::assetUrl('company_favicon'),
            'sales_monthly_target' => (float) SystemSetting::value('sales_monthly_target', 0),
        ];

        $commands = $this->allowedCommands();

        return view('admin.system-settings.index', compact('settings', 'commands'));
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:80'],
            'company_tagline' => ['nullable', 'string', 'max:140'],
            'company_logo' => ['nullable', 'file', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'],
            'company_favicon' => ['nullable', 'file', 'max:1024', 'mimes:ico,png,jpg,jpeg,webp,svg'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'sales_monthly_target' => ['nullable', 'numeric', 'min:0'],
        ]);

        SystemSetting::putValue('company_name', $data['company_name']);
        SystemSetting::putValue('company_tagline', $data['company_tagline'] ?? '');
        SystemSetting::putValue('sales_monthly_target', $data['sales_monthly_target'] ?? 0, 'number');

        if ($request->boolean('remove_logo')) {
            $this->deleteStoredFile('company_logo');
            SystemSetting::putValue('company_logo', null, 'file');
        }

        if ($request->hasFile('company_logo')) {
            $this->replaceStoredFile('company_logo', $request->file('company_logo')->store('system', 'public'));
        }

        if ($request->boolean('remove_favicon')) {
            $this->deleteStoredFile('company_favicon');
            SystemSetting::putValue('company_favicon', null, 'file');
        }

        if ($request->hasFile('company_favicon')) {
            $this->replaceStoredFile('company_favicon', $request->file('company_favicon')->store('system', 'public'));
        }

        Logger::record('updated', 'Branding sistem diperbarui dari menu System Settings');

        return back()->with('success', 'Branding perusahaan berhasil diperbarui. Jika favicon belum berubah, refresh browser dengan Ctrl+F5.');
    }

    public function runCommand(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'command' => ['required', Rule::in(array_keys($this->allowedCommands()))],
        ]);

        $command = $data['command'];
        $definition = $this->allowedCommands()[$command];

        try {
            $exitCode = Artisan::call($command, $definition['parameters']);
            $output = trim(Artisan::output());

            Logger::record('artisan_command', "Menjalankan php artisan {$command}", null, [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            $message = $exitCode === 0
                ? "Command php artisan {$command} berhasil dijalankan."
                : "Command php artisan {$command} selesai dengan exit code {$exitCode}.";

            return back()
                ->with($exitCode === 0 ? 'success' : 'error', $message)
                ->with('artisan_output', $output ?: '(Tidak ada output dari command.)')
                ->with('artisan_command', "php artisan {$command}");
        } catch (Throwable $e) {
            Logger::record('artisan_command_failed', "Gagal menjalankan php artisan {$command}", null, [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', "Gagal menjalankan php artisan {$command}: {$e->getMessage()}")
                ->with('artisan_output', $e->getMessage())
                ->with('artisan_command', "php artisan {$command}");
        }
    }

    protected function allowedCommands(): array
    {
        return [
            'migrate' => [
                'label' => 'Migrate Database',
                'command' => 'php artisan migrate --force',
                'description' => 'Menjalankan migration yang belum dijalankan. Gunakan setelah update patch yang menambah tabel/kolom.',
                'icon' => 'bi-database-check',
                'button' => 'Jalankan Migrate',
                'variant' => 'primary',
                'parameters' => ['--force' => true],
            ],
            'storage:link' => [
                'label' => 'Storage Link',
                'command' => 'php artisan storage:link',
                'description' => 'Membuat symbolic link public/storage agar upload logo, favicon, dan lampiran bisa diakses browser.',
                'icon' => 'bi-link-45deg',
                'button' => 'Buat Storage Link',
                'variant' => 'success',
                'parameters' => [],
            ],
            'optimize:clear' => [
                'label' => 'Clear Cache',
                'command' => 'php artisan optimize:clear',
                'description' => 'Membersihkan cache config, route, view, dan cache aplikasi setelah deploy/revisi.',
                'icon' => 'bi-stars',
                'button' => 'Clear Cache',
                'variant' => 'warning',
                'parameters' => [],
            ],
        ];
    }

    protected function replaceStoredFile(string $key, string $path): void
    {
        $this->deleteStoredFile($key);
        SystemSetting::putValue($key, $path, 'file');
    }

    protected function deleteStoredFile(string $key): void
    {
        $old = SystemSetting::value($key);
        if ($old && ! str_starts_with($old, 'http://') && ! str_starts_with($old, 'https://') && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
    }
}
