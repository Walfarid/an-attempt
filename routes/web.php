<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\EducationController;
use App\Http\Controllers\Dashboard\ExperienceController;
use App\Http\Controllers\Dashboard\GuideCoverController;
use App\Http\Controllers\Dashboard\MediaController;
use App\Http\Controllers\Dashboard\PostController;
use App\Http\Controllers\Dashboard\PostCoverController;
use App\Http\Controllers\Dashboard\PrivacyPolicyController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\ProjectController;
use App\Http\Controllers\Dashboard\PublicationController;
use App\Http\Controllers\Dashboard\ScreenshotController;
use App\Http\Controllers\Dashboard\SkillController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PrivacyController;
use App\Http\Middleware\CachePublicResponses;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::get('/', [HomeController::class, 'index'])
    ->middleware(CachePublicResponses::class)
    ->name('home');

Route::get('sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

Route::get('posts', [BlogController::class, 'index'])
    ->middleware(CachePublicResponses::class)
    ->name('posts.index')
    ->withHead(title: 'Blog', description: 'Thoughts on software development, APIs, and deployment platforms.');
Route::get('posts/tag/{tag}', [BlogController::class, 'tag'])
    ->middleware(CachePublicResponses::class)
    ->name('posts.tag')
    ->where('tag', '[a-z0-9-]+');
Route::get('posts/{post}', [BlogController::class, 'show'])
    ->middleware(CachePublicResponses::class)
    ->name('posts.show');

Route::get('guides', [GuideController::class, 'index'])
    ->middleware(CachePublicResponses::class)
    ->name('guides.index')
    ->withHead(title: 'Guides', description: 'Step-by-step tutorials and how-to guides.');

Route::get('guides/{guide}', [GuideController::class, 'show'])
    ->middleware(CachePublicResponses::class)
    ->name('guides.show');

Route::get('privacy', [PrivacyController::class, 'show'])
    ->middleware(CachePublicResponses::class)
    ->name('privacy')
    ->withHead(title: 'Privacy', description: 'What this site collects, which third-party analytics it uses, and how to change your consent choice.');

Route::middleware([
    'auth',
    ValidateSessionWithWorkOS::class,
])->group(function () {
    Route::get('dashboard', [AnalyticsController::class, 'index'])->name('dashboard');
    Route::post('analytics/clicks', [AnalyticsController::class, 'storeClick'])
        ->middleware('throttle:60,1')
        ->name('analytics.clicks.store');

    Route::resource('dashboard/projects', ProjectController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('dashboard.projects');

    Route::resource('dashboard/posts', PostController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->names('dashboard.posts');

    Route::put('dashboard/posts/{post}/cover', [PostCoverController::class, 'update'])->name('dashboard.posts.cover.update');
    Route::delete('dashboard/posts/{post}/cover', [PostCoverController::class, 'destroy'])->name('dashboard.posts.cover.destroy');

    Route::resource('dashboard/guides', App\Http\Controllers\Dashboard\GuideController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy'])
        ->names('dashboard.guides');

    Route::put('dashboard/guides/{guide}/cover', [GuideCoverController::class, 'update'])->name('dashboard.guides.cover.update');
    Route::delete('dashboard/guides/{guide}/cover', [GuideCoverController::class, 'destroy'])->name('dashboard.guides.cover.destroy');

    Route::resource('dashboard/media', MediaController::class)
        ->only(['index', 'store', 'destroy'])
        ->names('dashboard.media');

    Route::scopeBindings()->group(function () {
        Route::resource('dashboard/projects.screenshots', ScreenshotController::class)
            ->only(['store', 'destroy'])
            ->names('dashboard.projects.screenshots');
    });

    Route::get('dashboard/profile/edit', [ProfileController::class, 'edit'])->name('dashboard.profile.edit');
    Route::put('dashboard/profile', [ProfileController::class, 'update'])->name('dashboard.profile.update');

    Route::get('dashboard/privacy/edit', [PrivacyPolicyController::class, 'edit'])->name('dashboard.privacy.edit');
    Route::put('dashboard/privacy', [PrivacyPolicyController::class, 'update'])->name('dashboard.privacy.update');

    Route::resource('dashboard/skills', SkillController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('dashboard.skills');

    Route::resource('dashboard/educations', EducationController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('dashboard.educations');

    Route::resource('dashboard/publications', PublicationController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('dashboard.publications');

    Route::resource('dashboard/experience', ExperienceController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('dashboard.experience');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
