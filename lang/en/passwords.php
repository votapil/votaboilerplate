<?php

declare(strict_types=1);

/*
 * The first five keys are the password broker's status codes and their names
 * are fixed by the framework. The rest are used by
 * App\Notifications\ResetPasswordNotification.
 */

return [

    'reset' => 'Your password has been reset.',
    'sent' => 'We have emailed your password reset link.',
    'throttled' => 'Please wait before retrying.',
    'token' => 'This password reset token is invalid.',
    'user' => "We can't find a user with that email address.",

    'reset_subject' => 'Reset your password',
    'reset_line' => 'You are receiving this email because we received a password reset request for your account.',
    'reset_action' => 'Reset password',
    'reset_ignore' => 'If you did not request a password reset, no further action is required.',

];
