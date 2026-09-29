<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Livewire\Admin\Categories\CategoryManager;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Posts\PostForm;
use App\Livewire\Admin\Posts\PostIndex;
use App\Livewire\Admin\Settings\SettingsForm;
use App\Livewire\Admin\Users\UserManager;
use App\Livewire\Blog\PostList;
use App\Livewire\Profile\Edit as ProfileEdit;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/blog', PostList::class)->name('blog.index');
Route::get('/blog/category/{category}', PostList::class)->name('blog.category');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');

require __DIR__.'/auth.php';

// Self-service account page. Only requires a logged-in user (no CMS
// permission), so even an account with no role can manage its own details.
Route::get('/profile', ProfileEdit::class)->middleware('auth')->name('profile.edit');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Every route below requires an authenticated, verified, *active* user
| (EnsureUserIsNotBlocked runs globally on the web group — see
| bootstrap/app.php) who holds at least 'view dashboard'. Each sub-group
| then layers the specific permission it needs on top. The Livewire
| components themselves re-check authorization again for anything
| resource-specific (e.g. "is this post actually yours?") — the route
| middleware alone cannot know that.
*/

Route::middleware(['auth', 'verified', 'permission:view dashboard'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', Dashboard::class)->name('dashboard');

        Route::middleware('permission:manage own posts|manage all posts')->group(function () {
            Route::get('/posts', PostIndex::class)->name('posts.index');
            Route::get('/posts/create', PostForm::class)->name('posts.create');
            Route::get('/posts/{post}/edit', PostForm::class)->whereNumber('post')->name('posts.edit');
        });

        Route::middleware('permission:manage categories')->group(function () {
            Route::get('/categories', CategoryManager::class)->name('categories.index');
        });

        Route::middleware('permission:manage users')->group(function () {
            Route::get('/users', UserManager::class)->name('users.index');
        });

        Route::middleware('permission:manage settings')->group(function () {
            Route::get('/settings', SettingsForm::class)->name('settings');
        });
    });