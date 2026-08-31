<?php

declare(strict_types=1);

/*
 * Authentication and authorization messages.
 *
 * Every locale directory must contain the same keys. A message that exists in
 * one language and not another does not fail loudly — Laravel falls back to
 * printing the key itself, so users see "auth.forbidden" in the interface. Add
 * a key here and add it to every other locale in the same commit.
 */

return [

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'forbidden' => 'You do not have permission to perform this action.',
    'logged_out' => 'You have been signed out.',
    'account_deleted' => 'Your account and all of its data have been permanently deleted.',
    'reset_link_sent' => 'If an account with that email exists, a password reset link has been sent.',

];
