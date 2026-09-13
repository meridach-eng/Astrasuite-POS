<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\ConfiguracionNegocio;
use App\Http\Middleware\VerificarSucursalActiva;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $appName = 'Astra POS';

        try {
            if (Schema::hasTable('configuracion_negocio')) {
                $config = ConfiguracionNegocio::first();
                if ($config && !empty($config->nombre_comercial)) {
                    $appName = $config->nombre_comercial;
                }
            }
        } catch (\Exception $e) {
            // Ignora errores si las migraciones aún no corren
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName($appName)
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('10rem')
            ->favicon(asset('images/logo.png'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\EstadisticasOverview::class,
                \App\Filament\Widgets\IngresosChart::class,
                \App\Filament\Widgets\ReportesDetalleWidget::class,
                \App\Filament\Widgets\CuentasPorCobrarStatsWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <style>
                        .fi-sidebar-header {
                            height: auto !important;
                            min-height: 6rem;
                            padding: 1.5rem 1rem !important;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        .fi-sidebar-header img {
                            max-height: 5rem !important;
                            width: auto !important;
                        }
                    </style>
                ')
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('
                    <div style="display: flex; align-items: center; gap: 8px; margin-right: 12px;">
                        @if(request()->routeIs(\'filament.admin.pages.pos-page\'))
                            <a href="{{ route(\'filament.admin.pages.dashboard\') }}" style="display: inline-flex; align-items: center; gap: 6px; background-color: #4f46e5; color: #ffffff; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                Inicio
                            </a>
                        @else
                            <a href="{{ route(\'filament.admin.pages.pos-page\') }}" style="display: inline-flex; align-items: center; gap: 6px; background-color: #059669; color: #ffffff; padding: 6px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                POS
                            </a>
                        @endif

                        @if(session("sucursal_activa_nombre"))
                            <a href="{{ route(\'filament.admin.pages.seleccionar-sucursal\') }}" class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 rounded-lg text-xs font-semibold hover:bg-amber-100 transition">
                                <x-heroicon-m-building-storefront class="w-4 h-4" />
                                <span>Sucursal: <strong>{{ session("sucursal_activa_nombre") }}</strong></span>
                            </a>
                        @endif
                    </div>
                ')
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                VerificarSucursalActiva::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
                VerificarSucursalActiva::class,
            ]);
    }
}