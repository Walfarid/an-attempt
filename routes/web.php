<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Dashboard\AnalyticsController;
use App\Http\Controllers\Dashboard\EducationController;
use App\Http\Controllers\Dashboard\ExperienceController;
use App\Http\Controllers\Dashboard\PostController;
use App\Http\Controllers\Dashboard\PostCoverController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\ProjectController;
use App\Http\Controllers\Dashboard\PublicationController;
use App\Http\Controllers\Dashboard\ScreenshotController;
use App\Http\Controllers\Dashboard\SkillController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

Route::get('posts', [BlogController::class, 'index'])->name('posts.index');
Route::get('posts/{post:slug}', [BlogController::class, 'show'])->name('posts.show');

Route::post('contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

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
        ->only(['index', 'store', 'update', 'destroy'])
        ->names('dashboard.posts');

    Route::put('dashboard/posts/{post}/cover', [PostCoverController::class, 'update'])->name('dashboard.posts.cover.update');
    Route::delete('dashboard/posts/{post}/cover', [PostCoverController::class, 'destroy'])->name('dashboard.posts.cover.destroy');

    Route::scopeBindings()->group(function () {
        Route::resource('dashboard/projects.screenshots', ScreenshotController::class)
            ->only(['store', 'destroy'])
            ->names('dashboard.projects.screenshots');
    });

    Route::get('dashboard/profile/edit', [ProfileController::class, 'edit'])->name('dashboard.profile.edit');
    Route::put('dashboard/profile', [ProfileController::class, 'update'])->name('dashboard.profile.update');

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
