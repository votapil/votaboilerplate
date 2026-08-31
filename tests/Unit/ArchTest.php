<?php

/**
 * Architecture tests come free with Pest and cost nothing to run. Two of them earn
 * their place from the first commit; the rest of the ruleset is for a project to
 * add once it has layers worth defending.
 */
arch('debug helpers never reach a commit')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray'])
    ->not->toBeUsed();

arch('env() is only read in config files')
    // Anywhere else it returns null the moment `config:cache` runs — and config is
    // always cached in production, so the failure appears only after deploy.
    ->expect('env')
    ->not->toBeUsed();
