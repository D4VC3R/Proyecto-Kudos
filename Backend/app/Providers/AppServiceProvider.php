<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemComment;
use App\Models\Proposal;
use App\Models\Vote;
use App\Policies\CategoryPolicy;
use App\Policies\ItemPolicy;
use App\Policies\ItemCommentPolicy;
use App\Policies\ProposalPolicy;
use App\Policies\VotePolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
		//
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot(): void
	{
		ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
			return config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
		});

		VerifyEmail::createUrlUsing(function (object $notifiable) {
			$verifyUrl = URL::temporarySignedRoute(
					'verification.verify',
					Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
					[
							'id' => $notifiable->getKey(),
							'hash' => sha1($notifiable->getEmailForVerification()),
					]
			);

			// Parse the generated backend URL to replace it with the frontend URL
			return config('app.frontend_url') . '/verify-email?verify_url=' . urlencode($verifyUrl);
		});

		Gate::policy(Item::class, ItemPolicy::class);
		Gate::policy(ItemComment::class, ItemCommentPolicy::class);
		Gate::policy(Category::class, CategoryPolicy::class);
		Gate::policy(Proposal::class, ProposalPolicy::class);
		Gate::policy(Vote::class, VotePolicy::class);
	}
}
