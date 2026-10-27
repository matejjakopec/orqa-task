<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Audit\AuditWriterInterface;
use App\Contracts\Files\FileProcessingServiceInterface;
use App\Contracts\Files\FileRepositoryInterface;
use App\Contracts\Files\FileServiceInterface;
use App\Contracts\Files\FileStorageInterface;
use App\Contracts\Files\MalwareScannerInterface;
use App\Contracts\Files\QuotaServiceInterface;
use App\Contracts\Files\StorageAccountRepositoryInterface;
use App\Contracts\Files\StorageRepairServiceInterface;
use App\Contracts\Organisations\OrganisationRepositoryInterface;
use App\Contracts\Organisations\OrganisationServiceInterface;
use App\Contracts\Users\ProfileServiceInterface;
use App\Contracts\Users\UserAdministrationServiceInterface;
use App\Contracts\Users\UserRepositoryInterface;
use App\Enums\RoleName;
use App\Infrastructure\Malware\ClamAvScanner;
use App\Infrastructure\Storage\LaravelFileStorage;
use App\Models\Organisation;
use App\Models\StoredFile;
use App\Models\User;
use App\Policies\OrganisationPolicy;
use App\Policies\StoredFilePolicy;
use App\Policies\UserPolicy;
use App\Repositories\Eloquent\EloquentFileRepository;
use App\Repositories\Eloquent\EloquentOrganisationRepository;
use App\Repositories\Eloquent\EloquentStorageAccountRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\Audit\DatabaseAuditWriter;
use App\Services\Files\FileProcessingService;
use App\Services\Files\FileService;
use App\Services\Files\QuotaService;
use App\Services\Files\StorageRepairService;
use App\Services\Organisations\OrganisationService;
use App\Services\Users\ProfileService;
use App\Services\Users\UserAdministrationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class ApplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileRepositoryInterface::class, EloquentFileRepository::class);
        $this->app->bind(FileServiceInterface::class, FileService::class);
        $this->app->bind(FileStorageInterface::class, LaravelFileStorage::class);
        $this->app->bind(MalwareScannerInterface::class, ClamAvScanner::class);
        $this->app->bind(FileProcessingServiceInterface::class, FileProcessingService::class);
        $this->app->bind(StorageAccountRepositoryInterface::class, EloquentStorageAccountRepository::class);
        $this->app->bind(QuotaServiceInterface::class, QuotaService::class);
        $this->app->bind(StorageRepairServiceInterface::class, StorageRepairService::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(OrganisationRepositoryInterface::class, EloquentOrganisationRepository::class);
        $this->app->bind(UserAdministrationServiceInterface::class, UserAdministrationService::class);
        $this->app->bind(ProfileServiceInterface::class, ProfileService::class);
        $this->app->bind(OrganisationServiceInterface::class, OrganisationService::class);
        $this->app->bind(AuditWriterInterface::class, DatabaseAuditWriter::class);
    }

    public function boot(): void
    {
        View::composer('components.navigation', function (ViewContract $view): void {
            $actor = Auth::user();
            $actor?->loadMissing('roles');
            $view->with('hasAdminRole', $actor?->hasRole(RoleName::Admin) ?? false);
            $view->with('hasSuperadminRole', $actor?->hasRole(RoleName::Superadmin) ?? false);
            $view->with('hasFileAccess', $actor?->can('viewAny', StoredFile::class) ?? false);
        });

        foreach (['file-uploads' => 10, 'file-downloads' => 60, 'file-deletions' => 30, 'file-status' => 60] as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute($attempts)->by((string) $request->user()->id));
        }

        Gate::policy(StoredFile::class, StoredFilePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Organisation::class, OrganisationPolicy::class);
    }
}
